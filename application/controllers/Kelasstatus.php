<?php

/*   ________________________________________
    |                 GarudaCBT              |
    |    https://github.com/garudacbt/cbt    |
    |________________________________________|
*/
class Kelasstatus extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->ion_auth->logged_in()) {
            redirect("auth");
        } else {
            if (!$this->ion_auth->is_admin() && !$this->ion_auth->in_group("guru")) {
                show_error("Hanya Administrator yang diberi hak untuk mengakses halaman ini, <a href=\"" . base_url("dashboard") . "\">Kembali ke menu awal</a>", 403, "Akses Terlarang");
            }
        }
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
        $this->load->model("Dashboard_model", "dashboard");
        $this->load->model("Dropdown_model", "dropdown");
        $this->load->model("Kelas_model", "kelas");
        $user = $this->ion_auth->user()->row();
        $data = ["user" => $user, "judul" => "Nilai Harian Siswa", "subjudul" => "Nilai", "setting" => $this->dashboard->getSetting()];
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $data["tp"] = $this->dashboard->getTahun();
        $data["tp_active"] = $tp;
        $data["smt"] = $this->dashboard->getSemester();
        $data["smt_active"] = $smt;
        if ($this->ion_auth->is_admin()) {
            $data["profile"] = $this->dashboard->getProfileAdmin($user->id);
            $guru = $this->dropdown->getAllGuru();
            $data["gurus"] = $guru;
            $data["kelas"] = $this->dropdown->getAllKelas($tp->id_tp, $smt->id_smt);
            $data["mapels"] = $this->dropdown->getAllMapel();
            $this->load->view("_templates/dashboard/_header", $data);
            $this->load->view("kelas/status/data");
            $this->load->view("_templates/dashboard/_footer");
        } else {
            $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
            $nguru[$guru->id_guru] = $guru->nama_guru;
            $data["guru"] = $guru;
            $data["gurus"] = $nguru;
            $data["id_guru"] = $guru->id_guru;
            $mapel_guru = $this->kelas->getGuruMapelKelas($guru->id_guru, $tp->id_tp, $smt->id_smt);
            $mapel = json_decode(json_encode(unserialize($mapel_guru->mapel_kelas)));
            $arrMapel = [];
            $arrKelas = [];
            if ($mapel != null) {
                foreach ($mapel as $m) {
                    $arrMapel[$m->id_mapel] = $m->nama_mapel;
                    foreach ($m->kelas_mapel as $kls) {
                        $arrKelas[$kls->kelas] = $this->dropdown->getNamaKelasById($tp->id_tp, $smt->id_smt, $kls->kelas);
                    }
                }
            }
            $arrId = [];
            if ($mapel != null) {
                foreach ($mapel[0]->kelas_mapel as $id_mapel) {
                    array_push($arrId, $id_mapel->kelas);
                }
            }
            $data["mapel"] = $mapel;
            $data["mapels"] = $arrMapel;
            $data["kelas"] = $arrKelas;
            $this->load->view("members/guru/templates/header", $data);
            $this->load->view("kelas/status/data");
            $this->load->view("members/guru/templates/footer");
        }
    }
    public function getMateriGuru()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $this->load->model("Kelas_model", "kelas");
        $id_guru = $this->input->get("id", true);
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $materi = $this->kelas->getAllKodeMateri($tp->id_tp, $smt->id_smt, $id_guru);
        $arrKelasMateri = [];
        $arrKelasTugas = [];
        foreach ($materi as $m) {
            $kode_mapel = $m->kode_mapel == null ? "--" : $m->kode_mapel;
            if ($m->jenis == "1") {
                $arrKelasMateri[] = ["id_materi" => $m->id_materi, "id_kjm" => $m->id_kjm, "jadwal" => $m->jadwal_materi, "kode" => $m->kode_materi, "mapel" => $kode_mapel, "kelas" => unserialize($m->materi_kelas)];
            } else {
                $arrKelasTugas[] = ["id_materi" => $m->id_materi, "id_kjm" => $m->id_kjm, "jadwal" => $m->jadwal_materi, "kode" => $m->kode_materi, "mapel" => $kode_mapel, "kelas" => unserialize($m->materi_kelas)];
            }
        }
        $this->output_json(array("materi" => $arrKelasMateri, "tugas" => $arrKelasTugas));
    }
    public function getMateriMapel()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $this->load->model("Kelas_model", "kelas");
        $id_mapel = $this->input->get("id", true);
        $id_guru = $this->input->get("id_guru", true);
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $materi = $this->kelas->getKodeMateriMapel($tp->id_tp, $smt->id_smt, $id_mapel, $id_guru);
        $arrKelasMateri = [];
        $arrKelasTugas = [];
        $arrKelas = [];
        foreach ($materi as $m) {
            $kode_mapel = $m->kode_mapel == null ? "--" : $m->kode_mapel;
            if ($m->jenis == "1") {
                $arrMateri = ["id_materi" => $m->id_materi, "id_kjm" => $m->id_kjm, "jadwal" => $m->jadwal_materi, "kode" => $m->kode_materi, "mapel" => $kode_mapel, "guru" => $m->nama_guru];
                if (isset($arrKelasMateri[$m->id_kelas])) {
                    array_push($arrKelasMateri[$m->id_kelas], $arrMateri);
                } else {
                    $arrKelasMateri[$m->id_kelas] = [];
                    array_push($arrKelasMateri[$m->id_kelas], $arrMateri);
                }
            } else {
                $arrTugas = ["id_materi" => $m->id_materi, "id_kjm" => $m->id_kjm, "jadwal" => $m->jadwal_materi, "kode" => $m->kode_materi, "mapel" => $kode_mapel, "guru" => $m->nama_guru];
                if (isset($arrKelasTugas[$m->id_kelas])) {
                    array_push($arrKelasMateri[$m->id_kelas], $arrTugas);
                } else {
                    $arrKelasTugas[$m->id_kelas] = [];
                    array_push($arrKelasTugas[$m->id_kelas], $arrTugas);
                }
            }
            if (isset($arrKelas[$m->jenis])) {
                if (!in_array($m->id_kelas, $arrKelas[$m->jenis])) {
                    array_push($arrKelas[$m->jenis], $m->id_kelas);
                }
            } else {
                $arrKelas[$m->jenis] = [];
                array_push($arrKelas[$m->jenis], $m->id_kelas);
            }
        }
        $this->output_json(array("materi" => $arrKelasMateri, "tugas" => $arrKelasTugas, "kelas" => $arrKelas));
    }
    public function loadStatus()
    {
        $this->load->model("Master_model", "master");
        $this->load->model("Dashboard_model", "dashboard");
        $this->load->model("Kelas_model", "kelas");
        $label = $this->input->post("label", true);
        $id_kelas = $this->input->post("id_kelas", true);
        $id_kjm = $this->input->post("id_kjm", true);
        $id_tp = $this->master->getTahunActive()->id_tp;
        $id_smt = $this->master->getSemesterActive()->id_smt;
        $jenis = $label === "Materi" ? "1" : "2";
        $siswa = $this->kelas->getKelasSiswa($id_kelas, $id_tp, $id_smt);
        $logs = $this->kelas->getStatusMateriSiswa($id_kjm);
        $info = $this->dashboard->getJadwalKbm($id_tp, $id_smt, $id_kelas);
        if ($info != null) {
            $info->istirahat = unserialize($info->istirahat);
        }
        $materi = $this->kelas->getMateriKelasSiswa($id_kjm, $jenis);
        $detail = [];
        $jam_materi = [];
        if ($materi) {
            $kelas_materi = $this->kelas->getNamaKelasById([$id_kelas]);
            $numday = date("N", strtotime($materi->jadwal_materi));
            $jadwals = $this->kelas->loadJadwalSiswaHariIni($id_tp, $id_smt, $id_kelas, $numday, false);
            $key = array_search($materi->id_mapel, array_column($jadwals, "id_mapel"));
            $jadwal = $jadwals[$key];
            $ist = json_decode(json_encode($info->istirahat));
            $arrDur = [];
            $arrIst = [];
            foreach ($ist as $istirahat) {
                $arrIst[] = $istirahat->ist;
                $arrDur[$istirahat->ist] = $istirahat->dur;
            }
            $jamMulai = new DateTime($info->kbm_jam_mulai);
            $jamSampai = new DateTime($info->kbm_jam_mulai);
            $jam_mapel = [];
            for ($i = 0; $i < $info->kbm_jml_mapel_hari; $i++) {
                $jamke = $i + 1;
                if (in_array($jamke, $arrIst)) {
                    try {
                        $jamSampai->add(new DateInterval("PT" . $arrDur[$jamke] . "M"));
                        $jam_mapel[$jamke] = ["dari" => $jamMulai->format("H:i"), "sampai" => $jamSampai->format("H:i"), "tgl" => $materi->jadwal_materi];
                        $jamMulai->add(new DateInterval("PT" . $arrDur[$jamke] . "M"));
                    } catch (Exception $e) {
                    }
                } else {
                    try {
                        $jamSampai->add(new DateInterval("PT" . $info->kbm_jam_pel . "M"));
                        $jam_mapel[$jamke] = ["dari" => $jamMulai->format("H:i"), "sampai" => $jamSampai->format("H:i"), "tgl" => $materi->jadwal_materi];
                        $jamMulai->add(new DateInterval("PT" . $info->kbm_jam_pel . "M"));
                    } catch (Exception $e) {
                    }
                }
            }
            $jam_materi = $jam_mapel[$jadwal->jam_ke];
            $detail = ["mapel" => $materi->nama_mapel, "judul" => $materi->judul_materi, "guru" => $materi->nama_guru, "kelas" => $kelas_materi[$id_kelas], "jam_ke" => $jadwal->jam_ke, "waktu" => $jam_materi];
        }
        $log = [];
        foreach ($siswa as $s) {
            $mulai = isset($logs[$s->id_siswa]) ? $logs[$s->id_siswa]->log_time : null;
            $selesai = isset($logs[$s->id_siswa]) ? $logs[$s->id_siswa]->finish_time : null;
            $diff = null;
            if ($selesai) {
                $jam_jadwal = new DateTime(date("Y-m-d H:i:s", strtotime($materi->jadwal_materi . " " . $jam_materi["sampai"])));
                $jam_siswa = new DateTime(date("Y-m-d H:i:s", strtotime($mulai)));
                $interval = $jam_siswa->diff($jam_jadwal);
                $minutes = $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;
                $time_jadwal = strtotime($materi->jadwal_materi . " " . $jam_materi["sampai"]);
                $time_siswa = strtotime($mulai);
                $diff = ["days" => $interval->days, "hari" => $interval->d, "jam" => $interval->h, "menit" => $interval->i, "detik" => $interval->s, "total" => $minutes, "interval" => (int) $interval->format("%r%H:%i:%s"), "terlambat" => $time_siswa - $time_jadwal > 0];
            }
            $log[$s->id_siswa] = ["nama" => $s->nama, "nis" => $s->nis, "kelas" => $s->nama_kelas, "login" => $this->kelas->getLoginSiswa($s->username), "mulai" => $mulai, "selesai" => $selesai, "text" => isset($logs[$s->id_siswa]) ? $logs[$s->id_siswa]->text : '', "nilai" => isset($logs[$s->id_siswa]) ? $logs[$s->id_siswa]->nilai : '', "catatan" => isset($logs[$s->id_siswa]) ? $logs[$s->id_siswa]->catatan : '', "jam_ke" => isset($logs[$s->id_siswa]) ? $logs[$s->id_siswa]->jam_ke : null, "jadwal_materi" => isset($logs[$s->id_siswa]) ? $logs[$s->id_siswa]->jadwal_materi : null, "file" => isset($logs[$s->id_siswa]) && $logs[$s->id_siswa]->file != null ? unserialize($logs[$s->id_siswa]->file) : [], "diff" => $diff, "j_materi" => $jam_materi["sampai"]];
        }
        $this->output_json(["log" => $log, "jadwal" => $info, "materi" => $materi, "detail" => $detail]);
    }
    public function saveNilai()
    {
        $method = $this->input->post("method", true);
        $label = $this->input->post("label", true);
        $id_log = $this->input->post("id_log", true);
        $nilai = $this->input->post("nilai", true);
        $catatan = $this->input->post("catatan", true);
        $insert = ["nilai" => $nilai, "catatan" => $catatan];
        $this->db->where("id_log", $id_log);
        $q = $this->db->get("log_materi");
        if ($q->num_rows() > 0) {
            $this->db->where("id_log", $id_log);
            $update = $this->db->update("log_materi", $insert);
        } else {
            $this->db->set("id_log", $id_log);
            $update = $this->db->insert("log_materi", $insert);
        }
        $this->output_json($update);
    }
}
