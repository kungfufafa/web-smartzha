<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Orangtua extends CI_Controller
{
    private $user;
    private $anak_list;
    private $selected_anak;

    public function __construct()
    {
        parent::__construct();

        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        }

        if (!$this->ion_auth->in_group('orangtua')) {
            show_error('Halaman ini hanya untuk Orang Tua. <a href="' . base_url('dashboard') . '">Kembali</a>', 403, 'Akses Ditolak');
        }

        $this->load->model('Orangtua_model', 'orangtua');
        $this->load->model('Dashboard_model', 'dashboard');
        $this->load->model('Pembayaran_model', 'pembayaran');
        $this->load->model('Rapor_model', 'rapor');
        $this->load->model('Kelas_model', 'kelas');

        $this->user = $this->ion_auth->user()->row();
        $this->_loadAnakList();
    }

    private function _loadAnakList()
    {
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();

        if ($tp && $smt) {
            $this->anak_list = $this->orangtua->getAnakByUserIdWithTpSmt($this->user->id, $tp->id_tp, $smt->id_smt);
        } else {
            $this->anak_list = $this->orangtua->getAnakByUserId($this->user->id);
        }

        $selected_id = $this->session->userdata('selected_anak_id');
        // Validate selected_id is a positive integer
        $selected_id = filter_var($selected_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        
        if ($selected_id) {
            foreach ($this->anak_list as $anak) {
                if ($anak->id_siswa == $selected_id) {
                    $this->selected_anak = $anak;
                    break;
                }
            }
        }

        if (!$this->selected_anak && count($this->anak_list) > 0) {
            $this->selected_anak = $this->anak_list[0];
            $this->session->set_userdata('selected_anak_id', $this->selected_anak->id_siswa);
        }
    }

    private function output_json($data, $encode = true)
    {
        if ($encode) $data = json_encode($data);
        $this->output->set_content_type('application/json')->set_output($data);
    }

    private function getCommonData()
    {
        $setting = $this->dashboard->getSetting();
        $tp = $this->dashboard->getTahunActive();
        $smt = $this->dashboard->getSemesterActive();

        return [
            'user' => $this->user,
            'setting' => $setting,
            'tp_active' => $tp,
            'smt_active' => $smt,
            'anak_list' => $this->anak_list,
            'selected_anak' => $this->selected_anak,
            'profile' => (object)[
                'nama_lengkap' => 'Orang Tua: ' . ($this->selected_anak ? $this->selected_anak->nama : ''),
                'jabatan' => 'Orang Tua',
                'foto' => null
            ]
        ];
    }

    private function getMenuBox()
    {
        return [
            ['title' => 'Nilai Hasil', 'icon' => 'ic_exam.png', 'link' => 'orangtua/hasil'],
            ['title' => 'Absensi', 'icon' => 'ic_clipboard.png', 'link' => 'orangtua/kehadiran'],
            ['title' => 'Tagihan', 'icon' => 'ic_certificate.png', 'link' => 'orangtua/tagihan']
        ];
    }

    public function index()
    {
        if (empty($this->anak_list)) {
            $data = $this->getCommonData();
            $data['judul'] = 'Dashboard Orang Tua';
            $data['subjudul'] = 'Tidak Ada Data Anak';
            $this->load->view('members/orangtua/templates/header', $data);
            $this->load->view('members/orangtua/no_anak');
            $this->load->view('members/orangtua/templates/footer');
            return;
        }

        $data = $this->getCommonData();
        $data['judul'] = 'Dashboard Orang Tua';
        $data['subjudul'] = 'Informasi Anak: ' . $this->selected_anak->nama;
        $data['menu'] = json_decode(json_encode($this->getMenuBox()));

        $this->load->view('members/orangtua/templates/header', $data);
        $this->load->view('members/orangtua/dashboard', $data);
        $this->load->view('members/orangtua/templates/footer');
    }

    public function switchAnak($id_siswa)
    {
        // Validate id_siswa is a positive integer
        $id_siswa = filter_var($id_siswa, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        
        if (!$id_siswa || !$this->orangtua->isParentOfSiswa($this->user->id, $id_siswa)) {
            $this->session->set_flashdata('error', 'Anak tidak ditemukan');
            redirect('orangtua');
        }

        $this->session->set_userdata('selected_anak_id', $id_siswa);
        redirect($this->input->get('redirect') ?: 'orangtua');
    }

    public function hasil()
    {
        if (!$this->selected_anak) {
            redirect('orangtua');
        }

        $this->load->model('Cbt_model', 'cbt');

        $data = $this->getCommonData();
        $tp = $data['tp_active'];
        $smt = $data['smt_active'];

        $data['judul'] = 'Nilai Hasil';
        $data['subjudul'] = 'Nilai ' . $this->selected_anak->nama;

        $siswa = $this->orangtua->getSiswaDetailById($this->selected_anak->id_siswa, $tp->id_tp, $smt->id_smt);
        $data['siswa'] = $siswa;

        $data['nilai_materi'] = [];
        $data['nilai_tugas'] = [];
        $data['skor'] = [];
        $data['durasi'] = [];
        $data['jadwal'] = [];
        $data['jawaban'] = [];
        $data['kelass'] = [];
        $data['tp'] = $this->dashboard->getTahun();
        $data['smt'] = $this->dashboard->getSemester();

        if (!$siswa || !$siswa->id_kelas) {
            $this->load->view('members/orangtua/templates/header', $data);
            $this->load->view('members/orangtua/nilai/data', $data);
            $this->load->view('members/orangtua/templates/footer');
            return;
        }

        $logs = $this->kelas->getNilaiMateriSiswa($siswa->id_siswa);
        $data['nilai_materi'] = isset($logs[1]) ? $logs[1] : [];
        $data['nilai_tugas'] = isset($logs[2]) ? $logs[2] : [];

        $this->db->trans_start();
        $jadwals = $this->cbt->getJadwalByKelas($tp->id_tp, $smt->id_smt, $siswa->id_kelas);
        $skors = [];
        $durasies = [];
        $jawabans = [];
        $kelass_unset = [];

        foreach ($jadwals as $kj => $jadwal) {
            $kelass = unserialize($jadwal->bank_kelas);
            $arr_kls_jadwal = [];
            foreach ($kelass as $kll) {
                foreach ($kll as $kl) {
                    if ($kl != null) {
                        $arr_kls_jadwal[] = $kl;
                    }
                }
            }
            if (!in_array($siswa->id_kelas, $arr_kls_jadwal)) {
                unset($jadwals[$kj]);
                $kelass_unset[] = $kj;
                continue;
            }
            $jadwal->bank_kelas = unserialize($jadwal->bank_kelas);
            $info = $jadwal;
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

            $jawabans = $this->cbt->getJawabanSiswaByJadwal($jadwal->id_jadwal, $siswa->id_siswa);
            $jawabans_siswa = [];
            foreach ($jawabans as $jawaban_siswa) {
                if ($jawaban_siswa->jenis_soal == "2") {
                    $jawaban_siswa->opsi_a = @unserialize($jawaban_siswa->opsi_a);
                    $jawaban_siswa->jawaban_siswa = @unserialize($jawaban_siswa->jawaban_siswa);
                    $jawaban_siswa->jawaban_benar = @unserialize($jawaban_siswa->jawaban_benar);
                    $jawaban_siswa->jawaban = @unserialize($jawaban_siswa->jawaban);
                    $jawaban_siswa->jawaban_benar = array_map("strtoupper", $jawaban_siswa->jawaban_benar);
                    $jawaban_siswa->jawaban_benar = array_filter($jawaban_siswa->jawaban_benar, "strlen");
                    $jawaban_siswa->jawaban = array_map("strtoupper", $jawaban_siswa->jawaban);
                    $jawaban_siswa->jawaban = array_filter($jawaban_siswa->jawaban, "strlen");
                }
                if ($jawaban_siswa->jenis_soal == "3") {
                    $jawaban_siswa->jawaban_siswa = @unserialize($jawaban_siswa->jawaban_siswa);
                    $jawaban_siswa->jawaban_benar = @unserialize($jawaban_siswa->jawaban_benar);
                    $jawaban_siswa->jawaban = @unserialize($jawaban_siswa->jawaban);
                    $jawaban_siswa->jawaban_siswa = json_decode(json_encode($jawaban_siswa->jawaban_siswa));
                    $jawaban_siswa->jawaban_benar = json_decode(json_encode($jawaban_siswa->jawaban_benar));
                    $jawaban_siswa->jawaban = json_decode(json_encode($jawaban_siswa->jawaban));
                }
                $jawabans_siswa[$jawaban_siswa->id_siswa][$jawaban_siswa->jenis_soal][] = $jawaban_siswa;
            }

            $ada_jawaban = isset($jawabans_siswa[$siswa->id_siswa]);
            $ada_jawaban_pg = $ada_jawaban && isset($jawabans_siswa[$siswa->id_siswa]["1"]);
            $ada_jawaban_pg2 = $ada_jawaban && isset($jawabans_siswa[$siswa->id_siswa]["2"]);
            $ada_jawaban_jodoh = $ada_jawaban && isset($jawabans_siswa[$siswa->id_siswa]["3"]);
            $ada_jawaban_isian = $ada_jawaban && isset($jawabans_siswa[$siswa->id_siswa]["4"]);
            $ada_jawaban_essai = $ada_jawaban && isset($jawabans_siswa[$siswa->id_siswa]["5"]);

            $skor = new stdClass();
            $nilai_input = $this->cbt->getNilaiSiswaByJadwal($jadwal->id_jadwal, $siswa->id_siswa);
            if ($nilai_input != null) {
                $skor->dikoreksi = $nilai_input->dikoreksi;
            }

            // Calculate PG score
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
            $skor->skor_pg = $skor_pg = $bagi_pg == 0 ? 0 : round($benar_pg / $bagi_pg * $bobot_pg, 2);
            $skor->benar_pg = $benar_pg;

            // Calculate PG Kompleks score
            $jawaban_pg2 = $ada_jawaban_pg2 ? $jawabans_siswa[$siswa->id_siswa]["2"] : [];
            $benar_pg2 = 0;
            $skor_koreksi_pg2 = 0.0;
            $otomatis_pg2 = 0;
            if ($info->tampil_kompleks > 0 && count($jawaban_pg2) > 0) {
                foreach ($jawaban_pg2 as $jawab_pg2) {
                    $skor_koreksi_pg2 += $jawab_pg2->nilai_koreksi;
                    $arr_benar = [];
                    if ($jawab_pg2->jawaban_siswa) {
                        foreach ($jawab_pg2->jawaban_siswa as $js) {
                            if (in_array($js, $jawab_pg2->jawaban)) {
                                array_push($arr_benar, true);
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
            $input_pg2 = 0;
            if ($nilai_input != null && $nilai_input->kompleks_nilai != null) {
                $input_pg2 = $nilai_input->kompleks_nilai;
            }
            $skor_pg2 = $input_pg2 != 0 ? $input_pg2 : ($otomatis_pg2 == 0 ? $s_pg2 : $skor_koreksi_pg2);
            $skor->skor_kompleks = round($skor_pg2, 2);
            $skor->benar_kompleks = round($benar_pg2, 2);

            // Calculate Jodohkan score
            $jawaban_jodoh = $ada_jawaban_jodoh ? $jawabans_siswa[$siswa->id_siswa]["3"] : [];
            $benar_jod = 0;
            $skor_koreksi_jod = 0.0;
            $otomatis_jod = 0;
            if ($info->tampil_jodohkan > 0 && count($jawaban_jodoh) > 0) {
                foreach ($jawaban_jodoh as $jawab_jod) {
                    $skor_koreksi_jod += $jawab_jod->nilai_koreksi;
                    $typeSoal = $jawab_jod->jawaban->type;
                    $arrSoal = $jawab_jod->jawaban->jawaban;
                    $headSoal = array_shift($arrSoal);
                    $arrJwbSoal = [];
                    $items = 0;
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
                    $arrJawab = [];
                    $headJawab = null;
                    if (isset($jawab_jod->jawaban_siswa->jawaban)) {
                        $arrJawab = $jawab_jod->jawaban_siswa->jawaban;
                        $headJawab = array_shift($arrJawab);
                    }
                    $arrJwbJawab = [];
                    foreach ($arrJawab as $kolJawab) {
                        $jwbs = new stdClass();
                        foreach ($kolJawab as $po => $kol) {
                            if ($kol == "1") {
                                $sub = $headJawab[$po];
                                $jwbs->subtitle[] = $sub;
                            }
                        }
                        $jwbs->title = array_shift($kolJawab);
                        array_push($arrJwbJawab, $jwbs);
                    }
                    $item_benar = 0;
                    foreach ($arrJwbJawab as $p => $ajjs) {
                        if (!isset($ajjs->subtitle)) {
                            continue;
                        }
                        foreach ($ajjs->subtitle as $pp => $ajs) {
                            if (isset($arrJwbSoal[$p]) && isset($arrJwbSoal[$p]->subtitle)) {
                                if (in_array($ajs, $arrJwbSoal[$p]->subtitle)) {
                                    $item_benar++;
                                }
                            }
                        }
                    }
                    if ($items > 0) {
                        $benar_jod += 1 / $items * $item_benar;
                    }
                    $otomatis_jod = $jawab_jod->nilai_otomatis;
                }
            }
            $s_jod = $bagi_jodoh == 0 ? 0 : $benar_jod / $bagi_jodoh * $bobot_jodoh;
            $input_jod = 0;
            if ($nilai_input != null && $nilai_input->jodohkan_nilai != null) {
                $input_jod = $nilai_input->jodohkan_nilai;
            }
            $skor_jod = $input_jod != 0 ? $input_jod : ($otomatis_jod == 0 ? $s_jod : $skor_koreksi_jod);
            $skor->skor_jodohkan = round($skor_jod, 2);
            $skor->benar_jodohkan = round($benar_jod, 2);

            // Calculate Isian score
            $jawaban_is = $ada_jawaban_isian ? $jawabans_siswa[$siswa->id_siswa]["4"] : [];
            $benar_is = 0;
            $skor_koreksi_is = 0.0;
            $otomatis_is = 0;
            if ($info->tampil_isian > 0 && count($jawaban_is) > 0) {
                foreach ($jawaban_is as $jawab_is) {
                    $skor_koreksi_is += $jawab_is->nilai_koreksi;
                    $benar = $jawab_is != null && strtolower($jawab_is->jawaban_siswa) == strtolower($jawab_is->jawaban);
                    if ($benar) {
                        $benar_is++;
                    }
                    $otomatis_is = $jawab_is->nilai_otomatis;
                }
            }
            $s_is = $bagi_isian == 0 ? 0 : $benar_is / $bagi_isian * $bobot_isian;
            $input_is = 0;
            if ($nilai_input != null && $nilai_input->isian_nilai != null) {
                $input_is = $nilai_input->isian_nilai;
            }
            $skor_is = $input_is != 0 ? $input_is : ($otomatis_is == 0 ? $s_is : $skor_koreksi_is);
            $skor->skor_isian = round($skor_is, 2);
            $skor->benar_isian = $benar_is;

            // Calculate Essai score
            $jawaban_es = $ada_jawaban_essai ? $jawabans_siswa[$siswa->id_siswa]["5"] : [];
            $benar_es = 0;
            $skor_koreksi_es = 0.0;
            $otomatis_es = 0;
            if ($info->tampil_esai > 0 && count($jawaban_es) > 0) {
                foreach ($jawaban_es as $jawab_es) {
                    $skor_koreksi_es += $jawab_es->nilai_koreksi;
                    $benar = $jawab_es != null && strtolower($jawab_es->jawaban_siswa) == strtolower($jawab_es->jawaban);
                    if ($benar) {
                        $benar_es++;
                    }
                    $otomatis_es = $jawab_es->nilai_otomatis;
                }
            }
            $s_es = $bagi_essai == 0 ? 0 : $benar_es / $bagi_essai * $bobot_essai;
            $input_es = 0;
            if ($nilai_input != null && $nilai_input->essai_nilai != null) {
                $input_es = $nilai_input->essai_nilai;
            }
            $skor_es = $input_es != 0 ? $input_es : ($otomatis_es == 0 ? $s_es : $skor_koreksi_es);
            $skor->skor_essai = round($skor_es, 2);
            $skor->benar_esai = $benar_es;

            $total = $skor_pg + $skor_pg2 + $skor_jod + $skor_is + $skor_es;
            $skor->skor_total = round($total, 2);
            $skors[$jadwal->id_jadwal] = $skor;
            $durasies[$jadwal->id_jadwal] = $this->cbt->getDurasiSiswaByJadwal($jadwal->id_jadwal, $siswa->id_siswa);
        }
        $this->db->trans_complete();

        $data['skor'] = $skors;
        $data['durasi'] = $durasies;
        $data['jadwal'] = $jadwals;
        $data['jawaban'] = $jawabans;
        $data['kelass'] = $kelass_unset;
        $data['tp'] = $this->dashboard->getTahun();
        $data['smt'] = $this->dashboard->getSemester();

        $this->load->view('members/orangtua/templates/header', $data);
        $this->load->view('members/orangtua/nilai/data', $data);
        $this->load->view('members/orangtua/templates/footer');
    }

    public function kehadiran()
    {
        if (!$this->selected_anak) {
            redirect('orangtua');
        }

        $data = $this->getCommonData();
        $tp = $data['tp_active'];
        $smt = $data['smt_active'];

        $data['judul'] = 'Kehadiran';
        $data['subjudul'] = 'Absensi ' . $this->selected_anak->nama;

        $siswa = $this->orangtua->getSiswaDetailById($this->selected_anak->id_siswa, $tp->id_tp, $smt->id_smt);
        $data['siswa'] = $siswa;

        $this->load->model('Master_model', 'master');
        $this->load->model('Kelas_model', 'kelas_model');

        $today = date('Y-m-d');
        $day = date('N', strtotime($today));

        $kbm = $this->dashboard->getJadwalKbm($tp->id_tp, $smt->id_smt, $siswa->id_kelas);

        $result = $this->dashboard->loadJadwalHariIni($tp->id_tp, $smt->id_smt, $siswa->id_kelas, null);
        $jadwals = [];
        foreach ($result as $row) {
            $jadwals[$row->id_hari][$row->jam_ke] = $row;
        }

        $mapels = $this->master->getAllMapel();
        $arrIdMapel = [];
        foreach ($mapels as $mpl) {
            array_push($arrIdMapel, $mpl->id_mapel);
        }

        if ($kbm != null) {
            $bulan = date('m');
            $tahun = date('Y');
            $tgl = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);

            $materi_sebulan = [];
            for ($i = 0; $i < $tgl; $i++) {
                $t = $i + 1 < 10 ? '0' . ($i + 1) : $i + 1;
                $materi_sebulan[$t] = $this->kelas_model->getAllMateriByTgl($siswa->id_kelas, $tahun . '-' . $bulan . '-' . $t, $arrIdMapel);
            }

            $kbm->istirahat = unserialize($kbm->istirahat);
            $logs = $this->kelas_model->getRekapBulananSiswa(null, $siswa->id_kelas, $tahun, $bulan);

            $data['sebulan'] = [
                'log' => isset($logs[$siswa->id_siswa]) ? $logs[$siswa->id_siswa] : [],
                'materis' => $materi_sebulan
            ];
        } else {
            $data['sebulan'] = ['log' => [], 'materis' => []];
        }

        $data['kbm'] = $kbm;
        $data['mapels'] = $mapels;
        $data['jadwals'] = $jadwals;
        $data['jadwal'] = isset($jadwals[$day]) && $day != 7 ? $jadwals[$day] : [];

        $this->load->view('members/orangtua/templates/header', $data);
        $this->load->view('members/orangtua/absensi/data', $data);
        $this->load->view('members/orangtua/templates/footer');
    }

    public function tagihan()
    {
        if (!$this->selected_anak) {
            redirect('orangtua');
        }

        $data = $this->getCommonData();
        $data['judul'] = 'Tagihan';
        $data['subjudul'] = 'Tagihan ' . $this->selected_anak->nama;

        $id_siswa = $this->selected_anak->id_siswa;

        $data['tagihan_belum'] = $this->pembayaran->getTagihanBySiswa($id_siswa, ['belum_bayar', 'ditolak']);
        $data['tagihan_proses'] = $this->pembayaran->getTagihanBySiswa($id_siswa, 'menunggu_verifikasi');
        $data['tagihan_lunas'] = $this->pembayaran->getTagihanBySiswa($id_siswa, 'lunas');
        $data['config'] = $this->pembayaran->getConfig();
        $data['siswa'] = $this->orangtua->getSiswaById($id_siswa);

        $this->load->view('members/orangtua/templates/header', $data);
        $this->load->view('members/orangtua/tagihan/data', $data);
        $this->load->view('members/orangtua/templates/footer');
    }

    public function bayar($id_tagihan)
    {
        if (!$this->selected_anak) {
            redirect('orangtua');
        }

        $tagihan = $this->pembayaran->getTagihanById($id_tagihan);

        if (!$tagihan || $tagihan->id_siswa != $this->selected_anak->id_siswa) {
            show_404();
        }

        if (!$this->orangtua->isParentOfSiswa($this->user->id, $tagihan->id_siswa)) {
            show_error('Anda tidak memiliki akses ke tagihan ini', 403);
        }

        if (!in_array($tagihan->status, ['belum_bayar', 'ditolak'])) {
            redirect('orangtua/tagihan');
        }

        $data = $this->getCommonData();
        $data['judul'] = 'Bayar Tagihan';
        $data['subjudul'] = 'Upload Bukti Pembayaran';
        $data['tagihan'] = $tagihan;
        $data['config'] = $this->pembayaran->getConfig();
        $data['transaksi_terakhir'] = $this->pembayaran->getLatestTransaksiByTagihan($id_tagihan);
        $data['siswa'] = $this->orangtua->getSiswaById($this->selected_anak->id_siswa);

        $this->load->view('members/orangtua/templates/header', $data);
        $this->load->view('members/orangtua/tagihan/bayar', $data);
        $this->load->view('members/orangtua/templates/footer');
    }

    public function uploadBukti()
    {
        if (!$this->selected_anak) {
            $this->output_json(['status' => false, 'message' => 'Tidak ada anak yang dipilih']);
            return;
        }

        $id_tagihan = $this->input->post('id_tagihan');
        $tagihan = $this->pembayaran->getTagihanById($id_tagihan);

        if (!$tagihan || !$this->orangtua->isParentOfSiswa($this->user->id, $tagihan->id_siswa)) {
            $this->output_json(['status' => false, 'message' => 'Tagihan tidak valid']);
            return;
        }

        if (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] != 0) {
            $this->output_json(['status' => false, 'message' => 'File bukti pembayaran wajib diupload']);
            return;
        }

        $this->load->library('upload');
        $upload_path = './uploads/pembayaran/bukti/' . date('Y/m/');
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        $config_upload = [
            'upload_path' => $upload_path,
            'allowed_types' => Pembayaran_model::ALLOWED_UPLOAD_TYPES,
            'max_size' => Pembayaran_model::MAX_UPLOAD_SIZE_KB,
            'encrypt_name' => true,
            'file_ext_tolower' => true
        ];

        $this->upload->initialize($config_upload);

        if (!$this->upload->do_upload('bukti')) {
            $this->output_json(['status' => false, 'message' => strip_tags($this->upload->display_errors())]);
            return;
        }

        $upload_data = $this->upload->data();
        $file_path = $upload_path . $upload_data['file_name'];
        $file_hash = hash_file('sha256', $file_path);

        $metode_bayar = $this->input->post('metode_bayar');
        $tanggal_bayar = $this->input->post('tanggal_bayar');
        $catatan_siswa = $this->input->post('catatan_siswa', true);
        $last_transaksi = $this->pembayaran->getLatestTransaksiByTagihan($id_tagihan);

        $result = $this->pembayaran->processUploadBukti(
            $id_tagihan,
            $tagihan->id_siswa,
            $file_path,
            $file_hash,
            $metode_bayar,
            $tanggal_bayar,
            $catatan_siswa,
            $last_transaksi
        );

        if (!$result['success']) {
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            $this->output_json(['status' => false, 'message' => $result['message']]);
        } else {
            $this->output_json([
                'status' => true,
                'message' => $result['message'],
                'id_transaksi' => $result['id_transaksi']
            ]);
        }
    }

    public function riwayat()
    {
        if (!$this->selected_anak) {
            redirect('orangtua');
        }

        $data = $this->getCommonData();
        $data['judul'] = 'Riwayat Pembayaran';
        $data['subjudul'] = 'Riwayat ' . $this->selected_anak->nama;
        $data['transaksi'] = $this->pembayaran->getRiwayatTransaksiBySiswa($this->selected_anak->id_siswa);

        $this->load->view('members/orangtua/templates/header', $data);
        $this->load->view('members/orangtua/tagihan/riwayat', $data);
        $this->load->view('members/orangtua/templates/footer');
    }

    public function detailTransaksi($id)
    {
        $transaksi = $this->pembayaran->getTransaksiById($id);

        if (!$transaksi || !$this->orangtua->isParentOfSiswa($this->user->id, $transaksi->id_siswa)) {
            show_404();
        }

        $data = $this->getCommonData();
        $data['judul'] = 'Detail Transaksi';
        $data['subjudul'] = $transaksi->kode_transaksi;
        $data['transaksi'] = $transaksi;

        $this->load->view('members/orangtua/templates/header', $data);
        $this->load->view('members/orangtua/tagihan/detail_transaksi', $data);
        $this->load->view('members/orangtua/templates/footer');
    }
}
