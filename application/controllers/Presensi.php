<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Presensi extends CI_Controller
{
    private $user = null;
    private $setting = null;
    private $profile = null;
    private $tp = null;
    private $tp_active = null;
    private $smt = null;
    private $smt_active = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Presensi_model', 'ion_auth_model', 'Dashboard_model']);
        $this->load->library(['ion_auth', 'form_validation']);
        $this->load->helper(['url', 'file']);

        if ($this->ion_auth->logged_in()) {
            $this->user = $this->ion_auth->user()->row();
            $this->setting = $this->Dashboard_model->getSetting();
            $this->profile = $this->Dashboard_model->getProfileAdmin($this->user->id);
            $this->tp = $this->Dashboard_model->getTahun();
            $this->tp_active = $this->Dashboard_model->getTahunActive();
            $this->smt = $this->Dashboard_model->getSemester();
            $this->smt_active = $this->Dashboard_model->getSemesterActive();
        }
    }

    private function load_view($view, $data = [])
    {
        $data['user'] = $this->user;
        $data['setting'] = $this->setting;
        $data['profile'] = $this->profile;
        $data['tp'] = $this->tp;
        $data['tp_active'] = $this->tp_active;
        $data['smt'] = $this->smt;
        $data['smt_active'] = $this->smt_active;
        $data['judul'] = $data['judul'] ?? 'Presensi';
        $data['subjudul'] = $data['subjudul'] ?? '';

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view($view, $data);
        $this->load->view('_templates/dashboard/_footer');
    }

    // =========================================================================
    // USER-FACING METHODS
    // =========================================================================

    public function index()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth/login');
        }

        $this->checkin();
    }

    public function checkin()
    {
        if (!$this->ion_auth->logged_in()) {
            $this->output->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Unauthorized']));
            return;
        }

        $user_id = $this->ion_auth->get_user_id();
        $today = date('Y-m-d');

        $existing_log = $this->Presensi_model->getUserLog($user_id, $today);

        $data = [
            'existing_log' => $existing_log,
            'config' => $this->Presensi_model->getResolvedConfig($user_id),
            'shift' => $this->Presensi_model->getUserShiftForDate($user_id, $today),
            'judul' => 'Presensi',
            'subjudul' => 'Check-In / Check-Out'
        ];

        $this->load_view('presensi/checkin', $data);
    }

    public function do_checkin()
    {
        if (!$this->ion_auth->logged_in()) {
            $this->output->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Unauthorized']));
            return;
        }

        $user_id = $this->ion_auth->get_user_id();

        $this->form_validation->set_rules('lat', 'Latitude', 'trim');
        $this->form_validation->set_rules('lng', 'Longitude', 'trim');
        $this->form_validation->set_rules('qr_token', 'QR Token', 'trim');
        $this->form_validation->set_rules('photo', 'Photo', 'trim');

        if ($this->input->method() === 'post') {
            $input_data = [
                'lat' => $this->input->post('lat'),
                'lng' => $this->input->post('lng'),
                'qr_token' => $this->input->post('qr_token'),
                'photo' => $this->input->post('photo')
            ];
        } else {
            $input_data = [
                'lat' => $this->input->get('lat'),
                'lng' => $this->input->get('lng'),
                'qr_token' => $this->input->get('qr_token'),
                'photo' => $this->input->get('photo')
            ];
        }

        $result = $this->Presensi_model->doCheckIn($user_id, $input_data);

        $this->output->set_content_type('application/json')
            ->set_output(json_encode($result));
    }

    public function do_checkout()
    {
        if (!$this->ion_auth->logged_in()) {
            $this->output->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Unauthorized']));
            return;
        }

        $user_id = $this->ion_auth->get_user_id();

        if ($this->input->method() === 'post') {
            $input_data = [
                'lat' => $this->input->post('lat'),
                'lng' => $this->input->post('lng'),
                'qr_token' => $this->input->post('qr_token')
            ];
        } else {
            $input_data = [
                'lat' => $this->input->get('lat'),
                'lng' => $this->input->get('lng'),
                'qr_token' => $this->input->get('qr_token')
            ];
        }

        $result = $this->Presensi_model->doCheckOut($user_id, $input_data);

        $this->output->set_content_type('application/json')
            ->set_output(json_encode($result));
    }

    // =========================================================================
    // BYPASS REQUEST
    // =========================================================================

    public function bypass_request()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth/login');
        }

        $data = [
            'judul' => 'Presensi',
            'subjudul' => 'Request Bypass'
        ];

        $this->load_view('presensi/bypass_request', $data);
    }

    public function do_bypass_request()
    {
        if (!$this->ion_auth->logged_in()) {
            $this->output->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Unauthorized']));
            return;
        }

        $this->form_validation->set_rules('alasan', 'Alasan', 'required|trim');
        $this->form_validation->set_rules('lokasi', 'Lokasi Alternatif', 'trim');
        $this->form_validation->set_rules('lat', 'Latitude', 'trim');
        $this->form_validation->set_rules('lng', 'Longitude', 'trim');
        $this->form_validation->set_rules('photo', 'Foto Bukti', 'trim');

        if ($this->form_validation->run() === FALSE) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => validation_errors()]));
            return;
        }

        $user_id = $this->ion_auth->get_user_id();

        $input_data = [
            'tipe' => $this->input->post('tipe'),
            'alasan' => $this->input->post('alasan'),
            'lokasi' => $this->input->post('lokasi'),
            'lat' => $this->input->post('lat'),
            'lng' => $this->input->post('lng'),
            'photo' => $this->input->post('photo')
        ];

        $result = $this->Presensi_model->createBypassRequest($user_id, $input_data);

        $this->output->set_content_type('application/json')
            ->set_output(json_encode($result));
    }

    // =========================================================================
    // USER HISTORY
    // =========================================================================

    public function history()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth/login');
        }

        $user_id = $this->ion_auth->get_user_id();

        $start_date = $this->input->get('start_date') ?? date('Y-m-01');
        $end_date = $this->input->get('end_date') ?? date('Y-m-t');

        $logs = $this->Presensi_model->getUserLogs($user_id, $start_date, $end_date);

        $data = [
            'logs' => $logs,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'judul' => 'Presensi',
            'subjudul' => 'Riwayat Presensi'
        ];

        $this->load_view('presensi/history', $data);
    }

    // =========================================================================
    // ADMIN METHODS
    // =========================================================================

    public function dashboard_admin()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth/login');
        }

        if (!$this->ion_auth->is_admin()) {
            show_error('Akses ditolak', 403);
            return;
        }

        $stats = $this->Presensi_model->getTodayStats();

        $data = [
            'stats' => $stats,
            'judul' => 'Presensi',
            'subjudul' => 'Dashboard Admin'
        ];

        $this->load_view('presensi/dashboard_admin', $data);
    }

    public function shift_management()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            show_error('Akses ditolak', 403);
            return;
        }

        $shifts = $this->db->get('presensi_shift')->result();

        $data = [
            'shifts' => $shifts,
            'judul' => 'Presensi',
            'subjudul' => 'Kelola Shift'
        ];

        $this->load_view('presensi/shift_management', $data);
    }

    public function save_shift()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->output->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Akses ditolak']));
            return;
        }

        $this->form_validation->set_rules('nama_shift', 'Nama Shift', 'required|trim');
        $this->form_validation->set_rules('kode_shift', 'Kode Shift', 'required|trim|callback_kode_shift_check');
        $this->form_validation->set_rules('jam_masuk', 'Jam Masuk', 'required');
        $this->form_validation->set_rules('jam_pulang', 'Jam Pulang', 'required');
        $this->form_validation->set_rules('toleransi_masuk_menit', 'Toleransi Masuk', 'numeric');
        $this->form_validation->set_rules('toleransi_pulang_menit', 'Toleransi Pulang', 'numeric');
        $this->form_validation->set_rules('is_lintas_hari', 'Lintas Hari', 'integer');

        if ($this->form_validation->run() === FALSE) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => validation_errors()]));
            return;
        }

        $id_shift = $this->input->post('id_shift');

        $shift_data = [
            'nama_shift' => $this->input->post('nama_shift'),
            'kode_shift' => $this->input->post('kode_shift'),
            'jam_masuk' => $this->input->post('jam_masuk'),
            'jam_pulang' => $this->input->post('jam_pulang'),
            'toleransi_masuk_menit' => $this->input->post('toleransi_masuk_menit') ?: 15,
            'toleransi_pulang_menit' => $this->input->post('toleransi_pulang_menit') ?: 0,
            'is_lintas_hari' => $this->input->post('is_lintas_hari') ?: 0,
            'is_active' => 1
        ];

        if ($id_shift) {
            $this->db->where('id_shift', $id_shift)
                ->update('presensi_shift', $shift_data);
            $message = 'Shift berhasil diupdate';
        } else {
            $this->db->insert('presensi_shift', $shift_data);
            $message = 'Shift berhasil ditambahkan';
        }

        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'message' => $message]));
    }

    public function kode_shift_check($kode)
    {
        $id_shift = $this->input->post('id_shift');

        $this->db->where('kode_shift', $kode);

        if ($id_shift) {
            $this->db->where('id_shift !=', $id_shift);
        }

        $count = $this->db->count_all_results('presensi_shift');

        if ($count > 0) {
            $this->form_validation->set_message('kode_shift_check', 'Kode shift sudah ada');
            return FALSE;
        }

        return TRUE;
    }

    public function delete_shift()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->output->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Akses ditolak']));
            return;
        }

        $id_shift = $this->input->post('id_shift');

        if (!$id_shift) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'ID shift diperlukan']));
            return;
        }

        $this->db->where('id_shift', $id_shift)
            ->delete('presensi_shift');

        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'message' => 'Shift berhasil dihapus']));
    }

    // =========================================================================
    // LOCATION MANAGEMENT
    // =========================================================================

    public function location_management()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            show_error('Akses ditolak', 403);
            return;
        }

        $lokasi = $this->db->get('presensi_lokasi')->result();

        $data = [
            'lokasi' => $lokasi,
            'judul' => 'Presensi',
            'subjudul' => 'Kelola Lokasi'
        ];

        $this->load_view('presensi/location_management', $data);
    }

    public function save_location()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->output->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Akses ditolak']));
            return;
        }

        $this->form_validation->set_rules('nama_lokasi', 'Nama Lokasi', 'required|trim');
        $this->form_validation->set_rules('kode_lokasi', 'Kode Lokasi', 'required|trim|callback_kode_lokasi_check');
        $this->form_validation->set_rules('latitude', 'Latitude', 'required|numeric');
        $this->form_validation->set_rules('longitude', 'Longitude', 'required|numeric');
        $this->form_validation->set_rules('radius_meter', 'Radius (meter)', 'numeric');

        if ($this->form_validation->run() === FALSE) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => validation_errors()]));
            return;
        }

        $id_lokasi = $this->input->post('id_lokasi');
        $is_default = $this->input->post('is_default') ? 1 : 0;

        if ($is_default) {
            $this->db->set('is_default', 0)->update('presensi_lokasi');
        }

        $lokasi_data = [
            'nama_lokasi' => $this->input->post('nama_lokasi'),
            'kode_lokasi' => $this->input->post('kode_lokasi'),
            'alamat' => $this->input->post('alamat'),
            'latitude' => $this->input->post('latitude'),
            'longitude' => $this->input->post('longitude'),
            'radius_meter' => $this->input->post('radius_meter') ?: 100,
            'is_default' => $is_default,
            'is_active' => 1
        ];

        if ($id_lokasi) {
            $this->db->where('id_lokasi', $id_lokasi)
                ->update('presensi_lokasi', $lokasi_data);
            $message = 'Lokasi berhasil diupdate';
        } else {
            $this->db->insert('presensi_lokasi', $lokasi_data);
            $message = 'Lokasi berhasil ditambahkan';
        }

        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'message' => $message]));
    }

    public function kode_lokasi_check($kode)
    {
        $id_lokasi = $this->input->post('id_lokasi');

        $this->db->where('kode_lokasi', $kode);

        if ($id_lokasi) {
            $this->db->where('id_lokasi !=', $id_lokasi);
        }

        $count = $this->db->count_all_results('presensi_lokasi');

        if ($count > 0) {
            $this->form_validation->set_message('kode_lokasi_check', 'Kode lokasi sudah ada');
            return FALSE;
        }

        return TRUE;
    }

    public function delete_location()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->output->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Akses ditolak']));
            return;
        }

        $id_lokasi = $this->input->post('id_lokasi');

        if (!$id_lokasi) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'ID lokasi diperlukan']));
            return;
        }

        $this->db->where('id_lokasi', $id_lokasi)
            ->delete('presensi_lokasi');

        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'message' => 'Lokasi berhasil dihapus']));
    }

    // =========================================================================
    // GROUP CONFIGURATION
    // =========================================================================

    public function group_config()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            show_error('Akses ditolak', 403);
            return;
        }

        $configs = $this->db->select('pg.*, g.name as group_name')
            ->from('presensi_config_group pg')
            ->join('groups g', 'pg.id_group = g.id')
            ->get()
            ->result();

        $shifts = $this->db->get('presensi_shift')->result();
        $lokasi = $this->db->get('presensi_lokasi')->result();

        $data = [
            'configs' => $configs,
            'shifts' => $shifts,
            'lokasi' => $lokasi,
            'judul' => 'Presensi',
            'subjudul' => 'Konfigurasi Group'
        ];

        $this->load_view('presensi/group_config', $data);
    }

    public function save_group_config()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->output->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Akses ditolak']));
            return;
        }

        $id = $this->input->post('id');
        $config_data = [
            'id_group' => $this->input->post('id_group'),
            'nama_konfigurasi' => $this->input->post('nama_konfigurasi'),
            'id_shift_default' => $this->input->post('id_shift_default') ?: null,
            'id_lokasi_default' => $this->input->post('id_lokasi_default') ?: null,
            'validation_mode' => $this->input->post('validation_mode'),
            'require_photo' => $this->input->post('require_photo') ? 1 : null,
            'require_checkout' => $this->input->post('require_checkout') ? 1 : null,
            'allow_bypass' => $this->input->post('allow_bypass') ? 1 : null,
            'enable_overtime' => $this->input->post('enable_overtime') ? 1 : null,
            'overtime_require_approval' => $this->input->post('overtime_require_approval') ? 1 : null,
            'holiday_mode' => $this->input->post('holiday_mode'),
            'follow_academic_calendar' => $this->input->post('follow_academic_calendar') ? 1 : 0,
            'is_active' => 1
        ];

        if ($id) {
            $this->db->where('id', $id)
                ->update('presensi_config_group', $config_data);
            $message = 'Konfigurasi group berhasil diupdate';
        } else {
            $this->db->insert('presensi_config_group', $config_data);
            $message = 'Konfigurasi group berhasil ditambahkan';
        }

        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'message' => $message]));
    }

    public function delete_group_config($id)
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            show_error('Akses ditolak', 403);
            return;
        }

        $this->db->where('id', $id)
            ->delete('presensi_config_group');

        redirect('presensi/group_config');
    }

    // =========================================================================
    // WORKING SCHEDULE
    // =========================================================================

    public function jadwal_kerja()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            show_error('Akses ditolak', 403);
            return;
        }

        $jadwal = $this->db->select('pjk.*, ps.nama_shift, ps.jam_masuk, ps.jam_pulang, g.name as group_name')
            ->from('presensi_jadwal_kerja pjk')
            ->join('presensi_shift ps', 'pjk.id_shift = ps.id_shift')
            ->join('groups g', 'pjk.id_group = g.id')
            ->order_by('g.id, pjk.day_of_week')
            ->get()
            ->result();

        $groups = $this->db->get('groups')->result();
        $shifts = $this->db->get('presensi_shift')->result();

        $data = [
            'jadwal' => $jadwal,
            'groups' => $groups,
            'shifts' => $shifts,
            'judul' => 'Presensi',
            'subjudul' => 'Jadwal Kerja'
        ];

        $this->load_view('presensi/jadwal_kerja', $data);
    }

    public function save_jadwal_kerja()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->output->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Akses ditolak']));
            return;
        }

        $jadwal_data = [
            'id_group' => $this->input->post('id_group'),
            'day_of_week' => $this->input->post('day_of_week'),
            'id_shift' => $this->input->post('id_shift'),
            'is_active' => 1
        ];

        $this->db->delete('presensi_jadwal_kerja', [
            'id_group' => $jadwal_data['id_group'],
            'day_of_week' => $jadwal_data['day_of_week']
        ]);

        $this->db->insert('presensi_jadwal_kerja', $jadwal_data);

        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'message' => 'Jadwal kerja berhasil disimpan']));
    }

    // =========================================================================
    // QR TOKEN GENERATION
    // =========================================================================

    public function generate_qr_token()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            show_error('Akses ditolak', 403);
            return;
        }

        $token_code = bin2hex(random_bytes(32));
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $valid_minutes = $this->input->post('validity_minutes') ?: 5;

        $token_data = [
            'token_code' => $token_code,
            'token_type' => $this->input->post('token_type') ?: 'both',
            'id_lokasi' => $this->input->post('id_lokasi') ?: null,
            'id_shift' => $this->input->post('id_shift') ?: null,
            'tanggal' => $today,
            'valid_from' => $now,
            'valid_until' => date('Y-m-d H:i:s', strtotime('+' . $valid_minutes . ' minutes')),
            'created_by' => $this->ion_auth->get_user_id(),
            'max_usage' => $this->input->post('max_usage') ?: null,
            'is_active' => 1
        ];

        $this->db->insert('presensi_qr_token', $token_data);

        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'token' => $token_code]));
    }

    public function list_qr_tokens()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            show_error('Akses ditolak', 403);
            return;
        }

        $tokens = $this->db->where('tanggal >=', date('Y-m-d'))
            ->order_by('created_at', 'DESC')
            ->get('presensi_qr_token')
            ->result();

        $data = [
            'tokens' => $tokens,
            'judul' => 'Presensi',
            'subjudul' => 'QR Token'
        ];

        $this->load_view('presensi/list_qr_tokens', $data);
    }

    // =========================================================================
    // GLOBAL CONFIG
    // =========================================================================

    public function global_config()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            show_error('Akses ditolak', 403);
            return;
        }

        $configs = $this->Presensi_model->getGlobalConfig();

        $data = [
            'configs' => $configs,
            'judul' => 'Presensi',
            'subjudul' => 'Pengaturan Global'
        ];

        $this->load_view('presensi/global_config', $data);
    }

    public function save_global_config()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->output->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Akses ditolak']));
            return;
        }

        $configs = $this->input->post('config');

        foreach ($configs as $key => $value) {
            $this->db->where('config_key', $key)
                ->update('presensi_config_global', [
                    'config_value' => is_array($value) ? json_encode($value) : $value,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        }

        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'message' => 'Konfigurasi global berhasil disimpan']));
    }
}
