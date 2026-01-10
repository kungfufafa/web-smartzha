<?php

/*   ________________________________________
    |                 GarudaCBT              |
    |    https://github.com/garudacbt/cbt    |
    |________________________________________|
*/
defined("BASEPATH") or exit("No direct script access allowed");
class Datatendik extends CI_Controller
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

    public function index()
    {
        $user = $this->ion_auth->user()->row();
        $data = [
            "user" => $user,
            "judul" => "Tenaga Kependidikan",
            "subjudul" => "Data Tendik",
            "profile" => $this->dashboard->getProfileAdmin($user->id),
            "setting" => $this->dashboard->getSetting(),
            "tp_active" => $this->dashboard->getTahunActive(),
            "smt_active" => $this->dashboard->getSemesterActive()
        ];
        $this->load->view("_templates/dashboard/_header", $data);
        $this->load->view("master/tendik/data");
        $this->load->view("_templates/dashboard/_footer");
    }

    public function data()
    {
        $this->output_json($this->tendik->getDataMasterTendik(), false);
    }

    public function add()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $user = $this->ion_auth->user()->row();
        $data = ["user" => $user, "judul" => "Tenaga Kependidikan", "subjudul" => "Tambah Data Tendik", "profile" => $this->dashboard->getProfileAdmin($user->id), "setting" => $this->dashboard->getSetting()];
        $data["tipe_tendik_list"] = $this->tendik->get_tipe_list();
        $this->load->view("_templates/dashboard/_header", $data);
        $this->load->view("master/tendik/add");
        $this->load->view("_templates/dashboard/_footer");
    }

    public function edit($id)
    {
        $this->load->model("Dashboard_model", "dashboard");
        $tendik = $this->tendik->get_by_id($id);
        if (!$tendik) {
            show_404();
        }
        $user = $this->ion_auth->user()->row();
        $data = ["user" => $user, "judul" => "Edit Tenaga Kependidikan", "subjudul" => "Edit Data Tendik", "tendik" => $tendik, "profile" => $this->dashboard->getProfileAdmin($user->id), "setting" => $this->dashboard->getSetting()];
        $data["tipe_tendik_list"] = $this->tendik->get_tipe_list();
        $this->load->view("_templates/dashboard/_header", $data);
        $this->load->view("master/tendik/edit");
        $this->load->view("_templates/dashboard/_footer");
    }

    public function create()
    {
        $nip = $this->input->post("nip", true);
        $nama_tendik = $this->input->post("nama_tendik", true);
        $tipe_tendik = $this->input->post("tipe_tendik", true);

        $this->form_validation->set_rules("nama_tendik", "Nama Tendik", "required|trim|min_length[3]");
        $this->form_validation->set_rules("nip", "NIP", "trim|is_unique[master_tendik.nip]");
        $this->form_validation->set_rules("no_hp", "No. HP", "required|trim|min_length[10]");

        if ($this->form_validation->run() == FALSE) {
            $data = ["status" => false, "errors" => ["nama_tendik" => form_error("nama_tendik"), "nip" => form_error("nip"), "no_hp" => form_error("no_hp")]];
            $this->output_json($data);
        } else {
            $valid_tipe_list = $this->tendik->get_tipe_list();
            if (!in_array($tipe_tendik, $valid_tipe_list)) {
                $tipe_tendik = 'LAINNYA';
            }
            
            $input = [
                "nip" => trim($nip),
                "nama_tendik" => trim($nama_tendik),
                "jenis_kelamin" => $this->input->post("jenis_kelamin", true),
                "tempat_lahir" => $this->input->post("tempat_lahir", true),
                "tgl_lahir" => $this->input->post("tgl_lahir", true),
                "agama" => $this->input->post("agama", true),
                "no_hp" => $this->input->post("no_hp", true),
                "email" => $this->input->post("email", true),
                "alamat" => $this->input->post("alamat", true),
                "tipe_tendik" => $tipe_tendik,
                "jabatan" => $this->input->post("jabatan", true),
                "status_kepegawaian" => $this->input->post("status_kepegawaian", true),
                "tanggal_masuk" => $this->input->post("tanggal_masuk", true),
                "foto" => "uploads/foto_tendik/" . ($nip ?: time()) . ".jpg"
            ];
            $action = $this->tendik->create($input);
            if ($action) {
                $data = ["status" => true, "msg" => "Data tendik berhasil ditambahkan"];
            } else {
                $data = ["status" => false, "msg" => "Gagal menambahkan data tendik"];
            }
            $this->output_json($data);
        }
    }

    public function update()
    {
        $id_tendik = $this->input->post("id_tendik", true);
        $nip = $this->input->post("nip", true);
        $nama_tendik = $this->input->post("nama_tendik", true);
        $tipe_tendik = $this->input->post("tipe_tendik", true);

        $tendik = $this->tendik->get_by_id($id_tendik);
        if (!$tendik) {
            $data = ["status" => false, "msg" => "Data tendik tidak ditemukan"];
            $this->output_json($data);
            return;
        }

        $u_nip = $tendik->nip === $nip ? '' : '|is_unique[master_tendik.nip]';

        $this->form_validation->set_rules("nama_tendik", "Nama Tendik", "required|trim|min_length[3]");
        $this->form_validation->set_rules("nip", "NIP", "trim" . $u_nip);
        $this->form_validation->set_rules("no_hp", "No. HP", "required|trim|min_length[10]");

        if ($this->form_validation->run() == FALSE) {
            $data = ["status" => false, "errors" => ["nama_tendik" => form_error("nama_tendik"), "nip" => form_error("nip"), "no_hp" => form_error("no_hp")]];
            $this->output_json($data);
        } else {
            $valid_tipe_list = $this->tendik->get_tipe_list();
            if (!in_array($tipe_tendik, $valid_tipe_list)) {
                $tipe_tendik = 'LAINNYA';
            }
            
            $input = [
                "nip" => trim($nip),
                "nama_tendik" => trim($nama_tendik),
                "jenis_kelamin" => $this->input->post("jenis_kelamin", true),
                "tempat_lahir" => $this->input->post("tempat_lahir", true),
                "tgl_lahir" => $this->input->post("tgl_lahir", true),
                "agama" => $this->input->post("agama", true),
                "no_hp" => $this->input->post("no_hp", true),
                "email" => $this->input->post("email", true),
                "alamat" => $this->input->post("alamat", true),
                "tipe_tendik" => $tipe_tendik,
                "jabatan" => $this->input->post("jabatan", true),
                "status_kepegawaian" => $this->input->post("status_kepegawaian", true),
                "tanggal_masuk" => $this->input->post("tanggal_masuk", true)
            ];
            $action = $this->tendik->update($id_tendik, $input);
            if ($action) {
                $data = ["status" => true, "msg" => "Data tendik berhasil diperbarui"];
            } else {
                $data = ["status" => false, "msg" => "Gagal memperbarui data tendik"];
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
            $ids = ci_where_in_values($id);
            if (empty($ids)) {
                $this->output_json(["status" => false]);
                return;
            }
            $this->db->where_in("id_tendik", $ids);
            $this->db->update("master_tendik", ["is_active" => 0]);
            $this->output_json(["status" => true, "total" => count($ids)]);
        }
    }
}
