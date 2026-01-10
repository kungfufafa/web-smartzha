<?php

/*   ________________________________________
    |                 GarudaCBT              |
    |    https://github.com/garudacbt/cbt    |
    |________________________________________|
*/
defined("BASEPATH") or exit("No direct script access allowed");
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
class Datamapel extends CI_Controller
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
        $this->load->dbforge();
        $this->load->library(["datatables", "form_validation"]);
        $this->load->model("Master_model", "master");
        $this->load->model("Dashboard_model", "dashboard");
        $this->load->model("Dropdown_model", "dropdown");
        $this->form_validation->set_error_delimiters('', '');
    }
    public function output_json($data, $encode = true)
    {
        if ($encode) {
            $data = json_encode($data);
        }
        $this->output->set_content_type("application/json")->set_output($data);
    }
    private function updateUrutanTampil()
    {
        $mapels = $this->db->select("*")->from("master_mapel")->get()->result();
        $insert = [];
        foreach ($mapels as $mapel) {
            $insert = ["id_mapel" => $mapel->id_mapel, "nama_mapel" => $mapel->id_mapel, "kode" => $mapel->id_mapel, "kelompok" => $mapel->id_mapel, "bobot_p" => $mapel->id_mapel, "bobot_k" => $mapel->id_mapel, "jenjang" => $mapel->id_mapel, "urutan" => $mapel->id_mapel, "urutan_tampil" => $mapel->id_mapel, "status" => $mapel->id_mapel, "deletable" => $mapel->id_mapel];
        }
        if (count($insert) > 0) {
            $this->db->update_batch("master_mapel", $insert);
        }
    }
    public function index()
    {
        if (!$this->db->field_exists("urutan_tampil", "master_mapel")) {
            $fields = array("urutan_tampil" => array("type" => "int(3)", "after" => "urutan"));
            $this->dbforge->add_column("master_mapel", $fields);
        }
        $user = $this->ion_auth->user()->row();
        $setting = $this->dashboard->getSetting();
        $data = ["user" => $user, "judul" => "Mata Pelajaran", "subjudul" => "Daftar Mata Pelajaran", "profile" => $this->dashboard->getProfileAdmin($user->id), "setting" => $setting];
        $data["tp"] = $this->dashboard->getTahun();
        $data["tp_active"] = $this->dashboard->getTahunActive();
        $data["smt"] = $this->dashboard->getSemester();
        $data["smt_active"] = $this->dashboard->getSemesterActive();
        $data["kategori"] = ["WAJIB", "PAI (Kemenag)", "PEMINATAN AKADEMIK", "AKADEMIK KEJURUAN", "LINTAS MINAT", "MULOK"];
        $data["kelompok_mapel"] = $this->master->getDataKelompokMapel();
        $data["sub_kelompok_mapel"] = $this->master->getDataSubKelompokMapel();
        $data["kelompok"] = $this->dropdown->getDataKelompokMapel();
        $data["status"] = ["Nonaktif", "Aktif"];
        $data["mapel_non_aktif"] = $this->master->getAllMapelNonAktif($setting->jenjang);
        $this->load->view("_templates/dashboard/_header", $data);
        $this->load->view("master/mapel/data");
        $this->load->view("_templates/dashboard/_footer");
    }
    public function addKelompokMapel()
    {
        $id = $this->input->post("id_kel_mapel");
        $insert = ["nama_kel_mapel" => $this->input->post("nama_kel_mapel", true), "kode_kel_mapel" => $this->input->post("kode_kel_mapel", true), "kategori" => $this->input->post("kategori", true), "id_parent" => $this->input->post("id_parent", true)];
        if ($id != null) {
            $this->db->where("id_kel_mapel", $id);
            $data = $this->db->update("master_kelompok_mapel", $insert);
        } else {
            $data = $this->master->create("master_kelompok_mapel", $insert);
        }
        $this->output->set_content_type("application/json")->set_output($data);
    }
    public function hapusKelompok()
    {
        $id = $this->input->post("id_kel");
        $kode = $this->input->post("kode");
        $id_parent = $this->input->post("id_parent");
        $messages = [];
        safe_where_in($this->db, "kelompok", $kode);
        $numm = $this->db->count_all_results("master_mapel");
        if ($numm > 0) {
            array_push($messages, "Mata Pelajaran");
        }
        safe_where_in($this->db, "id_parent", $id);
        $nums = $this->db->count_all_results("master_kelompok_mapel");
        if ($nums > 0) {
            array_push($messages, "Sub Kelompok");
        }
        if (count($messages) > 0) {
            $this->output_json(["status" => false, "message" => "Kelompok Mapel digunakan di " . count($messages) . " tabel:<br>" . implode("<br>", $messages)]);
        } else {
            if ($this->master->delete("master_kelompok_mapel", $id, "id_kel_mapel")) {
                $this->output_json(["status" => true, "message" => "berhasil"]);
            }
        }
    }
    public function create()
    {
        $setting = $this->dashboard->getSetting();
        $insert = ["nama_mapel" => $this->input->post("nama_mapel", true), "kode" => $this->input->post("kode_mapel", true), "kelompok" => $this->input->post("kelompok", true), "urutan_tampil" => $this->input->post("urutan_tampil", true), "jenjang" => $setting->jenjang];
        $data = $this->master->create("master_mapel", $insert);
        $this->output->set_content_type("application/json")->set_output($data);
    }
    public function getDataKelompok()
    {
        $this->datatables->select("*");
        $this->datatables->from("master_kelompok_mapel");
        $this->datatables->where("id_parent", "0");
        $this->db->order_by("kode_kel_mapel");
        echo $this->datatables->generate();
    }
    public function getDataSubKelompok()
    {
        $this->datatables->select("*");
        $this->datatables->from("master_kelompok_mapel");
        $this->datatables->where("id_parent <> 0");
        $this->db->order_by("kode_kel_mapel");
        echo $this->datatables->generate();
    }
    public function read()
    {
        $setting = $this->dashboard->getSetting();
        $this->datatables->select("id_mapel, urutan_tampil, nama_mapel, kode, kelompok, deletable, status");
        $this->datatables->from("master_mapel");
        $this->db->order_by("kelompok");
        $this->db->order_by("urutan_tampil");
        echo $this->datatables->generate();
    }
    public function update()
    {
        $data = $this->master->updateMapel();
        $this->output->set_content_type("application/json")->set_output($data);
    }
    public function aktifkan($id)
    {
        $this->db->set("status", "1");
        $this->db->where("id_mapel", $id);
        $update = $this->db->update("master_mapel");
        $this->output_json($update);
    }
    public function delete()
    {
        $chk = $this->input->post("checked", true);
        if (!$chk) {
            $this->output_json(["status" => false, "total" => "Tidak ada data yang dipilih!"]);
        } else {
            $messages = [];
            $tables = [];
            $tabless = $this->db->list_tables();
            foreach ($tabless as $table) {
                $fields = $this->db->field_data($table);
                foreach ($fields as $field) {
                    if ($field->name == "id_mapel" || $field->name == "mapel_id") {
                        array_push($tables, $table);
                    }
                }
            }
            foreach ($tables as $table) {
                if ($table != "master_mapel") {
                    if ($table == "cbt_soal") {
                        safe_where_in($this->db, "mapel_id", $chk);
                        $num = $this->db->count_all_results($table);
                    } else {
                        safe_where_in($this->db, "id_mapel", $chk);
                        $num = $this->db->count_all_results($table);
                    }
                    if ($num > 0) {
                        array_push($messages, $table);
                    }
                }
            }
            if (count($messages) > 0) {
                $this->output_json(["status" => false, "total" => "Mapel digunakan di " . count($messages) . " tabel:<br>" . implode("<br>", $messages)]);
            } else {
                if ($this->master->delete("master_mapel", $chk, "id_mapel")) {
                    $this->output_json(["status" => true, "total" => count($chk)]);
                }
            }
        }
    }
    public function import($import_data = null)
    {
        $user = $this->ion_auth->user()->row();
        $data = ["user" => $user, "judul" => "Mata Pelajaran", "subjudul" => "Import Mata Pelajaran", "profile" => $this->dashboard->getProfileAdmin($user->id), "setting" => $this->dashboard->getSetting()];
        if ($import_data != null) {
            $data["import"] = $import_data;
        }
        $data["tp"] = $this->dashboard->getTahun();
        $data["tp_active"] = $this->dashboard->getTahunActive();
        $data["smt"] = $this->dashboard->getSemester();
        $data["smt_active"] = $this->dashboard->getSemesterActive();
        $this->load->view("_templates/dashboard/_header", $data);
        $this->load->view("master/mapel/import");
        $this->load->view("_templates/dashboard/_footer");
    }
    public function previewExcel()
    {
        $config["upload_path"] = "./uploads/import/";
        $config["allowed_types"] = "xls|xlsx|csv";
        $config["max_size"] = 2048;
        $config["encrypt_name"] = true;
        $this->load->library("upload", $config);
        if (!$this->upload->do_upload("upload_file")) {
            $error = $this->upload->display_errors();
            echo $error;
            die;
        }
        $file = $this->upload->data("full_path");
        $ext = $this->upload->data("file_ext");
        switch ($ext) {
            case ".xlsx":
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                break;
            case ".xls":
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
                break;
            case ".csv":
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
                break;
            default:
                echo "unknown file ext";
                die;
        }
        $spreadsheet = $reader->load($file);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        $data = [];
        for ($i = 1; $i < count($sheetData); $i++) {
            if ($sheetData[$i][1] != null) {
                $data[] = ["nama" => $sheetData[$i][1], "kode" => $sheetData[$i][2]];
            }
        }
        unlink($file);
        echo json_encode($data);
    }
    public function previewWord()
    {
        $config["upload_path"] = "./uploads/import/";
        $config["allowed_types"] = "docx";
        $config["max_size"] = 2048;
        $config["encrypt_name"] = true;
        $this->load->library("upload", $config);
        if (!$this->upload->do_upload("upload_file")) {
            $error = $this->upload->display_errors();
            echo $error;
            die;
        }
        $file = $this->upload->data("full_path");
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($file);
        $htmlWriter = new \PhpOffice\PhpWord\Writer\HTML($phpWord);
        try {
            $htmlWriter->save("./uploads/temp/doc.html");
        } catch (\PhpOffice\PhpWord\Exception\Exception $e) {
        }
        unlink($file);
        $text = file_get_contents("./uploads/temp/doc.html");
        $dom = new DOMDocument();
        $dom->loadHTML($text);
        $data = [];
        $dom->preserveWhiteSpace = false;
        $tables = $dom->getElementsByTagName("table");
        $rows = $tables->item(0)->getElementsByTagName("tr");
        for ($i = 1; $i < $rows->count(); $i++) {
            $cols = $rows[$i]->getElementsByTagName("td");
            $data[] = ["nama" => $cols->item(1)->nodeValue, "kode" => $cols->item(2)->nodeValue];
        }
        echo json_encode($data);
    }
    public function do_import()
    {
        $data = json_decode($this->input->post("mapel", true));
        $mapel = [];
        foreach ($data as $j) {
            $mapel[] = ["nama_mapel" => $j->nama, "kode" => $j->kode];
        }
        $save = $this->master->create("master_mapel", $mapel, true);
        $this->output->set_content_type("application/json")->set_output($save);
    }
}
