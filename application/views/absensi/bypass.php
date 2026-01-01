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
                        <li class="breadcrumb-item active">Pengajuan Bypass</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-map-marker-alt"></i> Form Pengajuan Bypass Lokasi</h3>
                        </div>
                        <?= form_open_multipart('absensi/submitBypass', ['id' => 'formBypass']) ?>
                        <div class="card-body">
                            <?php 
                            $max_bypass = $config['max_bypass_per_month'] ?? 5;
                            $remaining = $max_bypass - ($bypass_count ?? 0);
                            ?>
                            
                            <?php if ($remaining <= 0): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                Anda sudah mencapai batas maksimal pengajuan bypass bulan ini (<?= $max_bypass ?>x).
                            </div>
                            <?php else: ?>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Sisa kuota bypass bulan ini: <strong><?= $remaining ?></strong> dari <?= $max_bypass ?>
                            </div>

                            <div class="form-group">
                                <label>Tanggal Bypass <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
                                <small class="text-muted">Pilih tanggal kapan Anda membutuhkan bypass lokasi</small>
                            </div>

                            <div class="form-group">
                                <label>Tipe Bypass <span class="text-danger">*</span></label>
                                <select class="form-control" name="tipe_bypass" required>
                                    <option value="both">Check-in & Check-out</option>
                                    <option value="checkin">Hanya Check-in</option>
                                    <option value="checkout">Hanya Check-out</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Alasan <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="alasan" rows="3" required placeholder="Jelaskan alasan Anda membutuhkan bypass lokasi..."></textarea>
                            </div>

                            <div class="form-group">
                                <label>Lokasi Alternatif</label>
                                <input type="text" class="form-control" name="lokasi_alternatif" placeholder="Contoh: Kantor Cabang A, Rumah Klien, dll">
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Latitude</label>
                                        <input type="text" class="form-control" name="latitude" id="latitude" placeholder="Opsional">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Longitude</label>
                                        <input type="text" class="form-control" name="longitude" id="longitude" placeholder="Opsional">
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-sm btn-info mb-3" id="btnGetLoc">
                                <i class="fas fa-crosshairs"></i> Ambil Lokasi Saat Ini
                            </button>

                            <div class="form-group">
                                <label>Foto Bukti (opsional)</label>
                                <input type="file" class="form-control" name="foto_bukti" accept="image/*">
                                <small class="text-muted">Format: JPG, PNG. Maks 2MB</small>
                            </div>

                            <?php endif; ?>
                        </div>
                        <?php if ($remaining > 0): ?>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Ajukan Bypass
                            </button>
                            <a href="<?= base_url('absensi/checkin') ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                        <?php endif; ?>
                        <?= form_close() ?>
                    </div>
                </div>

                <div class="col-md-6">
                    <!-- Info -->
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-question-circle"></i> Tentang Bypass Lokasi</h3>
                        </div>
                        <div class="card-body">
                            <p>Fitur bypass lokasi memungkinkan Anda untuk absensi di luar radius kantor yang ditentukan.</p>
                            <p><strong>Kapan menggunakan bypass?</strong></p>
                            <ul>
                                <li>Dinas ke lokasi lain (meeting, kunjungan klien)</li>
                                <li>Work from home dengan izin</li>
                                <li>Tugas lapangan</li>
                            </ul>
                            <p><strong>Proses:</strong></p>
                            <ol>
                                <li>Ajukan bypass sebelum tanggal yang dibutuhkan</li>
                                <li>Tunggu persetujuan admin</li>
                                <li>Setelah disetujui, Anda dapat absensi tanpa validasi GPS</li>
                            </ol>
                        </div>
                    </div>

                    <!-- History -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-history"></i> Riwayat Pengajuan</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Tipe</th>
                                            <th>Status</th>
                                            <th>Diajukan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($bypass_history)): ?>
                                            <?php foreach ($bypass_history as $h): ?>
                                            <tr>
                                                <td><?= date('d/m/Y', strtotime($h->tanggal)) ?></td>
                                                <td><span class="badge badge-primary"><?= ucfirst($h->tipe_bypass) ?></span></td>
                                                <td>
                                                    <?php
                                                    $statusBadge = [
                                                        'pending' => 'badge-warning',
                                                        'approved' => 'badge-success',
                                                        'rejected' => 'badge-danger',
                                                        'used' => 'badge-info',
                                                        'expired' => 'badge-secondary'
                                                    ];
                                                    ?>
                                                    <span class="badge <?= $statusBadge[$h->status] ?? 'badge-secondary' ?>">
                                                        <?= ucfirst($h->status) ?>
                                                    </span>
                                                </td>
                                                <td><small><?= date('d/m H:i', strtotime($h->created_at)) ?></small></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3">
                                                    Belum ada riwayat pengajuan
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    $('#btnGetLoc').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Mengambil...');
        
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                $('#latitude').val(pos.coords.latitude.toFixed(8));
                $('#longitude').val(pos.coords.longitude.toFixed(8));
                toastr.success('Lokasi berhasil diambil');
                btn.prop('disabled', false).html('<i class="fas fa-crosshairs"></i> Ambil Lokasi Saat Ini');
            }, function(err) {
                toastr.error('Gagal mengambil lokasi: ' + err.message);
                btn.prop('disabled', false).html('<i class="fas fa-crosshairs"></i> Ambil Lokasi Saat Ini');
            });
        } else {
            toastr.error('Browser tidak mendukung geolocation');
            btn.prop('disabled', false).html('<i class="fas fa-crosshairs"></i> Ambil Lokasi Saat Ini');
        }
    });

    $('#formBypass').on('submit', function(e) {
        e.preventDefault();
        
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Mengirim...');
        
        var formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                if (res.status) {
                    toastr.success(res.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    toastr.error(res.message);
                    btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Ajukan Bypass');
                }
            },
            error: function() {
                toastr.error('Terjadi kesalahan sistem');
                btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Ajukan Bypass');
            }
        });
    });
});
</script>
