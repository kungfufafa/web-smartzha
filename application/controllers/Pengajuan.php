<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pengajuan extends CI_Controller
{
    private $is_guru = false;
    private $guru = null;
    private $tp = null;
    private $smt = null;

    public function __construct()
    {
        parent::__construct();
        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        }
        $this->load->model('Dashboard_model', 'dashboard');
        $this->load->model('Pengajuan_model', 'pengajuan');
        $this->load->model('Presensi_model', 'presensi');
        $this->load->library('form_validation');

        $this->is_guru = $this->ion_auth->in_group('guru');
        if ($this->is_guru) {
            $this->tp = $this->dashboard->getTahunActive();
            $this->smt = $this->dashboard->getSemesterActive();
            $user = $this->ion_auth->user()->row();
            $this->guru = $this->dashboard->getDataGuruByUserId($user->id, $this->tp->id_tp, $this->smt->id_smt);
        }
    }

    public function output_json($data, $encode = true)
    {
        if ($encode) {
            $data = json_encode($data);
        }
        $this->output->set_content_type('application/json')->set_output($data);
    }

    private function load_view($view, $data)
    {
        if ($this->is_guru) {
            $data['guru'] = $this->guru;
            $data['tp'] = $this->dashboard->getTahun();
            $data['tp_active'] = $this->tp;
            $data['smt'] = $this->dashboard->getSemester();
            $data['smt_active'] = $this->smt;
            
            $this->load->view('members/guru/templates/header', $data);
            $this->load->view('members/guru/templates/sidebar', $data);
            $this->load->view($view, $data);
            $this->load->view('members/guru/templates/footer');
        } elseif ($this->ion_auth->in_group('tendik')) {
            // Tendik uses top-nav layout like siswa/orangtua
            $data['tp_active'] = $this->dashboard->getTahunActive();
            $data['smt_active'] = $this->dashboard->getSemesterActive();
            
            $this->load->view('members/tendik/templates/header_topnav', $data);
            $this->load->view($view, $data);
            $this->load->view('members/tendik/templates/footer_topnav');
        } else {
            $this->load->view('_templates/dashboard/_header', $data);
            $this->load->view($view, $data);
            $this->load->view('_templates/dashboard/_footer');
        }
    }

    public function index()
    {
        if ($this->ion_auth->in_group('tendik')) {
            redirect('tendik/pengajuan');
            return;
        }

        $user = $this->ion_auth->user()->row();
        $setting = $this->dashboard->getSetting();
        $data = [
            'user' => $user,
            'judul' => 'Pengajuan Izin/Cuti',
            'subjudul' => 'Daftar Pengajuan',
            'setting' => $setting,
            'profile' => $this->dashboard->getProfileAdmin($user->id),
            'list_pengajuan' => $this->pengajuan->get_by_user($user->id),
            'jenis_izin' => $this->pengajuan->get_jenis_izin()
        ];
        
        $this->load_view('presensi/pengajuan/index', $data);
    }

    public function create()
    {
        $redirect_target = 'pengajuan';
        if ($this->ion_auth->in_group('tendik')) {
            $redirect_target = 'tendik/pengajuan';
        }

        $this->form_validation->set_rules('tipe_pengajuan', 'Tipe Pengajuan', 'required|in_list[Izin,Sakit,Cuti,Dinas,Lembur]');
        $this->form_validation->set_rules('tgl_mulai', 'Tanggal Mulai', 'required');
        $this->form_validation->set_rules('tgl_selesai', 'Tanggal Selesai', 'required');
        $this->form_validation->set_rules('keterangan', 'Keterangan', 'required|max_length[500]');

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect($redirect_target);
            return;
        }

        $user = $this->ion_auth->user()->row();
        $tipe = $this->input->post('tipe_pengajuan');
        
        $data = [
            'id_user' => $user->id,
            'tipe_pengajuan' => $tipe,
            'tgl_mulai' => $this->input->post('tgl_mulai'),
            'tgl_selesai' => $this->input->post('tgl_selesai'),
            'keterangan' => $this->security->xss_clean($this->input->post('keterangan')),
            'status' => 'Pending'
        ];

        if ($tipe === 'Izin') {
            $data['id_jenis_izin'] = $this->input->post('id_jenis_izin');
        }
        
        if ($tipe === 'Lembur') {
            $data['jam_mulai'] = $this->input->post('jam_mulai');
            $data['jam_selesai'] = $this->input->post('jam_selesai');
        }

        $start = new DateTime($data['tgl_mulai']);
        $end = new DateTime($data['tgl_selesai']);
        $diff = $start->diff($end);
        $data['jumlah_hari'] = $diff->days + 1;
        
        $this->pengajuan->create($data);
        $this->session->set_flashdata('success', 'Pengajuan berhasil dikirim');
        redirect($redirect_target);
    }

    public function manage()
    {
        if (!$this->ion_auth->is_admin()) {
            show_error('Akses Ditolak', 403);
        }

        $user = $this->ion_auth->user()->row();
        $setting = $this->dashboard->getSetting();
        $data = [
            'user' => $user,
            'judul' => 'Kelola Pengajuan',
            'subjudul' => 'Approval Izin/Lembur',
            'setting' => $setting,
            'profile' => $this->dashboard->getProfileAdmin($user->id),
            'list_pengajuan' => $this->pengajuan->get_all_pending(),
            'jenis_izin' => $this->pengajuan->get_jenis_izin()
        ];
        
        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('presensi/pengajuan/manage', $data);
        $this->load->view('_templates/dashboard/_footer');
    }

    public function approve()
    {
        if (!$this->ion_auth->is_admin()) {
            $this->output_json(['status' => false, 'message' => 'Akses ditolak']);
            return;
        }

        $id = $this->input->post('id_pengajuan');
        $status = $this->input->post('status');
        $alasan = $this->input->post('alasan_tolak');
        $user = $this->ion_auth->user()->row();

        if (!in_array($status, ['Disetujui', 'Ditolak'])) {
            $this->output_json(['status' => false, 'message' => 'Status tidak valid']);
            return;
        }

        $result = $this->pengajuan->update_status($id, $status, $user->id, $alasan);
        
        if ($result && $status === 'Disetujui') {
            $pengajuan = $this->pengajuan->get_by_id($id);
            if ($pengajuan && $pengajuan->tipe_pengajuan === 'Lembur') {
                $this->pengajuan->syncLemburToAbsensiLog($id);
            }
        }
        
        $this->output_json(['status' => true, 'message' => 'Pengajuan berhasil di-' . strtolower($status)]);
    }

    public function izinKeluar()
    {
        if (!$this->ion_auth->is_admin() && !$this->ion_auth->in_group('guru')) {
            $this->output_json(['status' => false, 'message' => 'Akses ditolak']);
            return;
        }

        $this->form_validation->set_rules('id_siswa', 'Siswa', 'required|numeric');
        $this->form_validation->set_rules('jam_keluar', 'Jam Keluar', 'required');
        $this->form_validation->set_rules('alasan', 'Alasan', 'required');

        if ($this->form_validation->run() == FALSE) {
            $this->output_json(['status' => false, 'message' => validation_errors()]);
            return;
        }

        $id_siswa = $this->input->post('id_siswa');
        $jam_keluar = $this->input->post('jam_keluar');
        $tanggal = date('Y-m-d');
        $user = $this->ion_auth->user()->row();

        $existing = $this->presensi->getTodayLog($id_siswa, $tanggal);

        if (!$existing || !$existing->jam_masuk) {
            $this->output_json(['status' => false, 'message' => 'Siswa belum check-in hari ini']);
            return;
        }

        if ($existing->jam_pulang) {
            $this->output_json(['status' => false, 'message' => 'Siswa sudah tercatat pulang']);
            return;
        }

        $data = [
            'id_user' => $id_siswa,
            'tipe_pengajuan' => 'IzinKeluar',
            'id_jenis_izin' => $this->input->post('id_jenis_izin'),
            'tgl_mulai' => $tanggal,
            'tgl_selesai' => $tanggal,
            'jam_selesai' => $jam_keluar,
            'jumlah_hari' => 1,
            'keterangan' => $this->security->xss_clean($this->input->post('alasan')),
            'status' => 'Disetujui',
            'approved_by' => $user->id,
            'approved_at' => date('Y-m-d H:i:s')
        ];

        $status_ortu = $this->input->post('status_ortu');
        if ($status_ortu) {
            $data['keterangan'] .= ' [' . $status_ortu . ']';
        }

        $id_pengajuan = $this->pengajuan->create($data);
        if (!$id_pengajuan) {
            $this->output_json(['status' => false, 'message' => 'Gagal menyimpan pengajuan izin keluar']);
            return;
        }

        $this->pengajuan->syncIzinKeluarToPresensiLog($id_pengajuan);

        $this->output_json(['status' => true, 'message' => 'Izin keluar berhasil dicatat']);
    }

    public function formIzinKeluar()
    {
        if (!$this->ion_auth->is_admin() && !$this->ion_auth->in_group('guru')) {
            show_error('Akses Ditolak', 403);
        }

        $user = $this->ion_auth->user()->row();
        $setting = $this->dashboard->getSetting();
        
        $today = date('Y-m-d');
        $siswa_hadir = $this->presensi->getOpenAttendanceUsers($today, ['siswa']);

        $data = [
            'user' => $user,
            'judul' => 'Izin Keluar Siswa',
            'subjudul' => 'Catat Siswa Pulang Lebih Awal',
            'setting' => $setting,
            'profile' => $this->dashboard->getProfileAdmin($user->id),
            'siswa_hadir' => $siswa_hadir,
            'jenis_izin' => $this->pengajuan->get_jenis_izin()
        ];
        
        $this->load_view('presensi/pengajuan/izin_keluar', $data);
    }
}
