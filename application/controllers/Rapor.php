<?php

/*   ________________________________________
    |                 GarudaCBT              |
    |    https://github.com/garudacbt/cbt    |
    |________________________________________|
*/
use alhimik1986\PhpExcelTemplator\PhpExcelTemplator;
use PhpOffice\PhpSpreadsheet\IOFactory;
class Rapor extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->ion_auth->logged_in()) {
            redirect("auth");
            goto t0ZBL;
        }
        if (!(!$this->ion_auth->is_admin() && !$this->ion_auth->in_group("guru"))) {
            goto K6mXk;
        }
        show_error("Hanya Administrator yang diberi hak untuk mengakses halaman ini, <a href=\"" . base_url("dashboard") . "\">Kembali ke menu awal</a>", 403, "Akses Terlarang");
        K6mXk:
        t0ZBL:
        $this->load->dbforge();
        $this->load->database();
        $this->load->library(["datatables", "form_validation"]);
        $this->load->model("Rapor_model", "rapor");
        $this->load->model("Kelas_model", "kelas");
        $this->load->model("Dropdown_model", "dropdown");
        $this->load->model("Master_model", "master");
        $this->form_validation->set_error_delimiters('', '');
    }
    public function output_json($data, $encode = true)
    {
        if (!$encode) {
            goto WvjIV;
        }
        $data = json_encode($data);
        WvjIV:
        $this->output->set_content_type("application/json")->set_output($data);
    }
    public function index()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $no_update = $this->db->field_exists("nip_kepsek", "rapor_admin_setting");
        if ($no_update) {
            goto t2ZvN;
        }
        $field = array("nip_kepsek" => array("type" => "int", "constraint" => 1, "default" => 0), "nip_walikelas" => array("type" => "int", "constraint" => 1, "default" => 0));
        $this->dbforge->add_column("rapor_admin_setting", $field);
        t2ZvN:
        $user = $this->ion_auth->user()->row();
        $data = ["user" => $user, "judul" => "Pengaturan Rapor", "subjudul" => "Pengaturan Rapor", "setting" => $this->dashboard->getSetting()];
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $data["tp"] = $this->dashboard->getTahun();
        $data["tp_active"] = $tp;
        $data["smt"] = $this->dashboard->getSemester();
        $data["smt_active"] = $smt;
        $data["profile"] = $this->dashboard->getProfileAdmin($user->id);
        $data["rapor"] = $this->rapor->getRaporSetting($tp->id_tp, $smt->id_smt);
        $data["kkm_drop"] = ["Tidak", "Ya"];
        if ($this->ion_auth->is_admin()) {
            $this->load->view("_templates/dashboard/_header", $data);
            $this->load->view("setting/rapor");
            $this->load->view("_templates/dashboard/_footer");
            goto WoD1g;
        }
        redirect("rapor/raporkkm");
        WoD1g:
    }
    public function saveRaporAdmin()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $input = ["id_setting" => $tp->id_tp . $smt->id_smt, "id_tp" => $tp->id_tp, "id_smt" => $smt->id_smt, "tgl_rapor_pts" => $this->input->post("tgl_rapor_pts", true), "nip_kepsek" => $this->input->post("nip_kepsek", true), "nip_walikelas" => $this->input->post("nip_walikelas", true), "tgl_rapor_akhir" => $this->input->post("tgl_rapor_akhir", true), "tgl_rapor_kelas_akhir" => $this->input->post("tgl_rapor_kelas_akhir", true), "kkm_tunggal" => $this->input->post("kkm_tunggal", true), "kkm" => $this->input->post("kkm", true), "bobot_ph" => $this->input->post("bobot_ph", true), "bobot_pts" => $this->input->post("bobot_pts", true), "bobot_pas" => $this->input->post("bobot_pas", true)];
        $update = $this->db->replace("rapor_admin_setting", $input);
        $data["status"] = $update;
        $this->output_json($data);
    }
    public function raporkkm()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $user = $this->ion_auth->user()->row();
        $data = ["user" => $user, "judul" => "KKM dan Bobot", "subjudul" => "Input KKM dan Bobot Nilai", "setting" => $this->dashboard->getSetting()];
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $data["tp"] = $this->dashboard->getTahun();
        $data["tp_active"] = $tp;
        $data["smt"] = $this->dashboard->getSemester();
        $data["smt_active"] = $smt;
        $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        $mapel_guru = $this->kelas->getGuruMapelKelas($guru->id_guru, $tp->id_tp, $smt->id_smt);
        $mapel = $mapel_guru->mapel_kelas != null ? json_decode(json_encode(unserialize($mapel_guru->mapel_kelas))) : [];
        $arrMapel = [];
        $arrKelas = [];
        $kelases = $this->kelas->getKelasList($tp->id_tp, $smt->id_smt);
        foreach ($mapel as $m) {
            $arrMapel[$m->id_mapel] = $m->nama_mapel;
            foreach ($m->kelas_mapel as $kls) {
                $key_kelas = array_search($kls->kelas, array_column($kelases, "id_kelas"));
                if (!($key_kelas !== false)) {
                    goto PuVfR;
                }
                $arrKelas[$m->id_mapel][] = ["id_kelas" => $kls->kelas, "nama_kelas" => $kelases[$key_kelas]->nama_kelas];
                PuVfR:
            }
        }
        $data["guru"] = $guru;
        $data["mapel"] = $arrMapel;
        $data["kelas"] = $arrKelas;
        $ekstra = $mapel_guru->ekstra_kelas != null ? json_decode(json_encode(unserialize($mapel_guru->ekstra_kelas))) : [];
        $arrEkstra = [];
        $arrKelasEkstra = [];
        if (!(count($ekstra) > 0)) {
            goto kkWEv;
        }
        foreach ($ekstra as $m) {
            $arrEkstra[$m->id_ekstra] = $m->nama_ekstra;
            foreach ($m->kelas_ekstra as $kls) {
                $key_kelas = array_search($kls->kelas, array_column($kelases, "id_kelas"));
                if (!($key_kelas !== false)) {
                    goto Hxqhi;
                }
                $arrKelasEkstra[$m->id_ekstra][] = ["id_kelas" => $kls->kelas, "nama_kelas" => $kelases[$key_kelas]->nama_kelas];
                Hxqhi:
            }
        }
        kkWEv:
        $data["ekstra"] = $arrEkstra;
        $data["kelas_ekstra"] = $arrKelasEkstra;
        $this->load->view("members/guru/templates/header", $data);
        $this->load->view("members/guru/rapor/kkm/data");
        $this->load->view("members/guru/templates/footer");
    }
    public function datakkm($mapel, $kelas)
    {
        $this->load->model("Dashboard_model", "dashboard");
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $kkm = '';
        if (!($kelas != null)) {
            goto OdVT0;
        }
        $kkm = $this->rapor->getKkm($mapel . $kelas . $tp->id_tp . $smt->id_smt . "1");
        OdVT0:
        $data["mapel"] = $mapel;
        $data["kelas"] = $kelas;
        $data["kkm"] = $kkm;
        $data["tp"] = $tp->id_tp;
        $data["smt"] = $smt->id_smt;
        $data["setting"] = $this->rapor->getRaporSetting($tp->id_tp, $smt->id_smt);
        $this->output_json($data);
    }
    public function datakkmEkstra($ekstra, $kelas)
    {
        $this->load->model("Dashboard_model", "dashboard");
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $kkm = '';
        if (!($kelas != null)) {
            goto hf0qF;
        }
        $kkm = $this->rapor->getKkm($ekstra . $kelas . $tp->id_tp . $smt->id_smt . "2");
        hf0qF:
        $data["ekstra"] = $ekstra;
        $data["kelas"] = $kelas;
        $data["kkm"] = $kkm;
        $data["tp"] = $tp->id_tp;
        $data["smt"] = $smt->id_smt;
        $data["setting"] = $this->rapor->getRaporSetting($tp->id_tp, $smt->id_smt);
        $this->output_json($data);
    }
    public function saveKkm()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $input = ["id_kkm" => $this->input->post("id_kkm", true), "id_tp" => $tp->id_tp, "id_smt" => $smt->id_smt, "bobot_ph" => $this->input->post("bobot_ph", true), "bobot_pts" => $this->input->post("bobot_pts", true), "bobot_pas" => $this->input->post("bobot_pas", true), "kkm" => $this->input->post("kkm", true), "beban_jam" => $this->input->post("beban", true), "jenis" => $this->input->post("jenis_kkm", true), "id_kelas" => $this->input->post("id_kelas", true), "id_mapel" => $this->input->post("id_mapel", true)];
        $update = $this->db->replace("rapor_kkm", $input);
        $data["status"] = $update;
        $this->output_json($data);
    }
    public function raporkikd()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $user = $this->ion_auth->user()->row();
        $data = ["user" => $user, "judul" => "Indikator KD", "subjudul" => "Ringkasan Materi Penilaian", "setting" => $this->dashboard->getSetting()];
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $data["tp"] = $this->dashboard->getTahun();
        $data["tp_active"] = $tp;
        $data["smt"] = $this->dashboard->getSemester();
        $data["smt_active"] = $smt;
        $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        $mapel_guru = $this->kelas->getGuruMapelKelas($guru->id_guru, $tp->id_tp, $smt->id_smt);
        $mapel = json_decode(json_encode(unserialize($mapel_guru->mapel_kelas)));
        $arrMapel = [];
        $arrKelas = [];
        $kelases = $this->kelas->getKelasList($tp->id_tp, $smt->id_smt);
        if (!($mapel != null)) {
            goto bNYJv;
        }
        foreach ($mapel as $m) {
            $arrMapel[$m->id_mapel] = $m->nama_mapel;
            foreach ($m->kelas_mapel as $kls) {
                $key_kelas = array_search($kls->kelas, array_column($kelases, "id_kelas"));
                if (!($key_kelas !== false)) {
                    goto FXyRg;
                }
                $arrKelas[$m->id_mapel][] = ["id_kelas" => $kls->kelas, "nama_kelas" => $kelases[$key_kelas]->nama_kelas];
                FXyRg:
            }
        }
        bNYJv:
        $data["guru"] = $guru;
        $data["mapel"] = $arrMapel;
        $data["kelas"] = $arrKelas;
        $this->load->view("members/guru/templates/header", $data);
        $this->load->view("members/guru/rapor/kikd/data");
        $this->load->view("members/guru/templates/footer");
    }
    public function datakikd($mapel, $kelas)
    {
        $this->load->model("Dashboard_model", "dashboard");
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $kikds = $this->rapor->getKikdMapelKelas($mapel, $kelas, $tp->id_tp, $smt->id_smt);
        $arrKiKd[] = [];
        if (!($kelas != null)) {
            goto t1gxp;
        }
        $aspek = ["1", "2"];
        foreach ($aspek as $asp) {
            $i = 0;
            WudrP:
            if (!($i < 8)) {
            }
            $no = $i + 1;
            $key_ki = array_search($mapel . $kelas . $asp . $no, array_column($kikds, "id_kikd"));
            if ($key_ki !== false) {
                $arrKiKd[$asp][$mapel . $kelas . $asp . $no] = $kikds[$key_ki];
                goto tOG6o;
            }
            $arrKiKd[$asp][$mapel . $kelas . $asp . $no] = ["materi_kikd" => ''];
            tOG6o:
            $i++;
            goto WudrP;
        }
        t1gxp:
        $data["mapel"] = $mapel;
        $data["kelas"] = $kelas;
        $data["kikd"] = $arrKiKd;
        $this->output_json($data);
    }
    public function saveKikd()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $sjson = $this->input->post("materi", true);
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $updated = false;
        foreach ((array) $sjson as $aspek => $mapel_kelas) {
            foreach ($mapel_kelas as $idmk => $kikd) {
                foreach ($kikd as $id => $materi) {
                    $input = ["id_kikd" => $id, "id_mapel_kelas" => $idmk, "aspek" => $aspek, "id_tp" => $tp->id_tp, "id_smt" => $smt->id_smt, "materi_kikd" => $materi];
                    $updated = $this->db->replace("rapor_kikd", $input);
                }
            }
        }
        $data["status"] = $updated;
        $data["json"] = $sjson;
        $this->output_json($data);
    }
    public function raporNilai()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $user = $this->ion_auth->user()->row();
        $data = ["user" => $user, "judul" => "Input Nilai", "subjudul" => "Input Nilai Rapor", "setting" => $this->dashboard->getSetting()];
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $data["tp"] = $this->dashboard->getTahun();
        $data["tp_active"] = $tp;
        $data["smt"] = $this->dashboard->getSemester();
        $data["smt_active"] = $smt;
        $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        $mapel_guru = $this->kelas->getGuruMapelKelas($guru->id_guru, $tp->id_tp, $smt->id_smt);
        $mapel = $mapel_guru->mapel_kelas != null ? json_decode(json_encode(unserialize($mapel_guru->mapel_kelas))) : [];
        $siswas = [];
        $arrMapel = [];
        $arrKelasMapel = [];
        $levelsMapel = [];
        $harian = [];
        $pts = [];
        $pas = [];
        foreach ($mapel as $m) {
            $arrMapel[$m->id_mapel] = $m->nama_mapel;
            foreach ($m->kelas_mapel as $kls) {
                $kelas_guru = $this->kelas->get_one($kls->kelas);
                if (!($kelas_guru != null)) {
                    goto MrmqJ;
                }
                $levelsMapel[] = $kelas_guru->level_id;
                $arrKelasMapel[$m->id_mapel][] = ["id_kelas" => $kelas_guru->id_kelas, "level" => $kelas_guru->level_id, "nama_kelas" => $kelas_guru->nama_kelas];
                $siswas[$m->id_mapel][$kelas_guru->nama_kelas] = count($this->kelas->getKelasSiswa($kelas_guru->id_kelas, $tp->id_tp, $smt->id_smt));
                $harian[$m->id_mapel][$kelas_guru->nama_kelas] = $this->rapor->cekNilaiHarianKelas($m->id_mapel, $kelas_guru->id_kelas, $tp->id_tp, $smt->id_smt);
                $pts[$m->id_mapel][$kelas_guru->nama_kelas] = $this->rapor->cekNilaiPtsKelas($m->id_mapel, $kelas_guru->id_kelas, $tp->id_tp, $smt->id_smt);
                $pas[$m->id_mapel][$kelas_guru->nama_kelas] = $this->rapor->cekNilaiAkhirKelas($m->id_mapel, $kelas_guru->id_kelas, $tp->id_tp, $smt->id_smt);
                MrmqJ:
            }
        }
        $data["mapel"] = $arrMapel;
        $data["kelas_mapel"] = $arrKelasMapel;
        $data["level"] = array_unique($levelsMapel);
        $data["siswas"] = $siswas;
        $data["harian"] = $harian;
        $data["pts"] = $pts;
        $data["pas"] = $pas;
        $ekstra = $mapel_guru->ekstra_kelas != null ? json_decode(json_encode(unserialize($mapel_guru->ekstra_kelas))) : [];
        $arrEkstra = [];
        $arrKelasEkstra = [];
        $ektras = [];
        $siswae = [];
        if (!(count($ekstra) > 0)) {
            goto c8L00;
        }
        foreach ($ekstra as $m) {
            $arrEkstra[$m->id_ekstra] = $m->nama_ekstra;
            foreach ($m->kelas_ekstra as $kls) {
                $kelas_guru = $this->kelas->get_one($kls->kelas);
                if (!($kelas_guru != null)) {
                    goto dwJ3o;
                }
                $arrKelasEkstra[$m->id_ekstra][] = ["id_kelas" => $kelas_guru->id_kelas, "level" => $kelas_guru->level_id, "nama_kelas" => $kelas_guru->nama_kelas];
                $siswae[$m->id_ekstra][$kelas_guru->nama_kelas] = count($this->kelas->getKelasSiswa($kelas_guru->id_kelas, $tp->id_tp, $smt->id_smt));
                $ektras[$m->id_ekstra][$kelas_guru->nama_kelas] = $this->rapor->cekNilaiEkstraKelas($m->id_ekstra, $kelas_guru->id_kelas, $tp->id_tp, $smt->id_smt);
                dwJ3o:
            }
        }
        c8L00:
        $data["ekstras"] = $ektras;
        $data["siswae"] = $siswae;
        $data["ekstra"] = $arrEkstra;
        $data["kelas_ekstra"] = $arrKelasEkstra;
        $data["guru"] = $guru;
        $this->load->view("members/guru/templates/header", $data);
        $this->load->view("members/guru/rapor/nilai/data");
        $this->load->view("members/guru/templates/footer");
    }
    public function raporNilaiGuru($filter = null, $id_mapel = null)
    {
        $this->load->model("Dashboard_model", "dashboard");
        $user = $this->ion_auth->user()->row();
        $data = ["user" => $user, "judul" => "Semua Nilai", "subjudul" => "Semua Nilai Rapor", "setting" => $this->dashboard->getSetting()];
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $data["tp"] = $this->dashboard->getTahun();
        $data["tp_active"] = $tp;
        $data["smt"] = $this->dashboard->getSemester();
        $data["smt_active"] = $smt;
        $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        $data["guru"] = $guru;
        $ret[''] = "Pilih Mapel";
        $dropMapel = $this->dropdown->getAllMapel();
        $data["mapel"] = $ret + $dropMapel;
        $ret[''] = "Pilih Eskul";
        $dropEskul = $this->dropdown->getAllEkskul();
        $data["ekstra"] = $ret + $dropEskul;
        $data["filter"] = ['' => "Filter berdasarkan", "1" => "Mata Pelajaran", "2" => "Ekstrakurikuler"];
        $data["ekstra_selected"] = $id_mapel;
        $data["mapel_selected"] = $id_mapel;
        $data["filter_selected"] = $filter;
        $jabatan_guru = $this->master->getGuruMapel($tp->id_tp, $smt->id_smt);
        foreach ($jabatan_guru as $jabatan) {
            $jabatan->mapel_kelas = $jabatan->mapel_kelas == null ? [] : unserialize($jabatan->mapel_kelas);
            $jabatan->ekstra_kelas = $jabatan->ekstra_kelas == null ? [] : unserialize($jabatan->ekstra_kelas);
        }
        if (!($id_mapel != null)) {
            goto dybjQ;
        }
        $setting = $this->rapor->getRaporSetting($tp->id_tp, $smt->id_smt);
        if ($setting->kkm_tunggal == "1") {
            $kkm = $setting;
            $kkm_ekstra = $setting;
            goto XM7Kc;
        }
        $kkm = $this->rapor->getKkm($id_mapel . $guru->wali_kelas . $tp->id_tp . $smt->id_smt . "1");
        $kkm_ekstra = $this->rapor->getKkm($id_mapel . $guru->wali_kelas . $tp->id_tp . $smt->id_smt . "2");
        XM7Kc:
        $siswas = $this->kelas->getKelasSiswa($guru->wali_kelas, $tp->id_tp, $smt->id_smt);
        $nilai = [];
        $arrKiKd[] = [];
        if (!($guru->wali_kelas != null)) {
            goto n0dkD;
        }
        $aspek = ["1", "2"];
        foreach ($aspek as $asp) {
            $i = 0;
            WgK2A:
            if (!($i < 8)) {
            }
            $no = $i + 1;
            $arrKiKd[$asp][$id_mapel . $guru->wali_kelas . $asp . $no] = $this->rapor->getKikdMapel($id_mapel . $guru->wali_kelas . $asp . $no, $tp->id_tp, $smt->id_smt);
            $i++;
            goto WgK2A;
        }
        n0dkD:
        if ($filter == "1") {
            $guru_mapel = '';
            foreach ($jabatan_guru as $jab) {
                foreach ($jab->mapel_kelas as $mk) {
                    if (!($mk["id_mapel"] == $id_mapel)) {
                        goto C34OP;
                    }
                    foreach ($mk["kelas_mapel"] as $km) {
                        if (!($km["kelas"] == $guru->wali_kelas)) {
                            goto chypu;
                        }
                        $guru_mapel = $jab->nama_guru;
                        chypu:
                    }
                    C34OP:
                }
            }
            $i = 0;
            s6h06:
            if (!($i < count($siswas))) {
                goto cpTMH;
            }
            $siswa = $siswas[$i];
            $dummyNilai = ["p1" => '', "p2" => '', "p3" => '', "p4" => '', "p5" => '', "p6" => '', "p7" => '', "p8" => '', "p_rata_rata" => '', "p_predikat" => "=", "p_deskripsi" => '', "k1" => '', "k2" => '', "k3" => '', "k4" => '', "k5" => '', "k6" => '', "k7" => '', "k8" => '', "k_rata_rata" => '', "k_predikat" => '', "k_deskripsi" => ''];
            $ns = $this->rapor->getNilaiHarianKelas($id_mapel, $guru->wali_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt);
            $nilai[$siswa->id_siswa] = $ns == null ? json_decode(json_encode($dummyNilai)) : $ns;
            $i++;
            goto s6h06;
        }
        $guru_mapel = '';
        foreach ($jabatan_guru as $jab) {
            foreach ($jab->ekstra_kelas as $mk) {
                if (!($mk["id_ekstra"] == $id_mapel)) {
                    goto y1aSw;
                }
                foreach ($mk["kelas_ekstra"] as $km) {
                    if (!($km["kelas"] == $guru->wali_kelas)) {
                        goto UQ5qI;
                    }
                    $guru_mapel = $jab->nama_guru;
                    UQ5qI:
                }
                y1aSw:
            }
        }
        $dummyEkstra = ["deskripsi" => '', "nilai" => '', "predikat" => ''];
        $i = 0;
        BWuT8:
        if (!($i < count($siswas))) {
            cpTMH:
            $data["siswa"] = $siswas;
            $data["nilai"] = $nilai;
            $data["kkm"] = $kkm;
            $data["kkm_ekstra"] = $kkm_ekstra;
            $data["guru_mapel"] = $guru_mapel;
            dybjQ:
            $this->load->view("members/guru/templates/header", $data);
            $this->load->view("members/guru/rapor/nilai/nilaiguru");
            $this->load->view("members/guru/templates/footer");
            // [PHPDeobfuscator] Implied return
            return;
        }
        $siswa = $siswas[$i];
        $ne = $this->rapor->getEkstraKelas($id_mapel, $siswa->id_siswa, $tp->id_tp, $smt->id_smt);
        $nilai[$siswa->id_siswa] = $ne == null ? json_decode(json_encode($dummyEkstra)) : $ne;
        $i++;
        goto BWuT8;
    }
    public function raporCekNilai($filter = null, $id_mapel = null)
    {
        $this->load->model("Dashboard_model", "dashboard");
        $user = $this->ion_auth->user()->row();
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        $mapel_guru = $this->kelas->getGuruMapelKelas($guru->id_guru, $tp->id_tp, $smt->id_smt);
        $mapels = json_decode(json_encode(unserialize($mapel_guru->mapel_kelas)));
        $data = ["user" => $user, "judul" => "Semua Nilai", "subjudul" => "Semua Nilai Rapor", "setting" => $this->dashboard->getSetting(), "guru" => $guru];
        $data["tp"] = $this->dashboard->getTahun();
        $data["tp_active"] = $tp;
        $data["smt"] = $this->dashboard->getSemester();
        $data["smt_active"] = $smt;
        $ret[''] = "Pilih Mapel";
        $dropMapel = $this->dropdown->getAllMapel();
        $data["mapel"] = $ret + $dropMapel;
        $ret[''] = "Pilih Eskul";
        $dropEskul = $this->dropdown->getAllEkskul();
        $data["ekstra"] = $ret + $dropEskul;
        $data["filter"] = ['' => "Filter berdasarkan", "1" => "Mata Pelajaran", "2" => "Ekstrakurikuler"];
        $data["ekstra_selected"] = $id_mapel;
        $data["mapel_selected"] = $id_mapel;
        $data["filter_selected"] = $filter;
        $jabatan_guru = $this->master->getGuruMapel($tp->id_tp, $smt->id_smt);
        foreach ($jabatan_guru as $jabatan) {
            $jabatan->mapel_kelas = $jabatan->mapel_kelas == null ? [] : unserialize($jabatan->mapel_kelas);
            $jabatan->ekstra_kelas = $jabatan->ekstra_kelas == null ? [] : unserialize($jabatan->ekstra_kelas);
        }
        if (!($id_mapel != null)) {
            goto wUNAw;
        }
        $setting = $this->rapor->getRaporSetting($tp->id_tp, $smt->id_smt);
        if ($setting->kkm_tunggal == "1") {
            $kkm = $setting;
            goto SUZ4d;
        }
        $jenis = $filter == "1" ? "1" : "2";
        $kkm = $this->rapor->getKkm($id_mapel . $guru->wali_kelas . $tp->id_tp . $smt->id_smt . $jenis);
        SUZ4d:
        $siswas = $this->kelas->getKelasSiswa($guru->wali_kelas, $tp->id_tp, $smt->id_smt);
        $nilai = [];
        $arrKiKd[] = [];
        if (!($guru->wali_kelas != null)) {
            goto ty7Xm;
        }
        $aspek = ["1", "2"];
        foreach ($aspek as $asp) {
            $i = 0;
            sxPVj:
            if (!($i < 8)) {
            }
            $no = $i + 1;
            $arrKiKd[$asp][$id_mapel . $guru->wali_kelas . $asp . $no] = $this->rapor->getKikdMapel($id_mapel . $guru->wali_kelas . $asp . $no, $tp->id_tp, $smt->id_smt);
            $i++;
            goto sxPVj;
        }
        ty7Xm:
        if ($filter == "1") {
            $guru_mapel = '';
            foreach ($jabatan_guru as $jab) {
                foreach ($jab->mapel_kelas as $mk) {
                    if (!($mk["id_mapel"] == $id_mapel)) {
                        goto efHwP;
                    }
                    foreach ($mk["kelas_mapel"] as $km) {
                        if (!($km["kelas"] == $guru->wali_kelas)) {
                            goto XXean;
                        }
                        $guru_mapel = $jab->nama_guru;
                        XXean:
                    }
                    efHwP:
                }
            }
            $i = 0;
            IQpE5:
            if (!($i < count($siswas))) {
                goto CCMkg;
            }
            $siswa = $siswas[$i];
            $dummyNilai = ["p1" => '', "p2" => '', "p3" => '', "p4" => '', "p5" => '', "p6" => '', "p7" => '', "p8" => '', "p_rata_rata" => '', "p_predikat" => "=", "p_deskripsi" => '', "k1" => '', "k2" => '', "k3" => '', "k4" => '', "k5" => '', "k6" => '', "k7" => '', "k8" => '', "k_rata_rata" => '', "k_predikat" => '', "k_deskripsi" => ''];
            $ns = $this->rapor->getNilaiHarianKelas($id_mapel, $guru->wali_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt);
            $nilai[$siswa->id_siswa] = $ns == null ? json_decode(json_encode($dummyNilai)) : $ns;
            $i++;
            goto IQpE5;
        }
        $guru_mapel = '';
        foreach ($jabatan_guru as $jab) {
            foreach ($jab->ekstra_kelas as $mk) {
                if (!($mk["id_ekstra"] == $id_mapel)) {
                    goto BrSlq;
                }
                foreach ($mk["kelas_ekstra"] as $km) {
                    if (!($km["kelas"] == $guru->wali_kelas)) {
                        goto TJffq;
                    }
                    $guru_mapel = $jab->nama_guru;
                    TJffq:
                }
                BrSlq:
            }
        }
        $dummyEkstra = ["deskripsi" => '', "nilai" => '', "predikat" => ''];
        $i = 0;
        fYKXn:
        if (!($i < count($siswas))) {
            CCMkg:
            $data["siswa"] = $siswas;
            $data["nilai"] = $nilai;
            $data["kkm"] = $kkm;
            $data["guru_mapel"] = $guru_mapel;
            wUNAw:
            $this->load->view("members/guru/templates/header", $data);
            $this->load->view("members/guru/rapor/nilai/periksa");
            $this->load->view("members/guru/templates/footer");
            // [PHPDeobfuscator] Implied return
            return;
        }
        $siswa = $siswas[$i];
        $ne = $this->rapor->getEkstraKelas($id_mapel, $siswa->id_siswa, $tp->id_tp, $smt->id_smt);
        $nilai[$siswa->id_siswa] = $ne == null ? json_decode(json_encode($dummyEkstra)) : $ne;
        $i++;
        goto fYKXn;
    }
    public function inputHarian($id_mapel, $id_kelas)
    {
        $this->load->model("Dashboard_model", "dashboard");
        $user = $this->ion_auth->user()->row();
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        $mapel_guru = $this->kelas->getGuruMapelKelas($guru->id_guru, $tp->id_tp, $smt->id_smt);
        $mapels = json_decode(json_encode(unserialize($mapel_guru->mapel_kelas)));
        $mapel = '';
        $kelas = [];
        foreach ($mapels as $m) {
            if (!($m->id_mapel === $id_mapel)) {
                goto ZvtoB;
            }
            $mapel = ["id_mapel" => $m->id_mapel, "nama_mapel" => $m->nama_mapel];
            ZvtoB:
            foreach ($m->kelas_mapel as $kls) {
                if (!($kls->kelas === $id_kelas)) {
                    goto Z6Osz;
                }
                $kelas = ["id_kelas" => $kls->kelas, "nama_kelas" => $this->dropdown->getNamaKelasById($tp->id_tp, $smt->id_smt, $kls->kelas)];
                Z6Osz:
            }
        }
        $siswas = $this->kelas->getKelasSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
        $nilai = [];
        $i = 0;
        ifHAN:
        if (!($i < count($siswas))) {
            $setting = $this->rapor->getRaporSetting($tp->id_tp, $smt->id_smt);
            $kkm = null;
            if (!($setting != null)) {
                goto Dvaeb;
            }
            if ($setting->kkm_tunggal == "1") {
                $kkm = $setting;
                goto PPLBd;
            }
            $kkm = $this->rapor->getKkm($id_mapel . $id_kelas . $tp->id_tp . $smt->id_smt . "1");
            PPLBd:
            Dvaeb:
            $arrKiKd[] = [];
            if (!($id_kelas != null)) {
                goto EuBGa;
            }
            $aspek = ["1", "2"];
            foreach ($aspek as $asp) {
                $i = 0;
                FXDP0:
                if (!($i < 8)) {
                }
                $no = $i + 1;
                $r = $this->rapor->getKikdMapel($id_mapel . $id_kelas . $asp . $no, $tp->id_tp, $smt->id_smt);
                if (!($r == null)) {
                    goto H9sak;
                }
                $r = $this->rapor->getKikdMapel($id_mapel . $id_kelas . $asp . $no, $tp->id_tp - 1, $smt->id_smt);
                H9sak:
                $arrKiKd[$asp][$id_mapel . $id_kelas . $asp . $no] = $r;
                $i++;
                goto FXDP0;
            }
            EuBGa:
            $data = ["user" => $user, "judul" => "Nilai Harian Kelas ", "subjudul" => "Input Nilai Harian Mapel ", "setting" => $this->dashboard->getSetting(), "guru" => $guru, "mapel" => $mapel, "kelas" => $kelas, "siswa" => $siswas, "nilai" => $nilai, "kkm" => $kkm];
            $data["tp"] = $this->dashboard->getTahun();
            $data["tp_active"] = $tp;
            $data["smt"] = $this->dashboard->getSemester();
            $data["smt_active"] = $smt;
            $data["kikd"] = $arrKiKd;
            $data["setting_rapor"] = $setting;
            $this->load->view("members/guru/templates/header", $data);
            $this->load->view("members/guru/rapor/nilai/harian");
            $this->load->view("members/guru/templates/footer");
            // [PHPDeobfuscator] Implied return
            return;
        }
        $siswa = $siswas[$i];
        $dummyNilai = ["p1" => '', "p2" => '', "p3" => '', "p4" => '', "p5" => '', "p6" => '', "p7" => '', "p8" => '', "p_rata_rata" => '', "p_predikat" => "=", "p_deskripsi" => '', "k1" => '', "k2" => '', "k3" => '', "k4" => '', "k5" => '', "k6" => '', "k7" => '', "k8" => '', "k_rata_rata" => '', "k_predikat" => '', "k_deskripsi" => ''];
        $ns = $this->rapor->getNilaiHarianKelas($id_mapel, $id_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt);
        $nilai[$siswa->id_siswa] = $ns == null ? $dummyNilai : $ns;
        $i++;
        goto ifHAN;
    }
    public function downloadTemplateHarian($id_mapel, $id_kelas)
    {
        $this->load->model("Dashboard_model", "dashboard");
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $siswas = $this->kelas->getKelasSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
        $nilais = [];
        $i = 0;
        jKxdz:
        if (!($i < count($siswas))) {
            $kelas = $this->kelas->getNamaKelasById([$id_kelas]);
            $mapel = $this->master->getMapelById($id_mapel, true);
            $template = "./uploads/import/format/template_harian.xlsx";
            $fileName = "Nilai_Harian " . $mapel->kode . " " . $kelas[$id_kelas] . ".xlsx";
            $no = [];
            $nisn = [];
            $nama = [];
            $p1 = [];
            $p2 = [];
            $p3 = [];
            $p4 = [];
            $p5 = [];
            $p6 = [];
            $p7 = [];
            $p8 = [];
            $k1 = [];
            $k2 = [];
            $k3 = [];
            $k4 = [];
            $k5 = [];
            $k6 = [];
            $k7 = [];
            $k8 = [];
            $i = 0;
            R8ozO:
            if (!($i < count($siswas))) {
                $kikds = $this->rapor->getKikdMapelKelas($id_mapel, $id_kelas, $tp->id_tp, $smt->id_smt);
                $no_kik = [];
                $kode_kik = [];
                $isi_kik = [];
                $no_kip = [];
                $kode_kip = [];
                $isi_kip = [];
                foreach ($kikds as $ki) {
                    if ($ki->aspek == 1) {
                        $nn = substr($ki->id_kikd, -1);
                        $no_kip[] = $nn;
                        $kode_kip[] = "P" . $nn;
                        $isi_kip[] = $ki->materi_kikd;
                        goto EevB2;
                    }
                    $nn = substr($ki->id_kikd, -1);
                    $no_kik[] = $nn;
                    $kode_kik[] = "K" . $nn;
                    $isi_kik[] = $ki->materi_kikd;
                    EevB2:
                }
                if (!(count($no_kip) == 0)) {
                    goto xyXss;
                }
                $no_kip[] = 1;
                $kode_kip[] = "P1";
                $isi_kip[] = "Materi yang dinilai (lihat tabel KATA KERJA sebelah kanan)";
                xyXss:
                if (!(count($no_kik) == 0)) {
                    goto tIKrI;
                }
                $no_kik[] = 1;
                $kode_kik[] = "K1";
                $isi_kik[] = "Praktik/Portofolio/Proyek yang dinilai (lihat tabel KATA KERJA sebelah kanan)";
                tIKrI:
                $params = ["[no]" => $no, "[nisn]" => $nisn, "[nama]" => $nama, "[p1]" => $p1, "[p2]" => $p2, "[p3]" => $p3, "[p4]" => $p4, "[p5]" => $p5, "[p6]" => $p6, "[p7]" => $p7, "[p8]" => $p8, "[k1]" => $k1, "[k2]" => $k2, "[k3]" => $k3, "[k4]" => $k4, "[k5]" => $k5, "[k6]" => $k6, "[k7]" => $k7, "[k8]" => $k8, "[nop]" => $no_kip, "[kodep]" => $kode_kip, "[pengetahuan]" => $isi_kip, "[nok]" => $no_kik, "[kodek]" => $kode_kik, "[keterampilan]" => $isi_kik, "{mapel}" => $mapel->kode];
                PhpExcelTemplator::outputToFile($template, $fileName, $params);
                // [PHPDeobfuscator] Implied return
                return;
            }
            $siswa = $siswas[$i];
            $nilai = $nilais[$siswa->id_siswa];
            $no_induk = $siswa->nisn != null ? "'" . $siswa->nisn : "'" . $siswa->nis;
            $no[] = $i + 1;
            $nisn[] = $no_induk;
            $nama[] = $siswa->nama;
            $p1[] = $nilai->p1;
            $p2[] = $nilai->p2;
            $p3[] = $nilai->p3;
            $p4[] = $nilai->p4;
            $p5[] = $nilai->p5;
            $p6[] = $nilai->p6;
            $p7[] = $nilai->p7;
            $p8[] = $nilai->p8;
            $k1[] = $nilai->k1;
            $k2[] = $nilai->k2;
            $k3[] = $nilai->k3;
            $k4[] = $nilai->k4;
            $k5[] = $nilai->k5;
            $k6[] = $nilai->k6;
            $k7[] = $nilai->k7;
            $k8[] = $nilai->k8;
            $i++;
            goto R8ozO;
        }
        $siswa = $siswas[$i];
        $dummyNilai = ["p1" => '', "p2" => '', "p3" => '', "p4" => '', "p5" => '', "p6" => '', "p7" => '', "p8" => '', "k1" => '', "k2" => '', "k3" => '', "k4" => '', "k5" => '', "k6" => '', "k7" => '', "k8" => ''];
        $ns = $this->rapor->getNilaiHarianKelas($id_mapel, $id_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt);
        $nilais[$siswa->id_siswa] = $ns == null ? json_decode(json_encode($dummyNilai)) : $ns;
        $i++;
        goto jKxdz;
    }
    public function uploadHarian($id_mapel, $id_kelas)
    {
        $this->load->model("Dashboard_model", "dashboard");
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
                goto b0xVi;
            case ".xls":
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
                goto b0xVi;
            case ".csv":
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
                goto b0xVi;
            default:
                echo "unknown file ext";
                die;
        }
        b0xVi:
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $siswas = $this->kelas->getKelasSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
        $spreadsheet = $reader->load($file);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        $datas = [];
        $kikdp = [];
        $kikdk = [];
        $readed = 0;
        foreach ($siswas as $siswa) {
            $i = 1;
            Yxw5l:
            if (!($i < count($sheetData))) {
            }
            if (!($sheetData[$i][0] != null)) {
                goto feM0u;
            }
            $readed++;
            $nisn = $sheetData[$i][1];
            $no_induk = $siswa->nisn != null ? "'" . $siswa->nisn : "'" . $siswa->nis;
            if (!($no_induk == $nisn)) {
                goto tHgkF;
            }
            $datas[] = ["id_nilai_harian" => $id_mapel . $id_kelas . $siswa->id_siswa . $tp->id_tp . $smt->id_smt, "id_siswa" => $siswa->id_siswa, "id_mapel" => $id_mapel, "id_kelas" => $id_kelas, "id_tp" => $tp->id_tp, "id_smt" => $smt->id_smt, "p1" => $sheetData[$i][3], "p2" => $sheetData[$i][4], "p3" => $sheetData[$i][5], "p4" => $sheetData[$i][6], "p5" => $sheetData[$i][7], "p6" => $sheetData[$i][8], "p7" => $sheetData[$i][9], "p8" => $sheetData[$i][10], "k1" => $sheetData[$i][11], "k2" => $sheetData[$i][12], "k3" => $sheetData[$i][13], "k4" => $sheetData[$i][14], "k5" => $sheetData[$i][15], "k6" => $sheetData[$i][16], "k7" => $sheetData[$i][17], "k8" => $sheetData[$i][18]];
            tHgkF:
            $nop = $sheetData[$i][20];
            if (!($nop != '')) {
                goto BRofx;
            }
            $kikdp[] = ["id_kikd" => $id_mapel . $id_kelas . "1" . $nop, "id_mapel_kelas" => $id_mapel . $id_kelas, "aspek" => 1, "id_tp" => $tp->id_tp, "id_smt" => $smt->id_smt, "materi_kikd" => $sheetData[$i][22] != null ? strip_tags($sheetData[$i][22]) : ''];
            BRofx:
            $nok = $sheetData[$i][24];
            if (!($nok != '')) {
                goto Qu_Et;
            }
            $kikdk[] = ["id_kikd" => $id_mapel . $id_kelas . "2" . $nok, "id_mapel_kelas" => $id_mapel . $id_kelas, "aspek" => 2, "id_tp" => $tp->id_tp, "id_smt" => $smt->id_smt, "materi_kikd" => $sheetData[$i][26] != null ? strip_tags($sheetData[$i][26]) : ''];
            Qu_Et:
            feM0u:
            $i++;
            goto Yxw5l;
        }
        unlink($file);
        $updated = 0;
        $this->db->trans_start();
        foreach ($datas as $data) {
            $update = $this->db->replace("rapor_nilai_harian", $data);
            if (!$update) {
                goto y8dpx;
            }
            $updated++;
            y8dpx:
        }
        foreach ($kikdp as $kip) {
            if (!($kip != null)) {
                goto YBQZm;
            }
            $this->db->replace("rapor_kikd", $kip);
            YBQZm:
        }
        foreach ($kikdk as $kik) {
            if (!($kik != null)) {
                goto hSlX7;
            }
            $this->db->replace("rapor_kikd", $kik);
            hSlX7:
        }
        $this->db->trans_complete();
        echo json_encode($updated);
    }
    public function importHarian()
    {
        $posts = $this->input->post("siswa", true);
        $updated = 0;
        $this->db->trans_start();
        foreach ((array) $posts as $data) {
            $update = $this->db->replace("rapor_nilai_harian", $data);
            if (!$update) {
                goto Vn1R_;
            }
            $updated++;
            Vn1R_:
        }
        $this->db->trans_complete();
        $data["updated"] = $updated;
        $this->output_json($data);
    }
    public function inputPts($id_mapel, $id_kelas)
    {
        $this->load->model("Dashboard_model", "dashboard");
        $user = $this->ion_auth->user()->row();
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        $mapel_guru = $this->kelas->getGuruMapelKelas($guru->id_guru, $tp->id_tp, $smt->id_smt);
        $mapels = json_decode(json_encode(unserialize($mapel_guru->mapel_kelas)));
        $mapel = '';
        $kelas = [];
        foreach ($mapels as $m) {
            if (!($m->id_mapel === $id_mapel)) {
                goto Zrj9S;
            }
            $mapel = ["id_mapel" => $m->id_mapel, "nama_mapel" => $m->nama_mapel];
            Zrj9S:
            foreach ($m->kelas_mapel as $kls) {
                if (!($kls->kelas === $id_kelas)) {
                    goto GLQOG;
                }
                $kelas = ["id_kelas" => $kls->kelas, "nama_kelas" => $this->dropdown->getNamaKelasById($tp->id_tp, $smt->id_smt, $kls->kelas)];
                GLQOG:
            }
        }
        $siswas = $this->kelas->getKelasSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
        $nilai = [];
        $i = 0;
        iY9mH:
        if (!($i < count($siswas))) {
            $setting = $this->rapor->getRaporSetting($tp->id_tp, $smt->id_smt);
            $kkm = null;
            if (!($setting != null)) {
                goto cW18X;
            }
            if ($setting->kkm_tunggal == "1") {
                $kkm = $setting;
                goto BmFAV;
            }
            $kkm = $this->rapor->getKkm($id_mapel . $id_kelas . $tp->id_tp . $smt->id_smt . "1");
            BmFAV:
            cW18X:
            $data = ["user" => $user, "judul" => "Nilai PTS Kelas ", "subjudul" => "Input Nilai PTS Mapel ", "setting" => $this->dashboard->getSetting(), "guru" => $guru, "mapel" => $mapel, "kelas" => $kelas, "siswa" => $siswas, "nilai" => $nilai, "kkm" => $kkm];
            $data["tp"] = $this->dashboard->getTahun();
            $data["tp_active"] = $tp;
            $data["smt"] = $this->dashboard->getSemester();
            $data["smt_active"] = $smt;
            $data["setting_rapor"] = $setting;
            $this->load->view("members/guru/templates/header", $data);
            $this->load->view("members/guru/rapor/nilai/pts");
            $this->load->view("members/guru/templates/footer");
            // [PHPDeobfuscator] Implied return
            return;
        }
        $siswa = $siswas[$i];
        $dummyNilai = ["p1" => '', "p2" => '', "p3" => '', "p4" => '', "p5" => '', "p6" => '', "p7" => '', "p8" => '', "p_rata_rata" => '', "p_predikat" => "=", "p_deskripsi" => '', "k1" => '', "k2" => '', "k3" => '', "k4" => '', "k5" => '', "k6" => '', "k7" => '', "k8" => '', "k_rata_rata" => '', "k_predikat" => '', "k_deskripsi" => ''];
        $ns = $this->rapor->getNilaiPtsKelas($id_mapel, $id_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt);
        $nilai[$siswa->id_siswa] = $ns == null ? $dummyNilai : $ns;
        $i++;
        goto iY9mH;
    }
    public function downloadTemplatePts($id_mapel, $id_kelas)
    {
        $this->load->model("Dashboard_model", "dashboard");
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $siswas = $this->kelas->getKelasSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
        $nilais = [];
        $i = 0;
        npRgB:
        if (!($i < count($siswas))) {
            $kelas = $this->kelas->getNamaKelasById([$id_kelas]);
            $mapel = $this->master->getMapelById($id_mapel, true);
            $template = "./uploads/import/format/template_pts.xlsx";
            $fileName = "Nilai_PTS " . $mapel->kode . " " . $kelas[$id_kelas] . ".xlsx";
            $no = [];
            $nisn = [];
            $nama = [];
            $p1 = [];
            $i = 0;
            tzJ42:
            if (!($i < count($siswas))) {
                $params = ["{mapel}" => $mapel->kode, "{kelas}" => $kelas[$id_kelas], "[no]" => $no, "[nisn]" => $nisn, "[nama]" => $nama, "[nilai]" => $p1];
                PhpExcelTemplator::outputToFile($template, $fileName, $params);
                // [PHPDeobfuscator] Implied return
                return;
            }
            $siswa = $siswas[$i];
            $nilai = $nilais[$siswa->id_siswa];
            $no_induk = $siswa->nisn != null ? "'" . $siswa->nisn : "'" . $siswa->nis;
            $no[] = $i + 1;
            $nisn[] = $no_induk;
            $nama[] = $siswa->nama;
            $p1[] = $nilai->nilai;
            $i++;
            goto tzJ42;
        }
        $siswa = $siswas[$i];
        $dummyNilai = ["nilai" => ''];
        $ns = $this->rapor->getNilaiPtsKelas($id_mapel, $id_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt);
        $nilais[$siswa->id_siswa] = $ns == null ? json_decode(json_encode($dummyNilai)) : $ns;
        $i++;
        goto npRgB;
    }
    public function uploadPts($id_mapel, $id_kelas)
    {
        $this->load->model("Dashboard_model", "dashboard");
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
                goto o1m35;
            case ".xls":
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
                goto o1m35;
            case ".csv":
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
                goto o1m35;
            default:
                echo "unknown file ext";
                die;
        }
        o1m35:
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $siswas = $this->kelas->getKelasSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
        $spreadsheet = $reader->load($file);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        $datas = [];
        $readed = 0;
        foreach ($siswas as $siswa) {
            $i = 1;
            Stc_U:
            if (!($i < count($sheetData))) {
            }
            if (!($sheetData[$i][0] != null)) {
                goto w0AyS;
            }
            $readed++;
            $nisn = $sheetData[$i][1];
            $no_induk = $siswa->nisn != null ? "'" . $siswa->nisn : "'" . $siswa->nis;
            if (!($no_induk == $nisn)) {
                goto UvFk0;
            }
            $datas[] = ["id_nilai_pts" => $id_mapel . $id_kelas . $siswa->id_siswa . $tp->id_tp . $smt->id_smt, "id_siswa" => $siswa->id_siswa, "id_mapel" => $id_mapel, "id_kelas" => $id_kelas, "nilai" => $sheetData[$i][3]];
            UvFk0:
            w0AyS:
            $i++;
            goto Stc_U;
        }
        unlink($file);
        $updated = 0;
        foreach ($datas as $data) {
            $update = $this->db->replace("rapor_nilai_pts", $data);
            if (!$update) {
                goto l2eci;
            }
            $updated++;
            l2eci:
        }
        echo json_encode($updated);
    }
    public function importPts()
    {
        $inputs = $this->input->post("siswa", true);
        $updated = 0;
        $this->db->trans_start();
        foreach ($inputs as $data) {
            $update = $this->db->replace("rapor_nilai_pts", $data);
            if (!$update) {
                goto GCdpJ;
            }
            $updated++;
            GCdpJ:
        }
        $this->db->trans_complete();
        echo json_encode($updated);
    }
    public function inputPas($id_mapel, $id_kelas)
    {
        $this->load->model("Dashboard_model", "dashboard");
        $user = $this->ion_auth->user()->row();
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        $mapel_guru = $this->kelas->getGuruMapelKelas($guru->id_guru, $tp->id_tp, $smt->id_smt);
        $mapels = json_decode(json_encode(unserialize($mapel_guru->mapel_kelas)));
        $mapel = '';
        $kelas = [];
        foreach ($mapels as $m) {
            if (!($m->id_mapel === $id_mapel)) {
                goto lFUX3;
            }
            $mapel = ["id_mapel" => $m->id_mapel, "nama_mapel" => $m->nama_mapel];
            lFUX3:
            foreach ($m->kelas_mapel as $kls) {
                if (!($kls->kelas === $id_kelas)) {
                    goto Zm5l5;
                }
                $kelas = ["id_kelas" => $kls->kelas, "nama_kelas" => $this->dropdown->getNamaKelasById($tp->id_tp, $smt->id_smt, $kls->kelas)];
                Zm5l5:
            }
        }
        $siswas = $this->kelas->getKelasSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
        $nilai = [];
        $i = 0;
        V1t5D:
        if (!($i < count($siswas))) {
            $setting = $this->rapor->getRaporSetting($tp->id_tp, $smt->id_smt);
            $kkm = null;
            if (!($setting != null)) {
                goto os1wz;
            }
            if ($setting->kkm_tunggal == "1") {
                $kkm = $setting;
                goto YIG1t;
            }
            $kkm = $this->rapor->getKkm($id_mapel . $id_kelas . $tp->id_tp . $smt->id_smt . "1");
            YIG1t:
            os1wz:
            $data = ["user" => $user, "judul" => "Nilai Akhir Kelas ", "subjudul" => "Input Nilai Akhir Mapel ", "setting" => $this->dashboard->getSetting(), "guru" => $guru, "mapel" => $mapel, "kelas" => $kelas, "siswa" => $siswas, "nilai" => $nilai, "kkm" => $kkm, "setting_rapor" => $setting];
            $data["tp"] = $this->dashboard->getTahun();
            $data["tp_active"] = $tp;
            $data["smt"] = $this->dashboard->getSemester();
            $data["smt_active"] = $smt;
            $this->load->view("members/guru/templates/header", $data);
            $this->load->view("members/guru/rapor/nilai/pas");
            $this->load->view("members/guru/templates/footer");
            // [PHPDeobfuscator] Implied return
            return;
        }
        $siswa = $siswas[$i];
        $dummyNilai = ["nhar" => '', "npts" => '', "npas" => ''];
        $ns = $this->rapor->getNilaiAkhirKelas($id_mapel, $id_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt);
        $nilai[$siswa->id_siswa] = $ns == null ? $dummyNilai : $ns;
        $i++;
        goto V1t5D;
    }
    public function downloadTemplatePas($id_mapel, $id_kelas)
    {
        $this->load->model("Dashboard_model", "dashboard");
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $siswas = $this->kelas->getKelasSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
        $nilais = [];
        $i = 0;
        RTKlT:
        if (!($i < count($siswas))) {
            $kelas = $this->kelas->getNamaKelasById([$id_kelas]);
            $mapel = $this->master->getMapelById($id_mapel, true);
            $template = "./uploads/import/format/template_pas.xlsx";
            $fileName = "Nilai_PAS " . $mapel->kode . " " . $kelas[$id_kelas] . ".xlsx";
            $no = [];
            $nisn = [];
            $nama = [];
            $p1 = [];
            $i = 0;
            anzAK:
            if (!($i < count($siswas))) {
                $params = ["{mapel}" => $mapel->kode, "{kelas}" => $kelas[$id_kelas], "[no]" => $no, "[nisn]" => $nisn, "[nama]" => $nama, "[nilai]" => $p1];
                PhpExcelTemplator::outputToFile($template, $fileName, $params);
                // [PHPDeobfuscator] Implied return
                return;
            }
            $siswa = $siswas[$i];
            $nilai = $nilais[$siswa->id_siswa];
            $no_induk = $siswa->nisn != null ? "'" . $siswa->nisn : "'" . $siswa->nis;
            $no[] = $i + 1;
            $nisn[] = $no_induk;
            $nama[] = $siswa->nama;
            $p1[] = $nilai->npas;
            $i++;
            goto anzAK;
        }
        $siswa = $siswas[$i];
        $dummyNilai = ["nilai" => '', "npas" => ''];
        $ns = $this->rapor->getNilaiAkhirKelas($id_mapel, $id_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt);
        $nilais[$siswa->id_siswa] = $ns == null ? json_decode(json_encode($dummyNilai)) : $ns;
        $i++;
        goto RTKlT;
    }
    public function uploadPas($id_mapel, $id_kelas)
    {
        $this->load->model("Dashboard_model", "dashboard");
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
                goto jl5nb;
            case ".xls":
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
                goto jl5nb;
            case ".csv":
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
                goto jl5nb;
            default:
                echo "unknown file ext";
                die;
        }
        jl5nb:
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $siswas = $this->kelas->getKelasSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
        $spreadsheet = $reader->load($file);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        $datas = [];
        $readed = 0;
        foreach ($siswas as $siswa) {
            $i = 1;
            Nyz02:
            if (!($i < count($sheetData))) {
            }
            if (!($sheetData[$i][0] != null)) {
                goto SVicX;
            }
            $readed++;
            $nisn = $sheetData[$i][1];
            $no_induk = $siswa->nisn != null ? "'" . $siswa->nisn : "'" . $siswa->nis;
            if (!($no_induk == $nisn)) {
                goto qbg9T;
            }
            $datas[] = ["id_nilai_akhir" => $id_mapel . $id_kelas . $siswa->id_siswa . $tp->id_tp . $smt->id_smt, "id_siswa" => $siswa->id_siswa, "id_mapel" => $id_mapel, "id_kelas" => $id_kelas, "nilai" => $sheetData[$i][3]];
            qbg9T:
            SVicX:
            $i++;
            goto Nyz02;
        }
        unlink($file);
        $updated = 0;
        foreach ($datas as $data) {
            $update = $this->db->replace("rapor_nilai_akhir", $data);
            if (!$update) {
                goto MTDsg;
            }
            $updated++;
            MTDsg:
        }
        echo json_encode($updated);
    }
    public function importPas()
    {
        $inputs = $this->input->post("siswa", true);
        $updated = 0;
        $this->db->trans_start();
        foreach ($inputs as $data) {
            $update = $this->db->replace("rapor_nilai_akhir", $data);
            if (!$update) {
                goto cCFwY;
            }
            $updated++;
            cCFwY:
        }
        $this->db->trans_complete();
        echo json_encode($updated);
    }
    public function inputEkstra($id_ekstra, $id_kelas)
    {
        $this->load->model("Dashboard_model", "dashboard");
        $user = $this->ion_auth->user()->row();
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        $ekstra_guru = $this->kelas->getGuruMapelKelas($guru->id_guru, $tp->id_tp, $smt->id_smt);
        $ekstras = json_decode(json_encode(unserialize($ekstra_guru->ekstra_kelas)));
        $ekstra = '';
        $kelas = [];
        foreach ($ekstras as $m) {
            if (!($m->id_ekstra === $id_ekstra)) {
                goto aGjFN;
            }
            $ekstra = ["id_ekstra" => $m->id_ekstra, "nama_ekstra" => $m->nama_ekstra];
            aGjFN:
            foreach ($m->kelas_ekstra as $kls) {
                if (!($kls->kelas === $id_kelas)) {
                    goto dUabw;
                }
                $kelas = ["id_kelas" => $kls->kelas, "nama_kelas" => $this->dropdown->getNamaKelasById($tp->id_tp, $smt->id_smt, $kls->kelas)];
                dUabw:
            }
        }
        $siswas = $this->kelas->getKelasSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
        $nilai = [];
        $i = 0;
        a4zdC:
        if (!($i < count($siswas))) {
            $setting = $this->rapor->getRaporSetting($tp->id_tp, $smt->id_smt);
            if ($setting->kkm_tunggal == "1") {
                $kkm = $setting;
                goto mrQxd;
            }
            $kkm = $this->rapor->getKkm($id_ekstra . $id_kelas . $tp->id_tp . $smt->id_smt . "2");
            mrQxd:
            $data = ["user" => $user, "judul" => "Nilai Ekstrakurikuler ", "subjudul" => "Input Nilai PTS Ekstra ", "setting" => $this->dashboard->getSetting(), "guru" => $guru, "ekstra" => $ekstra, "kelas" => $kelas, "siswa" => $siswas, "nilai" => $nilai, "kkm" => $kkm];
            $data["tp"] = $this->dashboard->getTahun();
            $data["tp_active"] = $tp;
            $data["smt"] = $this->dashboard->getSemester();
            $data["smt_active"] = $smt;
            $this->load->view("members/guru/templates/header", $data);
            $this->load->view("members/guru/rapor/nilai/ekstra");
            $this->load->view("members/guru/templates/footer");
            // [PHPDeobfuscator] Implied return
            return;
        }
        $siswa = $siswas[$i];
        $dummyNilai = ["p1" => '', "p2" => '', "p3" => '', "p4" => '', "p5" => '', "p6" => '', "p7" => '', "p8" => '', "p_rata_rata" => '', "p_predikat" => "=", "p_deskripsi" => '', "k1" => '', "k2" => '', "k3" => '', "k4" => '', "k5" => '', "k6" => '', "k7" => '', "k8" => '', "k_rata_rata" => '', "k_predikat" => '', "k_deskripsi" => ''];
        $ns = $this->rapor->getNilaiEkstraKelas($id_ekstra, $id_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt);
        $nilai[$siswa->id_siswa] = $ns == null ? $dummyNilai : $ns;
        $i++;
        goto a4zdC;
    }
    public function downloadTemplateEkstra($id_ekstra, $id_kelas)
    {
        $this->load->model("Dashboard_model", "dashboard");
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $siswas = $this->kelas->getKelasSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
        $nilais = [];
        $i = 0;
        gLYDN:
        if (!($i < count($siswas))) {
            $kelas = $this->kelas->getNamaKelasById([$id_kelas]);
            $ekstra = $this->master->getEkstraById($id_ekstra, true);
            $template = "./uploads/import/format/template_ekstra.xlsx";
            $fileName = "Nilai_Ekstrakurikuler " . $ekstra->kode_ekstra . " " . $kelas[$id_kelas] . ".xlsx";
            $no = [];
            $nisn = [];
            $nama = [];
            $p1 = [];
            $i = 0;
            QoJNS:
            if (!($i < count($siswas))) {
                $params = ["{mapel}" => $ekstra->nama_ekstra, "{kelas}" => $kelas[$id_kelas], "[no]" => $no, "[nisn]" => $nisn, "[nama]" => $nama, "[nilai]" => $p1];
                PhpExcelTemplator::outputToFile($template, $fileName, $params);
                // [PHPDeobfuscator] Implied return
                return;
            }
            $siswa = $siswas[$i];
            $no_induk = $siswa->nisn != null ? "'" . $siswa->nisn : "'" . $siswa->nis;
            $no[] = $i + 1;
            $nisn[] = $no_induk;
            $nama[] = $siswa->nama;
            if (!(count($nilais) > 0)) {
                goto agqA6;
            }
            $nilai = $nilais[$siswa->id_siswa];
            $p1[] = $nilai->nilai;
            agqA6:
            $i++;
            goto QoJNS;
        }
        $siswa = $siswas[$i];
        $dummyNilai = ["nilai" => ''];
        $ns = $this->rapor->getNilaiEkstraKelas($id_ekstra, $id_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt);
        $nilais[$siswa->id_siswa] = $ns == null ? json_decode(json_encode($dummyNilai)) : $ns;
        $i++;
        goto gLYDN;
    }
    public function uploadEkstra($id_ekstra, $id_kelas)
    {
        $this->load->model("Dashboard_model", "dashboard");
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
                goto rT8I9;
            case ".xls":
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
                goto rT8I9;
            case ".csv":
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
                goto rT8I9;
            default:
                echo "unknown file ext";
                die;
        }
        rT8I9:
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $siswas = $this->kelas->getKelasSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
        $spreadsheet = $reader->load($file);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        $datas = [];
        $readed = 0;
        foreach ($siswas as $siswa) {
            $i = 1;
            OHY4W:
            if (!($i < count($sheetData))) {
            }
            if (!($sheetData[$i][0] != null)) {
                goto q0q5w;
            }
            $readed++;
            $nisn = $sheetData[$i][1];
            $no_induk = $siswa->nisn != null ? "'" . $siswa->nisn : "'" . $siswa->nis;
            if (!($no_induk == $nisn)) {
                goto B6NVy;
            }
            $datas[] = ["id_nilai_ekstra" => $id_ekstra . $id_kelas . $siswa->id_siswa . $tp->id_tp . $smt->id_smt, "id_siswa" => $siswa->id_siswa, "id_ekstra" => $id_ekstra, "id_kelas" => $id_kelas, "nilai" => $sheetData[$i][3]];
            B6NVy:
            q0q5w:
            $i++;
            goto OHY4W;
        }
        unlink($file);
        $updated = 0;
        foreach ($datas as $data) {
            $update = $this->db->replace("rapor_nilai_ekstra", $data);
            if (!$update) {
                goto MzzuC;
            }
            $updated++;
            MzzuC:
        }
        echo json_encode($updated);
    }
    public function importEkstra()
    {
        $inputs = $this->input->post("siswa", true);
        $updated = 0;
        $this->db->trans_start();
        foreach ($inputs as $data) {
            $update = $this->db->replace("rapor_nilai_ekstra", $data);
            if (!$update) {
                goto kBKxT;
            }
            $updated++;
            kBKxT:
        }
        $this->db->trans_complete();
        echo json_encode($updated);
    }
    public function raporSikap()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $user = $this->ion_auth->user()->row();
        $data = ["user" => $user, "judul" => "Input Nilai Sikap", "subjudul" => "Input Nilai Sikap", "setting" => $this->dashboard->getSetting()];
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $data["tp"] = $this->dashboard->getTahun();
        $data["tp_active"] = $tp;
        $data["smt"] = $this->dashboard->getSemester();
        $data["smt_active"] = $smt;
        $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        $id_kelas = $guru->wali_kelas;
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
        $dummySikap = [];
        $i = 0;
        Kz_lj:
        if (!($i < 10)) {
            $i = 0;
            d5lqD:
            if (!($i < 10)) {
                $sikap = $this->rapor->getDeskripsiSikap($id_kelas, $tp->id_tp, $smt->id_smt);
                if (!(count($sikap) === 0)) {
                    goto Xn4y2;
                }
                $sikap = json_decode(json_encode($dummySikap));
                Xn4y2:
                $data["guru"] = $guru;
                $data["mapel"] = $arrMapel;
                $data["kelas"] = $arrKelas;
                $data["sikap"] = $sikap;
                $this->load->view("members/guru/templates/header", $data);
                $this->load->view("members/guru/rapor/sikap/data");
                $this->load->view("members/guru/templates/footer");
                // [PHPDeobfuscator] Implied return
                return;
            }
            $no = $i + 1;
            $s = ["id_sikap" => 2 . $no, "jenis" => "2", "kode" => $no, "sikap" => ''];
            array_push($dummySikap, $s);
            $i++;
            goto d5lqD;
        }
        $no = $i + 1;
        $s = ["id_sikap" => 1 . $no, "jenis" => "1", "kode" => $no, "sikap" => ''];
        array_push($dummySikap, $s);
        $i++;
        goto Kz_lj;
    }
    public function saveSikap()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $input = json_decode($this->input->post("sikap", true));
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        foreach ($input as $d) {
            $data = ["id_sikap" => $d->id_sikap, "id_kelas" => $d->kelas, "jenis" => $d->jenis, "kode" => $d->kode, "sikap" => $d->sikap, "id_tp" => $tp->id_tp, "id_smt" => $smt->id_smt];
            $update = $this->db->replace("rapor_data_sikap", $data);
        }
        $data["status"] = $update;
        $this->output_json($data);
    }
    public function raporSpiritual()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $user = $this->ion_auth->user()->row();
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        $id_kelas = $guru->wali_kelas;
        $kelas = $this->kelas->get_one($id_kelas, $tp->id_tp, $smt->id_smt);
        $dummySpiritual = [];
        $i = 0;
        y151q:
        if (!($i < 10)) {
            $spiritual = $this->rapor->getDeskripsiSikapByJenis($id_kelas, "1", $tp->id_tp, $smt->id_smt);
            if (!(count($spiritual) === 0)) {
                goto bYeGw;
            }
            $spiritual = json_decode(json_encode($dummySpiritual));
            bYeGw:
            $siswas = $this->kelas->getKelasSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
            $nilai = [];
            $i = 0;
            Si0vd:
            if (!($i < count($siswas))) {
                $data = ["user" => $user, "judul" => "Nilai Spiritual Kelas ", "subjudul" => "Input Nilai", "setting" => $this->dashboard->getSetting(), "guru" => $guru, "kelas" => $kelas, "siswa" => $siswas, "nilai" => $nilai, "spiritual" => $spiritual];
                $data["tp"] = $this->dashboard->getTahun();
                $data["tp_active"] = $tp;
                $data["smt"] = $this->dashboard->getSemester();
                $data["smt_active"] = $smt;
                $this->load->view("members/guru/templates/header", $data);
                $this->load->view("members/guru/rapor/sikap/spiritual");
                $this->load->view("members/guru/templates/footer");
                // [PHPDeobfuscator] Implied return
                return;
            }
            $siswa = $siswas[$i];
            $dummyNilai = ["predikat" => '', "sl1" => '', "sl2" => '', "sl3" => '', "mb1" => '', "mb2" => '', "mb3" => ''];
            $ns = $this->rapor->getNilaiSikapKelas($id_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt, "1");
            $nilai[$siswa->id_siswa] = $ns == null ? $dummyNilai : unserialize($ns->nilai);
            $i++;
            goto Si0vd;
        }
        $no = $i + 1;
        $s = ["id_sikap" => $id_kelas . 1 . $no, "jenis" => "1", "kode" => $no, "sikap" => $this->rapor->getDummyDeskripsiSpiritual()[$i]];
        array_push($dummySpiritual, $s);
        $i++;
        goto y151q;
    }
    public function importSpiritual($id_kelas)
    {
        $this->load->model("Dashboard_model", "dashboard");
        $input = json_decode($this->input->post("nilai", true));
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $datas = [];
        foreach ($input as $in) {
            $id_siswa = $in[11];
            if (!($id_siswa != "id")) {
                goto TyXFO;
            }
            $datas[] = ["id_nilai_sikap" => $id_kelas . $id_siswa . $tp->id_tp . $smt->id_smt . "1", "id_siswa" => $id_siswa, "id_kelas" => $id_kelas, "jenis" => 1, "nilai" => serialize(["predikat" => $in[3], "sl1" => $in[4], "sl2" => $in[5], "sl3" => $in[6], "mb1" => $in[7], "mb2" => $in[8], "mb3" => $in[9]]), "deskripsi" => $in[10], "id_tp" => $tp->id_tp, "id_smt" => $smt->id_smt];
            TyXFO:
        }
        $updated = 0;
        foreach ($datas as $data) {
            $update = $this->db->replace("rapor_nilai_sikap", $data);
            if (!$update) {
                goto oWDLN;
            }
            $updated++;
            oWDLN:
        }
        echo json_encode($updated);
    }
    public function raporSosial()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $user = $this->ion_auth->user()->row();
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        $id_kelas = $guru->wali_kelas;
        $kelas = $this->kelas->get_one($id_kelas, $tp->id_tp, $smt->id_smt);
        $dummySosial = [];
        $i = 0;
        aY0du:
        if (!($i < 10)) {
            $sosial = $this->rapor->getDeskripsiSikapByJenis($id_kelas, "2", $tp->id_tp, $smt->id_smt);
            if (!(count($sosial) === 0)) {
                goto k_wjg;
            }
            $sosial = json_decode(json_encode($dummySosial));
            k_wjg:
            $siswas = $this->kelas->getKelasSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
            $nilai = [];
            $i = 0;
            xsdUQ:
            if (!($i < count($siswas))) {
                $data = ["user" => $user, "judul" => "Nilai Sosial Kelas ", "subjudul" => "Input Nilai PTS Mapel ", "setting" => $this->dashboard->getSetting(), "guru" => $guru, "kelas" => $kelas, "siswa" => $siswas, "nilai" => $nilai, "sosial" => $sosial];
                $data["tp"] = $this->dashboard->getTahun();
                $data["tp_active"] = $tp;
                $data["smt"] = $this->dashboard->getSemester();
                $data["smt_active"] = $smt;
                $this->load->view("members/guru/templates/header", $data);
                $this->load->view("members/guru/rapor/sikap/sosial");
                $this->load->view("members/guru/templates/footer");
                // [PHPDeobfuscator] Implied return
                return;
            }
            $siswa = $siswas[$i];
            $dummyNilai = ["predikat" => '', "sl1" => '', "sl2" => '', "sl3" => '', "mb1" => '', "mb2" => '', "mb3" => ''];
            $ns = $this->rapor->getNilaiSikapKelas($id_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt, "2");
            $nilai[$siswa->id_siswa] = $ns == null ? $dummyNilai : unserialize($ns->nilai);
            $i++;
            goto xsdUQ;
        }
        $no = $i + 1;
        $s = ["id_sikap" => $id_kelas . 2 . $no, "jenis" => "2", "kode" => $no, "sikap" => $this->rapor->getDummyDeskripsiSosial()[$i]];
        array_push($dummySosial, $s);
        $i++;
        goto aY0du;
    }
    public function importSosial($id_kelas)
    {
        $this->load->model("Dashboard_model", "dashboard");
        $input = json_decode($this->input->post("nilai", true));
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $datas = [];
        foreach ($input as $in) {
            $id_siswa = $in[13];
            if (!($id_siswa != "id")) {
                goto ZLALF;
            }
            $datas[] = ["id_nilai_sikap" => $id_kelas . $id_siswa . $tp->id_tp . $smt->id_smt . "2", "id_siswa" => $id_siswa, "id_kelas" => $id_kelas, "jenis" => 2, "nilai" => serialize(["predikat" => $in[3], "a1" => $in[4], "a2" => $in[5], "a3" => $in[6], "b1" => $in[7], "b2" => $in[8], "b3" => $in[9], "c1" => $in[10], "c2" => $in[11]]), "deskripsi" => $in[12], "id_tp" => $tp->id_tp, "id_smt" => $smt->id_smt];
            ZLALF:
        }
        $updated = 0;
        foreach ($datas as $data) {
            $update = $this->db->replace("rapor_nilai_sikap", $data);
            if (!$update) {
                goto TPiYg;
            }
            $updated++;
            TPiYg:
        }
        echo json_encode($updated);
    }
    public function raporPrestasi()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $user = $this->ion_auth->user()->row();
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $setting = $this->dashboard->getSetting();
        $mapels = $this->master->getAllMapel();
        $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        $id_kelas = $guru->wali_kelas;
        $kelas = $this->kelas->get_one($id_kelas);
        $dummyDeskSaran = [];
        $dummyRank = ["1 ~ 3", "4 ~ 10", "11 ~ 15", "16 ~ 20", "21 ~ 25", "26 > >"];
        $dummyKode = ["1", "4", "11", "16", "21", "26"];
        $i = 0;
        SvBxv:
        if (!($i < 6)) {
            $deskPrestasi = $this->rapor->getDeskripsiCatatanByJenis($id_kelas, "1", $tp->id_tp, $smt->id_smt);
            if (!(count($deskPrestasi) === 0)) {
                goto bGuZY;
            }
            $deskPrestasi = json_decode(json_encode($dummyDeskSaran));
            bGuZY:
            $siswas = $this->kelas->getKelasSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
            $nilai = [];
            $nilaiHarian = [];
            $nilaiRata_p = [];
            $nilaiRata_k = [];
            $nilaiPts = [];
            $nilaiPas = [];
            $i = 0;
            Wm47X:
            if (!($i < count($siswas))) {
                $nilaiRata[] = [];
                $data = ["user" => $user, "judul" => "Ranking & Prestasi Kelas ", "subjudul" => "Input Nilai", "setting" => $this->dashboard->getSetting(), "guru" => $guru, "kelas" => $kelas, "siswa" => $siswas, "nilai" => $nilai, "nilaiHarian" => $nilaiHarian, "nilaiRata_p" => $nilaiRata_p, "nilaiRata_k" => $nilaiRata_k, "nilaiRata" => $nilaiRata, "nilaiPts" => $nilaiPts, "nilaiPas" => $nilaiPas, "deskRanking" => $deskPrestasi, "mapels" => $mapels];
                $data["tp"] = $this->dashboard->getTahun();
                $data["tp_active"] = $tp;
                $data["smt"] = $this->dashboard->getSemester();
                $data["smt_active"] = $smt;
                $this->load->view("members/guru/templates/header", $data);
                $this->load->view("members/guru/rapor/prestasi/data");
                $this->load->view("members/guru/templates/footer");
                // [PHPDeobfuscator] Implied return
                return;
            }
            $siswa = $siswas[$i];
            $id_siswa = $siswa->id_siswa;
            $dummyNilai = ["ranking" => '', "deskripsi" => '', "p1" => '', "p1_desk" => '', "p2" => '', "p2_desk" => '', "p3" => '', "p3_desk" => ''];
            foreach ($mapels as $mapel) {
                $h = $this->rapor->getJmlNilaiMapelHarianSiswa($mapel->id_mapel, $id_siswa, $tp->id_tp, $smt->id_smt);
                $nilaiHarian[$id_siswa][$mapel->id_mapel] = $h == null ? 0 : $h->jml;
                $nilaiRata_p[$id_siswa][$mapel->id_mapel] = $h == null ? 0 : $h->p_rata_rata;
                $nilaiRata_k[$id_siswa][$mapel->id_mapel] = $h == null ? 0 : $h->k_rata_rata;
                $pts = $this->rapor->getNilaiMapelPtsSiswa($mapel->id_mapel, $id_siswa, $tp->id_tp, $smt->id_smt);
                $nilaiPts[$id_siswa][$mapel->id_mapel] = $pts == null ? 0 : $pts->nilai;
                $pas = $this->rapor->getNilaiMapelPasSiswa($mapel->id_mapel, $id_siswa, $tp->id_tp, $smt->id_smt);
                $nilaiPas[$id_siswa][$mapel->id_mapel] = $pas == null ? 0 : $pas->akhir;
            }
            $ns = $this->rapor->getRankingKelas($id_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt);
            $nilai[$siswa->id_siswa] = $ns == null ? $dummyNilai : $ns;
            $i++;
            goto Wm47X;
        }
        $no = $i + 1;
        $s = ["id_catatan" => $id_kelas . 1 . $no, "jenis" => "3", "kode" => $dummyKode[$i], "deskripsi" => $this->rapor->getDummyDeskripsiRanking()[$i], "rank" => $dummyRank[$i]];
        array_push($dummyDeskSaran, $s);
        $i++;
        goto SvBxv;
    }
    public function savePrestasi()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $input = json_decode($this->input->post("catatan", true));
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        foreach ($input as $d) {
            $data = ["id_catatan" => $d->id_catatan, "id_kelas" => $d->kelas, "jenis" => $d->jenis, "kode" => $d->kode, "rank" => $d->rank, "deskripsi" => $d->deskripsi, "id_tp" => $tp->id_tp, "id_smt" => $smt->id_smt];
            $update = $this->db->replace("rapor_data_catatan", $data);
        }
        $data["status"] = $update;
        $this->output_json($data);
    }
    public function importPrestasi($id_kelas)
    {
        $this->load->model("Dashboard_model", "dashboard");
        $input = json_decode($this->input->post("nilai", true));
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $datas = [];
        foreach ($input as $in) {
            $id_siswa = $in[12];
            $datas[] = ["id_ranking" => $id_kelas . $id_siswa . $tp->id_tp . $smt->id_smt, "id_siswa" => $id_siswa, "id_kelas" => $id_kelas, "id_tp" => $tp->id_tp, "id_smt" => $smt->id_smt, "ranking" => $in[4], "deskripsi" => $in[5], "p1" => $in[6], "p1_desk" => $in[7], "p2" => $in[8], "p2_desk" => $in[9], "p3" => $in[10], "p3_desk" => $in[11]];
        }
        $updated = 0;
        foreach ($datas as $data) {
            $update = $this->db->replace("rapor_prestasi", $data);
            if (!$update) {
                goto mu4z5;
            }
            $updated++;
            mu4z5:
        }
        echo json_encode($updated);
    }
    public function raporCatatan()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $user = $this->ion_auth->user()->row();
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        $id_kelas = $guru->wali_kelas;
        $kelas = $this->kelas->get_one($id_kelas);
        $dummyDeskAbsensi = [];
        $dummyRank = ["1 ~ 3", "4 ~ 10", "11 ~ 15", "16 > >"];
        $dummyKode = ["1", "4", "11", "16"];
        $i = 0;
        aYgkQ:
        if (!($i < 4)) {
            $dummyDeskCatatan = [];
            $i = 0;
            K0pVG:
            if (!($i < 6)) {
                $deskAbsensi = $this->rapor->getDeskripsiCatatanByJenis($id_kelas, "1", $tp->id_tp, $smt->id_smt);
                if (!(count($deskAbsensi) === 0)) {
                    goto wz3ab;
                }
                $deskAbsensi = json_decode(json_encode($dummyDeskAbsensi));
                wz3ab:
                $deskCatatan = $this->rapor->getDeskripsiCatatanByJenis($id_kelas, "2", $tp->id_tp, $smt->id_smt);
                if (!(count($deskCatatan) === 0)) {
                    goto iUbtL;
                }
                $deskCatatan = json_decode(json_encode($dummyDeskCatatan));
                iUbtL:
                $siswas = $this->kelas->getKelasSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
                $nilai = [];
                $i = 0;
                vBig6:
                if (!($i < count($siswas))) {
                    $data = ["user" => $user, "judul" => "Absensi & Catatan Kelas ", "subjudul" => "Input Nilai", "setting" => $this->dashboard->getSetting(), "guru" => $guru, "kelas" => $kelas, "siswa" => $siswas, "nilai" => $nilai, "deskAbsensi" => $deskAbsensi, "deskCatatan" => $deskCatatan];
                    $data["tp"] = $this->dashboard->getTahun();
                    $data["tp_active"] = $tp;
                    $data["smt"] = $this->dashboard->getSemester();
                    $data["smt_active"] = $smt;
                    $this->load->view("members/guru/templates/header", $data);
                    $this->load->view("members/guru/rapor/catatan/data");
                    $this->load->view("members/guru/templates/footer");
                    // [PHPDeobfuscator] Implied return
                    return;
                }
                $siswa = $siswas[$i];
                $dummyNilai = ["s" => '', "i" => '', "a" => '', "op1" => '', "op2" => '', "op3" => ''];
                $ns = $this->rapor->getCatatanKelas($id_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt);
                $nilai[$siswa->id_siswa] = $ns == null ? $dummyNilai : unserialize($ns->nilai);
                $i++;
                goto vBig6;
            }
            $no = $i + 1;
            $s = ["id_sikap" => $id_kelas . 2 . $no, "jenis" => "2", "kode" => $no, "deskripsi" => $this->rapor->getDummyDeskripsiCatatan()[$i], "rank" => ''];
            array_push($dummyDeskCatatan, $s);
            $i++;
            goto K0pVG;
        }
        $no = $i + 1;
        $s = ["id_catatan" => $id_kelas . 1 . $no, "jenis" => "1", "kode" => $dummyKode[$i], "deskripsi" => $this->rapor->getDummyDeskripsiAbsensi()[$i], "rank" => $dummyRank[$i]];
        array_push($dummyDeskAbsensi, $s);
        $i++;
        goto aYgkQ;
    }
    public function saveCatatan()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $input = json_decode($this->input->post("catatan", true));
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        foreach ($input as $d) {
            $data = ["id_catatan" => $d->id_catatan, "id_kelas" => $d->kelas, "jenis" => $d->jenis, "kode" => $d->kode, "rank" => $d->rank, "deskripsi" => $d->deskripsi, "id_tp" => $tp->id_tp, "id_smt" => $smt->id_smt];
            $update = $this->db->replace("rapor_data_catatan", $data);
        }
        $data["status"] = $update;
        $this->output_json($data);
    }
    public function importCatatan($id_kelas)
    {
        $this->load->model("Dashboard_model", "dashboard");
        $input = json_decode($this->input->post("nilai", true));
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $datas = [];
        foreach ($input as $in) {
            $id_siswa = $in[10];
            if (!($id_siswa != "id")) {
                goto BigQg;
            }
            $datas[] = ["id_catatan_wali" => $id_kelas . $id_siswa . $tp->id_tp . $smt->id_smt, "id_siswa" => $id_siswa, "id_kelas" => $id_kelas, "nilai" => serialize(["op1" => $in[3], "op2" => $in[4], "op3" => $in[5], "s" => $in[6], "i" => $in[7], "a" => $in[8]]), "deskripsi" => $in[9], "id_tp" => $tp->id_tp, "id_smt" => $smt->id_smt];
            BigQg:
        }
        $updated = 0;
        foreach ($datas as $data) {
            $update = $this->db->replace("rapor_catatan_wali", $data);
            if (!$update) {
                goto ha1Hu;
            }
            $updated++;
            ha1Hu:
        }
        echo json_encode($updated);
    }
    public function raporFisik()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $user = $this->ion_auth->user()->row();
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        $id_kelas = $guru->wali_kelas;
        $kelas = $this->kelas->get_one($id_kelas);
        $dummyDeskFisik = [];
        $jenis = ["1", "2", "3", "4"];
        $i = 0;
        Gl2Y7:
        if (!($i < 4)) {
            $deskFisik = $this->rapor->getDeskripsiFisikKelas($id_kelas, $tp->id_tp, $smt->id_smt);
            if (!($deskFisik == null)) {
                goto TPv5T;
            }
            $deskFisik = json_decode(json_encode($dummyDeskFisik));
            TPv5T:
            $siswas = $this->kelas->getKelasSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
            $nilai = [];
            if ($smt->id_smt === "1") {
                $other = "2";
                goto hMlVp;
            }
            $other = "1";
            hMlVp:
            $i = 0;
            y3kBn:
            if (!($i < count($siswas))) {
                $data = ["user" => $user, "judul" => "Absensi & Catatan Kelas ", "subjudul" => "Input Nilai", "setting" => $this->dashboard->getSetting(), "guru" => $guru, "kelas" => $kelas, "siswa" => $siswas, "nilai" => $nilai, "deskFisik" => $deskFisik];
                $data["tp"] = $this->dashboard->getTahun();
                $data["tp_active"] = $tp;
                $data["smt"] = $this->dashboard->getSemester();
                $data["smt_active"] = $smt;
                $this->load->view("members/guru/templates/header", $data);
                $this->load->view("members/guru/rapor/fisik/data");
                $this->load->view("members/guru/templates/footer");
                // [PHPDeobfuscator] Implied return
                return;
            }
            $siswa = $siswas[$i];
            $dummyNilai = ["kondisi" => ["telinga" => '', "mata" => '', "gigi" => '', "lain" => ''], "smt" . $smt->id_smt => ["tinggi" => '', "berat" => '', "tp" => $tp->id_tp], "smt" . $other => ["tinggi" => '', "berat" => '', "tp" => $tp->id_tp]];
            $ns = $this->rapor->getFisikKelas($id_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt);
            $ns2 = $this->rapor->getFisikKelas($id_kelas, $siswa->id_siswa, $tp->id_tp, $other);
            $nilai[$siswa->id_siswa] = $ns != null ? ["kondisi" => unserialize($ns->kondisi), "smt" . $ns->id_smt => ["tinggi" => $ns->tinggi, "berat" => $ns->berat, "tp" => $ns->id_tp], "smt" . $other => ["tinggi" => $ns2 != null ? $ns2->tinggi : '', "berat" => $ns2 != null ? $ns2->berat : '', "tp" => $tp->id_tp]] : $dummyNilai;
            $i++;
            goto y3kBn;
        }
        $no = $i + 1;
        foreach ($jenis as $jns) {
            $s = ["id_fisik" => $id_kelas . $jns . $no, "jenis" => $jns, "kode" => $no, "deskripsi" => $this->rapor->getDummyDeskripsiFisik($jns)[$i]];
            array_push($dummyDeskFisik, $s);
        }
        $i++;
        goto Gl2Y7;
    }
    public function saveFisik()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $kelas = $this->input->post("kelas", true);
        $input = json_decode($this->input->post("fisik", true));
        $update = false;
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        foreach ($input as $d) {
            $kode = $d[0];
            $jns = $d[0];
            $data = ["id_fisik" => $kelas . $jns . $kode, "id_kelas" => $kelas, "jenis" => $d->jenis, "kode" => $d->kode, "deskripsi" => $d->deskripsi, "id_tp" => $tp->id_tp, "id_smt" => $smt->id_smt];
            $update = $this->db->replace("rapor_data_fisik", $data);
        }
        $data["status"] = $update;
        $this->output_json($data);
    }
    public function importFisik($id_kelas)
    {
        $this->load->model("Dashboard_model", "dashboard");
        $input = json_decode($this->input->post("nilai", true));
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $datas = [];
        foreach ($input as $in) {
            $id_siswa = $in[11];
            $tinggi = $smt->id_smt == 1 ? $in[3] : $in[4];
            $berat = $smt->id_smt == 1 ? $in[5] : $in[6];
            if (!($id_siswa != "id")) {
                goto rFxPv;
            }
            $datas[] = ["id_fisik" => $id_kelas . $id_siswa . $tp->id_tp . $smt->id_smt, "id_kelas" => $id_kelas, "id_siswa" => $id_siswa, "id_tp" => $tp->id_tp, "id_smt" => $smt->id_smt, "tinggi" => $tinggi, "berat" => $berat, "kondisi" => serialize(["telinga" => $in[7], "mata" => $in[8], "gigi" => $in[9], "lain" => $in[10]])];
            rFxPv:
        }
        $updated = 0;
        foreach ($datas as $data) {
            $update = $this->db->replace("rapor_fisik", $data);
            if (!$update) {
                goto VbmRT;
            }
            $updated++;
            VbmRT:
        }
        echo json_encode($updated);
    }
    public function raporNaik()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $user = $this->ion_auth->user()->row();
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        $id_kelas = $guru->wali_kelas;
        $kelas = $this->kelas->get_one($id_kelas);
        $siswas = $this->rapor->getKenaikanSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
        $data = ["user" => $user, "judul" => "Kenaikan Kelas ", "subjudul" => "Siswa Kelas ", "setting" => $this->dashboard->getSetting(), "guru" => $guru, "kelas" => $kelas, "siswas" => $siswas];
        $data["tp"] = $this->dashboard->getTahun();
        $data["tp_active"] = $tp;
        $data["smt"] = $this->dashboard->getSemester();
        $data["smt_active"] = $smt;
        $this->load->view("members/guru/templates/header", $data);
        $this->load->view("members/guru/rapor/kenaikan/data");
        $this->load->view("members/guru/templates/footer");
    }
    public function saveNaik()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $input = json_decode($this->input->post("naik", true));
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $updated = 0;
        foreach ($input as $d) {
            $data = ["id_naik" => $d->id_siswa . $tp->id_tp . $smt->id_smt, "id_siswa" => $d->id_siswa, "id_tp" => $tp->id_tp, "id_smt" => $smt->id_smt, "naik" => $d->naik];
            $update = $this->db->replace("rapor_naik", $data);
            if (!$update) {
                goto NsxUt;
            }
            $updated++;
            NsxUt:
        }
        echo json_encode($updated);
    }
    public function cetakPts()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $user = $this->ion_auth->user()->row();
        $setting = $this->dashboard->getSetting();
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $this->db->trans_start();
        $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        $id_kelas = $guru->wali_kelas;
        $kelas = $this->kelas->get_one($id_kelas);
        $siswas = $this->kelas->getKelasSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
        $jurusan = $this->kelas->getJurusanById($kelas->jurusan_id);
        $kelompoks = $this->master->getKodeKelompokMapel();
        $kategori_mapel = $this->master->getKategoriKelompokMapel();
        $arrk = [];
        foreach ($kategori_mapel as $kk => $km) {
            if (in_array($km, $arrk)) {
                goto Zx37P;
            }
            array_push($arrk, $km->kode_kel_mapel);
            Zx37P:
        }
        $mapels = $this->master->getAllMapel(empty($arrk) ? null : $arrk, isset($jurusan->mapel_peminatan) ? $jurusan->mapel_peminatan : null);
        $nilaiHarian = [];
        $nilaiPts = [];
        $dummyNilai = ["p1" => '', "p2" => '', "p3" => '', "p4" => '', "p5" => '', "k1" => '', "k2" => '', "k3" => '', "k4" => '', "k5" => ''];
        $settingRapor = $this->rapor->getRaporSetting($tp->id_tp, $smt->id_smt);
        $kkm = [];
        $arr_mapels = [];
        $arr_siswas = [];
        foreach ($mapels as $mapel) {
            $arr_mapels[] = $mapel->id_mapel;
        }
        $i = 0;
        WfNrB:
        if (!($i < count($siswas))) {
            $nilaiPts = $this->rapor->getArrNilaiMapelPtsSiswa($arr_mapels, $arr_siswas, $tp->id_tp, $smt->id_smt);
            $nilaiHarian = $this->rapor->getArrNilaiMapelHarianSiswa($arr_mapels, $arr_siswas, $tp->id_tp, $smt->id_smt);
            $data = ["user" => $user, "judul" => "Rapor PTS", "subjudul" => "Cetak Rapor PTS", "setting" => $setting];
            $data["tp"] = $this->dashboard->getTahun();
            $data["tp_active"] = $tp;
            $data["smt"] = $this->dashboard->getSemester();
            $data["smt_active"] = $smt;
            $data["guru"] = $guru;
            $data["siswas"] = $siswas;
            $data["kelas"] = $kelas->nama_kelas;
            $data["mapels"] = $mapels;
            $data["kelompoks"] = $kelompoks;
            $data["nilai_pts"] = $nilaiPts;
            $data["nilai_harian"] = $nilaiHarian;
            $data["kkm"] = $kkm;
            $data["rapor"] = $this->rapor->getRaporSetting($tp->id_tp, $smt->id_smt);
            $this->db->trans_complete();
            $this->load->view("members/guru/templates/header", $data);
            $this->load->view("members/guru/rapor/cetak/pts");
            $this->load->view("members/guru/templates/footer");
            // [PHPDeobfuscator] Implied return
            return;
        }
        $siswa = $siswas[$i];
        $id_siswa = $siswa->id_siswa;
        $arr_siswas[] = $id_siswa;
        foreach ($mapels as $mapel) {
            if (isset($settingRapor) && $settingRapor->kkm_tunggal == "1") {
                $kkm[$mapel->id_mapel] = $settingRapor;
                goto fnTPk;
            }
            $kkm[$mapel->id_mapel] = $this->rapor->getKkm($mapel->id_mapel . $id_kelas . $tp->id_tp . $smt->id_smt . "1");
            fnTPk:
        }
        $i++;
        goto WfNrB;
    }
    public function cetakAkhir()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $user = $this->ion_auth->user()->row();
        $setting = $this->dashboard->getSetting();
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        $id_kelas = $guru->wali_kelas;
        $kelas = $this->kelas->get_one($id_kelas);
        $jurusan = $this->kelas->getJurusanById($kelas->jurusan_id);
        $kelompoks = $this->master->getKodeKelompokMapel();
        $siswas = $this->rapor->getDetailSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
        $kategori_mapel = $this->master->getKategoriKelompokMapel();
        $arrk = [];
        foreach ($kategori_mapel as $kk => $km) {
            if (in_array($km, $arrk)) {
                goto UywYA;
            }
            array_push($arrk, $km->kode_kel_mapel);
            UywYA:
        }
        $mapels = $this->master->getAllMapel(empty($arrk) ? null : $arrk, isset($jurusan->mapel_peminatan) ? $jurusan->mapel_peminatan : null);
        $ekstras = $this->kelas->getKelasEkskul($id_kelas, $tp->id_tp, $smt->id_smt);
        $settingRapor = $this->rapor->getRaporSetting($tp->id_tp, $smt->id_smt);
        $kkm = [];
        $sikap = [];
        $nilai = [];
        $fisik = [];
        $desks = [];
        $absensi = [];
        $mapelEkstra = [];
        $nilaiEkstra = [];
        if ($smt->id_smt === "1") {
            $other = "2";
            goto JFhlz;
        }
        $other = "1";
        JFhlz:
        $nilai_sikap = $this->rapor->getNilaiSikapByKelas($id_kelas, $tp->id_tp, $smt->id_smt);
        $nilai_rapor = $this->rapor->getNilaiRaporByKelas($id_kelas, $tp->id_tp, $smt->id_smt);
        $prestasis = $this->rapor->getPrestasiByKelas($id_kelas, $tp->id_tp, $smt->id_smt);
        $catatans = $this->rapor->getCatatanWaliByKelas($id_kelas, $tp->id_tp, $smt->id_smt);
        foreach ($catatans as $catatan) {
            $catatan->nilai = unserialize($catatan->nilai);
        }
        $i = 0;
        TdGmn:
        if (!($i < count($siswas))) {
            $kkm = $this->rapor->getAllKkmRaporAkhir($id_kelas, $tp->id_tp, $smt->id_smt);
            $data = ["user" => $user, "judul" => "Rapor Akhir", "subjudul" => "Cetak Rapor Akhir", "setting" => $setting];
            $data["tp"] = $this->dashboard->getTahun();
            $data["tp_active"] = $tp;
            $data["smt"] = $this->dashboard->getSemester();
            $data["smt_active"] = $smt;
            $data["tp_name"] = $this->dashboard->getTahunById($tp->id_tp);
            $data["smt_name"] = $this->dashboard->getSemesterById($smt->id_smt);
            $data["guru"] = $guru;
            $data["siswas"] = $siswas;
            $data["kelas"] = $kelas->nama_kelas;
            $data["lvl_kelas"] = $kelas->level;
            $data["mapels"] = $mapels;
            $data["kelompoks"] = $kelompoks;
            $data["sikap"] = $sikap;
            $data["nilai"] = $nilai;
            $data["nilai_rapor"] = $nilai_rapor;
            $data["deskripsi"] = $desks;
            $data["absensi"] = $absensi;
            $data["fisik"] = $fisik;
            $data["nilai_ekstra"] = $nilaiEkstra;
            $data["mapel_ekstra"] = $mapelEkstra;
            $data["kkm"] = $kkm;
            $data["rapor"] = $settingRapor;
            $data["naik"] = $this->rapor->getKenaikanRapor($id_kelas, $tp->id_tp, $smt->id_smt);
            $this->load->view("members/guru/templates/header", $data);
            $this->load->view("members/guru/rapor/cetak/akhir");
            $this->load->view("members/guru/templates/footer");
            // [PHPDeobfuscator] Implied return
            return;
        }
        $siswa = $siswas[$i];
        $id_siswa = $siswa->id_siswa;
        $dummySikap = ["predikat" => ''];
        if (count($nilai_sikap) > 0) {
            foreach ($nilai_sikap as $nls) {
                if (!($nls->id_siswa == $id_siswa && $nls->jenis == "1")) {
                    goto jKet4;
                }
                $sikap[$id_siswa][1] = ["deskripsi" => $nls == null ? '' : $nls->deskripsi, "predikat" => $nls == null ? $dummySikap : unserialize($nls->nilai)];
                jKet4:
                if (!($nls->id_siswa == $id_siswa && $nls->jenis == "2")) {
                    goto VNWo8;
                }
                $sikap[$id_siswa][2] = ["deskripsi" => $nls == null ? '' : $nls->deskripsi, "predikat" => $nls == null ? $dummySikap : unserialize($nls->nilai)];
                VNWo8:
            }
            goto vszeb;
        }
        $sikap[$id_siswa][1] = ["deskripsi" => '', "predikat" => $dummySikap];
        $sikap[$id_siswa][2] = ["deskripsi" => '', "predikat" => $dummySikap];
        vszeb:
        foreach ($mapels as $mapel) {
            $dummyNilai = ["p_deskripsi" => '', "k_rata_rata" => '', "k_deskripsi" => '', "k_predikat" => '', "nilai" => '', "predikat" => ''];
            $key_mapel = array_search($mapel->id_mapel . $id_kelas . $id_siswa . $tp->id_tp . $smt->id_smt, array_column($nilai_rapor, "id_nilai_harian"));
            if (!($key_mapel !== false)) {
                goto JPlDc;
            }
            $nr = $nilai_rapor[$key_mapel];
            $nilai[$id_siswa][$mapel->id_mapel] = $nr;
            JPlDc:
        }
        $dummyDesks = ["ranking" => '', "rank_deskripsi" => '', "p1" => '', "p1_desk" => '', "p2" => '', "p2_desk" => '', "p3" => '', "p3_desk" => ''];
        $dummyAbsen = ["s" => " - ", "i" => " - ", "a" => " - ", "saran" => ''];
        $desks[$id_siswa] = isset($prestasis[$id_siswa]) ? $prestasis[$id_siswa] : $dummyDesks;
        $absensi[$id_siswa] = isset($catatans[$id_siswa]) ? $catatans[$id_siswa] : ["nilai" => $dummyAbsen];
        $dummyFisik = ["kondisi" => ["telinga" => '', "mata" => '', "gigi" => '', "lain" => ''], "smt" . $smt->id_smt => ["tinggi" => '', "berat" => '', "tp" => $tp->id_tp], "smt" . $other => ["tinggi" => '', "berat" => '', "tp" => $tp->id_tp]];
        $nf = $this->rapor->getFisikKelas($id_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt);
        $nf2 = $this->rapor->getFisikKelas($id_kelas, $siswa->id_siswa, $tp->id_tp, $other);
        $fisik[$siswa->id_siswa] = $nf != null ? ["kondisi" => unserialize($nf->kondisi), "smt" . $nf->id_smt => ["tinggi" => $nf->tinggi, "berat" => $nf->berat], "smt" . $other => ["tinggi" => $nf2 != null ? $nf2->tinggi : '', "berat" => $nf2 != null ? $nf2->berat : '']] : $dummyFisik;
        foreach ($ekstras as $ext) {
            $dummyEkstra = ["deskripsi" => '', "nilai" => '', "predikat" => ''];
            $arrEkstra = json_decode(json_encode(unserialize($ext->ekstra)));
            foreach ($arrEkstra as $ar) {
                $id_ekstra = $ar->ekstra;
                $mapelEkstra[$id_ekstra] = $this->kelas->getEkskulById($id_ekstra);
                if (!($id_ekstra != null)) {
                    goto JBO2r;
                }
                $ne = $this->rapor->getEkstraKelas($id_ekstra, $siswa->id_siswa, $tp->id_tp, $smt->id_smt);
                $nilaiEkstra[$id_siswa][$id_ekstra] = $ne == null ? $dummyEkstra : $ne;
                JBO2r:
            }
        }
        $i++;
        goto TdGmn;
    }
    public function cetakLeger()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $setting = $this->dashboard->getSetting();
        $user = $this->ion_auth->user()->row();
        $data = ["user" => $user, "judul" => "Leger Kelas ", "subjudul" => "Cetak Leger Kelas ", "setting" => $setting];
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        $id_kelas = $guru->wali_kelas;
        $kelases = $this->kelas->get_one($id_kelas);
        $siswas = $this->kelas->getKelasSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
        $mapels = $this->master->getAllMapel();
        $ekstras = $this->kelas->getKelasEkskul($id_kelas, $tp->id_tp, $smt->id_smt);
        $prestasis = $this->rapor->getPrestasiByKelas($id_kelas, $tp->id_tp, $smt->id_smt);
        $catatans = $this->rapor->getCatatanWaliByKelas($id_kelas, $tp->id_tp, $smt->id_smt);
        foreach ($catatans as $catatan) {
            $catatan->nilai = unserialize($catatan->nilai);
        }
        $setting_rapor = $this->rapor->getRaporSetting($tp->id_tp, $smt->id_smt);
        $kkm = [];
        $sikap = [];
        $nilai = [];
        $nilaiPts = [];
        $desks = [];
        $absensi = [];
        $mapelEkstra = [];
        $nilaiEkstra = [];
        $i = 0;
        sEt2Q:
        if (!($i < count($siswas))) {
            $data["tp"] = $this->dashboard->getTahun();
            $data["tp_active"] = $tp;
            $data["smt"] = $this->dashboard->getSemester();
            $data["smt_active"] = $smt;
            $data["guru"] = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
            $data["kelases"] = $kelases;
            $data["mapels"] = $mapels;
            $data["siswas"] = $siswas;
            $data["ekstras"] = $ekstras;
            $data["nilai"] = (array) json_decode(json_encode($nilai));
            $data["nilai_pts"] = (array) json_decode(json_encode($nilaiPts));
            $data["sikap"] = $sikap;
            $data["deskripsi"] = $desks;
            $data["absensi"] = $absensi;
            $data["nilai_ekstra"] = $nilaiEkstra;
            $data["mapel_ekstra"] = $mapelEkstra;
            $data["kkm"] = $kkm;
            $data["rapor"] = $setting_rapor;
            $data["naik"] = $this->rapor->getKenaikanRapor($id_kelas, $tp->id_tp, $smt->id_smt);
            $this->load->view("members/guru/templates/header", $data);
            $this->load->view("members/guru/rapor/leger/data");
            $this->load->view("members/guru/templates/footer");
            // [PHPDeobfuscator] Implied return
            return;
        }
        $siswa = $siswas[$i];
        $id_siswa = $siswa->id_siswa;
        foreach ($mapels as $mapel) {
            $dummySikap = ["predikat" => ''];
            $ns1 = $this->rapor->getNilaiSikapKelas($id_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt, "1");
            $sikap[$siswa->id_siswa][1] = ["deskripsi" => $ns1 == null ? '' : $ns1->deskripsi, "predikat" => $ns1 == null ? $dummySikap : unserialize($ns1->nilai)];
            $ns2 = $this->rapor->getNilaiSikapKelas($id_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt, "2");
            $sikap[$siswa->id_siswa][2] = ["deskripsi" => $ns2 == null ? '' : $ns2->deskripsi, "predikat" => $ns2 == null ? $dummySikap : unserialize($ns2->nilai)];
            $dummyNilai = ["k_rata_rata" => '', "k_predikat" => '', "p_rata_rata" => '', "nilai_pas" => '', "nilai" => '', "predikat" => ''];
            $nr = $this->rapor->getNilaiRapor($mapel->id_mapel, $id_kelas, $id_siswa, $tp->id_tp, $smt->id_smt);
            $nilai[$id_siswa][$mapel->id_mapel] = $nr == null ? $dummyNilai : $nr;
            $pts = $this->rapor->getNilaiMapelPtsSiswa($mapel->id_mapel, $id_siswa, $tp->id_tp, $smt->id_smt);
            $nilaiPts[$id_siswa][$mapel->id_mapel] = $pts == null ? 0 : $pts->nilai;
            $dummyAbsen = ["s" => '', "i" => '', "a" => ''];
            $absensi[$id_siswa] = isset($catatans[$id_siswa]) ? $catatans[$id_siswa]->nilai : $dummyAbsen;
            if (isset($setting_rapor->kkm_tunggal) && $setting_rapor->kkm_tunggal == "1") {
                $kkm[$mapel->id_mapel] = $setting_rapor;
                goto CAX3W;
            }
            $kkm[$mapel->id_mapel] = $this->rapor->getKkm($mapel->id_mapel . $id_kelas . $tp->id_tp . $smt->id_smt . "1");
            CAX3W:
            foreach ($ekstras as $ext) {
                $dummyEkstra = ["deskripsi" => '', "nilai" => '', "predikat" => ''];
                $arrEkstra = json_decode(json_encode(unserialize($ext->ekstra)));
                foreach ($arrEkstra as $ar) {
                    $id_ekstra = $ar->ekstra;
                    $mapelEkstra[$id_ekstra] = $this->kelas->getEkskulById($id_ekstra);
                    if (!($id_ekstra != null)) {
                        goto fmLJX;
                    }
                    $ne = $this->rapor->getEkstraKelas($id_ekstra, $siswa->id_siswa, $tp->id_tp, $smt->id_smt);
                    $nilaiEkstra[$id_siswa][$id_ekstra] = $ne == null ? json_decode(json_encode($dummyEkstra)) : $ne;
                    fmLJX:
                }
            }
        }
        $i++;
        goto sEt2Q;
    }
    public function downloadLeger()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $setting = $this->dashboard->getSetting();
        $user = $this->ion_auth->user()->row();
        $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        $id_kelas = $guru->wali_kelas;
        $kelases = $this->kelas->get_one($id_kelas);
        $siswas = $this->kelas->getKelasSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
        $mapels = $this->master->getAllMapel();
        $ekstras = $this->kelas->getKelasEkskul($id_kelas, $tp->id_tp, $smt->id_smt);
        $prestasis = $this->rapor->getPrestasiByKelas($id_kelas, $tp->id_tp, $smt->id_smt);
        $catatans = $this->rapor->getCatatanWaliByKelas($id_kelas, $tp->id_tp, $smt->id_smt);
        foreach ($catatans as $catatan) {
            $catatan->nilai = unserialize($catatan->nilai);
        }
        $setting_rapor = $this->rapor->getRaporSetting($tp->id_tp, $smt->id_smt);
        $kkm = [];
        $sikap = [];
        $nilai = [];
        $nilaiPts = [];
        $desks = [];
        $absensi = [];
        $mapelEkstra = [];
        $nilaiEkstra = [];
        $i = 0;
        devx9:
        if (!($i < count($siswas))) {
            $data["tp"] = $this->dashboard->getTahun();
            $data["tp_active"] = $tp;
            $data["smt"] = $this->dashboard->getSemester();
            $data["smt_active"] = $smt;
            $data["guru"] = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
            $data["kelases"] = $kelases;
            $data["mapels"] = $mapels;
            $data["siswas"] = $siswas;
            $data["ekstras"] = $ekstras;
            $data["nilai"] = (array) json_decode(json_encode($nilai));
            $data["nilai_pts"] = (array) json_decode(json_encode($nilaiPts));
            $data["sikap"] = $sikap;
            $data["deskripsi"] = $desks;
            $data["absensi"] = $absensi;
            $data["nilai_ekstra"] = $nilaiEkstra;
            $data["mapel_ekstra"] = $mapelEkstra;
            $data["kkm"] = $kkm;
            $data["rapor"] = $setting_rapor;
            $data["naik"] = $this->rapor->getKenaikanRapor($id_kelas, $tp->id_tp, $smt->id_smt);
            $no = [];
            $nisn = [];
            $nama = [];
            $p1 = [];
            $p2 = [];
            $p3 = [];
            $p4 = [];
            $p5 = [];
            $p6 = [];
            $p7 = [];
            $p8 = [];
            $k1 = [];
            $k2 = [];
            $k3 = [];
            $k4 = [];
            $k5 = [];
            $k6 = [];
            $k7 = [];
            $k8 = [];
            $i = 0;
            LV48A:
            if (!($i < count($siswas))) {
                $this->output_json($data);
                // [PHPDeobfuscator] Implied return
                return;
            }
            $siswa = $siswas[$i];
            $nilai = $nilai[$siswa->id_siswa];
            $no[] = $i + 1;
            $nisn[] = $siswa->nisn;
            $nama[] = $siswa->nama;
            $p1[] = $nilai->p1;
            $p2[] = $nilai->p2;
            $p3[] = $nilai->p3;
            $p4[] = $nilai->p4;
            $p5[] = $nilai->p5;
            $p6[] = $nilai->p6;
            $p7[] = $nilai->p7;
            $p8[] = $nilai->p8;
            $k1[] = $nilai->k1;
            $k2[] = $nilai->k2;
            $k3[] = $nilai->k3;
            $k4[] = $nilai->k4;
            $k5[] = $nilai->k5;
            $k6[] = $nilai->k6;
            $k7[] = $nilai->k7;
            $k8[] = $nilai->k8;
            $i++;
            goto LV48A;
        }
        $siswa = $siswas[$i];
        $id_siswa = $siswa->id_siswa;
        foreach ($mapels as $mapel) {
            $dummySikap = ["predikat" => ''];
            $ns1 = $this->rapor->getNilaiSikapKelas($id_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt, "1");
            $sikap[$siswa->id_siswa][1] = ["deskripsi" => $ns1 == null ? '' : $ns1->deskripsi, "predikat" => $ns1 == null ? $dummySikap : unserialize($ns1->nilai)];
            $ns2 = $this->rapor->getNilaiSikapKelas($id_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt, "2");
            $sikap[$siswa->id_siswa][2] = ["deskripsi" => $ns2 == null ? '' : $ns2->deskripsi, "predikat" => $ns2 == null ? $dummySikap : unserialize($ns2->nilai)];
            $dummyNilai = ["k_rata_rata" => '', "k_predikat" => '', "p_rata_rata" => '', "nilai_pas" => '', "nilai" => '', "predikat" => ''];
            $nr = $this->rapor->getNilaiRapor($mapel->id_mapel, $id_kelas, $id_siswa, $tp->id_tp, $smt->id_smt);
            $nilai[$id_siswa][$mapel->id_mapel] = $nr == null ? $dummyNilai : $nr;
            $pts = $this->rapor->getNilaiMapelPtsSiswa($mapel->id_mapel, $id_siswa, $tp->id_tp, $smt->id_smt);
            $nilaiPts[$id_siswa][$mapel->id_mapel] = $pts == null ? 0 : $pts->nilai;
            $dummyDesks = ["ranking" => '', "rank_deskripsi" => '', "p1" => '', "p1_desk" => '', "p2" => '', "p2_desk" => '', "p3" => '', "p3_desk" => '', "saran" => ''];
            $dummyAbsen = ["s" => '', "i" => '', "a" => ''];
            $absensi[$id_siswa] = isset($catatans[$id_siswa]) ? $catatans[$id_siswa]->nilai : ["nilai" => $dummyAbsen];
            if ($setting_rapor->kkm_tunggal == "1") {
                $kkm[$mapel->id_mapel] = $setting_rapor;
                goto D1xhn;
            }
            $kkm[$mapel->id_mapel] = $this->rapor->getKkm($mapel->id_mapel . $id_kelas . $tp->id_tp . $smt->id_smt . "1");
            D1xhn:
            foreach ($ekstras as $ext) {
                $dummyEkstra = ["deskripsi" => '', "nilai" => '', "predikat" => ''];
                $arrEkstra = json_decode(json_encode(unserialize($ext->ekstra)));
                foreach ($arrEkstra as $ar) {
                    $id_ekstra = $ar->ekstra;
                    $mapelEkstra[$id_ekstra] = $this->kelas->getEkskulById($id_ekstra);
                    if (!($id_ekstra != null)) {
                        goto UIyFG;
                    }
                    $ne = $this->rapor->getEkstraKelas($id_ekstra, $siswa->id_siswa, $tp->id_tp, $smt->id_smt);
                    $nilaiEkstra[$id_siswa][$id_ekstra] = $ne == null ? json_decode(json_encode($dummyEkstra)) : $ne;
                    UIyFG:
                }
            }
        }
        $i++;
        goto devx9;
    }
    public function dkn()
    {
        $this->load->model("Dashboard_model", "dashboard");
        $setting = $this->dashboard->getSetting();
        $user = $this->ion_auth->user()->row();
        $data = ["user" => $user, "judul" => "Daftar Kumpulan Nilai Kelas ", "subjudul" => "Cetak DKN ", "setting" => $setting];
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $guru = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
        $id_kelas = $guru->wali_kelas;
        $kelases = $this->kelas->get_one($id_kelas);
        $siswas = $this->kelas->getKelasSiswa($id_kelas, $tp->id_tp, $smt->id_smt);
        $mapels = $this->master->getAllMapel();
        $ekstras = $this->kelas->getKelasEkskul($id_kelas, $tp->id_tp, $smt->id_smt);
        $prestasis = $this->rapor->getPrestasiByKelas($id_kelas, $tp->id_tp, $smt->id_smt);
        $catatans = $this->rapor->getCatatanWaliByKelas($id_kelas, $tp->id_tp, $smt->id_smt);
        foreach ($catatans as $catatan) {
            $catatan->nilai = unserialize($catatan->nilai);
        }
        $setting_rapor = $this->rapor->getRaporSetting($tp->id_tp, $smt->id_smt);
        $kkm = [];
        $sikap = [];
        $nilai = [];
        $nilaiPts = [];
        $desks = [];
        $absensi = [];
        $mapelEkstra = [];
        $nilaiEkstra = [];
        $i = 0;
        tuD94:
        if (!($i < count($siswas))) {
            $data["tp"] = $this->dashboard->getTahun();
            $data["tp_active"] = $tp;
            $data["smt"] = $this->dashboard->getSemester();
            $data["smt_active"] = $smt;
            $data["guru"] = $this->dashboard->getDataGuruByUserId($user->id, $tp->id_tp, $smt->id_smt);
            $data["kelases"] = $kelases;
            $data["mapels"] = $mapels;
            $data["siswas"] = $siswas;
            $data["ekstras"] = $ekstras;
            $data["nilai"] = $nilai;
            $data["nilai_pts"] = $nilaiPts;
            $data["sikap"] = $sikap;
            $data["deskripsi"] = $desks;
            $data["absensi"] = $absensi;
            $data["nilai_ekstra"] = $nilaiEkstra;
            $data["mapel_ekstra"] = $mapelEkstra;
            $data["kkm"] = $kkm;
            $data["rapor"] = $setting_rapor;
            $data["naik"] = $this->rapor->getKenaikanRapor($id_kelas, $tp->id_tp, $smt->id_smt);
            $this->load->view("members/guru/templates/header", $data);
            $this->load->view("members/guru/rapor/dkn/data");
            $this->load->view("members/guru/templates/footer");
            // [PHPDeobfuscator] Implied return
            return;
        }
        $siswa = $siswas[$i];
        $id_siswa = $siswa->id_siswa;
        foreach ($mapels as $mapel) {
            $dummySikap = ["predikat" => ''];
            $ns1 = $this->rapor->getNilaiSikapKelas($id_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt, "1");
            $sikap[$siswa->id_siswa][1] = ["deskripsi" => $ns1 == null ? '' : $ns1->deskripsi, "predikat" => $ns1 == null ? $dummySikap : unserialize($ns1->nilai)];
            $ns2 = $this->rapor->getNilaiSikapKelas($id_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt, "2");
            $sikap[$siswa->id_siswa][2] = ["deskripsi" => $ns2 == null ? '' : $ns2->deskripsi, "predikat" => $ns2 == null ? $dummySikap : unserialize($ns2->nilai)];
            $dummyNilai = ["mapel" => $mapel->nama_mapel, "k_rata_rata" => '', "k_predikat" => '', "p_rata_rata" => '', "nilai_pas" => '', "nilai" => '', "predikat" => ''];
            $nr = $this->rapor->getNilaiRapor($mapel->id_mapel, $id_kelas, $id_siswa, $tp->id_tp, $smt->id_smt);
            $nr["mapel"] = $mapel->nama_mapel;
            $nilai[$id_siswa][$mapel->id_mapel] = $nr == null ? $dummyNilai : $nr;
            $pts = $this->rapor->getNilaiMapelPtsSiswa($mapel->id_mapel, $id_siswa, $tp->id_tp, $smt->id_smt);
            $nilaiPts[$id_siswa][$mapel->id_mapel] = $pts == null ? 0 : $pts->nilai;
            $dummyDesks = ["ranking" => '', "rank_deskripsi" => '', "p1" => '', "p1_desk" => '', "p2" => '', "p2_desk" => '', "p3" => '', "p3_desk" => '', "saran" => ''];
            $dummyAbsen = ["s" => '', "i" => '', "a" => ''];
            $nd = $this->rapor->getRaporDeskripsi($id_kelas, $siswa->id_siswa, $tp->id_tp, $smt->id_smt);
            $desks[$id_siswa] = $nd == null ? json_decode(json_encode($dummyDesks)) : $nd;
            $absensi[$id_siswa] = $nd == null ? $dummyAbsen : unserialize($nd->nilai);
            if (isset($setting_rapor->kkm_tunggal) && $setting_rapor->kkm_tunggal == "1") {
                $kkm[$mapel->id_mapel] = $setting_rapor;
                goto eiNvX;
            }
            $kkm[$mapel->id_mapel] = $this->rapor->getKkm($mapel->id_mapel . $id_kelas . $tp->id_tp . $smt->id_smt . "1");
            eiNvX:
            foreach ($ekstras as $ext) {
                $dummyEkstra = ["deskripsi" => '', "nilai" => '', "predikat" => ''];
                $arrEkstra = json_decode(json_encode(unserialize($ext->ekstra)));
                foreach ($arrEkstra as $ar) {
                    $id_ekstra = $ar->ekstra;
                    $mapelEkstra[$id_ekstra] = $this->kelas->getEkskulById($id_ekstra);
                    if (!($id_ekstra != null)) {
                        goto UfFNi;
                    }
                    $ne = $this->rapor->getEkstraKelas($id_ekstra, $siswa->id_siswa, $tp->id_tp, $smt->id_smt);
                    $nilaiEkstra[$id_siswa][$id_ekstra] = $ne == null ? json_decode(json_encode($dummyEkstra)) : $ne;
                    UfFNi:
                }
            }
        }
        $i++;
        goto tuD94;
    }
}
