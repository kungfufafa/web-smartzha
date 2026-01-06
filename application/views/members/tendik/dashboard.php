<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper" style="margin-top: -1px;">
    <!-- Main content -->
    <div class="sticky">
    </div>
    <section class="content overlap p-4">
        <div class="container">
            <!-- Profile Card -->
            <div class="info-box bg-transparent shadow-none">
                <?php 
                $foto = isset($tendik) && $tendik && $tendik->foto ? $tendik->foto : 'assets/adminlte/dist/img/avatar5.png';
                ?>
                <img class="avatar rounded-circle" src="<?= base_url($foto) ?>" width="120" height="120" style="object-fit: cover;">
                <div class="info-box-content">
                    <h5 class="info-box-text text-white text-wrap"><b><?= isset($profile) ? $profile->nama_lengkap : 'Tendik' ?></b></h5>
                    <span class="info-box-text text-white"><?= isset($profile) ? $profile->jabatan : '' ?></span>
                    <span class="info-box-text text-white mb-1">Tenaga Kependidikan</span>
                </div>
            </div>

            <script>
                $(`.avatar`).each(function () {
                    $(this).on("error", function () {
                        $(this).attr("src", base_url + 'assets/adminlte/dist/img/avatar5.png');
                    });
                });
            </script>

            <div class="row">
                <div class="col-12">
                    <div class="card card-blue">
                        <div class="card-header">
                            <div class="card-title text-white">
                                MENU UTAMA
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Absensi -->
                                <div class="col-lg-2 col-sm-3 col-4 mb-3">
                                    <a href="<?= base_url('tendik/absensi') ?>">
                                        <figure class="text-center">
                                            <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                                                <i class="fas fa-calendar-check fa-2x text-white"></i>
                                            </div>
                                            <figcaption class="mt-2">Absensi</figcaption>
                                        </figure>
                                    </a>
                                </div>
                                <!-- Riwayat -->
                                <div class="col-lg-2 col-sm-3 col-4 mb-3">
                                    <a href="<?= base_url('tendik/riwayat') ?>">
                                        <figure class="text-center">
                                            <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                                                <i class="fas fa-history fa-2x text-white"></i>
                                            </div>
                                            <figcaption class="mt-2">Riwayat</figcaption>
                                        </figure>
                                    </a>
                                </div>
                                <!-- Jadwal Shift -->
                                <div class="col-lg-2 col-sm-3 col-4 mb-3">
                                    <a href="<?= base_url('tendik/jadwal') ?>">
                                        <figure class="text-center">
                                            <div class="bg-warning rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                                                <i class="fas fa-clock fa-2x text-white"></i>
                                            </div>
                                            <figcaption class="mt-2">Jadwal</figcaption>
                                        </figure>
                                    </a>
                                </div>
                                <!-- Pengajuan Izin -->
                                <div class="col-lg-2 col-sm-3 col-4 mb-3">
                                    <a href="<?= base_url('tendik/pengajuan') ?>">
                                        <figure class="text-center">
                                            <div class="bg-info rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                                                <i class="fas fa-file-alt fa-2x text-white"></i>
                                            </div>
                                            <figcaption class="mt-2">Pengajuan</figcaption>
                                        </figure>
                                    </a>
                                </div>
                                <!-- Profil -->
                                <div class="col-lg-2 col-sm-3 col-4 mb-3">
                                    <a href="<?= base_url('tendik/profil') ?>">
                                        <figure class="text-center">
                                            <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                                                <i class="fas fa-user fa-2x text-white"></i>
                                            </div>
                                            <figcaption class="mt-2">Profil</figcaption>
                                        </figure>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Section -->
            <div class="row">
                <div class="col-12">
                    <div class="card card-success">
                        <div class="card-header">
                            <div class="card-title text-white">
                                <i class="fas fa-info-circle mr-2"></i>INFORMASI
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-light mb-0">
                                <h5><i class="fas fa-hand-paper text-warning mr-2"></i>Selamat Datang!</h5>
                                <p class="mb-0">Silakan gunakan menu di atas untuk melakukan absensi dan melihat riwayat kehadiran Anda.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>
