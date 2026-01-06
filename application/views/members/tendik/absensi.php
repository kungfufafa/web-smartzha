<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper" style="margin-top: -1px;">
    <div class="sticky"></div>
    <section class="content overlap p-4">
        <div class="container">
            <!-- Profile Card -->
            <div class="info-box bg-transparent shadow-none">
                <?php 
                $foto = isset($tendik) && $tendik && $tendik->foto ? $tendik->foto : 'assets/adminlte/dist/img/avatar5.png';
                ?>
                <img class="avatar rounded-circle" src="<?= base_url($foto) ?>" width="80" height="80" style="object-fit: cover;">
                <div class="info-box-content">
                    <h5 class="info-box-text text-white text-wrap"><b><?= isset($profile) ? $profile->nama_lengkap : 'Tendik' ?></b></h5>
                    <span class="info-box-text text-white"><?= $judul ?></span>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <div class="card-title text-white">
                                <i class="fas fa-calendar-check mr-2"></i><?= $subjudul ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($this->session->flashdata('success')): ?>
                                <div class="alert alert-success"><?= $this->session->flashdata('success') ?></div>
                            <?php endif; ?>
                            <?php if ($this->session->flashdata('error')): ?>
                                <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
                            <?php endif; ?>

                            <!-- Status Hari Ini -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <h5><i class="fas fa-info-circle mr-2"></i>Status Hari Ini - <?= date('d F Y') ?></h5>
                                        <?php if (isset($today_log) && $today_log): ?>
                                            <p class="mb-1">
                                                <strong>Check-in:</strong> 
                                                <?= $today_log->jam_masuk ? date('H:i', strtotime($today_log->jam_masuk)) : '<span class="text-danger">Belum</span>' ?>
                                            </p>
                                            <p class="mb-1">
                                                <strong>Check-out:</strong> 
                                                <?= $today_log->jam_pulang ? date('H:i', strtotime($today_log->jam_pulang)) : '<span class="text-warning">Belum</span>' ?>
                                            </p>
                                            <p class="mb-0">
                                                <strong>Status:</strong> 
                                                <span class="badge badge-<?= $today_log->status_kehadiran == 'Hadir' ? 'success' : ($today_log->status_kehadiran == 'Terlambat' ? 'warning' : 'secondary') ?>">
                                                    <?= $today_log->status_kehadiran ?>
                                                </span>
                                            </p>
                                        <?php else: ?>
                                            <p class="mb-0">Anda belum melakukan absensi hari ini.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Shift Info -->
                            <?php if (isset($shift) && $shift): ?>
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6><i class="fas fa-clock mr-2"></i>Shift Anda Hari Ini</h6>
                                            <p class="mb-1"><strong><?= $shift->nama_shift ?></strong></p>
                                            <p class="mb-0">
                                                Jam Masuk: <strong><?= date('H:i', strtotime($shift->jam_masuk)) ?></strong> - 
                                                Jam Pulang: <strong><?= date('H:i', strtotime($shift->jam_pulang)) ?></strong>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Action Buttons -->
                            <div class="row">
                                <div class="col-6">
                                    <?php 
                                    $can_checkin = !isset($today_log) || !$today_log || !$today_log->jam_masuk;
                                    ?>
                                    <button type="button" class="btn btn-success btn-lg btn-block" <?= $can_checkin ? '' : 'disabled' ?> onclick="doCheckin()">
                                        <i class="fas fa-sign-in-alt fa-2x mb-2"></i><br>
                                        CHECK-IN
                                    </button>
                                </div>
                                <div class="col-6">
                                    <?php 
                                    $can_checkout = isset($today_log) && $today_log && $today_log->jam_masuk && !$today_log->jam_pulang;
                                    ?>
                                    <button type="button" class="btn btn-danger btn-lg btn-block" <?= $can_checkout ? '' : 'disabled' ?> onclick="doCheckout()">
                                        <i class="fas fa-sign-out-alt fa-2x mb-2"></i><br>
                                        CHECK-OUT
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
function doCheckin() {
    Swal.fire({
        title: 'Check-in',
        text: 'Lakukan check-in sekarang?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Check-in!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Get location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    submitCheckin(position.coords.latitude, position.coords.longitude);
                }, function(error) {
                    Swal.fire('Error', 'Gagal mendapatkan lokasi. Pastikan GPS aktif.', 'error');
                });
            } else {
                Swal.fire('Error', 'Browser tidak mendukung geolocation', 'error');
            }
        }
    });
}

function submitCheckin(lat, lng) {
    $.ajax({
        url: base_url + 'presensi/checkin',
        type: 'POST',
        data: {
            latitude: lat,
            longitude: lng,
            <?= $this->security->get_csrf_token_name() ?>: '<?= $this->security->get_csrf_hash() ?>'
        },
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                Swal.fire('Berhasil!', res.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Gagal!', res.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error!', 'Terjadi kesalahan server', 'error');
        }
    });
}

function doCheckout() {
    Swal.fire({
        title: 'Check-out',
        text: 'Lakukan check-out sekarang?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Check-out!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    submitCheckout(position.coords.latitude, position.coords.longitude);
                }, function(error) {
                    Swal.fire('Error', 'Gagal mendapatkan lokasi. Pastikan GPS aktif.', 'error');
                });
            } else {
                Swal.fire('Error', 'Browser tidak mendukung geolocation', 'error');
            }
        }
    });
}

function submitCheckout(lat, lng) {
    $.ajax({
        url: base_url + 'presensi/checkout',
        type: 'POST',
        data: {
            latitude: lat,
            longitude: lng,
            <?= $this->security->get_csrf_token_name() ?>: '<?= $this->security->get_csrf_hash() ?>'
        },
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                Swal.fire('Berhasil!', res.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Gagal!', res.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error!', 'Terjadi kesalahan server', 'error');
        }
    });
}
</script>
