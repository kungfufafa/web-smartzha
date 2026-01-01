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
        } else {
            $this->load->view('_templates/dashboard/_header', $data);
            $this->load->view($view, $data);
            $this->load->view('_templates/dashboard/_footer');
        }
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
            'profile' => $this->dashboard->getProfileAdmin($user->id),
            'list_pengajuan' => $this->pengajuan->get_by_user($user->id),
            'jenis_izin' => $this->pengajuan->get_jenis_izin()
        ];
        
        $this->load_view('absensi/pengajuan/index', $data);
    }

    public function create()
    {
        $this->form_validation->set_rules('tipe_pengajuan', 'Tipe Pengajuan', 'required|in_list[Izin,Lembur]');
        $this->form_validation->set_rules('tgl_mulai', 'Tanggal Mulai', 'required');
        $this->form_validation->set_rules('tgl_selesai', 'Tanggal Selesai', 'required');
        $this->form_validation->set_rules('keterangan', 'Keterangan', 'required|max_length[500]');

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('pengajuan');
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

        if ($tipe == 'Izin') {
            $data['id_jenis_izin'] = $this->input->post('id_jenis_izin');
        } elseif ($tipe == 'Lembur') {
            $data['jam_mulai'] = $this->input->post('jam_mulai');
            $data['jam_selesai'] = $this->input->post('jam_selesai');
        }
        
        $this->pengajuan->create($data);
        $this->session->set_flashdata('success', 'Pengajuan berhasil dikirim');
        redirect('pengajuan');
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
        $this->load->view('absensi/pengajuan/manage', $data);
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

        $this->pengajuan->update_status($id, $status, $user->id, $alasan);
        $this->output_json(['status' => true, 'message' => 'Pengajuan berhasil di-' . strtolower($status)]);
    }
}
