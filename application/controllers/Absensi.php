<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Absensi extends CI_Controller
{
    private $is_admin = false;
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
        
        $this->load->library(['datatables', 'form_validation']);
        $this->load->model('Dashboard_model', 'dashboard');
        $this->load->model('Absensi_model', 'absensi');
        $this->load->model('Shift_model', 'shift');
        $this->load->helper('absen');
        $this->form_validation->set_error_delimiters('', '');
        
        $this->is_admin = $this->ion_auth->is_admin();
        $this->is_guru = $this->ion_auth->in_group('guru');
        
        if ($this->is_guru) {
            $this->tp = $this->dashboard->getTahunActive();
            $this->smt = $this->dashboard->getSemesterActive();
            $user = $this->ion_auth->user()->row();
            $this->guru = $this->dashboard->getDataGuruByUserId($user->id, $this->tp->id_tp, $this->smt->id_smt);
        }
    }

    private function output_json($data, $encode = true)
    {
        if ($encode) $data = json_encode($data);
        $this->output->set_content_type('application/json')->set_output($data);
    }

    private function getCommonData()
    {
        $user = $this->ion_auth->user()->row();
        $setting = $this->dashboard->getSetting();
        return [
            'user' => $user,
            'setting' => $setting,
            'profile' => $this->dashboard->getProfileAdmin($user->id),
            'is_admin' => $this->is_admin,
            'pending_bypass_count' => $this->is_admin ? $this->absensi->countPendingBypass() : 0,
            'pending_pengajuan_count' => $this->is_admin ? $this->absensi->countPendingPengajuan() : 0
        ];
    }

    private function loadView($view, $data)
    {
        if ($this->is_guru && !$this->is_admin) {
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

    private function requireAdmin()
    {
        if (!$this->is_admin) {
            show_error('Hanya Administrator yang dapat mengakses halaman ini', 403, 'Akses Ditolak');
        }
    }

    public function index()
    {
        if ($this->is_admin) {
            $this->dashboard_admin();
        } else {
            $this->checkin();
        }
    }

    public function dashboard_admin()
    {
        $this->requireAdmin();
        $data = $this->getCommonData();
        $data['judul'] = 'Absensi';
        $data['subjudul'] = 'Dashboard Absensi';
        
        $today = date('Y-m-d');
        $data['stats'] = $this->absensi->getDashboardStats($today);
        $data['recent_logs'] = $this->absensi->getRecentLogs(10);
        $data['late_today'] = $this->absensi->getLateToday($today);
        $data['not_checked_in'] = $this->absensi->getNotCheckedIn($today);
        
        $this->loadView('absensi/admin/dashboard', $data);
    }

    public function checkin()
    {
        $data = $this->getCommonData();
        $data['judul'] = 'Absensi';
        $data['subjudul'] = 'Check-in / Check-out';
        
        $user = $this->ion_auth->user()->row();
        $today = date('Y-m-d');
        
        $data['log'] = $this->absensi->getTodayLog($user->id, $today);
        $data['shift'] = $this->shift->getUserShift($user->id, $today);
        $data['config'] = $this->absensi->getConfig();
        $data['locations'] = $this->absensi->getActiveLocations();
        $data['has_bypass'] = $this->absensi->hasApprovedBypass($user->id, $today);
        
        $this->loadView('absensi/checkin', $data);
    }

    public function doCheckin()
    {
        $user = $this->ion_auth->user()->row();
        $method = $this->input->post('method');
        $lat = $this->input->post('lat');
        $lng = $this->input->post('lng');
        $foto = $this->input->post('foto');
        $qr_token = $this->input->post('qr_token');
        $id_lokasi = $this->input->post('id_lokasi');
        
        $today = date('Y-m-d');
        $time = date('H:i:s');
        
        $shift = $this->shift->getUserShift($user->id, $today);
        if (!$shift) {
            $this->output_json(['status' => false, 'message' => 'Anda tidak memiliki jadwal shift hari ini.']);
            return;
        }
        
        $log = $this->absensi->getTodayLog($user->id, $today);
        if ($log && $log->jam_masuk) {
            $this->output_json(['status' => false, 'message' => 'Anda sudah melakukan check-in hari ini.']);
            return;
        }
        
        $config = $this->absensi->getConfig();
        $validation_result = $this->validateAttendanceMethod($method, $lat, $lng, $qr_token, $id_lokasi, $user->id, $today, 'checkin', $config);
        
        if (!$validation_result['valid']) {
            $this->output_json(['status' => false, 'message' => $validation_result['message']]);
            return;
        }
        
        $status = 'Hadir';
        $terlambat_menit = 0;
        $toleransi = isset($shift->toleransi_terlambat) ? $shift->toleransi_terlambat : 0;
        
        $jam_masuk_toleransi = date('H:i:s', strtotime($shift->jam_masuk) + ($toleransi * 60));
        if ($time > $jam_masuk_toleransi) {
            $status = 'Terlambat';
            $start = strtotime($shift->jam_masuk);
            $end = strtotime($time);
            $terlambat_menit = round(($end - $start) / 60);
        }
        
        $foto_filename = $this->handlePhotoUpload($foto, 'checkin', $user->id);
        
        $insert_data = [
            'id_user' => $user->id,
            'id_shift' => $shift->id_shift,
            'id_lokasi' => $validation_result['id_lokasi'],
            'tanggal' => $today,
            'jam_masuk' => date('Y-m-d H:i:s'),
            'status_kehadiran' => $status,
            'metode_masuk' => $method,
            'lat_masuk' => $lat,
            'long_masuk' => $lng,
            'foto_masuk' => $foto_filename,
            'qr_token_masuk' => $qr_token,
            'bypass_id' => $validation_result['bypass_id'] ?? null,
            'device_info' => $this->input->user_agent(),
            'terlambat_menit' => $terlambat_menit
        ];
        
        $this->absensi->clockIn($insert_data);
        $this->absensi->logAudit(null, $user->id, 'checkin', $user->id, $insert_data);
        
        if ($qr_token) {
            $this->absensi->incrementQrUsage($qr_token);
        }
        
        $this->output_json(['status' => true, 'message' => 'Check-in berhasil! Status: ' . $status]);
    }

    public function doCheckout()
    {
        $user = $this->ion_auth->user()->row();
        $method = $this->input->post('method');
        $lat = $this->input->post('lat');
        $lng = $this->input->post('lng');
        $qr_token = $this->input->post('qr_token');
        
        $today = date('Y-m-d');
        $time = date('H:i:s');
        
        $log = $this->absensi->getTodayLog($user->id, $today);
        if (!$log) {
            $this->output_json(['status' => false, 'message' => 'Anda belum check-in hari ini.']);
            return;
        }
        if ($log->jam_pulang) {
            $this->output_json(['status' => false, 'message' => 'Anda sudah check-out hari ini.']);
            return;
        }
        
        $config = $this->absensi->getConfig();
        $validation_result = $this->validateAttendanceMethod($method, $lat, $lng, $qr_token, $log->id_lokasi, $user->id, $today, 'checkout', $config);
        
        if (!$validation_result['valid']) {
            $this->output_json(['status' => false, 'message' => $validation_result['message']]);
            return;
        }
        
        $shift = $this->shift->getShiftById($log->id_shift);
        $pulang_awal_menit = 0;
        $status = $log->status_kehadiran;
        
        if ($time < $shift->jam_pulang) {
            $start = strtotime($time);
            $end = strtotime($shift->jam_pulang);
            $pulang_awal_menit = round(($end - $start) / 60);
            
            if ($log->status_kehadiran == 'Terlambat') {
                $status = 'Terlambat + Pulang Awal';
            } else {
                $status = 'Pulang Awal';
            }
        }
        
        $update_data = [
            'jam_pulang' => date('Y-m-d H:i:s'),
            'metode_pulang' => $method,
            'lat_pulang' => $lat,
            'long_pulang' => $lng,
            'qr_token_pulang' => $qr_token,
            'status_kehadiran' => $status,
            'pulang_awal_menit' => $pulang_awal_menit
        ];
        
        $this->absensi->clockOut($log->id_log, $update_data);
        $this->absensi->logAudit($log->id_log, $user->id, 'checkout', $user->id, $update_data);
        
        $this->output_json(['status' => true, 'message' => 'Check-out berhasil!']);
    }

    private function validateAttendanceMethod($method, $lat, $lng, $qr_token, $id_lokasi, $id_user, $date, $type, $config)
    {
        $result = ['valid' => false, 'message' => '', 'id_lokasi' => $id_lokasi, 'bypass_id' => null];
        
        if ($method === 'GPS') {
            if (!$this->absensi->isMethodEnabled('enable_gps', $config)) {
                $result['message'] = 'Metode GPS tidak diaktifkan.';
                return $result;
            }
            
            $bypass = $this->absensi->getApprovedBypass($id_user, $date, $type);
            if ($bypass) {
                $result['valid'] = true;
                $result['bypass_id'] = $bypass->id_bypass;
                $this->absensi->markBypassUsed($bypass->id_bypass);
                return $result;
            }
            
            $locations = $this->absensi->getActiveLocations();
            $nearest = null;
            $min_distance = PHP_INT_MAX;
            
            foreach ($locations as $loc) {
                $distance = calculate_distance($lat, $lng, $loc->latitude, $loc->longitude);
                if ($distance <= $loc->radius_meter && $distance < $min_distance) {
                    $min_distance = $distance;
                    $nearest = $loc;
                }
            }
            
            if (!$nearest) {
                $default_loc = $this->absensi->getDefaultLocation();
                $distance = calculate_distance($lat, $lng, $default_loc->latitude, $default_loc->longitude);
                $result['message'] = 'Anda berada di luar area (' . round($distance) . 'm). Radius maksimal: ' . $default_loc->radius_meter . 'm.';
                return $result;
            }
            
            $result['valid'] = true;
            $result['id_lokasi'] = $nearest->id_lokasi;
            
        } elseif ($method === 'QR') {
            if (!$this->absensi->isMethodEnabled('enable_qr', $config)) {
                $result['message'] = 'Metode QR tidak diaktifkan.';
                return $result;
            }
            
            $qr = $this->absensi->validateQrToken($qr_token, $date, $type);
            if (!$qr['valid']) {
                $result['message'] = $qr['message'];
                return $result;
            }
            
            $result['valid'] = true;
            $result['id_lokasi'] = $qr['id_lokasi'];
            
        } else {
            $result['message'] = 'Metode absensi tidak valid.';
        }
        
        return $result;
    }

    private function handlePhotoUpload($base64_photo, $type, $user_id)
    {
        if (empty($base64_photo)) return null;
        
        $upload_path = FCPATH . 'uploads/absensi/' . date('Y/m/');
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }
        
        $filename = $type . '_' . $user_id . '_' . date('Ymd_His') . '.jpg';
        $foto_data = preg_replace('#^data:image/\w+;base64,#i', '', $base64_photo);
        file_put_contents($upload_path . $filename, base64_decode($foto_data));
        
        return 'uploads/absensi/' . date('Y/m/') . $filename;
    }

    public function riwayat()
    {
        $data = $this->getCommonData();
        $data['judul'] = 'Absensi';
        $data['subjudul'] = 'Riwayat Absensi';
        
        $user = $this->ion_auth->user()->row();
        $bulan = $this->input->get('bulan') ?: date('m');
        $tahun = $this->input->get('tahun') ?: date('Y');
        
        $data['bulan'] = $bulan;
        $data['tahun'] = $tahun;
        $data['logs'] = $this->absensi->getHistory($user->id, $bulan, $tahun);
        $data['rekap'] = $this->absensi->getRekapBulanan($user->id, $bulan, $tahun);
        
        $this->loadView('absensi/riwayat', $data);
    }

    public function jadwal()
    {
        $data = $this->getCommonData();
        $data['judul'] = 'Absensi';
        $data['subjudul'] = 'Jadwal Shift Saya';
        
        $user = $this->ion_auth->user()->row();
        $start_week = date('Y-m-d', strtotime('monday this week'));
        $end_week = date('Y-m-d', strtotime('sunday this week'));
        $start_next = date('Y-m-d', strtotime('monday next week'));
        $end_next = date('Y-m-d', strtotime('sunday next week'));
        
        $data['schedule_this_week'] = $this->shift->getUserShiftSchedule($user->id, $start_week, $end_week);
        $data['schedule_next_week'] = $this->shift->getUserShiftSchedule($user->id, $start_next, $end_next);
        $data['current_shift'] = $this->shift->getUserShift($user->id, date('Y-m-d'));
        $data['start_of_week'] = $start_week;
        $data['end_of_week'] = $end_week;
        $data['start_next_week'] = $start_next;
        $data['end_next_week'] = $end_next;
        
        $this->loadView('absensi/jadwal', $data);
    }

    public function config()
    {
        $this->requireAdmin();
        $data = $this->getCommonData();
        $data['judul'] = 'Absensi';
        $data['subjudul'] = 'Konfigurasi Absensi';
        $data['config'] = $this->absensi->getAllConfig();
        $data['groups'] = $this->ion_auth->groups()->result();
        $data['group_config'] = $this->absensi->getAllGroupConfig();
        
        $this->loadView('absensi/admin/config', $data);
    }

    public function saveConfig()
    {
        $this->requireAdmin();
        $configs = $this->input->post('config');
        
        if ($configs && is_array($configs)) {
            foreach ($configs as $key => $value) {
                $this->absensi->updateConfig($key, $value);
            }
        }
        
        $this->output_json(['status' => true, 'message' => 'Konfigurasi berhasil disimpan']);
    }

    public function lokasi()
    {
        $this->requireAdmin();
        $data = $this->getCommonData();
        $data['judul'] = 'Absensi';
        $data['subjudul'] = 'Kelola Lokasi';
        
        $this->loadView('absensi/admin/lokasi', $data);
    }

    public function dataLokasi()
    {
        $this->requireAdmin();
        $this->output_json($this->absensi->getDataTableLokasi(), false);
    }

    public function saveLokasi()
    {
        $this->requireAdmin();
        $this->form_validation->set_rules('nama_lokasi', 'Nama Lokasi', 'required');
        $this->form_validation->set_rules('kode_lokasi', 'Kode Lokasi', 'required');
        $this->form_validation->set_rules('latitude', 'Latitude', 'required|numeric');
        $this->form_validation->set_rules('longitude', 'Longitude', 'required|numeric');
        
        if ($this->form_validation->run() == FALSE) {
            $this->output_json(['status' => false, 'message' => validation_errors()]);
            return;
        }
        
        $id = $this->input->post('id_lokasi');
        $data = [
            'nama_lokasi' => $this->input->post('nama_lokasi'),
            'kode_lokasi' => strtoupper($this->input->post('kode_lokasi')),
            'alamat' => $this->input->post('alamat'),
            'latitude' => $this->input->post('latitude'),
            'longitude' => $this->input->post('longitude'),
            'radius_meter' => $this->input->post('radius_meter') ?: 100,
            'is_default' => $this->input->post('is_default') ? 1 : 0,
            'is_active' => 1
        ];
        
        if ($data['is_default']) {
            $this->absensi->resetDefaultLocation();
        }
        
        $result = $id ? $this->absensi->updateLokasi($id, $data) : $this->absensi->createLokasi($data);
        $this->output_json(['status' => $result, 'message' => $result ? 'Lokasi berhasil disimpan' : 'Gagal menyimpan lokasi']);
    }

    public function deleteLokasi()
    {
        $this->requireAdmin();
        $id = $this->input->post('id_lokasi');
        $result = $this->absensi->deleteLokasi($id);
        $this->output_json(['status' => $result, 'message' => $result ? 'Lokasi dihapus' : 'Gagal menghapus']);
    }

    public function shift()
    {
        $this->requireAdmin();
        $data = $this->getCommonData();
        $data['judul'] = 'Absensi';
        $data['subjudul'] = 'Kelola Shift';
        $data['shifts'] = $this->shift->getAllShifts();
        
        $this->loadView('absensi/admin/shift', $data);
    }

    public function saveShift()
    {
        $this->requireAdmin();
        $id = $this->input->post('id_shift');
        $data = [
            'nama_shift' => $this->input->post('nama_shift'),
            'kode_shift' => strtoupper($this->input->post('kode_shift')),
            'jam_masuk' => $this->input->post('jam_masuk'),
            'jam_pulang' => $this->input->post('jam_pulang'),
            'lintas_hari' => $this->input->post('lintas_hari') ? 1 : 0,
            'toleransi_terlambat' => $this->input->post('toleransi_terlambat') ?: 0,
            'jam_awal_checkin' => $this->input->post('jam_awal_checkin') ?: null,
            'jam_akhir_checkin' => $this->input->post('jam_akhir_checkin') ?: null,
            'is_active' => 1
        ];
        
        $result = $id ? $this->shift->updateShift($id, $data) : $this->shift->createShift($data);
        $this->output_json(['status' => $result, 'message' => $result ? 'Shift disimpan' : 'Gagal menyimpan']);
    }

    public function deleteShift()
    {
        $this->requireAdmin();
        $id = $this->input->post('id_shift');
        $this->shift->deactivateShift($id);
        $this->output_json(['status' => true]);
    }

    public function assignShift()
    {
        $this->requireAdmin();
        $data = $this->getCommonData();
        $data['judul'] = 'Absensi';
        $data['subjudul'] = 'Assign Shift';
        $data['guru_list'] = $this->shift->getAllGuruWithShift();
        $data['karyawan_list'] = $this->absensi->getAllKaryawanWithShift();
        $data['shifts'] = $this->shift->getAllShifts();
        
        $this->loadView('absensi/admin/assign', $data);
    }

    public function saveAssignment()
    {
        $this->requireAdmin();
        $id_user = $this->input->post('id_user');
        $id_shift = $this->input->post('id_shift');
        $tgl_efektif = $this->input->post('tgl_efektif');
        
        if (!$id_user || !$id_shift || !$tgl_efektif) {
            $this->output_json(['status' => false, 'message' => 'Data tidak lengkap']);
            return;
        }
        
        $this->shift->assignFixedShift($id_user, $id_shift, $tgl_efektif);
        $this->output_json(['status' => true, 'message' => 'Shift berhasil diassign']);
    }

    public function qrcode()
    {
        $this->requireAdmin();
        $data = $this->getCommonData();
        $data['judul'] = 'Absensi';
        $data['subjudul'] = 'QR Code Generator';
        $data['locations'] = $this->absensi->getActiveLocations();
        $data['shifts'] = $this->shift->getAllShifts();
        $data['active_tokens'] = $this->absensi->getActiveTokens(date('Y-m-d'));
        
        $this->loadView('absensi/admin/qrcode', $data);
    }

    public function generateQr()
    {
        $this->requireAdmin();
        $user = $this->ion_auth->user()->row();
        $config = $this->absensi->getConfig();
        
        $validity_minutes = $this->absensi->getConfigValue('qr_validity_minutes', $config) ?: 5;
        
        $token_code = bin2hex(random_bytes(16));
        $data = [
            'token_code' => $token_code,
            'token_type' => $this->input->post('token_type') ?: 'both',
            'id_lokasi' => $this->input->post('id_lokasi') ?: null,
            'id_shift' => $this->input->post('id_shift') ?: null,
            'tanggal' => date('Y-m-d'),
            'valid_from' => date('Y-m-d H:i:s'),
            'valid_until' => date('Y-m-d H:i:s', strtotime("+{$validity_minutes} minutes")),
            'created_by' => $user->id,
            'max_usage' => $this->input->post('max_usage') ?: null
        ];
        
        $this->absensi->createQrToken($data);
        $this->output_json(['status' => true, 'token' => $token_code, 'valid_until' => $data['valid_until']]);
    }

    public function getActiveQr()
    {
        $this->requireAdmin();
        $tokens = $this->absensi->getActiveTokens(date('Y-m-d'));
        $this->output_json(['status' => true, 'data' => $tokens]);
    }

    public function bypass()
    {
        $data = $this->getCommonData();
        $data['judul'] = 'Absensi';
        $data['subjudul'] = 'Pengajuan Bypass Lokasi';
        
        $user = $this->ion_auth->user()->row();
        $data['my_requests'] = $this->absensi->getUserBypassRequests($user->id);
        
        $this->loadView('absensi/bypass', $data);
    }

    public function submitBypass()
    {
        $user = $this->ion_auth->user()->row();
        
        $this->form_validation->set_rules('tanggal', 'Tanggal', 'required');
        $this->form_validation->set_rules('alasan', 'Alasan', 'required|min_length[10]');
        
        if ($this->form_validation->run() == FALSE) {
            $this->output_json(['status' => false, 'message' => validation_errors()]);
            return;
        }
        
        $config = $this->absensi->getConfig();
        $max_per_month = $this->absensi->getConfigValue('max_bypass_per_month', $config) ?: 5;
        $current_count = $this->absensi->countUserBypassThisMonth($user->id);
        
        if ($current_count >= $max_per_month) {
            $this->output_json(['status' => false, 'message' => "Anda sudah mencapai batas maksimal bypass ({$max_per_month}x/bulan)"]);
            return;
        }
        
        $data = [
            'id_user' => $user->id,
            'tanggal' => $this->input->post('tanggal'),
            'tipe_bypass' => $this->input->post('tipe_bypass') ?: 'both',
            'alasan' => $this->input->post('alasan'),
            'lokasi_alternatif' => $this->input->post('lokasi_alternatif'),
            'latitude' => $this->input->post('latitude'),
            'longitude' => $this->input->post('longitude'),
            'status' => $this->absensi->getConfigValue('bypass_auto_approve', $config) ? 'approved' : 'pending'
        ];
        
        $this->absensi->createBypassRequest($data);
        $this->output_json(['status' => true, 'message' => 'Pengajuan bypass berhasil dikirim']);
    }

    public function manageBypass()
    {
        $this->requireAdmin();
        $data = $this->getCommonData();
        $data['judul'] = 'Absensi';
        $data['subjudul'] = 'Kelola Pengajuan Bypass';
        
        $this->loadView('absensi/admin/manage_bypass', $data);
    }

    public function dataBypass()
    {
        $this->requireAdmin();
        $status = $this->input->get('status');
        $this->output_json($this->absensi->getDataTableBypass($status), false);
    }

    public function approveBypass()
    {
        $this->requireAdmin();
        $id = $this->input->post('id_bypass');
        $user = $this->ion_auth->user()->row();
        
        $this->absensi->updateBypassStatus($id, 'approved', $user->id, $this->input->post('catatan'));
        $this->output_json(['status' => true, 'message' => 'Bypass disetujui']);
    }

    public function rejectBypass()
    {
        $this->requireAdmin();
        $id = $this->input->post('id_bypass');
        $user = $this->ion_auth->user()->row();
        $catatan = $this->input->post('catatan');
        
        if (empty($catatan)) {
            $this->output_json(['status' => false, 'message' => 'Alasan penolakan wajib diisi']);
            return;
        }
        
        $this->absensi->updateBypassStatus($id, 'rejected', $user->id, $catatan);
        $this->output_json(['status' => true, 'message' => 'Bypass ditolak']);
    }

    public function manualEntry()
    {
        $this->requireAdmin();
        $data = $this->getCommonData();
        $data['judul'] = 'Absensi';
        $data['subjudul'] = 'Input Manual';
        $data['users'] = $this->absensi->getAllAttendanceUsers();
        $data['shifts'] = $this->shift->getAllShifts();
        $data['locations'] = $this->absensi->getActiveLocations();
        
        $this->loadView('absensi/admin/manual_entry', $data);
    }

    public function saveManualEntry()
    {
        $this->requireAdmin();
        $admin = $this->ion_auth->user()->row();
        
        $this->form_validation->set_rules('id_user', 'Pegawai', 'required');
        $this->form_validation->set_rules('tanggal', 'Tanggal', 'required');
        $this->form_validation->set_rules('alasan', 'Alasan', 'required|min_length[10]');
        
        if ($this->form_validation->run() == FALSE) {
            $this->output_json(['status' => false, 'message' => validation_errors()]);
            return;
        }
        
        $id_user = $this->input->post('id_user');
        $tanggal = $this->input->post('tanggal');
        
        $existing = $this->absensi->getTodayLog($id_user, $tanggal);
        
        $data = [
            'id_user' => $id_user,
            'id_shift' => $this->input->post('id_shift'),
            'id_lokasi' => $this->input->post('id_lokasi'),
            'tanggal' => $tanggal,
            'jam_masuk' => $this->input->post('jam_masuk') ? $tanggal . ' ' . $this->input->post('jam_masuk') . ':00' : null,
            'jam_pulang' => $this->input->post('jam_pulang') ? $tanggal . ' ' . $this->input->post('jam_pulang') . ':00' : null,
            'status_kehadiran' => $this->input->post('status_kehadiran'),
            'metode_masuk' => 'Manual',
            'metode_pulang' => $this->input->post('jam_pulang') ? 'Manual' : null,
            'keterangan' => $this->input->post('keterangan'),
            'is_manual_entry' => 1,
            'manual_entry_by' => $admin->id,
            'manual_entry_reason' => $this->input->post('alasan')
        ];
        
        if ($existing) {
            $this->absensi->updateLog($existing->id_log, $data);
            $this->absensi->logAudit($existing->id_log, $id_user, 'manual_edit', $admin->id, $data);
        } else {
            $id_log = $this->absensi->createLog($data);
            $this->absensi->logAudit($id_log, $id_user, 'manual_entry', $admin->id, $data);
        }
        
        $this->output_json(['status' => true, 'message' => 'Data absensi berhasil disimpan']);
    }

    public function monitoring()
    {
        $this->requireAdmin();
        $data = $this->getCommonData();
        $data['judul'] = 'Absensi';
        $data['subjudul'] = 'Monitoring Real-time';
        
        $today = date('Y-m-d');
        $data['today'] = $today;
        $data['stats'] = $this->absensi->getDashboardStats($today);
        
        $this->loadView('absensi/admin/monitoring', $data);
    }

    public function dataMonitoring()
    {
        $this->requireAdmin();
        $tanggal = $this->input->get('tanggal') ?: date('Y-m-d');
        $filter = $this->input->get('filter');
        $this->output_json($this->absensi->getDataTableMonitoring($tanggal, $filter), false);
    }

    public function rekap()
    {
        $this->requireAdmin();
        $data = $this->getCommonData();
        $data['judul'] = 'Absensi';
        $data['subjudul'] = 'Rekap Absensi';
        
        $this->loadView('absensi/admin/rekap', $data);
    }

    public function dataRekapHarian()
    {
        $this->requireAdmin();
        $tanggal = $this->input->get('tanggal') ?: date('Y-m-d');
        $data = $this->absensi->getRekapHarian($tanggal);
        $this->output_json(['status' => true, 'data' => $data, 'tanggal' => $tanggal]);
    }

    public function dataRekapBulanan()
    {
        $this->requireAdmin();
        $bulan = $this->input->get('bulan') ?: date('m');
        $tahun = $this->input->get('tahun') ?: date('Y');
        $data = $this->absensi->getRekapBulananAll($bulan, $tahun);
        $this->output_json(['status' => true, 'data' => $data]);
    }

    public function laporan()
    {
        $this->requireAdmin();
        $data = $this->getCommonData();
        $data['judul'] = 'Absensi';
        $data['subjudul'] = 'Laporan Absensi';
        
        $this->loadView('absensi/admin/laporan', $data);
    }

    public function exportExcel()
    {
        $this->requireAdmin();
        $bulan = $this->input->get('bulan') ?: date('m');
        $tahun = $this->input->get('tahun') ?: date('Y');
        
        $data = $this->absensi->getRekapBulananAll($bulan, $tahun);
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="rekap_absensi_' . $bulan . '_' . $tahun . '.xls"');
        
        $this->load->view('absensi/admin/export_excel', ['data' => $data, 'bulan' => $bulan, 'tahun' => $tahun]);
    }

    public function statistik()
    {
        $this->requireAdmin();
        $data = $this->getCommonData();
        $data['judul'] = 'Absensi';
        $data['subjudul'] = 'Statistik Kehadiran';
        
        $bulan = $this->input->get('bulan') ?: date('m');
        $tahun = $this->input->get('tahun') ?: date('Y');
        
        $data['bulan'] = $bulan;
        $data['tahun'] = $tahun;
        $data['chart_status'] = $this->absensi->getStatistikByStatus($bulan, $tahun);
        $data['chart_daily'] = $this->absensi->getStatistikDaily($bulan, $tahun);
        $data['top_late'] = $this->absensi->getTopLate($bulan, $tahun, 10);
        $data['top_absent'] = $this->absensi->getTopAbsent($bulan, $tahun, 10);
        
        $this->loadView('absensi/admin/statistik', $data);
    }

    public function getStatistikData()
    {
        $this->requireAdmin();
        $bulan = $this->input->get('bulan') ?: date('m');
        $tahun = $this->input->get('tahun') ?: date('Y');
        
        $data = [
            'chart_status' => $this->absensi->getStatistikByStatus($bulan, $tahun),
            'chart_daily' => $this->absensi->getStatistikDaily($bulan, $tahun),
            'top_late' => $this->absensi->getTopLate($bulan, $tahun, 10),
            'top_absent' => $this->absensi->getTopAbsent($bulan, $tahun, 10)
        ];
        
        $this->output_json(['status' => true, 'data' => $data]);
    }
}
