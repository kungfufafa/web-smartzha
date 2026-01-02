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
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
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
                ->get()->row();
            $tipe_karyawan = $karyawan ? $karyawan->tipe_karyawan : null;
        }

        $config = $this->db->where('id_group', $user_group->id)
            ->where('kode_tipe', $tipe_karyawan)
            ->where('is_active', 1)
            ->get('absensi_group_config')->row();

        if (!$config && $tipe_karyawan) {
            $config = $this->db->where('id_group', $user_group->id)
                ->where('kode_tipe IS NULL', null, false)
                ->where('is_active', 1)
                ->get('absensi_group_config')->row();
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
            'kode_tipe' => $tipe_karyawan
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
     */
    public function getUserShift($id_user, $date)
    {
        // First check shift_jadwal for specific date
        $jadwal = $this->db->select('s.*')
            ->from('shift_jadwal sj')
            ->join('master_shift s', 'sj.id_shift = s.id_shift')
            ->where('sj.id_user', $id_user)
            ->where('sj.tanggal', $date)
            ->get()
            ->row();

        if ($jadwal) return $jadwal;

        // Fall back to fixed shift assignment
        $fixed = $this->db->select('s.*')
            ->from('pegawai_shift ps')
            ->join('master_shift s', 'ps.id_shift_fixed = s.id_shift')
            ->where('ps.id_user', $id_user)
            ->where('ps.tipe_shift', 'fixed')
            ->where('ps.tgl_efektif <=', $date)
            ->order_by('ps.tgl_efektif', 'DESC')
            ->get()
            ->row();

        return $fixed;
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
     * Create new attendance log (clock in)
     */
    public function clockIn($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('absensi_logs', $data);
        return $this->db->insert_id();
    }

    /**
     * Update attendance log (clock out)
     */
    public function clockOut($id_log, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id_log', $id_log);
        return $this->db->update('absensi_logs', $data);
    }

    /**
     * Create new log entry
     */
    public function createLog($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('absensi_logs', $data);
        return $this->db->insert_id();
    }

    /**
     * Update log entry
     */
    public function updateLog($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id_log', $id);
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
        // Generate unique token
        $data['token_code'] = $this->generateUniqueToken();
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
     * Validate QR token
     * Returns array with 'valid', 'message', 'id_lokasi' keys for Controller compatibility
     */
    public function validateQrToken($token_code, $date = null, $type = 'checkin')
    {
        $now = date('Y-m-d H:i:s');
        $today = $date ?: date('Y-m-d');

        $token = $this->db->where('token_code', $token_code)
            ->where('is_active', 1)
            ->where('valid_from <=', $now)
            ->where('valid_until >=', $now)
            ->group_start()
                ->where('token_type', 'both')
                ->or_where('token_type', $type)
            ->group_end()
            ->get('absensi_qr_token')
            ->row();

        if (!$token) {
            return ['valid' => false, 'message' => 'QR Code tidak valid atau sudah kadaluarsa.'];
        }

        // Check max usage
        if ($token->max_usage !== null && $token->used_count >= $token->max_usage) {
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
     * Increment QR token usage count
     */
    public function incrementQrUsage($token_id)
    {
        $this->db->set('used_count', 'used_count + 1', false);
        $this->db->where('id_token', $token_id);
        return $this->db->update('absensi_qr_token');
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
     */
    public function countUserBypassThisMonth($id_user)
    {
        return $this->db->where('id_user', $id_user)
            ->where('MONTH(tanggal)', date('m'))
            ->where('YEAR(tanggal)', date('Y'))
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
     */
    public function getHistory($id_user, $month, $year)
    {
        return $this->db->select('a.*, s.nama_shift, s.jam_masuk as shift_masuk, 
                                  s.jam_pulang as shift_pulang, l.nama_lokasi')
            ->from('absensi_logs a')
            ->join('master_shift s', 'a.id_shift = s.id_shift', 'left')
            ->join('absensi_lokasi l', 'a.id_lokasi = l.id_lokasi', 'left')
            ->where('a.id_user', $id_user)
            ->where('MONTH(a.tanggal)', $month)
            ->where('YEAR(a.tanggal)', $year)
            ->order_by('a.tanggal', 'DESC')
            ->get()
            ->result();
    }

    /**
     * Get user's monthly recap
     */
    public function getRekapBulanan($id_user, $month, $year)
    {
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
            ->where('MONTH(tanggal)', $month)
            ->where('YEAR(tanggal)', $year)
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
     */
    public function getRekapBulananAll($month, $year)
    {
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
            ->where('MONTH(al.tanggal)', $month)
            ->where('YEAR(al.tanggal)', $year)
            ->group_by('al.id_user')
            ->order_by('nama_lengkap', 'ASC')
            ->get()
            ->result();
    }

    // =========================================================================
    // DASHBOARD & STATISTICS METHODS
    // =========================================================================

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats($date = null)
    {
        $date = $date ?: date('Y-m-d');

        $stats = new stdClass();

        // Total users who should attend
        $stats->total_pegawai = $this->countAttendanceUsers();

        // Checked in today
        $stats->sudah_masuk = $this->db->where('tanggal', $date)
            ->where('jam_masuk IS NOT NULL')
            ->count_all_results('absensi_logs');

        // Checked out today
        $stats->sudah_pulang = $this->db->where('tanggal', $date)
            ->where('jam_pulang IS NOT NULL')
            ->count_all_results('absensi_logs');

        // Late today
        $stats->terlambat = $this->db->where('tanggal', $date)
            ->where_in('status_kehadiran', ['Terlambat', 'Terlambat + Pulang Awal'])
            ->count_all_results('absensi_logs');

        // Not checked in
        $stats->belum_masuk = $stats->total_pegawai - $stats->sudah_masuk;

        // On leave/sick
        $stats->izin_sakit = $this->db->where('tanggal', $date)
            ->where_in('status_kehadiran', ['Izin', 'Sakit', 'Cuti', 'Dinas Luar'])
            ->count_all_results('absensi_logs');

        // Pending bypass requests
        $stats->pending_bypass = $this->countPendingBypass();

        // Pending pengajuan
        $stats->pending_pengajuan = $this->countPendingPengajuan();

        return $stats;
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
     */
    public function getTopLate($month, $year, $limit = 10)
    {
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
            ->where('MONTH(al.tanggal)', $month)
            ->where('YEAR(al.tanggal)', $year)
            ->where('al.terlambat_menit >', 0)
            ->group_by('al.id_user')
            ->order_by('jumlah_terlambat', 'DESC')
            ->limit($limit)
            ->get()
            ->result();
    }

    /**
     * Get top absent users
     */
    public function getTopAbsent($month, $year, $limit = 10)
    {
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
            ->where('MONTH(al.tanggal)', $month)
            ->where('YEAR(al.tanggal)', $year)
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
    // LEGACY COMPATIBILITY METHODS
    // =========================================================================

    /**
     * Legacy: get_today_log (snake_case)
     */
    public function get_today_log($id_user, $date)
    {
        return $this->getTodayLog($id_user, $date);
    }

    /**
     * Legacy: clock_in (snake_case)
     */
    public function clock_in($data)
    {
        return $this->clockIn($data);
    }

    /**
     * Legacy: clock_out (snake_case)
     */
    public function clock_out($id_log, $data)
    {
        return $this->clockOut($id_log, $data);
    }

    /**
     * Legacy: get_history (snake_case)
     */
    public function get_history($id_user, $month, $year)
    {
        return $this->getHistory($id_user, $month, $year);
    }

    /**
     * Legacy: get_rekap_bulanan (snake_case)
     */
    public function get_rekap_bulanan($id_user, $month, $year)
    {
        return $this->getRekapBulanan($id_user, $month, $year);
    }

    /**
     * Legacy: count_today_attendance (snake_case)
     */
    public function count_today_attendance($date)
    {
        return $this->db->where('tanggal', $date)
            ->where('jam_masuk IS NOT NULL')
            ->count_all_results('absensi_logs');
    }

    /**
     * Legacy: count_late_today (snake_case)
     */
    public function count_late_today($date)
    {
        return $this->db->where('tanggal', $date)
            ->where_in('status_kehadiran', ['Terlambat', 'Terlambat + Pulang Awal'])
            ->count_all_results('absensi_logs');
    }

    /**
     * Legacy: get_today_logs (snake_case)
     */
    public function get_today_logs($date)
    {
        return $this->getRekapHarian($date);
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
        
        $users = $this->getUsersWhoShouldWork($date);
        $marked = 0;

        $this->db->trans_start();

        foreach ($users as $user) {
            $existing = $this->getTodayLog($user->id, $date);
            if ($existing) {
                continue;
            }

            $has_leave = $this->pengajuan_model->has_approved_leave($user->id, $date);
            if ($has_leave) {
                continue;
            }

            $shift = $this->getUserShiftForDate($user->id, $date);
            
            $data = [
                'id_user' => $user->id,
                'tanggal' => $date,
                'status_kehadiran' => 'Alpha',
                'id_shift' => $shift ? $shift->id_shift : null,
                'keterangan' => 'Tidak hadir tanpa keterangan',
                'is_manual_entry' => $admin_id ? 1 : 0,
                'manual_entry_by' => $admin_id,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->db->insert('absensi_logs', $data);
            $marked++;
        }

        $this->db->trans_complete();
        
        return [
            'success' => $this->db->trans_status(),
            'marked' => $marked
        ];
    }

    public function getUsersWhoShouldWork($date)
    {
        $day_of_week = (int) date('N', strtotime($date));
        
        if ($this->isHoliday($date)) {
            return [];
        }
        
        $already_logged = $this->db->select('id_user')
            ->from('absensi_logs')
            ->where('tanggal', $date)
            ->get()
            ->result_array();
        
        $logged_ids = array_column($already_logged, 'id_user');

        $users = $this->db->select('u.id, u.username, gr.name as group_name, gr.id as group_id')
            ->from('users u')
            ->join('users_groups ug', 'u.id = ug.user_id')
            ->join('groups gr', 'ug.group_id = gr.id')
            ->where_in('gr.name', ['guru', 'karyawan', 'siswa'])
            ->where('u.active', 1)
            ->get()
            ->result();

        if (empty($users)) {
            return [];
        }

        $filtered_users = [];
        foreach ($users as $user) {
            if (!empty($logged_ids) && in_array($user->id, $logged_ids)) {
                continue;
            }
            
            $config = $this->getAbsensiConfigForUser($user->id);
            $working_days = isset($config->working_days) ? $config->working_days : [1,2,3,4,5];
            
            if (is_string($working_days)) {
                $working_days = json_decode($working_days, true);
            }
            
            if (!is_array($working_days)) {
                $working_days = [1,2,3,4,5];
            }
            
            if (!in_array($day_of_week, $working_days)) {
                continue;
            }
            
            $filtered_users[] = $user;
        }

        return $filtered_users;
    }

    public function getUserShiftForDate($id_user, $date)
    {
        $rotating = $this->db->select('sj.*, s.*')
            ->from('shift_jadwal sj')
            ->join('master_shift s', 'sj.id_shift = s.id_shift')
            ->where('sj.id_user', $id_user)
            ->where('sj.tanggal', $date)
            ->get()
            ->row();

        if ($rotating) {
            return $rotating;
        }

        return $this->db->select('ps.*, s.*')
            ->from('pegawai_shift ps')
            ->join('master_shift s', 'ps.id_shift_fixed = s.id_shift')
            ->where('ps.id_user', $id_user)
            ->where('ps.tgl_efektif <=', $date)
            ->order_by('ps.tgl_efektif', 'DESC')
            ->get()
            ->row();
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
}
