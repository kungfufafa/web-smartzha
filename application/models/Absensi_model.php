<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Absensi_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_today_log($id_user, $date)
    {
        $this->db->where('id_user', $id_user);
        $this->db->where('tanggal', $date);
        return $this->db->get('absensi_logs')->row();
    }

    public function clock_in($data)
    {
        $this->db->insert('absensi_logs', $data);
        return $this->db->insert_id();
    }

    public function clock_out($id_log, $data)
    {
        $this->db->where('id_log', $id_log);
        return $this->db->update('absensi_logs', $data);
    }

    public function get_history($id_user, $month, $year)
    {
        $this->db->select('a.*, s.nama_shift, s.jam_masuk as shift_masuk, s.jam_pulang as shift_pulang');
        $this->db->from('absensi_logs a');
        $this->db->join('master_shift s', 'a.id_shift = s.id_shift', 'left');
        $this->db->where('a.id_user', $id_user);
        $this->db->where('MONTH(a.tanggal)', $month);
        $this->db->where('YEAR(a.tanggal)', $year);
        $this->db->order_by('a.tanggal', 'DESC');
        return $this->db->get()->result();
    }

    public function get_rekap_bulanan($id_user, $month, $year)
    {
        $this->db->select("
            SUM(CASE WHEN status_kehadiran = 'Hadir' THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN status_kehadiran = 'Terlambat' THEN 1 ELSE 0 END) as terlambat,
            SUM(CASE WHEN status_kehadiran = 'Pulang Awal' THEN 1 ELSE 0 END) as pulang_awal,
            SUM(CASE WHEN status_kehadiran = 'Izin' THEN 1 ELSE 0 END) as izin,
            SUM(CASE WHEN status_kehadiran = 'Sakit' THEN 1 ELSE 0 END) as sakit,
            SUM(CASE WHEN status_kehadiran = 'Alpha' THEN 1 ELSE 0 END) as alpha,
            SUM(CASE WHEN status_kehadiran = 'Cuti' THEN 1 ELSE 0 END) as cuti,
            SUM(terlambat_menit) as total_terlambat_menit
        ");
        $this->db->from('absensi_logs');
        $this->db->where('id_user', $id_user);
        $this->db->where('MONTH(tanggal)', $month);
        $this->db->where('YEAR(tanggal)', $year);
        return $this->db->get()->row();
    }

    /**
     * Count total attendance for today
     */
    public function count_today_attendance($date)
    {
        $this->db->where('tanggal', $date);
        $this->db->where('jam_masuk IS NOT NULL');
        return $this->db->count_all_results('absensi_logs');
    }

    /**
     * Count late attendance for today
     */
    public function count_late_today($date)
    {
        $this->db->where('tanggal', $date);
        $this->db->where('status_kehadiran', 'Terlambat');
        return $this->db->count_all_results('absensi_logs');
    }

    /**
     * Get today's attendance logs with user and shift info
     */
    public function get_today_logs($date)
    {
        $this->db->select('a.*, u.username, u.first_name, u.last_name, s.nama_shift');
        $this->db->from('absensi_logs a');
        $this->db->join('users u', 'a.id_user = u.id', 'left');
        $this->db->join('master_shift s', 'a.id_shift = s.id_shift', 'left');
        $this->db->where('a.tanggal', $date);
        $this->db->order_by('a.jam_masuk', 'DESC');
        return $this->db->get()->result();
    }
}
