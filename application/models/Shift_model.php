<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Shift_model - Shift Management Model
 * 
 * Handles all shift-related database operations including:
 * - Shift CRUD
 * - User shift assignments (fixed and rotating)
 * - Shift schedule retrieval
 * 
 * @author SMARTZHA
 * @version 2.0.0 - Best Practice Edition (camelCase standardized)
 */
class Shift_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // =========================================================================
    // SHIFT CRUD METHODS
    // =========================================================================

    /**
     * Get all active shifts
     * 
     * @return array List of active shifts
     */
    public function getAllShifts()
    {
        return $this->db->get_where('master_shift', ['is_active' => 1])->result();
    }

    /**
     * Alias for getAllShifts - semantic clarity
     */
    public function getAllActive()
    {
        return $this->getAllShifts();
    }

    /**
     * Get shift by ID
     * 
     * @param int $id Shift ID
     * @return object|null Shift data or null if not found
     */
    public function getShiftById($id)
    {
        return $this->db->get_where('master_shift', ['id_shift' => $id])->row();
    }

    /**
     * Create new shift
     * 
     * @param array $data Shift data
     * @return int|false Insert ID on success, false on failure
     */
    public function createShift($data)
    {
        $data['is_active'] = 1;
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('master_shift', $data);
        return $this->db->insert_id();
    }

    /**
     * Update shift
     * 
     * @param int $id Shift ID
     * @param array $data Updated data
     * @return bool Success status
     */
    public function updateShift($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id_shift', $id);
        return $this->db->update('master_shift', $data);
    }

    /**
     * Deactivate shift (soft delete)
     * 
     * @param int $id Shift ID
     * @return bool Success status
     */
    public function deactivateShift($id)
    {
        $this->db->where('id_shift', $id);
        return $this->db->update('master_shift', ['is_active' => 0]);
    }

    // =========================================================================
    // USER SHIFT RESOLUTION METHODS
    // =========================================================================

    /**
     * Get active shift for a user on a specific date
     * 
     * Resolution Priority:
     * 1. Daily Roster (shift_jadwal) - Manual override for specific date
     * 2. Fixed Schedule (pegawai_shift) - Permanent assignment
     * 3. Group Default (absensi_group_config.id_shift_default) - Fallback
     * 
     * @param int $id_user User ID
     * @param string $date Date in Y-m-d format
     * @return object|null Shift data or null if no shift assigned
     */
    public function getUserShift($id_user, $date)
    {
        // 1. Check Daily Roster (Rotating/Override)
        $roster = $this->db->select('s.*, j.id_jadwal')
            ->from('shift_jadwal j')
            ->join('master_shift s', 'j.id_shift = s.id_shift')
            ->where('j.id_user', $id_user)
            ->where('j.tanggal', $date)
            ->get()
            ->row();

        if ($roster) {
            return $roster;
        }

        // 2. Check Fixed Assignment
        $fixed = $this->db->select('s.*')
            ->from('pegawai_shift ps')
            ->join('master_shift s', 'ps.id_shift_fixed = s.id_shift')
            ->where('ps.id_user', $id_user)
            ->where('ps.tipe_shift', 'fixed')
            ->where('ps.tgl_efektif <=', $date)
            ->order_by('ps.tgl_efektif', 'DESC')
            ->limit(1)
            ->get()
            ->row();
        
        if ($fixed) {
            return $fixed;
        }

        // 3. Check Group Default Shift (Fallback)
        $this->load->model('Absensi_model', 'absensi');
        $config = $this->absensi->getAbsensiConfigForUser($id_user);
        if ($config && !empty($config->id_shift_default)) {
            return $this->getShiftById($config->id_shift_default);
        }
        
        return null;
    }

    /**
     * Get user's shift schedule for a date range
     * 
     * @param int $id_user User ID
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array Schedule with date, day name, and shift info
     */
    public function getUserShiftSchedule($id_user, $startDate, $endDate)
    {
        $schedule = [];
        $current = strtotime($startDate);
        $end = strtotime($endDate);
        
        while ($current <= $end) {
            $date = date('Y-m-d', $current);
            $shift = $this->getUserShift($id_user, $date);
            $schedule[] = [
                'tanggal' => $date,
                'hari' => $this->getDayName(date('N', $current)),
                'shift' => $shift
            ];
            $current = strtotime('+1 day', $current);
        }
        
        return $schedule;
    }

    // =========================================================================
    // SHIFT ASSIGNMENT METHODS
    // =========================================================================

    /**
     * Assign fixed shift to user
     * 
     * @param int $idUser User ID
     * @param int $idShift Shift ID
     * @param string $tglEfektif Effective date (Y-m-d)
     * @return bool Success status
     */
    public function assignFixedShift($idUser, $idShift, $tglEfektif)
    {
        $data = [
            'id_user' => $idUser,
            'tipe_shift' => 'fixed',
            'id_shift_fixed' => $idShift,
            'tgl_efektif' => $tglEfektif
        ];
        
        // Check if exists for same date
        $exists = $this->db->get_where('pegawai_shift', [
            'id_user' => $idUser, 
            'tgl_efektif' => $tglEfektif
        ])->row();

        if ($exists) {
            $this->db->where('id_pegawai_shift', $exists->id_pegawai_shift);
            return $this->db->update('pegawai_shift', $data);
        }
        
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('pegawai_shift', $data);
    }

    /**
     * Remove user shift assignment
     * 
     * @param int $idUser User ID
     * @return bool Success status
     */
    public function removeUserShift($idUser)
    {
        return $this->db->delete('pegawai_shift', ['id_user' => $idUser]);
    }

    // =========================================================================
    // USER LISTING METHODS
    // =========================================================================

    /**
     * Get all guru (teachers) with their current shift assignment
     * 
     * @return array List of guru with shift info
     */
    public function getAllGuruWithShift()
    {
        return $this->db->select('g.id_guru, g.nama_guru, g.nip, g.id_user, u.email, 
                                  ps.id_shift_fixed, ps.tgl_efektif, 
                                  s.nama_shift, s.jam_masuk, s.jam_pulang')
            ->from('master_guru g')
            ->join('users u', 'g.id_user = u.id', 'left')
            ->join('pegawai_shift ps', 'g.id_user = ps.id_user AND ps.tipe_shift = "fixed"', 'left')
            ->join('master_shift s', 'ps.id_shift_fixed = s.id_shift', 'left')
            ->where('g.id_user IS NOT NULL')
            ->order_by('g.nama_guru', 'ASC')
            ->get()
            ->result();
    }

    /**
     * Get all siswa (students) with their current shift assignment
     * 
     * @return array List of siswa with shift info
     */
    public function getAllSiswaWithShift()
    {
        return $this->db->select('ms.id_siswa, ms.nama as nama_siswa, ms.nis, 
                                  u.id as id_user, u.email, 
                                  ps.id_shift_fixed, ps.tgl_efektif, 
                                  s.nama_shift, s.jam_masuk, s.jam_pulang, 
                                  mk.nama_kelas')
            ->from('master_siswa ms')
            ->join('users u', 'ms.username = u.username', 'left')
            ->join('pegawai_shift ps', 'u.id = ps.id_user AND ps.tipe_shift = "fixed"', 'left')
            ->join('master_shift s', 'ps.id_shift_fixed = s.id_shift', 'left')
            ->join('kelas_siswa ks', 'ms.id_siswa = ks.id_siswa', 'left')
            ->join('master_kelas mk', 'ks.id_kelas = mk.id_kelas', 'left')
            ->where('u.id IS NOT NULL')
            ->group_by('ms.id_siswa')
            ->order_by('mk.nama_kelas', 'ASC')
            ->order_by('ms.nama', 'ASC')
            ->get()
            ->result();
    }

    // =========================================================================
    // UTILITY METHODS
    // =========================================================================

    /**
     * Get Indonesian day name from day number
     * 
     * @param int $dayNum Day number (1=Monday, 7=Sunday)
     * @return string Day name in Indonesian
     */
    private function getDayName($dayNum)
    {
        $days = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu'
        ];
        return $days[$dayNum] ?? '';
    }
}
