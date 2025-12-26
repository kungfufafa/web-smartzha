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
use PhpOffice\PhpWord\PhpWord;
class Datajurusan extends CI_Controller
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
    public function index()
    {
        $user = $this->ion_auth->user()->row();
        $data = ["user" => $user, "judul" => "Jurusan", "subjudul" => "Daftar Jurusan", "profile" => $this->dashboard->getProfileAdmin($user->id), "setting" => $this->dashboard->getSetting()];
        $data["tp"] = $this->dashboard->getTahun();
        $data["tp_active"] = $this->dashboard->getTahunActive();
        $data["smt"] = $this->dashboard->getSemester();
        $data["smt_active"] = $this->dashboard->getSemesterActive();
        $data["mapel_peminatan"] = $this->dropdown->getAllMapelPeminatan();
        $jurusans = $this->master->getDataJurusan();
        $jurusan_mapels = [];
        foreach ($jurusans as $jurusan) {
            $jurusan_mapels[$jurusan->id_jurusan] = $this->master->getDataJurusanMapel(explode(",", $jurusan->mapel_peminatan));
        }
        $data["jurusans"] = $jurusans;
        $data["jurusan_mapels"] = $jurusan_mapels;
        $this->load->view("_templates/dashboard/_header", $data);
        $this->load->view("master/jurusan/data");
        $this->load->view("_templates/dashboard/_footer");
    }
    public function add()
    {
        $mapels = [];
        $check_mapel = $this->input->post("mapel", true);
        if ($check_mapel) {
            $row_mapels = count($this->input->post("mapel", true));
            for ($i = 0; $i <= $row_mapels; $i++) {
                array_push($mapels, $this->input->post("mapel[" . $i . "]", true));
            }
        }
        $insert = ["nama_jurusan" => $this->input->post("nama_jurusan", true), "kode_jurusan" => $this->input->post("kode_jurusan", true), "mapel_peminatan" => implode(",", $mapels)];
        $this->master->create("master_jurusan", $insert, false);
        $data["status"] = $insert;
        $this->output_json($data);
    }
    public function data()
    {
        $this->output_json($this->master->getDataTableJurusan(), false);
    }
    public function save()
    {
        $rows = count($this->input->post("nama_jurusan", true));
        $mode = $this->input->post("mode", true);
        $status = FALSE;
        $insert = [];
        $update = [];
        $error = [];
        for ($i = 1; $i <= $rows; $i++) {
            $nama_jurusan = "nama_jurusan[" . $i . "]";
            $this->form_validation->set_rules($nama_jurusan, "Jurusan", "required");
            $this->form_validation->set_message("required", "{field} Wajib diisi");
            if ($this->form_validation->run() === FALSE) {
                $error[] = [$nama_jurusan => form_error($nama_jurusan)];
                $status = FALSE;
            } else {
                if ($mode == "add") {
                    $insert[] = ["nama_jurusan" => $this->input->post($nama_jurusan, true)];
                } elseif ($mode == "edit") {
                    $update[] = array("id_jurusan" => $this->input->post("id_jurusan[" . $i . "]", true), "nama_jurusan" => $this->input->post($nama_jurusan, true));
                }
                $status = TRUE;
            }
        }
        if ($status) {
            if ($mode == "add") {
                $this->master->create("master_jurusan", $insert, true);
                $data["insert"] = $insert;
            } elseif ($mode == "edit") {
                $this->master->update("master_jurusan", $update, "id_jurusan", null, true);
                $data["update"] = $update;
            }
        } else {
            if (isset($error) && count($error) > 0) {
                $data["errors"] = $error;
            }
        }
        $data["status"] = $status;
        $this->output_json($data);
    }
    public function update()
    {
        $data = $this->master->updateJurusan();
        $this->output->set_content_type("application/json")->set_output($data);
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
                    if ($field->name == "id_jurusan" || $field->name == "jurusan_id") {
                        array_push($tables, $table);
                    }
                }
            }
            foreach ($tables as $table) {
                if ($table != "master_jurusan") {
                    if ($table == "master_kelas") {
                        $this->db->where_in("jurusan_id", $chk);
                        $num = $this->db->count_all_results($table);
                    } else {
                        $this->db->where_in("id_jurusan", $chk);
                        $num = $this->db->count_all_results($table);
                    }
                    if ($num > 0) {
                        array_push($messages, $table);
                    }
                }
            }
            if (count($messages) > 0) {
                $this->output_json(["status" => false, "total" => "Data Jurusan digunakan di " . count($messages) . " tabel:<br>" . implode("<br>", $messages)]);
            } else {
                if ($this->master->delete("master_jurusan", $chk, "id_jurusan")) {
                    $this->output_json(["status" => true, "total" => count($chk)]);
                }
            }
        }
    }
    public function load_jurusan()
    {
        $data = $this->master->getJurusan();
        $this->output_json($data);
    }
    public function import($import_data = null)
    {
        $user = $this->ion_auth->user()->row();
        $data = ["user" => $user, "judul" => "Import Jurusan", "subjudul" => "Import Jurusan", "profile" => $this->dashboard->getProfileAdmin($user->id), "setting" => $this->dashboard->getSetting()];
        if ($import_data != null) {
            $data["import"] = $import_data;
        }
        $data["tp"] = $this->dashboard->getTahun();
        $data["tp_active"] = $this->dashboard->getTahunActive();
        $data["smt"] = $this->dashboard->getSemester();
        $data["smt_active"] = $this->dashboard->getSemesterActive();
        $this->load->view("_templates/dashboard/_header", $data);
        $this->load->view("master/jurusan/import");
        $this->load->view("_templates/dashboard/_footer");
    }
    public function preview()
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
            if ($sheetData[$i][0] != null) {
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
        $data = json_decode($this->input->post("jurusan", true));
        $jurusan = [];
        foreach ($data as $j) {
            $jurusan[] = ["nama_jurusan" => $j->nama, "kode_jurusan" => $j->kode];
        }
        $save = $this->master->create("master_jurusan", $jurusan, true);
        $this->output->set_content_type("application/json")->set_output($save);
    }
    function updateById()
    {
        $id = $this->input->post("id_jurusan");
        $nama = $this->input->post("username", true);
        $kode = $this->input->post("email", true);
        $this->db->set("nama_jurusan", $nama);
        $this->db->set("kode_jurusan", $kode);
        $this->db->where("id_jurusan", $id);
        return $this->db->update("master_jurusan");
    }
    public function hapusById()
    {
        $id = $this->input->post("id");
        $this->db->where("id_jurusan", $id);
        return $this->db->delete("master_jurusan");
    }
    function exist($table, $data)
    {
        $query = $this->db->get_where($table, $data);
        $count = $query->num_rows();
        if ($count === 0) {
            return false;
        }
        return true;
    }
}
