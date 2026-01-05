<?php

defined("BASEPATH") or exit("No direct script access allowed");

class Usertendik extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->ion_auth->logged_in()) {
            redirect("auth");
        } else {
            if (!$this->ion_auth->is_admin()) {
                show_error("Hanya Administrator yang diberi hak untuk mengakses halaman ini", 403, "Akses Terlarang");
            }
        }
        $this->load->library(["datatables", "form_validation", "user_management"]);
        $this->load->model("Master_model", "master");
        $this->load->model("Tendik_model", "tendik");
        $this->load->model("Dashboard_model", "dashboard");
        $this->form_validation->set_error_delimiters('', '');
    }

    public function output_json($data, $encode = true)
    {
        if ($encode) {
            $data = json_encode($data);
        }
        $this->output->set_content_type("application/json")->set_output($data);
    }

    public function data()
    {
        $this->output_json($this->tendik->getUserTendik(), false);
    }

    private function activate_tendik_account($id_tendik)
    {
        $id_tendik = (int) $id_tendik;
        if ($id_tendik < 1) {
            return ['status' => false, 'msg' => 'ID tendik tidak ditemukan'];
        }

        $tendik = $this->tendik->get_by_id($id_tendik);
        if (!$tendik || (int) $tendik->is_active !== 1) {
            return ['status' => false, 'msg' => 'Data tendik tidak ditemukan'];
        }

        if (!empty($tendik->id_user)) {
            return ['status' => false, 'msg' => 'Akun tendik sudah aktif.'];
        }

        $username = trim((string) ($tendik->nip ?? ''));
        if ($username === '') {
            return ['status' => false, 'msg' => 'NIP kosong. Username & password user tendik menggunakan NIP.'];
        }

        $email = trim((string) ($tendik->email ?? ''));
        if ($email === '') {
            $email = strtolower($username) . '@tendik.com';
        }

        if (!$this->user_management->isUsernameAvailable($username)) {
            return ['status' => false, 'msg' => 'Username ' . $username . ' tidak tersedia (sudah digunakan).'];
        }

        if (!$this->user_management->isEmailAvailable($email)) {
            return ['status' => false, 'msg' => 'Email ' . $email . ' tidak tersedia (sudah digunakan).'];
        }

        $group = $this->db->get_where('groups', ['name' => 'tendik'])->row();
        if (!$group) {
            return ['status' => false, 'msg' => 'Group tendik tidak ditemukan.'];
        }

        $name = $this->user_management->parseName((string) $tendik->nama_tendik);
        $additional_data = ['first_name' => $name['first_name'], 'last_name' => $name['last_name']];

        $password = $username;
        $id_user = $this->user_management->createUser($username, $password, $email, $additional_data, [(string) $group->id]);
        if (!$id_user) {
            return ['status' => false, 'msg' => 'Gagal membuat user tendik.'];
        }

        $this->db->set('id_user', (int) $id_user);
        $this->db->where('id_tendik', $id_tendik);
        $updated = $this->db->update('master_tendik');
        if (!$updated) {
            $this->ion_auth->delete_user((int) $id_user);
            return ['status' => false, 'msg' => 'Gagal menyimpan link user ke data tendik.'];
        }

        return ['status' => true, 'msg' => 'Akun ' . $tendik->nama_tendik . ' diaktifkan.', 'username' => $username, 'pass' => $password];
    }

    private function deactivate_tendik_account($id_user)
    {
        $id_user = (int) $id_user;
        if ($id_user < 1) {
            return ['status' => false, 'msg' => 'ID user tidak ditemukan'];
        }

        $deleted = $this->ion_auth->delete_user($id_user);
        if ($deleted) {
            $this->db->set('id_user', NULL);
            $this->db->where('id_user', $id_user);
            $this->db->update('master_tendik');
        }

        return [
            'status' => (bool) $deleted,
            'msg' => $deleted ? 'telah dinonaktifkan.' : 'gagal dinonaktifkan.'
        ];
    }

    public function index()
    {
        $user = $this->ion_auth->user()->row();
        $group = $this->ion_auth->get_users_groups($user->id)->row()->name;
        $data = array("user" => $user, "judul" => "User Management", "subjudul" => "Data User Tendik", "profile" => $this->dashboard->getProfileAdmin($user->id), "setting" => $this->dashboard->getSetting());
        if ($group === "admin") {
            $data["tp"] = $this->dashboard->getTahun();
            $data["tp_active"] = $this->dashboard->getTahunActive();
            $data["smt"] = $this->dashboard->getSemester();
            $data["smt_active"] = $this->dashboard->getSemesterActive();
            $this->load->view("_templates/dashboard/_header", $data);
            $this->load->view("users/tendik/data");
            $this->load->view("_templates/dashboard/_footer");
        } else {
            redirect("dashboard");
        }
    }

    public function activate($id_tendik = null)
    {
        $this->requireAdmin();

        if ($id_tendik === null) {
            $id_tendik = $this->input->post('id', true);
        }

        $data = $this->activate_tendik_account($id_tendik);
        $this->output_json($data);
    }

    public function deactivate($id_user = null)
    {
        $this->requireAdmin();

        if ($id_user === null) {
            $id_user = $this->input->post('id', true);
        }

        $data = $this->deactivate_tendik_account($id_user);
        $this->output_json($data);
    }

    public function reset_login()
    {
        $this->requireAdmin();

        $username = $this->input->get('username', true);
        if ($username === null || $username === '') {
            $username = $this->input->post('username', true);
        }

        if (!$username) {
            $this->output_json(['status' => false, 'msg' => 'Username tidak ditemukan']);
            return;
        }

        $reset = $this->user_management->resetLogin($username);
        $this->output_json([
            'status' => (bool) $reset,
            'msg' => $reset ? ' berhasil direset' : ' gagal direset'
        ]);
    }

    public function aktifkanSemua()
    {
        $this->requireAdmin();

        $tendik_list = $this->db
            ->select('id_tendik')
            ->from('master_tendik')
            ->where('is_active', 1)
            ->where('id_user IS NULL', null, false)
            ->get()
            ->result();

        $jum = 0;
        foreach ($tendik_list as $t) {
            $res = $this->activate_tendik_account((int) $t->id_tendik);
            if (!empty($res['status'])) {
                $jum += 1;
            }
        }

        $this->output_json(['status' => true, 'jumlah' => $jum, 'msg' => $jum . ' tendik diaktifkan.']);
    }

    public function nonaktifkanSemua()
    {
        $this->requireAdmin();

        $tendikUsers = $this->db
            ->select('id_user')
            ->from('master_tendik')
            ->where('is_active', 1)
            ->where('id_user IS NOT NULL', null, false)
            ->get()
            ->result();

        $jum = 0;
        foreach ($tendikUsers as $tendik) {
            if ($tendik->id_user) {
                $res = $this->deactivate_tendik_account((int) $tendik->id_user);
                if (!empty($res['status'])) {
                    $jum += 1;
                }
            }
        }

        $this->output_json(['status' => true, 'jumlah' => $jum, 'msg' => $jum . ' tendik dinonaktifkan.']);
    }

    public function activate_all()
    {
        $this->aktifkanSemua();
    }

    public function deactivate_all()
    {
        $this->nonaktifkanSemua();
    }

    private function requireAdmin()
    {
        $this->user_management->requireAdmin();
    }
}
