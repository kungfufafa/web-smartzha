<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pembayaran extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        } elseif (!$this->ion_auth->is_admin()) {
            show_error('Hanya Administrator yang diberi hak untuk mengakses halaman ini, <a href="' . base_url('dashboard') . '">Kembali ke menu awal</a>', 403, 'Akses Terlarang');
        }
        $this->load->library(['datatables', 'form_validation', 'upload']);
        $this->load->model('Pembayaran_model', 'pembayaran');
        $this->load->model('Dashboard_model', 'dashboard');
        $this->load->model('Master_model', 'master');
        $this->load->model('Kelas_model', 'kelas');
        $this->form_validation->set_error_delimiters('', '');
    }

    private function output_json($data, $encode = true)
    {
        if ($encode) $data = json_encode($data);
        $this->output->set_content_type('application/json')->set_output($data);
    }

    private function getCommonData()
    {
        $user = $this->ion_auth->user()->row();
        $setting = $this->dashboard->getSetting();
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        return [
            'user' => $user,
            'setting' => $setting,
            'tp' => $this->dashboard->getTahun(),
            'tp_active' => $tp,
            'smt' => $this->dashboard->getSemester(),
            'smt_active' => $smt,
            'profile' => $this->dashboard->getProfileAdmin($user->id),
            'pending_count' => $this->pembayaran->getPendingCount()
        ];
    }

    public function index()
    {
        $data = $this->getCommonData();
        $data['judul'] = 'Pembayaran';
        $data['subjudul'] = 'Dashboard Pembayaran';
        $data['stats'] = $this->pembayaran->getDashboardStats($data['tp_active']->id_tp, $data['smt_active']->id_smt);
        $data['tunggakan_kelas'] = $this->pembayaran->getTotalTunggakanPerKelas($data['tp_active']->id_tp, $data['smt_active']->id_smt);

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('pembayaran/dashboard');
        $this->load->view('_templates/dashboard/_footer');
    }

    public function config()
    {
        $data = $this->getCommonData();
        $data['judul'] = 'Pembayaran';
        $data['subjudul'] = 'Konfigurasi Pembayaran';
        $data['config'] = $this->pembayaran->getConfig();

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('pembayaran/config');
        $this->load->view('_templates/dashboard/_footer');
    }

    public function jenis()
    {
        $data = $this->getCommonData();
        $data['judul'] = 'Pembayaran';
        $data['subjudul'] = 'Jenis Tagihan';

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('pembayaran/jenis/data');
        $this->load->view('_templates/dashboard/_footer');
    }

    public function dataJenis()
    {
        $this->output_json($this->pembayaran->getDataTableJenisTagihan(), false);
    }

    public function tagihan()
    {
        $data = $this->getCommonData();
        $data['judul'] = 'Pembayaran';
        $data['subjudul'] = 'Data Tagihan';
        $data['kelas'] = $this->kelas->getKelasList($data['tp_active']->id_tp, $data['smt_active']->id_smt);
        $data['jenis'] = $this->pembayaran->getAllJenisTagihan();

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('pembayaran/tagihan/data');
        $this->load->view('_templates/dashboard/_footer');
    }

    public function dataTagihan()
    {
        $id_tp = $this->dashboard->getTahunActive()->id_tp;
        $id_smt = $this->dashboard->getSemesterActive()->id_smt;
        $filters = [
            'id_kelas' => $this->input->post('id_kelas'),
            'id_jenis' => $this->input->post('id_jenis'),
            'status' => $this->input->post('status'),
            'bulan' => $this->input->post('bulan')
        ];
        $this->output_json($this->pembayaran->getDataTableTagihan($id_tp, $id_smt, $filters), false);
    }

    public function createTagihan()
    {
        $data = $this->getCommonData();
        $data['judul'] = 'Pembayaran';
        $data['subjudul'] = 'Buat Tagihan Baru';
        $data['kelas'] = $this->kelas->getKelasList($data['tp_active']->id_tp, $data['smt_active']->id_smt);
        $data['jenis'] = $this->pembayaran->getAllJenisTagihan();

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('pembayaran/tagihan/add');
        $this->load->view('_templates/dashboard/_footer');
    }

    public function createTagihanProcess()
    {
        $id_jenis = $this->input->post('id_jenis');
        $nominal = str_replace('.', '', $this->input->post('nominal'));
        $diskon = str_replace('.', '', $this->input->post('diskon'));
        $jatuh_tempo = $this->input->post('jatuh_tempo');
        $keterangan = $this->input->post('keterangan');
        $bulan = $this->input->post('bulan');
        $tahun = $this->input->post('tahun');
        $id_siswas = $this->input->post('id_siswa');
        
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();

        if (empty($id_jenis)) {
            $this->output_json(['status' => false, 'message' => 'Jenis tagihan wajib dipilih']);
            return;
        }

        if (empty($id_siswas)) {
            $this->output_json(['status' => false, 'message' => 'Pilih minimal satu siswa']);
            return;
        }

        $jenis = $this->pembayaran->getJenisTagihanById($id_jenis);
        if (!$jenis) {
            $this->output_json(['status' => false, 'message' => 'Jenis tagihan tidak valid']);
            return;
        }
        if ($jenis->is_recurring == 1 && (empty($bulan) || empty($tahun))) {
            $this->output_json(['status' => false, 'message' => 'Bulan dan Tahun wajib diisi untuk tagihan bulanan']);
            return;
        }

        $data_batch = [];
        $skipped = 0;

        foreach ($id_siswas as $id_siswa) {
            // Check duplicate
            if ($jenis->is_recurring == 1) {
                $exists = $this->pembayaran->checkTagihanExists($id_siswa, $id_jenis, $bulan, $tahun, $tp->id_tp, $smt->id_smt);
            } else {
                // Non-recurring: check if exists in this academic year/semester? 
                // Usually non-recurring can be multiple times, but let's assume unique per type/student for now or allow duplicate?
                // Based on logic, maybe just allow.
                $exists = false; 
            }

            if ($exists) {
                $skipped++;
                continue;
            }

            $data_batch[] = [
                'id_siswa' => $id_siswa,
                'id_jenis' => $id_jenis,
                'nominal' => $nominal,
                'diskon' => $diskon,
                'jatuh_tempo' => $jatuh_tempo,
                'keterangan' => $keterangan,
                'bulan' => $jenis->is_recurring == 1 ? $bulan : null,
                'tahun' => $jenis->is_recurring == 1 ? $tahun : null,
                'id_tp' => $tp->id_tp,
                'id_smt' => $smt->id_smt,
                'status' => 'belum_bayar',
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        if (empty($data_batch)) {
            $this->output_json(['status' => false, 'message' => 'Semua tagihan untuk siswa yang dipilih sudah ada']);
            return;
        }

        $result = $this->pembayaran->createTagihanBatch($data_batch);
        
        if ($result) {
            $msg = 'Berhasil membuat ' . count($data_batch) . ' tagihan.';
            if ($skipped > 0) $msg .= ' (' . $skipped . ' dilewati karena sudah ada)';
        } else {
            $msg = 'Gagal menyimpan data tagihan. Terjadi kesalahan database.';
        }

        $this->output_json(['status' => $result, 'message' => $msg]);
    }

    public function getSiswaByKelas($id_kelas)
    {
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $rows = $this->master->getSiswaByKelas($tp->id_tp, $smt->id_smt, $id_kelas);
        $data = [];
        foreach ($rows as $row) {
            $data[] = (object) [
                'id_siswa' => $row->id_siswa,
                'nama' => $row->nama,
                'nis' => $row->nis
            ];
        }
        $this->output_json(['status' => true, 'data' => $data]);
    }

    public function saveJenis()
    {
        $id = $this->input->post('id_jenis');
        $data = [
            'kode_jenis' => strtoupper($this->input->post('kode_jenis', true)),
            'nama_jenis' => $this->input->post('nama_jenis', true),
            'nominal_default' => str_replace('.', '', $this->input->post('nominal_default')),
            'keterangan' => $this->input->post('keterangan', true),
            'is_recurring' => $this->input->post('is_recurring') ? 1 : 0,
            'is_active' => $this->input->post('is_active') ? 1 : 0
        ];

        if ($id) {
            $result = $this->pembayaran->updateJenisTagihan($id, $data);
        } else {
            $result = $this->pembayaran->createJenisTagihan($data);
        }

        $this->output_json(['status' => $result, 'message' => $result ? 'Data berhasil disimpan' : 'Gagal menyimpan data']);
    }

    public function getJenis($id)
    {
        $data = $this->pembayaran->getJenisTagihanById($id);
        $this->output_json(['status' => true, 'data' => $data]);
    }

    public function deleteJenis()
    {
        $ids = $this->input->post('ids');
        $result = $this->pembayaran->deleteJenisTagihan($ids[0]);
        
        if ($result === false) {
            $this->output_json(['status' => false, 'message' => 'Gagal menghapus: Jenis tagihan sedang digunakan oleh data tagihan lain.']);
        } else {
            $this->output_json(['status' => $result, 'message' => $result ? 'Data berhasil dihapus' : 'Gagal menghapus data']);
        }
    }

    public function getTagihan($id)
    {
        $data = $this->pembayaran->getTagihanById($id);
        $this->output_json(['status' => true, 'data' => $data]);
    }

    public function updateTagihan()
    {
        $id = $this->input->post('id_tagihan');
        $data = [
            'nominal' => str_replace('.', '', $this->input->post('nominal')),
            'diskon' => str_replace('.', '', $this->input->post('diskon')),
            'denda' => str_replace('.', '', $this->input->post('denda')),
            'jatuh_tempo' => $this->input->post('jatuh_tempo'),
            'keterangan' => $this->input->post('keterangan')
        ];

        $result = $this->pembayaran->updateTagihan($id, $data);
        $this->output_json(['status' => $result, 'message' => $result ? 'Data berhasil diupdate' : 'Gagal update data']);
    }

    public function deleteTagihan()
    {
        $ids = $this->input->post('ids');
        $result = $this->pembayaran->deleteTagihan($ids);
        
        if ($result === false) {
            $this->output_json(['status' => false, 'message' => 'Gagal menghapus: Salah satu tagihan sudah memiliki riwayat transaksi/pembayaran.']);
        } else {
            $this->output_json(['status' => $result, 'message' => $result ? 'Data berhasil dihapus' : 'Gagal menghapus data']);
        }
    }

    public function saveConfig()
    {
        $data = [
            'qris_merchant_name' => $this->input->post('qris_merchant_name', true),
            'bank_name' => $this->input->post('bank_name', true),
            'bank_account' => $this->input->post('bank_account', true),
            'bank_holder' => $this->input->post('bank_holder', true),
            'payment_instruction' => $this->input->post('payment_instruction', true)
        ];

        if ($_FILES['qris_image']['name']) {
            $upload_path = './uploads/pembayaran/qris/';
            if (!is_dir($upload_path)) mkdir($upload_path, 0755, true);

            $config_upload = [
                'upload_path' => $upload_path,
                'allowed_types' => 'jpg|jpeg|png',
                'max_size' => 2048,
                'encrypt_name' => true
            ];
            $this->upload->initialize($config_upload);

            if ($this->upload->do_upload('qris_image')) {
                $upload_data = $this->upload->data();
                $data['qris_image'] = 'uploads/pembayaran/qris/' . $upload_data['file_name'];

                $old_config = $this->pembayaran->getConfig();
                if ($old_config && $old_config->qris_image && file_exists('./' . $old_config->qris_image)) {
                    unlink('./' . $old_config->qris_image);
                }
            } else {
                $this->output_json(['status' => false, 'message' => $this->upload->display_errors('', '')]);
                return;
            }
        }

        $result = $this->pembayaran->updateConfig($data);
        $this->output_json(['status' => $result, 'message' => $result ? 'Konfigurasi berhasil disimpan' : 'Gagal menyimpan konfigurasi']);
    }





    public function verifikasi()
    {
        $data = $this->getCommonData();
        $data['judul'] = 'Pembayaran';
        $data['subjudul'] = 'Verifikasi Pembayaran';

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('pembayaran/verifikasi/data');
        $this->load->view('_templates/dashboard/_footer');
    }

    public function dataVerifikasi()
    {
        $this->output_json($this->pembayaran->getDataTableTransaksiPending(), false);
    }

    public function detailTransaksi($id)
    {
        $transaksi = $this->pembayaran->getTransaksiById($id);
        if (!$transaksi) {
            show_404();
        }

        $data = $this->getCommonData();
        $data['judul'] = 'Pembayaran';
        $data['subjudul'] = 'Detail Transaksi';
        $data['transaksi'] = $transaksi;
        $data['logs'] = $this->pembayaran->getLogByTransaksi($id);

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('pembayaran/verifikasi/detail');
        $this->load->view('_templates/dashboard/_footer');
    }

    public function approve()
    {
        $id = $this->input->post('id_transaksi');
        $catatan = $this->input->post('catatan', true);

        // Lock the transaction row to prevent race conditions
        $this->db->query("SELECT * FROM pembayaran_transaksi WHERE id_transaksi = ? FOR UPDATE", [$id]);
        
        $transaksi = $this->pembayaran->getTransaksiById($id);
        if (!$transaksi || $transaksi->status != 'pending') {
            $this->output_json(['status' => false, 'message' => 'Transaksi tidak valid atau sudah diproses']);
            return;
        }

        $user = $this->ion_auth->user()->row();

        $this->db->trans_start();

        $this->pembayaran->updateTransaksi($id, [
            'status' => 'verified',
            'verified_by' => $user->id,
            'verified_at' => date('Y-m-d H:i:s'),
            'catatan_admin' => $catatan
        ]);

        $this->pembayaran->updateTagihan($transaksi->id_tagihan, [
            'status' => 'lunas'
        ]);

        $this->pembayaran->createLog([
            'id_transaksi' => $id,
            'id_tagihan' => $transaksi->id_tagihan,
            'action' => 'verify_approve',
            'status_before' => 'pending',
            'status_after' => 'verified',
            'data_snapshot' => json_encode(['nominal' => $transaksi->nominal_bayar, 'catatan' => $catatan]),
            'actor_id' => $user->id,
            'actor_type' => 'admin',
            'actor_name' => $this->dashboard->getProfileAdmin($user->id)->nama_lengkap ?? 'Admin'
        ]);

        $this->db->trans_complete();

        $this->output_json([
            'status' => $this->db->trans_status(),
            'message' => $this->db->trans_status() ? 'Pembayaran berhasil diverifikasi' : 'Gagal verifikasi pembayaran'
        ]);
    }

    public function reject()
    {
        $id = $this->input->post('id_transaksi');
        $catatan = $this->input->post('catatan', true);

        if (empty($catatan)) {
            $this->output_json(['status' => false, 'message' => 'Alasan penolakan wajib diisi']);
            return;
        }

        // Lock the transaction row to prevent race conditions
        $this->db->query("SELECT * FROM pembayaran_transaksi WHERE id_transaksi = ? FOR UPDATE", [$id]);
        
        $transaksi = $this->pembayaran->getTransaksiById($id);
        if (!$transaksi || $transaksi->status != 'pending') {
            $this->output_json(['status' => false, 'message' => 'Transaksi tidak valid atau sudah diproses']);
            return;
        }

        $user = $this->ion_auth->user()->row();
        $new_reject_count = $transaksi->reject_count + 1;
        $transaksi_status = $new_reject_count >= 3 ? 'cancelled' : 'rejected';
        $tagihan_status = 'ditolak';

        $this->db->trans_start();

        $this->pembayaran->updateTransaksi($id, [
            'status' => $transaksi_status,
            'verified_by' => $user->id,
            'verified_at' => date('Y-m-d H:i:s'),
            'catatan_admin' => $catatan,
            'reject_count' => $new_reject_count
        ]);

        $this->pembayaran->updateTagihan($transaksi->id_tagihan, [
            'status' => $tagihan_status
        ]);

        $this->pembayaran->createLog([
            'id_transaksi' => $id,
            'id_tagihan' => $transaksi->id_tagihan,
            'action' => 'verify_reject',
            'status_before' => 'pending',
            'status_after' => $transaksi_status,
            'data_snapshot' => json_encode(['nominal' => $transaksi->nominal_bayar, 'catatan' => $catatan, 'reject_count' => $new_reject_count]),
            'actor_id' => $user->id,
            'actor_type' => 'admin',
            'actor_name' => $this->dashboard->getProfileAdmin($user->id)->nama_lengkap ?? 'Admin'
        ]);

        $this->db->trans_complete();

        $message = $this->db->trans_status() ? 'Pembayaran ditolak' : 'Gagal menolak pembayaran';
        if ($new_reject_count >= 3) {
            $message .= '. Transaksi dibatalkan karena sudah 3x ditolak.';
        } else {
            $message .= '. Siswa dapat upload ulang bukti pembayaran.';
        }

        $this->output_json(['status' => $this->db->trans_status(), 'message' => $message]);
    }

    public function riwayat()
    {
        $data = $this->getCommonData();
        $data['judul'] = 'Pembayaran';
        $data['subjudul'] = 'Riwayat Verifikasi';

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('pembayaran/verifikasi/riwayat');
        $this->load->view('_templates/dashboard/_footer');
    }

    public function dataRiwayat()
    {
        // DataTables server-side library in this project reads POST parameters
        // but keep GET fallback for compatibility.
        $tanggal_dari = $this->input->post('tanggal_dari');
        if ($tanggal_dari === null || $tanggal_dari === '') {
            $tanggal_dari = $this->input->get('tanggal_dari');
        }
        $tanggal_sampai = $this->input->post('tanggal_sampai');
        if ($tanggal_sampai === null || $tanggal_sampai === '') {
            $tanggal_sampai = $this->input->get('tanggal_sampai');
        }

        $filters = [
            'tanggal_dari' => $tanggal_dari,
            'tanggal_sampai' => $tanggal_sampai
        ];
        $this->output_json($this->pembayaran->getDataTableRiwayatVerifikasi($filters), false);
    }

    public function laporan()
    {
        $data = $this->getCommonData();
        $data['judul'] = 'Pembayaran';
        $data['subjudul'] = 'Laporan Pembayaran';
        $data['kelas'] = $this->kelas->getKelasList($data['tp_active']->id_tp, $data['smt_active']->id_smt);

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('pembayaran/laporan/index');
        $this->load->view('_templates/dashboard/_footer');
    }

    public function laporanHarian()
    {
        $tanggal = $this->input->get('tanggal') ?: date('Y-m-d');
        $data = $this->pembayaran->getLaporanHarian($tanggal);

        $total = 0;
        foreach ($data as $row) {
            $total += $row->nominal_bayar;
        }

        $this->output_json(['status' => true, 'data' => $data, 'total' => $total, 'tanggal' => $tanggal]);
    }

    public function laporanTunggakan()
    {
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $id_kelas = $this->input->get('id_kelas');

        $data = $this->pembayaran->getLaporanTunggakan($tp->id_tp, $smt->id_smt, $id_kelas);

        $total = 0;
        foreach ($data as $row) {
            $total += $row->total;
        }

        $this->output_json(['status' => true, 'data' => $data, 'total' => $total]);
    }
}
