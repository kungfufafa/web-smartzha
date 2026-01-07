<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="content-wrapper bg-white">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $judul ?></h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-default my-shadow mb-4">
                <div class="card-header">
                    <h6 class="card-title"><?= $subjudul ?></h6>
                </div>
                <div class="card-body">
                    <?php if ($existing_log && $existing_log->jam_masuk): ?>
                        <div class="alert alert-success text-center">
                            <h4><i class="fas fa-check-circle"></i> Sudah Check-In</h4>
                            <p class="mb-0">
                                Waktu Masuk: <strong><?= date('H:i:s', strtotime($existing_log->jam_masuk)) ?></strong><br>
                                Status: <strong class="text-<?= $existing_log->status_kehadiran === 'Hadir' ? 'success' : 'warning' ?>">
                                    <?= $existing_log->status_kehadiran ?>
                                </strong>
                            </p>
                        </div>
                        
	                        <?php if (!$existing_log->jam_pulang): ?>
	                            <div class="text-center mt-4">
	                                <h5>Anda belum Check-Out</h5>
	                                <?php if (in_array($config->validation_mode, ['qr', 'gps_or_qr', 'any'], true)): ?>
	                                    <div class="form-group mt-3 text-left">
	                                        <label>QR Token<?= $config->validation_mode === 'qr' ? ' *' : '' ?></label>
	                                        <input type="text" class="form-control form-control-lg" id="qr-token" placeholder="Masukkan QR token">
	                                    </div>
	                                <?php endif; ?>
	                                <button type="button" class="btn btn-warning btn-lg" onclick="doCheckout()">
	                                    <i class="fas fa-sign-out-alt"></i> Check-Out
	                                </button>
	                            </div>
	                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <h4><i class="fas fa-check-circle"></i> Sudah Check-Out</h4>
                                <p class="mb-0">
                                    Waktu Pulang: <strong><?= date('H:i:s', strtotime($existing_log->jam_pulang)) ?></strong>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (in_array($existing_log->status_kehadiran, ['Izin', 'Sakit', 'Cuti'])): ?>
                            <div class="alert alert-warning text-center mt-3">
                                <strong><?= $existing_log->status_kehadiran ?></strong> untuk hari ini
                            </div>
                        <?php endif; ?>
                        
	                    <?php elseif (!$shift): ?>
	                        <div class="alert alert-warning text-center">
	                            <i class="fas fa-calendar-times fa-3x mb-3"></i>
	                            <h4>Hari Ini Bukan Hari Kerja</h4>
	                            <p class="mb-0">Silakan cek jadwal presensi Anda</p>
	                        </div>
                        
                        <?php if ($config->allow_bypass): ?>
                            <div class="text-center mt-3">
                                <button type="button" class="btn btn-secondary" onclick="showBypassForm()">
                                    <i class="fas fa-exclamation-circle"></i> Request Bypass
                                </button>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="text-center">
                            <?php if ($config->require_photo): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-camera mr-2"></i>Foto selfie wajib untuk check-in.
                                </div>
                                <div class="form-group">
                                    <label>Foto Selfie *</label>
                                    <input type="file" class="form-control form-control-lg" id="photo-file" accept="image/*" capture="user">
                                </div>
                            <?php endif; ?>

                            <?php switch ($config->validation_mode) {
                                case 'gps': ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-map-marker-alt fa-2x"></i>
                                        <p class="mb-0 mt-2">
                                            <strong>Mode Validasi: GPS</strong><br>
                                            Silakan aktifkan GPS dan tekan tombol Check-In
                                        </p>
                                    </div>
                                    <div class="mt-4" id="gps-status">
                                        <button type="button" class="btn btn-primary btn-lg" id="btn-checkin" onclick="getGPSAndCheckIn()">
                                            <i class="fas fa-location-arrow"></i> Ambil Lokasi &amp; Check-In
                                        </button>
                                    </div>
                                    <?php break;
                                    
                                case 'qr': ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-qrcode fa-2x"></i>
                                        <p class="mb-0 mt-2">
                                            <strong>Mode Validasi: QR Code</strong><br>
                                            Scan QR Code atau masukkan token di bawah
                                        </p>
                                    </div>
                                    <div class="mt-4">
                                        <div class="form-group">
                                            <label>QR Token:</label>
                                            <input type="text" class="form-control form-control-lg" id="qr-token" placeholder="Masukkan QR token">
                                        </div>
                                        <button type="button" class="btn btn-primary btn-lg" onclick="checkInWithQR()">
                                            <i class="fas fa-qrcode"></i> Check-In dengan QR
                                        </button>
                                    </div>
                                    <?php break;
                                    
                                case 'gps_or_qr': ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-location-arrow fa-2x"></i>
                                        <p class="mb-0 mt-2">
                                            <strong>Mode Validasi: GPS atau QR Code</strong><br>
                                            Anda bisa menggunakan salah satu metode
                                        </p>
                                    </div>
                                    <div class="row mt-4">
                                        <div class="col-md-6">
                                            <button type="button" class="btn btn-primary btn-lg btn-block" onclick="getGPSAndCheckIn()">
                                                <i class="fas fa-location-arrow"></i> Check-In GPS
                                            </button>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <input type="text" class="form-control form-control-lg" id="qr-token" placeholder="QR Token">
                                            </div>
                                            <button type="button" class="btn btn-success btn-lg btn-block" onclick="checkInWithQR()">
                                                <i class="fas fa-qrcode"></i> Check-In QR
                                            </button>
                                        </div>
                                    </div>
                                    <?php break;
                                    
                                case 'manual': ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-hand-pointer fa-2x"></i>
                                        <p class="mb-0 mt-2">
                                            <strong>Mode Validasi: Manual</strong><br>
                                            Klik tombol Check-In untuk presensi
                                        </p>
                                    </div>
                                    <div class="mt-4">
                                        <button type="button" class="btn btn-primary btn-lg btn-block" onclick="doManualCheckIn()">
                                            <i class="fas fa-check"></i> Check-In Manual
                                        </button>
                                    </div>
                                    <?php break;
                                    
                                case 'any': ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-check-circle fa-2x"></i>
                                        <p class="mb-0 mt-2">
                                            <strong>Mode Validasi: Bebas</strong><br>
                                            Anda bisa menggunakan metode apapun
                                        </p>
                                    </div>
                                    <div class="mt-4">
                                        <div class="form-group">
                                            <input type="text" class="form-control form-control-lg" id="qr-token" placeholder="QR Token (opsional)">
                                        </div>
                                        <button type="button" class="btn btn-primary btn-lg btn-block" onclick="getGPSAndCheckIn()">
                                            <i class="fas fa-location-arrow"></i> Ambil Lokasi &amp; Check-In
                                        </button>
                                    </div>
                                    <?php break;
                            } ?>
                        </div>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                Shift: <strong><?= $shift->nama_shift ?></strong> (<?= $shift->jam_masuk ?> - <?= $shift->jam_pulang ?>)<br>
                                Toleransi: <strong><?= $shift->toleransi_masuk_menit ?> menit</strong>
                            </small>
                        </div>
                        
                        <?php if ($config->allow_bypass): ?>
                            <div class="text-center mt-3">
                                <button type="button" class="btn btn-sm btn-secondary" onclick="showBypassForm()">
                                    <i class="fas fa-exclamation-circle"></i> Request Bypass
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Bypass Form Modal -->
<div class="modal fade" id="bypassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Request Bypass Presensi</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="bypassForm">
                    <div class="form-group">
                        <label>Tipe Bypass *</label>
                        <select class="form-control" name="tipe" required>
                            <option value="checkin">Check-In</option>
                            <option value="checkout">Check-Out</option>
                            <option value="both">Keduanya</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Alasan *</label>
                        <textarea class="form-control" name="alasan" rows="3" required placeholder="Jelaskan kenapa Anda perlu bypass"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Lokasi Alternatif</label>
                        <input type="text" class="form-control" name="lokasi" placeholder="Lokasi saat ini">
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
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="submitBypass()">Kirim Request</button>
            </div>
        </div>
    </div>
</div>

<script>
var currentLat = null;
var currentLng = null;
var validationMode = '<?= $config->validation_mode ?>';
var requirePhoto = <?= (int) $config->require_photo ?>;
var allowBypass = <?= (int) $config->allow_bypass ?>;
var isSiswa = <?= (isset($this->ion_auth) && $this->ion_auth->in_group('siswa')) ? 'true' : 'false' ?>;
var csrfName = '<?= $this->security->get_csrf_token_name() ?>';
var csrfHash = '<?= $this->security->get_csrf_hash() ?>';

function getPhotoFile() {
    var el = document.getElementById('photo-file');
    if (!el || !el.files || !el.files.length) {
        return null;
    }
    return el.files[0];
}

function getGPSAndCheckIn() {
    if (navigator.geolocation) {
        var btn = document.getElementById('btn-checkin');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengambil Lokasi...';
        }
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                currentLat = position.coords.latitude;
                currentLng = position.coords.longitude;
                
                document.getElementById('bypass-lat').value = currentLat;
                document.getElementById('bypass-lng').value = currentLng;
                
                doCheckIn(currentLat, currentLng);
            },
            function(error) {
                alert('Gagal mengambil lokasi GPS: ' + error.message);
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-location-arrow"></i> Ambil Lokasi & Check-In';
                }
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    } else {
        alert('Browser tidak mendukung geolocation');
    }
}

function doCheckIn(lat, lng, qrToken) {
    qrToken = qrToken || null;
    var photoFile = getPhotoFile();

    if (requirePhoto && !photoFile) {
        alert('Foto selfie wajib untuk check-in');
        var btn = document.getElementById('btn-checkin');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-location-arrow"></i> Ambil Lokasi & Check-In';
        }
        return;
    }

    var data = new FormData();
    if (lat !== null && lat !== undefined) {
        data.append('lat', lat);
    }
    if (lng !== null && lng !== undefined) {
        data.append('lng', lng);
    }
    if (qrToken) {
        data.append('qr_token', qrToken);
    }
    if (photoFile) {
        data.append('photo_file', photoFile);
    }
    data.append(csrfName, csrfHash);
    
    fetch('<?= base_url('presensi/do_checkin') ?>', {
        method: 'POST',
        body: data
    })
    .then(function(response) { return response.json(); })
    .then(function(result) {
        if (result.success) {
            alert('Check-In berhasil! Status: ' + result.status);
            location.reload();
        } else {
            alert('Check-In gagal: ' + result.message);
            
            if (result.show_bypass) {
                if (confirm('Gagal validasi. Apakah Anda ingin request bypass?')) {
                    showBypassForm('checkin');
                }
            }
            
            var btn = document.getElementById('btn-checkin');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-location-arrow"></i> Ambil Lokasi & Check-In';
            }
        }
    })
    .catch(function(error) {
        alert('Terjadi kesalahan: ' + error.message);
        
        var btn = document.getElementById('btn-checkin');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-location-arrow"></i> Ambil Lokasi & Check-In';
        }
    });
}

function checkInWithQR() {
    var qrToken = document.getElementById('qr-token').value;
    
        if (!qrToken.trim()) {
            alert('Silakan masukkan QR token');
            return;
        }
    
    doCheckIn(null, null, qrToken);
}

function doManualCheckIn() {
    doCheckIn(null, null, null);
}

function doCheckout() {
    var qrTokenEl = document.getElementById('qr-token');
    var qrToken = qrTokenEl ? (qrTokenEl.value || '').trim() : '';

    if (validationMode === 'qr') {
        if (!qrToken) {
            alert('Silakan masukkan QR token');
            return;
        }
        submitCheckout(null, null, qrToken);
        return;
    }

    if (validationMode === 'manual') {
        submitCheckout(null, null, null);
        return;
    }

    if (validationMode === 'gps_or_qr' && qrToken) {
        submitCheckout(null, null, qrToken);
        return;
    }

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                currentLat = position.coords.latitude;
                currentLng = position.coords.longitude;
                var tokenToSend = (validationMode === 'any') ? (qrToken || null) : null;
                submitCheckout(currentLat, currentLng, tokenToSend);
            },
            function(error) {
                if (validationMode === 'any') {
                    submitCheckout(null, null, qrToken || null);
                    return;
                }
                alert('Gagal mengambil lokasi GPS: ' + error.message);
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
        return;
    }

    if (validationMode === 'any') {
        submitCheckout(null, null, qrToken || null);
        return;
    }

    alert('Browser tidak mendukung geolocation');
}

function submitCheckout(lat, lng, qrToken) {
    qrToken = qrToken || null;
    var data = new FormData();
    if (lat !== null && lat !== undefined) {
        data.append('lat', lat);
    }
    if (lng !== null && lng !== undefined) {
        data.append('lng', lng);
    }
    if (qrToken) {
        data.append('qr_token', qrToken);
    }

    data.append(csrfName, csrfHash);

    fetch('<?= base_url('presensi/do_checkout') ?>', {
        method: 'POST',
        body: data
    })
    .then(function(response) { return response.json(); })
    .then(function(result) {
        if (result.success) {
            alert('Check-Out berhasil!');
            location.reload();
        } else {
            alert('Check-Out gagal: ' + result.message);

            if (result.show_bypass || allowBypass) {
                if (confirm('Gagal validasi. Apakah Anda ingin request bypass?')) {
                    showBypassForm('checkout');
                }
            }
        }
    })
    .catch(function(error) {
        alert('Terjadi kesalahan: ' + error.message);
    });
}

function showBypassForm(tipe) {
    tipe = tipe || 'checkin';

    if (isSiswa) {
        window.location.href = base_url + 'presensi/bypass_request?tipe=' + encodeURIComponent(tipe);
        return;
    }

    $('#bypassModal').modal('show');

    var tipeEl = document.querySelector('#bypassForm select[name="tipe"]');
    if (tipeEl && ['checkin', 'checkout', 'both'].indexOf(tipe) !== -1) {
        tipeEl.value = tipe;
    }
    
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                document.getElementById('bypass-lat').value = position.coords.latitude;
                document.getElementById('bypass-lng').value = position.coords.longitude;
            },
            function(error) {
                console.log('GPS error:', error);
            },
            { enableHighAccuracy: true }
        );
    }
}

function submitBypass() {
    var form = document.getElementById('bypassForm');
    var formData = new FormData(form);
    formData.append(csrfName, csrfHash);
    
    fetch('<?= base_url('presensi/do_bypass_request') ?>', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(result) {
        if (result.success) {
            alert('Bypass request berhasil dikirim');
            $('#bypassModal').modal('hide');
        } else {
            alert('Gagal mengirim bypass request: ' + result.message);
        }
    })
    .catch(function(error) {
        alert('Terjadi kesalahan: ' + error.message);
    });
}
</script>
