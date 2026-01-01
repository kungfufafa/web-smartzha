<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pengajuan extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        }
        $this->load->model('Dashboard_model', 'dashboard');
        $this->load->model('Pengajuan_model', 'pengajuan');
    }

    public function index()
    {
        $user = $this->ion_auth->user()->row();
        $setting = $this->dashboard->getSetting();
        $data = [
            'user' => $user,
            'judul' => 'Pengajuan Izin/Cuti',
            'subjudul' => 'Daftar Pengajuan',
            'setting' => $setting,
            'list_pengajuan' => $this->pengajuan->get_by_user($user->id),
            'jenis_izin' => $this->pengajuan->get_jenis_izin()
        ];
        
        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('absensi/pengajuan/index', $data);
        $this->load->view('_templates/dashboard/_footer');
    }

    public function create()
    {
        $user = $this->ion_auth->user()->row();
        $tipe = $this->input->post('tipe_pengajuan');
        
        $data = [
            'id_user' => $user->id,
            'tipe_pengajuan' => $tipe,
            'tgl_mulai' => $this->input->post('tgl_mulai'),
            'tgl_selesai' => $this->input->post('tgl_selesai'),
            'keterangan' => $this->input->post('keterangan'),
            'status' => 'Pending'
        ];

        if ($tipe == 'Izin') {
            $data['id_jenis_izin'] = $this->input->post('id_jenis_izin');
        } elseif ($tipe == 'Lembur') {
            $data['jam_mulai'] = $this->input->post('jam_mulai');
            $data['jam_selesai'] = $this->input->post('jam_selesai');
        }
        
        $this->pengajuan->create($data);
        redirect('pengajuan');
    }
}
