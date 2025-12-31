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

        // Mirroring Tagihanku::riwayat
        $this->db->select('tr.*, t.kode_tagihan, j.nama_jenis, t.bulan, t.tahun');
        $this->db->from('pembayaran_transaksi tr');
        $this->db->join('pembayaran_tagihan t', 'tr.id_tagihan = t.id_tagihan');
        $this->db->join('pembayaran_jenis j', 't.id_jenis = j.id_jenis');
        $this->db->where('tr.id_siswa', $siswa->id_siswa);
        $this->db->order_by('tr.created_at', 'DESC');
        $transaksi = $this->db->get()->result();

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

        $id_tagihan = $this->input->post('id_tagihan');
        $tagihan = $this->pembayaran->getTagihanById($id_tagihan);

        if (!$tagihan || $tagihan->id_siswa != $siswa->id_siswa) {
            $this->output_json(['status' => false, 'message' => 'Tagihan tidak ditemukan'], 404);
            return;
        }

        if (!in_array($tagihan->status, ['belum_bayar', 'ditolak'])) {
            $this->output_json(['status' => false, 'message' => 'Tagihan tidak dapat dibayar'], 400);
            return;
        }

        $last_transaksi = $this->pembayaran->getLatestTransaksiByTagihan($id_tagihan);
        if ($last_transaksi && $last_transaksi->reject_count >= 3) {
            $this->output_json(['status' => false, 'message' => 'Pembayaran sudah ditolak 3 kali. Silakan hubungi admin.'], 400);
            return;
        }

        if (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] != 0) {
            $this->output_json(['status' => false, 'message' => 'File bukti pembayaran wajib diupload'], 400);
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
            $this->output_json(['status' => false, 'message' => strip_tags($this->upload->display_errors())], 400);
            return;
        }

        $upload_data = $this->upload->data();
        $file_path = $upload_path . $upload_data['file_name'];
        $file_hash = hash_file('sha256', $file_path);

        if ($this->pembayaran->isDuplicateBukti($file_hash)) {
            unlink($file_path);
            $this->output_json(['status' => false, 'message' => 'Bukti pembayaran ini sudah pernah digunakan untuk transaksi lain'], 400);
            return;
        }

        $reject_count = 0;
        if ($last_transaksi && $last_transaksi->status == 'rejected') {
            $reject_count = $last_transaksi->reject_count;
        }

        $transaksi_data = [
            'id_tagihan' => $id_tagihan,
            'id_siswa' => $siswa->id_siswa,
            'metode_bayar' => $this->input->post('metode_bayar') ?: 'qris',
            'nominal_bayar' => $tagihan->total,
            'bukti_bayar' => str_replace('./', '', $file_path),
            'bukti_bayar_hash' => $file_hash,
            'tanggal_bayar' => $this->input->post('tanggal_bayar') ?: date('Y-m-d'),
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
            'actor_id' => $siswa->id_siswa,
            'actor_type' => 'siswa',
            'actor_name' => $siswa->nama
        ]);

        $this->db->trans_complete();

        if ($this->db->trans_status()) {
            $this->output_json([
                'status' => true,
                'message' => 'Bukti pembayaran berhasil diupload. Mohon tunggu verifikasi dari admin.',
                'data' => ['id_transaksi' => $id_transaksi]
            ]);
        } else {
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            $this->output_json(['status' => false, 'message' => 'Gagal menyimpan data. Silakan coba lagi.'], 500);
        }
    }
}
