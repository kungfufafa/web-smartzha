<?php
defined('BASEPATH') or exit('No direct script access allowed');

$back_url = base_url('tendik') . '#presensi';

$tipe_default = $tipe_default ?? 'checkin';
if (!in_array($tipe_default, ['checkin', 'checkout', 'both'], true)) {
    $tipe_default = 'checkin';
}
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper" style="margin-top: -1px;">
    <div class="sticky"></div>
    <section class="content overlap p-4">
        <div class="container">
            <!-- Profile Card -->
            <div class="info-box bg-transparent shadow-none">
                <?php
                $foto = 'assets/adminlte/dist/img/avatar5.png';
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
                                <i class="fas fa-shield-alt mr-2"></i><?= $subjudul ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($this->session->flashdata('success')): ?>
                                <div class="alert alert-success"><?= $this->session->flashdata('success') ?></div>
                            <?php endif; ?>
                            <?php if ($this->session->flashdata('error')): ?>
                                <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
                            <?php endif; ?>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>Request bypass digunakan jika presensi gagal divalidasi (mis. GPS/QR).
                            </div>

                            <form id="bypassForm" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label>Tipe Bypass <span class="text-danger">*</span></label>
                                    <select class="form-control" name="tipe" required>
                                        <option value="checkin" <?= $tipe_default === 'checkin' ? 'selected' : '' ?>>Masuk</option>
                                        <option value="checkout" <?= $tipe_default === 'checkout' ? 'selected' : '' ?>>Pulang</option>
                                        <option value="both" <?= $tipe_default === 'both' ? 'selected' : '' ?>>Keduanya</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Alasan <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="alasan" rows="3" required placeholder="Jelaskan alasan request bypass"></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Lokasi Alternatif</label>
                                    <input type="text" class="form-control" name="lokasi" placeholder="Lokasi saat ini (opsional)">
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Latitude</label>
                                            <input type="text" class="form-control" name="lat" id="bypass-lat" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Longitude</label>
                                            <input type="text" class="form-control" name="lng" id="bypass-lng" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="getLocationForBypass()">
                                        <i class="fas fa-location-arrow mr-1"></i>Ambil Lokasi
                                    </button>
                                </div>

                                <div class="form-group">
                                    <label>Foto Bukti (opsional)</label>
                                    <input type="file" class="form-control" name="photo_file" accept="image/*">
                                    <small class="text-muted">Maks 2MB, format JPG/PNG.</small>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="<?= $back_url ?>" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left mr-1"></i>Kembali
                                    </a>
                                    <button type="button" class="btn btn-primary" onclick="submitBypassRequest()">
                                        <i class="fas fa-paper-plane mr-1"></i>Kirim Request
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
var csrfName = '<?= $this->security->get_csrf_token_name() ?>';
var csrfHash = '<?= $this->security->get_csrf_hash() ?>';
var bypassBackUrl = '<?= $back_url ?>';

function getLocationForBypass() {
    if (!navigator.geolocation) {
        Swal.fire('Error', 'Browser tidak mendukung geolocation', 'error');
        return;
    }

    navigator.geolocation.getCurrentPosition(function(position) {
        document.getElementById('bypass-lat').value = position.coords.latitude;
        document.getElementById('bypass-lng').value = position.coords.longitude;
    }, function() {
        Swal.fire('Error', 'Gagal mendapatkan lokasi. Pastikan GPS aktif.', 'error');
    });
}

$(document).ready(function() {
    getLocationForBypass();
});

function submitBypassRequest() {
    var form = document.getElementById('bypassForm');
    if (!form.reportValidity()) {
        return;
    }

    var formData = new FormData(form);
    formData.append(csrfName, csrfHash);

    $.ajax({
        url: base_url + 'presensi/do_bypass_request',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                Swal.fire('Berhasil', 'Request bypass berhasil dikirim', 'success').then(function(result) {
                    if (result.value || result.isConfirmed) {
                        window.location.href = bypassBackUrl;
                    }
                });
                return;
            }

            Swal.fire('Gagal', res.message || 'Gagal mengirim request bypass', 'error');
        },
        error: function() {
            Swal.fire('Error', 'Terjadi kesalahan server', 'error');
        }
    });
}
</script>

