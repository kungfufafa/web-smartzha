<?php

defined("BASEPATH") or exit("No direct script access allowed");

class Orangtua_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get_all()
    {
        $this->db->select('mo.id_orangtua, mo.id_user, mo.nama_lengkap, mo.nik, mo.no_hp, mo.jenis_kelamin, mo.foto, mo.is_active, u.username, u.email, u.active, COUNT(ps.id) as jml_anak');
        $this->db->from('master_orangtua mo');
        $this->db->join('users u', 'mo.id_user = u.id', 'left');
        $this->db->join('parent_siswa ps', 'u.id = ps.id_user', 'left');
        $this->db->where('mo.is_active', 1);
        $this->db->group_by('mo.id_orangtua');

        return $this->db->get()->result();
    }

    public function get_by_id($id_orangtua)
    {
        return $this->db->get_where('master_orangtua', ['id_orangtua' => $id_orangtua])->row();
    }

    public function get_by_user_id($user_id)
    {
        return $this->db->get_where('master_orangtua', ['id_user' => $user_id, 'is_active' => 1])->row();
    }

    public function create($data)
    {
        $this->db->insert('master_orangtua', $data);
        return $this->db->insert_id();
    }

    public function update($id_orangtua, $data)
    {
        $this->db->where('id_orangtua', $id_orangtua);
        return $this->db->update('master_orangtua', $data);
    }

    public function delete($id)
    {
        $this->db->where('id_orangtua', $id);
        return $this->db->update('master_orangtua', ['is_active' => 0]);
    }

    public function get_with_user($id_orangtua)
    {
        $this->db->select('mo.*, u.username, u.email, u.id as user_id');
        $this->db->from('master_orangtua mo');
        $this->db->join('users u', 'mo.id_user = u.id', 'left');
        $this->db->where('mo.id_orangtua', $id_orangtua);
        return $this->db->get()->row();
    }

    public function getDataOrangtua()
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
            mo.nama_lengkap,
            mo.no_hp,
            mo.foto,
            (SELECT COUNT(*) FROM login_attempts WHERE login_attempts.login = users.username) AS reset
        ");
        $this->datatables->from("users_groups");
        $this->datatables->join("users", "users_groups.user_id=users.id");
        $this->datatables->join("groups", "users_groups.group_id=groups.id");
        $this->datatables->join("master_orangtua mo", "mo.id_user=users.id", "left");
        $this->datatables->where("groups.name", "orangtua");
        return $this->datatables->generate();
    }

    public function getUserOrangtua()
    {
        $this->db->query("SET SQL_BIG_SELECTS=1");
        $this->datatables->select("mo.id_orangtua, mo.nama_lengkap, COALESCE(u.username, mo.no_hp) AS username, u.id, (SELECT COUNT(id) FROM users WHERE users.id = mo.id_user) AS aktif, (SELECT COUNT(login) FROM login_attempts WHERE login_attempts.login = COALESCE(u.username, mo.no_hp)) AS reset, (SELECT GROUP_CONCAT(DISTINCT ms.nama ORDER BY ms.nama SEPARATOR ', ') FROM parent_siswa ps JOIN master_siswa ms ON ms.id_siswa = ps.id_siswa WHERE ps.id_user = mo.id_user) AS anak, (SELECT GROUP_CONCAT(DISTINCT ps.relasi ORDER BY ps.relasi SEPARATOR ', ') FROM parent_siswa ps WHERE ps.id_user = mo.id_user) AS peran");
        $this->datatables->from('master_orangtua mo');
        $this->datatables->join('users u', 'mo.id_user=u.id', 'left');
        $this->datatables->where('mo.is_active', 1);
        return $this->datatables->generate();
    }

    public function getDataMasterOrangtua()
    {
        $this->db->query("SET SQL_BIG_SELECTS=1");
        $this->datatables->select("
            mo.id_orangtua,
            mo.nama_lengkap,
            mo.no_hp,
            mo.email,
            mo.jenis_kelamin,
            mo.is_active,
            (SELECT COUNT(*) FROM parent_siswa ps WHERE ps.id_user = mo.id_user) AS jml_anak
        ");
        $this->datatables->from("master_orangtua mo");
        $this->datatables->where("mo.is_active", 1);
        return $this->datatables->generate();
    }

    public function createOrangtua($data)
    {
        return $this->create($data);
    }

    public function updateOrangtua($id_orangtua, $data)
    {
        return $this->update($id_orangtua, $data);
    }

    public function deleteOrangtua($id_orangtua)
    {
        return $this->delete($id_orangtua);
    }

    public function count_active()
    {
        return $this->db->where('is_active', 1)->count_all_results('master_orangtua');
    }

    public function get_all_inactive()
    {
        $this->db->select('mo.*, u.username, u.email');
        $this->db->from('master_orangtua mo');
        $this->db->join('users u', 'mo.id_user = u.id', 'left');
        $this->db->where('mo.is_active', 0);
        return $this->db->get()->result();
    }

    public function getAnakByUserId($user_id, $id_tp = null, $id_smt = null)
    {
        $this->db->select('ps.*, ms.id_siswa, ms.nama, ms.nis, ms.nisn, ms.foto, ms.jenis_kelamin,
                          ks.id_kelas, mk.nama_kelas, mk.kode_kelas, mk.level_id');
        $this->db->from('parent_siswa ps');
        $this->db->join('master_siswa ms', 'ps.id_siswa = ms.id_siswa');

        if ($id_tp && $id_smt) {
            // Use parameterized query to prevent SQL injection
            $id_tp = (int) $id_tp;
            $id_smt = (int) $id_smt;
            $this->db->join('kelas_siswa ks', "ms.id_siswa = ks.id_siswa AND ks.id_tp = {$id_tp} AND ks.id_smt = {$id_smt}", 'left', false);
        } else {
            $this->db->join('kelas_siswa ks', 'ms.id_siswa = ks.id_siswa', 'left');
        }

        $this->db->join('master_kelas mk', 'ks.id_kelas = mk.id_kelas', 'left');
        $this->db->where('ps.id_user', $user_id);
        $this->db->order_by('ms.nama', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Convenience wrapper used by Orangtua controller to force TP/SMT filtering.
     *
     * @param int $user_id
     * @param int $id_tp
     * @param int $id_smt
     * @return array
     */
    public function getAnakByUserIdWithTpSmt($user_id, $id_tp, $id_smt)
    {
        return $this->getAnakByUserId($user_id, $id_tp, $id_smt);
    }

    public function getSiswaById($id_siswa)
    {
        return $this->db->get_where('master_siswa', ['id_siswa' => $id_siswa])->row();
    }

    public function getSiswaDetailById($id_siswa, $id_tp, $id_smt)
    {
        // Sanitize parameters to prevent SQL injection
        $id_tp = (int) $id_tp;
        $id_smt = (int) $id_smt;
        
        $this->db->select('ms.*, ks.id_kelas, mk.nama_kelas, mk.kode_kelas, mk.level_id');
        $this->db->from('master_siswa ms');
        $this->db->join('kelas_siswa ks', "ms.id_siswa = ks.id_siswa AND ks.id_tp = {$id_tp} AND ks.id_smt = {$id_smt}", 'left', false);
        $this->db->join('master_kelas mk', 'ks.id_kelas = mk.id_kelas', 'left');
        $this->db->where('ms.id_siswa', $id_siswa);
        return $this->db->get()->row();
    }

    public function isParentOfSiswa($user_id, $id_siswa)
    {
        $this->db->where(['id_user' => $user_id, 'id_siswa' => $id_siswa]);
        return $this->db->count_all_results('parent_siswa') > 0;
    }

    public function getParentByPhone($phone)
    {
        $this->db->select('u.*, ps.id_siswa, ps.relasi, mo.nama_lengkap');
        $this->db->from('users u');
        $this->db->join('users_groups ug', 'u.id = ug.user_id');
        $this->db->join('groups g', 'ug.group_id = g.id');
        $this->db->join('parent_siswa ps', 'ps.id_user = u.id', 'left');
        $this->db->where('g.name', 'orangtua');
        return $this->db->get()->row();
    }

    public function getParentUserByPhone($phone)
    {
        $this->db->select('u.*');
        $this->db->from('users u');
        $this->db->where('u.username', $phone);
        return $this->db->get()->row();
    }

    public function get_by_student_phone($phone)
    {
        return $this->db->get_where('master_siswa', ['nohp_ayah' => $phone])->row();
    }

    public function createParentAccess($id_user, $id_siswa, $relasi, $created_by = null)
    {
        $data = [
            'id_user' => $id_user,
            'id_siswa' => $id_siswa,
            'relasi' => $relasi,
            'created_by' => $created_by
        ];
        return $this->db->insert('parent_siswa', $data);
    }

    public function getParentAccessBySiswa($id_siswa)
    {
        $this->db->select('ps.*, u.username, u.email, u.active');
        $this->db->from('parent_siswa ps');
        $this->db->join('users u', 'ps.id_user = u.id');
        $this->db->where('ps.id_siswa', $id_siswa);
        return $this->db->get()->result();
    }

    public function deleteParentAccess($id)
    {
        return $this->db->delete('parent_siswa', ['id' => $id]);
    }

    public function getRelation($id_user, $id_siswa)
    {
        return $this->db->get_where('parent_siswa', [
            'id_user' => $id_user,
            'id_siswa' => $id_siswa
        ])->row();
    }

    public function addParentSiswa($id_user, $id_siswa, $relasi, $created_by = null)
    {
        return $this->createParentAccess($id_user, $id_siswa, $relasi, $created_by);
    }

    public function removeParentSiswa($id)
    {
        return $this->db->delete('parent_siswa', ['id' => $id]);
    }

    public function getOrangtuaGroupId()
    {
        $group = $this->db->get_where('groups', ['name' => 'orangtua'])->row();
        return $group ? $group->id : null;
    }
}
