<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Presensi_model - Core attendance model with linear validation flow
 * Single source of truth, no overlapping settings
 */
class Presensi_model extends CI_Model
{
    private $config_global = null;
    private $config_group = null;
    private $config_user = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    // =========================================================================
    // CONFIGURATION RESOLUTION (Linear: User → Group → Global)
    // =========================================================================

    public function getResolvedConfig($id_user)
    {
        $this->load->model('ion_auth_model');

        $groups = $this->ion_auth_model->get_users_groups($id_user)->result();
        $primary_group = !empty($groups) ? $groups[0] : null;

        if (!$this->config_global) {
            $this->config_global = $this->getGlobalConfig();
        }

        if ($primary_group && !$this->config_group) {
            $this->config_group = $this->getGroupConfig($primary_group->id);
        }

        $user_config = $this->getUserConfig($id_user);

        $validation_mode = $user_config->validation_mode ?? $this->config_group->validation_mode ?? $this->config_global['validation_mode'] ?? 'gps';

        return (object) [
            'validation_mode' => $validation_mode,
            'require_photo' => $user_config->require_photo ?? $this->config_group->require_photo ?? $this->config_global['require_photo'] ?? 1,
            'require_checkout' => $user_config->require_checkout ?? $this->config_group->require_checkout ?? $this->config_global['require_checkout'] ?? 1,
            'allow_bypass' => $user_config->allow_bypass ?? $this->config_group->allow_bypass ?? $this->config_global['allow_bypass'] ?? 1,
            'id_shift_default' => $this->config_group->id_shift_default ?? null,
            'id_lokasi_default' => $this->config_group->id_lokasi_default ?? null,
            'holiday_mode' => $this->config_group->holiday_mode ?? 'all',
            'follow_academic_calendar' => $this->config_group->follow_academic_calendar ?? 0,
            'group_id' => $primary_group ? $primary_group->id : null,
            'group_name' => $primary_group ? $primary_group->name : 'unknown'
        ];
    }

    public function getGlobalConfig()
    {
        $rows = $this->db->get('presensi_config_global')->result();
        $config = [];

        foreach ($rows as $row) {
            $config[$row->config_key] = $this->parseConfigValue($row->config_value, $row->config_type);
        }

        return $config;
    }

    public function getGroupConfig($id_group)
    {
        return $this->db->where('id_group', $id_group)
            ->get('presensi_config_group')
            ->row();
    }

    public function getUserConfig($id_user)
    {
        return $this->db->where('id_user', $id_user)
            ->get('presensi_config_user')
            ->row();
    }

    private function parseConfigValue($value, $type)
    {
        switch ($type) {
            case 'int':
                return (int) $value;
            case 'boolean':
                return (bool) $value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    // =========================================================================
    // SHIFT RESOLUTION (Linear Priority)
    // =========================================================================

    public function getUserShiftForDate($id_user, $date)
    {
        $day_of_week = (int) date('N', strtotime($date));
        $this->load->model('ion_auth_model');

        $groups = $this->ion_auth_model->get_users_groups($id_user)->result();
        $primary_group = !empty($groups) ? $groups[0] : null;

        if (!$primary_group) {
            return null;
        }

        $override = $this->db->where('id_user', $id_user)
            ->where('tanggal', $date)
            ->get('presensi_jadwal_override')
            ->row();

        if ($override) {
            if ($override->id_shift) {
                return $this->db->where('id_shift', $override->id_shift)
                    ->get('presensi_shift')
                    ->row();
            }

            return null;
        }

        if ($primary_group) {
            $group_override = $this->db->where('id_group', $primary_group->id)
                ->where('id_user IS NULL', null, false)
                ->where('tanggal', $date)
                ->get('presensi_jadwal_override')
                ->row();

            if ($group_override) {
                if ($group_override->id_shift) {
                    return $this->db->where('id_shift', $group_override->id_shift)
                        ->get('presensi_shift')
                        ->row();
                }

                return null;
            }
        }

        if ($primary_group) {
            $schedule = $this->db->select('pjk.*, ps.*')
                ->from('presensi_jadwal_kerja pjk')
                ->join('presensi_shift ps', 'pjk.id_shift = ps.id_shift')
                ->where('pjk.id_group', $primary_group->id)
                ->where('pjk.day_of_week', $day_of_week)
                ->where('pjk.is_active', 1)
                ->get()
                ->row();

            if ($schedule) {
                return $schedule;
            }
        }

        $group_config = $this->getGroupConfig($primary_group->id);

        if ($group_config && $group_config->id_shift_default) {
            return $this->db->where('id_shift', $group_config->id_shift_default)
                ->get('presensi_shift')
                ->row();
        }

        return null;
    }

    // =========================================================================
    // WORKING DAY CHECK
    // =========================================================================

    public function isWorkingDay($id_user, $date)
    {
        $shift = $this->getUserShiftForDate($id_user, $date);

        if (!$shift) {
            return false;
        }

        return true;
    }

    public function isHoliday($date, $holiday_mode = 'all')
    {
        $day_of_week = (int) date('N', strtotime($date));

        if ($day_of_week === 7 && $holiday_mode === 'all') {
            return true;
        }

        $holiday = $this->db->where('tanggal', $date)
            ->where('is_active', 1);

        if ($holiday_mode === 'national_only') {
            $this->db->where('tipe_libur', 'NASIONAL');
        } elseif ($holiday_mode === 'all') {
            $this->db->where_in('tipe_libur', ['NASIONAL', 'AKADEMIK', 'KANTOR']);
        }

        return $this->db->count_all_results('presensi_hari_libur') > 0;
    }

    // =========================================================================
    // VALIDATION LOGIC (Linear Flow)
    // =========================================================================

    public function validateCheckIn($id_user, $data)
    {
        $config = $this->getResolvedConfig($id_user);

        if (!$this->isWorkingDay($id_user, date('Y-m-d'))) {
            return ['success' => false, 'message' => 'Hari ini bukan hari kerja'];
        }

        if ($this->isHoliday(date('Y-m-d'), $config->holiday_mode)) {
            return ['success' => false, 'message' => 'Hari ini adalah hari libur'];
        }

        $shift = $this->getUserShiftForDate($id_user, date('Y-m-d'));

        if (!$shift) {
            return ['success' => false, 'message' => 'Tidak ada shift untuk hari ini'];
        }

        $existing_log = $this->db->where('id_user', $id_user)
            ->where('tanggal', date('Y-m-d'))
            ->get('presensi_logs')
            ->row();

        if ($existing_log && $existing_log->jam_masuk) {
            return ['success' => false, 'message' => 'Anda sudah check-in hari ini'];
        }

        if ($existing_log && in_array($existing_log->status_kehadiran, ['Izin', 'Sakit', 'Cuti', 'Dinas Luar'])) {
            return ['success' => false, 'message' => 'Status hari ini: ' . $existing_log->status_kehadiran];
        }

        $validation = $this->validateMethod($config->validation_mode, $data);

        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message'], 'show_bypass' => $config->allow_bypass];
        }

        return [
            'success' => true,
            'shift' => $shift,
            'config' => $config,
            'validation' => $validation
        ];
    }

    public function validateMethod($validation_mode, $data)
    {
        switch ($validation_mode) {
            case 'gps':
                return $this->validateGPS($data['lat'] ?? null, $data['lng'] ?? null);

            case 'qr':
                return $this->validateQR($data['qr_token'] ?? null);

            case 'gps_or_qr':
                if (!empty($data['lat']) && !empty($data['lng'])) {
                    $gps = $this->validateGPS($data['lat'], $data['lng']);
                    if ($gps['valid']) {
                        return $gps;
                    }
                }

                if (!empty($data['qr_token'])) {
                    return $this->validateQR($data['qr_token']);
                }

                return ['valid' => false, 'message' => 'GPS atau QR Code diperlukan'];

            case 'manual':
                return ['valid' => true, 'message' => 'OK', 'id_lokasi' => null];

            case 'any':
                if (!empty($data['lat']) && !empty($data['lng'])) {
                    return $this->validateGPS($data['lat'], $data['lng']);
                }

                if (!empty($data['qr_token'])) {
                    return $this->validateQR($data['qr_token']);
                }

                return ['valid' => true, 'message' => 'OK', 'id_lokasi' => null];

            default:
                return ['valid' => false, 'message' => 'Mode validasi tidak dikenal'];
        }
    }

    public function validateGPS($lat, $lng)
    {
        if (empty($lat) || empty($lng)) {
            return ['valid' => false, 'message' => 'Koordinat GPS diperlukan'];
        }

        $lokasi = $this->db->where('is_default', 1)
            ->where('is_active', 1)
            ->get('presensi_lokasi')
            ->row();

        if (!$lokasi) {
            return ['valid' => true, 'message' => 'OK', 'id_lokasi' => null];
        }

        $distance = $this->calculateDistance($lat, $lng, $lokasi->latitude, $lokasi->longitude);

        if ($distance <= $lokasi->radius_meter) {
            return ['valid' => true, 'message' => 'OK', 'id_lokasi' => $lokasi->id_lokasi];
        }

        return ['valid' => false, 'message' => 'Lokasi di luar radius yang diizinkan'];
    }

    public function validateQR($qr_token)
    {
        if (empty($qr_token)) {
            return ['valid' => false, 'message' => 'QR Code diperlukan'];
        }

        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        $token = $this->db->where('token_code', $qr_token)
            ->where('tanggal', $today)
            ->where('valid_from <=', $now)
            ->where('valid_until >=', $now)
            ->where('is_active', 1)
            ->get('presensi_qr_token')
            ->row();

        if (!$token) {
            return ['valid' => false, 'message' => 'QR Code tidak valid atau sudah kadaluarsa'];
        }

        if ($token->max_usage && $token->used_count >= $token->max_usage) {
            return ['valid' => false, 'message' => 'QR Code sudah mencapai batas penggunaan'];
        }

        return [
            'valid' => true,
            'message' => 'OK',
            'id_lokasi' => $token->id_lokasi,
            'token_data' => $token
        ];
    }

    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
               cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
               sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    // =========================================================================
    // STATUS CALCULATION (Tolerance from Shift ONLY)
    // =========================================================================

    public function calculateLateStatus($current_time, $shift)
    {
        $toleransi = $shift->toleransi_masuk_menit ?? 0;

        $masuk = strtotime($current_time);
        $shift_time = strtotime($shift->jam_masuk);
        $batas = $shift_time + ($toleransi * 60);

        if ($shift->is_lintas_hari && $masuk < 43200) {
            $masuk += 86400;
        }

        if ($masuk > $batas) {
            return [
                'status' => 'Terlambat',
                'menit' => round(($masuk - $shift_time) / 60)
            ];
        }

        return ['status' => 'Hadir', 'menit' => 0];
    }

    // =========================================================================
    // CHECK-IN PROCESS
    // =========================================================================

    public function doCheckIn($id_user, $data)
    {
        $validation = $this->validateCheckIn($id_user, $data);

        if (!$validation['success']) {
            return $validation;
        }

        $config = $validation['config'];
        $shift = $validation['shift'];
        $validation_result = $validation['validation'];

        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');

        $status = $this->calculateLateStatus($now, $shift);

        $insert_data = [
            'id_user' => $id_user,
            'tanggal' => $today,
            'id_shift' => $shift->id_shift,
            'id_lokasi' => $validation_result['id_lokasi'] ?? $config->id_lokasi_default,
            'jam_masuk' => $now,
            'metode_masuk' => $validation_result['method'] ?? ($data['qr_token'] ? 'qr' : 'gps'),
            'lat_masuk' => $data['lat'] ?? null,
            'long_masuk' => $data['lng'] ?? null,
            'foto_masuk' => $data['photo'] ?? null,
            'qr_token_masuk' => $data['qr_token'] ?? null,
            'status_kehadiran' => $status['status'],
            'terlambat_menit' => $status['menit'],
            'is_overnight' => $shift->is_lintas_hari
        ];

        if ($this->db->insert('presensi_logs', $insert_data)) {
            if (!empty($data['qr_token'])) {
                $this->db->where('token_code', $data['qr_token'])
                    ->set('used_count', 'used_count + 1', FALSE)
                    ->update('presensi_qr_token');
            }

            $this->logAudit($id_user, 'checkin', $insert_data);

            return ['success' => true, 'status' => $status['status'], 'terlambat_menit' => $status['menit']];
        }

        return ['success' => false, 'message' => 'Gagal menyimpan data presensi'];
    }

    // =========================================================================
    // CHECK-OUT PROCESS
    // =========================================================================

    public function doCheckOut($id_user, $data)
    {
        $today = date('Y-m-d');

        $log = $this->db->where('id_user', $id_user)
            ->where('tanggal', $today)
            ->get('presensi_logs')
            ->row();

        if (!$log) {
            return ['success' => false, 'message' => 'Anda belum check-in hari ini'];
        }

        if ($log->jam_pulang) {
            return ['success' => false, 'message' => 'Anda sudah check-out hari ini'];
        }

        $config = $this->getResolvedConfig($id_user);
        $shift = $this->getUserShiftForDate($id_user, $today);

        $now = date('Y-m-d H:i:s');

        $pulang_awal_menit = 0;

        if ($shift) {
            $toleransi = $shift->toleransi_pulang_menit ?? 0;
            $pulang_time = strtotime($now);
            $shift_time = strtotime($shift->jam_pulang);

            if ($shift->is_lintas_hari) {
                $shift_time += 86400;
            }

            $batas = $shift_time - ($toleransi * 60);

            if ($pulang_time < $batas) {
                $pulang_awal_menit = round(($shift_time - $pulang_time) / 60);
            }
        }

        $update_data = [
            'jam_pulang' => $now,
            'metode_pulang' => $data['qr_token'] ? 'qr' : 'gps',
            'lat_pulang' => $data['lat'] ?? null,
            'long_pulang' => $data['lng'] ?? null,
            'pulang_awal_menit' => $pulang_awal_menit
        ];

        $status_kehadiran = $log->status_kehadiran;

        if ($pulang_awal_menit > 0 && $log->terlambat_menit > 0) {
            $status_kehadiran = 'Terlambat + Pulang Awal';
        } elseif ($pulang_awal_menit > 0) {
            $status_kehadiran = 'Pulang Awal';
        }

        $update_data['status_kehadiran'] = $status_kehadiran;

        if ($this->db->where('id_log', $log->id_log)->update('presensi_logs', $update_data)) {
            $this->logAudit($id_user, 'checkout', $update_data, $log);

            return ['success' => true, 'status' => $status_kehadiran];
        }

        return ['success' => false, 'message' => 'Gagal menyimpan data check-out'];
    }

    // =========================================================================
    // GET LOGS
    // =========================================================================

    public function getUserLog($id_user, $date)
    {
        return $this->db->where('id_user', $id_user)
            ->where('tanggal', $date)
            ->get('presensi_logs')
            ->row();
    }

    /**
     * Alias for getUserLog - used by Pengajuan controller
     */
    public function getTodayLog($id_user, $date = null)
    {
        if ($date === null) {
            $date = date('Y-m-d');
        }
        return $this->getUserLog($id_user, $date);
    }

    public function getUserLogs($id_user, $start_date, $end_date)
    {
        return $this->db->select('pl.*, ps.nama_shift')
            ->from('presensi_logs pl')
            ->join('presensi_shift ps', 'pl.id_shift = ps.id_shift')
            ->where('pl.id_user', $id_user)
            ->where('pl.tanggal >=', $start_date)
            ->where('pl.tanggal <=', $end_date)
            ->order_by('pl.tanggal', 'DESC')
            ->get()
            ->result();
    }

    // =========================================================================
    // BYPASS REQUEST
    // =========================================================================

    public function createBypassRequest($id_user, $data)
    {
        $config = $this->getResolvedConfig($id_user);

        if (!$config->allow_bypass) {
            return ['success' => false, 'message' => 'Bypass tidak diizinkan'];
        }

        $bypass_count = $this->db->where('id_user', $id_user)
            ->where('DATE_FORMAT(created_at, "%Y-%m")', date('Y-m'))
            ->where('status', 'pending')
            ->count_all_results('presensi_bypass');

        if ($bypass_count >= $config->max_bypass_per_month ?? 3) {
            return ['success' => false, 'message' => 'Batas bypass bulanan telah tercapai'];
        }

        $insert_data = [
            'id_user' => $id_user,
            'tanggal' => date('Y-m-d'),
            'tipe_bypass' => $data['tipe'] ?? 'checkin',
            'alasan' => $data['alasan'],
            'lokasi_alternatif' => $data['lokasi'] ?? null,
            'latitude' => $data['lat'] ?? null,
            'longitude' => $data['lng'] ?? null,
            'foto_bukti' => $data['photo'] ?? null
        ];

        if ($this->db->insert('presensi_bypass', $insert_data)) {
            $this->logAudit($id_user, 'bypass_request', $insert_data);
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Gagal menyimpan bypass request'];
    }

    // =========================================================================
    // AUDIT LOG
    // =========================================================================

    public function logAudit($id_user_target, $action, $data_after, $data_before = null)
    {
        $action_by = $this->session->userdata('user_id');

        if (!$action_by) {
            $action_by = $id_user_target;
        }

        $insert_data = [
            'id_user_target' => $id_user_target,
            'action' => $action,
            'action_by' => $action_by,
            'data_before' => $data_before ? json_encode($data_before) : null,
            'data_after' => json_encode($data_after),
            'ip_address' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent()
        ];

        $this->db->insert('presensi_audit_log', $insert_data);
    }

    // =========================================================================
    // DASHBOARD STATISTICS
    // =========================================================================

    /**
     * Get users with open attendance (checked in but not checked out)
     * Used by Pengajuan controller for "Izin Keluar" feature
     *
     * @param string $date Date to check
     * @param array $groups Group names to filter (e.g. ['siswa', 'guru'])
     * @return array List of users with open attendance
     */
    public function getOpenAttendanceUsers($date, $groups = [])
    {
        $this->load->model('ion_auth_model');

        $this->db->select('pl.*, u.id as user_id, u.username, u.email, u.first_name, u.last_name');
        $this->db->from('presensi_logs pl');
        $this->db->join('users u', 'pl.id_user = u.id');
        $this->db->where('pl.tanggal', $date);
        $this->db->where('pl.jam_masuk IS NOT NULL', null, false);
        $this->db->where('pl.jam_pulang IS NULL', null, false);
        $this->db->where_not_in('pl.status_kehadiran', ['Izin', 'Sakit', 'Cuti', 'Dinas Luar', 'Alpha']);

        if (!empty($groups)) {
            $this->db->join('users_groups ug', 'u.id = ug.user_id');
            $this->db->join('groups g', 'ug.group_id = g.id');
            $this->db->where_in('g.name', $groups);
        }

        $this->db->order_by('u.first_name', 'ASC');

        return $this->db->get()->result();
    }

    public function getTodayStats()
    {
        $today = date('Y-m-d');

        $stats = [
            'hadir' => $this->db->where('tanggal', $today)->where('status_kehadiran', 'Hadir')->count_all_results('presensi_logs'),
            'terlambat' => $this->db->where('tanggal', $today)->where('status_kehadiran', 'Terlambat')->count_all_results('presensi_logs'),
            'alpha' => $this->db->where('tanggal', $today)->where('status_kehadiran', 'Alpha')->count_all_results('presensi_logs'),
            'izin' => $this->db->where('tanggal', $today)->where_in('status_kehadiran', ['Izin', 'Sakit', 'Cuti'])->count_all_results('presensi_logs'),
        ];

        return $stats;
    }
}
