<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Absensi extends CI_Controller
{
    private $is_guru = false;
    private $guru = null;
    private $tp = null;
    private $smt = null;

    public function __construct()
    {
        parent::__construct();
        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        }
        $this->load->model('Dashboard_model', 'dashboard');
        $this->load->model('Shift_model', 'shift');
        $this->load->model('Absensi_model', 'absensi');
        $this->load->helper('absen');

        // Determine if user is guru
        $this->is_guru = $this->ion_auth->in_group('guru');
        if ($this->is_guru) {
            $this->tp = $this->dashboard->getTahunActive();
            $this->smt = $this->dashboard->getSemesterActive();
            $user = $this->ion_auth->user()->row();
            $this->guru = $this->dashboard->getDataGuruByUserId($user->id, $this->tp->id_tp, $this->smt->id_smt);
        }
    }

    public function output_json($data, $encode = true)
    {
        if ($encode) {
            $data = json_encode($data);
        }
        $this->output->set_content_type('application/json')->set_output($data);
    }

    /**
     * Load view dengan template yang sesuai (admin vs guru)
     */
    private function load_view($view, $data)
    {
        if ($this->is_guru) {
            $data['guru'] = $this->guru;
            $data['tp'] = $this->dashboard->getTahun();
            $data['tp_active'] = $this->tp;
            $data['smt'] = $this->dashboard->getSemester();
            $data['smt_active'] = $this->smt;
            
            $this->load->view('members/guru/templates/header', $data);
            $this->load->view('members/guru/templates/sidebar', $data);
            $this->load->view($view, $data);
            $this->load->view('members/guru/templates/footer');
        } else {
            $this->load->view('_templates/dashboard/_header', $data);
            $this->load->view($view, $data);
            $this->load->view('_templates/dashboard/_footer');
        }
    }

    public function index()
    {
        $user = $this->ion_auth->user()->row();
        $setting = $this->dashboard->getSetting();
        $data = [
            'user' => $user,
            'judul' => 'Absensi',
            'subjudul' => 'Check-in / Check-out',
            'setting' => $setting,
            'profile' => $this->dashboard->getProfileAdmin($user->id)
        ];

        $today = date('Y-m-d');
        $log = $this->absensi->get_today_log($user->id, $today);
        $shift = $this->shift->get_user_shift($user->id, $today);

        $data['log'] = $log;
        $data['shift'] = $shift;
        
        $this->load_view('absensi/checkin', $data);
    }

    public function do_checkin()
    {
        $user = $this->ion_auth->user()->row();
        $lat = $this->input->post('lat');
        $lng = $this->input->post('lng');
        $foto = $this->input->post('foto'); // Base64
        
        $today = date('Y-m-d');
        $time = date('H:i:s');
        
        // 1. Check Shift
        $shift = $this->shift->get_user_shift($user->id, $today);
        if (!$shift) {
            $this->output_json(['status' => false, 'message' => 'Anda tidak memiliki jadwal shift hari ini.']);
            return;
        }

        // 2. Check Existing Log
        $log = $this->absensi->get_today_log($user->id, $today);
        if ($log && $log->jam_masuk) {
            $this->output_json(['status' => false, 'message' => 'Anda sudah melakukan check-in hari ini.']);
            return;
        }

        // 3. Validate Location - Ambil koordinat dari setting
        $setting = $this->dashboard->getSetting();
        $center_lat = isset($setting->office_lat) && $setting->office_lat ? $setting->office_lat : -6.175392;
        $center_lng = isset($setting->office_lng) && $setting->office_lng ? $setting->office_lng : 106.827153;
        $radius = isset($setting->absen_radius) && $setting->absen_radius ? $setting->absen_radius : 100;

        $distance = calculate_distance($lat, $lng, $center_lat, $center_lng);
        
        // Validasi jarak - user harus berada dalam radius kantor
        if ($distance > $radius) {
            $this->output_json([
                'status' => false, 
                'message' => 'Anda berada di luar area kantor (' . round($distance) . ' meter dari lokasi). Maksimal jarak: ' . $radius . ' meter.'
            ]);
            return;
        }

        // 4. Determine Status (Terlambat logic)
        $status = 'Hadir';
        $terlambat_menit = 0;
        
        if ($time > $shift->jam_masuk) {
            $status = 'Terlambat';
            // Calculate late minutes
            $start = strtotime($shift->jam_masuk);
            $end = strtotime($time);
            $terlambat_menit = round(($end - $start) / 60);
        }

        // 5. Handle foto upload - simpan ke file, bukan base64 di database
        $foto_filename = null;
        if (!empty($foto)) {
            $upload_path = FCPATH . 'uploads/absensi/';
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }
            
            $foto_filename = 'checkin_' . $user->id . '_' . date('Ymd_His') . '.jpg';
            $foto_data = preg_replace('#^data:image/\w+;base64,#i', '', $foto);
            file_put_contents($upload_path . $foto_filename, base64_decode($foto_data));
        }

        // 6. Save
        $data = [
            'id_user' => $user->id,
            'id_shift' => $shift->id_shift,
            'tanggal' => $today,
            'jam_masuk' => date('Y-m-d H:i:s'),
            'status_kehadiran' => $status,
            'metode_masuk' => 'GPS',
            'lat_masuk' => $lat,
            'long_masuk' => $lng,
            'foto_masuk' => $foto_filename,
            'terlambat_menit' => $terlambat_menit
        ];

        $this->absensi->clock_in($data);
        $this->output_json(['status' => true, 'message' => 'Check-in berhasil! Status: ' . $status]);
    }

    public function do_checkout()
    {
        $user = $this->ion_auth->user()->row();
        $lat = $this->input->post('lat');
        $lng = $this->input->post('lng');
        
        $today = date('Y-m-d');
        $time = date('H:i:s');
        
        $log = $this->absensi->get_today_log($user->id, $today);
        
        if (!$log) {
            $this->output_json(['status' => false, 'message' => 'Anda belum melakukan check-in hari ini.']);
            return;
        }

        if ($log->jam_pulang) {
            $this->output_json(['status' => false, 'message' => 'Anda sudah melakukan check-out hari ini.']);
            return;
        }

        $setting = $this->dashboard->getSetting();
        $center_lat = isset($setting->office_lat) && $setting->office_lat ? $setting->office_lat : -6.175392;
        $center_lng = isset($setting->office_lng) && $setting->office_lng ? $setting->office_lng : 106.827153;
        $radius = isset($setting->absen_radius) && $setting->absen_radius ? $setting->absen_radius : 100;

        $distance = calculate_distance($lat, $lng, $center_lat, $center_lng);
        
        if ($distance > $radius) {
            $this->output_json([
                'status' => false, 
                'message' => 'Checkout harus dilakukan di area kantor (' . round($distance) . ' meter dari lokasi). Maksimal: ' . $radius . ' meter.'
            ]);
            return;
        }

        $shift = $this->shift->get_shift_by_id($log->id_shift);
        
        $pulang_awal_menit = 0;
        $status = $log->status_kehadiran;
        
        if ($time < $shift->jam_pulang) {
            if ($log->status_kehadiran == 'Terlambat') {
                $status = 'Terlambat + Pulang Awal';
            } else {
                $status = 'Pulang Awal';
            }
            $start = strtotime($time);
            $end = strtotime($shift->jam_pulang);
            $pulang_awal_menit = round(($end - $start) / 60);
        }

        $data = [
            'jam_pulang' => date('Y-m-d H:i:s'),
            'metode_pulang' => 'GPS',
            'lat_pulang' => $lat,
            'long_pulang' => $lng,
            'status_kehadiran' => $status,
            'pulang_awal_menit' => $pulang_awal_menit
        ];

        $this->absensi->clock_out($log->id_log, $data);
        $this->output_json(['status' => true, 'message' => 'Check-out berhasil!']);
    }

    public function riwayat()
    {
        $user = $this->ion_auth->user()->row();
        $setting = $this->dashboard->getSetting();
        
        $bulan = $this->input->get('bulan') ? $this->input->get('bulan') : date('m');
        $tahun = $this->input->get('tahun') ? $this->input->get('tahun') : date('Y');

        $data = [
            'user' => $user,
            'judul' => 'Riwayat Absensi',
            'subjudul' => 'Log Kehadiran',
            'setting' => $setting,
            'profile' => $this->dashboard->getProfileAdmin($user->id),
            'bulan' => $bulan,
            'tahun' => $tahun,
            'logs' => $this->absensi->get_history($user->id, $bulan, $tahun),
            'rekap' => $this->absensi->get_rekap_bulanan($user->id, $bulan, $tahun)
        ];
        
        $this->load_view('absensi/riwayat', $data);
    }
}
