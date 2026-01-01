<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Karyawan_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get_all()
    {
        $this->db->select('k.*, u.username, u.email');
        $this->db->from('master_karyawan k');
        $this->db->join('users u', 'k.id_user = u.id', 'left');
        $this->db->where('k.is_active', 1);
        return $this->db->get()->result();
    }

    public function get_by_id($id)
    {
        return $this->db->get_where('master_karyawan', ['id_karyawan' => $id])->row();
    }

    public function create($data)
    {
        return $this->db->insert('master_karyawan', $data);
    }

    public function update($id, $data)
    {
        $this->db->where('id_karyawan', $id);
        return $this->db->update('master_karyawan', $data);
    }

    public function delete($id)
    {
        $this->db->where('id_karyawan', $id);
        return $this->db->update('master_karyawan', ['is_active' => 0]);
    }
}
