<?php

defined("BASEPATH") or exit("No direct script access allowed");

class Userorangtua extends CI_Controller
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
        $this->load->model("Users_model", "users");
        $this->load->model("Orangtua_model", "orangtua");
        $this->load->model("Master_model", "master");
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
        $this->output_json($this->orangtua->getDataOrangtua(), false);
    }

    public function index()
    {
        $user = $this->ion_auth->user()->row();
        $group = $this->ion_auth->get_users_groups($user->id)->row()->name;
        $data = array("user" => $user, "judul" => "User Management", "subjudul" => "Data User Orang Tua", "profile" => $this->dashboard->getProfileAdmin($user->id), "setting" => $this->dashboard->getSetting());
        if ($group === "admin") {
            $data["tp"] = $this->dashboard->getTahun();
            $data["tp_active"] = $this->dashboard->getTahunActive();
            $data["smt"] = $this->dashboard->getSemester();
            $data["smt_active"] = $this->dashboard->getSemesterActive();
            $this->load->view("_templates/dashboard/_header", $data);
            $this->load->view("users/orangtua/data");
            $this->load->view("_templates/dashboard/_footer");
        } else {
            redirect("dashboard");
        }
    }

    public function activate()
    {
        $this->requireAdmin();

        $id_user = $this->input->post('id');

        if (!$id_user) {
            $this->user_management->jsonResponse([
                'status' => false,
                'msg' => 'ID user tidak ditemukan'
            ]);
            return;
        }

        $orangtua = $this->orangtua->get_by_user_id($id_user);

        if (!$orangtua) {
            $this->user_management->jsonResponse([
                'status' => false,
                'msg' => 'Data orang tua tidak ditemukan'
            ]);
            return;
        }

         $activated = $this->user_management->activateUser($id_user);

        if ($activated) {
            $this->db->set("id_user", $id_user);
            $this->db->where("id_orangtua", $orangtua->id_orangtua);
            $this->db->update("master_orangtua");

            $this->user_management->jsonResponse([
                'status' => true,
                'msg' => 'Akun ' . $orangtua->nama_lengkap . ' berhasil diaktifkan.'
            ]);
        } else {
            $this->user_management->jsonResponse([
                'status' => false,
                'msg' => 'Gagal mengaktifkan akun.'
            ]);
        }
    }

    public function deactivate()
    {
        $this->requireAdmin();

        $id_user = $this->input->post('id');

        if (!$id_user) {
            $this->user_management->jsonResponse([
                'status' => false,
                'msg' => 'ID user tidak ditemukan'
            ]);
            return;
        }

        $deactivated = $this->user_management->deactivateUser($id_user);

        if ($deactivated) {
            $this->db->set("id_user", NULL);
            $this->db->where("id_user", $id_user);
            $this->db->update("master_orangtua");

            $this->user_management->jsonResponse([
                'status' => true,
                'msg' => 'Akun berhasil dinonaktifkan.'
            ]);
        } else {
            $this->user_management->jsonResponse([
                'status' => false,
                'msg' => 'Gagal menonaktifkan akun.'
            ]);
        }
    }

    public function reset_login()
    {
        $this->requireAdmin();

        $username = $this->input->post('username');

        if (!$username) {
            $this->user_management->jsonResponse([
                'status' => false,
                'msg' => 'Username tidak ditemukan'
            ]);
            return;
        }

        $reset = $this->user_management->resetLogin($username);

        if ($reset) {
            $this->user_management->jsonResponse([
                'status' => true,
                'msg' => 'Reset login berhasil.'
            ]);
        } else {
            $this->user_management->jsonResponse([
                'status' => false,
                'msg' => 'Reset login gagal.'
            ]);
        }
    }

    public function activate_all()
    {
        $this->requireAdmin();

        $orangtuaUsers = $this->db
            ->select('u.id, u.active, mo.nama_lengkap, mo.no_hp, mo.id_orangtua')
            ->from('users u')
            ->join('users_groups ug', 'u.id = ug.user_id')
            ->join('groups g', 'ug.group_id = g.id')
            ->join('master_orangtua mo', 'mo.id_user = u.id', 'left')
            ->where('g.name', 'orangtua')
            ->where('u.active', 0)
            ->get()
            ->result();

        $count = 0;

        foreach ($orangtuaUsers as $orangtua) {
            if ($this->user_management->activateUser($orangtua->id)) {
                $count++;
            }
        }

        $this->user_management->jsonResponse([
            'status' => true,
            'msg' => $count . ' akun orang tua berhasil diaktifkan dari ' . count($orangtuaUsers) . ' data.'
        ]);
    }

    public function deactivate_all()
    {
        $this->requireAdmin();

        $orangtuaUsers = $this->orangtua->get_all();
        $count = 0;

        foreach ($orangtuaUsers as $orangtua) {
            if ($orangtua->id) {
                if ($this->user_management->deactivateUser($orangtua->id)) {
                    $count++;
                }
            }
        }

        $this->user_management->jsonResponse([
            'status' => true,
            'msg' => $count . ' akun orang tua berhasil dinonaktifkan.'
        ]);
    }

    private function requireAdmin()
    {
        $this->user_management->requireAdmin();
    }
}
