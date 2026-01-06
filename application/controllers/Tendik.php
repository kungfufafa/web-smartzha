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
        $data = $this->getCommonData();
        $data['judul'] = 'Dashboard Tenaga Kependidikan';
        $data['subjudul'] = $this->tendik_data ? $this->tendik_data->nama_tendik : '';

        $this->load_view('members/tendik/dashboard', $data);
    }

    public function absensi()
    {
        $this->load->model('Presensi_model', 'presensi');
        $this->load->model('Shift_model', 'shift');
        
        $data = $this->getCommonData();
        $data['judul'] = 'Absensi';
        $data['subjudul'] = 'Check-in / Check-out';
        
        $today = date('Y-m-d');
        $data['today_log'] = $this->presensi->getTodayLog($this->user->id, $today);
        $data['shift'] = $this->shift->getShiftForUser($this->user->id, $today);
        
        $this->load_view('members/tendik/absensi', $data);
    }

    public function riwayat()
    {
        $this->load->model('Presensi_model', 'presensi');
        
        $data = $this->getCommonData();
        $data['judul'] = 'Riwayat Absensi';
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
        $this->load->model('Shift_model', 'shift');
        
        $data = $this->getCommonData();
        $data['judul'] = 'Jadwal Kerja';
        $data['subjudul'] = 'Jadwal Shift';
        
        $data['jadwal'] = $this->shift->getWeeklyScheduleForUser($this->user->id);
        
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
}
