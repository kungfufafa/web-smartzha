<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
$tipe_default = $tipe_default ?? 'checkin';
if (!in_array($tipe_default, ['checkin', 'checkout', 'both'], true)) {
    $tipe_default = 'checkin';
}

$back_url = base_url('dashboard') . '#presensi';
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper" style="margin-top: -1px;">
    <div class="sticky"></div>
    <section class="content overlap p-4">
        <div class="container">
            <?php $this->load->view('members/siswa/templates/top'); ?>

            <div class="row">
                <div class="col-12">
                    <div class="card card-warning">
                        <div class="card-header">
                            <div class="card-title text-white">
                                <i class="fas fa-exclamation-circle mr-2"></i><?= $subjudul ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>Request bypass digunakan jika presensi gagal divalidasi (mis. GPS/QR).
                            </div>

                            <form id="bypassForm" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label>Tipe Bypass *</label>
                                    <select class="form-control" name="tipe" required>
                                        <option value="checkin" <?= $tipe_default === 'checkin' ? 'selected' : '' ?>>Check-In</option>
                                        <option value="checkout" <?= $tipe_default === 'checkout' ? 'selected' : '' ?>>Check-Out</option>
                                        <option value="both" <?= $tipe_default === 'both' ? 'selected' : '' ?>>Keduanya</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Alasan *</label>
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
                                    <button type="button" class="btn btn-warning" onclick="submitBypassRequest()">
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

document.addEventListener('DOMContentLoaded', function() {
    getLocationForBypass();
});

function submitBypassRequest() {
    var form = document.getElementById('bypassForm');
    if (!form.reportValidity()) {
        return;
    }

    var formData = new FormData(form);
    formData.append(csrfName, csrfHash);

    fetch(base_url + 'presensi/do_bypass_request', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(result) {
        if (result.success) {
            Swal.fire('Berhasil', 'Request bypass berhasil dikirim', 'success').then(function(res) {
                if (res.value || res.isConfirmed) {
                    window.location.href = '<?= $back_url ?>';
                }
            });
            return;
        }

        Swal.fire('Gagal', result.message || 'Gagal mengirim request bypass', 'error');
    })
    .catch(function() {
        Swal.fire('Error', 'Terjadi kesalahan server', 'error');
    });
}
</script>

