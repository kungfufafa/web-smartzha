<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Presensi_model - Core attendance model with linear validation flow
 * Single source of truth, no overlapping settings
 */
class Presensi_model extends CI_Model
{
    private $config_global = null;
    private $config_group_cache = [];  // Keyed by group_id to prevent cross-user contamination
    private $tendik_type_cache = [];   // Keyed by user_id

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    // =========================================================================
    // CONFIGURATION RESOLUTION (Group → Global)
    // =========================================================================

    /**
     * Get primary presensi group for user (deterministic)
     * Priority: tendik > guru > siswa > others (alphabetical)
     *
     * @param int $id_user User ID
     * @return object|null Primary group
     */
    public function getPrimaryPresensiGroup($id_user)
    {
        $this->load->model('ion_auth_model');

        $groups = $this->ion_auth_model->get_users_groups($id_user)->result();

        if (empty($groups)) {
            return null;
        }

        // Define priority order for presensi groups
        $priority = ['tendik' => 1, 'guru' => 2, 'siswa' => 3];

        usort($groups, function($a, $b) use ($priority) {
            $pa = $priority[$a->name] ?? 999;
            $pb = $priority[$b->name] ?? 999;

            if ($pa !== $pb) {
                return $pa - $pb;
            }

            // Same priority, sort by name for consistency
            return strcmp($a->name, $b->name);
        });

        return $groups[0];
    }

    public function getResolvedConfig($id_user)
    {
        $primary_group = $this->getPrimaryPresensiGroup($id_user);

        // Cache global config (same for all users)
        if (!$this->config_global) {
            $this->config_global = $this->getGlobalConfig();
        }

        // Cache group config keyed by group_id to prevent cross-user contamination
        $group_config = null;
        if ($primary_group) {
            $group_id = $primary_group->id;
            if (!isset($this->config_group_cache[$group_id])) {
                $this->config_group_cache[$group_id] = $this->getGroupConfig($group_id);
            }
            $group_config = $this->config_group_cache[$group_id];
        }

        // Resolve with proper NULL handling: NULL means "use system default"
        $validation_mode = $group_config ? $group_config->validation_mode : null;
        if ($validation_mode === null || $validation_mode === '') {
            $validation_mode = 'gps_or_qr';
        }

        return (object) [
            'validation_mode' => $validation_mode,
            'require_photo' => $this->resolveTriState(null, $group_config ? $group_config->require_photo : null, 1),
            'require_checkout' => $this->resolveTriState(null, $group_config ? $group_config->require_checkout : null, 1),
            'allow_bypass' => $this->resolveTriState(null, $group_config ? $group_config->allow_bypass : null, 1),
            'max_bypass_per_month' => (int) ($this->config_global['max_bypass_per_month'] ?? 3),
            'bypass_auto_approve' => (int) ($this->config_global['bypass_auto_approve'] ?? 0),
            'id_shift_default' => $group_config ? $group_config->id_shift_default : null,
            'id_lokasi_default' => $group_config ? $group_config->id_lokasi_default : null,
            'holiday_mode' => $group_config && $group_config->holiday_mode ? $group_config->holiday_mode : 'all',
            'follow_academic_calendar' => $group_config ? (int) $group_config->follow_academic_calendar : 0,
            'group_id' => $primary_group ? $primary_group->id : null,
            'group_name' => $primary_group ? $primary_group->name : 'unknown'
        ];
    }

    /**
     * Resolve tri-state config value
     * NULL = inherit from next level, 0 = disabled, 1 = enabled
     *
     * @param mixed $user_value User-level value
     * @param mixed $group_value Group-level value
     * @param mixed $global_value Global-level value (fallback)
     * @return int Resolved value (0 or 1)
     */
    private function resolveTriState($user_value, $group_value, $global_value)
    {
        // User level: if explicitly set (not NULL), use it
        if ($user_value !== null) {
            return (int) $user_value;
        }
        // Group level: if explicitly set (not NULL), use it
        if ($group_value !== null) {
            return (int) $group_value;
        }
        // Fall back to global
        return (int) $global_value;
    }

    public function getGlobalConfig()
    {
        if ($this->config_global !== null) {
            return $this->config_global;
        }

        $rows = $this->db->get('presensi_config_global')->result();
        $config = [];

        foreach ($rows as $row) {
            $config[$row->config_key] = $this->parseConfigValue($row->config_value, $row->config_type);
        }

        $this->config_global = $config;
        return $config;
    }

    public function getGroupConfig($id_group)
    {
        if (!$this->db->table_exists('presensi_config_group')) {
            return null;
        }

        return $this->db->where('id_group', $id_group)
            ->get('presensi_config_group')
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
        $primary_group = $this->getPrimaryPresensiGroup($id_user);

        if (!$primary_group) {
            return null;
        }

        if ($this->db->table_exists('presensi_jadwal_override')) {
            // 1. Check user-specific override for this date
            $override = $this->db->where('id_user', $id_user)
                ->where('tanggal', $date)
                ->get('presensi_jadwal_override')
                ->row();

            if ($override) {
                if ($override->id_shift) {
                    return $this->db->where('id_shift', $override->id_shift)
                        ->where('is_active', 1)
                        ->get('presensi_shift')
                        ->row();
                }

                return null;
            }

            // 2. Check group override for this date
            if ($primary_group) {
                $group_override = $this->db->where('id_group', $primary_group->id)
                    ->where('id_user IS NULL', null, false)
                    ->where('tanggal', $date)
                    ->get('presensi_jadwal_override')
                    ->row();

                if ($group_override) {
                    if ($group_override->id_shift) {
                        return $this->db->where('id_shift', $group_override->id_shift)
                            ->where('is_active', 1)
                            ->get('presensi_shift')
                            ->row();
                    }

                    return null;
                }
            }
        }

        // 3. Weekly schedule override per-user (edge cases: satpam pagi vs malam, guru panggilan, siswa sesi, dll)
        if ($this->db->table_exists('presensi_jadwal_user')) {
            $user_schedule = $this->db->select('id_shift')
                ->where('id_user', $id_user)
                ->where('day_of_week', $day_of_week)
                ->where('is_active', 1)
                ->get('presensi_jadwal_user')
                ->row();

            if ($user_schedule) {
                if ($user_schedule->id_shift !== null) {
                    return $this->db->where('id_shift', $user_schedule->id_shift)
                        ->where('is_active', 1)
                        ->get('presensi_shift')
                        ->row();
                }

                return null;
            }
        }

        // 4. Tendik-specific schedule by tipe_tendik (satpam/penjaga/dll)
        if ($primary_group && $primary_group->name === 'tendik' && $this->db->table_exists('presensi_jadwal_tendik')) {
            $tipe_tendik = $this->getTendikTypeForUser($id_user);

            if ($tipe_tendik) {
                $tendik_schedule = $this->db->select('pjt.*, ps.*')
                    ->from('presensi_jadwal_tendik pjt')
                    ->join('presensi_shift ps', 'pjt.id_shift = ps.id_shift')
                    ->where('pjt.tipe_tendik', $tipe_tendik)
                    ->where('pjt.day_of_week', $day_of_week)
                    ->where('pjt.is_active', 1)
                    ->get()
                    ->row();

                if ($tendik_schedule) {
                    return $tendik_schedule;
                }
            }
        }

        if ($primary_group && $this->db->table_exists('presensi_jadwal_kerja')) {
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

        // Fallback: Use group's default shift if configured
        $config = $this->getResolvedConfig($id_user);
        if ($config && $config->id_shift_default) {
            $default_shift = $this->db->where('id_shift', $config->id_shift_default)
                ->where('is_active', 1)
                ->get('presensi_shift')
                ->row();
            if ($default_shift) {
                return $default_shift;
            }
        }

        return null;
    }

    private function getTendikTypeForUser($id_user)
    {
        if (!$id_user) {
            return null;
        }

        if (array_key_exists($id_user, $this->tendik_type_cache)) {
            return $this->tendik_type_cache[$id_user];
        }

        if (!$this->db->table_exists('master_tendik')) {
            $this->tendik_type_cache[$id_user] = null;
            return null;
        }

        $row = $this->db->select('tipe_tendik')
            ->where('id_user', $id_user)
            ->where('is_active', 1)
            ->get('master_tendik')
            ->row();

        $tipe = $row && $row->tipe_tendik ? strtoupper((string) $row->tipe_tendik) : null;
        $this->tendik_type_cache[$id_user] = $tipe ?: null;

        return $this->tendik_type_cache[$id_user];
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
        // Early return: 'none' means ignore all holidays
        if ($holiday_mode === 'none') {
            return false;
        }

        if (!$this->db->table_exists('presensi_hari_libur')) {
            return false;
        }

        $allowed_types = ['NASIONAL', 'AKADEMIK', 'KANTOR'];
        if ($holiday_mode === 'national_only') {
            $allowed_types = ['NASIONAL'];
        } elseif ($holiday_mode === 'all') {
            $allowed_types = ['NASIONAL', 'AKADEMIK', 'KANTOR'];
        }

        // Exact date holiday
        $this->db->from('presensi_hari_libur');
        $this->db->where('tanggal', $date)
            ->where('is_active', 1)
            ->where_in('tipe_libur', $allowed_types);

        if ($this->db->count_all_results() > 0) {
            return true;
        }

        // Recurring holiday (month-day match)
        $month_day = date('m-d', strtotime($date));

        $this->db->from('presensi_hari_libur');
        $this->db->where('is_recurring', 1)
            ->where('is_active', 1)
            ->where_in('tipe_libur', $allowed_types);
        $this->db->where("DATE_FORMAT(tanggal, '%m-%d') = '" . $this->db->escape_str($month_day) . "'", null, false);

        return $this->db->count_all_results() > 0;
    }

    // =========================================================================
    // VALIDATION LOGIC (Linear Flow)
    // =========================================================================

    public function validateCheckIn($id_user, $data)
    {
        $config = $this->getResolvedConfig($id_user);
        $today = date('Y-m-d');
        $data_effective = is_array($data) ? $data : [];
        $approved_bypass = null;

        if (!$this->isWorkingDay($id_user, $today)) {
            return ['success' => false, 'message' => 'Hari ini bukan hari kerja'];
        }

        if ($this->isHoliday($today, $config->holiday_mode)) {
            return ['success' => false, 'message' => 'Hari ini adalah hari libur'];
        }

        $shift = $this->getUserShiftForDate($id_user, $today);

        if (!$shift) {
            return ['success' => false, 'message' => 'Tidak ada shift untuk hari ini'];
        }

        if ($config->require_checkout) {
            $open_log = $this->getOpenAttendanceLog($id_user);

            if ($open_log && $open_log->tanggal !== $today) {
                return [
                    'success' => false,
                    'message' => 'Anda masih memiliki presensi yang belum absen pulang (tanggal ' . date('d F Y', strtotime($open_log->tanggal)) . ')'
                ];
            }
        }

        $existing_log = $this->db->where('id_user', $id_user)
            ->where('tanggal', $today)
            ->get('presensi_logs')
            ->row();

        if ($existing_log && $existing_log->jam_masuk) {
            return ['success' => false, 'message' => 'Anda sudah absen masuk hari ini'];
        }

        if ($existing_log && in_array($existing_log->status_kehadiran, ['Izin', 'Sakit', 'Cuti', 'Dinas Luar'])) {
            return ['success' => false, 'message' => 'Status hari ini: ' . $existing_log->status_kehadiran];
        }

        if (!empty($config->allow_bypass)) {
            $approved_bypass = $this->getApprovedBypassForAction($id_user, $today, 'checkin');
        }

        // Enforce require_photo
        if ($config->require_photo && empty($data_effective['photo'])) {
            if ($approved_bypass && !empty($approved_bypass->foto_bukti)) {
                $data_effective['photo'] = $approved_bypass->foto_bukti;
            } else {
            return ['success' => false, 'message' => 'Foto selfie diperlukan untuk absen masuk'];
            }
        }

        $validation = $this->validateMethod($config->validation_mode, $data_effective, $config->id_lokasi_default, 'checkin');

        if (!$validation['valid']) {
            if ($approved_bypass) {
                return [
                    'success' => true,
                    'shift' => $shift,
                    'config' => $config,
                    'validation' => [
                        'valid' => true,
                        'message' => 'OK (bypass approved)',
                        'id_lokasi' => $config->id_lokasi_default,
                        'method' => 'bypass',
                        'id_bypass' => (int) $approved_bypass->id_bypass
                    ],
                    'bypass' => $approved_bypass,
                    'data_effective' => $data_effective
                ];
            }
            return ['success' => false, 'message' => $validation['message'], 'show_bypass' => $config->allow_bypass];
        }

        return [
            'success' => true,
            'shift' => $shift,
            'config' => $config,
            'validation' => $validation,
            'bypass' => $approved_bypass,
            'data_effective' => $data_effective
        ];
    }

    public function validateMethod($validation_mode, $data, $id_lokasi_default = null, $action = 'checkin')
    {
        switch ($validation_mode) {
            case 'gps':
                return $this->validateGPS($data['lat'] ?? null, $data['lng'] ?? null, $id_lokasi_default);

            case 'qr':
                return $this->validateQR($data['qr_token'] ?? null, $action);

            case 'gps_or_qr':
                if (!empty($data['lat']) && !empty($data['lng'])) {
                    $gps = $this->validateGPS($data['lat'], $data['lng'], $id_lokasi_default);
                    if ($gps['valid']) {
                        return $gps;
                    }
                }

                if (!empty($data['qr_token'])) {
                    return $this->validateQR($data['qr_token'], $action);
                }

                return ['valid' => false, 'message' => 'GPS atau QR Code diperlukan'];

            case 'manual':
                return ['valid' => true, 'message' => 'OK', 'id_lokasi' => null, 'method' => 'manual'];

            case 'any':
                $errors = [];
                $hasProof = false;

                if (!empty($data['lat']) && !empty($data['lng'])) {
                    $gps = $this->validateGPS($data['lat'], $data['lng'], $id_lokasi_default);
                    if ($gps['valid']) {
                        return $gps;
                    }
                    $errors[] = $gps['message'] ?? 'Validasi GPS gagal';
                    $hasProof = true;
                }

                if (!empty($data['qr_token'])) {
                    $qr = $this->validateQR($data['qr_token'], $action);
                    if ($qr['valid']) {
                        return $qr;
                    }
                    $errors[] = $qr['message'] ?? 'Validasi QR Code gagal';
                    $hasProof = true;
                }

                // Require at least one proof (GPS or QR)
                if (!$hasProof) {
                    return ['valid' => false, 'message' => 'GPS atau QR Code diperlukan'];
                }

                if (empty($errors)) {
                    return ['valid' => true, 'message' => 'OK', 'id_lokasi' => null, 'method' => 'manual'];
                }

                return ['valid' => false, 'message' => implode(' / ', $errors), 'method' => 'manual'];

            default:
                return ['valid' => false, 'message' => 'Mode validasi tidak dikenal'];
        }
    }

    public function validateGPS($lat, $lng, $id_lokasi_default = null)
    {
        if (!isset($lat) || !is_numeric($lat) || !isset($lng) || !is_numeric($lng)) {
            return ['valid' => false, 'message' => 'Koordinat GPS diperlukan', 'method' => 'gps'];
        }

        $lokasi = null;

        // Priority 1: Use group's default location if specified
        if ($id_lokasi_default) {
            $lokasi = $this->db->where('id_lokasi', $id_lokasi_default)
                ->where('is_active', 1)
                ->get('presensi_lokasi')
                ->row();
        }

        // Priority 2: Fall back to system default location
        if (!$lokasi) {
            $lokasi = $this->db->where('is_default', 1)
                ->where('is_active', 1)
                ->get('presensi_lokasi')
                ->row();
        }

        if (!$lokasi) {
            return ['valid' => false, 'message' => 'Lokasi belum dikonfigurasi. Silakan hubungi admin.', 'method' => 'gps'];
        }

        $distance = $this->calculateDistance($lat, $lng, $lokasi->latitude, $lokasi->longitude);

        if ($distance <= $lokasi->radius_meter) {
            return ['valid' => true, 'message' => 'OK', 'id_lokasi' => $lokasi->id_lokasi, 'method' => 'gps'];
        }

        return ['valid' => false, 'message' => 'Lokasi di luar radius yang diizinkan (' . round($distance) . 'm dari ' . $lokasi->nama_lokasi . ')', 'method' => 'gps'];
    }

    public function validateQR($qr_token, $action = 'checkin')
    {
        if (empty($qr_token)) {
            return ['valid' => false, 'message' => 'QR Code diperlukan', 'method' => 'qr'];
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
            return ['valid' => false, 'message' => 'QR Code tidak valid atau sudah kadaluarsa', 'method' => 'qr'];
        }

        if ($action === 'checkin' && $token->token_type === 'checkout') {
            return ['valid' => false, 'message' => 'QR Code ini hanya untuk Absen Pulang', 'method' => 'qr'];
        }

        if ($action === 'checkout' && $token->token_type === 'checkin') {
            return ['valid' => false, 'message' => 'QR Code ini hanya untuk Absen Masuk', 'method' => 'qr'];
        }

        if ($token->max_usage && $token->used_count >= $token->max_usage) {
            return ['valid' => false, 'message' => 'QR Code sudah mencapai batas penggunaan', 'method' => 'qr'];
        }

        return [
            'valid' => true,
            'message' => 'OK',
            'id_lokasi' => $token->id_lokasi,
            'token_data' => $token,
            'method' => 'qr'
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
        $data_effective = $validation['data_effective'] ?? (is_array($data) ? $data : []);
        $bypass = $validation['bypass'] ?? null;
        $method = $validation_result['method'] ?? (!empty($data_effective['qr_token']) ? 'qr' : 'gps');

        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');

        if ($method === 'bypass' && $bypass && !empty($bypass->created_at)) {
            $parsed = strtotime((string) $bypass->created_at);
            if ($parsed) {
                $now = date('Y-m-d H:i:s', $parsed);
            }
        }

        $status = $this->calculateLateStatus($now, $shift);

        $insert_data = [
            'id_user' => $id_user,
            'tanggal' => $today,
            'id_shift' => $shift->id_shift,
            'id_lokasi' => $validation_result['id_lokasi'] ?? $config->id_lokasi_default,
            'jam_masuk' => $now,
            'metode_masuk' => $method,
            'lat_masuk' => $data_effective['lat'] ?? null,
            'long_masuk' => $data_effective['lng'] ?? null,
            'foto_masuk' => $data_effective['photo'] ?? null,
            'qr_token_masuk' => ($method === 'qr') ? ($data_effective['qr_token'] ?? null) : null,
            'status_kehadiran' => $status['status'],
            'terlambat_menit' => $status['menit'],
            'is_overnight' => $shift->is_lintas_hari
        ];

        if ($method === 'bypass' && $bypass) {
            $insert_data['id_bypass'] = (int) $bypass->id_bypass;

            if (empty($insert_data['lat_masuk']) && $bypass->latitude !== null) {
                $insert_data['lat_masuk'] = $bypass->latitude;
            }

            if (empty($insert_data['long_masuk']) && $bypass->longitude !== null) {
                $insert_data['long_masuk'] = $bypass->longitude;
            }

            if (empty($insert_data['foto_masuk']) && !empty($bypass->foto_bukti)) {
                $insert_data['foto_masuk'] = $bypass->foto_bukti;
            }
        }

        $existing_log = $this->db->where('id_user', $id_user)
            ->where('tanggal', $today)
            ->get('presensi_logs')
            ->row();

        $update_data = $insert_data;
        unset($update_data['id_user'], $update_data['tanggal']);
        $update_data['keterangan'] = null;
        $update_data['is_manual_entry'] = 0;
        $update_data['manual_entry_by'] = null;
        $update_data['manual_entry_reason'] = null;

        // Transaction safety
        $this->db->trans_begin();

        if ($existing_log) {
            $this->db->where('id_log', $existing_log->id_log)
                ->update('presensi_logs', $update_data);
        } else {
            $this->db->insert('presensi_logs', $insert_data);
        }

        if ($method === 'qr' && !empty($data['qr_token'])) {
            $this->db->where('token_code', $data['qr_token'])
                ->where('used_count < max_usage', null, false)
                ->update('presensi_qr_token');
        }

        if ($this->db->trans_status() === FALSE) {
            $db_error = $this->db->error();
            $this->db->trans_rollback();

            if (!$existing_log && isset($db_error['code']) && (int) $db_error['code'] === 1062) {
                $existing_log = $this->db->where('id_user', $id_user)
                    ->where('tanggal', $today)
                    ->get('presensi_logs')
                    ->row();

                if ($existing_log && $existing_log->jam_masuk) {
                    return [
                        'success' => true,
                        'status' => $existing_log->status_kehadiran,
                        'terlambat_menit' => $existing_log->terlambat_menit
                    ];
                }

                if ($existing_log) {
                    $this->db->trans_begin();

                    $this->db->where('id_log', $existing_log->id_log)
                        ->update('presensi_logs', $update_data);

        if ($method === 'qr' && !empty($data['qr_token'])) {
            $this->db->where('token_code', $data['qr_token'])
                ->where('used_count < max_usage', null, false)
                ->where('used_count < max_usage', null, false)
                ->update('presensi_qr_token');
        }

                    if ($this->db->trans_status() !== FALSE) {
                        $this->db->trans_commit();
                        $this->logAudit($id_user, 'checkin', array_merge(['id_user' => $id_user, 'tanggal' => $today], $update_data), $existing_log);

                        return ['success' => true, 'status' => $status['status'], 'terlambat_menit' => $status['menit']];
                    }

                    $this->db->trans_rollback();
                }
            }

            return ['success' => false, 'message' => 'Gagal menyimpan data presensi'];
        }

        $this->db->trans_commit();
        if ($existing_log) {
            $this->logAudit($id_user, 'checkin', array_merge(['id_user' => $id_user, 'tanggal' => $today], $update_data), $existing_log);
        } else {
            $this->logAudit($id_user, 'checkin', $insert_data);
        }

        if ($method === 'bypass' && $bypass) {
            $this->markBypassUsedIfApplicable((int) $bypass->id_bypass, $id_user);
        }

        return ['success' => true, 'status' => $status['status'], 'terlambat_menit' => $status['menit']];
    }

    // =========================================================================
    // AUTO-ALPHA (No Cron: Runs On-Demand)
    // =========================================================================

    public function runAutoAlphaIfDue()
    {
        if (!$this->db->table_exists('presensi_config_global') || !$this->db->table_exists('presensi_logs')) {
            return null;
        }

        $config = $this->getGlobalConfig();

        if (empty($config['auto_alpha_enabled'])) {
            return null;
        }

        $timezone = $config['timezone'] ?? null;
        if ($timezone) {
            @date_default_timezone_set($timezone);
        }

        $auto_time = $config['auto_alpha_time'] ?? '23:00';
        if (!preg_match('/^\\d{2}:\\d{2}$/', $auto_time)) {
            $auto_time = '23:00';
        }

        $now = new DateTime();
        $today = $now->format('Y-m-d');

        $run_at = DateTime::createFromFormat('Y-m-d H:i', $today . ' ' . $auto_time);
        if (!$run_at) {
            $run_at = new DateTime($today . ' 23:00');
        }

        $target_date = $today;
        if ($now < $run_at) {
            $target_date = date('Y-m-d', strtotime('-1 day'));
        }

        $last_run = $config['auto_alpha_last_run'] ?? null;
        if ($last_run === $target_date) {
            return null;
        }

        $result = $this->generateAlphaLogsForDate($target_date, 'auto');

        $this->upsertGlobalConfigKey(
            'auto_alpha_last_run',
            $target_date,
            'string',
            'Last auto-alpha run date (internal)'
        );

        $this->config_global['auto_alpha_last_run'] = $target_date;

        return $result;
    }

    public function cleanupAutoAlphaForHolidayDate($date)
    {
        if (!$this->db->table_exists('presensi_logs') || !$this->db->table_exists('presensi_hari_libur')) {
            return ['checked' => 0, 'deleted' => 0];
        }

        $rows = $this->db->select('id_log, id_user')
            ->from('presensi_logs')
            ->where('tanggal', $date)
            ->where('status_kehadiran', 'Alpha')
            ->where('is_manual_entry', 1)
            ->where('jam_masuk IS NULL', null, false)
            ->where('jam_pulang IS NULL', null, false)
            ->group_start()
                ->where('keterangan', 'Auto-Alpha')
                ->or_where('manual_entry_reason', 'Auto-Alpha (system)')
            ->group_end()
            ->get()
            ->result();

        $checked = count($rows);
        $deleted = 0;

        foreach ($rows as $row) {
            $config = $this->getResolvedConfig((int) $row->id_user);
            if ($config && $this->isHoliday($date, $config->holiday_mode)) {
                $this->db->where('id_log', (int) $row->id_log)->delete('presensi_logs');
                if ($this->db->affected_rows() > 0) {
                    $deleted++;
                }
            }
        }

        return ['checked' => $checked, 'deleted' => $deleted];
    }

    private function hasExplicitWorkingScheduleForDate($id_user, $date)
    {
        $day_of_week = (int) date('N', strtotime($date));
        $primary_group = $this->getPrimaryPresensiGroup($id_user);

        if (!$primary_group) {
            return false;
        }

        if ($this->db->table_exists('presensi_jadwal_override')) {
            $override = $this->db->select('id_shift')
                ->where('id_user', $id_user)
                ->where('tanggal', $date)
                ->get('presensi_jadwal_override')
                ->row();

            if ($override) {
                return !empty($override->id_shift);
            }

            $group_override = $this->db->select('id_shift')
                ->where('id_group', $primary_group->id)
                ->where('id_user IS NULL', null, false)
                ->where('tanggal', $date)
                ->get('presensi_jadwal_override')
                ->row();

            if ($group_override) {
                return !empty($group_override->id_shift);
            }
        }

        if ($this->db->table_exists('presensi_jadwal_user')) {
            $user_schedule = $this->db->select('id_shift')
                ->where('id_user', $id_user)
                ->where('day_of_week', $day_of_week)
                ->where('is_active', 1)
                ->get('presensi_jadwal_user')
                ->row();

            if ($user_schedule) {
                return $user_schedule->id_shift !== null && (int) $user_schedule->id_shift > 0;
            }
        }

        if ($primary_group->name === 'tendik' && $this->db->table_exists('presensi_jadwal_tendik')) {
            $tipe_tendik = $this->getTendikTypeForUser($id_user);

            if ($tipe_tendik) {
                $row = $this->db->select('id_shift')
                    ->where('tipe_tendik', $tipe_tendik)
                    ->where('day_of_week', $day_of_week)
                    ->where('is_active', 1)
                    ->get('presensi_jadwal_tendik')
                    ->row();

                if ($row) {
                    return !empty($row->id_shift);
                }
            }
        }

        if ($this->db->table_exists('presensi_jadwal_kerja')) {
            $row = $this->db->select('id_shift')
                ->where('id_group', $primary_group->id)
                ->where('day_of_week', $day_of_week)
                ->where('is_active', 1)
                ->get('presensi_jadwal_kerja')
                ->row();

            if ($row) {
                return !empty($row->id_shift);
            }
        }

        return false;
    }

    private function generateAlphaLogsForDate($date, $source = 'auto')
    {
        $work_groups = ['guru', 'siswa', 'tendik'];

        $users = $this->db->select('DISTINCT u.id', false)
            ->from('users u')
            ->join('users_groups ug', 'u.id = ug.user_id')
            ->join('groups g', 'ug.group_id = g.id')
            ->where_in('g.name', $work_groups)
            ->where('u.active', 1)
            ->get()
            ->result_array();

        if (empty($users)) {
            return ['inserted' => 0, 'skipped' => 0, 'errors' => 0];
        }

        $user_ids = [];
        foreach ($users as $row) {
            $user_ids[] = (int) $row['id'];
        }

        $existing_rows = $this->db->select('id_user')
            ->from('presensi_logs')
            ->where('tanggal', $date)
            ->get()
            ->result_array();

        $existing_map = [];
        foreach ($existing_rows as $row) {
            $existing_map[(int) $row['id_user']] = true;
        }

        $leave_map = [];
        if ($this->db->table_exists('presensi_pengajuan')) {
            $leave_rows = $this->db->select('DISTINCT p.id_user', false)
                ->from('presensi_pengajuan p')
                ->join('users_groups ug', 'p.id_user = ug.user_id')
                ->join('groups g', 'ug.group_id = g.id')
                ->where_in('g.name', $work_groups)
                ->where('p.status', 'Disetujui')
                ->where('p.tgl_mulai <=', $date)
                ->where('p.tgl_selesai >=', $date)
                ->where_in('p.tipe_pengajuan', ['Izin', 'Sakit', 'Cuti', 'Dinas'])
                ->get()
                ->result_array();

            foreach ($leave_rows as $row) {
                $leave_map[(int) $row['id_user']] = true;
            }
        }

        $inserted = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($user_ids as $id_user) {
            if (isset($existing_map[$id_user]) || isset($leave_map[$id_user])) {
                $skipped++;
                continue;
            }

            if (!$this->hasExplicitWorkingScheduleForDate($id_user, $date)) {
                $skipped++;
                continue;
            }

            $config = $this->getResolvedConfig($id_user);
            if ($this->isHoliday($date, $config->holiday_mode)) {
                $skipped++;
                continue;
            }

            $shift = $this->getUserShiftForDate($id_user, $date);
            if (!$shift) {
                $skipped++;
                continue;
            }

            $insert_data = [
                'id_user' => $id_user,
                'tanggal' => $date,
                'id_shift' => $shift->id_shift,
                'id_lokasi' => $config->id_lokasi_default,
                'status_kehadiran' => 'Alpha',
                'keterangan' => $source === 'auto' ? 'Auto-Alpha' : 'Generate Alpha',
                'is_manual_entry' => 1,
                'manual_entry_by' => null,
                'manual_entry_reason' => $source === 'auto' ? 'Auto-Alpha (system)' : 'Generate Alpha (admin)'
            ];

            $ok = $this->db->insert('presensi_logs', $insert_data);

            if (!$ok) {
                $db_error = $this->db->error();

                if (isset($db_error['code']) && (int) $db_error['code'] === 1062) {
                    $existing_map[$id_user] = true;
                    $skipped++;
                    continue;
                }

                $errors++;
                log_message(
                    'error',
                    'Auto-alpha insert failed. user=' . $id_user . ' date=' . $date . ' error=' . ($db_error['message'] ?? 'unknown')
                );
                continue;
            }

            $existing_map[$id_user] = true;
            $inserted++;
        }

        return ['inserted' => $inserted, 'skipped' => $skipped, 'errors' => $errors];
    }

    private function upsertGlobalConfigKey($key, $value, $type = 'string', $description = null)
    {
        if (!$this->db->table_exists('presensi_config_global')) {
            return false;
        }

        $existing = $this->db->select('id')
            ->where('config_key', $key)
            ->get('presensi_config_global')
            ->row();

        $data = [
            'config_value' => (string) $value,
            'config_type' => $type
        ];

        if ($description !== null) {
            $data['description'] = $description;
        }

        if ($existing) {
            $this->db->where('id', $existing->id)
                ->update('presensi_config_global', $data);
            return $this->db->affected_rows() >= 0;
        }

        $data['config_key'] = $key;
        $this->db->insert('presensi_config_global', $data);
        return $this->db->affected_rows() > 0;
    }

    // =========================================================================
    // CHECK-OUT PROCESS
    // =========================================================================

    public function doCheckOut($id_user, $data)
    {
        // Find open attendance log (handles overnight shifts)
        // Look for most recent log with jam_pulang IS NULL within last 2 days
        $log = $this->getOpenAttendanceLog($id_user);

        if (!$log) {
            return ['success' => false, 'message' => 'Tidak ada presensi yang belum absen pulang'];
        }

        if ($log->jam_pulang) {
            return ['success' => false, 'message' => 'Anda sudah absen pulang'];
        }

        $checkout_validation = $this->validateCheckOut($id_user, $data, $log->tanggal ?? null);

        if (!$checkout_validation['success']) {
            return $checkout_validation;
        }

        $validation_result = $checkout_validation['validation'];
        $data_effective = $checkout_validation['data_effective'] ?? (is_array($data) ? $data : []);
        $bypass = $checkout_validation['bypass'] ?? null;
        $method = $validation_result['method'] ?? (!empty($data_effective['qr_token']) ? 'qr' : (!empty($data_effective['lat']) && !empty($data_effective['lng']) ? 'gps' : 'manual'));
        $shift = $this->getShiftById($log->id_shift);

        $now = date('Y-m-d H:i:s');

        if ($method === 'bypass' && $bypass && !empty($bypass->created_at)) {
            $parsed = strtotime((string) $bypass->created_at);
            if ($parsed) {
                $now = date('Y-m-d H:i:s', $parsed);
            }
        }

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
            'metode_pulang' => $method,
            'lat_pulang' => $data_effective['lat'] ?? null,
            'long_pulang' => $data_effective['lng'] ?? null,
            'qr_token_pulang' => ($method === 'qr') ? ($data_effective['qr_token'] ?? null) : null,
            'pulang_awal_menit' => $pulang_awal_menit
        ];

        if ($method === 'bypass' && $bypass) {
            $update_data['id_bypass'] = (int) $bypass->id_bypass;

            if (empty($update_data['lat_pulang']) && $bypass->latitude !== null) {
                $update_data['lat_pulang'] = $bypass->latitude;
            }

            if (empty($update_data['long_pulang']) && $bypass->longitude !== null) {
                $update_data['long_pulang'] = $bypass->longitude;
            }

            if (!empty($bypass->foto_bukti)) {
                $update_data['foto_pulang'] = $bypass->foto_bukti;
            }
        }

        $status_kehadiran = $log->status_kehadiran;

        if ($pulang_awal_menit > 0 && $log->terlambat_menit > 0) {
            $status_kehadiran = 'Terlambat + Pulang Awal';
        } elseif ($pulang_awal_menit > 0) {
            $status_kehadiran = 'Pulang Awal';
        }

        $update_data['status_kehadiran'] = $status_kehadiran;

        // Transaction safety
        $this->db->trans_begin();

        $this->db->where('id_log', $log->id_log)->update('presensi_logs', $update_data);

        if ($method === 'qr' && !empty($data['qr_token'])) {
            $this->db->where('token_code', $data['qr_token'])
                ->where('used_count < max_usage', null, false)
                ->update('presensi_qr_token');
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return ['success' => false, 'message' => 'Gagal menyimpan data absen pulang'];
        }

        $this->db->trans_commit();
        $this->logAudit($id_user, 'checkout', $update_data, $log);

        if ($method === 'bypass' && $bypass) {
            $this->markBypassUsedIfApplicable((int) $bypass->id_bypass, $id_user);
        }

        return ['success' => true, 'status' => $status_kehadiran];
    }

    public function validateCheckOut($id_user, $data, $log_date = null)
    {
        $config = $this->getResolvedConfig($id_user);
        $data_effective = is_array($data) ? $data : [];
        $approved_bypass = null;

        if (!empty($config->allow_bypass)) {
            $dates = [date('Y-m-d')];
            if ($log_date) {
                $parsed = strtotime((string) $log_date);
                if ($parsed) {
                    $dates[] = date('Y-m-d', $parsed);
                }
            }
            $approved_bypass = $this->getApprovedBypassForAction($id_user, $dates, 'checkout');
        }

        $validation = $this->validateMethod($config->validation_mode, $data_effective, $config->id_lokasi_default, 'checkout');

        if (!$validation['valid']) {
            if ($approved_bypass) {
                return [
                    'success' => true,
                    'config' => $config,
                    'validation' => [
                        'valid' => true,
                        'message' => 'OK (bypass approved)',
                        'id_lokasi' => $config->id_lokasi_default,
                        'method' => 'bypass',
                        'id_bypass' => (int) $approved_bypass->id_bypass
                    ],
                    'bypass' => $approved_bypass,
                    'data_effective' => $data_effective
                ];
            }
            return ['success' => false, 'message' => $validation['message'], 'show_bypass' => $config->allow_bypass];
        }

        return [
            'success' => true,
            'config' => $config,
            'validation' => $validation,
            'bypass' => $approved_bypass,
            'data_effective' => $data_effective
        ];
    }

    // =========================================================================
    // GET LOGS
    // =========================================================================

    /**
     * Get open attendance log (checked in but not checked out)
     * Handles overnight shifts by looking back up to 2 days
     *
     * @param int $id_user User ID
     * @return object|null Open log or null if none found
     */
    public function getOpenAttendanceLog($id_user)
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        return $this->db->where('id_user', $id_user)
            ->where('jam_masuk IS NOT NULL', null, false)
            ->where('jam_pulang IS NULL', null, false)
            ->where('tanggal >=', $yesterday)
            ->where('tanggal <=', $today)
            ->order_by('tanggal', 'DESC')
            ->order_by('jam_masuk', 'DESC')
            ->limit(1)
            ->get('presensi_logs')
            ->row();
    }

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

        $month_start = date('Y-m-01 00:00:00');
        $next_month_start = date('Y-m-01 00:00:00', strtotime('+1 month'));

        // Count all relevant bypasses that contribute to the limit
        // Include pending, approved, and used statuses
        $bypass_count = $this->db->where('id_user', $id_user)
            ->where('created_at >=', $month_start)
            ->where('created_at <', $next_month_start)
            ->where_in('status', ['pending', 'approved', 'used'])
            ->count_all_results('presensi_bypass');

        // Also check for duplicate bypass requests for same date and type
        $duplicate_count = $this->db->where('id_user', $id_user)
            ->where('tanggal', date('Y-m-d'))
            ->where('tipe_bypass', $data['tipe'] ?? 'checkin')
            ->where('created_at >=', $month_start)
            ->where('created_at <', $next_month_start)
            ->where_in('status', ['pending', 'approved', 'used'])
            ->count_all_results('presensi_bypass');

        if ($bypass_count >= $config->max_bypass_per_month ?? 3) {
            return ['success' => false, 'message' => 'Batas bypass bulanan telah tercapai'];
        }

        if ($duplicate_count > 0) {
            return ['success' => false, 'message' => 'Anda sudah memiliki pengajuan bypass untuk hari ini'];
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

        if (!empty($config->bypass_auto_approve)) {
            $insert_data['status'] = 'approved';
            $insert_data['approved_by'] = null;
            $insert_data['approved_at'] = date('Y-m-d H:i:s');
            $insert_data['catatan_admin'] = 'Auto-approved by system';
        }

        if ($this->db->insert('presensi_bypass', $insert_data)) {
            $this->logAudit($id_user, 'bypass_request', $insert_data);
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Gagal menyimpan bypass request'];
    }

    public function getBypassRequests($status = 'pending', $group_name = null)
    {
        if (!$this->db->table_exists('presensi_bypass')) {
            return [];
        }

        $allowed_statuses = ['pending', 'approved', 'rejected', 'used', 'expired'];
        if ($status !== null && $status !== '' && !in_array($status, $allowed_statuses, true)) {
            $status = 'pending';
        }

        $allowed_groups = ['guru', 'tendik', 'siswa'];
        if ($group_name !== null && $group_name !== '' && !in_array($group_name, $allowed_groups, true)) {
            $group_name = null;
        }

        $name_candidates = [];
        if ($this->db->table_exists('master_guru')) {
            $name_candidates[] = 'mg.nama_guru';
        }
        if ($this->db->table_exists('master_tendik')) {
            $name_candidates[] = 'mt.nama_tendik';
        }
        if ($this->db->table_exists('master_siswa')) {
            $name_candidates[] = 'ms.nama';
        }
        $name_candidates[] = "NULLIF(TRIM(CONCAT(u.first_name, ' ', u.last_name)), '')";
        $name_candidates[] = 'u.username';
        $name_expr = 'COALESCE(' . implode(', ', $name_candidates) . ')';

        $this->db->select('pb.id_bypass');
        $this->db->select('pb.id_user');
        $this->db->select('pb.tanggal');
        $this->db->select('pb.tipe_bypass');
        $this->db->select('pb.alasan');
        $this->db->select('pb.lokasi_alternatif');
        $this->db->select('pb.latitude');
        $this->db->select('pb.longitude');
        $this->db->select('pb.foto_bukti');
        $this->db->select('pb.status');
        $this->db->select('pb.approved_by');
        $this->db->select('pb.approved_at');
        $this->db->select('pb.catatan_admin');
        $this->db->select('pb.created_at');
        $this->db->select('u.username');
        $this->db->select('u.email');
        $this->db->select($name_expr . ' AS nama', false);
        $this->db->from('presensi_bypass pb');
        $this->db->join('users u', 'pb.id_user = u.id');

        if ($this->db->table_exists('master_guru')) {
            $this->db->join('master_guru mg', 'mg.id_user = u.id', 'left');
        }

        if ($this->db->table_exists('master_tendik')) {
            $this->db->join('master_tendik mt', 'mt.id_user = u.id AND mt.is_active = 1', 'left', false);
        }

        if ($this->db->table_exists('master_siswa')) {
            $this->db->join('master_siswa ms', 'ms.username = u.username', 'left');
        }

        if ($status !== null && $status !== '') {
            $this->db->where('pb.status', $status);
        }

        if ($group_name) {
            $exists = 'EXISTS (SELECT 1 FROM users_groups ug2 JOIN groups g2 ON ug2.group_id = g2.id WHERE ug2.user_id = u.id AND g2.name = ' . $this->db->escape($group_name) . ')';
            $this->db->where($exists, null, false);
        }

        $this->db->order_by('pb.created_at', 'ASC');

        $query = $this->db->get();
        if (!$query) {
            return [];
        }

        $rows = $query->result();

        $user_ids = [];
        foreach ($rows as $r) {
            $user_ids[] = (int) ($r->id_user ?? 0);
        }
        $user_ids = array_values(array_unique(array_filter($user_ids)));

        $roles = $this->getPresensiRoleLabelsByUserIds($user_ids);

        foreach ($rows as $r) {
            $uid = (int) ($r->id_user ?? 0);
            $r->role = $roles[$uid] ?? '';
        }

        return $rows;
    }

    private function getPresensiRoleLabelsByUserIds($user_ids)
    {
        if ( ! has_where_in_values($user_ids)) {
            return [];
        }
        $user_ids = array_values(array_unique(array_map('intval', (array) ci_where_in_values($user_ids))));
        $user_ids = array_filter($user_ids);

        if (empty($user_ids)) {
            return [];
        }

        $role_priority_expr = "MAX(CASE WHEN g.name = 'tendik' THEN 3 WHEN g.name = 'guru' THEN 2 WHEN g.name = 'siswa' THEN 1 ELSE 0 END)";

        $this->db->select('ug.user_id');
        $this->db->select($role_priority_expr . ' AS role_priority', false);
        $this->db->from('users_groups ug');
        $this->db->join('groups g', 'ug.group_id = g.id');
        $this->db->where_in('ug.user_id', $user_ids);
        $this->db->where_in('g.name', ['guru', 'tendik', 'siswa']);
        $this->db->group_by('ug.user_id');

        $query = $this->db->get();
        if (!$query) {
            return [];
        }

        $map = [];
        foreach ($query->result() as $row) {
            $priority = (int) ($row->role_priority ?? 0);
            $label = '';
            if ($priority === 3) {
                $label = 'Tendik';
            } elseif ($priority === 2) {
                $label = 'Guru';
            } elseif ($priority === 1) {
                $label = 'Siswa';
            }
            $map[(int) $row->user_id] = $label;
        }

        return $map;
    }

    public function updateBypassRequestStatus($id_bypass, $status, $admin_id, $note = null)
    {
        if (!$this->db->table_exists('presensi_bypass')) {
            return ['success' => false, 'message' => 'Tabel presensi_bypass belum tersedia'];
        }

        $id_bypass = (int) $id_bypass;
        $admin_id = (int) $admin_id;

        if (!$id_bypass) {
            return ['success' => false, 'message' => 'ID bypass tidak valid'];
        }

        if (!$admin_id) {
            return ['success' => false, 'message' => 'User approval tidak valid'];
        }

        if (!in_array($status, ['approved', 'rejected'], true)) {
            return ['success' => false, 'message' => 'Status tidak valid'];
        }

        $bypass = $this->db->where('id_bypass', $id_bypass)->get('presensi_bypass')->row();
        if (!$bypass) {
            return ['success' => false, 'message' => 'Request bypass tidak ditemukan'];
        }

        if ($bypass->status !== 'pending') {
            return ['success' => false, 'message' => 'Request bypass sudah diproses'];
        }

        $data = [
            'status' => $status,
            'approved_by' => $admin_id,
            'approved_at' => date('Y-m-d H:i:s'),
            'catatan_admin' => $note ? (string) $note : null
        ];

        $ok = $this->db->where('id_bypass', $id_bypass)
            ->where('status', 'pending')
            ->update('presensi_bypass', $data);

        if (!$ok) {
            return ['success' => false, 'message' => 'Gagal memperbarui status bypass'];
        }

        if ($this->db->affected_rows() < 1) {
            return ['success' => false, 'message' => 'Request sudah diproses oleh user lain'];
        }

        $this->logAudit((int) $bypass->id_user, 'bypass_' . $status, $data, $bypass);

        return ['success' => true];
    }

    public function getApprovedBypassForAction($id_user, $dates, $action)
    {
        if (!$this->db->table_exists('presensi_bypass')) {
            return null;
        }

        $id_user = (int) $id_user;
        if (!$id_user) {
            return null;
        }

        $allowed_actions = ['checkin', 'checkout'];
        if (!in_array($action, $allowed_actions, true)) {
            return null;
        }

        $dates = is_array($dates) ? $dates : [$dates];
        $date_values = [];
        foreach ($dates as $d) {
            $parsed = strtotime((string) $d);
            if ($parsed) {
                $date_values[] = date('Y-m-d', $parsed);
            }
        }
        $date_values = array_values(array_unique(array_filter($date_values)));

        if (empty($date_values)) {
            return null;
        }

        $allowed_types = ($action === 'checkin') ? ['checkin', 'both'] : ['checkout', 'both'];

        return $this->db->where('id_user', $id_user)
            ->where_in('tanggal', $date_values)
            ->where_in('tipe_bypass', $allowed_types)
            ->where('status', 'approved')
            ->order_by('created_at', 'DESC')
            ->limit(1)
            ->get('presensi_bypass')
            ->row();
    }

    public function markBypassUsedIfApplicable($id_bypass, $id_user)
    {
        if (!$this->db->table_exists('presensi_bypass') || !$this->db->table_exists('presensi_logs')) {
            return false;
        }

        $id_bypass = (int) $id_bypass;
        $id_user = (int) $id_user;

        if (!$id_bypass || !$id_user) {
            return false;
        }

        $bypass = $this->db->where('id_bypass', $id_bypass)->get('presensi_bypass')->row();
        if (!$bypass) {
            return false;
        }

        if ($bypass->status !== 'approved') {
            return false;
        }

        $log = $this->db->select('jam_masuk, jam_pulang, metode_masuk, metode_pulang')
            ->from('presensi_logs')
            ->where('id_user', $id_user)
            ->where('id_bypass', $id_bypass)
            ->limit(1)
            ->get()
            ->row();

        if (!$log) {
            return false;
        }

        $checkin_used = !empty($log->jam_masuk) && $log->metode_masuk === 'bypass';
        $checkout_used = !empty($log->jam_pulang) && $log->metode_pulang === 'bypass';

        $tipe = $bypass->tipe_bypass ?? null;
        $should_mark_used = false;

        if ($tipe === 'checkin' && $checkin_used) {
            $should_mark_used = true;
        } elseif ($tipe === 'checkout' && $checkout_used) {
            $should_mark_used = true;
        } elseif ($tipe === 'both' && $checkin_used && $checkout_used) {
            $should_mark_used = true;
        }

        if (!$should_mark_used) {
            return false;
        }

        $ok = $this->db->where('id_bypass', $id_bypass)
            ->where('status', 'approved')
            ->update('presensi_bypass', [
                'status' => 'used',
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        return (bool) $ok;
    }

    // =========================================================================
    // AUDIT LOG WITH DECISION TRACE
    // =========================================================================

    /**
     * Log audit trail with decision context
     * Stores effective config snapshot for debugging "why behavior changed"
     *
     * @param int $id_user_target User being affected
     * @param string $action Action type (checkin, checkout, bypass_request, etc.)
     * @param array $data_after Data after change
     * @param object|null $data_before Data before change (for updates)
     * @param array|null $decision_context Additional context (config, shift, etc.)
     */
    public function logAudit($id_user_target, $action, $data_after, $data_before = null, $decision_context = null)
    {
        $action_by = $this->session->userdata('user_id');

        if (!$action_by) {
            $action_by = $id_user_target;
        }

        // Build decision trace for debugging
        $decision_trace = null;
        if (in_array($action, ['checkin', 'checkout'])) {
            $config = $this->getResolvedConfig($id_user_target);
            $decision_trace = [
                'effective_config' => [
                    'group_id' => $config->group_id,
                    'group_name' => $config->group_name,
                    'validation_mode' => $config->validation_mode,
                    'require_photo' => $config->require_photo,
                    'require_checkout' => $config->require_checkout,
                    'allow_bypass' => $config->allow_bypass,
                    'holiday_mode' => $config->holiday_mode
                ],
                'timestamp' => date('Y-m-d H:i:s'),
                'context' => $decision_context
            ];
        }

        // Merge decision trace into data_after
        if ($decision_trace) {
            $data_after['_decision_trace'] = $decision_trace;
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
            if ( ! has_where_in_values($groups)) {
                return [];
            }
            $groups = ci_where_in_values($groups);
            $this->db->join('users_groups ug', 'u.id = ug.user_id');
            $this->db->join('groups g', 'ug.group_id = g.id');
            $this->db->where_in('g.name', $groups);
        }

        $this->db->order_by('u.first_name', 'ASC');

        return $this->db->get()->result();
    }

    public function getTodayStats()
    {
        return $this->getStatsByDateAndGroups(date('Y-m-d'));
    }

    public function getTodayStatsByGroups($group_names = [])
    {
        return $this->getStatsByDateAndGroups(date('Y-m-d'), $group_names);
    }

    public function getStatsByDateAndGroups($date, $group_names = [])
    {
        if (!$date) {
            $date = date('Y-m-d');
        } else {
            $parsed = strtotime($date);
            if ($parsed) {
                $date = date('Y-m-d', $parsed);
            }
        }

        return [
            'hadir' => $this->countLogsByDateStatus($date, 'Hadir', $group_names),
            'terlambat' => $this->countLogsByDateStatus($date, 'Terlambat', $group_names),
            'alpha' => $this->countLogsByDateStatus($date, 'Alpha', $group_names),
            'izin' => $this->countLogsByDateStatuses($date, ['Izin', 'Sakit', 'Cuti'], $group_names),
        ];
    }

    private function countLogsByDateStatus($date, $status, $group_names = [])
    {
        $this->db->select('COUNT(DISTINCT pl.id_log) AS total', false);
        $this->db->from('presensi_logs pl');
        $this->db->where('pl.tanggal', $date);
        $this->db->where('pl.status_kehadiran', $status);

        if (!empty($group_names)) {
            $this->db->join('users_groups ug', 'pl.id_user = ug.user_id');
            $this->db->join('groups g', 'ug.group_id = g.id');
            $this->db->where_in('g.name', $group_names);
        }

        $row = $this->db->get()->row();
        return (int) ($row->total ?? 0);
    }

    private function countLogsByDateStatuses($date, $statuses, $group_names = [])
    {
        $this->db->select('COUNT(DISTINCT pl.id_log) AS total', false);
        $this->db->from('presensi_logs pl');
        $this->db->where('pl.tanggal', $date);
        $this->db->where_in('pl.status_kehadiran', $statuses);

        if (!empty($group_names)) {
            $this->db->join('users_groups ug', 'pl.id_user = ug.user_id');
            $this->db->join('groups g', 'ug.group_id = g.id');
            $this->db->where_in('g.name', $group_names);
        }

        $row = $this->db->get()->row();
        return (int) ($row->total ?? 0);
    }

    public function getRekapUserStats($start_date, $end_date, $group_names = [])
    {
        if (empty($group_names)) {
            return [];
        }

        if (!$this->db->table_exists('presensi_logs')) {
            return [];
        }

        $start_parsed = strtotime((string) $start_date);
        $end_parsed = strtotime((string) $end_date);

        if (!$start_parsed || !$end_parsed) {
            return [];
        }

        $start_date = date('Y-m-d', $start_parsed);
        $end_date = date('Y-m-d', $end_parsed);

        $name_candidates = [];

        if ($this->db->table_exists('master_guru')) {
            $name_candidates[] = 'mg.nama_guru';
        }

        if ($this->db->table_exists('master_tendik')) {
            $name_candidates[] = 'mt.nama_tendik';
        }

        if ($this->db->table_exists('master_siswa')) {
            $name_candidates[] = 'ms.nama';
        }

        $name_candidates[] = 'NULLIF(TRIM(CONCAT(u.first_name, " ", u.last_name)), "")';
        $name_candidates[] = 'u.username';
        $name_expr = 'COALESCE(' . implode(', ', $name_candidates) . ')';

        $role_priority_expr = "MAX(CASE WHEN g.name = 'tendik' THEN 2 WHEN g.name = 'guru' THEN 1 ELSE 0 END)";
        $role_label_expr = "CASE {$role_priority_expr} WHEN 2 THEN 'Tendik' WHEN 1 THEN 'Guru' ELSE 'Siswa' END";

        $this->db->select('u.id AS user_id');
        $this->db->select('MAX(u.username) AS username', false);
        $this->db->select('MAX(u.email) AS email', false);
        $this->db->select('MAX(' . $name_expr . ') AS nama', false);
        $this->db->select($role_label_expr . ' AS role', false);
        $this->db->select("COUNT(DISTINCT CASE WHEN pl.status_kehadiran = 'Hadir' THEN pl.id_log END) AS hadir", false);
        $this->db->select("COUNT(DISTINCT CASE WHEN pl.status_kehadiran = 'Terlambat' THEN pl.id_log END) AS terlambat", false);
        $this->db->select("COUNT(DISTINCT CASE WHEN pl.status_kehadiran = 'Alpha' THEN pl.id_log END) AS alpha", false);
        $this->db->select("COUNT(DISTINCT CASE WHEN pl.status_kehadiran IN ('Izin', 'Sakit', 'Cuti') THEN pl.id_log END) AS izin", false);
        $this->db->from('users u');
        $this->db->join('users_groups ug', 'u.id = ug.user_id');
        $this->db->join('groups g', 'ug.group_id = g.id');
        $this->db->where('u.active', 1);
        $this->db->where_in('g.name', $group_names);

        if ($this->db->table_exists('master_guru')) {
            $this->db->join('master_guru mg', 'mg.id_user = u.id', 'left');
        }

        if ($this->db->table_exists('master_tendik')) {
            $this->db->join('master_tendik mt', 'mt.id_user = u.id AND mt.is_active = 1', 'left', false);
        }

        if ($this->db->table_exists('master_siswa')) {
            $this->db->join('master_siswa ms', 'ms.username = u.username', 'left');
        }

        $join_logs = 'pl.id_user = u.id AND pl.tanggal >= ' . $this->db->escape($start_date) . ' AND pl.tanggal <= ' . $this->db->escape($end_date);
        $this->db->join('presensi_logs pl', $join_logs, 'left', false);
        $this->db->group_by('u.id');
        $this->db->order_by('nama', 'ASC');

        $query = $this->db->get();

        if (!$query) {
            return [];
        }

        return $query->result();
    }

    public function getRekapPTKUsers()
    {
        $group_names = ['guru', 'tendik'];

        $name_candidates = [];
        if ($this->db->table_exists('master_guru')) {
            $name_candidates[] = 'mg.nama_guru';
        }
        if ($this->db->table_exists('master_tendik')) {
            $name_candidates[] = 'mt.nama_tendik';
        }
        $name_candidates[] = "NULLIF(TRIM(CONCAT(u.first_name, ' ', u.last_name)), '')";
        $name_candidates[] = 'u.username';
        $name_expr = 'COALESCE(' . implode(', ', $name_candidates) . ')';

        $role_priority_expr = "MAX(CASE WHEN g.name = 'tendik' THEN 2 WHEN g.name = 'guru' THEN 1 ELSE 0 END)";
        $role_label_expr = "CASE {$role_priority_expr} WHEN 2 THEN 'Tendik' WHEN 1 THEN 'Guru' ELSE '' END";

        $this->db->select('u.id AS user_id');
        $this->db->select('MAX(u.username) AS username', false);
        $this->db->select('MAX(' . $name_expr . ') AS nama', false);
        $this->db->select($role_label_expr . ' AS role', false);
        $this->db->from('users u');
        $this->db->join('users_groups ug', 'u.id = ug.user_id');
        $this->db->join('groups g', 'ug.group_id = g.id');
        $this->db->where('u.active', 1);
        $this->db->where_in('g.name', $group_names);

        if ($this->db->table_exists('master_guru')) {
            $this->db->join('master_guru mg', 'mg.id_user = u.id', 'left');
        }

        if ($this->db->table_exists('master_tendik')) {
            $this->db->join('master_tendik mt', 'mt.id_user = u.id AND mt.is_active = 1', 'left', false);
        }

        $this->db->group_by('u.id');
        $this->db->order_by('nama', 'ASC');

        $query = $this->db->get();

        if (!$query) {
            return [];
        }

        return $query->result();
    }

    public function getRekapGuruUsers()
    {
        $name_candidates = [];
        if ($this->db->table_exists('master_guru')) {
            $name_candidates[] = 'mg.nama_guru';
        }
        $name_candidates[] = "NULLIF(TRIM(CONCAT(u.first_name, ' ', u.last_name)), '')";
        $name_candidates[] = 'u.username';
        $name_expr = 'COALESCE(' . implode(', ', $name_candidates) . ')';

        $this->db->select('u.id AS user_id');
        $this->db->select('MAX(u.username) AS username', false);
        $this->db->select('MAX(' . $name_expr . ') AS nama', false);
        $this->db->from('users u');
        $this->db->join('users_groups ug', 'u.id = ug.user_id');
        $this->db->join('groups g', 'ug.group_id = g.id');
        $this->db->where('u.active', 1);
        $this->db->where('g.name', 'guru');

        if ($this->db->table_exists('master_guru')) {
            $this->db->join('master_guru mg', 'mg.id_user = u.id', 'left');
        }

        $this->db->group_by('u.id');
        $this->db->order_by('nama', 'ASC');

        $query = $this->db->get();

        if (!$query) {
            return [];
        }

        return $query->result();
    }

    public function getRekapTendikUsers()
    {
        $name_candidates = [];
        if ($this->db->table_exists('master_tendik')) {
            $name_candidates[] = 'mt.nama_tendik';
        }
        $name_candidates[] = "NULLIF(TRIM(CONCAT(u.first_name, ' ', u.last_name)), '')";
        $name_candidates[] = 'u.username';
        $name_expr = 'COALESCE(' . implode(', ', $name_candidates) . ')';

        $this->db->select('u.id AS user_id');
        $this->db->select('MAX(u.username) AS username', false);
        $this->db->select('MAX(' . $name_expr . ') AS nama', false);
        $this->db->from('users u');
        $this->db->join('users_groups ug', 'u.id = ug.user_id');
        $this->db->join('groups g', 'ug.group_id = g.id');
        $this->db->where('u.active', 1);
        $this->db->where('g.name', 'tendik');

        if ($this->db->table_exists('master_tendik')) {
            $this->db->join('master_tendik mt', 'mt.id_user = u.id AND mt.is_active = 1', 'left', false);
        }

        $this->db->group_by('u.id');
        $this->db->order_by('nama', 'ASC');

        $query = $this->db->get();

        if (!$query) {
            return [];
        }

        return $query->result();
    }

    public function getRekapSiswaUsersByKelas($id_kelas, $id_tp, $id_smt)
    {
        $id_kelas = (int) $id_kelas;
        $id_tp = (int) $id_tp;
        $id_smt = (int) $id_smt;

        if (!$id_kelas || !$id_tp || !$id_smt) {
            return [];
        }

        if (
            !$this->db->table_exists('master_siswa') ||
            !$this->db->table_exists('kelas_siswa') ||
            !$this->db->table_exists('master_kelas')
        ) {
            return [];
        }

        $this->db->distinct();
        $this->db->select('u.id AS user_id');
        $this->db->select('u.username');
        $this->db->select('ms.id_siswa');
        $this->db->select('ms.nis');
        $this->db->select('ms.nama AS nama', false);
        $this->db->select('mk.id_kelas');
        $this->db->select('mk.nama_kelas');
        $this->db->from('users u');
        $this->db->join('users_groups ug', 'u.id = ug.user_id');
        $this->db->join('groups g', 'ug.group_id = g.id');
        $this->db->join('master_siswa ms', 'ms.username = u.username');
        $this->db->join('kelas_siswa ks', 'ks.id_siswa = ms.id_siswa AND ks.id_tp = ' . $this->db->escape($id_tp) . ' AND ks.id_smt = ' . $this->db->escape($id_smt), 'inner', false);
        $this->db->join('master_kelas mk', 'mk.id_kelas = ks.id_kelas');
        $this->db->where('u.active', 1);
        $this->db->where('g.name', 'siswa');
        $this->db->where('mk.id_kelas', $id_kelas);
        $this->db->order_by('ms.nama', 'ASC');

        $query = $this->db->get();

        if (!$query) {
            return [];
        }

        return $query->result();
    }

    public function getRekapLogsByUsers($user_ids, $start_date, $end_date)
    {
        if ( ! has_where_in_values($user_ids)) {
            return [];
        }
        $user_ids = ci_where_in_values($user_ids);

        $start_parsed = strtotime((string) $start_date);
        $end_parsed = strtotime((string) $end_date);

        if (!$start_parsed || !$end_parsed) {
            return [];
        }

        $start_date = date('Y-m-d', $start_parsed);
        $end_date = date('Y-m-d', $end_parsed);

        $this->db->select('id_user');
        $this->db->select('tanggal');
        $this->db->select('status_kehadiran');
        $this->db->from('presensi_logs');
        $this->db->where_in('id_user', $user_ids);
        $this->db->where('tanggal >=', $start_date);
        $this->db->where('tanggal <=', $end_date);

        $query = $this->db->get();

        if (!$query) {
            return [];
        }

        return $query->result();
    }

    // =========================================================================
    // SHIFT HELPER METHODS (replaces Shift_model)
    // =========================================================================

    /**
     * Get shift by ID
     *
     * @param int $id_shift Shift ID
     * @return object|null Shift data or null if not found
     */
    public function getShiftById($id_shift)
    {
        return $this->db->where('id_shift', $id_shift)
            ->where('is_active', 1)
            ->get('presensi_shift')
            ->row();
    }

    /**
     * Get all active shifts
     *
     * @return array List of active shifts
     */
    public function getAllShifts()
    {
        return $this->db->where('is_active', 1)
            ->order_by('nama_shift', 'ASC')
            ->get('presensi_shift')
            ->result();
    }

    /**
     * Get weekly schedule for a user (Mon-Sun)
     * Used by Tendik controller for jadwal display
     *
     * @param int $id_user User ID
     * @return array Weekly schedule with day info and shift data
     */
    public function getWeeklyScheduleForUser($id_user)
    {
        // Use the same deterministic group selection as getResolvedConfig
        $primary_group = $this->getPrimaryPresensiGroup($id_user);

        if (!$primary_group) {
            return [];
        }

        $days = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu'
        ];

        $jadwal_user_map = [];
        if ($this->db->table_exists('presensi_jadwal_user')) {
            $rows = $this->db->select('day_of_week, id_shift')
                ->where('id_user', $id_user)
                ->where('is_active', 1)
                ->get('presensi_jadwal_user')
                ->result();

            foreach ($rows as $row) {
                $jadwal_user_map[(int) $row->day_of_week] = $row; // id_shift may be NULL = day off
            }
        }

        $jadwal_tendik_map = [];
        if ($primary_group->name === 'tendik' && $this->db->table_exists('presensi_jadwal_tendik')) {
            $tipe_tendik = $this->getTendikTypeForUser($id_user);

            if ($tipe_tendik) {
                $rows = $this->db->select('pjt.day_of_week, ps.id_shift, ps.nama_shift, ps.jam_masuk, ps.jam_pulang')
                    ->from('presensi_jadwal_tendik pjt')
                    ->join('presensi_shift ps', 'pjt.id_shift = ps.id_shift')
                    ->where('pjt.tipe_tendik', $tipe_tendik)
                    ->where('pjt.is_active', 1)
                    ->get()
                    ->result();

                foreach ($rows as $row) {
                    $jadwal_tendik_map[(int) $row->day_of_week] = $row;
                }
            }
        }

        $jadwal_group_map = [];
        if ($this->db->table_exists('presensi_jadwal_kerja')) {
            $rows = $this->db->select('pjk.day_of_week, ps.id_shift, ps.nama_shift, ps.jam_masuk, ps.jam_pulang')
                ->from('presensi_jadwal_kerja pjk')
                ->join('presensi_shift ps', 'pjk.id_shift = ps.id_shift')
                ->where('pjk.id_group', $primary_group->id)
                ->where('pjk.is_active', 1)
                ->get()
                ->result();

            foreach ($rows as $row) {
                $jadwal_group_map[(int) $row->day_of_week] = $row;
            }
        }

        $schedule = [];

        foreach ($days as $day_num => $day_name) {
            $jadwal = null;
            if (isset($jadwal_user_map[$day_num])) {
                $override = $jadwal_user_map[$day_num];
                if ($override->id_shift !== null) {
                    $jadwal = $this->getShiftById((int) $override->id_shift);
                }
            } else {
                $jadwal = $jadwal_tendik_map[$day_num] ?? $jadwal_group_map[$day_num] ?? null;
            }

            $schedule[] = (object) [
                'day_of_week' => $day_num,
                'nama_hari' => $day_name,
                'id_shift' => $jadwal ? $jadwal->id_shift : null,
                'nama_shift' => $jadwal ? $jadwal->nama_shift : null,
                'jam_masuk' => $jadwal ? $jadwal->jam_masuk : null,
                'jam_pulang' => $jadwal ? $jadwal->jam_pulang : null,
                'is_working_day' => $jadwal ? true : false
            ];
        }

        return $schedule;
    }

    /**
     * Get user's monthly attendance logs
     * Used by Tendik controller for riwayat display
     *
     * @param int $id_user User ID
     * @param int $month Month number (1-12)
     * @param int $year Year (e.g., 2026)
     * @return array Monthly logs
     */
    public function getMonthlyLogs($id_user, $month, $year)
    {
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));

        return $this->getUserLogs($id_user, $start_date, $end_date);
    }
}
