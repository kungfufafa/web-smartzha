<?php

/*   ________________________________________
    |                 GarudaCBT              |
    |    https://github.com/garudacbt/cbt    |
    |________________________________________|
*/
defined("BASEPATH") or exit("No direct script access allowed");
class Dataorangtua extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->ion_auth->logged_in()) {
            redirect("auth");
        } else {
            if (!$this->ion_auth->is_admin()) {
                show_error("Hanya Administrator yang diberi hak untuk mengakses halaman ini, <a href=\"" . base_url("dashboard") . "\">Kembali ke menu awal</a>", 403, "Akses Terlarang");
            }
        }
        $this->load->library(["datatables", "form_validation"]);
        $this->load->model("Master_model", "master");
        $this->load->model("Orangtua_model", "orangtua");
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

    public function index()
    {
        $user = $this->ion_auth->user()->row();
        $data = ["user" => $user, "judul" => "Orang Tua", "subjudul" => "Data Orang Tua", "profile" => $this->dashboard->getProfileAdmin($user->id), "setting" => $this->dashboard->getSetting()];
        $this->load->view("_templates/dashboard/_header", $data);
        $this->load->view("master/orangtua/data");
        $this->load->view("_templates/dashboard/_footer");
    }

    public function data()
    {
        $this->output_json($this->orangtua->get_all(), true);
    }

    public function add()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $user = $this->ion_auth->user()->row();
        $data = ["user" => $user, "judul" => "Orang Tua", "subjudul" => "Tambah Data Orang Tua", "profile" => $this->dashboard->getProfileAdmin($user->id), "setting" => $this->dashboard->getSetting()];
        $this->load->view("_templates/dashboard/_header", $data);
        $this->load->view("master/orangtua/add");
        $this->load->view("_templates/dashboard/_footer");
    }

    public function edit($id)
    {
        $this->load->model("Dashboard_model", "dashboard");
        $orangtua = $this->orangtua->get_by_id($id);
        if (!$orangtua) {
            show_404();
        }
        $user = $this->ion_auth->user()->row();
        $data = ["user" => $user, "judul" => "Edit Orang Tua", "subjudul" => "Edit Data Orang Tua", "orangtua" => $orangtua, "profile" => $this->dashboard->getProfileAdmin($user->id), "setting" => $this->dashboard->getSetting()];
        $this->load->view("_templates/dashboard/_header", $data);
        $this->load->view("master/orangtua/edit");
        $this->load->view("_templates/dashboard/_footer");
    }

    public function create()
    {
        $nik = $this->input->post("nik", true);
        $nama_lengkap = $this->input->post("nama_lengkap", true);
        $no_hp = $this->input->post("no_hp", true);

        $this->form_validation->set_rules("nama_lengkap", "Nama Lengkap", "required|trim|min_length[3]");
        $this->form_validation->set_rules("no_hp", "No. HP", "required|trim|min_length[10]|is_unique[master_orangtua.no_hp]");
        $this->form_validation->set_rules("nik", "NIK", "trim|is_unique[master_orangtua.nik]");

        if ($this->form_validation->run() == FALSE) {
            $data = ["status" => false, "errors" => ["nama_lengkap" => form_error("nama_lengkap"), "no_hp" => form_error("no_hp"), "nik" => form_error("nik")]];
            $this->output_json($data);
        } else {
            $input = [
                "nik" => trim($nik),
                "nama_lengkap" => trim($nama_lengkap),
                "no_hp" => trim($no_hp),
                "email" => $this->input->post("email", true),
                "jenis_kelamin" => $this->input->post("jenis_kelamin", true),
                "agama" => $this->input->post("agama", true),
                "pendidikan_terakhir" => $this->input->post("pendidikan_terakhir", true),
                "pekerjaan" => $this->input->post("pekerjaan", true),
                "alamat" => $this->input->post("alamat", true),
                "kota" => $this->input->post("kota", true),
                "provinsi" => $this->input->post("provinsi", true),
                "kode_pos" => $this->input->post("kode_pos", true),
                "foto" => "uploads/foto_orangtua/" . ($nik ?: time()) . ".jpg"
            ];
            $action = $this->orangtua->createOrangtua($input);
            if ($action) {
                $data = ["status" => true, "msg" => "Data orang tua berhasil ditambahkan"];
            } else {
                $data = ["status" => false, "msg" => "Gagal menambahkan data orang tua"];
            }
            $this->output_json($data);
        }
    }

    public function update()
    {
        $id_orangtua = $this->input->post("id_orangtua", true);
        $nik = $this->input->post("nik", true);
        $nama_lengkap = $this->input->post("nama_lengkap", true);
        $no_hp = $this->input->post("no_hp", true);

        $orangtua = $this->orangtua->get_by_id($id_orangtua);
        if (!$orangtua) {
            $data = ["status" => false, "msg" => "Data orang tua tidak ditemukan"];
            $this->output_json($data);
            return;
        }

        $u_nik = $orangtua->nik === $nik ? '' : '|is_unique[master_orangtua.nik]';
        $u_no_hp = $orangtua->no_hp === $no_hp ? '' : '|is_unique[master_orangtua.no_hp]';

        $this->form_validation->set_rules("nama_lengkap", "Nama Lengkap", "required|trim|min_length[3]");
        $this->form_validation->set_rules("no_hp", "No. HP", "required|trim|min_length[10]" . $u_no_hp);
        $this->form_validation->set_rules("nik", "NIK", "trim" . $u_nik);

        if ($this->form_validation->run() == FALSE) {
            $data = ["status" => false, "errors" => ["nama_lengkap" => form_error("nama_lengkap"), "no_hp" => form_error("no_hp"), "nik" => form_error("nik")]];
            $this->output_json($data);
        } else {
            $input = [
                "nik" => trim($nik),
                "nama_lengkap" => trim($nama_lengkap),
                "no_hp" => trim($no_hp),
                "email" => $this->input->post("email", true),
                "jenis_kelamin" => $this->input->post("jenis_kelamin", true),
                "agama" => $this->input->post("agama", true),
                "pendidikan_terakhir" => $this->input->post("pendidikan_terakhir", true),
                "pekerjaan" => $this->input->post("pekerjaan", true),
                "alamat" => $this->input->post("alamat", true),
                "kota" => $this->input->post("kota", true),
                "provinsi" => $this->input->post("provinsi", true),
                "kode_pos" => $this->input->post("kode_pos", true)
            ];
            $action = $this->orangtua->updateOrangtua($id_orangtua, $input);
            if ($action) {
                $data = ["status" => true, "msg" => "Data orang tua berhasil diperbarui"];
            } else {
                $data = ["status" => false, "msg" => "Gagal memperbarui data orang tua"];
            }
            $this->output_json($data);
        }
    }

    public function delete()
    {
        $id = $this->input->post("checked", true);
        if (!$id) {
            $this->output_json(["status" => false]);
        } else {
            $this->db->where_in("id_orangtua", $id);
            $this->db->update("master_orangtua", ["is_active" => 0]);
            $this->output_json(["status" => true, "total" => count($id)]);
        }
    }
}
