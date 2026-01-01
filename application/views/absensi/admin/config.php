<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $judul ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('absensi') ?>">Absensi</a></li>
                        <li class="breadcrumb-item active">Konfigurasi</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <?= form_open('absensi/saveConfig', ['id' => 'formConfig']) ?>
            
            <div class="row">
                <!-- Left Column -->
                <div class="col-md-6">
                    <!-- Metode Absensi -->
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-cog"></i> Metode Absensi</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_gps" name="enable_gps" value="1" <?= !empty($config['enable_gps']) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="enable_gps">
                                        <strong>GPS / Lokasi</strong>
                                        <small class="text-muted d-block">Absensi berdasarkan koordinat GPS</small>
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_qr" name="enable_qr" value="1" <?= !empty($config['enable_qr']) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="enable_qr">
                                        <strong>QR Code</strong>
                                        <small class="text-muted d-block">Absensi dengan scan QR yang di-generate admin</small>
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_manual" name="enable_manual" value="1" <?= !empty($config['enable_manual']) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="enable_manual">
                                        <strong>Input Manual</strong>
                                        <small class="text-muted d-block">Admin dapat menginput absensi secara manual</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pengaturan Foto -->
                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-camera"></i> Pengaturan Foto</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="require_photo_checkin" name="require_photo_checkin" value="1" <?= !empty($config['require_photo_checkin']) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="require_photo_checkin">
                                        Wajib foto saat Check-in
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="require_photo_checkout" name="require_photo_checkout" value="1" <?= !empty($config['require_photo_checkout']) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="require_photo_checkout">
                                        Wajib foto saat Check-out
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pengaturan Bypass -->
                    <div class="card card-warning card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-map-marker-alt"></i> Pengaturan Bypass Lokasi</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="allow_bypass_request" name="allow_bypass_request" value="1" <?= !empty($config['allow_bypass_request']) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="allow_bypass_request">
                                        Izinkan pengajuan bypass lokasi
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="bypass_auto_approve" name="bypass_auto_approve" value="1" <?= !empty($config['bypass_auto_approve']) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="bypass_auto_approve">
                                        Auto-approve bypass (tanpa perlu approval)
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Maksimal Bypass per Bulan</label>
                                <input type="number" class="form-control" name="max_bypass_per_month" value="<?= $config['max_bypass_per_month'] ?? 5 ?>" min="0" max="31">
                                <small class="text-muted">Jumlah maksimal pengajuan bypass per user per bulan</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-md-6">
                    <!-- Pengaturan GPS -->
                    <div class="card card-success card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-map-marked-alt"></i> Pengaturan GPS</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Radius Default (meter)</label>
                                <input type="number" class="form-control" name="default_radius_meter" value="<?= $config['default_radius_meter'] ?? 100 ?>" min="10" max="1000">
                                <small class="text-muted">Radius default untuk lokasi baru</small>
                            </div>
                        </div>
                    </div>

                    <!-- Pengaturan QR Code -->
                    <div class="card card-secondary card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-qrcode"></i> Pengaturan QR Code</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Validitas QR Code (menit)</label>
                                <input type="number" class="form-control" name="qr_validity_minutes" value="<?= $config['qr_validity_minutes'] ?? 5 ?>" min="1" max="60">
                                <small class="text-muted">Berapa lama QR code valid setelah di-generate</small>
                            </div>
                            <div class="form-group">
                                <label>Interval Refresh QR (detik)</label>
                                <input type="number" class="form-control" name="qr_refresh_interval" value="<?= $config['qr_refresh_interval'] ?? 60 ?>" min="10" max="300">
                                <small class="text-muted">Seberapa sering QR code di-refresh otomatis</small>
                            </div>
                        </div>
                    </div>

                    <!-- Pengaturan Keterlambatan -->
                    <div class="card card-danger card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-clock"></i> Pengaturan Keterlambatan</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Batas Terlambat Berat (menit)</label>
                                <input type="number" class="form-control" name="late_threshold_minutes" value="<?= $config['late_threshold_minutes'] ?? 30 ?>" min="1" max="120">
                                <small class="text-muted">Lebih dari ini dianggap "Terlambat Berat"</small>
                            </div>
                        </div>
                    </div>

                    <!-- Hari Kerja -->
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Hari Kerja</h3>
                        </div>
                        <div class="card-body">
                            <?php
                            $working_days = $config['working_days'] ?? [1, 2, 3, 4, 5];
                            if (is_string($working_days)) {
                                $working_days = json_decode($working_days, true) ?: [1, 2, 3, 4, 5];
                            }
                            $days = [
                                1 => 'Senin',
                                2 => 'Selasa',
                                3 => 'Rabu',
                                4 => 'Kamis',
                                5 => 'Jumat',
                                6 => 'Sabtu',
                                7 => 'Minggu'
                            ];
                            ?>
                            <div class="row">
                                <?php foreach ($days as $num => $name): ?>
                                <div class="col-6 col-md-4 mb-2">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="day_<?= $num ?>" name="working_days[]" value="<?= $num ?>" <?= in_array($num, $working_days) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="day_<?= $num ?>"><?= $name ?></label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Timezone -->
                    <div class="card card-dark card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-globe"></i> Zona Waktu</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-0">
                                <select class="form-control" name="timezone">
                                    <?php
                                    $timezones = [
                                        'Asia/Jakarta' => 'WIB (Jakarta)',
                                        'Asia/Makassar' => 'WITA (Makassar)',
                                        'Asia/Jayapura' => 'WIT (Jayapura)'
                                    ];
                                    $current_tz = $config['timezone'] ?? 'Asia/Jakarta';
                                    foreach ($timezones as $tz => $label): ?>
                                    <option value="<?= $tz ?>" <?= $current_tz == $tz ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Simpan Konfigurasi
                            </button>
                            <a href="<?= base_url('absensi') ?>" class="btn btn-secondary btn-lg">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <?= form_close() ?>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    $('#formConfig').on('submit', function(e) {
        e.preventDefault();
        
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status) {
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function() {
                toastr.error('Terjadi kesalahan sistem');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Simpan Konfigurasi');
            }
        });
    });
});
</script>
