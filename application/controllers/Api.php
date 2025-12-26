<?php
defined("BASEPATH") or exit("No direct script access allowed");

/**
 * API Controller untuk Flutter Mobile App
 * Semua endpoint mengembalikan JSON
 */
class Api extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library("ion_auth");
        $this->load->library("form_validation");
        $this->load->library("user_agent");
        $this->load->model("Dashboard_model", "dashboard");
        $this->load->model("Cbt_model", "cbt");
    }

    private function output_json($data)
    {
        $this->output
            ->set_content_type("application/json")
            ->set_output(json_encode($data));
    }

    private function check_login()
    {
        if (!$this->ion_auth->logged_in()) {
            $this->output_json(["status" => false, "message" => "Unauthorized", "code" => 401]);
            return false;
        }
        return true;
    }

    private function get_tp_smt()
    {
        return [
            'tp' => $this->dashboard->getTahunActive(),
            'smt' => $this->dashboard->getSemesterActive()
        ];
    }

    private function get_siswa()
    {
        $user = $this->ion_auth->user()->row();
        $periode = $this->get_tp_smt();
        return $this->cbt->getDataSiswa($user->username, $periode['tp']->id_tp, $periode['smt']->id_smt);
    }

    // ==================== AUTH ====================

    public function login()
    {
        $identity = $this->input->post("identity");
        $password = $this->input->post("password");

        if (empty($identity) || empty($password)) {
            $this->output_json(["status" => false, "message" => "Username dan password harus diisi"]);
            return;
        }

        if ($this->ion_auth->login($identity, $password, true)) {
            $user = $this->ion_auth->user()->row();
            $group = $this->ion_auth->get_users_groups($user->id)->row();

            if ($group->name !== "siswa") {
                $this->ion_auth->logout();
                $this->output_json(["status" => false, "message" => "Hanya siswa yang bisa login via aplikasi mobile"]);
                return;
            }

            $siswa = $this->get_siswa();
            $periode = $this->get_tp_smt();
            $cbt_info = $this->cbt->getSiswaCbtInfo($siswa->id_siswa, $periode['tp']->id_tp, $periode['smt']->id_smt);

            $this->output_json([
                "status" => true,
                "message" => "Login berhasil",
                "siswa" => $siswa,
                "cbt_info" => $cbt_info,
                "session_id" => session_id()
            ]);
        } else {
            $msg = $this->ion_auth->is_max_login_attempts_exceeded($identity) 
                ? "Terlalu banyak percobaan login" 
                : "Username atau password salah";
            $this->output_json(["status" => false, "message" => $msg]);
        }
    }

    public function logout()
    {
        $this->ion_auth->logout();
        $this->output_json(["status" => true, "message" => "Logout berhasil"]);
    }

    public function checksession()
    {
        $this->output_json([
            "status" => $this->ion_auth->logged_in(),
            "message" => $this->ion_auth->logged_in() ? "Session valid" : "Session expired"
        ]);
    }

    // ==================== SISWA ====================

    public function siswa()
    {
        if (!$this->check_login()) return;
        $siswa = $this->get_siswa();
        $this->output_json(["status" => true, "data" => $siswa]);
    }

    // ==================== CBT ====================

    public function cbt()
    {
        if (!$this->check_login()) return;

        $siswa = $this->get_siswa();
        $periode = $this->get_tp_smt();
        $today = strtotime(date("Y-m-d"));

        $cbt_info = $this->cbt->getSiswaCbtInfo($siswa->id_siswa, $periode['tp']->id_tp, $periode['smt']->id_smt);
        if ($cbt_info) {
            $cbt_info->no_peserta = $this->cbt->getNomorPeserta($siswa->id_siswa);
        }

        $cbt_jadwal = $this->cbt->getJadwalCbt($periode['tp']->id_tp, $periode['smt']->id_smt, $siswa->level_id);
        $jadwal_aktif = [];
        $elapsed = [];

        foreach ($cbt_jadwal as $jadwal) {
            $kk = unserialize($jadwal->bank_kelas);
            $arrKelas = array_column($kk, 'kelas_id');

            if ($cbt_info && in_array($cbt_info->id_kelas, $arrKelas) && $jadwal->status === "1") {
                $mulai = strtotime($jadwal->tgl_mulai);
                $selesai = strtotime($jadwal->tgl_selesai);

                if ($today >= $mulai && $today <= $selesai) {
                    if ($jadwal->soal_agama == "-" || $jadwal->soal_agama == "0" || $jadwal->soal_agama == $siswa->agama) {
                        $jadwal_aktif[$jadwal->tgl_mulai][] = $jadwal;
                    }
                }
            }
            $elapsed[$jadwal->id_jadwal] = $this->cbt->getElapsed($siswa->id_siswa . "0" . $jadwal->id_jadwal);
        }

        $this->load->model("Dropdown_model", "dropdown");
        $sesi = $this->dropdown->getAllWaktuSesi();

        $this->output_json([
            "status" => true,
            "cbt_info" => $cbt_info,
            "jadwal" => $jadwal_aktif,
            "elapsed" => $elapsed,
            "sesi" => $sesi
        ]);
    }

    public function konfirmasi($id_jadwal)
    {
        if (!$this->check_login()) return;

        $this->load->model("Master_model", "master");
        $siswa = $this->get_siswa();
        $periode = $this->get_tp_smt();

        $info = $this->cbt->getJadwalById($id_jadwal);
        $bank = $this->cbt->getCbt($id_jadwal);
        $cbt_info = $this->cbt->getSiswaCbtInfo($siswa->id_siswa, $periode['tp']->id_tp, $periode['smt']->id_smt);

        // Get pengawas
        $pengawas = [];
        if ($cbt_info) {
            $pengawas_data = $this->cbt->getPengawas(
                $periode['tp']->id_tp . $periode['smt']->id_smt . $id_jadwal . $cbt_info->id_ruang . $cbt_info->id_sesi
            );
            if ($pengawas_data && $pengawas_data->id_guru) {
                $pengawas = $this->master->getGuruByArrId(explode(",", $pengawas_data->id_guru));
            }
        }

        // Check device validity untuk reset_login
        $valid = true;
        if ($info->reset_login == "1") {
            $log = $this->db->where("id_log", $siswa->id_siswa . "0" . $id_jadwal . "1")->get("log_ujian")->row();
            if ($log && $log->reset == 0) {
                $curr_agent = $this->agent->is_browser() ? $this->agent->browser() . " " . $this->agent->version() : 
                             ($this->agent->is_mobile() ? $this->agent->mobile() : "Mobile App");
                $valid = ($log->agent == $curr_agent || $log->agent == "Mobile App");
            }
        }

        $this->output_json([
            "status" => true,
            "jadwal" => $info,
            "bank" => $bank,
            "pengawas" => $pengawas,
            "token_required" => $info->token == '1',
            "valid" => $valid
        ]);
    }

    public function validasi()
    {
        if (!$this->check_login()) return;

        $id_jadwal = $this->input->post("jadwal");
        $id_siswa = $this->input->post("siswa");
        $id_bank = $this->input->post("bank");
        $token_siswa = $this->input->post("token");

        $info = $this->cbt->getJadwalById($id_jadwal);

        // Validasi token
        if ($info->token == "1") {
            $token = $this->cbt->getToken();
            if (!$token || $token->token != $token_siswa) {
                $this->output_json(["status" => false, "message" => "Token tidak valid"]);
                return;
            }
        }

        // Cek/buat log ujian
        $log = $this->db->where("id_log", $id_siswa . "0" . $id_jadwal . "1")->get("log_ujian")->row();
        $mulai_baru = false;

        if (!$log) {
            $this->cbt->saveLog($id_siswa, $id_jadwal, 1, "Memulai Ujian Mobile");
            $log = $this->db->where("id_log", $id_siswa . "0" . $id_jadwal . "1")->get("log_ujian")->row();
            $mulai_baru = true;

            // Set device info
            $this->db->set("agent", "Mobile App");
            $this->db->set("device", "Mobile");
            $this->db->set("address", $this->input->ip_address());
            $this->db->where("id_log", $id_siswa . "0" . $id_jadwal . "1");
            $this->db->update("log_ujian");
        }

        // Cek/buat durasi
        $id_durasi = $id_siswa . "0" . $id_jadwal;
        $durasi = $this->cbt->getElapsed($id_durasi);
        if (!$durasi) {
            $this->db->insert("cbt_durasi_siswa", [
                "id_durasi" => $id_durasi,
                "id_siswa" => $id_siswa,
                "id_jadwal" => $id_jadwal,
                "mulai" => date("Y-m-d H:i:s"),
                "status" => 1
            ]);
            $durasi = $this->cbt->getElapsed($id_durasi);
        }

        $this->output_json([
            "status" => true,
            "message" => $mulai_baru ? "Ujian dimulai" : "Melanjutkan ujian",
            "log" => $log,
            "durasi" => $durasi
        ]);
    }

    public function loadsoal()
    {
        if (!$this->check_login()) return;

        $id_siswa = $this->input->post("siswa");
        $id_jadwal = $this->input->post("jadwal");
        $id_bank = $this->input->post("bank");
        $nomor = $this->input->post("nomor") ?: 1;

        $periode = $this->get_tp_smt();
        $siswa = $this->cbt->getDataSiswaById($periode['tp']->id_tp, $periode['smt']->id_smt, $id_siswa);

        // Update timer
        $id_durasi = $id_siswa . "0" . $id_jadwal;
        $durasi = $this->cbt->getElapsed($id_durasi);
        if ($durasi && $durasi->reset == "0") {
            $mulai = new DateTime($durasi->mulai);
            $elapsed = $mulai->diff(new DateTime())->format("%H:%I:%S");
            $this->db->set("lama_ujian", $elapsed);
            $this->db->where("id_durasi", $id_durasi);
            $this->db->update("cbt_durasi_siswa");
            $durasi = $this->cbt->getElapsed($id_durasi);
        }

        // Get semua soal siswa
        $soals = $this->cbt->getALLSoalSiswa($id_bank, $siswa->id_siswa);
        if (empty($soals)) {
            $this->output_json(["status" => false, "message" => "Soal belum diacak"]);
            return;
        }

        // Process soal jodohkan
        foreach ($soals as &$s) {
            if ($s->jenis_soal == "3") {
                $s->jawaban = unserialize($s->jawaban);
                if ($s->jawaban_siswa) $s->jawaban_siswa = unserialize($s->jawaban_siswa);
            }
        }

        // Find current soal
        $id_soal_siswa = $siswa->id_siswa . "0" . $id_jadwal . $id_bank . $nomor;
        $ind_soal = array_search($id_soal_siswa, array_column($soals, "id_soal_siswa"));
        
        if ($ind_soal === false) {
            $this->output_json(["status" => false, "message" => "Soal tidak ditemukan"]);
            return;
        }

        $item_soal = $soals[$ind_soal];

        // Build opsi jawaban
        $opsis = [];
        $max_jawaban = [];

        if ($item_soal->jenis_soal == "1") { // PG
            $jwbSiswa = strtoupper($item_soal->jawaban_siswa ?? '');
            $opsis = [
                ["alias" => $item_soal->opsi_alias_a, "opsi" => $item_soal->opsi_a, "value" => "A", "checked" => $jwbSiswa == "A"],
                ["alias" => $item_soal->opsi_alias_b, "opsi" => $item_soal->opsi_b, "value" => "B", "checked" => $jwbSiswa == "B"],
                ["alias" => $item_soal->opsi_alias_c, "opsi" => $item_soal->opsi_c, "value" => "C", "checked" => $jwbSiswa == "C"],
                ["alias" => $item_soal->opsi_alias_d, "opsi" => $item_soal->opsi_d, "value" => "D", "checked" => $jwbSiswa == "D"],
                ["alias" => $item_soal->opsi_alias_e, "opsi" => $item_soal->opsi_e, "value" => "E", "checked" => $jwbSiswa == "E"],
            ];
            usort($opsis, fn($a, $b) => $a["alias"] <=> $b["alias"]);
        } elseif ($item_soal->jenis_soal == "2") { // Kompleks
            $item_soal->opsi_a = unserialize($item_soal->opsi_a);
            $item_soal->jawaban_siswa = $item_soal->jawaban_siswa ? unserialize($item_soal->jawaban_siswa) : [];
            $max_jawaban = count(array_filter(unserialize($item_soal->jawaban_benar ?? $item_soal->jawaban)));
            
            foreach ($item_soal->opsi_a as $key => $opsi) {
                $opsis[] = [
                    "opsi" => $opsi,
                    "value" => $key,
                    "checked" => in_array(strtoupper($key), $item_soal->jawaban_siswa)
                ];
            }
        }

        // Build daftar soal untuk navigasi
        $daftar_soal = [];
        foreach ($soals as $s) {
            $terjawab = false;
            if ($s->jawaban_siswa) {
                if ($s->jenis_soal == "3") {
                    // Jodohkan - cek apakah ada jawaban
                    $terjawab = is_array($s->jawaban_siswa) && !empty($s->jawaban_siswa);
                } else {
                    $terjawab = $s->jawaban_siswa != '';
                }
            }
            $daftar_soal[] = [
                "nomor" => $s->no_soal_alias,
                "jenis" => $s->jenis_soal,
                "terjawab" => $terjawab,
                "jawaban_alias" => $s->jawaban_alias
            ];
        }

        $jadwal = $this->cbt->getJadwalById($id_jadwal);

        $this->output_json([
            "status" => true,
            "soal" => [
                "id" => $item_soal->id_soal,
                "id_soal_siswa" => $item_soal->id_soal_siswa,
                "nomor" => $item_soal->no_soal_alias,
                "jenis" => $item_soal->jenis_soal,
                "soal" => $item_soal->soal,
                "file" => $item_soal->file,
                "opsi" => $opsis,
                "jawaban_siswa" => $item_soal->jawaban_siswa,
                "max_jawaban" => $max_jawaban
            ],
            "durasi" => $durasi,
            "jadwal" => [
                "durasi_ujian" => $jadwal->durasi_ujian,
                "reset_login" => $jadwal->reset_login
            ],
            "daftar_soal" => $daftar_soal,
            "total_soal" => count($soals)
        ]);
    }

    public function simpanjawaban()
    {
        if (!$this->check_login()) return;

        $id_siswa = $this->input->post("siswa");
        $id_jadwal = $this->input->post("jadwal");
        $id_bank = $this->input->post("bank");
        $elapsed = $this->input->post("elapsed");
        $jawab = json_decode($this->input->post("data"));

        // Update elapsed time
        if ($elapsed && $elapsed != "0") {
            $this->db->set("lama_ujian", $elapsed);
            $this->db->where("id_durasi", $id_siswa . "0" . $id_jadwal);
            $this->db->update("cbt_durasi_siswa");
        }

        // Simpan jawaban
        if ($jawab && isset($jawab->jenis)) {
            if ($jawab->jenis == 1) { // PG
                $this->db->set("jawaban_alias", $jawab->jawaban_alias);
                $this->db->set("jawaban_siswa", $jawab->jawaban_siswa);
            } elseif ($jawab->jenis == 2) { // Kompleks
                $this->db->set("jawaban_alias", '');
                $this->db->set("jawaban_siswa", serialize($jawab->jawaban_siswa));
            } elseif ($jawab->jenis == 3) { // Jodohkan
                $this->db->set("jawaban_siswa", serialize($jawab->jawaban_siswa));
            } elseif ($jawab->jenis == 4 || $jawab->jenis == 5) { // Isian/Esai
                $this->db->set("jawaban_siswa", $jawab->jawaban_siswa);
            }
            $this->db->where("id_soal_siswa", $jawab->id_soal_siswa);
            $this->db->update("cbt_soal_siswa");
        }

        // Hitung jumlah terjawab
        $terjawab = $this->cbt->getJumlahJawaban($id_bank, $id_siswa);
        $count = 0;
        foreach ($terjawab as $j) {
            if ($j->jawaban_siswa) $count++;
        }

        $this->output_json([
            "status" => true,
            "message" => "Jawaban tersimpan",
            "terjawab" => $count
        ]);
    }

    public function selesai()
    {
        if (!$this->check_login()) return;

        $id_siswa = $this->input->post("siswa");
        $id_jadwal = $this->input->post("jadwal");

        // Olah nilai
        $this->olahNilai($id_siswa, $id_jadwal);

        // Update status selesai
        $this->db->set("selesai", date("Y-m-d H:i:s"));
        $this->db->set("status", 2);
        $this->db->where("id_durasi", $id_siswa . "0" . $id_jadwal);
        $this->db->update("cbt_durasi_siswa");

        $this->cbt->saveLog($id_siswa, $id_jadwal, 2, "Menyelesaikan Ujian Mobile");

        $this->output_json([
            "status" => true,
            "message" => "Ujian selesai"
        ]);
    }

    public function cekStatusUjian()
    {
        if (!$this->check_login()) return;

        $id_siswa = $this->input->post("siswa");
        $id_jadwal = $this->input->post("jadwal");
        
        // Cek durasi
        $id_durasi = $id_siswa . "0" . $id_jadwal;
        $durasi = $this->cbt->getElapsed($id_durasi);

        if (!$durasi) {
            $this->output_json(["status" => false, "message" => "Ujian belum dimulai"]);
            return;
        }

        // Calculate remaining time server-side
        $mulai = new DateTime($durasi->mulai);
        $diff = $mulai->diff(new DateTime());
        $elapsed_seconds = ($diff->h * 3600) + ($diff->i * 60) + $diff->s;

        $this->output_json([
            "status" => true,
            "durasi" => $durasi,
            "server_time" => date("Y-m-d H:i:s"),
            "elapsed_seconds" => $elapsed_seconds
        ]);
    }

    private function olahNilai($id_siswa, $id_jadwal)
    {
        $jadwal = $this->cbt->getJadwalById($id_jadwal);
        $soals = $this->cbt->getSoalSiswaByJadwal($id_jadwal, $id_siswa);

        $benar_pg = $skor_pg = 0;
        $benar_kompleks = $skor_kompleks = 0;
        $benar_jodohkan = $skor_jodohkan = 0;
        $benar_isian = $skor_isian = 0;

        foreach ($soals as $soal) {
            if ($soal->jenis_soal == "1") { // PG
                if (strtoupper($soal->jawaban_siswa) == strtoupper($soal->jawaban_benar)) {
                    $benar_pg++;
                }
            }
            // Kompleks, Jodohkan, Isian perlu koreksi manual atau logic lebih kompleks
        }

        // Hitung skor
        if ($jadwal->tampil_pg > 0) {
            $skor_pg = round(($benar_pg / $jadwal->tampil_pg) * $jadwal->bobot_pg, 2);
        }

        $skor_total = $skor_pg + $skor_kompleks + $skor_jodohkan + $skor_isian;

        // Simpan nilai
        $this->db->where("id_nilai", $id_siswa . $id_jadwal);
        $exists = $this->db->get("cbt_nilai")->row();

        $data_nilai = [
            "id_siswa" => $id_siswa,
            "id_jadwal" => $id_jadwal,
            "benar_pg" => $benar_pg,
            "skor_pg" => $skor_pg,
            "benar_kompleks" => $benar_kompleks,
            "skor_kompleks" => $skor_kompleks,
            "benar_jodohkan" => $benar_jodohkan,
            "skor_jodohkan" => $skor_jodohkan,
            "benar_isian" => $benar_isian,
            "skor_isian" => $skor_isian,
            "skor_total" => $skor_total
        ];

        if ($exists) {
            $this->db->where("id_nilai", $id_siswa . $id_jadwal);
            $this->db->update("cbt_nilai", $data_nilai);
        } else {
            $data_nilai["id_nilai"] = $id_siswa . $id_jadwal;
            $this->db->insert("cbt_nilai", $data_nilai);
        }

        return true;
    }

    public function leave($jadwal, $siswa)
    {
        $this->db->set("agent", "illegal agent");
        $this->db->set("device", "illegal device");
        $this->db->where("id_log", $siswa . "0" . $jadwal . "1");
        $this->db->update("log_ujian");
        $this->output_json(["status" => true]);
    }

    // ==================== NILAI ====================

    public function nilai()
    {
        if (!$this->check_login()) return;

        $siswa = $this->get_siswa();
        $periode = $this->get_tp_smt();

        // Get nilai materi & tugas
        $this->load->model("Kelas_model", "kelas");
        $logs = $this->kelas->getNilaiMateriSiswa($siswa->id_siswa);
        $nilai_materi = isset($logs[1]) ? $logs[1] : [];
        $nilai_tugas = isset($logs[2]) ? $logs[2] : [];

        // Get nilai ujian (Replicating Siswa::hasil logic)
        $this->load->model("Cbt_model", "cbt");
        $jadwals = $this->cbt->getJadwalByKelas($periode['tp']->id_tp, $periode['smt']->id_smt, $siswa->id_kelas);
        
        $nilai_ujian = [];
        foreach ($jadwals as $jadwal) {
            $kelass = unserialize($jadwal->bank_kelas);
            $arr_kls_jadwal = [];
            foreach ($kelass as $kll) {
                foreach ($kll as $kl) {
                    if ($kl != null) {
                        $arr_kls_jadwal[] = $kl;
                    }
                }
            }

            if (in_array($siswa->id_kelas, $arr_kls_jadwal)) {
                // Get exam properties and weights
                $info = $jadwal;
                $jadwal->bank_kelas = unserialize($jadwal->bank_kelas);
                
                $bagi_pg = $info->tampil_pg / 100;
                $bobot_pg = $info->bobot_pg / 100;
                $bagi_pg2 = $info->tampil_kompleks / 100;
                $bobot_pg2 = $info->bobot_kompleks / 100;
                $bagi_jodoh = $info->tampil_jodohkan / 100;
                $bobot_jodoh = $info->bobot_jodohkan / 100;
                $bagi_isian = $info->tampil_isian / 100;
                $bobot_isian = $info->bobot_isian / 100;
                $bagi_essai = $info->tampil_esai / 100;
                $bobot_essai = $info->bobot_esai / 100;
                
                // Get answers
                $jawabans = $this->cbt->getJawabanSiswaByJadwal($jadwal->id_jadwal, $siswa->id_siswa);
                $jawabans_siswa = [];
                foreach ($jawabans as $jawaban_siswa) {
                    if ($jawaban_siswa->jenis_soal == "2") {
                        $jawaban_siswa->opsi_a = @unserialize($jawaban_siswa->opsi_a);
                        $jawaban_siswa->jawaban_siswa = @unserialize($jawaban_siswa->jawaban_siswa);
                        $jawaban_siswa->jawaban_benar = @unserialize($jawaban_siswa->jawaban_benar);
                        $jawaban_siswa->jawaban = @unserialize($jawaban_siswa->jawaban);
                        
                        if (is_array($jawaban_siswa->jawaban_benar)) {
                            $jawaban_siswa->jawaban_benar = array_map("strtoupper", $jawaban_siswa->jawaban_benar);
                            $jawaban_siswa->jawaban_benar = array_filter($jawaban_siswa->jawaban_benar, "strlen");
                        }
                        if (is_array($jawaban_siswa->jawaban)) {
                           $jawaban_siswa->jawaban = array_map("strtoupper", $jawaban_siswa->jawaban);
                           $jawaban_siswa->jawaban = array_filter($jawaban_siswa->jawaban, "strlen");
                        }
                    }
                    if ($jawaban_siswa->jenis_soal == "3") {
                        $jawaban_siswa->jawaban_siswa = @unserialize($jawaban_siswa->jawaban_siswa);
                        $jawaban_siswa->jawaban_benar = @unserialize($jawaban_siswa->jawaban_benar);
                        $jawaban_siswa->jawaban = @unserialize($jawaban_siswa->jawaban);
                        
                        $jawaban_siswa->jawaban_siswa = json_decode(json_encode($jawaban_siswa->jawaban_siswa));
                        $jawaban_siswa->jawaban_benar = json_decode(json_encode($jawaban_siswa->jawaban_benar));
                        $jawaban_siswa->jawaban = json_decode(json_encode($jawaban_siswa->jawaban));
                    }
                    $jawabans_siswa[$siswa->id_siswa][$jawaban_siswa->jenis_soal][] = $jawaban_siswa;
                }
                
                $ada_jawaban = isset($jawabans_siswa[$siswa->id_siswa]);
                $ada_jawaban_pg = $ada_jawaban && isset($jawabans_siswa[$siswa->id_siswa]["1"]);
                $ada_jawaban_pg2 = $ada_jawaban && isset($jawabans_siswa[$siswa->id_siswa]["2"]);
                $ada_jawaban_jodoh = $ada_jawaban && isset($jawabans_siswa[$siswa->id_siswa]["3"]);
                $ada_jawaban_isian = $ada_jawaban && isset($jawabans_siswa[$siswa->id_siswa]["4"]);
                $ada_jawaban_essai = $ada_jawaban && isset($jawabans_siswa[$siswa->id_siswa]["5"]);

                // Get existing score from cbt_nilai
                $nilai_input = $this->cbt->getNilaiSiswaByJadwal($jadwal->id_jadwal, $siswa->id_siswa);
                
                $skor = new stdClass();
                if ($nilai_input != null) {
                    $skor->dikoreksi = $nilai_input->dikoreksi;
                } else {
                    $skor->dikoreksi = "1";
                }

                // --- Calculate PG ---
                $jawaban_pg = $ada_jawaban_pg ? $jawabans_siswa[$siswa->id_siswa]["1"] : [];
                $benar_pg = 0;
                if ($info->tampil_pg > 0 && count($jawaban_pg) > 0) {
                    foreach ($jawaban_pg as $jwb_pg) {
                        if ($jwb_pg != null && $jwb_pg->jawaban_siswa != null) {
                             if (strtoupper($jwb_pg->jawaban_siswa) == strtoupper($jwb_pg->jawaban)) {
                                $benar_pg += 1;
                            }
                        }
                    }
                }
                $skor->benar_pg = $benar_pg;
                $skor_pg = $bagi_pg == 0 ? 0 : round($benar_pg / $bagi_pg * $bobot_pg, 2);
                $skor->skor_pg = $skor_pg;

                // --- Calculate PG Kompleks ---
                $jawaban_pg2 = $ada_jawaban_pg2 ? $jawabans_siswa[$siswa->id_siswa]["2"] : [];
                $benar_pg2 = 0;
                $skor_koreksi_pg2 = 0.0;
                $otomatis_pg2 = 0;
                if ($info->tampil_kompleks > 0 && count($jawaban_pg2) > 0) {
                    foreach ($jawaban_pg2 as $jawab_pg2) {
                        $skor_koreksi_pg2 += $jawab_pg2->nilai_koreksi;
                        $arr_benar = [];
                        if (is_array($jawab_pg2->jawaban_siswa)) {
                            foreach ($jawab_pg2->jawaban_siswa as $js) {
                                if (in_array($js, $jawab_pg2->jawaban)) {
                                    $arr_benar[] = true;
                                }
                            }
                        }
                        if (count($jawab_pg2->jawaban) > 0) {
                            $benar_pg2 += 1 / count($jawab_pg2->jawaban) * count($arr_benar);
                        }
                        $otomatis_pg2 = $jawab_pg2->nilai_otomatis;
                    }
                }
                $s_pg2 = $bagi_pg2 == 0 ? 0 : $benar_pg2 / $bagi_pg2 * $bobot_pg2;
                $input_pg2 = ($nilai_input != null && $nilai_input->kompleks_nilai != null) ? $nilai_input->kompleks_nilai : 0;
                
                $skor->skor_kompleks = $input_pg2 != 0 ? $input_pg2 : ($otomatis_pg2 == 0 ? $s_pg2 : $skor_koreksi_pg2);
                $skor->benar_kompleks = round($benar_pg2, 2);

                // --- Calculate Menjodohkan ---
                $jawaban_jodoh = $ada_jawaban_jodoh ? $jawabans_siswa[$siswa->id_siswa]["3"] : [];
                $benar_jod = 0;
                $skor_koreksi_jod = 0.0;
                $otomatis_jod = 0;
                if ($info->tampil_jodohkan > 0 && count($jawaban_jodoh) > 0) {
                     foreach ($jawaban_jodoh as $num => $jawab_jod) {
                        $skor_koreksi_jod += $jawab_jod->nilai_koreksi;
                        
                        // Parse Soal
                        $arrSoal = $jawab_jod->jawaban->jawaban ?? [];
                        $headSoal = array_shift($arrSoal);
                        $arrJwbSoal = [];
                        $items = 0;
                        if (is_array($arrSoal)) {
                            foreach ($arrSoal as $kolSoal) {
                                $jwb = new stdClass();
                                foreach ($kolSoal as $pos => $kol) {
                                    if ($kol == "1") {
                                        $jwb->subtitle[] = $headSoal[$pos];
                                        $items++;
                                    }
                                }
                                $jwb->title = array_shift($kolSoal);
                                array_push($arrJwbSoal, $jwb);
                            }
                        }

                        // Parse Jawab
                        $arrJawab = isset($jawab_jod->jawaban_siswa->jawaban) ? $jawab_jod->jawaban_siswa->jawaban : [];
                        $headJawab = array_shift($arrJawab);
                        $arrJwbJawab = [];
                        if (is_array($arrJawab)) {
                            foreach ($arrJawab as $kolJawab) {
                                $jwbs = new stdClass();
                                foreach ($kolJawab as $po => $kol) {
                                    if ($kol == "1") {
                                        $sub = $headJawab[$po];
                                        $jwbs->subtitle[] = $sub;
                                    }
                                }
                                array_push($arrJwbJawab, $jwbs);
                            }
                        }

                        // Compare
                        $item_benar = 0;
                        foreach ($arrJwbJawab as $p => $ajjs) {
                             if (isset($ajjs->subtitle)) {
                                foreach ($ajjs->subtitle as $pp => $ajs) {
                                    if (isset($arrJwbSoal[$p]) && isset($arrJwbSoal[$p]->subtitle) && in_array($ajs, $arrJwbSoal[$p]->subtitle)) {
                                        $item_benar++;
                                    }
                                }
                            }
                        }
                        $benar_jod += $items == 0 ? 0 : 1 / $items * $item_benar;
                        $otomatis_jod = $jawab_jod->nilai_otomatis;
                     }
                }
                
                $s_jod = $bagi_jodoh == 0 ? 0 : $benar_jod / $bagi_jodoh * $bobot_jodoh;
                $input_jod = ($nilai_input != null && $nilai_input->jodohkan_nilai != null) ? $nilai_input->jodohkan_nilai : 0;
                
                $skor->skor_jodohkan = $input_jod != 0 ? $input_jod : ($otomatis_jod == 0 ? $s_jod : $skor_koreksi_jod);
                $skor->benar_jodohkan = round($benar_jod, 2);

                // --- Calculate Isian ---
                $jawaban_is = $ada_jawaban_isian ? $jawabans_siswa[$siswa->id_siswa]["4"] : [];
                $benar_is = 0;
                $skor_koreksi_is = 0.0;
                $otomatis_is = 0;
                if ($info->tampil_isian > 0 && count($jawaban_is) > 0) {
                    foreach ($jawaban_is as $jawab_is) {
                        $skor_koreksi_is += $jawab_is->nilai_koreksi;
                        $benar = $jawab_is != null && strtolower($jawab_is->jawaban_siswa) == strtolower($jawab_is->jawaban);
                        if ($benar) $benar_is++;
                        $otomatis_is = $jawab_is->nilai_otomatis;
                    }
                }
                $s_is = $bagi_isian == 0 ? 0 : $benar_is / $bagi_isian * $bobot_isian;
                $input_is = ($nilai_input != null && $nilai_input->isian_nilai != null) ? $nilai_input->isian_nilai : 0;
                
                $skor->skor_isian = $input_is != 0 ? $input_is : ($otomatis_is == 0 ? $s_is : $skor_koreksi_is);
                $skor->benar_isian = $benar_is;

                 // --- Calculate Esai ---
                $jawaban_es = $ada_jawaban_essai ? $jawabans_siswa[$siswa->id_siswa]["5"] : [];
                $benar_es = 0; // Essay correctness usually not auto-graded like PG
                $skor_koreksi_es = 0.0;
                $otomatis_es = 0;
                 if ($info->tampil_esai > 0 && count($jawaban_es) > 0) {
                    foreach ($jawaban_es as $jawab_es) {
                        $skor_koreksi_es += $jawab_es->nilai_koreksi;
                        $otomatis_es = $jawab_es->nilai_otomatis;
                        // Basic comparison if auto
                         $benar = $jawab_es != null && strtolower($jawab_es->jawaban_siswa) == strtolower($jawab_es->jawaban);
                        if ($benar) $benar_es++;
                    }
                }
                $s_es = $bagi_essai == 0 ? 0 : $benar_es / $bagi_essai * $bobot_essai;
                $input_es = ($nilai_input != null && $nilai_input->essai_nilai != null) ? $nilai_input->essai_nilai : 0;
                
                $skor->skor_essai = $input_es != 0 ? $input_es : ($otomatis_es == 0 ? $s_es : $skor_koreksi_es);
                $skor->benar_esai = $benar_es;


                // --- Total Score ---
                $total = $skor->skor_pg + $skor->skor_kompleks + $skor->skor_jodohkan + $skor->skor_isian + $skor->skor_essai;
                $skor->skor_total = number_format($total, 2);

                // --- Display Logic ---
                $hanya_pg = $jadwal->tampil_pg > 0 && $jadwal->tampil_kompleks == 0 && $jadwal->tampil_jodohkan == 0 && $jadwal->tampil_isian == 0 && $jadwal->tampil_esai == 0;
                
                $final_score = $skor->skor_total;
                if (!$hanya_pg && $skor->dikoreksi == '0') {
                    $final_score = '*';
                } else if ($jadwal->hasil_tampil == '0') {
                    $final_score = '**';
                }

                // Get duration info
                $durasi = $this->cbt->getDurasiSiswaByJadwal($jadwal->id_jadwal, $siswa->id_siswa);
                $dur = isset($durasi[0]) ? $durasi[0] : null;

                // Construct result item
                $item = [
                    'id_jadwal' => $jadwal->id_jadwal,
                    'nama_jenis' => $jadwal->nama_jenis,
                    'kode' => $jadwal->kode, // Mapel code
                    'bank_kode' => $jadwal->bank_kode,
                    'tgl_mulai' => $jadwal->tgl_mulai,
                    'hasil_tampil' => $jadwal->hasil_tampil,
                    'dikoreksi' => $skor->dikoreksi,
                    'nilai_total_display' => $final_score,
                    
                    // Duration Details
                    'dur_mulai' => $dur ? $dur->mulai : null,
                    'dur_selesai' => $dur ? $dur->selesai : null,
                    'dur_lama' => $dur ? $dur->lama_ujian : null,

                    // Details for dialog
                    'tampil_pg' => $jadwal->tampil_pg,
                    'benar_pg' => $skor->benar_pg,
                    'nilai_pg' => $skor->skor_pg,
                    
                    'tampil_kompleks' => $jadwal->tampil_kompleks,
                    'benar_kompleks' => $skor->benar_kompleks,
                    'nilai_kompleks' => $skor->skor_kompleks,

                    'tampil_jodohkan' => $jadwal->tampil_jodohkan,
                    'benar_jodohkan' => $skor->benar_jodohkan,
                    'nilai_jodohkan' => $skor->skor_jodohkan,

                    'tampil_isian' => $jadwal->tampil_isian,
                    'benar_isian' => $skor->benar_isian,
                    'nilai_isian' => $skor->skor_isian,

                    'tampil_esai' => $jadwal->tampil_esai,
                    'benar_esai' => '-', // Usually not shown/relevant for essay
                    'nilai_esai' => $skor->skor_essai,
                ];
                
                $nilai_ujian[] = $item;
            }
        }

        $this->output_json([
            "status" => true,
            "ujian" => $nilai_ujian,
            "materi" => $nilai_materi,
            "tugas" => $nilai_tugas
        ]);
    }

    // ==================== PENGUMUMAN ====================

    public function pengumuman()
    {
        if (!$this->check_login()) return;

        $this->load->model("Post_model", "post");
        $siswa = $this->get_siswa();
        $posts = $this->post->getPostForUser("'%siswa%'", "'%" . $siswa->kode_kelas . "%'");

        $this->output_json(["status" => true, "data" => $posts]);
    }

    // ==================== JADWAL HARI INI ====================

    public function jadwalhariini()
    {
        if (!$this->check_login()) return;

        $siswa = $this->get_siswa();
        $periode = $this->get_tp_smt();
        $hari = date('N'); // 1=Senin, 7=Minggu

        $this->load->model("Kelas_model", "kelas");
        
        $jadwal = $this->db->select("j.*, m.nama_mapel, m.kode")
            ->from("kelas_jadwal_mapel j")
            ->join("master_mapel m", "j.id_mapel = m.id_mapel")
            ->where("j.id_kelas", $siswa->id_kelas)
            ->where("j.id_tp", $periode['tp']->id_tp)
            ->where("j.id_smt", $periode['smt']->id_smt)
            ->where("j.id_hari", $hari)
            ->order_by("j.jam_ke", "ASC")
            ->get()->result();

        $this->output_json(["status" => true, "jadwal" => $jadwal]);
    }

    // ==================== MATERI ====================

    public function materi()
    {
        if (!$this->check_login()) return;

        $siswa = $this->get_siswa();
        $periode = $this->get_tp_smt();

        // Match Siswa.php getTugasMateri() -> Kelas_model.getMateriSiswaSeminggu()
        $this->db->select("a.*, b.id_materi, b.kode_materi, b.judul_materi, b.materi_kelas, b.tgl_mulai, c.nama_guru, d.nama_mapel");
        $this->db->from("kelas_jadwal_materi a");
        $this->db->join("kelas_materi b", "a.id_materi=b.id_materi AND b.status=1", "left");
        $this->db->join("master_guru c", "b.id_guru=c.id_guru", "left");
        $this->db->join("master_mapel d", "b.id_mapel=d.id_mapel", "left");
        $this->db->where("a.id_tp", $periode['tp']->id_tp);
        $this->db->where("a.id_smt", $periode['smt']->id_smt);
        $this->db->where("a.jenis", 1); // 1 = Materi
        $this->db->where("a.id_kelas", $siswa->id_kelas);
        $this->db->order_by("a.jadwal_materi", "DESC");
        $materi = $this->db->get()->result();

        // Group by date
        $grouped = [];
        foreach ($materi as $m) {
            $tgl = substr($m->jadwal_materi, 0, 10);
            $grouped[$tgl][$m->jam_ke] = $m;
        }

        $this->output_json(["status" => true, "materis" => $grouped]);
    }

    public function bukamateri($id_kjm, $jam_ke)
    {
        if (!$this->check_login()) return;

        $siswa = $this->get_siswa();
        $id_log = $siswa->id_siswa . $id_kjm;

        $this->db->where("id_log", $id_log);
        $exists = $this->db->get("log_materi")->row();

        if (!$exists) {
            $this->db->insert("log_materi", [
                "id_log" => $id_log,
                "id_siswa" => $siswa->id_siswa,
                "id_materi" => $id_kjm,
                "jam_ke" => $jam_ke,
                "log_desc" => "Membuka materi"
            ]);
        }

        $this->output_json(["status" => true, "message" => "Materi dibuka"]);
    }

    // ==================== TUGAS ====================

    public function tugas()
    {
        if (!$this->check_login()) return;

        $siswa = $this->get_siswa();
        $periode = $this->get_tp_smt();

        // Match Siswa.php getTugasMateri() -> Kelas_model.getMateriSiswaSeminggu()
        $this->db->select("a.*, b.id_materi, b.kode_materi, b.judul_materi, b.materi_kelas, b.tgl_mulai, c.nama_guru, d.nama_mapel");
        $this->db->from("kelas_jadwal_materi a");
        $this->db->join("kelas_materi b", "a.id_materi=b.id_materi AND b.status=1", "left");
        $this->db->join("master_guru c", "b.id_guru=c.id_guru", "left");
        $this->db->join("master_mapel d", "b.id_mapel=d.id_mapel", "left");
        $this->db->where("a.id_tp", $periode['tp']->id_tp);
        $this->db->where("a.id_smt", $periode['smt']->id_smt);
        $this->db->where("a.jenis", 2); // 2 = Tugas
        $this->db->where("a.id_kelas", $siswa->id_kelas);
        $this->db->order_by("a.jadwal_materi", "DESC");
        $tugas = $this->db->get()->result();

        $grouped = [];
        foreach ($tugas as $t) {
            $tgl = substr($t->jadwal_materi, 0, 10); // Fixed: use jadwal_materi not jadwal_tugas
            $grouped[$tgl][$t->jam_ke] = $t;
        }

        $this->output_json(["status" => true, "tugass" => $grouped]);
    }

    public function bukatugas($id_kjm, $jam_ke)
    {
        if (!$this->check_login()) return;

        $siswa = $this->get_siswa();
        $id_log = $siswa->id_siswa . $id_kjm;

        $this->db->where("id_log", $id_log);
        $exists = $this->db->get("log_tugas")->row();

        if (!$exists) {
            $this->db->insert("log_tugas", [
                "id_log" => $id_log,
                "id_siswa" => $siswa->id_siswa,
                "id_materi" => $id_kjm,
                "jam_ke" => $jam_ke,
                "log_desc" => "Membuka tugas"
            ]);
        }

        $this->output_json(["status" => true, "message" => "Tugas dibuka"]);
    }

    // ==================== JADWAL PELAJARAN ====================

    public function jadwal()
    {
        if (!$this->check_login()) return;

        $siswa = $this->get_siswa();
        $periode = $this->get_tp_smt();

        // Get KBM settings
        $kbm = $this->db->where("id_tp", $periode['tp']->id_tp)
            ->where("id_smt", $periode['smt']->id_smt)
            ->where("id_kelas", $siswa->id_kelas)
            ->get("kelas_jadwal_kbm")->row();

        // Get jadwal
        $jadwal = $this->db->select("j.*, m.kode, m.nama_mapel")
            ->from("kelas_jadwal_mapel j")
            ->join("master_mapel m", "j.id_mapel = m.id_mapel")
            ->where("j.id_kelas", $siswa->id_kelas)
            ->where("j.id_tp", $periode['tp']->id_tp)
            ->where("j.id_smt", $periode['smt']->id_smt)
            ->order_by("j.id_hari, j.jam_ke")
            ->get()->result();

        $this->output_json([
            "status" => true,
            "jadwal_kbm" => $kbm,
            "jadwal_mapel" => [["jadwal" => $jadwal]]
        ]);
    }

    // ==================== ABSENSI ====================

    public function absensi()
    {
        if (!$this->check_login()) return;

        $siswa = $this->get_siswa();
        $periode = $this->get_tp_smt();
        $hari = date('N');

        // Jadwal hari ini
        $jadwal = $this->db->select("j.*, m.nama_mapel")
            ->from("kelas_jadwal_mapel j")
            ->join("master_mapel m", "j.id_mapel = m.id_mapel")
            ->where("j.id_kelas", $siswa->id_kelas)
            ->where("j.id_tp", $periode['tp']->id_tp)
            ->where("j.id_smt", $periode['smt']->id_smt)
            ->where("j.id_hari", $hari)
            ->order_by("j.jam_ke")
            ->get()->result();

        // KBM
        $kbm = $this->db->where("id_tp", $periode['tp']->id_tp)
            ->where("id_smt", $periode['smt']->id_smt)
            ->where("id_kelas", $siswa->id_kelas)
            ->get("kelas_jadwal_kbm")->row();

        $this->output_json([
            "status" => true,
            "jadwal" => $jadwal,
            "kbm" => $kbm
        ]);
    }

    // ==================== CATATAN ====================

    public function catatan()
    {
        if (!$this->check_login()) return;

        $siswa = $this->get_siswa();

        $catatan = $this->db->select("c.*, g.nama_guru, g.foto")
            ->from("kelas_catatan_mapel c")
            ->join("master_guru g", "c.id_guru = g.id_guru", "left")
            ->group_start()
            ->where("c.id_siswa", $siswa->id_siswa)
            ->or_where("c.id_kelas", $siswa->id_kelas)
            ->group_end()
            ->order_by("c.tgl", "DESC")
            ->get()->result();

        $this->output_json(["status" => true, "catatan" => $catatan]);
    }

    public function detailcatatan($table, $id)
    {
        if (!$this->check_login()) return;

        $this->db->select("c.*, g.nama_guru, g.jabatan");
        $this->db->from("catatan_siswa c");
        $this->db->join("master_guru g", "c.id_guru = g.id_guru", "left");
        $this->db->where("c.id_catatan", $id);
        $detail = $this->db->get()->row();

        $this->output_json(["status" => true, "detail" => $detail]);
    }

    public function readcatatan($table, $id)
    {
        if (!$this->check_login()) return;

        $this->db->set("readed", 1);
        $this->db->where("id_catatan", $id);
        $this->db->update("catatan_siswa");

        $this->output_json(["status" => true]);
    }
    public function getAbsensi()
    {
        if (!$this->check_login()) return;

        $this->load->model("Master_model", "master");
        $this->load->model("Dashboard_model", "dashboard");
        $this->load->model("Kelas_model", "kelas");
        $this->load->model("Cbt_model", "cbt");

        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();
        $siswa = $this->get_siswa();

        $today = date("Y-m-d");
        $day = date("N", strtotime($today));

        // Get KBM & Istirahat
        $kbm = $this->dashboard->getJadwalKbm($tp->id_tp, $smt->id_smt, $siswa->id_kelas);
        if ($kbm != null) {
            $kbm->istirahat = unserialize($kbm->istirahat);
        }

        // Get Jadwal Hari Ini & Seminggu
        // loadJadwalHariIni checks for specific day if 4th arg provided, else returns all?
        // Logic in Siswa.php suggests it returns simple list, then re-keyed by day/jam
        $result = $this->dashboard->loadJadwalHariIni($tp->id_tp, $smt->id_smt, $siswa->id_kelas, null);
        $jadwals = [];
        foreach ($result as $row) {
            $jadwals[$row->id_hari][$row->jam_ke] = $row;
        }

        // Get Mapels
        $mapels = $this->master->getAllMapel();
        $arrIdMapel = [];
        foreach ($mapels as $mpl) {
            array_push($arrIdMapel, $mpl->id_mapel);
        }

        // Get Monthly Data
        $sebulan = ["log" => [], "materis" => []];
        if ($kbm != null) {
            $bulan = date("m");
            $tahun = date("Y");
            $tgl = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
            $materi_sebulan = [];
            $i = 0;
            while ($i < $tgl) {
                $t = $i + 1 < 10 ? "0" . ($i + 1) : $i + 1;
                $tgl_str = $tahun . "-" . $bulan . "-" . $t;
                // Reuse existing model
                $materi_sebulan[$t] = $this->kelas->getAllMateriByTgl($siswa->id_kelas, $tgl_str, $arrIdMapel);
                $i++;
            }
            // Get Logs
            $logs = $this->kelas->getRekapBulananSiswa(null, $siswa->id_kelas, $tahun, $bulan);
            $sebulan = ["log" => isset($logs[$siswa->id_siswa]) ? $logs[$siswa->id_siswa] : [], "materis" => $materi_sebulan];
        }

        $this->output_json([
            "status" => true,
            "kbm" => $kbm,
            "jadwals" => $jadwals, // All days, keyed by Day ID -> Jam Ke
            "jadwal_hari_ini" => isset($jadwals[$day]) && $day != 7 ? $jadwals[$day] : [],
            "sebulan" => $sebulan,
            "mapels" => $mapels, // Can use to lookup mapel names if needed
            "bulan_label" => $this->_buat_tanggal(date('M')), // Helper from CI project likely available
            "tahun" => date('Y'),
            "bulan" => date('m')
        ]);
    }

    private function _buat_tanggal($str)
    {
        $str = str_replace("Jan", "Januari", $str);
        $str = str_replace("Feb", "Februari", $str);
        $str = str_replace("Mar", "Maret", $str);
        $str = str_replace("Apr", "April", $str);
        $str = str_replace("May", "Mei", $str);
        $str = str_replace("Jun", "Juni", $str);
        $str = str_replace("Jul", "Juli", $str);
        $str = str_replace("Aug", "Agustus", $str);
        $str = str_replace("Sep", "September", $str);
        $str = str_replace("Oct", "Oktober", $str);
        $str = str_replace("Nov", "Nopember", $str);
        $str = str_replace("Dec", "Desember", $str);
        $str = str_replace("Mon", "Senin", $str);
        $str = str_replace("Tue", "Selasa", $str);
        $str = str_replace("Wed", "Rabu", $str);
        $str = str_replace("Thu", "Kamis", $str);
        $str = str_replace("Fri", "Jumat", $str);
        $str = str_replace("Sat", "Sabtu", $str);
        $str = str_replace("Sun", "Minggu", $str);
        return $str;
    }
}
