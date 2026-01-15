<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * @property Presensi_model $Presensi_model
 * @property Dashboard_model $Dashboard_model
 * @property Master_model $Master_model
 * @property Ion_auth_model $ion_auth_model
 * @property Ion_auth $ion_auth
 * @property CI_Input $input
 * @property CI_Output $output
 * @property CI_DB_query_builder $db
 * @property CI_Security $security
 */
class Presensi extends CI_Controller
{
    private $user = null;
    private $setting = null;
    private $profile = null;
    private $guru = null;
    private $tp = null;
    private $tp_active = null;
    private $smt = null;
    private $smt_active = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Presensi_model', 'ion_auth_model', 'Dashboard_model', 'Master_model', 'Log_model' => 'logging']);
        $this->load->library(['ion_auth', 'form_validation', 'datatables']);
        $this->load->helper(['url', 'file']);

        if ($this->db->table_exists('presensi_config_global')) {
            $global_config = $this->Presensi_model->getGlobalConfig();

            if (!empty($global_config['timezone'])) {
                @date_default_timezone_set($global_config['timezone']);
            }
        }

        if ($this->ion_auth->logged_in()) {
            $this->user = $this->ion_auth->user()->row();
            $this->setting = $this->Dashboard_model->getSetting();
            $this->profile = $this->Dashboard_model->getProfileAdmin($this->user->id);
            $this->tp = $this->Dashboard_model->getTahun();
            $this->tp_active = $this->Dashboard_model->getTahunActive();
            $this->smt = $this->Dashboard_model->getSemester();
            $this->smt_active = $this->Dashboard_model->getSemesterActive();

            if ($this->ion_auth->in_group('guru') && $this->tp_active && $this->smt_active) {
                $this->guru = $this->Dashboard_model->getDataGuruByUserId($this->user->id, $this->tp_active->id_tp, $this->smt_active->id_smt);
            }
        }

        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
            $this->Presensi_model->runAutoAlphaIfDue();
        }
    }

    public function output_json($data, $encode = true)
    {
        if ($encode) {
            $data = json_encode($data);
        }
        $this->output->set_content_type('application/json')->set_output($data);
    }

    private function load_view($view, $data = [])
    {
        $data['user'] = $this->user;
        $data['setting'] = $this->setting;
        $data['profile'] = $this->profile;
        $data['guru'] = $data['guru'] ?? $this->guru;
        $data['tp'] = $this->tp;
        $data['tp_active'] = $this->tp_active;
        $data['smt'] = $this->smt;
        $data['smt_active'] = $this->smt_active;
        $data['judul'] = $data['judul'] ?? 'Presensi';
        $data['subjudul'] = $data['subjudul'] ?? '';

        if ($this->ion_auth->logged_in() && $this->ion_auth->in_group('guru') && !$this->ion_auth->is_admin()) {
            if (!$data['guru']) {
                $this->load->view('disable_login', $data);
                return;
            }

            $this->load->view('members/guru/templates/header', $data);
            $this->load->view($view, $data);
            $this->load->view('members/guru/templates/footer');
            return;
        }

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view($view, $data);
        $this->load->view('_templates/dashboard/_footer');
    }

    private function get_presensi_work_group_names()
    {
        return ['guru', 'siswa', 'tendik'];
    }

    private function get_presensi_work_groups()
    {
        $allowed_names = $this->get_presensi_work_group_names();

        return $this->db->where_in('name', $allowed_names)
            ->order_by('name', 'ASC')
            ->get('groups')
            ->result();
    }

    private function is_presensi_work_group($id_group)
    {
        $allowed_names = $this->get_presensi_work_group_names();

        if (!$id_group) {
            return false;
        }

        $group = $this->db->select('name')
            ->where('id', $id_group)
            ->get('groups')
            ->row();

        if (!$group) {
            return false;
        }

        return in_array($group->name, $allowed_names, true);
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
            $this->output_json(['success' => false, 'message' => 'Unauthorized'], false);
            $this->output->set_status_header(401);
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
            'subjudul' => 'Masuk / Pulang'
        ];

        $this->load_view('presensi/checkin', $data);
    }

    public function do_checkin()
    {
        if (!$this->ion_auth->logged_in()) {
            $this->output->set_status_header(401);
            $this->output_json(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $user_id = $this->ion_auth->get_user_id();
        $uploaded_file_path = null;

        $this->form_validation->set_rules('lat', 'Latitude', 'trim|numeric');
        $this->form_validation->set_rules('lng', 'Longitude', 'trim|numeric');
        $this->form_validation->set_rules('qr_token', 'QR Token', 'trim');
        $this->form_validation->set_rules('photo', 'Photo', 'trim');

        if ($this->form_validation->run() === FALSE) {
            $this->output_json(['success' => false, 'message' => validation_errors()]);
            return;
        }

        if ($this->input->method() === 'post') {
            $input_data = [
                'lat' => $this->input->post('lat', true),
                'lng' => $this->input->post('lng', true),
                'qr_token' => $this->input->post('qr_token', true),
                'photo' => $this->input->post('photo', true)
            ];

            if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
                $this->load->library('upload');

                $upload_dir = './uploads/presensi/selfie/' . date('Y/m/') ;
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $config_upload = [
                    'upload_path' => $upload_dir,
                    'allowed_types' => 'jpg|jpeg|png',
                    'max_size' => 2048,
                    'encrypt_name' => true,
                    'file_ext_tolower' => true
                ];

                $this->upload->initialize($config_upload);

                if (!$this->upload->do_upload('photo_file')) {
                    $this->output_json(['success' => false, 'message' => strip_tags($this->upload->display_errors())]);
                    return;
                }

                $upload_data = $this->upload->data();
                $uploaded_file_path = $upload_dir . $upload_data['file_name'];
                $input_data['photo'] = 'uploads/presensi/selfie/' . date('Y/m/') . $upload_data['file_name'];
            }
        } else {
            $input_data = [
                'lat' => $this->input->get('lat', true),
                'lng' => $this->input->get('lng', true),
                'qr_token' => $this->input->get('qr_token', true),
                'photo' => $this->input->get('photo', true)
            ];
        }

        $result = $this->Presensi_model->doCheckIn($user_id, $input_data);

        if (isset($result['success']) && !$result['success'] && $uploaded_file_path && file_exists($uploaded_file_path)) {
            unlink($uploaded_file_path);
        }

        $this->output_json($result);
    }

    public function do_checkout()
    {
        if (!$this->ion_auth->logged_in()) {
            $this->output->set_status_header(401);
            $this->output_json(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $user_id = $this->ion_auth->get_user_id();

        // Validate lat/lng if provided
        $lat = $this->input->post('lat', true) ?: $this->input->get('lat', true);
        $lng = $this->input->post('lng', true) ?: $this->input->get('lng', true);
        
        if ($lat !== null && $lat !== '' && !is_numeric($lat)) {
            $this->output_json(['success' => false, 'message' => 'Latitude harus berupa angka']);
            return;
        }
        if ($lng !== null && $lng !== '' && !is_numeric($lng)) {
            $this->output_json(['success' => false, 'message' => 'Longitude harus berupa angka']);
            return;
        }

        if ($this->input->method() === 'post') {
            $input_data = [
                'lat' => $this->input->post('lat', true),
                'lng' => $this->input->post('lng', true),
                'qr_token' => $this->input->post('qr_token', true)
            ];
        } else {
            $input_data = [
                'lat' => $this->input->get('lat', true),
                'lng' => $this->input->get('lng', true),
                'qr_token' => $this->input->get('qr_token', true)
            ];
        }

        $result = $this->Presensi_model->doCheckOut($user_id, $input_data);

        $this->output_json($result);
    }

    // =========================================================================
    // BYPASS REQUEST
    // =========================================================================

    public function bypass_request()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth/login');
        }

        if ($this->ion_auth->in_group('tendik')) {
            $tipe = $this->input->get('tipe', true);
            if (!in_array($tipe, ['checkin', 'checkout', 'both'], true)) {
                $tipe = 'checkin';
            }
            redirect('tendik/bypass_request?tipe=' . $tipe);
            return;
        }

        $tipe = $this->input->get('tipe', true);
        if (!in_array($tipe, ['checkin', 'checkout', 'both'], true)) {
            $tipe = 'checkin';
        }

        if ($this->ion_auth->in_group('siswa')) {
            $this->load->model('Cbt_model', 'cbt');

            if (!$this->tp_active || !$this->smt_active) {
                show_error('Tahun/Semester aktif belum diset', 500);
                return;
            }

            $siswa = $this->cbt->getDataSiswa($this->user->username, $this->tp_active->id_tp, $this->smt_active->id_smt);

            if (!$siswa) {
                $data = [
                    'user' => $this->user,
                    'judul' => 'Presensi',
                    'subjudul' => 'Request Bypass',
                    'setting' => $this->setting,
                    'tp' => $this->tp,
                    'tp_active' => $this->tp_active,
                    'smt' => $this->smt,
                    'smt_active' => $this->smt_active
                ];
                $this->load->view('disable_login', $data);
                return;
            }

            $config = $this->Presensi_model->getResolvedConfig($this->user->id);
            if (!$config || !(int) $config->allow_bypass) {
                $this->session->set_flashdata('error', 'Bypass tidak diizinkan');
                redirect('dashboard');
                return;
            }

            $data = [
                'user' => $this->user,
                'siswa' => $siswa,
                'judul' => 'Presensi',
                'subjudul' => 'Request Bypass',
                'setting' => $this->setting,
                'tipe_default' => $tipe,
                'tp' => $this->tp,
                'tp_active' => $this->tp_active,
                'smt' => $this->smt,
                'smt_active' => $this->smt_active,
                'running_text' => $this->Dashboard_model->getRunningText()
            ];

            $this->load->view('members/siswa/templates/header', $data);
            $this->load->view('members/siswa/presensi/bypass_request', $data);
            $this->load->view('members/siswa/templates/footer');
            return;
        }

        $data = [
            'judul' => 'Presensi',
            'subjudul' => 'Request Bypass',
            'tipe_default' => $tipe
        ];

        $this->load_view('presensi/bypass_request', $data);
    }

    public function do_bypass_request()
    {
        if (!$this->ion_auth->logged_in()) {
            $this->output->set_status_header(401);
            $this->output_json(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $uploaded_file_path = null;

        $this->form_validation->set_rules('tipe', 'Tipe Bypass', 'required|in_list[checkin,checkout,both]|trim');
        $this->form_validation->set_rules('alasan', 'Alasan', 'required|trim');
        $this->form_validation->set_rules('lokasi', 'Lokasi Alternatif', 'trim');
        $this->form_validation->set_rules('lat', 'Latitude', 'trim');
        $this->form_validation->set_rules('lng', 'Longitude', 'trim');
        $this->form_validation->set_rules('photo', 'Foto Bukti', 'trim');

        if ($this->form_validation->run() === FALSE) {
            $this->output_json(['success' => false, 'message' => validation_errors()]);
            return;
        }

        $user_id = $this->ion_auth->get_user_id();

        $input_data = [
            'tipe' => $this->input->post('tipe', true),
            'alasan' => $this->input->post('alasan', true),
            'lokasi' => $this->input->post('lokasi', true),
            'lat' => $this->input->post('lat', true),
            'lng' => $this->input->post('lng', true),
            'photo' => $this->input->post('photo', true)
        ];

        if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
            $this->load->library('upload');

            $upload_dir = './uploads/presensi/bypass/' . date('Y/m/');
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $config_upload = [
                'upload_path' => $upload_dir,
                'allowed_types' => 'jpg|jpeg|png',
                'max_size' => 2048,
                'encrypt_name' => true,
                'file_ext_tolower' => true
            ];

            $this->upload->initialize($config_upload);

            if (!$this->upload->do_upload('photo_file')) {
                $this->output_json(['success' => false, 'message' => strip_tags($this->upload->display_errors())]);
                return;
            }

            $upload_data = $this->upload->data();
            $uploaded_file_path = $upload_dir . $upload_data['file_name'];
            $input_data['photo'] = 'uploads/presensi/bypass/' . date('Y/m/') . $upload_data['file_name'];
        }

        $result = $this->Presensi_model->createBypassRequest($user_id, $input_data);

        if (isset($result['success']) && !$result['success'] && $uploaded_file_path && file_exists($uploaded_file_path)) {
            unlink($uploaded_file_path);
        }

        $this->output_json($result);
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
    // USER SCHEDULE
    // =========================================================================

    public function jadwal()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth/login');
        }

        if ($this->ion_auth->in_group('tendik')) {
            redirect('tendik/jadwal');
            return;
        }

        $user_id = $this->ion_auth->get_user_id();

        $data = [
            'jadwal' => $this->Presensi_model->getWeeklyScheduleForUser($user_id),
            'judul' => 'Presensi',
            'subjudul' => 'Jadwal Presensi'
        ];

        $this->load_view('presensi/jadwal', $data);
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

        $stats_ptk = $this->Presensi_model->getTodayStatsByGroups(['guru', 'tendik']);
        $stats_siswa = $this->Presensi_model->getTodayStatsByGroups(['siswa']);

        $data = [
            'stats_ptk' => $stats_ptk,
            'stats_siswa' => $stats_siswa,
            'judul' => 'Presensi',
            'subjudul' => 'Dashboard Admin'
        ];

        $this->load_view('presensi/dashboard_admin', $data);
    }

    public function bypass_manage()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth/login');
        }

        if (!$this->ion_auth->is_admin()) {
            show_error('Akses ditolak', 403);
            return;
        }

        $status = $this->input->get('status', true);
        if (!in_array($status, ['pending', 'approved', 'rejected', 'used', 'expired'], true)) {
            $status = 'pending';
        }

        $group = $this->input->get('group', true) ?: $this->input->get('jenis', true);
        if (!in_array($group, ['guru', 'tendik', 'siswa'], true)) {
            $group = null;
        }

        $requests = $this->Presensi_model->getBypassRequests($status, $group);

        $data = [
            'requests' => $requests,
            'status_filter' => $status,
            'group_filter' => $group,
            'judul' => 'Presensi',
            'subjudul' => 'Kelola Bypass Request'
        ];

        $this->load_view('presensi/bypass_manage', $data);
    }

    public function bypass_update_status()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->output->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Akses ditolak']));
            return;
        }

        if ($this->input->method() !== 'post') {
            $this->output->set_status_header(405)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Method not allowed']));
            return;
        }

        $this->form_validation->set_rules('id_bypass', 'ID Bypass', 'required|integer');
        $this->form_validation->set_rules('status', 'Status', 'required|in_list[approved,rejected]');
        $this->form_validation->set_rules('catatan_admin', 'Catatan', 'trim');

        if ($this->form_validation->run() === FALSE) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => strip_tags(validation_errors())]));
            return;
        }

        $id_bypass = (int) $this->input->post('id_bypass', true);
        $status = $this->input->post('status', true);
        $note = $this->input->post('catatan_admin', true);

        $admin_id = (int) ($this->ion_auth->user()->row()->id ?? 0);

        $result = $this->Presensi_model->updateBypassRequestStatus($id_bypass, $status, $admin_id, $note);

        $this->output->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => (bool) ($result['success'] ?? false),
                'message' => $result['message'] ?? 'OK'
            ]));
    }

    public function rekap()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth/login');
        }

        if (!$this->ion_auth->is_admin()) {
            show_error('Akses ditolak', 403);
            return;
        }

        $jenis = $this->input->get('jenis', true) ?: $this->input->get('group', true);
        if (!in_array($jenis, ['guru', 'tendik', 'siswa'], true)) {
            $jenis = 'guru';
        }

        $month = (int) ($this->input->get('month', true) ?: date('n'));
        $year = (int) ($this->input->get('year', true) ?: date('Y'));

        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }

        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        $id_kelas = (int) ($this->input->get('kelas', true) ?: 0);
        if (!$id_kelas) {
            $id_kelas = (int) ($this->input->get('id_kelas', true) ?: 0);
        }

        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));
        $days_in_month = (int) date('t', strtotime($start_date));

        $this->load->model('Dropdown_model', 'dropdown');
        $kelas_list = [];
        if ($this->tp_active && $this->smt_active) {
            $kelas_list = $this->dropdown->getAllKelas($this->tp_active->id_tp, $this->smt_active->id_smt);
        }

        $users = [];
        $map = [];
        $kelas_selected_name = null;

        if ($jenis === 'guru') {
            $users = $this->Presensi_model->getRekapGuruUsers();
        } elseif ($jenis === 'tendik') {
            $users = $this->Presensi_model->getRekapTendikUsers();
        } elseif ($jenis === 'siswa') {
            if ($id_kelas && $this->tp_active && $this->smt_active) {
                $users = $this->Presensi_model->getRekapSiswaUsersByKelas($id_kelas, $this->tp_active->id_tp, $this->smt_active->id_smt);
                $kelas_selected_name = $kelas_list[$id_kelas] ?? null;
            }
        }

        $user_ids = [];
        foreach ($users as $u) {
            $user_ids[] = (int) $u->user_id;
        }

        $logs = $this->Presensi_model->getRekapLogsByUsers($user_ids, $start_date, $end_date);
        foreach ($logs as $log) {
            $uid = (int) $log->id_user;
            $tgl = (string) $log->tanggal;
            $map[$uid][$tgl] = $log->status_kehadiran;
        }

        $data = [
            'jenis' => $jenis,
            'month' => $month,
            'year' => $year,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'days_in_month' => $days_in_month,
            'kelas_list' => $kelas_list,
            'kelas_selected' => $id_kelas,
            'kelas_selected_name' => $kelas_selected_name,
            'users' => $users,
            'map' => $map,
            'judul' => 'Presensi',
            'subjudul' => 'Rekap Presensi'
        ];

        $this->load_view('presensi/rekap', $data);
    }

    public function rekap_export()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('auth/login');
        }

        if (!$this->ion_auth->is_admin()) {
            show_error('Akses ditolak', 403);
            return;
        }

        $jenis = $this->input->get('jenis', true) ?: $this->input->get('group', true);
        if (!in_array($jenis, ['guru', 'tendik', 'siswa'], true)) {
            $jenis = 'guru';
        }

        $month = (int) ($this->input->get('month', true) ?: date('n'));
        $year = (int) ($this->input->get('year', true) ?: date('Y'));

        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }

        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        $id_kelas = (int) ($this->input->get('kelas', true) ?: 0);
        if (!$id_kelas) {
            $id_kelas = (int) ($this->input->get('id_kelas', true) ?: 0);
        }

        if ($jenis === 'siswa') {
            if (!$id_kelas) {
                show_error('Pilih kelas terlebih dahulu untuk export rekap siswa.', 400);
                return;
            }

            if (!$this->tp_active || !$this->smt_active) {
                show_error('Tahun ajaran / semester aktif belum diset.', 400);
                return;
            }
        }

        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));
        $days_in_month = (int) date('t', strtotime($start_date));

        $dates = [];
        for ($d = 1; $d <= $days_in_month; $d++) {
            $dates[] = sprintf('%04d-%02d-%02d', $year, $month, $d);
        }

        $kelas_selected_name = null;
        if ($jenis === 'siswa') {
            $this->load->model('Dropdown_model', 'dropdown');
            $kelas_list = $this->dropdown->getAllKelas($this->tp_active->id_tp, $this->smt_active->id_smt);
            $kelas_selected_name = $kelas_list[$id_kelas] ?? null;
        }

        $users = [];
        if ($jenis === 'guru') {
            $users = $this->Presensi_model->getRekapGuruUsers();
        } elseif ($jenis === 'tendik') {
            $users = $this->Presensi_model->getRekapTendikUsers();
        } elseif ($jenis === 'siswa') {
            $users = $this->Presensi_model->getRekapSiswaUsersByKelas($id_kelas, $this->tp_active->id_tp, $this->smt_active->id_smt);
        }

        $user_ids = [];
        foreach ($users as $u) {
            $user_ids[] = (int) $u->user_id;
        }

        $map = [];
        $logs = $this->Presensi_model->getRekapLogsByUsers($user_ids, $start_date, $end_date);
        foreach ($logs as $log) {
            $uid = (int) $log->id_user;
            $tgl = (string) $log->tanggal;
            $map[$uid][$tgl] = $log->status_kehadiran;
        }

        $month_names = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $month_label = $month_names[$month] ?? (string) $month;
        $jenis_label = [
            'guru' => 'Guru',
            'tendik' => 'Tendik',
            'siswa' => 'Siswa'
        ][$jenis] ?? ucfirst($jenis);

        $status_codes = [
            'Hadir' => 'H',
            'Terlambat' => 'T',
            'Pulang Awal' => 'PA',
            'Terlambat + Pulang Awal' => 'TP',
            'Alpha' => 'A',
            'Izin' => 'I',
            'Sakit' => 'S',
            'Cuti' => 'C',
            'Dinas Luar' => 'DL',
        ];

        $base_cols = ($jenis === 'siswa') ? 4 : 3;
        $total_cols = $base_cols + $days_in_month;
        $last_col = Coordinate::stringFromColumnIndex($total_cols);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap');

        $title = 'Rekap Presensi ' . $jenis_label . ' - ' . $month_label . ' ' . $year;
        if ($jenis === 'siswa' && $kelas_selected_name) {
            $title .= ' (' . trim((string) $kelas_selected_name) . ')';
        }

        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:' . $last_col . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Periode: ' . $month_label . ' ' . $year);
        $sheet->mergeCells('A2:' . $last_col . '2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $header_row = 4;
        $col = 1;
        $sheet->setCellValueByColumnAndRow($col++, $header_row, 'No');

        if ($jenis === 'siswa') {
            $sheet->setCellValueByColumnAndRow($col++, $header_row, 'NIS');
        }

        $sheet->setCellValueByColumnAndRow($col++, $header_row, 'Username');
        $sheet->setCellValueByColumnAndRow($col++, $header_row, 'Nama');

        foreach ($dates as $date_str) {
            $day_num = (int) date('j', strtotime($date_str));
            $sheet->setCellValueByColumnAndRow($col++, $header_row, $day_num);
        }

        $header_range = 'A' . $header_row . ':' . $last_col . $header_row;
        $sheet->getStyle($header_range)->getFont()->setBold(true);
        $sheet->getStyle($header_range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($header_range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($header_range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFEFEFEF');

        $day_start_col = $base_cols + 1;
        $sheet->freezePane(Coordinate::stringFromColumnIndex($day_start_col) . ($header_row + 1));
        $sheet->setAutoFilter('A' . $header_row . ':' . $last_col . $header_row);

        $row = $header_row + 1;
        $no = 1;
        foreach ($users as $u) {
            $uid = (int) ($u->user_id ?? 0);
            $col = 1;
            $sheet->setCellValueByColumnAndRow($col++, $row, $no++);

            if ($jenis === 'siswa') {
                $sheet->setCellValueByColumnAndRow($col++, $row, (string) ($u->nis ?? ''));
            }

            $sheet->setCellValueByColumnAndRow($col++, $row, (string) ($u->username ?? ''));
            $sheet->setCellValueByColumnAndRow($col++, $row, (string) ($u->nama ?? ''));

            foreach ($dates as $date_str) {
                $status = $map[$uid][$date_str] ?? null;
                $code = $status ? ($status_codes[$status] ?? (string) $status) : '-';
                $sheet->setCellValueByColumnAndRow($col++, $row, $code);
            }

            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(5);
        $col_idx = 2;
        if ($jenis === 'siswa') {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col_idx))->setWidth(14);
            $col_idx++;
        }
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col_idx))->setWidth(18);
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col_idx + 1))->setWidth(28);

        for ($c = $day_start_col; $c <= $total_cols; $c++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setWidth(4);
        }

        $data_range = Coordinate::stringFromColumnIndex($day_start_col) . ($header_row + 1) . ':' . $last_col . max($header_row + 1, $row - 1);
        $sheet->getStyle($data_range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('A' . ($header_row + 1) . ':' . $last_col . max($header_row + 1, $row - 1))
            ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $ket_sheet = $spreadsheet->createSheet();
        $ket_sheet->setTitle('Keterangan');
        $ket_sheet->setCellValue('A1', 'Kode');
        $ket_sheet->setCellValue('B1', 'Keterangan');
        $ket_sheet->getStyle('A1:B1')->getFont()->setBold(true);
        $ket_sheet->getStyle('A1:B1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFEFEFEF');

        $r = 2;
        foreach ($status_codes as $status => $code) {
            $ket_sheet->setCellValue('A' . $r, $code);
            $ket_sheet->setCellValue('B' . $r, $status);
            $r++;
        }
        $ket_sheet->getColumnDimension('A')->setWidth(8);
        $ket_sheet->getColumnDimension('B')->setWidth(30);

        $spreadsheet->setActiveSheetIndex(0);

        $filename_parts = ['rekap', 'presensi', strtolower($jenis_label), sprintf('%04d-%02d', $year, $month)];
        if ($jenis === 'siswa' && $id_kelas) {
            $filename_parts[] = 'kelas-' . $id_kelas;
        }
        $filename = implode('-', $filename_parts) . '.xlsx';

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }


    // =========================================================================
    // SHIFT MANAGEMENT (Standardized)
    // =========================================================================

    public function shift_management()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('auth');
        }

        $user = $this->ion_auth->user()->row();
        $data = [
            'user' => $user,
            'judul' => 'Presensi',
            'subjudul' => 'Kelola Shift',
            'profile' => $this->Dashboard_model->getProfileAdmin($user->id),
            'setting' => $this->Dashboard_model->getSetting()
        ];

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('presensi/shift_management');
        $this->load->view('_templates/dashboard/_footer');
    }

    public function shift_data()
    {
        $this->output_json($this->Presensi_model->getShiftData(), false);
    }

    public function shift_save()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->output_json(['status' => false, 'msg' => 'Akses ditolak']);
            return;
        }

        $method = $this->input->post('method', true);
        $id_shift = $this->input->post('id_shift', true);
        $nama_shift = $this->input->post('nama_shift', true);
        $kode_shift = $this->input->post('kode_shift', true);
        $jam_masuk = $this->input->post('jam_masuk', true);
        $jam_pulang = $this->input->post('jam_pulang', true);
        $toleransi_masuk = $this->input->post('toleransi_masuk_menit', true);
        $toleransi_pulang = $this->input->post('toleransi_pulang_menit', true);
        $is_lintas_hari = $this->input->post('is_lintas_hari', true) ? 1 : 0;
        $is_active = $this->input->post('is_active', true) ? 1 : 0;

        // Validation
        if ($this->db->get_where('presensi_shift', ['kode_shift' => $kode_shift])->num_rows() > 0 && $method === 'add') {
             $this->output_json(['status' => false, 'msg' => 'Kode Shift sudah ada']);
             return;
        }

        $data = [
            'nama_shift' => $nama_shift,
            'kode_shift' => $kode_shift,
            'jam_masuk' => $jam_masuk,
            'jam_pulang' => $jam_pulang,
            'toleransi_masuk_menit' => $toleransi_masuk,
            'toleransi_pulang_menit' => $toleransi_pulang,
            'is_lintas_hari' => $is_lintas_hari,
            'is_active' => $is_active
        ];

        if ($method === 'add') {
            $action = $this->Master_model->create('presensi_shift', $data);
            if ($action) {
                $this->logging->saveLog(3, 'menambah shift presensi: ' . $nama_shift);
            }
        } else {
            $action = $this->Master_model->update('presensi_shift', $data, 'id_shift', $id_shift);
            if ($action) {
                $this->logging->saveLog(4, 'mengedit shift presensi: ' . $nama_shift);
            }
        }

        $this->output_json(['status' => $action, 'msg' => ($action ? 'Berhasil menyimpan data' : 'Gagal menyimpan data')]);
    }

    public function shift_delete()
    {
        $chk = $this->input->post('checked', true);
        if (!$chk) {
            $this->output_json(['status' => false, 'msg' => 'Tidak ada data yang dipilih']);
            return;
        }

        if ($this->Master_model->delete('presensi_shift', $chk, 'id_shift')) {
            $this->logging->saveLog(5, 'menghapus ' . count($chk) . ' shift presensi');
            $this->output_json(['status' => true, 'total' => count($chk), 'msg' => 'Data berhasil dihapus']);
        } else {
            $this->output_json(['status' => false, 'msg' => 'Gagal menghapus data']);
        }
    }



    // =========================================================================
    // LOCATION MANAGEMENT (Standardized)
    // =========================================================================

    public function location_management()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('auth');
        }

        $user = $this->ion_auth->user()->row();
        $data = [
            'user' => $user,
            'judul' => 'Presensi',
            'subjudul' => 'Kelola Lokasi',
            'profile' => $this->Dashboard_model->getProfileAdmin($user->id),
            'setting' => $this->Dashboard_model->getSetting()
        ];

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('presensi/location_management', $data);
        $this->load->view('_templates/dashboard/_footer');
    }

    public function lokasi_data()
    {
        $this->output_json($this->Presensi_model->getLocationData(), false);
    }

    public function lokasi_save()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->output_json(['status' => false, 'msg' => 'Akses ditolak']);
            return;
        }

        $method = $this->input->post('method', true);
        $id_lokasi = $this->input->post('id_lokasi', true);
        $kode_lokasi = $this->input->post('kode_lokasi', true);
        $is_default = $this->input->post('is_default', true) ? 1 : 0;

        // Validation
        if ($this->db->get_where('presensi_lokasi', ['kode_lokasi' => $kode_lokasi])->num_rows() > 0 && $method === 'add') {
             $this->output_json(['status' => false, 'msg' => 'Kode Lokasi sudah ada']);
             return;
        }

        if ($is_default) {
            $this->db->update('presensi_lokasi', ['is_default' => 0]);
        }

        $nama_lokasi = $this->input->post('nama_lokasi', true);

        $data = [
            'nama_lokasi' => $nama_lokasi,
            'kode_lokasi' => $kode_lokasi,
            'alamat' => $this->input->post('alamat', true),
            'latitude' => $this->input->post('latitude', true),
            'longitude' => $this->input->post('longitude', true),
            'radius_meter' => $this->input->post('radius_meter', true) ?: 100,
            'is_default' => $is_default,
            'is_active' => $this->input->post('is_active', true) ? 1 : 0
        ];

        if ($method === 'add') {
            $action = $this->Master_model->create('presensi_lokasi', $data);
            if ($action) {
                $this->logging->saveLog(3, 'menambah lokasi presensi: ' . $nama_lokasi);
            }
        } else {
            $action = $this->Master_model->update('presensi_lokasi', $data, 'id_lokasi', $id_lokasi);
            if ($action) {
                $this->logging->saveLog(4, 'mengedit lokasi presensi: ' . $nama_lokasi);
            }
        }

        $this->output_json(['status' => $action, 'msg' => ($action ? 'Berhasil menyimpan data' : 'Gagal menyimpan data')]);
    }

    public function lokasi_delete()
    {
        $chk = $this->input->post('checked', true);
        if (!$chk) {
            $this->output_json(['status' => false, 'msg' => 'Tidak ada data yang dipilih']);
            return;
        }

        if ($this->Master_model->delete('presensi_lokasi', $chk, 'id_lokasi')) {
            $this->logging->saveLog(5, 'menghapus ' . count($chk) . ' lokasi presensi');
            $this->output_json(['status' => true, 'total' => count($chk), 'msg' => 'Data berhasil dihapus']);
        } else {
            $this->output_json(['status' => false, 'msg' => 'Gagal menghapus data']);
        }
    }



    // =========================================================================
    // HOLIDAY MANAGEMENT (Standardized)
    // =========================================================================

    public function hari_libur()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            redirect('auth');
        }

        $user = $this->ion_auth->user()->row();
        $data = [
            'user' => $user,
            'judul' => 'Presensi',
            'subjudul' => 'Hari Libur',
            'has_table' => $this->db->table_exists('presensi_hari_libur'),
            'profile' => $this->Dashboard_model->getProfileAdmin($user->id),
            'setting' => $this->Dashboard_model->getSetting()
        ];

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('presensi/hari_libur', $data);
        $this->load->view('_templates/dashboard/_footer');
    }

    public function hari_libur_data()
    {
        $this->output_json($this->Presensi_model->getHolidayData(), false);
    }

    public function hari_libur_save()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->output_json(['status' => false, 'msg' => 'Akses ditolak']);
            return;
        }

        $method = $this->input->post('method', true);
        $id_libur = $this->input->post('id_libur', true);
        $nama_libur = $this->input->post('nama_libur', true);
        
        $data = [
            'tanggal' => $this->input->post('tanggal', true),
            'nama_libur' => $nama_libur,
            'tipe_libur' => $this->input->post('tipe_libur', true),
            'is_recurring' => $this->input->post('is_recurring', true) ? 1 : 0,
            'is_active' => $this->input->post('is_active', true) ? 1 : 0
        ];

        if ($method === 'add') {
            $action = $this->Master_model->create('presensi_hari_libur', $data);
            if ($action) {
                $this->logging->saveLog(3, 'menambah hari libur: ' . $nama_libur);
            }
        } else {
            $action = $this->Master_model->update('presensi_hari_libur', $data, 'id_libur', $id_libur);
            if ($action) {
                $this->logging->saveLog(4, 'mengedit hari libur: ' . $nama_libur);
            }
        }

        $this->output_json(['status' => $action, 'msg' => ($action ? 'Berhasil menyimpan data' : 'Gagal menyimpan data')]);
    }

    public function hari_libur_delete()
    {
        $chk = $this->input->post('checked', true);
        if (!$chk) {
            $this->output_json(['status' => false, 'msg' => 'Tidak ada data yang dipilih']);
            return;
        }

        if ($this->Master_model->delete('presensi_hari_libur', $chk, 'id_libur')) {
            $this->logging->saveLog(5, 'menghapus ' . count($chk) . ' hari libur');
            $this->output_json(['status' => true, 'total' => count($chk), 'msg' => 'Data berhasil dihapus']);
        } else {
            $this->output_json(['status' => false, 'msg' => 'Gagal menghapus data']);
        }
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

        $groups = $this->get_presensi_work_groups();

        $shifts = $this->db->get('presensi_shift')->result();
        $lokasi = $this->db->get('presensi_lokasi')->result();

        $data = [
            'configs' => $configs,
            'groups' => $groups,
            'allowed_group_names' => $this->get_presensi_work_group_names(),
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
        $id_group = $this->input->post('id_group');

        if (!$id_group) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Group wajib dipilih']));
            return;
        }

        if (!$this->is_presensi_work_group($id_group)) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Group presensi hanya untuk Guru, Siswa, dan Tendik (Satpam termasuk Tendik).']));
            return;
        }

        // Tri-state handler: '' => NULL (inherit), '0' => 0 (disabled), '1' => 1 (enabled)
        $require_photo = $this->input->post('require_photo');
        $require_checkout = $this->input->post('require_checkout');
        $allow_bypass = $this->input->post('allow_bypass');
        $enable_overtime = $this->input->post('enable_overtime');
        $overtime_require_approval = $this->input->post('overtime_require_approval');

        $config_data = [
            'id_group' => $id_group,
            'nama_konfigurasi' => $this->input->post('nama_konfigurasi'),
            'id_shift_default' => $this->input->post('id_shift_default') ?: null,
            'id_lokasi_default' => $this->input->post('id_lokasi_default') ?: null,
            'validation_mode' => $this->input->post('validation_mode'),
            // Tri-state: empty string = NULL (inherit), otherwise cast to int
            'require_photo' => ($require_photo === '' || $require_photo === null) ? null : (int) $require_photo,
            'require_checkout' => ($require_checkout === '' || $require_checkout === null) ? null : (int) $require_checkout,
            'allow_bypass' => ($allow_bypass === '' || $allow_bypass === null) ? null : (int) $allow_bypass,
            'enable_overtime' => ($enable_overtime === '' || $enable_overtime === null) ? null : (int) $enable_overtime,
            'overtime_require_approval' => ($overtime_require_approval === '' || $overtime_require_approval === null) ? null : (int) $overtime_require_approval,
            'holiday_mode' => $this->input->post('holiday_mode'),
            'follow_academic_calendar' => $this->input->post('follow_academic_calendar') ? 1 : 0,
            'is_active' => 1
        ];

        if (!$id) {
            $existing = $this->db->select('id')
                ->where('id_group', $id_group)
                ->get('presensi_config_group')
                ->row();

            if ($existing) {
                $id = $existing->id;
            }
        }

        if ($id) {
            $success = $this->db->where('id', $id)
                ->update('presensi_config_group', $config_data);
            $message = 'Konfigurasi group berhasil diupdate';
            if ($success) {
                $this->logging->saveLog(4, 'mengedit konfigurasi group presensi: ' . $config_data['nama_konfigurasi']);
            }
        } else {
            $success = $this->db->insert('presensi_config_group', $config_data);
            $message = 'Konfigurasi group berhasil ditambahkan';
            if ($success) {
                $this->logging->saveLog(3, 'menambah konfigurasi group presensi: ' . $config_data['nama_konfigurasi']);
            }
        }

        if (!$success) {
            $db_error = $this->db->error();
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Gagal menyimpan konfigurasi group: ' . ($db_error['message'] ?? 'Unknown error')]));
            return;
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

        $groups = $this->get_presensi_work_groups();
        $shifts = $this->db->get('presensi_shift')->result();

        $jadwal_tendik = [];
        $tipe_tendik_list = [];
        $has_jadwal_tendik_table = $this->db->table_exists('presensi_jadwal_tendik');
        $has_jadwal_user_table = $this->db->table_exists('presensi_jadwal_user');

        $this->load->model('Tendik_model', 'tendik');
        $tipe_tendik_list = $this->tendik->get_tipe_list();

        if ($has_jadwal_tendik_table) {
            $jadwal_tendik = $this->db->select('pjt.*, ps.nama_shift, ps.jam_masuk, ps.jam_pulang')
                ->from('presensi_jadwal_tendik pjt')
                ->join('presensi_shift ps', 'pjt.id_shift = ps.id_shift')
                ->order_by('pjt.tipe_tendik, pjt.day_of_week')
                ->get()
                ->result();
        }

	        $data = [
	            'jadwal' => $jadwal,
	            'groups' => $groups,
	            'allowed_group_names' => $this->get_presensi_work_group_names(),
	            'shifts' => $shifts,
	            'jadwal_tendik' => $jadwal_tendik,
	            'tipe_tendik_list' => $tipe_tendik_list,
	            'has_jadwal_tendik_table' => $has_jadwal_tendik_table,
	            'has_jadwal_user_table' => $has_jadwal_user_table,
	            'judul' => 'Presensi',
	            'subjudul' => 'Jadwal Presensi'
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

        $id_group = $this->input->post('id_group');
        $day_of_week = $this->input->post('day_of_week');
        $id_shift = $this->input->post('id_shift');

        if (!$id_group || !$day_of_week || !$id_shift) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Group, hari, dan shift wajib diisi']));
            return;
        }

	        if (!$this->is_presensi_work_group($id_group)) {
	            $this->output->set_content_type('application/json')
	                ->set_output(json_encode(['success' => false, 'message' => 'Jadwal presensi hanya untuk group Guru, Siswa, dan Tendik (Satpam termasuk Tendik).']));
	            return;
	        }

        $jadwal_data = [
            'id_group' => $id_group,
            'day_of_week' => $day_of_week,
            'id_shift' => $id_shift,
            'is_active' => 1
        ];

        $this->db->delete('presensi_jadwal_kerja', [
            'id_group' => $jadwal_data['id_group'],
            'day_of_week' => $jadwal_data['day_of_week']
        ]);

	        if (!$this->db->insert('presensi_jadwal_kerja', $jadwal_data)) {
	            $db_error = $this->db->error();
	            $this->output->set_content_type('application/json')
	                ->set_output(json_encode(['success' => false, 'message' => 'Gagal menyimpan jadwal presensi: ' . ($db_error['message'] ?? 'Unknown error')]));
	            return;
	        }

	        $this->output->set_content_type('application/json')
	            ->set_output(json_encode(['success' => true, 'message' => 'Jadwal presensi berhasil disimpan']));
	    }

    public function save_jadwal_tendik()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->output->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Akses ditolak']));
            return;
        }

        if (!$this->db->table_exists('presensi_jadwal_tendik')) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Tabel presensi_jadwal_tendik belum ada. Jalankan update SQL Presensi terlebih dahulu.']));
            return;
        }

        $this->load->model('Tendik_model', 'tendik');

        $tipe_tendik = strtoupper((string) $this->input->post('tipe_tendik', true));
        $day_of_week = (int) $this->input->post('day_of_week', true);
        $id_shift = (int) $this->input->post('id_shift', true);

        if (!$tipe_tendik || $day_of_week < 1 || $day_of_week > 7 || $id_shift <= 0) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Tipe tendik, hari, dan shift wajib diisi']));
            return;
        }

        $tipe_list = $this->tendik->get_tipe_list();
        if (!in_array($tipe_tendik, $tipe_list, true)) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Tipe tendik tidak valid']));
            return;
        }

        $shift_exists = $this->db->where('id_shift', $id_shift)->count_all_results('presensi_shift');
        if ((int) $shift_exists <= 0) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Shift tidak ditemukan']));
            return;
        }

        $jadwal_data = [
            'tipe_tendik' => $tipe_tendik,
            'day_of_week' => $day_of_week,
            'id_shift' => $id_shift,
            'is_active' => 1
        ];

        $this->db->delete('presensi_jadwal_tendik', [
            'tipe_tendik' => $jadwal_data['tipe_tendik'],
            'day_of_week' => $jadwal_data['day_of_week']
        ]);

        if (!$this->db->insert('presensi_jadwal_tendik', $jadwal_data)) {
            $db_error = $this->db->error();
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Gagal menyimpan jadwal tendik: ' . ($db_error['message'] ?? 'Unknown error')]));
            return;
        }

        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'message' => 'Jadwal tendik berhasil disimpan']));
    }

    public function delete_jadwal_tendik()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->output->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Akses ditolak']));
            return;
        }

        if (!$this->db->table_exists('presensi_jadwal_tendik')) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Tabel presensi_jadwal_tendik belum ada. Jalankan update SQL Presensi terlebih dahulu.']));
            return;
        }

        $this->load->model('Tendik_model', 'tendik');

        $tipe_tendik = strtoupper((string) $this->input->post('tipe_tendik', true));
        $day_of_week = (int) $this->input->post('day_of_week', true);

        if (!$tipe_tendik || $day_of_week < 1 || $day_of_week > 7) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Tipe tendik dan hari wajib diisi']));
            return;
        }

        $tipe_list = $this->tendik->get_tipe_list();
        if (!in_array($tipe_tendik, $tipe_list, true)) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Tipe tendik tidak valid']));
            return;
        }

        $this->db->where('tipe_tendik', $tipe_tendik)
            ->where('day_of_week', $day_of_week)
            ->delete('presensi_jadwal_tendik');

        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'message' => 'Jadwal tendik berhasil dihapus']));
    }

    public function search_users()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->output->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['results' => []]));
            return;
        }

        $q = trim((string) $this->input->get('q', true));
        $group = strtolower(trim((string) $this->input->get('group', true)));
        $allowed = $this->get_presensi_work_group_names();

        if ($group && !in_array($group, $allowed, true)) {
            $group = '';
        }

        $this->db->select('u.id, u.username, u.first_name, u.last_name, GROUP_CONCAT(DISTINCT g.name) as group_names', false);
        $this->db->from('users u');
        $this->db->join('users_groups ug', 'u.id = ug.user_id');
        $this->db->join('groups g', 'ug.group_id = g.id');
        $this->db->where_in('g.name', $allowed);
        if ($group) {
            $this->db->where('g.name', $group);
        }
        if ($q !== '') {
            $this->db->group_start()
                ->like('u.username', $q)
                ->or_like('u.first_name', $q)
                ->or_like('u.last_name', $q)
                ->group_end();
        }
        $this->db->group_by('u.id');
        $this->db->order_by('u.username', 'ASC');
        $this->db->limit(20);
        $rows = $this->db->get()->result();

        $results = [];
        foreach ($rows as $row) {
            $name = trim((string) $row->first_name . ' ' . (string) $row->last_name);
            $label = $name !== '' ? ($name . ' (' . $row->username . ')') : (string) $row->username;
            if (!empty($row->group_names)) {
                $label .= ' - ' . $row->group_names;
            }
            $results[] = [
                'id' => (int) $row->id,
                'text' => $label
            ];
        }

        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['results' => $results]));
    }

    private function is_presensi_work_user($id_user)
    {
        if (!$id_user) {
            return false;
        }

        $allowed = $this->get_presensi_work_group_names();
        $groups = $this->ion_auth_model->get_users_groups($id_user)->result();

        foreach ($groups as $g) {
            if (in_array($g->name, $allowed, true)) {
                return true;
            }
        }

        return false;
    }

    public function get_jadwal_user()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->output->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Akses ditolak']));
            return;
        }

        if (!$this->db->table_exists('presensi_jadwal_user')) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Tabel presensi_jadwal_user belum ada. Jalankan update SQL Presensi terlebih dahulu.']));
            return;
        }

        $id_user = (int) $this->input->get('id_user', true);
        if ($id_user <= 0 || !$this->is_presensi_work_user($id_user)) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'User tidak valid']));
            return;
        }

        $user = $this->db->select('id, username, first_name, last_name')
            ->where('id', $id_user)
            ->get('users')
            ->row();

        $rows = $this->db->select('day_of_week, id_shift')
            ->where('id_user', $id_user)
            ->where('is_active', 1)
            ->get('presensi_jadwal_user')
            ->result();

        $overrides = [];
        foreach ($rows as $row) {
            $overrides[] = [
                'day_of_week' => (int) $row->day_of_week,
                'id_shift' => $row->id_shift === null ? null : (int) $row->id_shift
            ];
        }

        $this->output->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'user' => $user,
                'overrides' => $overrides
            ]));
    }

    public function save_jadwal_user_weekly()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->output->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Akses ditolak']));
            return;
        }

        if (!$this->db->table_exists('presensi_jadwal_user')) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Tabel presensi_jadwal_user belum ada. Jalankan update SQL Presensi terlebih dahulu.']));
            return;
        }

        $id_user = (int) $this->input->post('id_user', true);
        if ($id_user <= 0 || !$this->is_presensi_work_user($id_user)) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'User tidak valid']));
            return;
        }

        $schedule = $this->input->post('schedule', true);
        if (!is_array($schedule)) {
            $schedule = [];
        }

        $valid_shift_ids = $this->db->select('id_shift')->get('presensi_shift')->result();
        $shift_set = [];
        foreach ($valid_shift_ids as $row) {
            $shift_set[(int) $row->id_shift] = true;
        }

        $insert_batch = [];
        for ($day = 1; $day <= 7; $day++) {
            $value = $schedule[$day] ?? $schedule[(string) $day] ?? '';
            $value = is_string($value) ? trim($value) : $value;

            if ($value === '' || $value === null) {
                continue; // inherit
            }

            if ($value === 'OFF') {
                $insert_batch[] = [
                    'id_user' => $id_user,
                    'day_of_week' => $day,
                    'id_shift' => null,
                    'is_active' => 1
                ];
                continue;
            }

            $id_shift = (int) $value;
            if ($id_shift <= 0 || empty($shift_set[$id_shift])) {
                $this->output->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'message' => 'Shift tidak valid untuk hari ' . $day]));
                return;
            }

            $insert_batch[] = [
                'id_user' => $id_user,
                'day_of_week' => $day,
                'id_shift' => $id_shift,
                'is_active' => 1
            ];
        }

        $this->db->trans_start();
        $this->db->where('id_user', $id_user)->delete('presensi_jadwal_user');
        if (!empty($insert_batch)) {
            $this->db->insert_batch('presensi_jadwal_user', $insert_batch);
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            $db_error = $this->db->error();
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Gagal menyimpan jadwal user: ' . ($db_error['message'] ?? 'Unknown error')]));
            return;
        }

        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'message' => 'Jadwal user berhasil disimpan']));
    }

    public function clear_jadwal_user_weekly()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->output->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Akses ditolak']));
            return;
        }

        if (!$this->db->table_exists('presensi_jadwal_user')) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Tabel presensi_jadwal_user belum ada. Jalankan update SQL Presensi terlebih dahulu.']));
            return;
        }

        $id_user = (int) $this->input->post('id_user', true);
        if ($id_user <= 0 || !$this->is_presensi_work_user($id_user)) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'User tidak valid']));
            return;
        }

        $this->db->where('id_user', $id_user)->delete('presensi_jadwal_user');

        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'message' => 'Jadwal user berhasil direset']));
    }

    public function delete_jadwal_kerja()
    {
        if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
            $this->output->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Akses ditolak']));
            return;
        }

        $id_group = $this->input->post('id_group');
        $day_of_week = $this->input->post('day_of_week');

        if (!$id_group || !$day_of_week) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Group dan hari wajib diisi']));
            return;
        }

	        $this->db->where('id_group', $id_group)
	            ->where('day_of_week', $day_of_week)
	            ->delete('presensi_jadwal_kerja');

	        $this->output->set_content_type('application/json')
	            ->set_output(json_encode(['success' => true, 'message' => 'Jadwal presensi berhasil dihapus']));
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
            'subjudul' => 'Pengaturan Sistem'
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

        $configs = $this->input->post('config', true);

        if (!$configs || !is_array($configs)) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Tidak ada konfigurasi yang dikirim']));
            return;
        }

        $allowed_keys = [
            'max_bypass_per_month',
            'bypass_auto_approve',
            'qr_validity_minutes',
            'qr_refresh_interval',
            'enable_overtime',
            'overtime_require_approval',
            'min_overtime_minutes',
            'auto_alpha_enabled',
            'auto_alpha_time',
            'timezone'
        ];

        foreach ($configs as $key => $value) {
            if (!in_array($key, $allowed_keys, true)) {
                continue;
            }

            $this->db->where('config_key', $key)
                ->update('presensi_config_global', [
                    'config_value' => is_array($value) ? json_encode($value) : $value,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        }

        $this->logging->saveLog(4, 'mengedit konfigurasi global presensi');

        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'message' => 'Konfigurasi sistem berhasil disimpan']));
    }
}
