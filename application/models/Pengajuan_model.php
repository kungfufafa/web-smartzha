<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pengajuan_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create($data)
    {
        return $this->db->insert('absensi_pengajuan', $data);
    }

    public function get_by_user($id_user)
    {
        $this->db->select('p.*, j.nama_izin');
        $this->db->from('absensi_pengajuan p');
        $this->db->join('master_jenis_izin j', 'p.id_jenis_izin = j.id_jenis', 'left');
        $this->db->where('p.id_user', $id_user);
        $this->db->order_by('p.created_at', 'DESC');
        return $this->db->get()->result();
    }

    public function get_pending_all()
    {
        $this->db->select('p.*, u.first_name, u.last_name, j.nama_izin');
        $this->db->from('absensi_pengajuan p');
        $this->db->join('users u', 'p.id_user = u.id');
        $this->db->join('master_jenis_izin j', 'p.id_jenis_izin = j.id_jenis', 'left');
        $this->db->where('p.status', 'Pending');
        $this->db->order_by('p.created_at', 'ASC');
        return $this->db->get()->result();
    }

    public function update_status($id, $status, $approver_id, $reason = null)
    {
        $data = [
            'status' => $status,
            'approved_by' => $approver_id,
            'approved_at' => date('Y-m-d H:i:s'),
            'alasan_tolak' => $reason
        ];
        
        $this->db->where('id_pengajuan', $id);
        return $this->db->update('absensi_pengajuan', $data);
    }

    public function get_jenis_izin()
    {
        return $this->db->get('master_jenis_izin')->result();
    }
}
