<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pembayaran_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('datatables');
    }

    // ==========================================
    // CONFIG METHODS
    // ==========================================

    public function getConfig()
    {
        return $this->db->get('pembayaran_config')->row();
    }

    public function updateConfig($data)
    {
        $config = $this->getConfig();
        if ($config) {
            $this->db->where('id_config', $config->id_config);
            return $this->db->update('pembayaran_config', $data);
        }
        return $this->db->insert('pembayaran_config', $data);
    }

    // ==========================================
    // JENIS TAGIHAN METHODS
    // ==========================================

    public function getAllJenisTagihan($active_only = true)
    {
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        $this->db->order_by('kode_jenis', 'ASC');
        return $this->db->get('pembayaran_jenis')->result();
    }

    public function getJenisTagihanById($id)
    {
        return $this->db->get_where('pembayaran_jenis', ['id_jenis' => $id])->row();
    }

    public function getJenisTagihanByKode($kode)
    {
        return $this->db->get_where('pembayaran_jenis', ['kode_jenis' => $kode])->row();
    }

    public function createJenisTagihan($data)
    {
        $this->db->insert('pembayaran_jenis', $data);
        return $this->db->insert_id();
    }

    public function updateJenisTagihan($id, $data)
    {
        $this->db->where('id_jenis', $id);
        return $this->db->update('pembayaran_jenis', $data);
    }

    public function deleteJenisTagihan($id)
    {
        $this->db->where('id_jenis', $id);
        $count = $this->db->count_all_results('pembayaran_tagihan');
        if ($count > 0) {
            return false;
        }
        $this->db->where('id_jenis', $id);
        return $this->db->delete('pembayaran_jenis');
    }

    public function getDataTableJenisTagihan()
    {
        $this->datatables->select('id_jenis, kode_jenis, nama_jenis, nominal_default, is_recurring, is_active');
        $this->datatables->from('pembayaran_jenis');
        return $this->datatables->generate();
    }

    // ==========================================
    // TAGIHAN METHODS
    // ==========================================

    public function generateKodeTagihan()
    {
        $prefix = 'TG-' . date('Ym') . '-';
        $this->db->select_max('kode_tagihan');
        $this->db->like('kode_tagihan', $prefix, 'after');
        $row = $this->db->get('pembayaran_tagihan')->row();

        if ($row && $row->kode_tagihan) {
            $last_num = (int) substr($row->kode_tagihan, -5);
            $new_num = str_pad($last_num + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $new_num = '00001';
        }
        return $prefix . $new_num;
    }

    public function getTagihanById($id)
    {
        $this->db->select('t.*, j.kode_jenis, j.nama_jenis, s.nama as nama_siswa, s.nis, s.nisn');
        $this->db->from('pembayaran_tagihan t');
        $this->db->join('pembayaran_jenis j', 't.id_jenis = j.id_jenis');
        $this->db->join('master_siswa s', 't.id_siswa = s.id_siswa');
        $this->db->where('t.id_tagihan', $id);
        return $this->db->get()->row();
    }

    public function getTagihanBySiswa($id_siswa, $status = null)
    {
        $this->db->select('t.*, j.kode_jenis, j.nama_jenis');
        $this->db->from('pembayaran_tagihan t');
        $this->db->join('pembayaran_jenis j', 't.id_jenis = j.id_jenis');
        $this->db->where('t.id_siswa', $id_siswa);
        if ($status) {
            if (is_array($status)) {
                $this->db->where_in('t.status', $status);
            } else {
                $this->db->where('t.status', $status);
            }
        }
        $this->db->order_by('t.jatuh_tempo', 'ASC');
        return $this->db->get()->result();
    }

    public function createTagihanBatch($data_array)
    {
        // Get last code once to prevent duplicate keys in batch
        $prefix = 'TG-' . date('Ym') . '-';
        $this->db->select_max('kode_tagihan');
        $this->db->like('kode_tagihan', $prefix, 'after');
        $row = $this->db->get('pembayaran_tagihan')->row();
        
        $last_num = 0;
        if ($row && $row->kode_tagihan) {
            $last_num = (int) substr($row->kode_tagihan, -5);
        }

        foreach ($data_array as &$data) {
            if (empty($data['kode_tagihan'])) {
                $last_num++;
                $new_num = str_pad($last_num, 5, '0', STR_PAD_LEFT);
                $data['kode_tagihan'] = $prefix . $new_num;
            }
        }

        return $this->db->insert_batch('pembayaran_tagihan', $data_array);
    }

    public function updateTagihan($id, $data)
    {
        $this->db->where('id_tagihan', $id);
        return $this->db->update('pembayaran_tagihan', $data);
    }

    public function deleteTagihan($ids)
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        // Check for dependencies
        $this->db->where_in('id_tagihan', $ids);
        $count = $this->db->count_all_results('pembayaran_transaksi');
        
        if ($count > 0) {
            return false;
        }

        $this->db->where_in('id_tagihan', $ids);
        return $this->db->delete('pembayaran_tagihan');
    }

    public function checkTagihanExists($id_siswa, $id_jenis, $bulan, $tahun, $id_tp, $id_smt)
    {
        $this->db->where([
            'id_siswa' => $id_siswa,
            'id_jenis' => $id_jenis,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'id_tp' => $id_tp,
            'id_smt' => $id_smt
        ]);
        return $this->db->get('pembayaran_tagihan')->row();
    }

    public function getDataTableTagihan($id_tp, $id_smt, $filters = [])
    {
        $this->db->query("SET SQL_BIG_SELECTS=1");
        $this->datatables->select("
            t.id_tagihan, t.kode_tagihan, t.bulan, t.tahun,
            s.nama as nama_siswa, s.nis,
            k.nama_kelas,
            j.nama_jenis,
            t.nominal, t.diskon, t.denda, t.total,
            t.jatuh_tempo, t.status, t.created_at
        ");
        $this->datatables->from('pembayaran_tagihan t');
        $this->datatables->join('master_siswa s', 't.id_siswa = s.id_siswa');
        $this->datatables->join('pembayaran_jenis j', 't.id_jenis = j.id_jenis');
        $this->datatables->join('kelas_siswa ks', 's.id_siswa = ks.id_siswa AND ks.id_tp = ' . $id_tp . ' AND ks.id_smt = ' . $id_smt, 'left');
        $this->datatables->join('master_kelas k', 'ks.id_kelas = k.id_kelas', 'left');
        $this->datatables->where('t.id_tp', $id_tp);
        $this->datatables->where('t.id_smt', $id_smt);

        if (!empty($filters['id_kelas'])) {
            $this->datatables->where('ks.id_kelas', $filters['id_kelas']);
        }
        if (!empty($filters['id_jenis'])) {
            $this->datatables->where('t.id_jenis', $filters['id_jenis']);
        }
        if (!empty($filters['status'])) {
            $this->datatables->where('t.status', $filters['status']);
        }
        if (!empty($filters['bulan'])) {
            $this->datatables->where('t.bulan', $filters['bulan']);
        }

        return $this->datatables->generate();
    }

    // ==========================================
    // TRANSAKSI METHODS
    // ==========================================

    public function generateKodeTransaksi()
    {
        $prefix = 'TRX-' . date('Ymd') . '-';
        $this->db->select_max('kode_transaksi');
        $this->db->like('kode_transaksi', $prefix, 'after');
        $row = $this->db->get('pembayaran_transaksi')->row();

        if ($row && $row->kode_transaksi) {
            $last_num = (int) substr($row->kode_transaksi, -5);
            $new_num = str_pad($last_num + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $new_num = '00001';
        }
        return $prefix . $new_num;
    }

    public function getTransaksiById($id)
    {
        $this->db->select('
            tr.*, 
            t.kode_tagihan, t.total as nominal_tagihan, t.bulan, t.tahun,
            j.nama_jenis, 
            s.nama as nama_siswa, s.nis,
            k.nama_kelas
        ');
        $this->db->from('pembayaran_transaksi tr');
        $this->db->join('pembayaran_tagihan t', 'tr.id_tagihan = t.id_tagihan');
        $this->db->join('pembayaran_jenis j', 't.id_jenis = j.id_jenis');
        $this->db->join('master_siswa s', 'tr.id_siswa = s.id_siswa');
        $this->db->join('kelas_siswa ks', 's.id_siswa = ks.id_siswa AND ks.id_tp = t.id_tp AND ks.id_smt = t.id_smt', 'left');
        $this->db->join('master_kelas k', 'ks.id_kelas = k.id_kelas', 'left');
        $this->db->where('tr.id_transaksi', $id);
        return $this->db->get()->row();
    }

    public function getTransaksiByTagihan($id_tagihan)
    {
        $this->db->where('id_tagihan', $id_tagihan);
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get('pembayaran_transaksi')->result();
    }

    public function getLatestTransaksiByTagihan($id_tagihan)
    {
        $this->db->where('id_tagihan', $id_tagihan);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit(1);
        return $this->db->get('pembayaran_transaksi')->row();
    }

    public function isDuplicateBukti($file_hash, $exclude_id = null)
    {
        $this->db->where('bukti_bayar_hash', $file_hash);
        $this->db->where('status !=', 'rejected');
        if ($exclude_id) {
            $this->db->where('id_transaksi !=', $exclude_id);
        }
        return $this->db->get('pembayaran_transaksi')->num_rows() > 0;
    }

    public function createTransaksi($data)
    {
        if (empty($data['kode_transaksi'])) {
            $data['kode_transaksi'] = $this->generateKodeTransaksi();
        }
        $this->db->insert('pembayaran_transaksi', $data);
        return $this->db->insert_id();
    }

    public function updateTransaksi($id, $data)
    {
        $this->db->where('id_transaksi', $id);
        return $this->db->update('pembayaran_transaksi', $data);
    }

    public function getDataTableTransaksiPending()
    {
        $this->db->query("SET SQL_BIG_SELECTS=1");
        $this->datatables->select("
            tr.id_transaksi, tr.kode_transaksi, tr.metode_bayar,
            tr.nominal_bayar, tr.tanggal_bayar, tr.waktu_upload, tr.status,
            t.kode_tagihan, j.nama_jenis, t.bulan, t.tahun,
            s.nama as nama_siswa, s.nis,
            k.nama_kelas
        ");
        $this->datatables->from('pembayaran_transaksi tr');
        $this->datatables->join('pembayaran_tagihan t', 'tr.id_tagihan = t.id_tagihan');
        $this->datatables->join('pembayaran_jenis j', 't.id_jenis = j.id_jenis');
        $this->datatables->join('master_siswa s', 'tr.id_siswa = s.id_siswa');
        $this->datatables->join('kelas_siswa ks', 's.id_siswa = ks.id_siswa AND ks.id_tp = t.id_tp AND ks.id_smt = t.id_smt', 'left');
        $this->datatables->join('master_kelas k', 'ks.id_kelas = k.id_kelas', 'left');
        $this->datatables->where('tr.status', 'pending');

        return $this->datatables->generate();
    }

    public function getDataTableRiwayatVerifikasi($filters = [])
    {
        $this->db->query("SET SQL_BIG_SELECTS=1");
        $this->datatables->select("
            tr.id_transaksi, tr.kode_transaksi, tr.metode_bayar,
            tr.nominal_bayar, tr.tanggal_bayar, tr.status,
            tr.verified_at, tr.catatan_admin,
            t.kode_tagihan, j.nama_jenis,
            s.nama as nama_siswa, s.nis,
            k.nama_kelas,
            COALESCE(g.nama_guru, up.nama_lengkap, u.username) as verified_by_name
        ");
        $this->datatables->from('pembayaran_transaksi tr');
        $this->datatables->join('pembayaran_tagihan t', 'tr.id_tagihan = t.id_tagihan');
        $this->datatables->join('pembayaran_jenis j', 't.id_jenis = j.id_jenis');
        $this->datatables->join('master_siswa s', 'tr.id_siswa = s.id_siswa');
        $this->datatables->join('kelas_siswa ks', 's.id_siswa = ks.id_siswa AND ks.id_tp = t.id_tp AND ks.id_smt = t.id_smt', 'left');
        $this->datatables->join('master_kelas k', 'ks.id_kelas = k.id_kelas', 'left');
        $this->datatables->join('users u', 'tr.verified_by = u.id', 'left');
        $this->datatables->join('master_guru g', 'u.username = g.username', 'left');
        $this->datatables->join('users_profile up', 'u.id = up.id_user', 'left');
        $this->datatables->where_in('tr.status', ['verified', 'rejected']);

        if (!empty($filters['tanggal_dari'])) {
            $this->datatables->where('DATE(tr.verified_at) >=', $filters['tanggal_dari']);
        }
        if (!empty($filters['tanggal_sampai'])) {
            $this->datatables->where('DATE(tr.verified_at) <=', $filters['tanggal_sampai']);
        }

        return $this->datatables->generate();
    }

    public function getPendingCount()
    {
        $this->db->where('status', 'pending');
        return $this->db->count_all_results('pembayaran_transaksi');
    }

    // ==========================================
    // AUDIT LOG METHODS
    // ==========================================

    public function createLog($data)
    {
        $data['ip_address'] = $this->input->ip_address();
        $data['user_agent'] = substr($this->input->user_agent(), 0, 500);
        return $this->db->insert('pembayaran_log', $data);
    }

    public function getLogByTransaksi($id_transaksi)
    {
        $this->db->where('id_transaksi', $id_transaksi);
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get('pembayaran_log')->result();
    }

    // ==========================================
    // DASHBOARD & REPORTING METHODS
    // ==========================================

    public function getDashboardStats($id_tp, $id_smt)
    {
        $stats = [];

        $this->db->where(['id_tp' => $id_tp, 'id_smt' => $id_smt]);
        $stats['total_tagihan'] = $this->db->count_all_results('pembayaran_tagihan');

        $this->db->where(['id_tp' => $id_tp, 'id_smt' => $id_smt, 'status' => 'lunas']);
        $stats['total_lunas'] = $this->db->count_all_results('pembayaran_tagihan');

        $this->db->where(['id_tp' => $id_tp, 'id_smt' => $id_smt, 'status' => 'menunggu_verifikasi']);
        $stats['menunggu_verifikasi'] = $this->db->count_all_results('pembayaran_tagihan');

        $this->db->where(['id_tp' => $id_tp, 'id_smt' => $id_smt, 'status' => 'belum_bayar']);
        $stats['belum_bayar'] = $this->db->count_all_results('pembayaran_tagihan');

        $this->db->select_sum('total');
        $this->db->where(['id_tp' => $id_tp, 'id_smt' => $id_smt, 'status' => 'lunas']);
        $stats['nominal_lunas'] = $this->db->get('pembayaran_tagihan')->row()->total ?? 0;

        $this->db->select_sum('total');
        $this->db->where(['id_tp' => $id_tp, 'id_smt' => $id_smt]);
        $this->db->where_in('status', ['belum_bayar', 'ditolak']);
        $stats['nominal_tunggakan'] = $this->db->get('pembayaran_tagihan')->row()->total ?? 0;

        $stats['pending_verifikasi'] = $this->getPendingCount();

        return $stats;
    }

    public function getLaporanHarian($tanggal)
    {
        $this->db->select("
            tr.kode_transaksi, tr.metode_bayar, tr.nominal_bayar,
            tr.tanggal_bayar, tr.verified_at,
            t.kode_tagihan, j.nama_jenis, t.bulan, t.tahun,
            s.nama as nama_siswa, s.nis,
            k.nama_kelas,
            g.nama_guru as verifikator
        ");
        $this->db->from('pembayaran_transaksi tr');
        $this->db->join('pembayaran_tagihan t', 'tr.id_tagihan = t.id_tagihan');
        $this->db->join('pembayaran_jenis j', 't.id_jenis = j.id_jenis');
        $this->db->join('master_siswa s', 'tr.id_siswa = s.id_siswa');
        $this->db->join('kelas_siswa ks', 's.id_siswa = ks.id_siswa AND ks.id_tp = t.id_tp AND ks.id_smt = t.id_smt', 'left');
        $this->db->join('master_kelas k', 'ks.id_kelas = k.id_kelas', 'left');
        $this->db->join('users u', 'tr.verified_by = u.id', 'left');
        $this->db->join('master_guru g', 'u.username = g.username', 'left');
        $this->db->where('DATE(tr.verified_at)', $tanggal);
        $this->db->where('tr.status', 'verified');
        $this->db->order_by('tr.verified_at', 'ASC');
        return $this->db->get()->result();
    }

    public function getLaporanTunggakan($id_tp, $id_smt, $id_kelas = null)
    {
        $this->db->select("
            s.nama as nama_siswa, s.nis, s.nisn,
            k.nama_kelas,
            j.nama_jenis,
            t.bulan, t.tahun, t.total, t.jatuh_tempo, t.status
        ");
        $this->db->from('pembayaran_tagihan t');
        $this->db->join('master_siswa s', 't.id_siswa = s.id_siswa');
        $this->db->join('pembayaran_jenis j', 't.id_jenis = j.id_jenis');
        $this->db->join('kelas_siswa ks', 's.id_siswa = ks.id_siswa AND ks.id_tp = ' . $id_tp . ' AND ks.id_smt = ' . $id_smt, 'left');
        $this->db->join('master_kelas k', 'ks.id_kelas = k.id_kelas', 'left');
        $this->db->where('t.id_tp', $id_tp);
        $this->db->where('t.id_smt', $id_smt);
        $this->db->where_in('t.status', ['belum_bayar', 'ditolak']);

        if ($id_kelas) {
            $this->db->where('ks.id_kelas', $id_kelas);
        }

        $this->db->order_by('k.nama_kelas', 'ASC');
        $this->db->order_by('s.nama', 'ASC');
        $this->db->order_by('t.jatuh_tempo', 'ASC');
        return $this->db->get()->result();
    }

    public function getTotalTunggakanPerKelas($id_tp, $id_smt)
    {
        $this->db->select("
            k.id_kelas, k.nama_kelas,
            COUNT(t.id_tagihan) as jumlah_tagihan,
            SUM(t.total) as total_tunggakan
        ");
        $this->db->from('pembayaran_tagihan t');
        $this->db->join('master_siswa s', 't.id_siswa = s.id_siswa');
        $this->db->join('kelas_siswa ks', 's.id_siswa = ks.id_siswa AND ks.id_tp = ' . $id_tp . ' AND ks.id_smt = ' . $id_smt);
        $this->db->join('master_kelas k', 'ks.id_kelas = k.id_kelas');
        $this->db->where('t.id_tp', $id_tp);
        $this->db->where('t.id_smt', $id_smt);
        $this->db->where_in('t.status', ['belum_bayar', 'ditolak']);
        $this->db->group_by('k.id_kelas');
        $this->db->order_by('k.nama_kelas', 'ASC');
        return $this->db->get()->result();
    }
}
