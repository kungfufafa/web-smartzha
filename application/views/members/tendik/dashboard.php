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
                $foto = 'assets/adminlte/dist/img/avatar5.png';
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

            <!-- Presensi Section -->
            <div class="row" id="presensi">
                <div class="col-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <div class="card-title text-white">
                                <i class="fas fa-calendar-check mr-2"></i>Presensi Hari Ini
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($this->session->flashdata('success')): ?>
                                <div class="alert alert-success"><?= $this->session->flashdata('success') ?></div>
                            <?php endif; ?>
	                            <?php if ($this->session->flashdata('error')): ?>
	                                <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
	                            <?php endif; ?>

	                            <?php
	                            $log_display = (isset($open_log) && $open_log) ? $open_log : ((isset($today_log) && $today_log) ? $today_log : null);
	                            $has_shift = isset($shift) && $shift;
	                            $validation_mode = (isset($presensi_config) && $presensi_config && $presensi_config->validation_mode) ? $presensi_config->validation_mode : 'gps';
	                            $require_photo = (isset($presensi_config) && $presensi_config) ? (int) $presensi_config->require_photo : 0;
	                            $require_checkout = (isset($presensi_config) && $presensi_config) ? (int) $presensi_config->require_checkout : 1;
	                            $allow_bypass = (isset($presensi_config) && $presensi_config) ? (int) $presensi_config->allow_bypass : 0;
	                            ?>

	                            <div class="alert alert-info">
	                                <h5><i class="fas fa-info-circle mr-2"></i>Status - <?= date('d F Y') ?></h5>
	                                <?php if ($log_display): ?>
	                                    <p class="mb-1">
	                                        <strong>Check-in:</strong>
	                                        <?= $log_display->jam_masuk ? date('H:i', strtotime($log_display->jam_masuk)) : '<span class="text-danger">Belum</span>' ?>
	                                    </p>
	                                    <p class="mb-1">
	                                        <strong>Check-out:</strong>
	                                        <?= $log_display->jam_pulang ? date('H:i', strtotime($log_display->jam_pulang)) : '<span class="text-warning">Belum</span>' ?>
	                                    </p>
	                                    <p class="mb-0">
	                                        <strong>Status:</strong>
	                                        <span class="badge badge-<?= $log_display->status_kehadiran == 'Hadir' ? 'success' : ($log_display->status_kehadiran == 'Terlambat' ? 'warning' : 'secondary') ?>">
	                                            <?= $log_display->status_kehadiran ?>
	                                        </span>
	                                    </p>
	                                    <?php if (isset($open_log) && $open_log && $open_log->tanggal !== date('Y-m-d')): ?>
	                                        <small class="text-muted d-block mt-2">
	                                            Presensi terbuka dari tanggal <strong><?= date('d F Y', strtotime($open_log->tanggal)) ?></strong>.
	                                        </small>
	                                    <?php endif; ?>
	                                <?php else: ?>
	                                    <?php if ($has_shift): ?>
	                                        <p class="mb-0">Anda belum melakukan presensi hari ini.</p>
	                                    <?php else: ?>
	                                        <p class="mb-0">Hari ini tidak ada jadwal presensi untuk Anda.</p>
	                                    <?php endif; ?>
	                                <?php endif; ?>
	                            </div>

	                            <?php
	                            $shift_display = null;
	                            $shift_title = 'Shift Anda Hari Ini';
	                            if (isset($open_log) && $open_log && isset($open_shift) && $open_shift) {
	                                $shift_display = $open_shift;
	                                $shift_title = 'Shift Presensi Terbuka';
	                            } elseif (isset($shift) && $shift) {
	                                $shift_display = $shift;
	                            }
	                            ?>
	                            <?php if ($shift_display): ?>
	                                <div class="card bg-light mb-3">
	                                    <div class="card-body">
	                                        <h6><i class="fas fa-clock mr-2"></i><?= $shift_title ?></h6>
	                                        <p class="mb-1"><strong><?= $shift_display->nama_shift ?></strong></p>
	                                        <p class="mb-0">
	                                            Jam Masuk: <strong><?= date('H:i', strtotime($shift_display->jam_masuk)) ?></strong> -
	                                            Jam Pulang: <strong><?= date('H:i', strtotime($shift_display->jam_pulang)) ?></strong>
	                                        </p>
	                                    </div>
	                                </div>
	                            <?php endif; ?>

	                            <?php if (!$has_shift && isset($log_display) && $log_display): ?>
	                                <div class="alert alert-warning">
	                                    <i class="fas fa-calendar-times mr-2"></i>Hari ini tidak ada jadwal presensi untuk Anda.
	                                </div>
	                            <?php endif; ?>

	                            <div class="alert alert-light">
	                                <div>
	                                    <strong>Mode Validasi:</strong> <?= strtoupper(str_replace('_', ' ', $validation_mode)) ?>
	                                    <?php if ($require_photo): ?>
	                                        <span class="badge badge-warning ml-2">Selfie wajib</span>
	                                    <?php endif; ?>
	                                </div>
	                            </div>

	                            <?php if (in_array($validation_mode, ['qr', 'gps_or_qr', 'any'], true)): ?>
	                                <div class="form-group">
	                                    <label>QR Token<?= $validation_mode === 'qr' ? ' *' : '' ?></label>
	                                    <input type="text" class="form-control" id="presensi-qr-token" placeholder="Masukkan QR token">
	                                    <?php if ($validation_mode !== 'qr'): ?>
	                                        <small class="text-muted">Opsional, bisa digunakan untuk presensi via QR.</small>
	                                    <?php endif; ?>
	                                </div>
	                            <?php endif; ?>

	                            <?php if ($require_photo): ?>
	                                <div class="form-group">
	                                    <label>Foto Selfie *</label>
	                                    <input type="file" class="form-control" id="presensi-photo" accept="image/*" capture="user">
	                                    <small class="text-muted">Wajib untuk check-in.</small>
	                                </div>
	                            <?php endif; ?>

	                            <div class="row">
	                                <div class="col-6">
	                                    <?php
	                                    $blocked_statuses = ['Izin', 'Sakit', 'Cuti', 'Dinas Luar'];
	                                    $status_block = isset($today_log) && $today_log && in_array($today_log->status_kehadiran, $blocked_statuses, true);
	                                    $has_open_log = isset($open_log) && $open_log;
	                                    $block_by_open_log = ($require_checkout === 1 && $has_open_log);

	                                    $can_checkin = isset($shift) && $shift && !$status_block && !$block_by_open_log && (!isset($today_log) || !$today_log || !$today_log->jam_masuk);
	                                    ?>
	                                    <button type="button" class="btn btn-success btn-lg btn-block" <?= $can_checkin ? '' : 'disabled' ?> onclick="doCheckin()">
	                                        <i class="fas fa-sign-in-alt fa-2x mb-2"></i><br>
	                                        CHECK-IN
	                                    </button>
	                                </div>
	                                <div class="col-6">
	                                    <?php
	                                    $can_checkout = isset($open_log) && $open_log && $open_log->jam_masuk && !$open_log->jam_pulang;
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
                                <p class="mb-0">Silakan lakukan presensi pada bagian "Presensi Hari Ini" dan gunakan menu untuk melihat riwayat kehadiran Anda.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<script>
var presensiValidationMode = '<?= $validation_mode ?>';
var presensiRequirePhoto = <?= (int) $require_photo ?>;
var presensiAllowBypass = <?= (int) $allow_bypass ?>;
var csrfName = '<?= $this->security->get_csrf_token_name() ?>';
var csrfHash = '<?= $this->security->get_csrf_hash() ?>';

function getPresensiQrToken() {
    var el = document.getElementById('presensi-qr-token');
    if (!el) {
        return '';
    }
    return (el.value || '').trim();
}

function getPresensiPhotoFile() {
    var el = document.getElementById('presensi-photo');
    if (!el || !el.files || !el.files.length) {
        return null;
    }
    return el.files[0];
}

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
        if (result.value || result.isConfirmed) {
            var qrToken = getPresensiQrToken();
            var photoFile = getPresensiPhotoFile();

            if (presensiRequirePhoto && !photoFile) {
                Swal.fire('Gagal!', 'Foto selfie wajib untuk check-in.', 'error');
                return;
            }

            if (presensiValidationMode === 'qr') {
                if (!qrToken) {
                    Swal.fire('Gagal!', 'QR token wajib untuk mode QR.', 'error');
                    return;
                }
                submitCheckin(null, null, qrToken, photoFile);
                return;
            }

            if (presensiValidationMode === 'manual') {
                submitCheckin(null, null, null, photoFile);
                return;
            }

            if (presensiValidationMode === 'gps_or_qr' && qrToken) {
                submitCheckin(null, null, qrToken, photoFile);
                return;
            }

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var tokenToSend = (presensiValidationMode === 'any') ? (qrToken || null) : null;
                    submitCheckin(position.coords.latitude, position.coords.longitude, tokenToSend, photoFile);
                }, function() {
                    if (presensiValidationMode === 'any') {
                        submitCheckin(null, null, qrToken || null, photoFile);
                        return;
                    }
                    Swal.fire('Error', 'Gagal mendapatkan lokasi. Pastikan GPS aktif.', 'error');
                });
            } else {
                if (presensiValidationMode === 'any') {
                    submitCheckin(null, null, qrToken || null, photoFile);
                    return;
                }
                Swal.fire('Error', 'Browser tidak mendukung geolocation', 'error');
            }
        }
    });
}

function submitCheckin(lat, lng, qrToken, photoFile) {
    var formData = new FormData();
    if (lat !== null && lat !== undefined) {
        formData.append('lat', lat);
    }
    if (lng !== null && lng !== undefined) {
        formData.append('lng', lng);
    }
    if (qrToken) {
        formData.append('qr_token', qrToken);
    }
    if (photoFile) {
        formData.append('photo_file', photoFile);
    }
    formData.append(csrfName, csrfHash);

    $.ajax({
        url: base_url + 'presensi/do_checkin',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                var msg = 'Check-in berhasil';
                if (res.status) {
                    msg += ' (' + res.status;
                    if (res.terlambat_menit && parseInt(res.terlambat_menit, 10) > 0) {
                        msg += ' ' + res.terlambat_menit + ' menit';
                    }
                    msg += ')';
                }
                Swal.fire('Berhasil!', msg, 'success').then(() => {
                    location.reload();
                });
            } else {
                if (res.show_bypass) {
                    Swal.fire({
                        title: 'Gagal!',
                        text: (res.message || 'Presensi gagal.') + ' Ajukan bypass?',
                        icon: 'error',
                        showCancelButton: true,
                        confirmButtonText: 'Ajukan Bypass',
                        cancelButtonText: 'Tutup'
	                    }).then((bypassResult) => {
	                        if (bypassResult.value || bypassResult.isConfirmed) {
	                            window.location.href = base_url + 'tendik/bypass_request?tipe=checkin';
	                        }
	                    });
	                    return;
	                }
                Swal.fire('Gagal!', res.message || 'Presensi gagal.', 'error');
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
        if (result.value || result.isConfirmed) {
            var qrToken = getPresensiQrToken();

            if (presensiValidationMode === 'qr') {
                if (!qrToken) {
                    Swal.fire('Gagal!', 'QR token wajib untuk mode QR.', 'error');
                    return;
                }
                submitCheckout(null, null, qrToken);
                return;
            }

            if (presensiValidationMode === 'manual') {
                submitCheckout(null, null, null);
                return;
            }

            if (presensiValidationMode === 'gps_or_qr' && qrToken) {
                submitCheckout(null, null, qrToken);
                return;
            }

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var tokenToSend = (presensiValidationMode === 'any') ? (qrToken || null) : null;
                    submitCheckout(position.coords.latitude, position.coords.longitude, tokenToSend);
                }, function() {
                    if (presensiValidationMode === 'any') {
                        submitCheckout(null, null, qrToken || null);
                        return;
                    }
                    Swal.fire('Error', 'Gagal mendapatkan lokasi. Pastikan GPS aktif.', 'error');
                });
            } else {
                if (presensiValidationMode === 'any') {
                    submitCheckout(null, null, qrToken || null);
                    return;
                }
                Swal.fire('Error', 'Browser tidak mendukung geolocation', 'error');
            }
        }
    });
}

function submitCheckout(lat, lng, qrToken) {
    var formData = new FormData();
    if (lat !== null && lat !== undefined) {
        formData.append('lat', lat);
    }
    if (lng !== null && lng !== undefined) {
        formData.append('lng', lng);
    }
    if (qrToken) {
        formData.append('qr_token', qrToken);
    }
    formData.append(csrfName, csrfHash);

    $.ajax({
        url: base_url + 'presensi/do_checkout',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                var msg = 'Check-out berhasil';
                if (res.status) {
                    msg += ' (' + res.status + ')';
                }
                Swal.fire('Berhasil!', msg, 'success').then(() => {
                    location.reload();
                });
            } else {
                if (res.show_bypass) {
                    Swal.fire({
                        title: 'Gagal!',
                        text: (res.message || 'Check-out gagal.') + ' Ajukan bypass?',
                        icon: 'error',
                        showCancelButton: true,
                        confirmButtonText: 'Ajukan Bypass',
                        cancelButtonText: 'Tutup'
	                    }).then((bypassResult) => {
	                        if (bypassResult.value || bypassResult.isConfirmed) {
	                            window.location.href = base_url + 'tendik/bypass_request?tipe=checkout';
	                        }
	                    });
	                    return;
	                }
                Swal.fire('Gagal!', res.message || 'Check-out gagal.', 'error');
            }
        },
        error: function() {
            Swal.fire('Error!', 'Terjadi kesalahan server', 'error');
        }
    });
}
</script>
