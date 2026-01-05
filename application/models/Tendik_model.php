<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Tendik_model extends CI_Model
{
    private $tipe_tendik_list = ['TU', 'PUSTAKAWAN', 'LABORAN', 'SATPAM', 'KEBERSIHAN', 'PENJAGA', 'TEKNISI', 'DRIVER', 'LAINNYA'];

    public function __construct()
    {
        parent::__construct();
    }

    public function get_all()
    {
        $this->db->select('t.id_tendik, t.id_user, t.nip, t.nama_tendik, t.jenis_kelamin, t.no_hp, t.email, t.tipe_tendik, t.jabatan, t.foto, t.is_active, u.username, u.email as user_email, u.active');
        $this->db->from('master_tendik t');
        $this->db->join('users u', 't.id_user = u.id', 'left');
        $this->db->where('t.is_active', 1);
        return $this->db->get()->result();
    }

    public function get_by_id($id)
    {
        return $this->db->get_where('master_tendik', ['id_tendik' => $id])->row();
    }

    public function get_by_user_id($id_user)
    {
        return $this->db->get_where('master_tendik', ['id_user' => $id_user, 'is_active' => 1])->row();
    }

    public function get_by_tipe($tipe_tendik)
    {
        $this->db->select('t.*, u.username, u.email');
        $this->db->from('master_tendik t');
        $this->db->join('users u', 't.id_user = u.id', 'left');
        $this->db->where('t.tipe_tendik', $tipe_tendik);
        $this->db->where('t.is_active', 1);
        return $this->db->get()->result();
    }

    public function get_tipe_list()
    {
        return $this->tipe_tendik_list;
    }

    public function create($data)
    {
        $this->db->insert('master_tendik', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id_tendik', $id);
        return $this->db->update('master_tendik', $data);
    }

    public function delete($id)
    {
        $this->db->where('id_tendik', $id);
        return $this->db->update('master_tendik', ['is_active' => 0]);
    }

    public function update_tipe($id_tendik, $tipe_tendik)
    {
        if (!in_array($tipe_tendik, $this->tipe_tendik_list)) {
            return false;
        }
        $this->db->where('id_tendik', $id_tendik);
        return $this->db->update('master_tendik', ['tipe_tendik' => $tipe_tendik]);
    }

    public function get_with_user($id_tendik)
    {
        $this->db->select('t.*, u.username, u.email, u.id as user_id');
        $this->db->from('master_tendik t');
        $this->db->join('users u', 't.id_user = u.id', 'left');
        $this->db->where('t.id_tendik', $id_tendik);
        return $this->db->get()->row();
    }

    public function count_active()
    {
        return $this->db->where('is_active', 1)->count_all_results('master_tendik');
    }

    public function count_by_tipe($tipe_tendik)
    {
        return $this->db->where(['tipe_tendik' => $tipe_tendik, 'is_active' => 1])->count_all_results('master_tendik');
    }

    public function get_all_inactive()
    {
        $this->db->select('t.*, u.username, u.email');
        $this->db->from('master_tendik t');
        $this->db->join('users u', 't.id_user = u.id', 'left');
        $this->db->where('t.is_active', 0);
        return $this->db->get()->result();
    }

    public function getDataTendik()
    {
        $this->db->query("SET SQL_BIG_SELECTS=1");
        $this->datatables->select("
            users.id,
            users.username,
            users.email,
            FROM_UNIXTIME(users.created_on) as created_on,
            users.last_login,
            users.active,
            groups.name as level,
            t.nama_tendik,
            t.nip,
            t.tipe_tendik,
            (SELECT COUNT(*) FROM login_attempts WHERE login_attempts.login = users.username) AS reset
        ");
        $this->datatables->from("users_groups");
        $this->datatables->join("users", "users_groups.user_id=users.id");
        $this->datatables->join("groups", "users_groups.group_id=groups.id");
        $this->datatables->join("master_tendik t", "t.id_user=users.id", "left");
        $this->datatables->where("groups.name", "tendik");
        return $this->datatables->generate();
    }

    public function getUserTendik()
    {
        $this->db->query("SET SQL_BIG_SELECTS=1");
        $this->datatables->select("t.id_tendik, t.nama_tendik, COALESCE(u.username, t.nip) AS username, u.id, (SELECT COUNT(id) FROM users WHERE users.id = t.id_user) AS aktif, (SELECT COUNT(login) FROM login_attempts WHERE login_attempts.login = COALESCE(u.username, t.nip)) AS reset");
        $this->datatables->from('master_tendik t');
        $this->datatables->join('users u', 't.id_user=u.id', 'left');
        $this->datatables->where('t.is_active', 1);
        return $this->datatables->generate();
    }

    public function getDataMasterTendik()
    {
        $this->db->query("SET SQL_BIG_SELECTS=1");
        $this->datatables->select("
            t.id_tendik,
            t.nama_tendik,
            t.nip,
            t.tipe_tendik,
            t.jabatan,
            t.is_active
        ");
        $this->datatables->from("master_tendik t");
        $this->datatables->where("t.is_active", 1);
        return $this->datatables->generate();
    }
}
