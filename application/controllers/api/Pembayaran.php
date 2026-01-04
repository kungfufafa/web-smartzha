<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pembayaran extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library(['ion_auth', 'form_validation', 'user_agent', 'upload']);
        $this->load->model('Pembayaran_model', 'pembayaran');
        $this->load->model('Master_model', 'master');
        $this->load->model('Dashboard_model', 'dashboard');
    }

    private function output_json($data, $code = 200)
    {
        $this->output
            ->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    private function check_login()
    {
        if (!$this->ion_auth->logged_in()) {
            $this->output_json(['status' => false, 'message' => 'Unauthorized', 'code' => 401], 401);
            return false;
        }
        return true;
    }

    private function get_siswa()
    {
        $user = $this->ion_auth->user()->row();
        $siswa = $this->master->getSiswaByUsername($user->username);
        
        if (!$siswa) {
            $this->output_json(['status' => false, 'message' => 'Data siswa tidak ditemukan', 'code' => 403], 403);
            return false;
        }
        
        return $siswa;
    }

    public function index()
    {
        if (!$this->check_login()) return;
        $siswa = $this->get_siswa();
        if (!$siswa) return;

        // Mirroring Tagihanku::index
        $tagihan_belum = $this->pembayaran->getTagihanBySiswa($siswa->id_siswa, ['belum_bayar', 'ditolak']);
        $tagihan_proses = $this->pembayaran->getTagihanBySiswa($siswa->id_siswa, 'menunggu_verifikasi');
        $tagihan_lunas = $this->pembayaran->getTagihanBySiswa($siswa->id_siswa, 'lunas');
        $config = $this->pembayaran->getConfig();

        $this->output_json([
            'status' => true,
            'data' => [
                'tagihan_belum' => $tagihan_belum,
                'tagihan_proses' => $tagihan_proses,
                'tagihan_lunas' => $tagihan_lunas,
                'config' => $config
            ]
        ]);
    }

    public function history()
    {
        if (!$this->check_login()) return;
        $siswa = $this->get_siswa();
        if (!$siswa) return;

        $transaksi = $this->pembayaran->getRiwayatTransaksiBySiswa($siswa->id_siswa);

        $this->output_json([
            'status' => true,
            'data' => $transaksi
        ]);
    }

    public function detail()
    {
        if (!$this->check_login()) return;
        $siswa = $this->get_siswa();
        if (!$siswa) return;

        $id_tagihan = $this->input->get('id_tagihan');
        if (!$id_tagihan) {
            $this->output_json(['status' => false, 'message' => 'ID Tagihan required'], 400);
            return;
        }

        $tagihan = $this->pembayaran->getTagihanById($id_tagihan);

        if (!$tagihan || $tagihan->id_siswa != $siswa->id_siswa) {
            $this->output_json(['status' => false, 'message' => 'Tagihan tidak ditemukan'], 404);
            return;
        }

        $transaksi_terakhir = $this->pembayaran->getLatestTransaksiByTagihan($id_tagihan);

        $this->output_json([
            'status' => true,
            'data' => [
                'tagihan' => $tagihan,
                'transaksi_terakhir' => $transaksi_terakhir,
                'config' => $this->pembayaran->getConfig()
            ]
        ]);
    }

    public function upload()
    {
        if (!$this->check_login()) return;
        $siswa = $this->get_siswa();
        if (!$siswa) return;

        // Check if file is uploaded
        if (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] != 0) {
            $this->output_json(['status' => false, 'message' => 'File bukti pembayaran wajib diupload'], 400);
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
            $this->output_json(['status' => false, 'message' => strip_tags($this->upload->display_errors())], 400);
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

        // Call shared Model method to process upload
        $result = $this->pembayaran->processUploadBukti(
            $id_tagihan,
            $siswa->id_siswa,
            $file_path,
            $file_hash,
            $metode_bayar,
            $tanggal_bayar,
            $catatan_siswa,
            $last_transaksi
        );

        // Cleanup and return response
        if (!$result['success']) {
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            $http_code = $result['code'] ?? 400;
            $this->output_json([
                'status' => false,
                'message' => $result['message']
            ], $http_code);
        } else {
            $this->output_json([
                'status' => true,
                'message' => $result['message'],
                'data' => ['id_transaksi' => $result['id_transaksi']]
            ]);
        }
    }
}
