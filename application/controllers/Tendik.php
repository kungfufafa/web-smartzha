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

    public function index()
    {
        $data = $this->getCommonData();
        $data['judul'] = 'Dashboard Tenaga Kependidikan';
        $data['subjudul'] = $this->tendik_data ? $this->tendik_data->nama_tendik : '';

        $this->load->view('members/tendik/templates/header', $data);
        $this->load->view('members/tendik/dashboard', $data);
        $this->load->view('members/tendik/templates/footer');
    }
}
