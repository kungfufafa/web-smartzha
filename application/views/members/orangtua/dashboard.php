<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper" style="margin-top: -1px;">
    <!-- Main content -->
    <div class="sticky">
    </div>
    <section class="content overlap p-4">
        <div class="container">
            <!-- Student Info Card -->
            <div class="info-box bg-transparent shadow-none">
                <?php 
                $foto = $selected_anak->foto ?: 'assets/img/siswa.png';
                ?>
                <img class="avatar" src="<?= base_url($foto) ?>" width="120" height="120">
                <div class="info-box-content">
                    <h5 class="info-box-text text-white text-wrap"><b><?= $selected_anak->nama ?></b></h5>
                    <span class="info-box-text text-white"><?= $selected_anak->nis ?? '-' ?></span>
                    <span class="info-box-text text-white mb-1"><?= $selected_anak->nama_kelas ?? 'Belum ada kelas' ?></span>
                </div>
            </div>

            <script>
                $(`.avatar`).each(function () {
                    $(this).on("error", function () {
                        var src = $(this).attr('src').replace('profiles', 'foto_siswa');
                        $(this).attr("src", src);
                        $(this).on("error", function () {
                            $(this).attr("src", base_url + 'assets/img/siswa.png');
                        });
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
                                <?php foreach ($menu as $m): ?>
                                    <div class="col-lg-2 col-sm-3 col-4 mb-3">
                                        <a href="<?= base_url($m->link) ?>">
                                            <figure class="text-center">
                                                <img class="img-fluid"
                                                     src="<?= base_url() ?>/assets/app/img/<?= $m->icon ?>" width="80"
                                                     height="80"/>
                                                <figcaption><?= $m->title ?></figcaption>
                                            </figure>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informasi Anak -->
            <div class="row">
                <div class="col-12">
                    <div class="card card-success">
                        <div class="card-header">
                            <div class="card-title text-white">
                                <i class="fas fa-user-graduate mr-2"></i>INFORMASI ANAK
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="200">Nama Lengkap</td>
                                        <td><strong><?= $selected_anak->nama ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td>NIS</td>
                                        <td><?= $selected_anak->nis ?? '-' ?></td>
                                    </tr>
                                    <tr>
                                        <td>NISN</td>
                                        <td><?= $selected_anak->nisn ?? '-' ?></td>
                                    </tr>
                                    <tr>
                                        <td>Kelas</td>
                                        <td><?= $selected_anak->nama_kelas ?? 'Belum ada kelas' ?></td>
                                    </tr>
                                    <tr>
                                        <td>Jenis Kelamin</td>
                                        <td><?= $selected_anak->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (count($anak_list) > 1): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card card-info">
                        <div class="card-header">
                            <div class="card-title text-white">
                                <i class="fas fa-users mr-2"></i>DAFTAR ANAK
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($anak_list as $anak): ?>
                                <div class="col-md-4 col-sm-6 mb-3">
                                    <div class="card <?= $anak->id_siswa == $selected_anak->id_siswa ? 'bg-primary' : 'bg-light' ?>">
                                        <div class="card-body text-center">
                                            <img src="<?= base_url() ?>assets/img/siswa.png" class="rounded-circle mb-2" width="60" height="60">
                                            <h6 class="<?= $anak->id_siswa == $selected_anak->id_siswa ? 'text-white' : '' ?>">
                                                <?= $anak->nama ?>
                                            </h6>
                                            <small class="<?= $anak->id_siswa == $selected_anak->id_siswa ? 'text-white-50' : 'text-muted' ?>">
                                                <?= $anak->nama_kelas ?? 'Belum ada kelas' ?>
                                            </small>
                                            <?php if ($anak->id_siswa != $selected_anak->id_siswa): ?>
                                            <div class="mt-2">
                                                <a href="<?= base_url('orangtua/switchAnak/' . $anak->id_siswa) ?>" class="btn btn-sm btn-primary">
                                                    Lihat
                                                </a>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </section>
</div>
