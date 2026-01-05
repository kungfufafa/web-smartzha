<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Absensi_model - Comprehensive Attendance Model V2
 * 
 * Handles all attendance-related database operations including:
 * - Configuration management
 * - Multiple office locations
 * - QR token generation & validation
 * - Bypass request workflow
 * - Attendance logging
 * - Dashboard statistics
 * - Reporting & rekap
 * - Audit trail
 */
class Absensi_model extends CI_Model
{
    private $audit_schema_checked = false;
    private $audit_has_changes_column = false;
    private $audit_has_data_after_column = false;
    private $audit_has_data_before_column = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    private function initAuditSchemaCache()
    {
        if ($this->audit_schema_checked) {
            return;
        }

        $this->audit_has_changes_column = $this->db->field_exists('changes', 'absensi_audit_log');
        $this->audit_has_data_after_column = $this->db->field_exists('data_after', 'absensi_audit_log');
        $this->audit_has_data_before_column = $this->db->field_exists('data_before', 'absensi_audit_log');
        $this->audit_schema_checked = true;
    }

    // =========================================================================
    // CONFIGURATION METHODS
    // =========================================================================

    /**
     * Get all config as key-value array
     */
    public function getAllConfig()
    {
        $result = $this->db->get('absensi_config')->result();
        $config = [];
        foreach ($result as $row) {
            $config[$row->config_key] = $this->parseConfigValue($row->config_value, $row->config_type);
        }
        return $config;
    }

    /**
     * Get single config value
     * Supports both: getConfigValue($key, $default) and getConfigValue($key, $config_array)
     */
    public function getConfigValue($key, $config_or_default = null)
    {
        // If second param is an array, extract from it
        if (is_array($config_or_default)) {
            return isset($config_or_default[$key]) ? $config_or_default[$key] : null;
        }
        
        // Original behavior - query from database
        $row = $this->db->where('config_key', $key)->get('absensi_config')->row();
        if (!$row) return $config_or_default; // $config_or_default is the default value
        return $this->parseConfigValue($row->config_value, $row->config_type);
    }

    /**
     * Update config value
     */
    public function updateConfig($key, $value)
    {
        $this->db->where('config_key', $key);
        return $this->db->update('absensi_config', [
            'config_value' => is_array($value) ? json_encode($value) : $value,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Batch update multiple configs
     */
    public function updateConfigBatch($configs)
    {
        $this->db->trans_start();
        foreach ($configs as $key => $value) {
            $this->updateConfig($key, $value);
        }
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Check if attendance method is enabled
     * Supports both single parameter and config array parameter
     */
    public function isMethodEnabled($method_or_key, $config = null)
    {
        // If config is provided, extract from key directly
        if ($config !== null) {
            $key = $method_or_key;
            return !empty($config[$key]);
        }
        
        // Original behavior
        $key = 'enable_' . strtolower($method_or_key);
        return (bool) $this->getConfigValue($key, false);
    }

    /**
     * Get config as associative array (alias for Controller compatibility)
     */
    public function getConfig()
    {
        return $this->getAllConfig();
    }

    public function getAllGroupConfig()
    {
        if (!$this->db->table_exists('absensi_group_config')) {
            return [];
        }
        return $this->db->get('absensi_group_config')->result();
    }

    public function getGroupConfigsWithGroupName()
    {
        if (!$this->db->table_exists('absensi_group_config')) {
            return [];
        }

        return $this->db->select('gc.*, g.name as group_name')
            ->from('absensi_group_config gc')
            ->join('groups g', 'gc.id_group = g.id', 'left')
            ->order_by('g.id', 'ASC')
            ->order_by('gc.kode_tipe', 'ASC')
            ->get()
            ->result();
    }

    public function groupConfigExists($id_group, $kode_tipe, $exclude_id = null)
    {
        if (!$this->db->table_exists('absensi_group_config')) {
            return false;
        }

        $builder = $this->db->from('absensi_group_config')
            ->where('id_group', $id_group);

        $kode_tipe = $this->normalizeKodeTipe($kode_tipe);
        if ($kode_tipe === null) {
            $builder->where('kode_tipe IS NULL', null, false);
        } else {
            $builder->where('kode_tipe', $kode_tipe);
        }

        if ($exclude_id !== null) {
            $builder->where('id !=', $exclude_id);
        }

        return $builder->count_all_results() > 0;
    }

    public function createGroupConfig($data)
    {
        if (!$this->db->table_exists('absensi_group_config')) {
            return false;
        }
        return $this->db->insert('absensi_group_config', $data);
    }

    public function updateGroupConfig($id, $data)
    {
        if (!$this->db->table_exists('absensi_group_config')) {
            return false;
        }
        $this->db->where('id', $id);
        return $this->db->update('absensi_group_config', $data);
    }

    public function deleteGroupConfig($id)
    {
        if (!$this->db->table_exists('absensi_group_config')) {
            return false;
        }
        $this->db->where('id', $id);
        return $this->db->delete('absensi_group_config');
    }

    private function normalizeKodeTipe($kode_tipe)
    {
        if ($kode_tipe === null) {
            return null;
        }

        $kode_tipe = strtoupper(trim((string) $kode_tipe));
        return $kode_tipe === '' ? null : $kode_tipe;
    }

    public function getAbsensiConfigForUser($id_user)
    {
        if (!$this->db->table_exists('absensi_group_config')) {
            return $this->getDefaultUserConfig();
        }

        $user_group = $this->getUserPrimaryGroup($id_user);
        if (!$user_group) {
            return $this->getDefaultUserConfig();
        }

        $tipe_karyawan = null;
        if ($user_group->name === 'karyawan') {
            $karyawan = $this->db->select('tipe_karyawan')
                ->from('master_karyawan')
                ->where('id_user', $id_user)
                ->where('is_active', 1)
                ->get()
                ->row();
            $tipe_karyawan = $karyawan ? $this->normalizeKodeTipe($karyawan->tipe_karyawan) : null;
        }

        $kode_tipe_candidates = [];
        if ($user_group->name === 'karyawan') {
            if ($tipe_karyawan !== null) {
                $kode_tipe_candidates[] = $tipe_karyawan;
            }
            $kode_tipe_candidates[] = strtoupper($user_group->name); // KARYAWAN (optional default)
        } else {
            // For non-karyawan groups, prefer kode_tipe matching group name (e.g., GURU/SISWA) as used in SQL seeds.
            $kode_tipe_candidates[] = strtoupper($user_group->name);
        }

        // Backward-compatible fallback: group default row (kode_tipe IS NULL)
        $kode_tipe_candidates[] = null;

        $config = null;
        foreach ($kode_tipe_candidates as $kode_tipe) {
            $builder = $this->db->where('id_group', $user_group->id)
                ->where('is_active', 1);

            if ($kode_tipe === null) {
                $builder->where('kode_tipe IS NULL', null, false);
            } else {
                $builder->where('kode_tipe', $kode_tipe);
            }

            $config = $builder->get('absensi_group_config')->row();
            if ($config) {
                break;
            }
        }

        if (!$config) {
            return $this->getDefaultUserConfig();
        }

        return (object) [
            'working_days' => json_decode($config->working_days, true) ?: [1,2,3,4,5],
            'id_shift_default' => $config->id_shift_default,
            'follow_academic_calendar' => (bool) $config->follow_academic_calendar,
            'holiday_group' => $config->holiday_group,
            'enable_gps' => (bool) $config->enable_gps,
            'enable_qr' => (bool) $config->enable_qr,
            'enable_manual' => (bool) $config->enable_manual,
            'require_photo' => (bool) $config->require_photo,
            'allow_bypass' => (bool) $config->allow_bypass,
            'toleransi_terlambat' => $config->toleransi_terlambat,
            'id_lokasi_default' => $config->id_lokasi_default,
            'require_checkout' => isset($config->require_checkout) ? (bool) $config->require_checkout : true,
            'enable_lembur' => isset($config->enable_lembur) ? (bool) $config->enable_lembur : false,
            'lembur_require_approval' => isset($config->lembur_require_approval) ? (bool) $config->lembur_require_approval : true,
            'group_name' => $user_group->name,
            'kode_tipe' => $config->kode_tipe
        ];
    }

    private function getUserPrimaryGroup($id_user)
    {
        return $this->db->select('g.id, g.name')
            ->from('users_groups ug')
            ->join('groups g', 'ug.group_id = g.id')
            ->where('ug.user_id', $id_user)
            ->order_by('g.id', 'ASC')
            ->limit(1)
            ->get()->row();
    }

    private function getDefaultUserConfig()
    {
        $global = $this->getAllConfig();
        return (object) [
            'working_days' => isset($global['working_days']) ? $global['working_days'] : [1,2,3,4,5],
            'id_shift_default' => null,
            'follow_academic_calendar' => false,
            'holiday_group' => 'all',
            'enable_gps' => isset($global['enable_gps']) ? $global['enable_gps'] : true,
            'enable_qr' => isset($global['enable_qr']) ? $global['enable_qr'] : true,
            'enable_manual' => isset($global['enable_manual']) ? $global['enable_manual'] : false,
            'require_photo' => isset($global['require_photo_checkin']) ? $global['require_photo_checkin'] : true,
            'allow_bypass' => isset($global['allow_bypass_request']) ? $global['allow_bypass_request'] : true,
            'toleransi_terlambat' => null,
            'id_lokasi_default' => null,
            'require_checkout' => true,
            'enable_lembur' => false,
            'lembur_require_approval' => true,
            'group_name' => 'unknown',
            'kode_tipe' => null
        ];
    }

    public function getConfigValueFromArray($key, $config, $default = null)
    {
        return isset($config[$key]) ? $config[$key] : $default;
    }

    /**
     * Parse config value based on type
     */
    private function parseConfigValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return (bool) $value;
            case 'int':
                return (int) $value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    // =========================================================================
    // LOCATION METHODS
    // =========================================================================

    /**
     * Get all active locations
     */
    public function getActiveLocations()
    {
        return $this->db->where('is_active', 1)
            ->order_by('is_default', 'DESC')
            ->order_by('nama_lokasi', 'ASC')
            ->get('absensi_lokasi')
            ->result();
    }

    /**
     * Get location by ID
     */
    public function getLokasiById($id)
    {
        return $this->db->where('id_lokasi', $id)->get('absensi_lokasi')->row();
    }

    /**
     * Get default location
     */
    public function getDefaultLocation()
    {
        return $this->db->where('is_default', 1)
            ->where('is_active', 1)
            ->get('absensi_lokasi')
            ->row();
    }

    /**
     * Create new location
     */
    public function createLokasi($data)
    {
        // If this is set as default, reset others first
        if (!empty($data['is_default'])) {
            $this->resetDefaultLocation();
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('absensi_lokasi', $data);
        return $this->db->insert_id();
    }

    /**
     * Update location
     */
    public function updateLokasi($id, $data)
    {
        // If this is set as default, reset others first
        if (!empty($data['is_default'])) {
            $this->resetDefaultLocation();
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id_lokasi', $id);
        return $this->db->update('absensi_lokasi', $data);
    }

    /**
     * Delete location (soft delete)
     */
    public function deleteLokasi($id)
    {
        $this->db->where('id_lokasi', $id);
        return $this->db->update('absensi_lokasi', ['is_active' => 0]);
    }

    /**
     * Reset all default locations to non-default
     */
    public function resetDefaultLocation()
    {
        return $this->db->update('absensi_lokasi', ['is_default' => 0]);
    }

    /**
     * Set a location as default
     */
    public function setDefaultLocation($id)
    {
        $this->resetDefaultLocation();
        $this->db->where('id_lokasi', $id);
        return $this->db->update('absensi_lokasi', ['is_default' => 1]);
    }

    /**
     * Get locations for DataTables
     */
    public function getDataTableLokasi()
    {
        $this->db->select('*');
        $this->db->from('absensi_lokasi');
        $this->db->where('is_active', 1);
        $this->db->order_by('is_default', 'DESC');
        $this->db->order_by('nama_lokasi', 'ASC');
        return $this->db->get()->result();
    }

    // =========================================================================
    // SHIFT METHODS
    // =========================================================================

    /**
     * Get all active shifts
     */
    public function getActiveShifts()
    {
        return $this->db->where('is_active', 1)
            ->order_by('jam_masuk', 'ASC')
            ->get('master_shift')
            ->result();
    }

    /**
     * Get shift by ID
     */
    public function getShiftById($id)
    {
        return $this->db->where('id_shift', $id)->get('master_shift')->row();
    }

    /**
     * Create new shift
     */
    public function createShift($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('master_shift', $data);
        return $this->db->insert_id();
    }

    /**
     * Update shift
     */
    public function updateShift($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id_shift', $id);
        return $this->db->update('master_shift', $data);
    }

    /**
     * Delete shift (soft delete)
     */
    public function deleteShift($id)
    {
        $this->db->where('id_shift', $id);
        return $this->db->update('master_shift', ['is_active' => 0]);
    }

    /**
     * Get user's assigned shift for a date
     * 
     * DEPRECATED: This method delegates to Shift_model::getUserShift() 
     * to maintain a single source of truth for shift resolution.
     * 
     * Priority (handled by Shift_model):
     * 1. Daily roster (shift_jadwal) - specific date override
     * 2. Fixed schedule (pegawai_shift) - permanent assignment
     * 3. Group default (absensi_group_config.id_shift_default) - fallback
     * 
     * @param int $id_user User ID
     * @param string $date Date in Y-m-d format
     * @return object|null Shift data or null if no shift assigned
     */
    public function getUserShift($id_user, $date)
    {
        // Delegate to Shift_model to maintain single source of truth
        $this->load->model('Shift_model', 'shift_model');
        return $this->shift_model->getUserShift($id_user, $date);
    }

    // =========================================================================
    // ATTENDANCE LOG METHODS
    // =========================================================================

    /**
     * Get today's log for a user
     */
    public function getTodayLog($id_user, $date)
    {
        return $this->db->where('id_user', $id_user)
            ->where('tanggal', $date)
            ->get('absensi_logs')
            ->row();
    }

    /**
     * Get an open (not yet checked-out) attendance log for user.
     * By default searches all dates; pass $days_back to limit the window (e.g., 1 for today+yesterday).
     */
    public function getOpenLog($id_user, $reference_date = null, $days_back = null)
    {
        $reference_date = $reference_date ?: date('Y-m-d');

        $builder = $this->db->where('id_user', $id_user)
            ->where('jam_masuk IS NOT NULL', null, false)
            ->where('jam_pulang IS NULL', null, false);

        if ($days_back !== null) {
            $start_date = date('Y-m-d', strtotime("-{$days_back} day", strtotime($reference_date)));
            $builder->where('tanggal >=', $start_date)
                ->where('tanggal <=', $reference_date);
        }

        return $builder
            ->order_by('tanggal', 'DESC')
            ->order_by('id_log', 'DESC')
            ->limit(1)
            ->get('absensi_logs')
            ->row();
    }

    public function getOpenAttendanceUsers($date, $group_names = ['siswa'])
    {
        $builder = $this->db->distinct()
            ->select('al.id_log, al.id_user, al.jam_masuk, u.first_name, u.last_name')
            ->from('absensi_logs al')
            ->join('users u', 'al.id_user = u.id')
            ->where('al.tanggal', $date)
            ->where('al.jam_masuk IS NOT NULL', null, false)
            ->where('al.jam_pulang IS NULL', null, false)
            ->where_in('al.status_kehadiran', ['Hadir', 'Terlambat']);

        if ($group_names !== null) {
            $builder->join('users_groups ug', 'u.id = ug.user_id')
                ->join('groups g', 'ug.group_id = g.id');
            if (!empty($group_names)) {
                $builder->where_in('g.name', $group_names);
            }
        }

        return $builder
            ->order_by('u.first_name', 'ASC')
            ->get()
            ->result();
    }

    /**
     * Create new attendance log entry
     * Used for both clock-in and manual entry
     * 
     * @param array $data Log data including id_user, tanggal, jam_masuk, etc.
     * @return int|false Insert ID on success, false on failure
     */
    public function createLog($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('absensi_logs', $data);
        return $this->db->insert_id();
    }

    /**
     * Update attendance log entry
     * Used for both clock-out and manual edit
     * 
     * @param int $id_log The log ID to update
     * @param array $data Updated data
     * @return bool Success status
     */
    public function updateLog($id_log, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id_log', $id_log);
        return $this->db->update('absensi_logs', $data);
    }

    /**
     * Get log by ID with relations
     */
    public function getLogById($id)
    {
        return $this->db->select('al.*, s.nama_shift, l.nama_lokasi')
            ->from('absensi_logs al')
            ->join('master_shift s', 'al.id_shift = s.id_shift', 'left')
            ->join('absensi_lokasi l', 'al.id_lokasi = l.id_lokasi', 'left')
            ->where('al.id_log', $id)
            ->get()
            ->row();
    }

    /**
     * Calculate late minutes
     */
    public function calculateLateMinutes($jam_masuk, $shift_jam_masuk, $toleransi = 0)
    {
        $masuk = strtotime($jam_masuk);
        $batas = strtotime($shift_jam_masuk) + ($toleransi * 60);

        if ($masuk > $batas) {
            return round(($masuk - strtotime($shift_jam_masuk)) / 60);
        }
        return 0;
    }

    /**
     * Calculate early leave minutes
     */
    public function calculateEarlyLeaveMinutes($jam_pulang, $shift_jam_pulang)
    {
        $pulang = strtotime($jam_pulang);
        $batas = strtotime($shift_jam_pulang);

        if ($pulang < $batas) {
            return round(($batas - $pulang) / 60);
        }
        return 0;
    }

    /**
     * Determine attendance status based on conditions
     */
    public function determineStatus($terlambat_menit, $pulang_awal_menit)
    {
        $is_late = $terlambat_menit > 0;
        $is_early = $pulang_awal_menit > 0;

        if ($is_late && $is_early) {
            return 'Terlambat + Pulang Awal';
        } elseif ($is_late) {
            return 'Terlambat';
        } elseif ($is_early) {
            return 'Pulang Awal';
        }
        return 'Hadir';
    }

    // =========================================================================
    // QR TOKEN METHODS
    // =========================================================================

    /**
     * Create new QR token
     */
    public function createQrToken($data)
    {
        // Generate unique token only when not provided
        if (empty($data['token_code'])) {
            $data['token_code'] = $this->generateUniqueToken();
        }
        $data['created_at'] = date('Y-m-d H:i:s');

        $this->db->insert('absensi_qr_token', $data);
        return $this->db->insert_id() ? $data['token_code'] : false;
    }

    /**
     * Generate unique token string
     */
    private function generateUniqueToken()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Get active tokens for a date
     */
    public function getActiveTokens($date = null)
    {
        $date = $date ?: date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        return $this->db->select('qt.*, l.nama_lokasi, s.nama_shift, u.username as created_by_name')
            ->from('absensi_qr_token qt')
            ->join('absensi_lokasi l', 'qt.id_lokasi = l.id_lokasi', 'left')
            ->join('master_shift s', 'qt.id_shift = s.id_shift', 'left')
            ->join('users u', 'qt.created_by = u.id', 'left')
            ->where('qt.tanggal', $date)
            ->where('qt.is_active', 1)
            ->where('qt.valid_until >=', $now)
            ->order_by('qt.valid_until', 'ASC')
            ->get()
            ->result();
    }

    /**
     * Atomically claim and increment QR token usage count.
     * Returns true only if the token was successfully claimed (prevents race condition).
     * 
     * @param string|int $token_id Token ID or token_code
     * @return bool True if claimed successfully, false if max usage exceeded or token not found
     */
    public function incrementQrUsage($token_id)
    {
        // Use atomic conditional update to prevent race condition
        // Only increment if max_usage is null OR used_count < max_usage
        $this->db->set('used_count', 'used_count + 1', false);
        
        if (is_numeric($token_id)) {
            $this->db->where('id_token', (int) $token_id);
        } else {
            $this->db->where('token_code', $token_id);
        }
        
        // Atomic condition: only update if we haven't exceeded max usage
        $this->db->where('(max_usage IS NULL OR used_count < max_usage)', null, false);
        $this->db->update('absensi_qr_token');
        
        // Return true only if exactly one row was updated (claim successful)
        return $this->db->affected_rows() === 1;
    }
    
    /**
     * Validate and atomically claim QR token in one operation.
     * This combines validation + claiming to prevent race conditions.
     * 
     * @param string $token_code The QR token code
     * @param string $date The date to validate for
     * @param string $type 'checkin' or 'checkout'
     * @return array ['valid' => bool, 'message' => string, 'id_lokasi' => int|null, 'token' => object|null]
     */
    public function validateAndClaimQrToken($token_code, $date = null, $type = 'checkin')
    {
        $now = date('Y-m-d H:i:s');
        $today = $date ?: date('Y-m-d');

        // First, get token info for validation
        $token = $this->db->where('token_code', $token_code)
            ->where('is_active', 1)
            ->where('valid_from <=', $now)
            ->where('valid_until >=', $now)
            ->where('tanggal', $today)
            ->group_start()
                ->where('token_type', 'both')
                ->or_where('token_type', $type)
            ->group_end()
            ->get('absensi_qr_token')
            ->row();

        if (!$token) {
            return ['valid' => false, 'message' => 'QR Code tidak valid atau sudah kadaluarsa.'];
        }

        // Atomically claim the token (increment usage with condition)
        $claimed = $this->incrementQrUsage($token_code);
        
        if (!$claimed) {
            return ['valid' => false, 'message' => 'QR Code sudah mencapai batas penggunaan maksimal.'];
        }

        return [
            'valid' => true, 
            'message' => 'QR Code valid.',
            'id_lokasi' => $token->id_lokasi,
            'token' => $token
        ];
    }

    /**
     * Deactivate expired tokens (cleanup)
     */
    public function deactivateExpiredTokens()
    {
        $this->db->where('valid_until <', date('Y-m-d H:i:s'));
        $this->db->where('is_active', 1);
        return $this->db->update('absensi_qr_token', ['is_active' => 0]);
    }

    // =========================================================================
    // BYPASS REQUEST METHODS
    // =========================================================================

    /**
     * Create bypass request
     */
    public function createBypassRequest($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('absensi_bypass_request', $data);
        return $this->db->insert_id();
    }

    /**
     * Get bypass request by ID
     */
    public function getBypassById($id)
    {
        return $this->db->select('br.*, u.username, u.first_name, u.last_name')
            ->from('absensi_bypass_request br')
            ->join('users u', 'br.id_user = u.id', 'left')
            ->where('br.id_bypass', $id)
            ->get()
            ->row();
    }

    /**
     * Get approved bypass for user on date
     */
    public function getApprovedBypass($id_user, $date, $type = 'both')
    {
        $this->db->where('id_user', $id_user);
        $this->db->where('tanggal', $date);
        $this->db->where('status', 'approved');
        $this->db->group_start()
            ->where('tipe_bypass', 'both')
            ->or_where('tipe_bypass', $type)
        ->group_end();
        return $this->db->get('absensi_bypass_request')->row();
    }

    /**
     * Check if user has approved bypass
     */
    public function hasApprovedBypass($id_user, $date, $type = 'both')
    {
        return $this->getApprovedBypass($id_user, $date, $type) !== null;
    }

    /**
     * Update bypass status
     */
    public function updateBypassStatus($id, $status, $admin_id, $notes = null)
    {
        $data = [
            'status' => $status,
            'approved_by' => $admin_id,
            'approved_at' => date('Y-m-d H:i:s'),
            'catatan_admin' => $notes,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->where('id_bypass', $id);
        return $this->db->update('absensi_bypass_request', $data);
    }

    /**
     * Mark bypass as used
     */
    public function markBypassUsed($id)
    {
        $this->db->where('id_bypass', $id);
        return $this->db->update('absensi_bypass_request', [
            'status' => 'used',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Atomically claim bypass - only succeeds if bypass is currently 'approved'.
     * This prevents race condition where two requests try to use the same bypass.
     * 
     * @param int $id_bypass The bypass ID to claim
     * @return bool True if successfully claimed, false if already used or not approved
     */
    public function claimBypass($id_bypass)
    {
        $this->db->where('id_bypass', $id_bypass);
        $this->db->where('status', 'approved'); // Only claim if still approved
        $this->db->update('absensi_bypass_request', [
            'status' => 'used',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Return true only if exactly one row was updated
        return $this->db->affected_rows() === 1;
    }

    // =========================================================================
    // ATTENDANCE VALIDATION METHODS (Business Logic)
    // =========================================================================

    /**
     * Validate attendance method (GPS, QR, Manual, Bypass)
     * 
     * Centralized validation logic for check-in and check-out.
     * Moved from Controller to Model for proper MVC separation.
     * 
     * @param string $method 'GPS', 'QR', 'Manual', or empty
     * @param float|null $lat Latitude for GPS method
     * @param float|null $lng Longitude for GPS method
     * @param string|null $qr_token QR token code
     * @param int|null $id_lokasi Location ID (for checkout)
     * @param int $id_user User ID
     * @param string $date Date in Y-m-d format
     * @param string $type 'checkin' or 'checkout'
     * @param array|object $config User's absensi config
     * @return array ['valid' => bool, 'message' => string, 'id_lokasi' => int|null, 'bypass_id' => int|null]
     */
    public function validateAttendanceMethod($method, $lat, $lng, $qr_token, $id_lokasi, $id_user, $date, $type, $config)
    {
        // Convert config to array if object
        if (is_object($config)) {
            $config = (array) $config;
        }
        
        $result = ['valid' => false, 'message' => '', 'id_lokasi' => $id_lokasi, 'bypass_id' => null];
        
        $gps_enabled = !empty($config['enable_gps']);
        $qr_enabled = !empty($config['enable_qr']);
        $manual_enabled = !empty($config['enable_manual']);
        $allow_bypass = !empty($config['allow_bypass']);
        
        // If both GPS and QR are disabled, allow manual automatically
        if (!$gps_enabled && !$qr_enabled) {
            $result['valid'] = true;
            $result['id_lokasi'] = null;
            return $result;
        }
        
        if ($method === 'GPS') {
            if (!$gps_enabled) {
                $result['message'] = 'Metode GPS tidak diaktifkan.';
                return $result;
            }

            if ($lat === null || $lng === null || $lat === '' || $lng === '' || !is_numeric($lat) || !is_numeric($lng)) {
                $result['message'] = 'Koordinat GPS tidak valid.';
                return $result;
            }
            
            // Check for approved bypass first
            $bypass = $allow_bypass ? $this->getApprovedBypass($id_user, $date, $type) : null;
            if ($bypass) {
                $result['valid'] = true;
                $result['bypass_id'] = $bypass->id_bypass;
                return $result;
            }
            
            // Validate location against registered locations
            $locations = $this->getActiveLocations();
            if (empty($locations)) {
                $result['message'] = 'Lokasi absensi belum dikonfigurasi.';
                return $result;
            }

            $nearest = null;
            $min_distance = PHP_INT_MAX;
            
            // Load helper for distance calculation
            $this->load->helper('absen');
            
            foreach ($locations as $loc) {
                $distance = calculate_distance($lat, $lng, $loc->latitude, $loc->longitude);
                if ($distance <= $loc->radius_meter && $distance < $min_distance) {
                    $min_distance = $distance;
                    $nearest = $loc;
                }
            }
            
            if (!$nearest) {
                $default_loc = $this->getDefaultLocation();
                if (!$default_loc) {
                    $result['message'] = 'Lokasi default absensi belum diset.';
                    return $result;
                }
                $distance = calculate_distance($lat, $lng, $default_loc->latitude, $default_loc->longitude);
                $result['message'] = 'Anda berada di luar area (' . round($distance) . 'm). Radius maksimal: ' . $default_loc->radius_meter . 'm.';
                return $result;
            }
            
            $result['valid'] = true;
            $result['id_lokasi'] = $nearest->id_lokasi;
            
        } elseif ($method === 'QR') {
            if (!$qr_enabled) {
                $result['message'] = 'Metode QR tidak diaktifkan.';
                return $result;
            }
            
            // Use atomic validate-and-claim to prevent max-usage race condition
            $qr = $this->validateAndClaimQrToken($qr_token, $date, $type);
            if (!$qr['valid']) {
                $result['message'] = $qr['message'];
                return $result;
            }
            
            $result['valid'] = true;
            $result['id_lokasi'] = $qr['id_lokasi'];
            $result['qr_claimed'] = true;
            
        } elseif ($method === 'Manual' || empty($method)) {
            // Manual is allowed when explicitly enabled or when GPS/QR are both disabled
            if (($gps_enabled || $qr_enabled) && !$manual_enabled) {
                $result['message'] = 'Silakan gunakan GPS atau QR untuk absensi.';
                return $result;
            }
            $result['valid'] = true;
            // Security: Force id_lokasi to null or use configured default for Manual method
            $result['id_lokasi'] = isset($config['id_lokasi_default']) ? $config['id_lokasi_default'] : null;
            
        } else {
            $result['message'] = 'Metode absensi tidak valid.';
        }
        
        return $result;
    }

    /**
     * Calculate late status based on check-in time vs shift time
     * 
     * Handles overnight shifts and tolerance properly.
     * 
     * @param string $current_time Current time (H:i:s)
     * @param string $shift_jam_masuk Shift start time (H:i:s)
     * @param int $toleransi Tolerance in minutes
     * @param bool $is_overnight Whether shift crosses midnight
     * @return array ['status' => string, 'menit' => int]
     */
    public function calculateLateStatusWithOvernight($current_time, $shift_jam_masuk, $toleransi, $is_overnight = false)
    {
        $now = strtotime($current_time);
        $shift_time = strtotime($shift_jam_masuk);
        $batas = $shift_time + ($toleransi * 60);
        
        if ($is_overnight) {
            // For overnight shifts, adjust times based on noon boundary
            if ($now < 43200) { // Before noon (12:00)
                $now += 86400; // Add 24 hours
            }
            if ($shift_time >= 43200) { // Shift starts after noon
                // Keep as-is
            } else {
                $shift_time += 86400;
                $batas = $shift_time + ($toleransi * 60);
            }
        }
        
        if ($now > $batas) {
            $late_seconds = $now - $shift_time;
            return [
                'status' => 'Terlambat',
                'menit' => max(0, round($late_seconds / 60))
            ];
        }
        
        return ['status' => 'Hadir', 'menit' => 0];
    }

    /**
     * Calculate early leave status based on check-out time vs shift time
     * 
     * Handles overnight shifts properly.
     * 
     * @param string $current_time Current time (H:i:s)
     * @param string $shift_jam_pulang Shift end time (H:i:s)
     * @param bool $is_overnight Whether shift crosses midnight
     * @return array ['is_early' => bool, 'menit' => int]
     */
    public function calculateEarlyLeaveWithOvernight($current_time, $shift_jam_pulang, $is_overnight = false)
    {
        $now = strtotime($current_time);
        $shift_time = strtotime($shift_jam_pulang);
        
        if ($is_overnight) {
            if ($shift_time < 43200) { // End time before noon = next day
                $shift_time += 86400;
            }
            if ($now < 43200) { // Current time before noon
                $now += 86400;
            }
        }
        
        if ($now < $shift_time) {
            return [
                'is_early' => true,
                'menit' => round(($shift_time - $now) / 60)
            ];
        }
        
        return ['is_early' => false, 'menit' => 0];
    }

    /**
     * Get user's bypass request history
     */
    public function getUserBypassRequests($id_user, $limit = 20)
    {
        return $this->db->where('id_user', $id_user)
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get('absensi_bypass_request')
            ->result();
    }

    /**
     * Count user's bypass requests this month
     * Uses date range instead of MONTH()/YEAR() for better index usage
     */
    public function countUserBypassThisMonth($id_user)
    {
        // Calculate date range for current month
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        
        return $this->db->where('id_user', $id_user)
            ->where('tanggal >=', $start_date)
            ->where('tanggal <=', $end_date)
            ->where_in('status', ['approved', 'used', 'pending'])
            ->count_all_results('absensi_bypass_request');
    }

    /**
     * Get bypass requests for DataTables
     */
    public function getDataTableBypass($status = null)
    {
        $this->db->select('br.*, u.username, u.first_name, u.last_name, 
                          COALESCE(g.nama_guru, k.nama_karyawan, u.username) as nama_lengkap,
                          au.username as approved_by_name');
        $this->db->from('absensi_bypass_request br');
        $this->db->join('users u', 'br.id_user = u.id', 'left');
        $this->db->join('master_guru g', 'u.id = g.id_user', 'left');
        $this->db->join('master_karyawan k', 'u.id = k.id_user', 'left');
        $this->db->join('users au', 'br.approved_by = au.id', 'left');

        if ($status) {
            $this->db->where('br.status', $status);
        }

        $this->db->order_by('br.created_at', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Count pending bypass requests
     */
    public function countPendingBypass()
    {
        return $this->db->where('status', 'pending')
            ->count_all_results('absensi_bypass_request');
    }

    // =========================================================================
    // HISTORY & REKAP METHODS
    // =========================================================================

    /**
     * Get user's attendance history
     * Uses date range instead of MONTH()/YEAR() for better index usage
     */
    public function getHistory($id_user, $month, $year)
    {
        // Calculate date range for the month
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date)); // Last day of month
        
        return $this->db->select('a.*, s.nama_shift, s.jam_masuk as shift_masuk, 
                                  s.jam_pulang as shift_pulang, l.nama_lokasi')
            ->from('absensi_logs a')
            ->join('master_shift s', 'a.id_shift = s.id_shift', 'left')
            ->join('absensi_lokasi l', 'a.id_lokasi = l.id_lokasi', 'left')
            ->where('a.id_user', $id_user)
            ->where('a.tanggal >=', $start_date)
            ->where('a.tanggal <=', $end_date)
            ->order_by('a.tanggal', 'DESC')
            ->get()
            ->result();
    }

    /**
     * Get user's monthly recap
     * Uses date range instead of MONTH()/YEAR() for better index usage
     */
    public function getRekapBulanan($id_user, $month, $year)
    {
        // Calculate date range for the month
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date)); // Last day of month
        
        return $this->db->select("
            COUNT(*) as total_hari_kerja,
            SUM(CASE WHEN status_kehadiran IN ('Hadir', 'Terlambat', 'Pulang Awal', 'Terlambat + Pulang Awal') THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN status_kehadiran = 'Terlambat' THEN 1 ELSE 0 END) as terlambat,
            SUM(CASE WHEN status_kehadiran = 'Pulang Awal' THEN 1 ELSE 0 END) as pulang_awal,
            SUM(CASE WHEN status_kehadiran = 'Terlambat + Pulang Awal' THEN 1 ELSE 0 END) as terlambat_pulang_awal,
            SUM(CASE WHEN status_kehadiran = 'Izin' THEN 1 ELSE 0 END) as izin,
            SUM(CASE WHEN status_kehadiran = 'Sakit' THEN 1 ELSE 0 END) as sakit,
            SUM(CASE WHEN status_kehadiran = 'Cuti' THEN 1 ELSE 0 END) as cuti,
            SUM(CASE WHEN status_kehadiran = 'Dinas Luar' THEN 1 ELSE 0 END) as dinas,
            SUM(CASE WHEN status_kehadiran = 'Alpha' THEN 1 ELSE 0 END) as alpha,
            SUM(terlambat_menit) as total_terlambat_menit,
            SUM(pulang_awal_menit) as total_pulang_awal_menit
        ")
            ->from('absensi_logs')
            ->where('id_user', $id_user)
            ->where('tanggal >=', $start_date)
            ->where('tanggal <=', $end_date)
            ->get()
            ->row();
    }

    /**
     * Get daily recap for all users
     */
    public function getRekapHarian($date)
    {
        return $this->db->select('al.*, u.username, 
                                  COALESCE(g.nama_guru, k.nama_karyawan, ms.nama, u.first_name) as nama_lengkap,
                                  s.nama_shift, l.nama_lokasi')
            ->from('absensi_logs al')
            ->join('users u', 'al.id_user = u.id', 'left')
            ->join('master_guru g', 'u.id = g.id_user', 'left')
            ->join('master_karyawan k', 'u.id = k.id_user', 'left')
            ->join('master_siswa ms', 'u.username = ms.username', 'left')
            ->join('master_shift s', 'al.id_shift = s.id_shift', 'left')
            ->join('absensi_lokasi l', 'al.id_lokasi = l.id_lokasi', 'left')
            ->where('al.tanggal', $date)
            ->order_by('al.jam_masuk', 'ASC')
            ->get()
            ->result();
    }

    /**
     * Get monthly recap for all users
     * Uses date range instead of MONTH()/YEAR() for better index usage
     */
    public function getRekapBulananAll($month, $year)
    {
        // Calculate date range for the month
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date)); // Last day of month
        
        return $this->db->select("
            al.id_user,
            COALESCE(g.nama_guru, k.nama_karyawan, ms.nama, u.first_name) as nama_lengkap,
            COALESCE(g.nip, k.nip, ms.nis, '') as nip,
            COUNT(*) as total_hari,
            SUM(CASE WHEN al.status_kehadiran IN ('Hadir', 'Terlambat', 'Pulang Awal', 'Terlambat + Pulang Awal') THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN al.status_kehadiran = 'Terlambat' THEN 1 ELSE 0 END) as terlambat,
            SUM(CASE WHEN al.status_kehadiran = 'Alpha' THEN 1 ELSE 0 END) as alpha,
            SUM(CASE WHEN al.status_kehadiran IN ('Izin', 'Sakit', 'Cuti', 'Dinas Luar') THEN 1 ELSE 0 END) as izin_total,
            SUM(al.terlambat_menit) as total_menit_terlambat
        ")
            ->from('absensi_logs al')
            ->join('users u', 'al.id_user = u.id', 'left')
            ->join('master_guru g', 'u.id = g.id_user', 'left')
            ->join('master_karyawan k', 'u.id = k.id_user', 'left')
            ->join('master_siswa ms', 'u.username = ms.username', 'left')
            ->where('al.tanggal >=', $start_date)
            ->where('al.tanggal <=', $end_date)
            ->group_by('al.id_user')
            ->order_by('nama_lengkap', 'ASC')
            ->get()
            ->result();
    }

    // =========================================================================
    // DASHBOARD & STATISTICS METHODS
    // =========================================================================

    /**
     * Get dashboard statistics - separated by role groups
     * Pegawai = guru + karyawan (staff)
     * Siswa = students (separate tracking)
     */
    public function getDashboardStats($date = null)
    {
        $date = $date ?: date('Y-m-d');

        $stats = new stdClass();

        // Count by role groups
        $stats->total_guru = $this->countUsersByGroup('guru');
        $stats->total_karyawan = $this->countUsersByGroup('karyawan');
        $stats->total_siswa = $this->countUsersByGroup('siswa');
        $stats->total_pegawai = $stats->total_guru + $stats->total_karyawan;

        // Pegawai (guru + karyawan) stats
        $stats->pegawai = $this->getStatsForGroups($date, ['guru', 'karyawan']);
        
        // Siswa stats (separate)
        $stats->siswa = $this->getStatsForGroups($date, ['siswa']);

        // Legacy compatibility - overall stats
        $stats->sudah_masuk = $stats->pegawai->hadir + $stats->siswa->hadir;
        $stats->terlambat = $stats->pegawai->terlambat + $stats->siswa->terlambat;
        $stats->belum_masuk = $stats->pegawai->belum_masuk + $stats->siswa->belum_masuk;
        $stats->izin_sakit = $stats->pegawai->izin + $stats->siswa->izin;

        // Pending requests
        $stats->pending_bypass = $this->countPendingBypass();
        $stats->pending_pengajuan = $this->countPendingPengajuan();

        return $stats;
    }

    /**
     * Get attendance stats for specific user groups
     */
    private function getStatsForGroups($date, $groups)
    {
        $result = new stdClass();
        
        // Get user IDs in these groups
        $users = $this->db->select('u.id')
            ->from('users u')
            ->join('users_groups ug', 'u.id = ug.user_id')
            ->join('groups gr', 'ug.group_id = gr.id')
            ->where_in('gr.name', $groups)
            ->where('u.active', 1)
            ->get()
            ->result();
        
        $user_ids = array_column($users, 'id');
        $total = count($user_ids);
        
        $result->total = $total;
        
        if (empty($user_ids)) {
            $result->hadir = 0;
            $result->terlambat = 0;
            $result->izin = 0;
            $result->belum_masuk = 0;
            return $result;
        }
        
        // Hadir (checked in)
        $result->hadir = $this->db->where('tanggal', $date)
            ->where('jam_masuk IS NOT NULL', null, false)
            ->where_in('id_user', $user_ids)
            ->count_all_results('absensi_logs');
        
        // Terlambat
        $result->terlambat = $this->db->where('tanggal', $date)
            ->where_in('status_kehadiran', ['Terlambat', 'Terlambat + Pulang Awal'])
            ->where_in('id_user', $user_ids)
            ->count_all_results('absensi_logs');
        
        // Izin/Sakit/Cuti
        $result->izin = $this->db->where('tanggal', $date)
            ->where_in('status_kehadiran', ['Izin', 'Sakit', 'Cuti', 'Dinas Luar'])
            ->where_in('id_user', $user_ids)
            ->count_all_results('absensi_logs');
        
        // Belum masuk
        $result->belum_masuk = $total - $result->hadir - $result->izin;
        if ($result->belum_masuk < 0) $result->belum_masuk = 0;
        
        return $result;
    }

    /**
     * Count users by group name
     */
    public function countUsersByGroup($group_name)
    {
        return $this->db->from('users u')
            ->join('users_groups ug', 'u.id = ug.user_id')
            ->join('groups gr', 'ug.group_id = gr.id')
            ->where('gr.name', $group_name)
            ->where('u.active', 1)
            ->count_all_results();
    }

    /**
     * Get recent attendance logs
     */
    public function getRecentLogs($limit = 10)
    {
        return $this->db->select('al.*, u.username,
                                  COALESCE(g.nama_guru, k.nama_karyawan, ms.nama, u.first_name) as nama_lengkap,
                                  s.nama_shift')
            ->from('absensi_logs al')
            ->join('users u', 'al.id_user = u.id', 'left')
            ->join('master_guru g', 'u.id = g.id_user', 'left')
            ->join('master_karyawan k', 'u.id = k.id_user', 'left')
            ->join('master_siswa ms', 'u.username = ms.username', 'left')
            ->join('master_shift s', 'al.id_shift = s.id_shift', 'left')
            ->order_by('al.updated_at', 'DESC')
            ->limit($limit)
            ->get()
            ->result();
    }

    /**
     * Get late users today
     */
    public function getLateToday($date = null)
    {
        $date = $date ?: date('Y-m-d');

        return $this->db->select('al.*, u.username,
                                  COALESCE(g.nama_guru, k.nama_karyawan, ms.nama, u.first_name) as nama_lengkap,
                                  s.nama_shift, s.jam_masuk as shift_masuk')
            ->from('absensi_logs al')
            ->join('users u', 'al.id_user = u.id', 'left')
            ->join('master_guru g', 'u.id = g.id_user', 'left')
            ->join('master_karyawan k', 'u.id = k.id_user', 'left')
            ->join('master_siswa ms', 'u.username = ms.username', 'left')
            ->join('master_shift s', 'al.id_shift = s.id_shift', 'left')
            ->where('al.tanggal', $date)
            ->where('al.terlambat_menit >', 0)
            ->order_by('al.terlambat_menit', 'DESC')
            ->get()
            ->result();
    }

    /**
     * Get users not checked in today
     */
    public function getNotCheckedIn($date = null)
    {
        $date = $date ?: date('Y-m-d');

        // Get all users who should attend but haven't
        $subquery = $this->db->select('id_user')
            ->from('absensi_logs')
            ->where('tanggal', $date)
            ->get_compiled_select();

        return $this->db->select('u.id, u.username,
                                  COALESCE(g.nama_guru, k.nama_karyawan, ms.nama, u.first_name) as nama_lengkap,
                                  s.nama_shift, s.jam_masuk')
            ->from('users u')
            ->join('users_groups ug', 'u.id = ug.user_id')
            ->join('groups gr', 'ug.group_id = gr.id')
            ->join('master_guru g', 'u.id = g.id_user', 'left')
            ->join('master_karyawan k', 'u.id = k.id_user', 'left')
            ->join('master_siswa ms', 'u.username = ms.username', 'left')
            ->join('pegawai_shift ps', 'u.id = ps.id_user', 'left')
            ->join('master_shift s', 'ps.id_shift_fixed = s.id_shift', 'left')
            ->where("u.id NOT IN ({$subquery})", null, false)
            ->where_in('gr.name', ['guru', 'karyawan', 'siswa'])
            ->where('u.active', 1)
            ->get()
            ->result();
    }

    /**
     * Get statistics by status for period
     * Supports both (month, year) and (start_date, end_date) parameters
     */
    public function getStatistikByStatus($param1, $param2)
    {
        // Detect if params are month/year or date range
        if (strlen($param1) <= 2 && strlen($param2) == 4) {
            // Month and Year format
            $month = $param1;
            $year = $param2;
            $start_date = "$year-$month-01";
            $end_date = date('Y-m-t', strtotime($start_date));
        } else {
            // Date range format
            $start_date = $param1;
            $end_date = $param2;
        }

        return $this->db->select("
            status_kehadiran,
            COUNT(*) as jumlah
        ")
            ->from('absensi_logs')
            ->where('tanggal >=', $start_date)
            ->where('tanggal <=', $end_date)
            ->group_by('status_kehadiran')
            ->get()
            ->result();
    }

    /**
     * Get daily attendance statistics
     * Supports both (month, year) and (start_date, end_date) parameters
     */
    public function getStatistikDaily($param1, $param2)
    {
        // Detect if params are month/year or date range
        if (strlen($param1) <= 2 && strlen($param2) == 4) {
            // Month and Year format
            $month = $param1;
            $year = $param2;
            $start_date = "$year-$month-01";
            $end_date = date('Y-m-t', strtotime($start_date));
        } else {
            // Date range format
            $start_date = $param1;
            $end_date = $param2;
        }

        return $this->db->select("
            tanggal,
            COUNT(*) as total,
            SUM(CASE WHEN status_kehadiran IN ('Hadir', 'Terlambat', 'Pulang Awal', 'Terlambat + Pulang Awal') THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN status_kehadiran = 'Terlambat' THEN 1 ELSE 0 END) as terlambat,
            SUM(CASE WHEN status_kehadiran = 'Alpha' THEN 1 ELSE 0 END) as alpha
        ")
            ->from('absensi_logs')
            ->where('tanggal >=', $start_date)
            ->where('tanggal <=', $end_date)
            ->group_by('tanggal')
            ->order_by('tanggal', 'ASC')
            ->get()
            ->result();
    }

    /**
     * Get top late users
     * Uses date range instead of MONTH()/YEAR() for better index usage
     */
    public function getTopLate($month, $year, $limit = 10)
    {
        // Calculate date range for the month
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date)); // Last day of month
        
        return $this->db->select("
            al.id_user,
            COALESCE(g.nama_guru, k.nama_karyawan, ms.nama, u.first_name) as nama_lengkap,
            COUNT(*) as jumlah_terlambat,
            SUM(al.terlambat_menit) as total_menit
        ")
            ->from('absensi_logs al')
            ->join('users u', 'al.id_user = u.id', 'left')
            ->join('master_guru g', 'u.id = g.id_user', 'left')
            ->join('master_karyawan k', 'u.id = k.id_user', 'left')
            ->join('master_siswa ms', 'u.username = ms.username', 'left')
            ->where('al.tanggal >=', $start_date)
            ->where('al.tanggal <=', $end_date)
            ->where('al.terlambat_menit >', 0)
            ->group_by('al.id_user')
            ->order_by('jumlah_terlambat', 'DESC')
            ->limit($limit)
            ->get()
            ->result();
    }

    /**
     * Get top absent users
     * Uses date range instead of MONTH()/YEAR() for better index usage
     */
    public function getTopAbsent($month, $year, $limit = 10)
    {
        // Calculate date range for the month
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date)); // Last day of month
        
        return $this->db->select("
            al.id_user,
            COALESCE(g.nama_guru, k.nama_karyawan, ms.nama, u.first_name) as nama_lengkap,
            COUNT(*) as jumlah_alpha
        ")
            ->from('absensi_logs al')
            ->join('users u', 'al.id_user = u.id', 'left')
            ->join('master_guru g', 'u.id = g.id_user', 'left')
            ->join('master_karyawan k', 'u.id = k.id_user', 'left')
            ->join('master_siswa ms', 'u.username = ms.username', 'left')
            ->where('al.tanggal >=', $start_date)
            ->where('al.tanggal <=', $end_date)
            ->where('al.status_kehadiran', 'Alpha')
            ->group_by('al.id_user')
            ->order_by('jumlah_alpha', 'DESC')
            ->limit($limit)
            ->get()
            ->result();
    }

    // =========================================================================
    // MONITORING METHODS
    // =========================================================================

    /**
     * Get monitoring data for DataTables
     */
    public function getDataTableMonitoring($date = null, $status = null)
    {
        $date = $date ?: date('Y-m-d');

        $this->db->select('al.*, u.username,
                          COALESCE(g.nama_guru, k.nama_karyawan, ms.nama, u.first_name) as nama_lengkap,
                          COALESCE(g.nip, k.nip, ms.nis, \'\') as nip,
                          s.nama_shift, s.jam_masuk as shift_masuk, s.jam_pulang as shift_pulang,
                          l.nama_lokasi');
        $this->db->from('absensi_logs al');
        $this->db->join('users u', 'al.id_user = u.id', 'left');
        $this->db->join('master_guru g', 'u.id = g.id_user', 'left');
        $this->db->join('master_karyawan k', 'u.id = k.id_user', 'left');
        $this->db->join('master_siswa ms', 'u.username = ms.username', 'left');
        $this->db->join('master_shift s', 'al.id_shift = s.id_shift', 'left');
        $this->db->join('absensi_lokasi l', 'al.id_lokasi = l.id_lokasi', 'left');
        $this->db->where('al.tanggal', $date);

        if ($status) {
            $this->db->where('al.status_kehadiran', $status);
        }

        $this->db->order_by('al.jam_masuk', 'DESC');
        return $this->db->get()->result();
    }

    // =========================================================================
    // UTILITY METHODS
    // =========================================================================

    /**
     * Get all users who should attend (guru + karyawan + siswa)
     */
    public function getAllAttendanceUsers()
    {
        return $this->db->select('u.id, u.username, u.first_name, u.last_name,
                                  COALESCE(g.nama_guru, k.nama_karyawan, ms.nama, u.first_name) as nama_lengkap,
                                  COALESCE(g.nip, k.nip, ms.nis, \'\') as nip,
                                  gr.name as group_name')
            ->from('users u')
            ->join('users_groups ug', 'u.id = ug.user_id')
            ->join('groups gr', 'ug.group_id = gr.id')
            ->join('master_guru g', 'u.id = g.id_user', 'left')
            ->join('master_karyawan k', 'u.id = k.id_user', 'left')
            ->join('master_siswa ms', 'u.username = ms.username', 'left')
            ->where_in('gr.name', ['guru', 'karyawan', 'siswa'])
            ->where('u.active', 1)
            ->order_by('nama_lengkap', 'ASC')
            ->get()
            ->result();
    }

    /**
     * Count users who should attend
     */
    public function countAttendanceUsers()
    {
        return $this->db->from('users u')
            ->join('users_groups ug', 'u.id = ug.user_id')
            ->join('groups gr', 'ug.group_id = gr.id')
            ->where_in('gr.name', ['guru', 'karyawan', 'siswa'])
            ->where('u.active', 1)
            ->count_all_results();
    }

    /**
     * Get all karyawan with shift info
     */
    public function getAllKaryawanWithShift()
    {
        return $this->db->select('u.id, u.username,
                                  COALESCE(g.nama_guru, k.nama_karyawan, u.first_name) as nama_lengkap,
                                  COALESCE(g.nip, k.nip, \'\') as nip,
                                  gr.name as group_name,
                                  ps.tipe_shift, ps.id_shift_fixed,
                                  s.nama_shift, s.jam_masuk, s.jam_pulang')
            ->from('users u')
            ->join('users_groups ug', 'u.id = ug.user_id')
            ->join('groups gr', 'ug.group_id = gr.id')
            ->join('master_guru g', 'u.id = g.id_user', 'left')
            ->join('master_karyawan k', 'u.id = k.id_user', 'left')
            ->join('pegawai_shift ps', 'u.id = ps.id_user', 'left')
            ->join('master_shift s', 'ps.id_shift_fixed = s.id_shift', 'left')
            ->where_in('gr.name', ['guru', 'karyawan'])
            ->where('u.active', 1)
            ->order_by('nama_lengkap', 'ASC')
            ->get()
            ->result();
    }

    /**
     * Count pending pengajuan (izin/cuti)
     */
    public function countPendingPengajuan()
    {
        return $this->db->where('status', 'Pending')
            ->count_all_results('absensi_pengajuan');
    }

    public function hasApprovedLemburRequest($id_user, $date)
    {
        return $this->db->from('absensi_pengajuan')
            ->where('id_user', $id_user)
            ->where('tgl_mulai <=', $date)
            ->where('tgl_selesai >=', $date)
            ->where('tipe_pengajuan', 'Lembur')
            ->where_in('status', ['Disetujui', 'Approved'])
            ->count_all_results() > 0;
    }

    /**
     * Check if date is a holiday
     */
    public function isHoliday($date)
    {
        // Check exact date
        $count = $this->db->where('tanggal', $date)
            ->where('is_active', 1)
            ->count_all_results('master_hari_libur');

        if ($count > 0) return true;

        // Check recurring (month-day only)
        $md = date('m-d', strtotime($date));
        $count = $this->db->where("DATE_FORMAT(tanggal, '%m-%d')", $md)
            ->where('is_recurring', 1)
            ->where('is_active', 1)
            ->count_all_results('master_hari_libur');

        return $count > 0;
    }

    public function isWorkingDay($date, $id_user = null)
    {
        if ($id_user) {
            return $this->isWorkingDayForUser($date, $id_user);
        }

        if ($this->isHoliday($date)) return false;

        $working_days = $this->getConfigValue('working_days', [1, 2, 3, 4, 5]);
        $day_of_week = (int) date('N', strtotime($date));

        return in_array($day_of_week, $working_days);
    }

    public function isWorkingDayForUser($date, $id_user)
    {
        $config = $this->getAbsensiConfigForUser($id_user);
        $day_of_week = (int) date('N', strtotime($date));

        if (!in_array($day_of_week, $config->working_days)) {
            return false;
        }

        return !$this->isHolidayForUser($date, $config);
    }

    public function isHolidayForUser($date, $config)
    {
        if ($config->holiday_group === 'none') {
            return false;
        }

        if ($config->holiday_group === 'all') {
            return $this->isHoliday($date);
        }

        if ($config->holiday_group === 'essential') {
            return $this->isNationalHoliday($date);
        }

        if ($config->holiday_group === 'academic') {
            return $this->isAcademicHoliday($date);
        }

        return $this->isHoliday($date);
    }

    public function isNationalHoliday($date)
    {
        $count = $this->db->where('tanggal', $date)
            ->where('is_active', 1)
            ->where("(nama_libur LIKE '%nasional%' OR nama_libur LIKE '%kemerdekaan%' OR nama_libur LIKE '%tahun baru%' OR is_recurring = 1)", null, false)
            ->count_all_results('master_hari_libur');

        return $count > 0;
    }

    public function isAcademicHoliday($date)
    {
        return $this->isHoliday($date);
    }

    // =========================================================================
    // AUDIT LOG METHODS
    // =========================================================================

    /**
     * Create audit log entry
     * Supports both: logAudit($data_array) and logAudit($id_log, $id_user, $action, $action_by, $changes)
     */
    public function logAudit($id_log_or_data, $id_user_target = null, $action = null, $action_by = null, $changes = null)
    {
        // Check if called with array (original signature)
        if (is_array($id_log_or_data)) {
            $data = $id_log_or_data;
        } else {
            // Called with individual parameters (Controller signature)
            $data = [
                'id_log' => $id_log_or_data,
                'id_user_target' => $id_user_target,
                'action' => $action,
                'action_by' => $action_by,
                'changes' => is_array($changes) ? json_encode($changes) : $changes
            ];
        }

        $data['ip_address'] = $this->input->ip_address();
        $data['user_agent'] = substr($this->input->user_agent(), 0, 255);
        $data['created_at'] = date('Y-m-d H:i:s');

        // Support both schema variants:
        // - Newer schema uses data_before/data_after
        // - Older/alternate schema may use a single "changes" column
        $this->initAuditSchemaCache();
        if (isset($data['changes']) && !$this->audit_has_changes_column) {
            $changes_payload = $data['changes'];
            unset($data['changes']);

            if ($this->audit_has_data_after_column) {
                $data['data_after'] = $changes_payload;
            } elseif ($this->audit_has_data_before_column) {
                $data['data_before'] = $changes_payload;
            }
        }

        return $this->db->insert('absensi_audit_log', $data);
    }

    /**
     * Get audit logs for a specific attendance log
     */
    public function getAuditLogs($id_log, $limit = 50)
    {
        return $this->db->select('a.*, u.username as action_by_name')
            ->from('absensi_audit_log a')
            ->join('users u', 'a.action_by = u.id', 'left')
            ->where('a.id_log', $id_log)
            ->order_by('a.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->result();
    }

    /**
     * Get audit logs for a user
     */
    public function getUserAuditLogs($id_user, $limit = 50)
    {
        return $this->db->select('a.*, u.username as action_by_name')
            ->from('absensi_audit_log a')
            ->join('users u', 'a.action_by = u.id', 'left')
            ->where('a.id_user_target', $id_user)
            ->order_by('a.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->result();
    }

    // =========================================================================
    // SHIFT ASSIGNMENT METHODS
    // =========================================================================

    /**
     * Assign fixed shift to user
     */
    public function assignFixedShift($id_user, $id_shift, $tgl_efektif)
    {
        // Check if already has assignment
        $existing = $this->db->where('id_user', $id_user)
            ->where('tipe_shift', 'fixed')
            ->get('pegawai_shift')
            ->row();

        $data = [
            'id_user' => $id_user,
            'tipe_shift' => 'fixed',
            'id_shift_fixed' => $id_shift,
            'tgl_efektif' => $tgl_efektif
        ];

        if ($existing) {
            $this->db->where('id_pegawai_shift', $existing->id_pegawai_shift);
            return $this->db->update('pegawai_shift', $data);
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('pegawai_shift', $data);
    }

    /**
     * Assign rotating shift to user for specific date
     */
    public function assignRotatingShift($id_user, $id_shift, $tanggal)
    {
        $data = [
            'id_user' => $id_user,
            'id_shift' => $id_shift,
            'tanggal' => $tanggal,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Use replace to handle duplicate key
        return $this->db->replace('shift_jadwal', $data);
    }

    /**
     * Get user's shift schedule for a month
     */
    public function getUserShiftSchedule($id_user, $month, $year)
    {
        return $this->db->select('sj.*, s.nama_shift, s.jam_masuk, s.jam_pulang')
            ->from('shift_jadwal sj')
            ->join('master_shift s', 'sj.id_shift = s.id_shift')
            ->where('sj.id_user', $id_user)
            ->where('MONTH(sj.tanggal)', $month)
            ->where('YEAR(sj.tanggal)', $year)
            ->order_by('sj.tanggal', 'ASC')
            ->get()
            ->result();
    }

    /**
     * Bulk assign shifts for multiple users
     */
    public function bulkAssignShift($user_ids, $id_shift, $tgl_efektif)
    {
        $this->db->trans_start();

        foreach ($user_ids as $id_user) {
            $this->assignFixedShift($id_user, $id_shift, $tgl_efektif);
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function markAbsentAsAlpha($date, $admin_id = null)
    {
        $this->load->model('Pengajuan_model', 'pengajuan_model');
        
        // Batch fetch: get all users who should work first
        $users = $this->getFilteredUsersWhoShouldWork($date);
        if (empty($users)) {
            return ['success' => true, 'marked' => 0];
        }
        
        $user_ids = array_column($users, 'id');
        
        // Batch fetch: existing logs for all users
        $existing_logs = $this->db->select('id_user')
            ->from('absensi_logs')
            ->where('tanggal', $date)
            ->where_in('id_user', $user_ids)
            ->get()
            ->result_array();
        $logged_user_ids = array_column($existing_logs, 'id_user');
        
        // Batch fetch: approved leaves for all users
        $approved_leaves = $this->pengajuan_model->get_approved_leaves_batch($user_ids, $date);
        $leave_user_ids = array_column($approved_leaves, 'id_user');
        
        // Prepare batch insert data
        $insert_batch = [];
        $now = date('Y-m-d H:i:s');
        
        foreach ($users as $user) {
            if (in_array($user->id, $logged_user_ids)) {
                continue;
            }
            
            if (in_array($user->id, $leave_user_ids)) {
                continue;
            }
            
            $insert_batch[] = [
                'id_user' => $user->id,
                'tanggal' => $date,
                'status_kehadiran' => 'Alpha',
                'id_shift' => isset($user->id_shift) ? $user->id_shift : null,
                'keterangan' => 'Tidak hadir tanpa keterangan',
                'is_manual_entry' => $admin_id ? 1 : 0,
                'manual_entry_by' => $admin_id,
                'created_at' => $now
            ];
        }
        
        if (empty($insert_batch)) {
            return ['success' => true, 'marked' => 0];
        }
        
        $this->db->trans_start();
        $this->db->insert_batch('absensi_logs', $insert_batch);
        $this->db->trans_complete();
        
        return [
            'success' => $this->db->trans_status(),
            'marked' => count($insert_batch)
        ];
    }

    public function getFilteredUsersWhoShouldWork($date)
    {
        $day_of_week = (int) date('N', strtotime($date));
        
        if ($this->isHoliday($date)) {
            return [];
        }
        
        // Get users with their group config for working days check
        // Join with group config to get working_days in one query
        $users = $this->db->select('u.id, u.username, gr.name as group_name, gr.id as group_id, 
                                    gc.working_days, gc.id_shift_default,
                                    ps.id_shift_fixed')
            ->from('users u')
            ->join('users_groups ug', 'u.id = ug.user_id')
            ->join('groups gr', 'ug.group_id = gr.id')
            ->join('absensi_group_config gc', 'gc.id_group = gr.id AND gc.is_active = 1', 'left')
            ->join('pegawai_shift ps', 'u.id = ps.id_user AND ps.tipe_shift = "fixed"', 'left')
            ->where_in('gr.name', ['guru', 'karyawan', 'siswa'])
            ->where('u.active', 1)
            ->get()
            ->result();

        if (empty($users)) {
            return [];
        }

        $filtered_users = [];
        foreach ($users as $user) {
            $working_days = isset($user->working_days) ? $user->working_days : null;
            
            if (is_string($working_days) && !empty($working_days)) {
                $working_days = json_decode($working_days, true);
            }
            
            if (!is_array($working_days) || empty($working_days)) {
                $working_days = [1, 2, 3, 4, 5];
            }
            
            if (!in_array($day_of_week, $working_days)) {
                continue;
            }
            
            // Attach shift ID for batch insert
            $user->id_shift = $user->id_shift_fixed ?: ($user->id_shift_default ?: null);
            $filtered_users[] = $user;
        }

        return $filtered_users;
    }

    /**
     * Get user's shift for a specific date
     * Alias for getUserShift with consistent resolution logic
     * (roster -> fixed -> group default)
     */
    public function getUserShiftForDate($id_user, $date)
    {
        return $this->getUserShift($id_user, $date);
    }

    public function getUnmarkedUsersCount($date)
    {
        $logged = $this->db->select('id_user')
            ->from('absensi_logs')
            ->where('tanggal', $date)
            ->get()
            ->result_array();
        
        $logged_ids = array_column($logged, 'id_user');

        $this->db->from('users u')
            ->join('users_groups ug', 'u.id = ug.user_id')
            ->join('groups gr', 'ug.group_id = gr.id')
            ->where_in('gr.name', ['guru', 'karyawan', 'siswa'])
            ->where('u.active', 1);

        if (!empty($logged_ids)) {
            $this->db->where_not_in('u.id', $logged_ids);
        }

        return $this->db->count_all_results();
    }

    public function calculateOvertimeMinutes($jam_pulang, $shift_jam_pulang)
    {
        if (empty($jam_pulang) || empty($shift_jam_pulang)) {
            return 0;
        }

        $pulang = strtotime($jam_pulang);
        $batas = strtotime($shift_jam_pulang);

        if ($pulang > $batas) {
            return round(($pulang - $batas) / 60);
        }
        
        return 0;
    }

    // =========================================================================
    // LEGACY METHODS (Absensimanager backward compatibility)
    // =========================================================================

    /**
     * Count today's attendance (checked-in users)
     * @deprecated Use getDashboardStats() for complete stats
     */
    public function count_today_attendance($date = null)
    {
        $date = $date ?: date('Y-m-d');
        return $this->db->where('tanggal', $date)
            ->where('jam_masuk IS NOT NULL', null, false)
            ->count_all_results('absensi_logs');
    }

    /**
     * Count late arrivals today
     * @deprecated Use getDashboardStats() for complete stats
     */
    public function count_late_today($date = null)
    {
        $date = $date ?: date('Y-m-d');
        return $this->db->where('tanggal', $date)
            ->where_in('status_kehadiran', ['Terlambat', 'Terlambat + Pulang Awal'])
            ->count_all_results('absensi_logs');
    }

    /**
     * Get today's attendance logs
     * @deprecated Use getRecentLogs() or getRekapHarian() instead
     */
    public function get_today_logs($date = null, $limit = 20)
    {
        $date = $date ?: date('Y-m-d');
        return $this->db->select('al.*, u.username,
                                  COALESCE(g.nama_guru, k.nama_lengkap, u.first_name) as nama_lengkap,
                                  s.nama_shift')
            ->from('absensi_logs al')
            ->join('users u', 'al.id_user = u.id', 'left')
            ->join('master_guru g', 'u.username = g.nip', 'left')
            ->join('master_karyawan k', 'u.id = k.id_user', 'left')
            ->join('master_shift s', 'al.id_shift = s.id_shift', 'left')
            ->where('al.tanggal', $date)
            ->order_by('al.jam_masuk', 'DESC')
            ->limit($limit)
            ->get()
            ->result();
    }

    // =========================================================================
    // DATATABLE METHODS (Centralized query composition)
    // =========================================================================

    /**
     * DataTables server-side for attendance logs
     * Centralizes join logic and schema knowledge
     * 
     * @param array $filters ['start_date', 'end_date', 'status', 'id_shift']
     * @return string JSON response for DataTables
     */
    public function datatableLogs(array $filters = [])
    {
        $start_date = isset($filters['start_date']) ? $filters['start_date'] : date('Y-m-01');
        $end_date = isset($filters['end_date']) ? $filters['end_date'] : date('Y-m-d');
        $status = isset($filters['status']) ? $filters['status'] : null;
        $id_shift = isset($filters['id_shift']) ? $filters['id_shift'] : null;

        $this->load->library('datatables');
        
        $this->datatables
            ->select('al.id_log, al.tanggal, u.username, 
                      COALESCE(g.nama_guru, k.nama_lengkap, s.nama) as nama_user,
                      ms.nama_shift, al.jam_masuk, al.jam_pulang, 
                      al.status_kehadiran, al.metode_masuk, al.terlambat_menit,
                      al.pulang_awal_menit')
            ->from('absensi_logs al')
            ->join('users u', 'al.id_user = u.id')
            ->join('master_guru g', 'u.username = g.nip', 'left')
            ->join('master_karyawan k', 'u.id = k.id_user', 'left')
            ->join('master_siswa s', 'u.username = s.nis', 'left')
            ->join('master_shift ms', 'al.id_shift = ms.id_shift', 'left')
            ->where('al.tanggal >=', $start_date)
            ->where('al.tanggal <=', $end_date);
        
        if ($status) {
            $this->datatables->where('al.status_kehadiran', $status);
        }
        if ($id_shift) {
            $this->datatables->where('al.id_shift', $id_shift);
        }
        
        return $this->datatables->generate();
    }

    /**
     * DataTables server-side for karyawan/guru list
     * Centralizes join logic and uses correct column names
     * 
     * @return string JSON response for DataTables
     */
    public function datatableKaryawan()
    {
        $this->load->library('datatables');
        
        $this->datatables
            ->select('u.id, u.username, u.email, u.active,
                      COALESCE(g.nama_guru, k.nama_lengkap) as nama_user,
                      gr.name as group_name,
                      ms.nama_shift as shift_name,
                      ps.tgl_efektif')
            ->from('users u')
            ->join('users_groups ug', 'u.id = ug.user_id')
            ->join('groups gr', 'ug.group_id = gr.id')
            ->join('master_guru g', 'u.username = g.nip', 'left')
            ->join('master_karyawan k', 'u.id = k.id_user', 'left')
            ->join('pegawai_shift ps', 'u.id = ps.id_user AND ps.tipe_shift = "fixed"', 'left')
            ->join('master_shift ms', 'ps.id_shift_fixed = ms.id_shift', 'left')
            ->where_in('gr.name', ['guru', 'karyawan'])
            ->where('u.active', 1);
        
        return $this->datatables->generate();
    }

    /**
     * Get karyawan/guru detail for editing
     * Returns all fields needed by the edit modal including shift info
     * 
     * @param int $id User ID
     * @return object|null User data with shift info
     */
    public function getKaryawanDetail($id)
    {
        return $this->db->select('u.*, 
                                  ps.id_shift_fixed as id_shift, 
                                  ps.tgl_efektif,
                                  ms.nama_shift as shift_name,
                                  COALESCE(g.nama_guru, k.nama_lengkap) as nama_user,
                                  gr.name as group_name')
            ->from('users u')
            ->join('users_groups ug', 'u.id = ug.user_id', 'left')
            ->join('groups gr', 'ug.group_id = gr.id', 'left')
            ->join('master_guru g', 'u.username = g.nip', 'left')
            ->join('master_karyawan k', 'u.id = k.id_user', 'left')
             ->join('pegawai_shift ps', 'u.id = ps.id_user AND ps.tipe_shift = "fixed"', 'left')
            ->join('master_shift ms', 'ps.id_shift_fixed = ms.id_shift', 'left')
            ->where('u.id', $id)
            ->get()
            ->row();
    }

    /**
     * DataTables server-side for tendik
     * @return string JSON response for DataTables
     */
    public function datatableTendik()
    {
        $this->load->library('datatables');

        $this->datatables
            ->select('u.id, u.username, u.email, u.active,
                      t.nama_tendik as nama_user,
                      t.tipe_tendik,
                      ms.nama_shift as shift_name,
                      ps.tgl_efektif')
            ->from('users u')
            ->join('users_groups ug', 'u.id = ug.user_id')
            ->join('groups gr', 'ug.group_id = gr.id')
            ->join('master_tendik t', 'u.id = t.id_user', 'left')
            ->join('pegawai_shift ps', 'u.id = ps.id_user AND ps.tipe_shift = "fixed"', 'left')
            ->join('master_shift ms', 'ps.id_shift_fixed = ms.id_shift', 'left')
            ->where('gr.name', 'tendik')
            ->where('u.active', 1);

        return $this->datatables->generate();
    }

    /**
     * Get tendik detail for editing
     * Returns all fields needed by edit modal including shift info
     *
     * @param int $id User ID
     * @return object|null User data with shift info
     */
    public function getTendikDetail($id)
    {
        return $this->db->select('u.*, 
                                   ps.id_shift_fixed as id_shift, 
                                   ps.tgl_efektif,
                                   ms.nama_shift as shift_name,
                                   t.nama_tendik,
                                   t.tipe_tendik,
                                   t.nip,
                                   t.jabatan,
                                   t.no_hp,
                                   t.email,
                                   t.jenis_kelamin,
                                   t.agama,
                                   t.tempat_lahir,
                                   t.tgl_lahir,
                                   t.alamat,
                                   t.is_active')
            ->from('users u')
            ->join('users_groups ug', 'u.id = ug.user_id', 'left')
            ->join('groups gr', 'ug.group_id = gr.id', 'left')
            ->join('master_tendik t', 'u.id = t.id_user', 'left')
            ->join('pegawai_shift ps', 'u.id = ps.id_user AND ps.tipe_shift = "fixed"', 'left')
            ->join('master_shift ms', 'ps.id_shift_fixed = ms.id_shift', 'left')
            ->where('u.id', $id)
            ->get()
            ->row();
    }
}
