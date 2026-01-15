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
        $this->load->model('Log_model', 'logging');
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

    public function qris($id_tagihan)
    {
        $tagihan = $this->pembayaran->getTagihanById($id_tagihan);

        if (!$tagihan || $tagihan->id_siswa != $this->siswa->id_siswa)
        {
            show_404();
        }

        $config = $this->pembayaran->getConfig();
        if (!$config || empty($config->qris_string))
        {
            show_404();
        }

        $this->load->library('Qris');

        try
        {
            $payload = $this->qris->generateDynamicQris($config->qris_string, (int) round($tagihan->total));
            $png = $this->qris->renderPng($payload);

            $this->output
                ->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0')
                ->set_header('Pragma: no-cache')
                ->set_content_type('image/png')
                ->set_output($png);
            return;
        }
        catch (Throwable $e)
        {
            log_message('error', 'QRIS dinamis gagal (Tagihanku/qris): ' . $e->getMessage());

            // Fallback to static image if available
            if (!empty($config->qris_image) && file_exists('./' . $config->qris_image))
            {
                $path = './' . $config->qris_image;
                $mime = 'image/png';
                if (class_exists('finfo'))
                {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $detected = $finfo->file($path);
                    if (!empty($detected))
                    {
                        $mime = $detected;
                    }
                }

                $this->output
                    ->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0')
                    ->set_header('Pragma: no-cache')
                    ->set_content_type($mime)
                    ->set_output(file_get_contents($path));
                return;
            }

            show_error('Gagal membuat QRIS dinamis.', 500);
        }
    }

    public function uploadBukti()
    {
        // Check if file is uploaded
        if (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] != 0) {
            $this->output_json(['status' => false, 'message' => 'File bukti pembayaran wajib diupload']);
            return;
        }

        // Setup upload config
        $upload_path = './uploads/pembayaran/bukti/' . date('Y/m/');
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        $config_upload = [
            'upload_path' => $upload_path,
            'allowed_types' => Pembayaran_model::ALLOWED_UPLOAD_TYPES,
            'max_size' => Pembayaran_model::MAX_UPLOAD_SIZE_KB,
            'encrypt_name' => true,
            'file_ext_tolower' => true
        ];

        $this->upload->initialize($config_upload);

        // Upload file
        if (!$this->upload->do_upload('bukti')) {
            $this->output_json(['status' => false, 'message' => strip_tags($this->upload->display_errors())]);
            return;
        }

        $upload_data = $this->upload->data();
        $file_path = $upload_path . $upload_data['file_name'];
        $file_hash = hash_file('sha256', $file_path);

        // Get input data
        $id_tagihan = $this->input->post('id_tagihan');
        $metode_bayar = $this->input->post('metode_bayar');
        $tanggal_bayar = $this->input->post('tanggal_bayar');
        $catatan_siswa = $this->input->post('catatan_siswa', true);
        
        // Get last transaction for reject count
        $last_transaksi = $this->pembayaran->getLatestTransaksiByTagihan($id_tagihan);

        // Start transaction for atomic operation
        $this->db->trans_start();

        // Call shared Model method to process upload
        $result = $this->pembayaran->processUploadBukti(
            $id_tagihan,
            $this->siswa->id_siswa,
            $file_path,
            $file_hash,
            $metode_bayar,
            $tanggal_bayar,
            $catatan_siswa,
            $last_transaksi
        );

        $this->db->trans_complete();

        // Cleanup and return response
        if (!$result['success'] || $this->db->trans_status() === FALSE) {
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            $http_code = $result['code'] ?? 400;
            $this->output_json([
                'status' => false,
                'message' => $result['message'] ?? 'Gagal menyimpan transaksi'
            ], $http_code);
        } else {
            $this->logging->saveLog(3, 'siswa upload bukti pembayaran tagihan #' . $id_tagihan);
            $this->output_json([
                'status' => true,
                'message' => $result['message'],
                'id_transaksi' => $result['id_transaksi']
            ]);
        }
    }

    public function riwayat()
    {
        $data = $this->getCommonData();
        $data['judul'] = 'Tagihan Saya';
        $data['subjudul'] = 'Riwayat Pembayaran';

        $data['transaksi'] = $this->pembayaran->getRiwayatTransaksiBySiswa($this->siswa->id_siswa);

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
