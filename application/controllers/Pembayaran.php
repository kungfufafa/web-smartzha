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

    public function jenis()
    {
        $data = $this->getCommonData();
        $data['judul'] = 'Pembayaran';
        $data['subjudul'] = 'Jenis Tagihan';

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('pembayaran/jenis');
        $this->load->view('_templates/dashboard/_footer');
    }

    public function dataJenis()
    {
        $this->output_json($this->pembayaran->getDataTableJenisTagihan(), false);
    }

    public function saveJenis()
    {
        $this->form_validation->set_rules('kode_jenis', 'Kode', 'required|trim');
        $this->form_validation->set_rules('nama_jenis', 'Nama Jenis', 'required|trim');

        if ($this->form_validation->run() == false) {
            $this->output_json(['status' => false, 'message' => validation_errors()]);
            return;
        }

        $id = $this->input->post('id_jenis');
        $data = [
            'kode_jenis' => strtoupper($this->input->post('kode_jenis', true)),
            'nama_jenis' => $this->input->post('nama_jenis', true),
            'nominal_default' => (int) str_replace(['.', ','], '', $this->input->post('nominal_default', true)),
            'is_recurring' => $this->input->post('is_recurring') ? 1 : 0,
            'keterangan' => $this->input->post('keterangan', true),
            'is_active' => $this->input->post('is_active') ? 1 : 0
        ];

        if ($id) {
            $result = $this->pembayaran->updateJenisTagihan($id, $data);
            $message = $result ? 'Jenis tagihan berhasil diupdate' : 'Gagal update jenis tagihan';
        } else {
            $existing = $this->pembayaran->getJenisTagihanByKode($data['kode_jenis']);
            if ($existing) {
                $this->output_json(['status' => false, 'message' => 'Kode jenis sudah digunakan']);
                return;
            }
            $result = $this->pembayaran->createJenisTagihan($data);
            $message = $result ? 'Jenis tagihan berhasil ditambahkan' : 'Gagal menambah jenis tagihan';
        }

        $this->output_json(['status' => (bool) $result, 'message' => $message]);
    }

    public function getJenis($id)
    {
        $jenis = $this->pembayaran->getJenisTagihanById($id);
        $this->output_json(['status' => (bool) $jenis, 'data' => $jenis]);
    }

    public function deleteJenis()
    {
        $ids = $this->input->post('ids');
        if (!$ids) {
            $this->output_json(['status' => false, 'message' => 'Tidak ada data yang dipilih']);
            return;
        }

        $result = true;
        foreach ($ids as $id) {
            if (!$this->pembayaran->deleteJenisTagihan($id)) {
                $result = false;
            }
        }

        $this->output_json(['status' => $result, 'message' => $result ? 'Jenis tagihan berhasil dihapus' : 'Gagal menghapus jenis tagihan']);
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
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();

        $filters = [
            'id_kelas' => $this->input->get('id_kelas'),
            'id_jenis' => $this->input->get('id_jenis'),
            'status' => $this->input->get('status'),
            'bulan' => $this->input->get('bulan')
        ];

        $this->output_json($this->pembayaran->getDataTableTagihan($tp->id_tp, $smt->id_smt, $filters), false);
    }

    public function addTagihan()
    {
        $data = $this->getCommonData();
        $data['judul'] = 'Pembayaran';
        $data['subjudul'] = 'Tambah Tagihan';
        $data['kelas'] = $this->kelas->getKelasList($data['tp_active']->id_tp, $data['smt_active']->id_smt);
        $data['jenis'] = $this->pembayaran->getAllJenisTagihan();

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('pembayaran/tagihan/add');
        $this->load->view('_templates/dashboard/_footer');
    }

    public function getSiswaByKelas($id_kelas)
    {
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $siswa = $this->master->getSiswaByKelas($tp->id_tp, $smt->id_smt, $id_kelas);
        $this->output_json(['status' => true, 'data' => $siswa]);
    }

    public function saveTagihan()
    {
        $this->form_validation->set_rules('id_siswa[]', 'Siswa', 'required');
        $this->form_validation->set_rules('id_jenis', 'Jenis Tagihan', 'required');
        $this->form_validation->set_rules('nominal', 'Nominal', 'required');
        $this->form_validation->set_rules('jatuh_tempo', 'Jatuh Tempo', 'required');

        if ($this->form_validation->run() == false) {
            $this->output_json(['status' => false, 'message' => validation_errors()]);
            return;
        }

        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $user = $this->ion_auth->user()->row();

        $siswa_ids = $this->input->post('id_siswa');
        $id_jenis = $this->input->post('id_jenis');
        $nominal = (int) str_replace(['.', ','], '', $this->input->post('nominal', true));
        $diskon = (int) str_replace(['.', ','], '', $this->input->post('diskon', true));
        $jatuh_tempo = $this->input->post('jatuh_tempo');
        $bulan = $this->input->post('bulan') ?: null;
        $tahun = $this->input->post('tahun') ?: null;
        $keterangan = $this->input->post('keterangan', true);

        $data_batch = [];
        $skipped = 0;

        foreach ($siswa_ids as $id_siswa) {
            $existing = $this->pembayaran->checkTagihanExists($id_siswa, $id_jenis, $bulan, $tahun, $tp->id_tp, $smt->id_smt);
            if ($existing) {
                $skipped++;
                continue;
            }

            $data_batch[] = [
                'id_siswa' => $id_siswa,
                'id_jenis' => $id_jenis,
                'id_tp' => $tp->id_tp,
                'id_smt' => $smt->id_smt,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'nominal' => $nominal,
                'diskon' => $diskon,
                'jatuh_tempo' => $jatuh_tempo,
                'keterangan' => $keterangan,
                'created_by' => $user->id
            ];
        }

        if (empty($data_batch)) {
            $this->output_json(['status' => false, 'message' => 'Semua tagihan sudah ada (' . $skipped . ' data di-skip)']);
            return;
        }

        $result = $this->pembayaran->createTagihanBatch($data_batch);
        $message = $result ? 'Berhasil membuat ' . count($data_batch) . ' tagihan' : 'Gagal membuat tagihan';
        if ($skipped > 0) {
            $message .= ' (' . $skipped . ' data di-skip karena sudah ada)';
        }

        $this->output_json(['status' => (bool) $result, 'message' => $message]);
    }

    public function getTagihan($id)
    {
        $tagihan = $this->pembayaran->getTagihanById($id);
        $this->output_json(['status' => (bool) $tagihan, 'data' => $tagihan]);
    }

    public function updateTagihan()
    {
        $id = $this->input->post('id_tagihan');
        if (!$id) {
            $this->output_json(['status' => false, 'message' => 'ID tagihan tidak valid']);
            return;
        }

        $tagihan = $this->pembayaran->getTagihanById($id);
        if (!$tagihan) {
            $this->output_json(['status' => false, 'message' => 'Tagihan tidak ditemukan']);
            return;
        }

        if ($tagihan->status == 'lunas') {
            $this->output_json(['status' => false, 'message' => 'Tagihan yang sudah lunas tidak dapat diedit']);
            return;
        }

        $data = [
            'nominal' => (int) str_replace(['.', ','], '', $this->input->post('nominal', true)),
            'diskon' => (int) str_replace(['.', ','], '', $this->input->post('diskon', true)),
            'denda' => (int) str_replace(['.', ','], '', $this->input->post('denda', true)),
            'jatuh_tempo' => $this->input->post('jatuh_tempo'),
            'keterangan' => $this->input->post('keterangan', true)
        ];

        $result = $this->pembayaran->updateTagihan($id, $data);
        $this->output_json(['status' => $result, 'message' => $result ? 'Tagihan berhasil diupdate' : 'Gagal update tagihan']);
    }

    public function deleteTagihan()
    {
        $ids = $this->input->post('ids');
        if (!$ids) {
            $this->output_json(['status' => false, 'message' => 'Tidak ada data yang dipilih']);
            return;
        }

        foreach ($ids as $id) {
            $tagihan = $this->pembayaran->getTagihanById($id);
            if ($tagihan && $tagihan->status == 'lunas') {
                $this->output_json(['status' => false, 'message' => 'Tagihan yang sudah lunas tidak dapat dihapus']);
                return;
            }
        }

        $result = $this->pembayaran->deleteTagihan($ids);
        $this->output_json(['status' => $result, 'message' => $result ? 'Tagihan berhasil dihapus' : 'Gagal menghapus tagihan']);
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

        $transaksi = $this->pembayaran->getTransaksiById($id);
        if (!$transaksi || $transaksi->status != 'pending') {
            $this->output_json(['status' => false, 'message' => 'Transaksi tidak valid atau sudah diproses']);
            return;
        }

        $user = $this->ion_auth->user()->row();
        $new_reject_count = $transaksi->reject_count + 1;
        $transaksi_status = $new_reject_count >= 3 ? 'cancelled' : 'rejected';
        $tagihan_status = $new_reject_count >= 3 ? 'belum_bayar' : 'ditolak';

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
        $filters = [
            'tanggal_dari' => $this->input->get('tanggal_dari'),
            'tanggal_sampai' => $this->input->get('tanggal_sampai')
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
