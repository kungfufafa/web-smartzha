<?php

/*   ________________________________________
    |                 GarudaCBT              |
    |    https://github.com/garudacbt/cbt    |
    |________________________________________|
*/
class Cbtbanksoal extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->ion_auth->logged_in()) {
            redirect("auth");
        } elseif (!$this->ion_auth->is_admin() && !$this->ion_auth->in_group("guru")) {
            show_error("Hanya Administrator dan guru yang diberi hak untuk mengakses halaman ini, <a href=\"" . base_url("dashboard") . "\">Kembali ke menu awal</a>", 403, "Akses Terlarang");
        }
        $this->load->library("upload");
        $this->load->library(["datatables", "form_validation"]);
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
        $this->load->model("Master_model", "master");
        $this->load->model("Dropdown_model", "dropdown");
        $this->load->model("Dashboard_model", "dashboard");
        $this->load->model("Cbt_model", "cbt");
        $user = $this->ion_auth->user()->row();
        $setting = $this->dashboard->getSetting();
        $data = ["user" => $user, "judul" => "Bank Soal", "subjudul" => "Soal", "setting" => $setting];
        $tp = $this->master->getTahunActive();
        $smt = $this->master->getSemesterActive();
        $data["tp"] = $this->dashboard->getTahun();
        $data["tp_active"] = $tp;
        $data["smt"] = $this->dashboard->getSemester();
        $data["smt_active"] = $smt;
        $data["levels"] = $this->dropdown->getAllLevel($setting->jenjang);
        $data["mapels"] = $this->dropdown->getAllMapel();
        $mode = $this->input->get("mode");
        $type = $this->input->get("type");
        $data["mode"] = $mode == null ? "1" : $mode;
        if ($this->ion_auth->is_admin()) {
            $data["profile"] = $this->dashboard->getProfileAdmin($user->id);
            $data["gurus"] = $this->dropdown->getAllGuru();
            $data["kelas"] = $this->cbt->getKelas($tp->id_tp, $smt->id_smt);
            $data["filters"] = ["0" => "Semua", "1" => "Guru", "2" => "Mapel", "3" => "Level"];
            $data["id_filter"] = $type == null ? '' : $type;
            $banks = [];
            if ($type == "0") {
                $banks = $this->cbt->getDataBank();
                $data["id_guru"] = null;
                $data["id_mapel"] = null;
                $data["id_level"] = null;
            } elseif ($type == "1") {
                $id_guru = $this->input->get("id");
                $data["id_guru"] = $id_guru;
                $banks = $this->cbt->getDataBank($id_guru);
                $data["id_mapel"] = '';
                $data["id_level"] = '';
            } elseif ($type == "2") {
                $id_mapel = $this->input->get("id");
                $data["id_mapel"] = $id_mapel;
                $banks = $this->cbt->getDataBank(null, $id_mapel);
                $data["id_guru"] = '';
                $data["id_level"] = '';
            } elseif ($type == "3") {
                $id_level = $this->input->get("id");
                $data["id_level"] = $id_level;
                $banks = $this->cbt->getDataBank(null, null, $id_level);
                $data["id_guru"] = '';
                $data["id_mapel"] = '';
            } else {
                $data["id_guru"] = null;
                $data["id_mapel"] = null;
                $data["id_level"] = null;
            }
            if ($type != null) {
                $data["banks"] = $banks;
                $jadwal_terpakai = [];
                if (count($banks) > 0) {
                    $ids = [];
                    foreach ($banks as $bank) {
                        foreach ($bank as $tp) {
                            foreach ($tp as $smt) {
                                $ids[] = $smt->id_bank;
                            }
                        }
                    }
                    if (count($ids) > 0) {
                        $terpakai = $this->cbt->getBankTerpakai($ids);
                        foreach ($terpakai as $idj => $rows) {
                            $jadwal_terpakai[$idj] = count($rows);
                        }
                    }
                }
                $data["total_siswa"] = $jadwal_terpakai;
            }
            $this->load->view("_templates/dashboard/_header", $data);
            $this->load->view("cbt/banksoal/data");
            $this->load->view("_templates/dashboard/_footer");
        } else {
            $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
            $nguru[$guru->id_guru] = $guru->nama_guru;
            $data["guru"] = $guru;
            $data["gurus"] = $nguru;
            $data["kelas"] = $this->cbt->getKelas($tp->id_tp, $smt->id_smt);
            $data["filters"] = ["0" => "Semua", "2" => "Mapel", "3" => "Level"];
            $data["id_filter"] = $type == null ? '' : $type;
            $banks = [];
            if ($type == "2") {
                $id_mapel = $this->input->get("id");
                $data["id_mapel"] = $id_mapel;
                $banks = $this->cbt->getDataBank($guru->id_guru, $id_mapel);
                $data["id_guru"] = '';
                $data["id_level"] = '';
            } elseif ($type == "3") {
                $id_level = $this->input->get("id");
                $data["id_level"] = $id_level;
                $banks = $this->cbt->getDataBank($guru->id_guru, null, $id_level);
                $data["id_guru"] = '';
                $data["id_mapel"] = '';
            } else {
                $data["id_guru"] = $guru->id_guru;
                $banks = $this->cbt->getDataBank($guru->id_guru);
                $data["id_mapel"] = '';
                $data["id_level"] = '';
            }
            if ($type != null) {
                $data["banks"] = $banks;
                $jadwal_terpakai = [];
                if (count($banks) > 0) {
                    $ids = [];
                    foreach ($banks as $bank) {
                        foreach ($bank as $tp) {
                            foreach ($tp as $smt) {
                                $ids[] = $smt->id_bank;
                            }
                        }
                    }
                    if (count($ids) > 0) {
                        $terpakai = $this->cbt->getBankTerpakai($ids);
                        foreach ($terpakai as $idj => $rows) {
                            $jadwal_terpakai[$idj] = count($rows);
                        }
                    }
                }
                $data["total_siswa"] = $jadwal_terpakai;
            }
            $this->load->view("members/guru/templates/header", $data);
            $this->load->view("cbt/banksoal/data");
            $this->load->view("members/guru/templates/footer");
        }
    }
    public function data($guru = null)
    {
        $this->load->model("Cbt_model", "cbt");
        $this->output_json($this->cbt->getDataBank($guru), false);
    }
    public function dataTable($guru = null)
    {
        $this->load->model("Cbt_model", "cbt");
        $this->output_json($this->cbt->getDataTableBank($guru), false);
    }
    public function getMapelGuru()
    {
        $this->load->model("Master_model", "master");
        $this->load->model("Kelas_model", "kelas");
        $id_guru = $this->input->get("id_guru", true);
        $tp = $this->master->getTahunActive();
        $smt = $this->master->getSemesterActive();
        $mapel_guru = $this->kelas->getGuruMapelKelas($id_guru, $tp->id_tp, $smt->id_smt);
        $mapel = json_decode(json_encode(unserialize($mapel_guru->mapel_kelas)));
        $arrMapel = [];
        if ($mapel != null) {
            foreach ($mapel as $m) {
                $arrMapel[$m->id_mapel] = $m->nama_mapel;
            }
        }
        $this->output_json($arrMapel);
    }
    public function getGuruMapel()
    {
        $this->load->model("Master_model", "master");
        $this->load->model("Kelas_model", "kelas");
        $id_mapel = $this->input->get("id_mapel", true);
        $tp = $this->master->getTahunActive();
        $smt = $this->master->getSemesterActive();
        $mapel_guru = $this->kelas->getMapelGuruKelas($tp->id_tp, $smt->id_smt);
        $arrGuru = [];
        foreach ($mapel_guru as $guru) {
            $mapel = json_decode(json_encode(unserialize($guru->mapel_kelas)));
            if ($mapel != null) {
                foreach ($mapel as $m) {
                    if (isset($m->id_mapel) && $m->id_mapel == $id_mapel) {
                        $arrGuru[$guru->id_guru] = $guru->nama_guru;
                    }
                }
            }
        }
        $this->output_json($arrGuru);
    }
    public function getKelasLevel()
    {
        $this->load->model("Master_model", "master");
        $this->load->model("Kelas_model", "kelas");
        $this->load->model("Cbt_model", "cbt");
        $level = $this->input->get("level", true);
        $id_guru = $this->input->get("id_guru", true);
        $id_mapel = $this->input->get("mapel", true);
        $tp = $this->master->getTahunActive();
        $smt = $this->master->getSemesterActive();
        $mapel_guru = $this->kelas->getGuruMapelKelas($id_guru, $tp->id_tp, $smt->id_smt);
        $arrKelas = [];
        $arrMapel = [];
        $mapel = json_decode(json_encode(unserialize($mapel_guru->mapel_kelas)));
        foreach ($mapel as $m) {
            $arrMapel[$m->id_mapel] = $m->nama_mapel;
            if ($id_mapel === $m->id_mapel) {
                foreach ($m->kelas_mapel as $kls) {
                    array_push($arrKelas, $kls->kelas);
                }
            }
        }
        $this->output_json(["mapel" => $arrMapel, "kelas" => count($arrKelas) > 0 ? $this->cbt->getKelasByLevel($level, $arrKelas) : []]);
    }
    public function addBank()
    {
        $this->load->model("Dropdown_model", "dropdown");
        $this->load->model("Master_model", "master");
        $this->load->model("Dashboard_model", "dashboard");
        $this->load->model("Kelas_model", "kelas");
        $this->load->model("Cbt_model", "cbt");
        $user = $this->ion_auth->user()->row();
        $setting = $this->dashboard->getSetting();
        $data = ["user" => $user, "judul" => "Bank Soal", "subjudul" => "Buat Bank Soal"];
        $tp = $this->master->getTahunActive();
        $smt = $this->master->getSemesterActive();
        $data["tp"] = $this->dashboard->getTahun();
        $data["tp_active"] = $tp;
        $data["smt"] = $this->dashboard->getSemester();
        $data["smt_active"] = $smt;
        $data["setting"] = $this->dashboard->getSetting();
        $data["bank"] = json_decode(json_encode($this->cbt->dummy($setting->jenjang)));
        $data["jenis"] = $this->cbt->getAllJenisUjian();
        $data["jurusan"] = $this->cbt->getAllJurusan();
        $data["level"] = $this->dropdown->getAllLevel($setting->jenjang);
        $data["mapel_agama"] = $this->master->getAgamaSiswa();
        if ($this->ion_auth->is_admin()) {
            $data["profile"] = $this->dashboard->getProfileAdmin($user->id);
            $data["kelas"] = $this->dropdown->getAllKelas($tp->id_tp, $smt->id_smt);
            $data["id_guru"] = '';
            $data["gurus"] = $this->dropdown->getAllGuru();
            $data["mapel"] = $this->dropdown->getAllMapel();
            $this->load->view("_templates/dashboard/_header", $data);
            $this->load->view("cbt/banksoal/add");
            $this->load->view("_templates/dashboard/_footer");
        } else {
            $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
            $nguru[$guru->id_guru] = $guru->nama_guru;
            $data["gurus"] = $nguru;
            $data["guru"] = $guru;
            $data["id_guru"] = $guru->id_guru;
            $mapel_guru = $this->kelas->getGuruMapelKelas($guru->id_guru, $tp->id_tp, $smt->id_smt);
            $mapel = json_decode(json_encode(unserialize($mapel_guru->mapel_kelas)));
            $arrMapel = [];
            $arrKelas = [];
            foreach ($mapel as $m) {
                $arrMapel[$m->id_mapel] = $m->nama_mapel;
                foreach ($m->kelas_mapel as $kls) {
                    $arrKelas[$m->id_mapel][] = ["id_kelas" => $kls->kelas, "nama_kelas" => $this->dropdown->getNamaKelasById($tp->id_tp, $smt->id_smt, $kls->kelas)];
                }
            }
            $arrId = [];
            if (count($mapel) > 0) {
                foreach ($mapel[0]->kelas_mapel as $id_mapel) {
                    array_push($arrId, $id_mapel->kelas);
                }
            }
            $data["mapel_guru"] = $mapel_guru;
            $data["mapel"] = $arrMapel;
            $data["arrkelas"] = $arrKelas;
            $data["kelas"] = count($arrId) > 0 ? $this->dropdown->getAllKelasByArrayId($tp->id_tp, $smt->id_smt, $arrId) : [];
            $this->load->view("members/guru/templates/header", $data);
            $this->load->view("cbt/banksoal/add");
            $this->load->view("members/guru/templates/footer");
        }
    }
    public function editBank()
    {
        $this->load->model("Master_model", "master");
        $this->load->model("Dropdown_model", "dropdown");
        $this->load->model("Dashboard_model", "dashboard");
        $this->load->model("Kelas_model", "kelas");
        $this->load->model("Cbt_model", "cbt");
        $id_bank = $this->input->get("id_bank", true);
        $id_guru = $this->input->get("id_guru", true);
        $setting = $this->dashboard->getSetting();
        $user = $this->ion_auth->user()->row();
        $data = ["user" => $user, "judul" => "Edit Bank Soal", "subjudul" => "Edit Bank Soal"];
        $tp = $this->master->getTahunActive();
        $smt = $this->master->getSemesterActive();
        $data["tp"] = $this->dashboard->getTahun();
        $data["tp_active"] = $tp;
        $data["smt"] = $this->dashboard->getSemester();
        $data["smt_active"] = $smt;
        $data["bulan"] = $this->dropdown->getBulan();
        $data["setting"] = $this->dashboard->getSetting();
        $data["jenis"] = $this->cbt->getAllJenisUjian();
        $data["jurusan"] = $this->cbt->getAllJurusan();
        $data["level"] = $this->dropdown->getAllLevel($setting->jenjang);
        $data["kelas"] = $this->dropdown->getAllKelas($tp->id_tp, $smt->id_smt);
        $data["bank"] = $this->cbt->getDataBankById($id_bank);
        $data["mapel_agama"] = $this->master->getAgamaSiswa();
        if ($this->ion_auth->is_admin()) {
            $data["profile"] = $this->dashboard->getProfileAdmin($user->id);
            $data["id_guru"] = $id_guru;
            $data["gurus"] = $this->dropdown->getAllGuru();
            $data["mapel"] = $this->dropdown->getAllMapel();
            $mapel_guru = $this->kelas->getGuruMapelKelas($id_guru, $tp->id_tp, $smt->id_smt);
            $data["mapel_guru"] = $mapel_guru;
            $this->load->view("_templates/dashboard/_header", $data);
            $this->load->view("cbt/banksoal/add");
            $this->load->view("_templates/dashboard/_footer");
        } else {
            $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
            $nguru[$guru->id_guru] = $guru->nama_guru;
            $mapel_guru = $this->kelas->getGuruMapelKelas($guru->id_guru, $tp->id_tp, $smt->id_smt);
            $mapel = json_decode(json_encode(unserialize($mapel_guru->mapel_kelas)));
            $arrMapel = [];
            foreach ($mapel as $m) {
                $arrMapel[$m->id_mapel] = $m->nama_mapel;
            }
            $data["gurus"] = $nguru;
            $data["mapel_guru"] = $mapel_guru;
            $data["guru"] = $guru;
            $data["id_guru"] = $guru->id_guru;
            $data["mapel"] = $arrMapel;
            $this->load->view("members/guru/templates/header", $data);
            $this->load->view("cbt/banksoal/add");
            $this->load->view("members/guru/templates/footer");
        }
    }
    public function saveBank()
    {
        $this->load->model("Master_model", "master");
        $this->load->model("Log_model", "logging");
        $this->load->model("Cbt_model", "cbt");
        if ($this->input->post()) {
            $tp = $this->master->getTahunActive();
            $smt = $this->master->getSemesterActive();
            $this->cbt->saveBankSoal($tp->id_tp, $smt->id_smt);
            $status = TRUE;
        } else {
            $status = FALSE;
        }
        $data["status"] = $status;
        $id = $this->input->post("id_bank", true);
        if (!$id) {
            $this->logging->saveLog(3, "menambah bank soal");
        } else {
            $this->logging->saveLog(4, "mengedit bank soal");
        }
        $this->output_json($data);
    }
    public function deleteBank()
    {
        $this->load->model("Master_model", "master");
        $this->load->model("Log_model", "logging");
        $this->load->model("Cbt_model", "cbt");
        $id = $this->input->get("id_bank", true);
        if ($this->cbt->cekJadwalBankSoal($id) > 0) {
            $this->output_json(["status" => false, "message" => "Ada jadwal ujian yang menggunakan bank soal ini"]);
        } else {
            if ($this->master->delete("cbt_soal", $id, "bank_id")) {
                if ($this->master->delete("cbt_bank_soal", $id, "id_bank")) {
                    $this->logging->saveLog(5, "menghapus bank soal");
                    $this->output_json(["status" => true, "message" => "berhasil"]);
                }
            }
        }
    }
    public function deleteAllBank()
    {
        $this->load->model("Master_model", "master");
        $this->load->model("Log_model", "logging");
        $this->load->model("Cbt_model", "cbt");
        $ids = json_decode($this->input->post("ids", true));
        if ($this->cbt->cekJadwalBankSoal($ids) > 0) {
            $this->output_json(["status" => false, "message" => "Ada jadwal ujian yang menggunakan bank soal ini"]);
        } else {
            if ($this->master->delete("cbt_soal", $ids, "bank_id")) {
                if ($this->master->delete("cbt_bank_soal", $ids, "id_bank")) {
                    $this->logging->saveLog(5, "menghapus bank soal");
                    $this->output_json(["status" => true, "message" => "berhasil"]);
                }
            }
        }
    }
    public function detail($id)
    {
        $this->load->model("Master_model", "master");
        $this->load->model("Dashboard_model", "dashboard");
        $this->load->model("Cbt_model", "cbt");
        $user = $this->ion_auth->user()->row();
        $data = ["user" => $user, "judul" => "Detail Soal", "subjudul" => "Detail Soal"];
        $data["setting"] = $this->dashboard->getSetting();
        $tp = $this->master->getTahunActive();
        $smt = $this->master->getSemesterActive();
        $data["tp"] = $this->dashboard->getTahun();
        $data["tp_active"] = $tp;
        $data["smt"] = $this->dashboard->getSemester();
        $data["smt_active"] = $smt;
        $data["bank"] = $this->cbt->getDataBankById($id);
        $data["soals"] = $this->cbt->getAllSoalByBank($id);
        $data["kelas"] = $this->cbt->getKelas($tp->id_tp, $smt->id_smt);
        $terpakai = $this->cbt->getBankTerpakai([$id]);
        $data["total_siswa"] = isset($terpakai[$id]) ? count($terpakai[$id]) : 0;
        if ($this->ion_auth->is_admin()) {
            $data["profile"] = $this->dashboard->getProfileAdmin($user->id);
            $this->load->view("_templates/dashboard/_header", $data);
            $this->load->view("cbt/banksoal/detail");
            $this->load->view("_templates/dashboard/_footer");
        } else {
            $data["guru"] = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
            $this->load->view("members/guru/templates/header", $data);
            $this->load->view("cbt/banksoal/detail");
            $this->load->view("members/guru/templates/footer");
        }
    }
    public function saveSelected()
    {
        $this->load->model("Cbt_model", "cbt");
        $bank_id = $this->input->post("id_bank", true);
        $jenis = $this->input->post("jenis", true);
        $jml = $this->input->post("soal", true);
        $soal = $jml != null ? count($jml) : 0;
        $unchek = json_decode($this->input->post("uncheck", true));
        $arrId = [];
        for ($i = 0; $i <= $soal; $i++) {
            $id = $this->input->post("soal[" . $i . "]", true);
            if ($id != null) {
                array_push($arrId, $id);
            }
        }
        $updated = 0;
        foreach ($arrId as $id) {
            $this->db->set("tampilkan", 1);
            $this->db->where("id_soal", $id);
            $this->db->update("cbt_soal");
            $updated++;
        }
        foreach ($unchek as $id) {
            $this->db->set("tampilkan", 0);
            $this->db->where("id_soal", $id);
            $this->db->update("cbt_soal");
        }
        sleep(1);
        $bank = $this->cbt->getDataBankById($bank_id);
        $soals = $this->cbt->getAllSoalByBank($bank_id);
        $total_soal_tampil = isset(array_count_values(array_column($soals, "tampilkan"))["1"]) ? array_count_values(array_column($soals, "tampilkan"))["1"] : 0;
        $total_soal_seharusnya_tampil = $bank->tampil_pg + $bank->tampil_kompleks + $bank->tampil_jodohkan + $bank->tampil_isian + $bank->tampil_esai;
        $tampil_kurang = $total_soal_tampil < $total_soal_seharusnya_tampil;
        $status_soal = $tampil_kurang ? "0" : "1";
        $this->db->set("status_soal", $status_soal);
        $this->db->where("id_bank", $bank_id);
        $this->db->update("cbt_bank_soal");
        $data["check"] = $updated;
        $this->output_json($data);
    }
    public function copyBankSoal($id_bank)
    {
        $this->load->model("Master_model", "master");
        $this->load->model("Dashboard_model", "dashboard");
        $this->load->model("Log_model", "logging");
        $this->load->model("Cbt_model", "cbt");
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $bank = $this->cbt->getDataBankById($id_bank);
        $soals = $this->cbt->getAllSoalByBank($id_bank);
        $data = ["id_tp" => $tp->id_tp, "id_smt" => $smt->id_smt, "bank_jenis_id" => $bank->bank_jenis_id, "bank_kode" => $bank->bank_kode . "_COPY", "bank_level" => $bank->bank_level, "bank_kelas" => $bank->bank_kelas, "bank_mapel_id" => $bank->bank_mapel_id, "bank_jurusan_id" => $bank->bank_jurusan_id, "bank_guru_id" => $bank->bank_guru_id, "bank_nama" => $bank->bank_nama, "kkm" => $bank->kkm, "deskripsi" => $bank->deskripsi, "jml_soal" => $bank->jml_soal, "tampil_pg" => $bank->tampil_pg, "bobot_pg" => $bank->bobot_pg, "jml_kompleks" => $bank->jml_kompleks, "tampil_kompleks" => $bank->tampil_kompleks, "bobot_kompleks" => $bank->bobot_kompleks, "jml_jodohkan" => $bank->jml_jodohkan, "tampil_jodohkan" => $bank->tampil_jodohkan, "bobot_jodohkan" => $bank->bobot_jodohkan, "jml_isian" => $bank->jml_isian, "tampil_isian" => $bank->tampil_isian, "bobot_isian" => $bank->bobot_isian, "jml_esai" => $bank->jml_esai, "tampil_esai" => $bank->tampil_esai, "bobot_esai" => $bank->bobot_esai, "opsi" => $bank->opsi, "date" => date("Y-m-d H:i:s"), "status" => $bank->status, "soal_agama" => $bank->soal_agama];
        $result = $this->master->create("cbt_bank_soal", $data);
        $id = $this->db->insert_id();
        if (count($soals) > 0) {
            foreach ($soals as $soal) {
                unset($soal->id_soal);
                $soal->bank_id = $id;
                $soal->created_on = time();
                $soal->updated_on = time();
            }
            $this->db->insert_batch("cbt_soal", $soals);
            $this->logging->saveLog(3, "membuat bank soal");
        }
        $this->output_json($result);
    }
    public function buatsoal($id_bank)
    {
        $this->load->model("Master_model", "master");
        $this->load->model("Dropdown_model", "dropdown");
        $this->load->model("Dashboard_model", "dashboard");
        $this->load->model("Cbt_model", "cbt");
        $_no = $this->input->get("no", true);
        $_jns = $this->input->get("jns", true);
        $user = $this->ion_auth->user()->row();
        $data = ["user" => $user, "judul" => "Buat Soal", "subjudul" => "Buat Soal"];
        $tp = $this->master->getTahunActive();
        $smt = $this->master->getSemesterActive();
        $data["tp"] = $this->dashboard->getTahun();
        $data["tp_active"] = $tp;
        $data["smt"] = $this->dashboard->getSemester();
        $data["smt_active"] = $smt;
        $setting = $this->dashboard->getSetting();
        $data["setting"] = $setting;
        $data["p_no"] = $_no != null ? $_no : "1";
        $act_tab = $_jns != null ? $_jns : "1";
        $data["p_jns"] = $act_tab;
        $tab = $this->input->get("tab", true);
        $jenis = $tab == null ? $act_tab : $tab;
        $data["tab_active"] = $jenis;
        $bank = $this->cbt->getDataBankById($id_bank);
        $data["soal"] = null;
        $data["soal_ada"] = $this->cbt->cekSoalAda($id_bank, $jenis);
        $data_komplit = $this->cbt->cekSoalBelumKomplit($jenis, $bank->opsi);
        $data["soal_belum_komplit"] = isset($data_komplit[$id_bank]) ? $data_komplit[$id_bank] : [];
        if ($jenis == "1") {
            $data["jml_pg"] = $this->cbt->getNomorSoalTerbesar($id_bank, 1);
        } elseif ($jenis == "2") {
            $data["jml_pg2"] = $this->cbt->getNomorSoalTerbesar($id_bank, 2);
        } elseif ($jenis == "3") {
            $data["jml_jodohkan"] = $this->cbt->getNomorSoalTerbesar($id_bank, 3);
        } elseif ($jenis == "4") {
            $data["jml_isian"] = $this->cbt->getNomorSoalTerbesar($id_bank, 4);
        } elseif ($jenis == "5") {
            $data["jml_essai"] = $this->cbt->getNomorSoalTerbesar($id_bank, 5);
        }
        $data["bank"] = $bank;
        $data["soals"] = $this->cbt->getAllSoalByBank($id_bank, $jenis);
        $data["jurusan"] = $this->cbt->getAllJurusan();
        $data["level"] = $this->dropdown->getAllLevel($setting->jenjang);
        $data["kelas"] = $this->dropdown->getAllKelas($tp->id_tp, $smt->id_smt);
        if ($this->ion_auth->is_admin()) {
            $data["profile"] = $this->dashboard->getProfileAdmin($user->id);
            $this->load->view("_templates/dashboard/_header", $data);
            $this->load->view("cbt/banksoal/soal");
            $this->load->view("_templates/dashboard/_footer");
        } else {
            $data["guru"] = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
            $this->load->view("members/guru/templates/header", $data);
            $this->load->view("cbt/banksoal/soal");
            $this->load->view("members/guru/templates/footer");
        }
    }
    public function getSoalByNomor()
    {
        $this->load->model("Cbt_model", "cbt");
        $bank_id = $this->input->get("bank_id", true);
        $nomor = $this->input->get("nomor", true);
        $jenis = $this->input->get("jenis", true);
        $soal = $this->cbt->getSoalByNomor($bank_id, $nomor, $jenis);
        $data = $soal;
        if ($data != null) {
            $data->file = unserialize($soal->file);
            if ($jenis == "2") {
                $t = @unserialize($soal->opsi_a);
                if ($t !== false) {
                    $data->opsi_a = $t;
                } else {
                    $data->opsi_a = false;
                }
                $j = @unserialize($soal->jawaban);
                if ($j !== false) {
                    $data->jawaban = $j;
                } else {
                    $data->jawaban = false;
                }
            } elseif ($jenis == "3") {
                $j = @unserialize($soal->jawaban);
                if ($j !== false) {
                    $data->jawaban = $j;
                } else {
                    $data->jawaban = false;
                }
            }
        } else {
            if ($nomor != 1) {
                $data = ["bank_id" => $bank_id, "jenis" => $jenis, "nomor_soal" => $nomor];
            }
        }
        $this->output_json($data);
    }
    public function tambahSoal()
    {
        $bank = $this->input->post("bank", true);
        $nomor = $this->input->post("nomor", true);
        $jenis = $this->input->post("jenis", true);
        $data = ["bank_id" => $bank, "nomor_soal" => $nomor, "jenis" => $jenis, "tampilkan" => 0, "created_on" => time(), "updated_on" => time()];
        $insert = $this->db->insert("cbt_soal", $data);
        $this->output_json($insert);
    }
    public function importsoal($id)
    {
        $this->load->model("Master_model", "master");
        $this->load->model("Dropdown_model", "dropdown");
        $this->load->model("Dashboard_model", "dashboard");
        $this->load->model("Cbt_model", "cbt");
        $user = $this->ion_auth->user()->row();
        $setting = $this->dashboard->getSetting();
        $data = ["user" => $user, "judul" => "Import Bank Soal", "subjudul" => "Import Bank Soal"];
        $tp = $this->master->getTahunActive();
        $smt = $this->master->getSemesterActive();
        $data["tp"] = $this->dashboard->getTahun();
        $data["tp_active"] = $tp;
        $data["smt"] = $this->dashboard->getSemester();
        $data["smt_active"] = $smt;
        $data["setting"] = $setting;
        $data["bank"] = $this->cbt->getDataBankById($id);
        $data["jenis"] = $this->cbt->getAllJenisUjian();
        $data["jurusan"] = $this->cbt->getAllJurusan();
        $data["level"] = $this->dropdown->getAllLevel($setting->jenjang);
        $data["kelas"] = $this->dropdown->getAllKelas($tp->id_tp, $smt->id_smt);
        if ($this->ion_auth->is_admin()) {
            $data["profile"] = $this->dashboard->getProfileAdmin($user->id);
            $this->load->view("_templates/dashboard/_header", $data);
            $this->load->view("cbt/banksoal/import");
            $this->load->view("_templates/dashboard/_footer");
        } else {
            $data["guru"] = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
            $this->load->view("members/guru/templates/header", $data);
            $this->load->view("cbt/banksoal/import");
            $this->load->view("members/guru/templates/footer");
        }
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
            if ($sheetData[$i][0] != null) {
                $data[] = ["nama" => $sheetData[$i][1], "nip" => $sheetData[$i][2], "kode" => $sheetData[$i][3], "username" => $sheetData[$i][4], "password" => $sheetData[$i][5]];
            }
        }
        unlink($file);
        echo json_encode($data);
    }
    public function previewWord($id_bank)
    {
        $config["upload_path"] = "./uploads/import";
        $config["allowed_types"] = "docx";
        $config["max_size"] = 2048;
        $config["encrypt_name"] = true;
        $this->upload->initialize($config);
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
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadHTML($text);
        $images = $dom->getElementsByTagName("img");
        $numimg = 1;
        foreach ($images as $image) {
            $base64_image_string = $image->getAttribute("src");
            $splited = explode(",", substr($base64_image_string, 5), 2);
            $mime = $splited[0];
            $data = $splited[1];
            $mime_split_without_base64 = explode(";", $mime, 2);
            $mime_split = explode("/", $mime_split_without_base64[0], 2);
            if (count($mime_split) == 2) {
                $extension = $mime_split[1];
                if ($extension == "jpeg") {
                    $extension = "jpg";
                }
                $output_file = "img_" . $id_bank . date("YmdHis") . $numimg . "." . $extension;
            }
            file_put_contents("./uploads/bank_soal/" . $output_file, base64_decode($data));
            $image->setAttribute("src", "uploads/bank_soal/" . $output_file);
            $numimg++;
        }
        $newhtml = $dom->saveHTML();
        $dataInsert = json_decode(json_encode($newhtml));
        $result["pg"] = $dataInsert;
        $result["type"] = "html";
        $this->output_json($result);
    }
    public function import()
    {
        $this->load->model("Cbt_model", "cbt");
        $bank_id = $this->input->post("bank_id", true);
        $bank = $this->cbt->getDataBankById($bank_id);
        $input = $this->input->post("ganda");
        $str = preg_replace("\xef\xbb\xbf", '', $input);
        $obj = json_decode($str);
        $json = json_decode(preg_replace("/[\\x00-\\x1F\\x80-\\xFF]/", '', $input), true);
        $result["error"] = json_last_error_msg();
        $soal = json_decode(json_encode($json));
        $result["soal"] = $obj;
        $this->output_json($result);
    }
    public function export($bank_id)
    {
        $this->load->model("Cbt_model", "cbt");
        $bank = $this->cbt->getDataBankById($bank_id);
        $soal[] = json_decode(json_encode(["soal" => '', "opsi_a" => '', "opsi_b" => '', "opsi_c" => '', "opsi_d" => '', "opsi_e" => '', "jawaban" => '']));
        $list = $this->cbt->getAllSoalByBank($bank_id, "1");
        $soals = array_merge($soal, $list);
        $ess[] = json_decode(json_encode(["soal" => '', "jawaban" => '']));
        $listEss = $this->cbt->getAllSoalByBank($bank_id, "2");
        $essai = array_merge($ess, $listEss);
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();
        $header = array("size" => 10, "bold" => true);
        $arrHeader = ['', "NO", "SOAL", "JAWABAN A", "JAWABAN B", "JAWABAN C", "JAWABAN D", "JAWABAN E", "JAWABAN BENAR"];
        $cols = 8;
        $section->addText("I. PILIHAN GANDA", $header);
        $tableStyle = array("borderSize" => 6, "borderColor" => "000000");
        $phpWord->addTableStyle("tab style", $tableStyle);
        $table = $section->addTable("tab style");
        for ($r = 1; $r <= count($soals); $r++) {
            $soal = $soals[$r - 1];
            $arrVal = ['', '', isset($soal) ? $soal->soal : '', isset($soal) ? $soal->opsi_a : '', isset($soal) ? $soal->opsi_b : '', isset($soal) ? $soal->opsi_c : '', isset($soal) ? $soal->opsi_d : '', isset($soal) ? $soal->opsi_e : '', isset($soal) ? $soal->jawaban : ''];
            $table->addRow();
            for ($c = 1; $c <= $cols; $c++) {
                $width = 4000;
                $align = array("align" => "left", "space" => array("before" => 50, "after" => 50), "indentation" => array("left" => 100, "right" => 100));
                if ($c == 1) {
                    $align = array("align" => "center", "space" => array("before" => 50, "after" => 50), "indentation" => array("left" => 100, "right" => 100));
                    $width = 500;
                } elseif ($c == 2) {
                    $width = 8000;
                }
                $fontStyle = array("size" => 10, "bold" => false);
                $no = $r - 1;
                if ($r == 1) {
                    $no = "NO";
                    $align = array("align" => "center", "space" => array("before" => 50, "after" => 50), "indentation" => array("left" => 50, "right" => 50));
                    $fontStyle = array("size" => 10, "bold" => true);
                }
                if ($r == 1) {
                    if ($c == 1) {
                        $table->addCell($width)->addText($no, $fontStyle, $align);
                    } else {
                        $table->addCell($width)->addText($arrHeader[$c], $fontStyle, $align);
                    }
                } else {
                    if ($c == 1) {
                        $table->addCell($width)->addText($no, $fontStyle, $align);
                    } else {
                        $tagRemoved = strip_tags($arrVal[$c]);
                        $html = htmlspecialchars($tagRemoved);
                        $table->addCell($width)->addText($this->cleanString($html), $fontStyle, $align);
                    }
                }
            }
        }
        $section->addPageBreak();
        $section->addText("II. ESSAI", $header);
        $arrHeader = ['', "NO", "SOAL", "JAWABAN"];
        $cols = 3;
        $phpWord->addTableStyle("tab style", $tableStyle);
        $table = $section->addTable("tab style");
        for ($r = 1; $r <= count($essai); $r++) {
            $soal = $essai[$r - 1];
            $arrVal = ['', '', isset($soal) ? $soal->soal : '', isset($soal) ? $soal->jawaban : ''];
            $table->addRow();
            for ($c = 1; $c <= $cols; $c++) {
                $width = 4000;
                $align = array("align" => "left", "space" => array("before" => 50, "after" => 50), "indentation" => array("left" => 100, "right" => 100));
                if ($c == 1) {
                    $align = array("align" => "center", "space" => array("before" => 50, "after" => 50), "indentation" => array("left" => 100, "right" => 100));
                    $width = 500;
                } elseif ($c == 2) {
                    $width = 8000;
                }
                $fontStyle = array("size" => 10, "bold" => false);
                $no = $r - 1;
                if ($r == 1) {
                    $no = "NO";
                    $align = array("align" => "center", "space" => array("before" => 50, "after" => 50), "indentation" => array("left" => 50, "right" => 50));
                    $fontStyle = array("size" => 10, "bold" => true);
                }
                if ($r == 1) {
                    if ($c == 1) {
                        $table->addCell($width)->addText($no, $fontStyle, $align);
                    } else {
                        $table->addCell($width)->addText($arrHeader[$c], $fontStyle, $align);
                    }
                } else {
                    if ($c == 1) {
                        $table->addCell($width)->addText($no, $fontStyle, $align);
                    } else {
                        $tagRemoved = strip_tags($arrVal[$c]);
                        $html = htmlspecialchars($tagRemoved);
                        $table->addCell($width)->addText($this->cleanString($html), $fontStyle, $align);
                    }
                }
            }
        }
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, "Word2007");
        header("Content-Disposition: attachment; filename=Soal " . $bank->nama_mapel . ".docx");
        $objWriter->save("php://output");
    }
    public function getSoalSiswa($id_bank)
    {
        $this->load->model("Cbt_model", "cbt");
        $soals = $this->cbt->getAllSoalByBank($id_bank);
        foreach ($soals as $soal) {
            if (isset($soal->file)) {
                $soal->file = unserialize($soal->file);
            }
            if ($soal->jenis == "2") {
                $soal->jawaban = unserialize($soal->jawaban);
                $soal->opsi_a = unserialize($soal->opsi_a);
            } elseif ($soal->jenis == "3") {
                $soal->jawaban = unserialize($soal->jawaban);
            }
        }
        $data["soal"] = $soals;
        $this->output_json($data);
    }
    function innerXML($node)
    {
        $doc = $node->ownerDocument;
        $frag = $doc->createDocumentFragment();
        foreach ($node->childNodes as $child) {
            $frag->appendChild($child->cloneNode(TRUE));
        }
        return $doc->saveXML($frag);
    }
    public function file_config()
    {
        $allowed_type = ["image/jpeg", "image/jpg", "image/png", "image/gif", "audio/mpeg", "audio/mpg", "audio/mpeg3", "audio/mp3", "audio/x-wav", "audio/wave", "audio/wav", "video/mp4", "application/octet-stream"];
        $config["upload_path"] = "FCPATHuploads/bank_soal/";
        $config["allowed_types"] = "jpeg|jpg|png|gif|mpeg|mpg|mpeg3|mp3|wav|wave|mp4";
        $config["encrypt_name"] = TRUE;
        return $this->load->library("upload", $config);
    }
    public function validasi($jenis)
    {
        $this->form_validation->set_rules("soal", "Soal", "required");
        if ($jenis == 1) {
            $this->form_validation->set_rules("jawaban_pg", "Kunci Jawaban", "required");
        } elseif ($jenis == 2) {
            $this->form_validation->set_rules("jawaban2_a", "Kunci Jawaban", "required");
            $this->form_validation->set_rules("jawaban_benar_pg2[]", "Kunci Jawaban", "required");
        } elseif ($jenis == 3) {
            $this->form_validation->set_rules("jawaban[][]", "Kunci Jawaban", "required");
        } elseif ($jenis == 4) {
            $this->form_validation->set_rules("jawaban_isian", "Kunci Jawaban", "required");
        } else {
            $this->form_validation->set_rules("jawaban_essai", "Kunci Jawaban", "required");
        }
    }
    public function saveSoal()
    {
        $this->load->model("Master_model", "master");
        $this->load->model("Log_model", "logging");
        $method = $this->input->post("method", true);
        $jenis = $this->input->post("jenis", true);
        $bank_id = $this->input->post("bank_id", true);
        $nomor_soal = $this->input->post("nomor_soal", true);
        $soal = $this->input->post("soal", false);
        $this->validasi($jenis);
        $this->file_config();
        $data = ["bank_id" => $bank_id, "jenis" => $jenis, "nomor_soal" => $nomor_soal, "soal" => $soal];
        if ($jenis == 1) {
            $abjad = ["a", "b", "c", "d", "e"];
            foreach ($abjad as $abj) {
                $data["opsi_" . $abj] = $this->input->post("jawaban_" . $abj, false);
            }
            $data["jawaban"] = $this->input->post("jawaban_pg", true);
        } elseif ($jenis == 2) {
            $opsis = [];
            for ($i = 97; $i < 117; $i++) {
                $op = $this->input->post("jawaban2_" . chr($i), false);
                if ($op != null) {
                    $opsis[chr($i)] = $op;
                }
            }
            $data["opsi_a"] = serialize($opsis);
            $jawabans = [];
            $jwb_pg2 = count($this->input->post("jawaban_benar_pg2", true));
            for ($i = 0; $i <= $jwb_pg2; $i++) {
                $jwb = $this->input->post("jawaban_benar_pg2[" . $i . "]", true);
                array_push($jawabans, $jwb);
            }
            $data["jawaban"] = serialize($jawabans);
        } elseif ($jenis == 3) {
            $jawabans = $this->input->post("jawaban", false);
            for ($i = 0; $i < count($jawabans); $i++) {
                for ($j = 0; $j < count($jawabans[$i]); $j++) {
                    if ($j === 0) {
                        $jawabans[$i][$j] = $this->decode_data($jawabans[$i][$j], $bank_id, $jenis, $nomor_soal);
                    }
                }
            }
            $jwb_jodohkan = ["model" => $this->input->post("model", true), "type" => $this->input->post("type", true), "jawaban" => $jawabans];
            $data["jawaban"] = serialize($jwb_jodohkan);
        } elseif ($jenis == 4) {
            $data["jawaban"] = $this->input->post("jawaban_isian", true);
        } else {
            $data["jawaban"] = $this->input->post("jawaban_essai", false);
        }
        if ($this->form_validation->run() === FALSE) {
            $result["status"] = "error";
            $result["error"] = form_error();
        } elseif ($method === "add") {
            $data["created_on"] = time();
            $data["updated_on"] = time();
            $this->master->create("cbt_soal", $data);
            $result["status"] = "Soal berhasil dibuat";
            $this->logging->saveLog(3, "membuat soal");
        } elseif ($method === "edit") {
            $id_soal = $this->input->post("soal_id", true);
            $data["updated_on"] = time();
            $this->master->update("cbt_soal", $data, "id_soal", $id_soal);
            $result["status"] = "Soal berhasil diupdate";
            $this->logging->saveLog(4, "mengedit soal");
        } else {
            $result["status"] = "400 Method not found";
        }
        $this->output_json($result);
    }
    function base64_to_jpeg($base64_string, $output_file)
    {
        $ifp = fopen($output_file, "wb");
        $data = explode(",", $base64_string);
        fwrite($ifp, base64_decode($data[1]));
        fclose($ifp);
        return $output_file;
    }
    public function hapusSoal()
    {
        $this->load->model("Cbt_model", "cbt");
        $id_soal = $this->input->post("soal_id", true);
        $result = $this->cbt->getNomorSoalById($id_soal);
        $all_soal = $this->cbt->getNomorSoalByBankJenis($result->bank_id, $result->jenis);
        $nomor = $result->nomor_soal;
        $this->db->where("id_soal", $id_soal);
        $deleted = $this->db->delete("cbt_soal");
        if ($deleted) {
            $update = [];
            $nomor_baru = 1;
            foreach ($all_soal as $soal) {
                $update[] = ["id_soal" => $soal->id_soal, "nomor_soal" => $nomor_baru];
                $nomor_baru++;
            }
            if (count($update) > 0) {
                $this->db->update_batch("cbt_soal", $update, "id_soal");
            }
        }
        $this->output_json($deleted);
    }
    function uploadFile()
    {
        $this->load->model("Cbt_model", "cbt");
        $id_soal = $this->input->get("id_soal", true);
        $soal = $this->cbt->getFileSoalById($id_soal);
        $files = $soal == null || $soal->file == null ? [] : unserialize($soal->file);
        if (isset($_FILES["file_uploads"]["name"])) {
            $nama_file_asal = $_FILES["file_uploads"]["name"];
            $kode_file = $id_soal . "_" . time();
            $config["upload_path"] = "./uploads/bank_soal/";
            $config["allowed_types"] = "mpeg|mpg|mpeg3|mp3|wav|wave|mp4|avi";
            $config["file_name"] = $kode_file;
            $this->upload->initialize($config);
            if (!$this->upload->do_upload("file_uploads")) {
                $data["status"] = false;
                $src = '';
                $filename = '';
                $data["src"] = $this->upload->display_errors();
            } else {
                $file = $this->upload->data();
                $ext = pathinfo($file["file_name"], PATHINFO_EXTENSION);
                $src = "uploads/bank_soal/" . $kode_file . "." . $ext;
                $data["src"] = $src;
                $data["filename"] = $nama_file_asal;
                $data["status"] = true;
                $type = $_FILES["file_uploads"]["type"];
                $data["type"] = $type;
                $data["size"] = $_FILES["file_uploads"]["size"];
                $data["soal"] = $soal;
                $files[] = ["file_name" => $nama_file_asal, "alias" => $kode_file, "src" => $src, "type" => $type];
                $this->db->set("file", serialize($files));
                $this->db->where("id_soal", $id_soal);
                $this->db->update("cbt_soal");
            }
        }
        $data["files"] = $files;
        $this->output_json($data);
    }
    function upload_image()
    {
        $status = false;
        if (isset($_FILES["file"]["name"])) {
            $config["upload_path"] = "./uploads/bank_soal/";
            $config["allowed_types"] = "jpg|jpeg|png|gif|mp3|ogg|wav|mp4|mpeg|webm";
            $config["file_name"] = "file_" . date("YmdHis");
            $this->upload->initialize($config);
            if (!$this->upload->do_upload("file")) {
                $this->upload->display_errors();
                $status = false;
            } else {
                $uploaded = $this->upload->data();
                $data["filename"] = "uploads/bank_soal/" . $uploaded["file_name"];
                $status = true;
            }
        }
        $data["status"] = $status;
        $this->output_json($data);
    }
    function uploadSoalImage()
    {
        $name = $this->input->post("name");
        $src = $this->input->post("src");
        str_replace("%2B", "+", $src);
        $data["status"] = file_put_contents("./uploads/bank_soal/" . $name, base64_decode($src));
        $data["src"] = "uploads/bank_soal/" . $name;
        $this->output_json($data);
    }
    function deleteFile()
    {
        $src = $this->input->post("src");
        $file_name = str_replace(base_url(), '', $src);
        if (unlink($file_name)) {
            echo "File Delete Successfully";
        }
    }
    function cleanString($text)
    {
        $text = preg_replace("/[\xc3\xa1\xc3\xa0\xc3\xa2\xc3\xa3\xc2\xaa\xc3\xa4]/u", "a", $text);
        $text = preg_replace("/[\xc3\x81\xc3\x80\xc3\x82\xc3\x83\xc3\x84]/u", "A", $text);
        $text = preg_replace("/[\xc3\x8d\xc3\x8c\xc3\x8e\xc3\x8f]/u", "I", $text);
        $text = preg_replace("/[\xc3\xad\xc3\xac\xc3\xae\xc3\xaf]/u", "i", $text);
        $text = preg_replace("/[\xc3\xa9\xc3\xa8\xc3\xaa\xc3\xab]/u", "e", $text);
        $text = preg_replace("/[\xc3\x89\xc3\x88\xc3\x8a\xc3\x8b]/u", "E", $text);
        $text = preg_replace("/[\xc3\xb3\xc3\xb2\xc3\xb4\xc3\xb5\xc2\xba\xc3\xb6]/u", "o", $text);
        $text = preg_replace("/[\xc3\x93\xc3\x92\xc3\x94\xc3\x95\xc3\x96]/u", "O", $text);
        $text = preg_replace("/[\xc3\xba\xc3\xb9\xc3\xbb\xc3\xbc]/u", "u", $text);
        $text = preg_replace("/[\xc3\x9a\xc3\x99\xc3\x9b\xc3\x9c]/u", "U", $text);
        $text = preg_replace("/[\xe2\x80\x99\xe2\x80\x98\xe2\x80\xb9\xe2\x80\xba\xe2\x80\x9a]/u", "'", $text);
        $text = preg_replace("/[\xe2\x80\x9c\xe2\x80\x9d\xc2\xab\xc2\xbb\xe2\x80\x9e]/u", "\"", $text);
        $text = str_replace("\xe2\x80\x93", "-", $text);
        $text = str_replace(" ", " ", $text);
        $text = str_replace("\xc3\xa7", "c", $text);
        $text = str_replace("\xc3\x87", "C", $text);
        $text = str_replace("\xc3\xb1", "n", $text);
        $text = str_replace("\xc3\x91", "N", $text);
        $trans = get_html_translation_table(HTML_ENTITIES);
        $trans["\x82"] = "&sbquo;";
        $trans["\x83"] = "&fnof;";
        $trans["\x84"] = "&bdquo;";
        $trans["\x85"] = "&hellip;";
        $trans["\x86"] = "&dagger;";
        $trans["\x87"] = "&Dagger;";
        $trans["\x88"] = "&circ;";
        $trans["\x89"] = "&permil;";
        $trans["\x8a"] = "&Scaron;";
        $trans["\x8b"] = "&lsaquo;";
        $trans["\x8c"] = "&OElig;";
        $trans["\x91"] = "&lsquo;";
        $trans["\x92"] = "&rsquo;";
        $trans["\x93"] = "&ldquo;";
        $trans["\x94"] = "&rdquo;";
        $trans["\x95"] = "&bull;";
        $trans["\x96"] = "&ndash;";
        $trans["\x97"] = "&mdash;";
        $trans["\x98"] = "&tilde;";
        $trans["\x99"] = "&trade;";
        $trans["\x9a"] = "&scaron;";
        $trans["\x9b"] = "&rsaquo;";
        $trans["\x9c"] = "&oelig;";
        $trans["\x9f"] = "&Yuml;";
        $trans["euro"] = "&euro;";
        ksort($trans);
        foreach ($trans as $k => $v) {
            $text = str_replace($v, $k, $text);
        }
        $text = strip_tags($text);
        $text = html_entity_decode($text);
        $text = preg_replace("/[^(\\x20-\\x7F)]*/", '', $text);
        $targets = array("\\r\\n", "\\n", "\\r", "\\t");
        $results = array(" ", " ", " ", '');
        $text = str_replace($targets, $results, $text);
        return $text;
    }
    function cleanHTML($html)
    {
        $doc = new DOMDocument();
        $doc->loadHTML($html, "LIBXML_N____O_OOVL]ED");
        return $doc->saveHTML();
    }
    function addNamespaces($xml)
    {
        $root = "<w:wordDocument\r\n        xmlns:w=\"http://schemas.microsoft.com/office/word/2003/wordml\"\r\n        xmlns:wx=\"http://schemas.microsoft.com/office/word/2003/auxHint\"\r\n        xmlns:o=\"urn:schemas-microsoft-com:office:office\">";
        $root .= $xml;
        $root .= "</w:wordDocument>";
        return $root;
    }
    function doImport()
    {
        $this->load->model("Cbt_model", "cbt");
        $bank_id = $this->input->post("id_bank", true);
        $string = $this->input->post("data", false);
        $bank = $this->cbt->getDataBankById($bank_id);
        $jml_seharusnya = $bank->tampil_pg + $bank->tampil_kompleks + $bank->tampil_jodohkan + $bank->tampil_isian + $bank->tampil_esai;
        $json = json_decode($string);
        $datas = [];
        $jml = [];
        foreach ($json as $jenis => $values) {
            $data_soal = [];
            foreach ($values as $val) {
                if (isset($val->NO)) {
                    $no = trim($val->NO);
                    if (isset($val->SOAL) && $val->SOAL != '') {
                        $data_soal[$no]["soal"] = $val->SOAL;
                    }
                    if ($jenis == "1") {
                        if (isset($val->OPSI)) {
                            $data_soal[$no]["opsi"][strtoupper($val->OPSI)] = $val->JAWABAN;
                            if (isset($val->KUNCI) && strtolower($val->KUNCI) == "v") {
                                $data_soal[$no]["kunci"][strtolower($val->KUNCI)] = strtoupper($val->OPSI);
                            }
                        }
                    } elseif ($jenis == "2") {
                        if (isset($val->OPSI)) {
                            $data_soal[$no]["opsi"][strtoupper($val->OPSI)] = $val->JAWABAN;
                            if (isset($val->KUNCI) && strtolower($val->KUNCI) == "v") {
                                $data_soal[$no]["kunci"][strtoupper($val->OPSI)] = strtolower($val->KUNCI);
                            }
                        }
                    } elseif ($jenis == "3") {
                        if (isset($val->KD_BARIS)) {
                            $data_soal[$no]["baris"][strtoupper($val->KD_BARIS)] = $val->BARIS;
                            if (isset($val->KUNCI)) {
                                $data_soal[$no]["kunci"][strtoupper($val->KD_KUNCI)] = strtoupper($val->KUNCI);
                            }
                        }
                        if (isset($val->KD_KOLOM)) {
                            $data_soal[$no]["kolom"][strtoupper($val->KD_KOLOM)] = $val->KOLOM;
                        }
                    } else {
                        if (isset($val->KUNCI)) {
                            $data_soal[$no]["kunci"] = $val->KUNCI;
                        }
                    }
                }
            }
            $datas[$jenis] = $data_soal;
        }
        $data_insert = [];
        foreach ($datas as $jenis => $keys) {
            foreach ($keys as $no => $v) {
                $isi_soal = isset($v["soal"]) ? $v["soal"] : '';
                if ($isi_soal != '') {
                    $insert = ["jenis" => $jenis, "nomor_soal" => $no, "soal" => $isi_soal, "file" => serialize([])];
                    if ($jenis == "1") {
                        $insert["opsi_a"] = isset($v["opsi"]) && isset($v["opsi"]["A"]) ? $v["opsi"]["A"] : '';
                        $insert["opsi_b"] = isset($v["opsi"]) && isset($v["opsi"]["B"]) ? $v["opsi"]["B"] : '';
                        $insert["opsi_c"] = isset($v["opsi"]) && isset($v["opsi"]["C"]) ? $v["opsi"]["C"] : '';
                        $insert["opsi_d"] = isset($v["opsi"]) && isset($v["opsi"]["D"]) ? $v["opsi"]["D"] : '';
                        $insert["opsi_e"] = isset($v["opsi"]) && isset($v["opsi"]["E"]) ? $v["opsi"]["E"] : '';
                        $insert["jawaban"] = isset($v["kunci"]) && isset($v["kunci"]["v"]) ? $v["kunci"]["v"] : '';
                    } elseif ($jenis == "2") {
                        $opsis = [];
                        $kuncis = [];
                        if (isset($v["opsi"])) {
                            foreach ($v["opsi"] as $opsi => $jawaban) {
                                $opsis[strtolower($opsi)] = $jawaban;
                            }
                        }
                        if (isset($v["kunci"])) {
                            foreach ($v["kunci"] as $kunci => $jawaban) {
                                if ($jawaban == "v") {
                                    $kuncis[] = strtolower($kunci);
                                }
                            }
                        }
                        $insert["opsi_a"] = serialize($opsis);
                        $insert["jawaban"] = serialize($kuncis);
                    } elseif ($jenis == "3") {
                        $baris = [];
                        $kolom = [];
                        $header = [];
                        array_push($header, "#");
                        $arrKol = [];
                        foreach ($v["kolom"] as $kd_kol => $kol) {
                            $kolom[$kd_kol] = $kol;
                            if ($kol != '') {
                                array_push($header, $kol);
                            }
                            foreach ($v["kunci"] as $kd_bar => $kd_kol) {
                                if ($kd_kol != '') {
                                    $arrKol[$kd_bar] = explode(",", $kd_kol);
                                }
                            }
                        }
                        array_push($baris, $header);
                        $jwbnBaris = [];
                        foreach ($v["baris"] as $kd_bar => $bar) {
                            $jwbn = [];
                            if ($kd_bar != '') {
                                array_push($jwbn, $bar);
                            }
                            foreach ($kolom as $kk => $val) {
                                if ($kd_bar != '' && $val != '' && isset($arrKol[$kd_bar])) {
                                    $match = in_array($kk, $arrKol[$kd_bar]);
                                    array_push($jwbn, $match ? "1" : "0");
                                }
                            }
                            if (count($jwbn) > 0) {
                                array_push($baris, $jwbn);
                            }
                            if ($kd_bar != '') {
                                array_shift($jwbn);
                                $jwbnBaris[$kd_bar] = $jwbn;
                            }
                        }
                        $types = [];
                        foreach ($jwbnBaris as $brs => $jml) {
                            $jmlType = array_count_values($jml);
                            if (isset($jmlType[1]) && $jmlType[1] > 1) {
                                array_push($types, "checkbox");
                            }
                        }
                        $type = count($types) > 0 ? "1" : "2";
                        $jml_baris = count($baris);
                        $jml_kolom = count($baris[0]);
                        $jwb_jodohkan = ["model" => $jml_baris == $jml_kolom ? "1" : "2", "type" => $type, "jawaban" => $baris];
                        $insert["jawaban"] = serialize($jwb_jodohkan);
                    } elseif ($jenis == "4") {
                        if (isset($v["kunci"])) {
                            $insert["jawaban"] = strip_tags($v["kunci"]);
                        }
                    } else {
                        if (isset($v["kunci"])) {
                            $insert["jawaban"] = $v["kunci"];
                        }
                    }
                    $data_insert[] = $insert;
                }
            }
        }
        $inserted = [];
        $total_soal = count($data_insert);
        foreach ($data_insert as $dins) {
            $inserted[] = ["bank_id" => $bank_id, "jenis" => $dins["jenis"], "nomor_soal" => $dins["nomor_soal"], "soal" => $dins["soal"], "deskripsi" => '', "kesulitan" => "8", "timer" => "0", "timer_menit" => "0", "file" => $dins["file"], "tampilkan" => "0", "created_on" => time(), "updated_on" => time(), "opsi_a" => isset($dins["opsi_a"]) ? $dins["opsi_a"] : '', "opsi_b" => isset($dins["opsi_b"]) ? $dins["opsi_b"] : '', "opsi_c" => isset($dins["opsi_c"]) ? $dins["opsi_c"] : '', "opsi_d" => isset($dins["opsi_d"]) ? $dins["opsi_d"] : '', "opsi_e" => isset($dins["opsi_e"]) ? $dins["opsi_e"] : '', "jawaban" => $dins["jawaban"], "tampilkan" => $total_soal == $jml_seharusnya ? "1" : "0"];
        }
        $data["data_insert"] = $inserted;
        $data["total"] = count($inserted);
        $data["json"] = $json;
        if (count($inserted) > 0) {
            $this->db->where("bank_id", $bank_id);
            if ($this->db->delete("cbt_soal")) {
                $data["insert"] = $this->db->insert_batch("cbt_soal", $inserted);
            }
        } else {
            $data["insert"] = 0;
        }
        $this->output_json($data);
    }
    function uploadSoal()
    {
        $this->load->model("Cbt_model", "cbt");
        $bank_id = $this->input->post("id_bank", true);
        $datas = $this->input->post("soal", false);
        $bank = $this->cbt->getDataBankById($bank_id);
        $jml_spg1 = 0;
        $jml_spg2 = 0;
        $jml_sjod = 0;
        $jml_siss = 0;
        $jml_sess = 0;
        $data_insert = [];
        foreach ($datas as $jenis => $nomor) {
            foreach ($nomor as $no => $v) {
                $isi_soal = isset($v["soal"]) ? $this->decode_data(rawurldecode($v["soal"]), $bank_id, $jenis, $no) : '';
                if ($isi_soal != '') {
                    $insert = ["jenis" => $jenis, "nomor_soal" => $no, "soal" => $isi_soal, "file" => serialize([])];
                    if ($jenis == 1) {
                        $insert["opsi_a"] = isset($v["opsi"]) && isset($v["opsi"]["A"]) ? $this->decode_data(rawurldecode($v["opsi"]["A"]), $bank_id, $jenis, $no) : '';
                        $insert["opsi_b"] = isset($v["opsi"]) && isset($v["opsi"]["B"]) ? $this->decode_data(rawurldecode($v["opsi"]["B"]), $bank_id, $jenis, $no) : '';
                        $insert["opsi_c"] = isset($v["opsi"]) && isset($v["opsi"]["C"]) ? $this->decode_data(rawurldecode($v["opsi"]["C"]), $bank_id, $jenis, $no) : '';
                        $insert["opsi_d"] = isset($v["opsi"]) && isset($v["opsi"]["D"]) ? $this->decode_data(rawurldecode($v["opsi"]["D"]), $bank_id, $jenis, $no) : '';
                        $insert["opsi_e"] = isset($v["opsi"]) && isset($v["opsi"]["E"]) ? $this->decode_data(rawurldecode($v["opsi"]["E"]), $bank_id, $jenis, $no) : '';
                        $insert["jawaban"] = isset($v["kunci"]) && count($v["kunci"]) > 0 ? $v["kunci"][0] : '';
                        $jml_spg1++;
                    } elseif ($jenis == "2") {
                        $opsis = [];
                        $kuncis = [];
                        if (isset($v["opsi"])) {
                            foreach ($v["opsi"] as $opsi => $jawaban) {
                                $opsis[strtolower($opsi)] = $this->decode_data(rawurldecode($jawaban), $bank_id, $jenis, $no);
                            }
                        }
                        if (isset($v["kunci"])) {
                            foreach ($v["kunci"] as $jawaban) {
                                array_push($kuncis, strtolower($jawaban));
                            }
                        }
                        $insert["opsi_a"] = serialize($opsis);
                        $insert["jawaban"] = serialize($kuncis);
                        $jml_spg2++;
                    } elseif ($jenis == "3") {
                        $baris = [];
                        $kolom = [];
                        $header = [];
                        array_push($header, "#");
                        $arrKol = [];
                        foreach ($v["kolom"] as $kd_kol => $kol) {
                            $kolom[$kd_kol] = $kol;
                            if ($kol != '') {
                                array_push($header, $this->decode_data(rawurldecode($kol), $bank_id, $jenis, $no));
                            }
                            foreach ($v["kunci"] as $kd_bar => $kd_kol) {
                                if ($kd_kol != '') {
                                    $arrKol[$kd_bar] = explode(",", $kd_kol);
                                }
                            }
                        }
                        array_push($baris, $header);
                        $jwbnBaris = [];
                        foreach ($v["baris"] as $kd_bar => $bar) {
                            $jwbn = [];
                            if ($kd_bar != '') {
                                array_push($jwbn, $this->decode_data(rawurldecode($bar), $bank_id, $jenis, $no));
                            }
                            foreach ($kolom as $kk => $val) {
                                if ($kd_bar != '' && $val != '' && isset($arrKol[$kd_bar])) {
                                    $match = in_array($kk, $arrKol[$kd_bar]);
                                    array_push($jwbn, $match ? "1" : "0");
                                }
                            }
                            if (count($jwbn) > 0) {
                                array_push($baris, $jwbn);
                            }
                            if ($kd_bar != '') {
                                array_shift($jwbn);
                                $jwbnBaris[$kd_bar] = $jwbn;
                            }
                        }
                        $types = [];
                        foreach ($jwbnBaris as $brs => $jml) {
                            $jmlType = array_count_values($jml);
                            if (isset($jmlType[1]) && $jmlType[1] > 1) {
                                array_push($types, "checkbox");
                            }
                        }
                        $type = count($types) > 0 ? "1" : "2";
                        $jml_baris = count($baris);
                        $jml_kolom = count($baris[0]);
                        $jwb_jodohkan = ["model" => $jml_baris == $jml_kolom ? "1" : "2", "type" => $type, "jawaban" => $baris];
                        $insert["jawaban"] = serialize($jwb_jodohkan);
                        $jml_sjod++;
                    } elseif ($jenis == "4") {
                        if (isset($v["kunci"])) {
                            $insert["jawaban"] = strip_tags($this->decode_data(rawurldecode($v["kunci"]), $bank_id, $jenis, $no));
                        }
                        $jml_siss++;
                    } else {
                        if (isset($v["kunci"])) {
                            $insert["jawaban"] = $this->decode_data(rawurldecode($v["kunci"]), $bank_id, $jenis, $no);
                        }
                        $jml_sess++;
                    }
                    $data_insert[] = $insert;
                }
            }
        }
        $tmpl["1"] = $jml_spg1 == $bank->tampil_pg ? "1" : "0";
        $tmpl["2"] = $jml_spg2 == $bank->tampil_kompleks ? "1" : "0";
        $tmpl["3"] = $jml_sjod == $bank->tampil_jodohkan ? "1" : "0";
        $tmpl["4"] = $jml_siss == $bank->tampil_isian ? "1" : "0";
        $tmpl["5"] = $jml_sess == $bank->tampil_esai ? "1" : "0";
        $inserted = [];
        $total_soal = count($data_insert);
        foreach ($data_insert as $dins) {
            $inserted[] = ["bank_id" => $bank_id, "jenis" => $dins["jenis"], "nomor_soal" => $dins["nomor_soal"], "soal" => $dins["soal"], "deskripsi" => '', "kesulitan" => "8", "timer" => "0", "timer_menit" => "0", "file" => $dins["file"], "created_on" => time(), "updated_on" => time(), "opsi_a" => isset($dins["opsi_a"]) ? $dins["opsi_a"] : '', "opsi_b" => isset($dins["opsi_b"]) ? $dins["opsi_b"] : '', "opsi_c" => isset($dins["opsi_c"]) ? $dins["opsi_c"] : '', "opsi_d" => isset($dins["opsi_d"]) ? $dins["opsi_d"] : '', "opsi_e" => isset($dins["opsi_e"]) ? $dins["opsi_e"] : '', "jawaban" => $dins["jawaban"], "tampilkan" => $tmpl[$dins["jenis"]]];
        }
        $data["data_insert"] = $inserted;
        $data["total"] = count($inserted);
        if (count($inserted) > 0) {
            $this->db->where("bank_id", $bank_id);
            if ($this->db->delete("cbt_soal")) {
                $data["insert"] = $this->db->insert_batch("cbt_soal", $inserted);
            }
            $status_soal = $tmpl["1"] == "1" && $tmpl["2"] == "1" && $tmpl["3"] == "1" && $tmpl["4"] == "1" && $tmpl["5"] == "1" ? "1" : "0";
            $this->db->set("status_soal", $status_soal);
            $this->db->where("id_bank", $bank_id);
            $soal_updated = $this->db->update("cbt_bank_soal");
            $data["selesai"] = $soal_updated;
        } else {
            $data["insert"] = 0;
        }
        $this->output_json($data);
    }
    function decode_data($html, $id_bank, $jenis, $nomor)
    {
        if (empty($html)) {
            return "";
        }
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadHTML("<?xml encoding=\"UTF-8\">" . $html, "LIBXML_HTML_NOMMVL]ED");
        $images = $dom->getElementsByTagName("img");
        if ($images) {
            $numimg = 1;
            foreach ($images as $image) {
                $src = $image->getAttribute("src");
                if (substr($src, 0, 5) === "data:") {
                    $base64_image_string = $image->getAttribute("src");
                    $splited = explode(",", substr($base64_image_string, 5), 2);
                    $mime = $splited[0];
                    $data = $splited[1];
                    $mime_split_without_base64 = explode(";", $mime, 2);
                    $mime_split = explode("/", $mime_split_without_base64[0], 2);
                    if (count($mime_split) == 2) {
                        $extension = $mime_split[1];
                        if ($extension == "jpeg") {
                            $extension = "jpg";
                        }
                        try {
                            $bytes = random_bytes(10);
                        } catch (Exception $e) {
                        }
                        $output_file = "img_" . $id_bank . $jenis . $nomor . "_" . bin2hex($bytes) . "." . $extension;
                        file_put_contents("./uploads/bank_soal/" . $output_file, base64_decode($data));
                        $image->setAttribute("src", "uploads/bank_soal/" . $output_file);
                        $numimg++;
                    }
                } else {
                    $image->setAttribute("src", str_replace(base_url(), '', $src));
                }
            }
            $res = $dom->saveHTML();
            return str_replace("<?xml encoding=\"UTF-8\">", '', $res);
        }
        return $html;
    }
}
