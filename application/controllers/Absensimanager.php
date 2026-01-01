<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Absensimanager extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        } elseif (!$this->ion_auth->is_admin()) {
            show_error('Hanya Administrator yang diberi hak untuk mengakses halaman ini, <a href="' . base_url('dashboard') . '">Kembali ke menu awal</a>', 403, 'Akses Terlarang');
        }
        $this->load->library(['datatables', 'form_validation']);
        $this->load->model('Dashboard_model', 'dashboard');
        $this->load->model('Shift_model', 'shift');
        $this->load->model('Karyawan_model', 'karyawan');
        $this->load->model('Absensi_model', 'absensi');
        $this->form_validation->set_error_delimiters('', '');
    }

    public function output_json($data, $encode = true)
    {
        if ($encode) {
            $data = json_encode($data);
        }
        $this->output->set_content_type('application/json')->set_output($data);
    }

    public function index()
    {
        $user = $this->ion_auth->user()->row();
        $setting = $this->dashboard->getSetting();
        $today = date('Y-m-d');
        
        $data = [
            'user' => $user,
            'judul' => 'Manajemen Absensi',
            'subjudul' => 'Dashboard Absensi',
            'setting' => $setting,
            'profile' => $this->dashboard->getProfileAdmin($user->id)
        ];

        $data['total_karyawan'] = count($this->karyawan->get_all());
        $data['shift_active'] = count($this->shift->get_all_shifts());
        $data['hadir_hari_ini'] = $this->absensi->count_today_attendance($today);
        $data['terlambat_hari_ini'] = $this->absensi->count_late_today($today);
        $data['logs_hari_ini'] = $this->absensi->get_today_logs($today);
        
        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('absensi/dashboard', $data);
        $this->load->view('_templates/dashboard/_footer');
    }

    // SHIFT MANAGEMENT
    public function shift()
    {
        $user = $this->ion_auth->user()->row();
        $setting = $this->dashboard->getSetting();
        $data = [
            'user' => $user,
            'judul' => 'Manajemen Shift',
            'subjudul' => 'Data Shift Kerja',
            'setting' => $setting,
            'profile' => $this->dashboard->getProfileAdmin($user->id),
            'shifts' => $this->shift->get_all_shifts()
        ];
        
        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('absensi/shift/index', $data);
        $this->load->view('_templates/dashboard/_footer');
    }

    public function save_shift()
    {
        $id = $this->input->post('id_shift');
        $data = [
            'nama_shift' => $this->input->post('nama_shift'),
            'kode_shift' => $this->input->post('kode_shift'),
            'jam_masuk' => $this->input->post('jam_masuk'),
            'jam_pulang' => $this->input->post('jam_pulang'),
            'lintas_hari' => $this->input->post('lintas_hari') ? 1 : 0,
            'jam_awal_checkin' => $this->input->post('jam_awal_checkin'),
            'jam_akhir_checkin' => $this->input->post('jam_akhir_checkin'),
            'is_active' => 1
        ];

        if ($id) {
            $this->db->where('id_shift', $id);
            $this->db->update('master_shift', $data);
        } else {
            $this->db->insert('master_shift', $data);
        }
        
        $this->output_json(['status' => true]);
    }

    public function delete_shift()
    {
        $id = $this->input->post('id_shift');
        if (!$id) {
            $this->output_json(['status' => false, 'message' => 'ID tidak valid']);
            return;
        }
        $this->db->where('id_shift', $id);
        $this->db->update('master_shift', ['is_active' => 0]);
        $this->output_json(['status' => true]);
    }

    // KARYAWAN MANAGEMENT
    public function karyawan()
    {
        $user = $this->ion_auth->user()->row();
        $setting = $this->dashboard->getSetting();
        $data = [
            'user' => $user,
            'judul' => 'Data Karyawan',
            'subjudul' => 'Manajemen Staff/Karyawan',
            'setting' => $setting,
            'profile' => $this->dashboard->getProfileAdmin($user->id),
            'karyawan' => $this->karyawan->get_all(),
            'shifts' => $this->shift->get_all_shifts()
        ];
        
        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('absensi/karyawan/index', $data);
        $this->load->view('_templates/dashboard/_footer');
    }

    public function save_karyawan()
    {
        $id = $this->input->post('id_karyawan');
        $data = [
            'nama_karyawan' => $this->input->post('nama_karyawan'),
            'nip' => $this->input->post('nip'),
            'jabatan' => $this->input->post('jabatan'),
            'no_hp' => $this->input->post('no_hp'),
            'alamat' => $this->input->post('alamat')
        ];

        if ($id) {
            $this->karyawan->update($id, $data);
        } else {
            $this->karyawan->create($data);
        }
        
        $this->output_json(['status' => true]);
    }

    public function save_shift_assignment()
    {
        $id_user = $this->input->post('id_user');
        $id_shift = $this->input->post('id_shift');
        $tgl_efektif = $this->input->post('tgl_efektif');

        if ($id_user && $id_shift && $tgl_efektif) {
            $this->shift->assign_fixed_shift($id_user, $id_shift, $tgl_efektif);
            $this->output_json(['status' => true, 'message' => 'Shift berhasil diatur']);
        } else {
            $this->output_json(['status' => false, 'message' => 'Data tidak lengkap']);
        }
    }

    // ASSIGN SHIFT TO GURU
    public function assign()
    {
        $user = $this->ion_auth->user()->row();
        $setting = $this->dashboard->getSetting();
        
        // Get all guru with their current shift assignment
        $this->load->model('Master_model', 'master');
        $guru_list = $this->shift->get_all_guru_with_shift();
        
        $data = [
            'user' => $user,
            'judul' => 'Assign Shift Guru',
            'subjudul' => 'Pengaturan Shift untuk Guru',
            'setting' => $setting,
            'profile' => $this->dashboard->getProfileAdmin($user->id),
            'guru_list' => $guru_list,
            'shifts' => $this->shift->get_all_shifts()
        ];
        
        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('absensi/assign/index', $data);
        $this->load->view('_templates/dashboard/_footer');
    }

    public function save_guru_shift()
    {
        $this->form_validation->set_rules('id_user', 'Guru', 'required');
        $this->form_validation->set_rules('id_shift', 'Shift', 'required');
        $this->form_validation->set_rules('tgl_efektif', 'Tanggal Efektif', 'required');

        if ($this->form_validation->run() == FALSE) {
            $this->output_json([
                'status' => false, 
                'message' => validation_errors()
            ]);
            return;
        }

        $id_user = $this->input->post('id_user');
        $id_shift = $this->input->post('id_shift');
        $tgl_efektif = $this->input->post('tgl_efektif');

        $this->shift->assign_fixed_shift($id_user, $id_shift, $tgl_efektif);
        $this->output_json(['status' => true, 'message' => 'Shift guru berhasil diatur']);
    }

    public function delete_guru_shift()
    {
        $id_user = $this->input->post('id_user');
        if (!$id_user) {
            $this->output_json(['status' => false, 'message' => 'ID tidak valid']);
            return;
        }
        
        $this->shift->remove_user_shift($id_user);
        $this->output_json(['status' => true, 'message' => 'Shift guru berhasil dihapus']);
    }

    public function get_guru_shift_data()
    {
        $guru_list = $this->shift->get_all_guru_with_shift();
        $this->output_json(['status' => true, 'data' => $guru_list]);
    }
}
