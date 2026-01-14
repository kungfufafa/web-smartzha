<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Presensi_admin_model extends CI_Model
{
    public function get_shifts()
    {
        return $this->db->order_by('kode_shift', 'ASC')
            ->get('presensi_shift')
            ->result();
    }

    public function is_shift_code_unique($kode_shift, $id_shift = null)
    {
        $this->db->where('kode_shift', $kode_shift);
        if (!empty($id_shift)) {
            $this->db->where('id_shift !=', (int) $id_shift);
        }

        return $this->db->count_all_results('presensi_shift') === 0;
    }

    public function save_shift($data, $id_shift = null)
    {
        if (!$this->is_shift_code_unique($data['kode_shift'], $id_shift)) {
            return ['status' => false, 'msg' => 'Kode shift sudah ada'];
        }

        if (!empty($id_shift)) {
            $success = $this->db->where('id_shift', (int) $id_shift)
                ->update('presensi_shift', $data);
            $message = 'Shift berhasil diupdate';
        } else {
            $success = $this->db->insert('presensi_shift', $data);
            $message = 'Shift berhasil ditambahkan';
        }

        if (!$success) {
            $db_error = $this->db->error();
            return ['status' => false, 'msg' => 'Gagal menyimpan shift: ' . ($db_error['message'] ?? 'Unknown error')];
        }

        return ['status' => true, 'msg' => $message];
    }

    public function delete_shift($id_shift)
    {
        $id_shift = (int) $id_shift;
        if ($id_shift <= 0) {
            return ['status' => false, 'msg' => 'ID shift tidak valid'];
        }

        $dependencies = [];
        $jadwal_count = $this->db->where('id_shift', $id_shift)->count_all_results('presensi_jadwal_kerja');
        if ($jadwal_count > 0) {
            $dependencies[] = $jadwal_count . ' jadwal kerja';
        }

        $config_count = $this->db->where('id_shift_default', $id_shift)->count_all_results('presensi_config_group');
        if ($config_count > 0) {
            $dependencies[] = $config_count . ' konfigurasi group';
        }

        if ($this->db->table_exists('presensi_jadwal_tendik')) {
            $tendik_count = $this->db->where('id_shift', $id_shift)->count_all_results('presensi_jadwal_tendik');
            if ($tendik_count > 0) {
                $dependencies[] = $tendik_count . ' jadwal tendik';
            }
        }

        if ($this->db->table_exists('presensi_jadwal_user')) {
            $user_count = $this->db->where('id_shift', $id_shift)->count_all_results('presensi_jadwal_user');
            if ($user_count > 0) {
                $dependencies[] = $user_count . ' jadwal user';
            }
        }

        if (!empty($dependencies)) {
            return ['status' => false, 'msg' => 'Shift tidak bisa dihapus karena masih digunakan oleh: ' . implode(', ', $dependencies)];
        }

        $this->db->where('id_shift', $id_shift)->delete('presensi_shift');

        if ($this->db->affected_rows() === 0) {
            return ['status' => false, 'msg' => 'Data shift tidak ditemukan'];
        }

        return ['status' => true, 'msg' => 'Shift berhasil dihapus'];
    }

    public function get_locations()
    {
        return $this->db->order_by('kode_lokasi', 'ASC')
            ->get('presensi_lokasi')
            ->result();
    }

    public function is_location_code_unique($kode_lokasi, $id_lokasi = null)
    {
        $this->db->where('kode_lokasi', $kode_lokasi);
        if (!empty($id_lokasi)) {
            $this->db->where('id_lokasi !=', (int) $id_lokasi);
        }

        return $this->db->count_all_results('presensi_lokasi') === 0;
    }

    public function save_location($data, $id_lokasi = null)
    {
        if (!$this->is_location_code_unique($data['kode_lokasi'], $id_lokasi)) {
            return ['status' => false, 'msg' => 'Kode lokasi sudah ada'];
        }

        if (!empty($data['is_default'])) {
            $this->db->set('is_default', 0)->update('presensi_lokasi');
        }

        if (!empty($id_lokasi)) {
            $success = $this->db->where('id_lokasi', (int) $id_lokasi)
                ->update('presensi_lokasi', $data);
            $message = 'Lokasi berhasil diupdate';
        } else {
            $success = $this->db->insert('presensi_lokasi', $data);
            $message = 'Lokasi berhasil ditambahkan';
        }

        if (!$success) {
            $db_error = $this->db->error();
            return ['status' => false, 'msg' => 'Gagal menyimpan lokasi: ' . ($db_error['message'] ?? 'Unknown error')];
        }

        return ['status' => true, 'msg' => $message];
    }

    public function delete_location($id_lokasi)
    {
        $id_lokasi = (int) $id_lokasi;
        if ($id_lokasi <= 0) {
            return ['status' => false, 'msg' => 'ID lokasi tidak valid'];
        }

        $dependencies = [];
        $config_count = $this->db->where('id_lokasi_default', $id_lokasi)->count_all_results('presensi_config_group');
        if ($config_count > 0) {
            $dependencies[] = $config_count . ' konfigurasi group';
        }

        if ($this->db->table_exists('presensi_logs')) {
            $log_count = $this->db->where('id_lokasi', $id_lokasi)->count_all_results('presensi_logs');
            if ($log_count > 0) {
                $dependencies[] = $log_count . ' log presensi';
            }
        }

        if (!empty($dependencies)) {
            return ['status' => false, 'msg' => 'Lokasi tidak bisa dihapus karena masih digunakan oleh: ' . implode(', ', $dependencies)];
        }

        $this->db->where('id_lokasi', $id_lokasi)->delete('presensi_lokasi');

        if ($this->db->affected_rows() === 0) {
            return ['status' => false, 'msg' => 'Data lokasi tidak ditemukan'];
        }

        return ['status' => true, 'msg' => 'Lokasi berhasil dihapus'];
    }

    public function get_holidays()
    {
        if (!$this->db->table_exists('presensi_hari_libur')) {
            return [];
        }

        return $this->db->order_by('tanggal', 'DESC')
            ->get('presensi_hari_libur')
            ->result();
    }

    public function save_holiday($data, $id_libur = null)
    {
        if (!$this->db->table_exists('presensi_hari_libur')) {
            return ['status' => false, 'msg' => 'Tabel presensi_hari_libur belum ada. Jalankan update SQL Presensi terlebih dahulu.'];
        }

        if (!empty($id_libur)) {
            $success = $this->db->where('id_libur', (int) $id_libur)
                ->update('presensi_hari_libur', $data);
            $message = 'Hari libur berhasil diupdate';
        } else {
            $existing = $this->db->select('id_libur')
                ->where('tanggal', $data['tanggal'])
                ->get('presensi_hari_libur')
                ->row();
            if ($existing) {
                return ['status' => false, 'msg' => 'Hari libur untuk tanggal ' . $data['tanggal'] . ' sudah ada'];
            }

            $success = $this->db->insert('presensi_hari_libur', $data);
            $message = 'Hari libur berhasil ditambahkan';
        }

        if (!$success) {
            $db_error = $this->db->error();
            return ['status' => false, 'msg' => 'Gagal menyimpan hari libur: ' . ($db_error['message'] ?? 'Unknown error')];
        }

        return ['status' => true, 'msg' => $message];
    }

    public function delete_holiday($id_libur)
    {
        if (!$this->db->table_exists('presensi_hari_libur')) {
            return ['status' => false, 'msg' => 'Tabel presensi_hari_libur belum ada. Jalankan update SQL Presensi terlebih dahulu.'];
        }

        $id_libur = (int) $id_libur;
        if ($id_libur <= 0) {
            return ['status' => false, 'msg' => 'ID libur tidak valid'];
        }

        $this->db->where('id_libur', $id_libur)->delete('presensi_hari_libur');

        if ($this->db->affected_rows() === 0) {
            return ['status' => false, 'msg' => 'Data hari libur tidak ditemukan atau sudah dihapus'];
        }

        return ['status' => true, 'msg' => 'Hari libur berhasil dihapus'];
    }

    public function get_group_configs()
    {
        return $this->db->select('pg.*, g.name as group_name, ps.nama_shift as shift_name, pl.nama_lokasi as lokasi_name')
            ->from('presensi_config_group pg')
            ->join('groups g', 'pg.id_group = g.id')
            ->join('presensi_shift ps', 'pg.id_shift_default = ps.id_shift', 'left')
            ->join('presensi_lokasi pl', 'pg.id_lokasi_default = pl.id_lokasi', 'left')
            ->order_by('g.name', 'ASC')
            ->get()
            ->result();
    }

    public function save_group_config($data, $id = null)
    {
        if (empty($id)) {
            $existing = $this->db->select('id')
                ->where('id_group', $data['id_group'])
                ->get('presensi_config_group')
                ->row();
            if ($existing) {
                return ['status' => false, 'msg' => 'Konfigurasi untuk group ini sudah ada. Gunakan tombol Edit untuk mengubah.'];
            }
        }

        if (!empty($id)) {
            $success = $this->db->where('id', (int) $id)
                ->update('presensi_config_group', $data);
            $message = 'Konfigurasi group berhasil diupdate';
        } else {
            $success = $this->db->insert('presensi_config_group', $data);
            $message = 'Konfigurasi group berhasil ditambahkan';
        }

        if (!$success) {
            $db_error = $this->db->error();
            return ['status' => false, 'msg' => 'Gagal menyimpan konfigurasi group: ' . ($db_error['message'] ?? 'Unknown error')];
        }

        return ['status' => true, 'msg' => $message];
    }

    public function delete_group_config($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return ['status' => false, 'msg' => 'ID tidak valid'];
        }

        $this->db->where('id', $id)->delete('presensi_config_group');

        if ($this->db->affected_rows() === 0) {
            return ['status' => false, 'msg' => 'Konfigurasi group tidak ditemukan atau sudah dihapus'];
        }

        return ['status' => true, 'msg' => 'Konfigurasi group berhasil dihapus'];
    }

    public function get_qr_tokens($start_date)
    {
        return $this->db->select('qt.*, pl.nama_lokasi as lokasi_nama, ps.nama_shift as shift_nama')
            ->from('presensi_qr_token qt')
            ->join('presensi_lokasi pl', 'qt.id_lokasi = pl.id_lokasi', 'left')
            ->join('presensi_shift ps', 'qt.id_shift = ps.id_shift', 'left')
            ->where('qt.tanggal >=', $start_date)
            ->order_by('qt.created_at', 'DESC')
            ->get()
            ->result();
    }

    public function create_qr_token($data)
    {
        $success = $this->db->insert('presensi_qr_token', $data);
        if (!$success) {
            $db_error = $this->db->error();
            return ['status' => false, 'msg' => 'Gagal membuat QR token: ' . ($db_error['message'] ?? 'Unknown error')];
        }

        return ['status' => true, 'msg' => 'QR token berhasil dibuat'];
    }
}
