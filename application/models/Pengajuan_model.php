<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pengajuan_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $result = $this->db->insert('presensi_pengajuan', $data);
        if (!$result) {
            return false;
        }
        return $this->db->insert_id();
    }

    public function get_by_id($id)
    {
        return $this->db->select('p.*, j.nama_izin, j.status_presensi, j.kode_izin')
            ->from('presensi_pengajuan p')
            ->join('presensi_jenis_izin j', 'p.id_jenis_izin = j.id_jenis', 'left')
            ->where('p.id_pengajuan', $id)
            ->get()
            ->row();
    }

    public function get_by_user($id_user)
    {
        return $this->db->select('p.*, j.nama_izin')
            ->from('presensi_pengajuan p')
            ->join('presensi_jenis_izin j', 'p.id_jenis_izin = j.id_jenis', 'left')
            ->where('p.id_user', $id_user)
            ->order_by('p.created_at', 'DESC')
            ->get()
            ->result();
    }

    public function get_pending_all()
    {
        return $this->db->select('p.*, u.first_name, u.last_name, j.nama_izin, j.status_presensi')
            ->from('presensi_pengajuan p')
            ->join('users u', 'p.id_user = u.id')
            ->join('presensi_jenis_izin j', 'p.id_jenis_izin = j.id_jenis', 'left')
            ->where('p.status', 'Pending')
            ->order_by('p.created_at', 'ASC')
            ->get()
            ->result();
    }

    public function get_all_pending()
    {
        return $this->get_pending_all();
    }

    public function update_status($id, $status, $approver_id, $reason = null)
    {
        $data = [
            'status' => $status,
            'approved_by' => $approver_id,
            'approved_at' => date('Y-m-d H:i:s'),
            'alasan_tolak' => $reason
        ];
        
        $this->db->where('id_pengajuan', $id);
        $result = $this->db->update('presensi_pengajuan', $data);
        
        if ($result && $status === 'Disetujui') {
            $this->syncToAbsensiLogs($id);
        }
        
        return $result;
    }

    public function syncToAbsensiLogs($id_pengajuan)
    {
        $pengajuan = $this->get_by_id($id_pengajuan);
        if (!$pengajuan || $pengajuan->status !== 'Disetujui') {
            return false;
        }

        if ($pengajuan->tipe_pengajuan === 'IzinKeluar') {
            return $this->syncIzinKeluarToAbsensiLog($id_pengajuan, $pengajuan);
        }

        $status_presensi = $this->determineAbsensiStatus($pengajuan);
        if (!$status_presensi) {
            return false;
        }

        $this->db->trans_start();

        $start = new DateTime($pengajuan->tgl_mulai);
        $end = new DateTime($pengajuan->tgl_selesai);
        $end->modify('+1 day');

        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end);

        foreach ($period as $date) {
            $tanggal = $date->format('Y-m-d');
            $this->upsertAbsensiLog($pengajuan->id_user, $tanggal, $status_presensi, $id_pengajuan, $pengajuan);
        }

        $this->db->where('id_pengajuan', $id_pengajuan)
            ->update('presensi_pengajuan', [
                'is_synced' => 1,
                'synced_at' => date('Y-m-d H:i:s')
            ]);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function syncIzinKeluarToAbsensiLog($id_pengajuan, $pengajuan = null)
    {
        if (!$pengajuan) {
            $pengajuan = $this->get_by_id($id_pengajuan);
        }
        
        if (!$pengajuan || $pengajuan->tipe_pengajuan !== 'IzinKeluar' || $pengajuan->status !== 'Disetujui') {
            return false;
        }

        $tanggal = $pengajuan->tgl_mulai;
        $jam_keluar = $pengajuan->jam_selesai;

        $existing = $this->db->where('id_user', $pengajuan->id_user)
            ->where('tanggal', $tanggal)
            ->get('presensi_logs')
            ->row();

        if (!$existing || !$existing->jam_masuk) {
            return false;
        }

        $this->load->model('Presensi_model', 'presensi');
        $shift = $this->presensi->getShiftById($existing->id_shift);
        
        $pulang_awal_menit = 0;
        if ($shift && $jam_keluar) {
            $jam_pulang_shift = strtotime($shift->jam_pulang);
            $jam_keluar_actual = strtotime($jam_keluar);
            
            if ($jam_keluar_actual < $jam_pulang_shift) {
                $pulang_awal_menit = round(($jam_pulang_shift - $jam_keluar_actual) / 60);
            }
        }

        $alasan = '';
        if (!empty($pengajuan->nama_izin)) {
            $alasan = $pengajuan->nama_izin;
        }
        if (!empty($pengajuan->keterangan)) {
            $alasan .= ($alasan ? ' - ' : '') . $pengajuan->keterangan;
        }

        $status = $existing->status_kehadiran;
        if ($status === 'Hadir' && $pulang_awal_menit > 0) {
            $status = 'Pulang Awal';
        } elseif ($status === 'Terlambat' && $pulang_awal_menit > 0) {
            $status = 'Terlambat + Pulang Awal';
        }

        $this->db->where('id_log', $existing->id_log)
            ->update('presensi_logs', [
                'jam_pulang' => $tanggal . ' ' . $jam_keluar,
                'status_kehadiran' => $status,
                'pulang_awal_menit' => $pulang_awal_menit,
                'id_pengajuan' => $id_pengajuan,
                'keterangan' => $alasan,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        $this->db->where('id_pengajuan', $id_pengajuan)
            ->update('presensi_pengajuan', [
                'is_synced' => 1,
                'synced_at' => date('Y-m-d H:i:s')
            ]);

        return true;
    }

    private function determineAbsensiStatus($pengajuan)
    {
        if ($pengajuan->tipe_pengajuan === 'Lembur') {
            return null;
        }

        if (!empty($pengajuan->status_presensi)) {
            return $pengajuan->status_presensi;
        }

        $mapping = [
            'Izin' => 'Izin',
            'Sakit' => 'Sakit',
            'Cuti' => 'Cuti',
            'Dinas' => 'Dinas Luar'
        ];

        return isset($mapping[$pengajuan->tipe_pengajuan]) 
            ? $mapping[$pengajuan->tipe_pengajuan] 
            : 'Izin';
    }

    private function upsertAbsensiLog($id_user, $tanggal, $status, $id_pengajuan, $pengajuan)
    {
        $existing = $this->db->where('id_user', $id_user)
            ->where('tanggal', $tanggal)
            ->get('presensi_logs')
            ->row();

        $data = [
            'status_kehadiran' => $status,
            'id_pengajuan' => $id_pengajuan,
            'keterangan' => $pengajuan->keterangan,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($existing) {
            if ($existing->jam_masuk !== null) {
                return;
            }
            
            $this->db->where('id_log', $existing->id_log)
                ->update('presensi_logs', $data);
        } else {
            $data['id_user'] = $id_user;
            $data['tanggal'] = $tanggal;
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('presensi_logs', $data);
        }
    }

    public function syncLemburToAbsensiLog($id_pengajuan)
    {
        $pengajuan = $this->get_by_id($id_pengajuan);
        if (!$pengajuan || $pengajuan->tipe_pengajuan !== 'Lembur' || $pengajuan->status !== 'Disetujui') {
            return false;
        }

        $lembur_menit = $this->calculateLemburMinutes($pengajuan->jam_mulai, $pengajuan->jam_selesai);

        $this->db->trans_start();

        $start = new DateTime($pengajuan->tgl_mulai);
        $end = new DateTime($pengajuan->tgl_selesai);
        $end->modify('+1 day');

        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end);

        foreach ($period as $date) {
            $tanggal = $date->format('Y-m-d');
            $existing = $this->db->where('id_user', $pengajuan->id_user)
                ->where('tanggal', $tanggal)
                ->get('presensi_logs')
                ->row();

            if ($existing) {
                $this->db->where('id_log', $existing->id_log)
                    ->update('presensi_logs', [
                        'lembur_menit' => $lembur_menit,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
            }
        }

        $this->db->where('id_pengajuan', $id_pengajuan)
            ->update('presensi_pengajuan', [
                'is_synced' => 1,
                'synced_at' => date('Y-m-d H:i:s')
            ]);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    private function calculateLemburMinutes($jam_mulai, $jam_selesai)
    {
        if (empty($jam_mulai) || empty($jam_selesai)) {
            return 0;
        }

        $start = strtotime($jam_mulai);
        $end = strtotime($jam_selesai);

        if ($end <= $start) {
            $end += 86400;
        }

        return round(($end - $start) / 60);
    }

    public function get_jenis_izin()
    {
        return $this->db->where('is_active', 1)
            ->get('presensi_jenis_izin')
            ->result();
    }

    public function get_by_date_range($id_user, $start_date, $end_date)
    {
        return $this->db->select('p.*, j.nama_izin, j.status_presensi')
            ->from('presensi_pengajuan p')
            ->join('presensi_jenis_izin j', 'p.id_jenis_izin = j.id_jenis', 'left')
            ->where('p.id_user', $id_user)
            ->where('p.status', 'Disetujui')
            ->group_start()
                ->where('p.tgl_mulai <=', $end_date)
                ->where('p.tgl_selesai >=', $start_date)
            ->group_end()
            ->get()
            ->result();
    }

    public function has_approved_leave($id_user, $date)
    {
        return $this->db->from('presensi_pengajuan')
            ->where('id_user', $id_user)
            ->where('status', 'Disetujui')
            ->where('tgl_mulai <=', $date)
            ->where('tgl_selesai >=', $date)
            ->where_in('tipe_pengajuan', ['Izin', 'Sakit', 'Cuti', 'Dinas'])
            ->count_all_results() > 0;
    }

    public function get_approved_leaves_batch($user_ids, $date)
    {
        if ( ! has_where_in_values($user_ids)) {
            return [];
        }
        $user_ids = ci_where_in_values($user_ids);
        
        return $this->db->select('id_user')
            ->from('presensi_pengajuan')
            ->where_in('id_user', $user_ids)
            ->where('status', 'Disetujui')
            ->where('tgl_mulai <=', $date)
            ->where('tgl_selesai >=', $date)
            ->where_in('tipe_pengajuan', ['Izin', 'Sakit', 'Cuti', 'Dinas'])
            ->get()
            ->result_array();
    }
}
