<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $judul ?></h1>
                    <?php if (!empty($subjudul)): ?>
                        <p class="text-muted mb-0"><?= $subjudul ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

	    <section class="content">
	        <div class="container-fluid">
	            <div class="card card-outline card-primary">
	                <div class="card-header">
	                    <h3 class="card-title"><i class="fas fa-users mr-1"></i> Rekap Hari Ini - PTK (Guru + Tendik)</h3>
	                </div>
	                <div class="card-body">
	                    <div class="row">
	                        <div class="col-lg-3 col-6">
	                            <div class="small-box bg-success">
	                                <div class="inner">
	                                    <h3><?= number_format($stats_ptk['hadir'] ?? 0) ?></h3>
	                                    <p>Hadir</p>
	                                </div>
	                                <div class="icon"><i class="fas fa-user-check"></i></div>
	                            </div>
	                        </div>
	                        <div class="col-lg-3 col-6">
	                            <div class="small-box bg-warning">
	                                <div class="inner">
	                                    <h3><?= number_format($stats_ptk['terlambat'] ?? 0) ?></h3>
	                                    <p>Terlambat</p>
	                                </div>
	                                <div class="icon"><i class="fas fa-clock"></i></div>
	                            </div>
	                        </div>
	                        <div class="col-lg-3 col-6">
	                            <div class="small-box bg-danger">
	                                <div class="inner">
	                                    <h3><?= number_format($stats_ptk['alpha'] ?? 0) ?></h3>
	                                    <p>Alpha</p>
	                                </div>
	                                <div class="icon"><i class="fas fa-user-times"></i></div>
	                            </div>
	                        </div>
	                        <div class="col-lg-3 col-6">
	                            <div class="small-box bg-info">
	                                <div class="inner">
	                                    <h3><?= number_format($stats_ptk['izin'] ?? 0) ?></h3>
	                                    <p>Izin / Sakit / Cuti</p>
	                                </div>
	                                <div class="icon"><i class="fas fa-notes-medical"></i></div>
	                            </div>
	                        </div>
	                    </div>
	                </div>
	            </div>

	            <div class="card card-outline card-success">
	                <div class="card-header">
	                    <h3 class="card-title"><i class="fas fa-user-graduate mr-1"></i> Rekap Hari Ini - Siswa</h3>
	                </div>
	                <div class="card-body">
	                    <div class="row">
	                        <div class="col-lg-3 col-6">
	                            <div class="small-box bg-success">
	                                <div class="inner">
	                                    <h3><?= number_format($stats_siswa['hadir'] ?? 0) ?></h3>
	                                    <p>Hadir</p>
	                                </div>
	                                <div class="icon"><i class="fas fa-user-check"></i></div>
	                            </div>
	                        </div>
	                        <div class="col-lg-3 col-6">
	                            <div class="small-box bg-warning">
	                                <div class="inner">
	                                    <h3><?= number_format($stats_siswa['terlambat'] ?? 0) ?></h3>
	                                    <p>Terlambat</p>
	                                </div>
	                                <div class="icon"><i class="fas fa-clock"></i></div>
	                            </div>
	                        </div>
	                        <div class="col-lg-3 col-6">
	                            <div class="small-box bg-danger">
	                                <div class="inner">
	                                    <h3><?= number_format($stats_siswa['alpha'] ?? 0) ?></h3>
	                                    <p>Alpha</p>
	                                </div>
	                                <div class="icon"><i class="fas fa-user-times"></i></div>
	                            </div>
	                        </div>
	                        <div class="col-lg-3 col-6">
	                            <div class="small-box bg-info">
	                                <div class="inner">
	                                    <h3><?= number_format($stats_siswa['izin'] ?? 0) ?></h3>
	                                    <p>Izin / Sakit / Cuti</p>
	                                </div>
	                                <div class="icon"><i class="fas fa-notes-medical"></i></div>
	                            </div>
	                        </div>
	                    </div>
	                </div>
	            </div>

	            <div class="row">
	                <div class="col-12">
	                    <div class="card card-outline card-secondary">
	                        <div class="card-header">
	                            <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Panduan Cepat Setup Presensi</h3>
	                        </div>
	                        <div class="card-body">
	                            <ol class="pl-3 mb-0">
	                                <li><strong>Kelola Shift</strong>: buat jam masuk/pulang, toleransi telat, dan opsi lintas hari.</li>
	                                <li><strong>Kelola Lokasi</strong>: set lokasi sekolah + radius (untuk validasi GPS) dan pilih lokasi default.</li>
		                                <li><strong>Pengaturan Sistem</strong>: atur hal sistem (QR validity/refresh, auto-alpha tanpa cronjob, timezone, limit bypass, dll).</li>
		                                <li><strong>Konfigurasi Group</strong>: override aturan per group (guru/tendik/siswa; satpam termasuk tendik) + set shift/lokasi default per group.</li>
		                                <li><strong>Jadwal Presensi</strong>: mapping group + hari → shift (guru/siswa + default tendik).</li>
		                                <li><strong>Hari Libur</strong>: set tanggal merah/libur akademik/kantor agar tidak dihitung Alpha.</li>
		                                <li><strong>Jadwal Tendik (Per Tipe)</strong> (opsional): khusus tendik, atur jam kerja per tipe (SATPAM/TU/dll) di <a href="<?= base_url('presensi/jadwal_kerja#jadwal-tendik') ?>"><strong>Jadwal Presensi</strong></a>.</li>
		                                <li><strong>Jadwal User (Override Mingguan)</strong> (opsional): untuk beda shift per orang (satpam pagi/malam, guru panggilan, siswa sesi 1/2) di <a href="<?= base_url('presensi/jadwal_kerja#jadwal-user') ?>"><strong>Jadwal Presensi</strong></a>.</li>
		                            </ol>
		                            <div class="alert alert-light border mt-3 mb-0">
		                                <small class="text-muted">
		                                    Konfigurasi utama (validasi/selfie/pulang/bypass): <strong>Konfigurasi Group</strong>. Pengaturan Sistem: QR, Auto-Alpha (jalan saat admin buka menu), Timezone, limit bypass.
		                                    Prioritas shift: <strong>Override Tanggal</strong> → <strong>Jadwal User</strong> → <strong>Jadwal Tendik (Per Tipe)</strong> → <strong>Jadwal Presensi</strong>. Jika tidak ada jadwal: <strong>Libur</strong>.
		                                    Jadwal presensi tidak diperlukan untuk group <strong>Admin</strong> / <strong>Orangtua</strong>.
		                                    Jika mode QR dipakai, pastikan admin men-generate token di menu <strong>QR Token</strong>.
		                                </small>
		                            </div>
	                        </div>
	                    </div>
	                </div>
	            </div>

	            <div class="row">
	                <div class="col-12">
	                    <div class="card">
	                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-cogs mr-1"></i> Menu Presensi</h3>
	                        </div>
	                        <div class="card-body">
	                            <div class="row">
	                                <div class="col-md-3 col-6 mb-3">
	                                    <a href="<?= base_url('presensi/shift_management') ?>" class="btn btn-outline-primary btn-block">
	                                        <i class="fas fa-business-time fa-2x mb-2"></i><br>Kelola Shift
	                                    </a>
	                                </div>
	                                <div class="col-md-3 col-6 mb-3">
	                                    <a href="<?= base_url('presensi/rekap') ?>" class="btn btn-outline-secondary btn-block">
	                                        <i class="fas fa-chart-bar fa-2x mb-2"></i><br>Rekap Presensi
	                                    </a>
	                                </div>
	                                <div class="col-md-3 col-6 mb-3">
	                                    <a href="<?= base_url('presensi/bypass_manage') ?>" class="btn btn-outline-success btn-block">
	                                        <i class="fas fa-user-shield fa-2x mb-2"></i><br>Approval Bypass
	                                    </a>
	                                </div>
	                                <div class="col-md-3 col-6 mb-3">
	                                    <a href="<?= base_url('presensi/location_management') ?>" class="btn btn-outline-success btn-block">
	                                        <i class="fas fa-map-marker-alt fa-2x mb-2"></i><br>Kelola Lokasi
	                                    </a>
	                                </div>
	                                <div class="col-md-3 col-6 mb-3">
	                                    <a href="<?= base_url('presensi/global_config') ?>" class="btn btn-outline-dark btn-block">
	                                        <i class="fas fa-sliders-h fa-2x mb-2"></i><br>Pengaturan Sistem
	                                    </a>
	                                </div>
	                                <div class="col-md-3 col-6 mb-3">
	                                    <a href="<?= base_url('presensi/group_config') ?>" class="btn btn-outline-warning btn-block">
	                                        <i class="fas fa-users-cog fa-2x mb-2"></i><br>Konfigurasi Group
	                                    </a>
	                                </div>
			                                <div class="col-md-3 col-6 mb-3">
			                                    <a href="<?= base_url('presensi/jadwal_kerja') ?>" class="btn btn-outline-info btn-block">
			                                        <i class="fas fa-calendar-alt fa-2x mb-2"></i><br>Jadwal Presensi
			                                    </a>
			                                </div>
		                                <div class="col-md-3 col-6 mb-3">
		                                    <a href="<?= base_url('presensi/hari_libur') ?>" class="btn btn-outline-danger btn-block">
		                                        <i class="fas fa-calendar-times fa-2x mb-2"></i><br>Hari Libur
		                                    </a>
		                                </div>
		                                <div class="col-md-3 col-6 mb-3">
		                                    <a href="<?= base_url('presensi/list_qr_tokens') ?>" class="btn btn-outline-secondary btn-block">
		                                        <i class="fas fa-qrcode fa-2x mb-2"></i><br>QR Token
		                                    </a>
		                                </div>
	                            </div>
	                        </div>
	                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
