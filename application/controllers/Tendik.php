<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Tendik extends CI_Controller
{
    private $user;
    private $tendik_data;

    public function __construct()
    {
        parent::__construct();

        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        }

        if (!$this->ion_auth->in_group('tendik')) {
            show_error('Halaman ini hanya untuk Tenaga Kependidikan. <a href="' . base_url('dashboard') . '">Kembali</a>', 403, 'Akses Ditolak');
        }

        $this->load->model('Tendik_model', 'tendik');
        $this->load->model('Dashboard_model', 'dashboard');

        $this->user = $this->ion_auth->user()->row();
        $this->tendik_data = $this->tendik->get_by_user_id($this->user->id);
    }

    private function output_json($data, $encode = true)
    {
        if ($encode) $data = json_encode($data);
        $this->output->set_content_type('application/json')->set_output($data);
    }

    private function getCommonData()
    {
        $setting = $this->dashboard->getSetting();
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();

        return [
            'user' => $this->user,
            'setting' => $setting,
            'tp_active' => $tp,
            'smt_active' => $smt,
            'tendik' => $this->tendik_data,
            'profile' => (object)[
                'nama_lengkap' => $this->tendik_data ? $this->tendik_data->nama_tendik : 'Unknown',
                'jabatan' => $this->tendik_data ? $this->tendik_data->tipe_tendik : '',
                'foto' => $this->tendik_data && $this->tendik_data->foto ? base_url($this->tendik_data->foto) : base_url('assets/adminlte/dist/img/avatar5.png')
            ]
        ];
    }

    private function load_view($view, $data)
    {
        $this->load->view('members/tendik/templates/header_topnav', $data);
        $this->load->view($view, $data);
        $this->load->view('members/tendik/templates/footer_topnav');
    }

    public function index()
    {
        $this->load->model('Presensi_model', 'presensi');

        $data = $this->getCommonData();
        $data['judul'] = 'Dashboard Tenaga Kependidikan';
        $data['subjudul'] = $this->tendik_data ? $this->tendik_data->nama_tendik : '';

        $today = date('Y-m-d');
        $data['today_log'] = $this->presensi->getTodayLog($this->user->id, $today);
        $data['shift'] = $this->presensi->getUserShiftForDate($this->user->id, $today);
        $data['open_log'] = $this->presensi->getOpenAttendanceLog($this->user->id);
        $data['open_shift'] = $data['open_log'] && $data['open_log']->id_shift
            ? $this->presensi->getShiftById($data['open_log']->id_shift)
            : null;
        $data['presensi_config'] = $this->presensi->getResolvedConfig($this->user->id);

        $this->load_view('members/tendik/dashboard', $data);
    }

    public function absensi()
    {
        redirect(base_url('tendik') . '#presensi');
    }

    public function presensi()
    {
        redirect(base_url('tendik') . '#presensi');
    }

    public function bypass_request()
    {
        $this->load->model('Presensi_model', 'presensi');

        $tipe = $this->input->get('tipe', true);
        if (!in_array($tipe, ['checkin', 'checkout', 'both'], true)) {
            $tipe = 'checkin';
        }

        $config = $this->presensi->getResolvedConfig($this->user->id);
        if (!$config || !(int) $config->allow_bypass) {
            $this->session->set_flashdata('error', 'Bypass tidak diizinkan');
            redirect(base_url('tendik') . '#presensi');
            return;
        }

        $data = $this->getCommonData();
        $data['judul'] = 'Presensi';
        $data['subjudul'] = 'Request Bypass';
        $data['tipe_default'] = $tipe;

        $this->load_view('members/tendik/bypass_request', $data);
    }

    public function riwayat()
    {
        $this->load->model('Presensi_model', 'presensi');
        
        $data = $this->getCommonData();
        $data['judul'] = 'Riwayat Presensi';
        $data['subjudul'] = 'Rekap Kehadiran';
        
        $month = $this->input->get('month') ?: date('m');
        $year = $this->input->get('year') ?: date('Y');
        
        $data['month'] = $month;
        $data['year'] = $year;
        $data['logs'] = $this->presensi->getMonthlyLogs($this->user->id, $month, $year);
        
        $this->load_view('members/tendik/riwayat', $data);
    }

    public function jadwal()
    {
        $this->load->model('Presensi_model', 'presensi');
        
        $data = $this->getCommonData();
        $data['judul'] = 'Jadwal Presensi';
        $data['subjudul'] = 'Jadwal Shift';
        
        $data['jadwal'] = $this->presensi->getWeeklyScheduleForUser($this->user->id);
        
        $this->load_view('members/tendik/jadwal', $data);
    }

    public function pengajuan()
    {
        $this->load->model('Pengajuan_model', 'pengajuan_model');
        
        $data = $this->getCommonData();
        $data['judul'] = 'Pengajuan Izin/Cuti';
        $data['subjudul'] = 'Daftar Pengajuan';
        
        $data['list_pengajuan'] = $this->pengajuan_model->get_by_user($this->user->id);
        $data['jenis_izin'] = $this->pengajuan_model->get_jenis_izin();
        
        $this->load_view('members/tendik/pengajuan', $data);
    }

    public function profil()
    {
        $data = $this->getCommonData();
        $data['judul'] = 'Profil Saya';
        $data['subjudul'] = 'Informasi Akun';
        
        $this->load_view('members/tendik/profil', $data);
    }

    public function change_password()
    {
        if ($this->input->method() !== 'post') {
            $this->output->set_status_header(405);
            $this->output_json(['status' => false, 'message' => 'Method not allowed']);
            return;
        }

        $this->load->library('form_validation');

        $min_password_length = (int) $this->config->item('min_password_length', 'ion_auth');
        if ($min_password_length <= 0) {
            $min_password_length = 6;
        }

        $this->form_validation->set_rules('old', 'Password Lama', 'required|trim');
        $this->form_validation->set_rules('new', 'Password Baru', 'required|trim|min_length[' . $min_password_length . ']|matches[new_confirm]');
        $this->form_validation->set_rules('new_confirm', 'Konfirmasi Password Baru', 'required|trim');

        if ($this->form_validation->run() === FALSE) {
            $errors = [
                'old' => strip_tags((string) form_error('old')),
                'new' => strip_tags((string) form_error('new')),
                'new_confirm' => strip_tags((string) form_error('new_confirm'))
            ];

            $this->output_json(['status' => false, 'errors' => $errors, 'message' => 'Validasi gagal']);
            return;
        }

        $identity = $this->session->userdata('identity');
        $old_password = $this->input->post('old', true);
        $new_password = $this->input->post('new', true);

        $change = $this->ion_auth->change_password($identity, $old_password, $new_password);
        if ($change) {
            $this->output_json(['status' => true, 'message' => 'Password berhasil diubah']);
            return;
        }

        $this->output_json(['status' => false, 'message' => strip_tags((string) $this->ion_auth->errors())]);
    }
}
