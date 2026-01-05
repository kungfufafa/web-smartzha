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
        $data = [
            "user" => $user,
            "judul" => "Orang Tua",
            "subjudul" => "Data Orang Tua",
            "profile" => $this->dashboard->getProfileAdmin($user->id),
            "setting" => $this->dashboard->getSetting(),
            "tp_active" => $this->dashboard->getTahunActive(),
            "smt_active" => $this->dashboard->getSemesterActive()
        ];
        $this->load->view("_templates/dashboard/_header", $data);
        $this->load->view("master/orangtua/data");
        $this->load->view("_templates/dashboard/_footer");
    }

    public function data()
    {
        $this->output_json($this->orangtua->getDataMasterOrangtua(), false);
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
        $this->form_validation->set_rules("no_hp", "No. HP", "required|trim|min_length[10]|max_length[15]|regex_match[/^[0-9]+$/]|is_unique[master_orangtua.no_hp]");
        $this->form_validation->set_rules("nik", "NIK", "trim|exact_length[16]|regex_match[/^[0-9]+$/]|is_unique[master_orangtua.nik]");

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
        $this->form_validation->set_rules("no_hp", "No. HP", "required|trim|min_length[10]|max_length[15]|regex_match[/^[0-9]+$/]" . $u_no_hp);
        $this->form_validation->set_rules("nik", "NIK", "trim|exact_length[16]|regex_match[/^[0-9]+$/]" . $u_nik);

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
        $ids = $this->input->post("checked", true);
        if (!$ids) {
            $this->output_json(["status" => false]);
            return;
        }

        $ids = array_values(array_filter(array_map('intval', (array) $ids)));
        if (count($ids) === 0) {
            $this->output_json(["status" => false]);
            return;
        }

        $rows = $this->db
            ->select('id_orangtua, id_user, no_hp, nik')
            ->from('master_orangtua')
            ->where_in('id_orangtua', $ids)
            ->get()
            ->result();

        $now = time();
        $deleted = 0;

        $this->db->trans_start();
        foreach ($rows as $row) {
            $id_orangtua = (int) $row->id_orangtua;
            $id_user = (int) ($row->id_user ?? 0);

            if ($id_user > 0) {
                $this->db->where('id_user', $id_user)->delete('parent_siswa');
                $this->ion_auth->delete_user($id_user);
            }

            $deleted_phone = '99' . str_pad((string) $id_orangtua, 5, '0', STR_PAD_LEFT) . substr((string) $now, -8);
            $deleted_nik = '88' . str_pad((string) $id_orangtua, 6, '0', STR_PAD_LEFT) . substr((string) $now, -8);

            $this->db->where('id_orangtua', $id_orangtua);
            $this->db->update('master_orangtua', [
                'is_active' => 0,
                'id_user' => NULL,
                'no_hp' => $deleted_phone,
                'nik' => $deleted_nik,
            ]);

            $deleted += 1;
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->output_json(["status" => false, "msg" => "Gagal menghapus data orang tua"]);
            return;
        }

        $this->output_json(["status" => true, "total" => $deleted]);
    }
}
