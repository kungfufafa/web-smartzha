<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Tagihanku extends CI_Controller
{
    private $siswa;

    public function __construct()
    {
        parent::__construct();
        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        }

        $user = $this->ion_auth->user()->row();
        $this->load->model('Master_model', 'master');
        $siswa = $this->master->getSiswaByUsername($user->username);

        if (!$siswa) {
            show_error('Halaman ini hanya untuk siswa. <a href="' . base_url('dashboard') . '">Kembali ke menu awal</a>', 403, 'Akses Terlarang');
        }

        $this->siswa = $siswa;
        $this->load->library(['form_validation', 'upload']);
        $this->load->model('Pembayaran_model', 'pembayaran');
        $this->load->model('Dashboard_model', 'dashboard');
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
            'siswa' => $this->siswa,
            'setting' => $setting,
            'tp_active' => $tp,
            'smt_active' => $smt,
            'profile' => $this->siswa
        ];
    }

    public function index()
    {
        $data = $this->getCommonData();
        $data['judul'] = 'Tagihan Saya';
        $data['subjudul'] = 'Daftar Tagihan';

        $tagihan_belum = $this->pembayaran->getTagihanBySiswa($this->siswa->id_siswa, ['belum_bayar', 'ditolak']);
        $tagihan_proses = $this->pembayaran->getTagihanBySiswa($this->siswa->id_siswa, 'menunggu_verifikasi');
        $tagihan_lunas = $this->pembayaran->getTagihanBySiswa($this->siswa->id_siswa, 'lunas');

        $data['tagihan_belum'] = $tagihan_belum;
        $data['tagihan_proses'] = $tagihan_proses;
        $data['tagihan_lunas'] = $tagihan_lunas;
        $data['config'] = $this->pembayaran->getConfig();

        $this->load->view('members/siswa/templates/header', $data);
        $this->load->view('pembayaran/siswa/tagihan', $data);
        $this->load->view('members/siswa/templates/footer');
    }

    public function bayar($id_tagihan)
    {
        $tagihan = $this->pembayaran->getTagihanById($id_tagihan);

        if (!$tagihan || $tagihan->id_siswa != $this->siswa->id_siswa) {
            show_404();
        }

        if (!in_array($tagihan->status, ['belum_bayar', 'ditolak'])) {
            redirect('tagihanku');
        }

        $data = $this->getCommonData();
        $data['judul'] = 'Bayar Tagihan';
        $data['subjudul'] = 'Upload Bukti Pembayaran';
        $data['tagihan'] = $tagihan;
        $data['config'] = $this->pembayaran->getConfig();
        $data['transaksi_terakhir'] = $this->pembayaran->getLatestTransaksiByTagihan($id_tagihan);

        $this->load->view('members/siswa/templates/header', $data);
        $this->load->view('pembayaran/siswa/bayar', $data);
        $this->load->view('members/siswa/templates/footer');
    }

    public function uploadBukti()
    {
        $id_tagihan = $this->input->post('id_tagihan');
        $tagihan = $this->pembayaran->getTagihanById($id_tagihan);

        if (!$tagihan || $tagihan->id_siswa != $this->siswa->id_siswa) {
            $this->output_json(['status' => false, 'message' => 'Tagihan tidak ditemukan']);
            return;
        }

        if (!in_array($tagihan->status, ['belum_bayar', 'ditolak'])) {
            $this->output_json(['status' => false, 'message' => 'Tagihan tidak dapat dibayar']);
            return;
        }

        $last_transaksi = $this->pembayaran->getLatestTransaksiByTagihan($id_tagihan);
        if ($last_transaksi && $last_transaksi->reject_count >= 3) {
            $this->output_json(['status' => false, 'message' => 'Pembayaran sudah ditolak 3 kali. Silakan hubungi admin.']);
            return;
        }

        if (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] != 0) {
            $this->output_json(['status' => false, 'message' => 'File bukti pembayaran wajib diupload']);
            return;
        }

        $upload_path = './uploads/pembayaran/bukti/' . date('Y/m/');
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        $config_upload = [
            'upload_path' => $upload_path,
            'allowed_types' => 'jpg|jpeg|png|pdf',
            'max_size' => 2048,
            'encrypt_name' => true,
            'file_ext_tolower' => true
        ];

        $this->upload->initialize($config_upload);

        if (!$this->upload->do_upload('bukti')) {
            $this->output_json(['status' => false, 'message' => strip_tags($this->upload->display_errors())]);
            return;
        }

        $upload_data = $this->upload->data();
        $file_path = $upload_path . $upload_data['file_name'];
        $file_hash = hash_file('sha256', $file_path);

        if ($this->pembayaran->isDuplicateBukti($file_hash)) {
            unlink($file_path);
            $this->output_json(['status' => false, 'message' => 'Bukti pembayaran ini sudah pernah digunakan untuk transaksi lain']);
            return;
        }

        $reject_count = 0;
        if ($last_transaksi && $last_transaksi->status == 'rejected') {
            $reject_count = $last_transaksi->reject_count;
        }

        $transaksi_data = [
            'id_tagihan' => $id_tagihan,
            'id_siswa' => $this->siswa->id_siswa,
            'metode_bayar' => $this->input->post('metode_bayar') ?: 'qris',
            'nominal_bayar' => $tagihan->total,
            'bukti_bayar' => str_replace('./', '', $file_path),
            'bukti_bayar_hash' => $file_hash,
            'tanggal_bayar' => $this->input->post('tanggal_bayar'),
            'catatan_siswa' => $this->input->post('catatan_siswa', true),
            'reject_count' => $reject_count,
            'ip_address' => $this->input->ip_address(),
            'user_agent' => substr($this->input->user_agent(), 0, 500)
        ];

        $this->db->trans_start();

        $id_transaksi = $this->pembayaran->createTransaksi($transaksi_data);

        $this->pembayaran->updateTagihan($id_tagihan, [
            'status' => 'menunggu_verifikasi'
        ]);

        $this->pembayaran->createLog([
            'id_transaksi' => $id_transaksi,
            'id_tagihan' => $id_tagihan,
            'action' => 'upload_bukti',
            'status_before' => $tagihan->status,
            'status_after' => 'menunggu_verifikasi',
            'data_snapshot' => json_encode(['nominal' => $tagihan->total, 'metode' => $transaksi_data['metode_bayar']]),
            'actor_id' => $this->siswa->id_siswa,
            'actor_type' => 'siswa',
            'actor_name' => $this->siswa->nama
        ]);

        $this->db->trans_complete();

        if ($this->db->trans_status()) {
            $this->output_json([
                'status' => true,
                'message' => 'Bukti pembayaran berhasil diupload. Mohon tunggu verifikasi dari admin.'
            ]);
        } else {
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            $this->output_json(['status' => false, 'message' => 'Gagal menyimpan data. Silakan coba lagi.']);
        }
    }

    public function riwayat()
    {
        $data = $this->getCommonData();
        $data['judul'] = 'Tagihan Saya';
        $data['subjudul'] = 'Riwayat Pembayaran';

        $this->db->select('tr.*, t.kode_tagihan, j.nama_jenis, t.bulan, t.tahun');
        $this->db->from('pembayaran_transaksi tr');
        $this->db->join('pembayaran_tagihan t', 'tr.id_tagihan = t.id_tagihan');
        $this->db->join('pembayaran_jenis j', 't.id_jenis = j.id_jenis');
        $this->db->where('tr.id_siswa', $this->siswa->id_siswa);
        $this->db->order_by('tr.created_at', 'DESC');
        $data['transaksi'] = $this->db->get()->result();

        $this->load->view('members/siswa/templates/header', $data);
        $this->load->view('pembayaran/siswa/riwayat', $data);
        $this->load->view('members/siswa/templates/footer');
    }

    public function detailTransaksi($id)
    {
        $transaksi = $this->pembayaran->getTransaksiById($id);

        if (!$transaksi || $transaksi->id_siswa != $this->siswa->id_siswa) {
            show_404();
        }

        $data = $this->getCommonData();
        $data['judul'] = 'Detail Transaksi';
        $data['subjudul'] = $transaksi->kode_transaksi;
        $data['transaksi'] = $transaksi;

        $this->load->view('members/siswa/templates/header', $data);
        $this->load->view('pembayaran/siswa/detail_transaksi', $data);
        $this->load->view('members/siswa/templates/footer');
    }
}
