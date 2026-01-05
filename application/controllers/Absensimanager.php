<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Absensimanager Controller
 * 
 * DEPRECATION NOTICE:
 * This controller handles legacy admin absensi management routes.
 * New features should be added to Absensi.php controller instead.
 * 
 * Overlapping functionality:
 * - shift management -> use Absensi::shift(), Absensi::saveShift(), Absensi::deleteShift()
 * - assign shift -> use Absensi::assignShift(), Absensi::saveAssignment()
 * 
 * This controller is kept for backward compatibility with existing menu links/routes.
 * 
 * @deprecated Consider migrating to Absensi.php controller
 */
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
        $this->load->model('Tendik_model', 'tendik');
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

        $data['total_tendik'] = count($this->tendik->get_all());
        $data['shift_active'] = count($this->shift->getAllShifts());
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
            'shifts' => $this->shift->getAllShifts()
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

        $result = $id
            ? $this->shift->updateShift($id, $data)
            : (bool) $this->shift->createShift($data);

        $this->output_json(['status' => (bool) $result]);
    }

    public function delete_shift()
    {
        $id = $this->input->post('id_shift');
        if (!$id) {
            $this->output_json(['status' => false, 'message' => 'ID tidak valid']);
            return;
        }
        $result = $this->shift->deactivateShift($id);
        $this->output_json(['status' => (bool) $result]);
    }

    // TENDIK MANAGEMENT
    public function karyawan()
    {
        $user = $this->ion_auth->user()->row();
        $setting = $this->dashboard->getSetting();
        $data = [
            'user' => $user,
            'judul' => 'Data Tenaga Kependidikan',
            'subjudul' => 'Manajemen Tendik',
            'setting' => $setting,
            'profile' => $this->dashboard->getProfileAdmin($user->id),
            'tendik_list' => $this->tendik->get_all(),
            'shifts' => $this->shift->getAllShifts()
        ];

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('absensi/admin/karyawan', $data);
        $this->load->view('_templates/dashboard/_footer');
    }

    public function save_karyawan()
    {
        $id = $this->input->post('id_tendik');
        $data = [
            'nama_tendik' => $this->input->post('nama_tendik'),
            'nip' => $this->input->post('nip'),
            'jabatan' => $this->input->post('jabatan'),
            'no_hp' => $this->input->post('no_hp'),
            'email' => $this->input->post('email'),
            'alamat' => $this->input->post('alamat'),
            'tipe_tendik' => $this->input->post('tipe_tendik'),
            'jenis_kelamin' => $this->input->post('jenis_kelamin'),
            'agama' => $this->input->post('agama'),
            'tempat_lahir' => $this->input->post('tempat_lahir'),
            'tgl_lahir' => $this->input->post('tgl_lahir')
        ];

        if ($id) {
            $this->tendik->update($id, $data);
        } else {
            $this->tendik->create($data);
        }

        $this->output_json(['status' => true]);
    }

    public function save_shift_assignment()
    {
        $id_user = $this->input->post('id_user');
        $id_shift = $this->input->post('id_shift');
        $tgl_efektif = $this->input->post('tgl_efektif');

        if ($id_user && $id_shift && $tgl_efektif) {
            $this->shift->assignFixedShift($id_user, $id_shift, $tgl_efektif);
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
        $guru_list = $this->shift->getAllGuruWithShift();
        
        $data = [
            'user' => $user,
            'judul' => 'Assign Shift Guru',
            'subjudul' => 'Pengaturan Shift untuk Guru',
            'setting' => $setting,
            'profile' => $this->dashboard->getProfileAdmin($user->id),
            'guru_list' => $guru_list,
            'shifts' => $this->shift->getAllShifts()
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

        $this->shift->assignFixedShift($id_user, $id_shift, $tgl_efektif);
        $this->output_json(['status' => true, 'message' => 'Shift guru berhasil diatur']);
    }

    public function delete_guru_shift()
    {
        $id_user = $this->input->post('id_user');
        if (!$id_user) {
            $this->output_json(['status' => false, 'message' => 'ID tidak valid']);
            return;
        }
        
        $this->shift->removeUserShift($id_user);
        $this->output_json(['status' => true, 'message' => 'Shift guru berhasil dihapus']);
    }

    public function get_guru_shift_data()
    {
        $guru_list = $this->shift->getAllGuruWithShift();
        $this->output_json(['status' => true, 'data' => $guru_list]);
    }
}
