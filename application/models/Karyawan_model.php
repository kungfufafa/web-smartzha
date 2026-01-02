<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Karyawan_model extends CI_Model
{
    private $tipe_karyawan_list = ['TU', 'SATPAM', 'KEBUN', 'DRIVER', 'LAINNYA'];

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

    public function get_by_user_id($id_user)
    {
        return $this->db->get_where('master_karyawan', ['id_user' => $id_user, 'is_active' => 1])->row();
    }

    public function get_by_tipe($tipe_karyawan)
    {
        $this->db->select('k.*, u.username, u.email');
        $this->db->from('master_karyawan k');
        $this->db->join('users u', 'k.id_user = u.id', 'left');
        $this->db->where('k.tipe_karyawan', $tipe_karyawan);
        $this->db->where('k.is_active', 1);
        return $this->db->get()->result();
    }

    public function get_tipe_list()
    {
        return $this->tipe_karyawan_list;
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

    public function update_tipe($id_karyawan, $tipe_karyawan)
    {
        if (!in_array($tipe_karyawan, $this->tipe_karyawan_list)) {
            return false;
        }
        $this->db->where('id_karyawan', $id_karyawan);
        return $this->db->update('master_karyawan', ['tipe_karyawan' => $tipe_karyawan]);
    }
}
