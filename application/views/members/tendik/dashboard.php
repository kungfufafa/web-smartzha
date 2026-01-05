<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-12">
                    <h1><?= $judul ?></h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Selamat Datang, Tenaga Kependidikan!</h5>
                        <p>Silakan menggunakan menu <strong>Absensi</strong> untuk melakukan check-in dan check-out.</p>
                        <hr>
                        <ul class="mb-0">
                            <li><strong>Check-in / Check-out:</strong> Lakukan absensi harian Anda</li>
                            <li><strong>Riwayat Absensi:</strong> Lihat semua riwayat absensi yang sudah dilakukan</li>
                            <li><strong>Jadwal Shift:</strong> Lihat jadwal shift kerja Anda</li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <a href="<?= base_url('absensi') ?>" class="card card-outline card-primary text-center card-hover-shadow">
                                <div class="card-body">
                                    <i class="fas fa-calendar-alt fa-3x mb-3 text-primary"></i>
                                    <h4>Absensi</h4>
                                    <p class="text-muted">Lakukan check-in / check-out</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="<?= base_url('absensi/riwayat') ?>" class="card card-outline card-success text-center card-hover-shadow">
                                <div class="card-body">
                                    <i class="fas fa-history fa-3x mb-3 text-success"></i>
                                    <h4>Riwayat</h4>
                                    <p class="text-muted">Lihat riwayat absensi</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="<?= base_url('absensi/jadwal') ?>" class="card card-outline card-warning text-center card-hover-shadow">
                                <div class="card-body">
                                    <i class="fas fa-clock fa-3x mb-3 text-warning"></i>
                                    <h4>Jadwal Shift</h4>
                                    <p class="text-muted">Lihat jadwal kerja</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
