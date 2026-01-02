<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Shift_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get_all_shifts()
    {
        return $this->db->get_where('master_shift', ['is_active' => 1])->result();
    }

    public function get_shift_by_id($id)
    {
        return $this->db->get_where('master_shift', ['id_shift' => $id])->row();
    }

    /**
     * Get active shift for a user on a specific date
     * Priority:
     * 1. Manual Override / Roster (shift_jadwal)
     * 2. Fixed Schedule (pegawai_shift)
     * 3. Group Default Configuration (absensi_group_config)
     */
    public function get_user_shift($id_user, $date)
    {
        // 1. Check Daily Roster (Rotating/Override)
        $this->db->select('s.*, j.id_jadwal');
        $this->db->from('shift_jadwal j');
        $this->db->join('master_shift s', 'j.id_shift = s.id_shift');
        $this->db->where('j.id_user', $id_user);
        $this->db->where('j.tanggal', $date);
        $roster = $this->db->get()->row();

        if ($roster) {
            return $roster;
        }

        // 2. Check Fixed Assignment
        $this->db->select('s.*');
        $this->db->from('pegawai_shift ps');
        $this->db->join('master_shift s', 'ps.id_shift_fixed = s.id_shift');
        $this->db->where('ps.id_user', $id_user);
        $this->db->where('ps.tipe_shift', 'fixed');
        $this->db->where('ps.tgl_efektif <=', $date);
        $this->db->order_by('ps.tgl_efektif', 'DESC');
        $this->db->limit(1);
        
        $fixed = $this->db->get()->row();
        
        if ($fixed) {
            return $fixed;
        }

        // 3. Check Group Default Shift (Fallback)
        // Load Absensi_model manually to avoid circular dependency if possible, 
        // but here we just need a direct DB query to keep it light.
        
        // Get user's primary group
        $group = $this->db->select('g.id, g.name')
            ->from('users_groups ug')
            ->join('groups g', 'ug.group_id = g.id')
            ->where('ug.user_id', $id_user)
            ->order_by('g.id', 'ASC') // Priority to lower ID (usually admin/guru)
            ->limit(1)
            ->get()->row();
            
        if ($group) {
            // Get config for this group
            $config = $this->db->where('id_group', $group->id)
                ->where('is_active', 1)
                ->get('absensi_group_config')
                ->row();
                
            if ($config && $config->id_shift_default) {
                return $this->get_shift_by_id($config->id_shift_default);
            }
        }
        
        return null;
    }

    public function assign_fixed_shift($id_user, $id_shift, $tgl_efektif)
    {
        $data = [
            'id_user' => $id_user,
            'tipe_shift' => 'fixed',
            'id_shift_fixed' => $id_shift,
            'tgl_efektif' => $tgl_efektif
        ];
        
        // Check if exists for same date
        $exists = $this->db->get_where('pegawai_shift', [
            'id_user' => $id_user, 
            'tgl_efektif' => $tgl_efektif
        ])->row();

        if ($exists) {
            $this->db->where('id_pegawai_shift', $exists->id_pegawai_shift);
            return $this->db->update('pegawai_shift', $data);
        }
        
        return $this->db->insert('pegawai_shift', $data);
    }

    /**
     * Get all guru (teachers) with their current shift assignment
     */
    public function get_all_guru_with_shift()
    {
        $this->db->select('g.id_guru, g.nama_guru, g.nip, g.id_user, u.email, ps.id_shift_fixed, ps.tgl_efektif, s.nama_shift, s.jam_masuk, s.jam_pulang');
        $this->db->from('master_guru g');
        $this->db->join('users u', 'g.id_user = u.id', 'left');
        $this->db->join('pegawai_shift ps', 'g.id_user = ps.id_user AND ps.tipe_shift = "fixed"', 'left');
        $this->db->join('master_shift s', 'ps.id_shift_fixed = s.id_shift', 'left');
        $this->db->where('g.id_user IS NOT NULL');
        $this->db->order_by('g.nama_guru', 'ASC');
        
        return $this->db->get()->result();
    }

    /**
     * Get all siswa with their current shift assignment
     */
    public function get_all_siswa_with_shift()
    {
        $this->db->select('ms.id_siswa, ms.nama as nama_siswa, ms.nis, u.id as id_user, u.email, ps.id_shift_fixed, ps.tgl_efektif, s.nama_shift, s.jam_masuk, s.jam_pulang, mk.nama_kelas');
        $this->db->from('master_siswa ms');
        $this->db->join('users u', 'ms.username = u.username', 'left');
        $this->db->join('pegawai_shift ps', 'u.id = ps.id_user AND ps.tipe_shift = "fixed"', 'left');
        $this->db->join('master_shift s', 'ps.id_shift_fixed = s.id_shift', 'left');
        $this->db->join('kelas_siswa ks', 'ms.id_siswa = ks.id_siswa', 'left');
        $this->db->join('master_kelas mk', 'ks.id_kelas = mk.id_kelas', 'left');
        $this->db->where('u.id IS NOT NULL');
        // Filter only active class relation if needed, usually we sort by name
        $this->db->group_by('ms.id_siswa'); // Prevent duplicates if student has multiple class history
        $this->db->order_by('mk.nama_kelas', 'ASC');
        $this->db->order_by('ms.nama', 'ASC');
        
        return $this->db->get()->result();
    }
    
    public function getAllActive()
    {
        return $this->get_all_shifts();
    }
    
    /**
     * Remove user shift assignment
     */
    public function remove_user_shift($id_user)
    {
        return $this->db->delete('pegawai_shift', ['id_user' => $id_user]);
    }

    /**
     * Get user's shift schedule for a date range
     */
    public function get_user_shift_schedule($id_user, $start_date, $end_date)
    {
        $schedule = [];
        $current = strtotime($start_date);
        $end = strtotime($end_date);
        
        while ($current <= $end) {
            $date = date('Y-m-d', $current);
            $shift = $this->get_user_shift($id_user, $date);
            $schedule[] = [
                'tanggal' => $date,
                'hari' => $this->get_day_name(date('N', $current)),
                'shift' => $shift
            ];
            $current = strtotime('+1 day', $current);
        }
        
        return $schedule;
    }

    private function get_day_name($day_num)
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
        return $days[$day_num] ?? '';
    }

    // =========================================================================
    // CAMELCASE ALIASES (for Controller compatibility)
    // =========================================================================

    public function getAllShifts()
    {
        return $this->get_all_shifts();
    }

    public function getShiftById($id)
    {
        return $this->get_shift_by_id($id);
    }

    public function getUserShift($id_user, $date)
    {
        return $this->get_user_shift($id_user, $date);
    }

    public function assignFixedShift($id_user, $id_shift, $tgl_efektif)
    {
        return $this->assign_fixed_shift($id_user, $id_shift, $tgl_efektif);
    }

    public function getAllGuruWithShift()
    {
        return $this->get_all_guru_with_shift();
    }

    public function getAllSiswaWithShift()
    {
        return $this->get_all_siswa_with_shift();
    }

    public function getUserShiftSchedule($id_user, $start_date, $end_date)
    {
        return $this->get_user_shift_schedule($id_user, $start_date, $end_date);
    }

    public function createShift($data)
    {
        $data['is_active'] = 1;
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('master_shift', $data);
        return $this->db->insert_id();
    }

    public function updateShift($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id_shift', $id);
        return $this->db->update('master_shift', $data);
    }

    public function deactivateShift($id)
    {
        $this->db->where('id_shift', $id);
        return $this->db->update('master_shift', ['is_active' => 0]);
    }
}
