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
        
        return $this->db->get()->row();
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
}
