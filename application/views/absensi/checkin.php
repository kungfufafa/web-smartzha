<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="card card-primary card-outline">
  <div class="card-header">
    <h3 class="card-title"><i class="fa fa-clock"></i> Check-in / Check-out Absensi</h3>
  </div>
  <div class="card-body">
    <!-- Status Saat Ini -->
    <div class="alert alert-<?= $log && $log->jam_masuk ? 'success' : ($log && $log->jam_pulang ? 'info' : 'warning') ?>">
      <?php if ($log && $log->jam_masuk && !$log->jam_pulang): ?>
        <i class="fa fa-check-circle"></i> <strong>Anda sudah check-in</strong>
        <div class="mt-2">
          <small class="text-muted">Sejak: <?= date('H:i', strtotime($log->jam_masuk)); ?></small>
          <span class="badge badge-success badge-sm">Hadir</span>
        </div>
      <?php elseif ($log && $log->jam_masuk && $log->jam_pulang): ?>
        <i class="fa fa-sign-out-alt"></i> <strong>Anda sudah check-out</strong>
        <div class="mt-2">
          <small class="text-muted">Pukul: <?= date('H:i', strtotime($log->jam_pulang)); ?></small>
        </div>
      <?php else: ?>
        <i class="fa fa-clock"></i> <strong>Anda belum check-in hari ini</strong>
        <div class="mt-2">
          <small class="text-muted">Waktu: <?= date('H:i'); ?></small>
          <span class="badge badge-secondary badge-sm">Belum Absen</span>
        </div>
      <?php endif; ?>
    </div>

    <!-- Shift Info -->
    <div class="mb-3">
      <div class="info-box bg-light">
        <span class="info-box-icon bg-primary"><i class="fa fa-briefcase"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Shift Hari Ini</span>
          <?php if ($shift): ?>
            <span class="info-box-number"><?= $shift->nama_shift ?></span>
            <span class="text-muted">
              <i class="fa fa-clock"></i> <?= $shift->jam_masuk ?> - <?= $shift->jam_pulang ?>
              <?php if (isset($shift->lintas_hari) && $shift->lintas_hari == 1): ?>
                <span class="badge badge-info badge-sm ml-2">Lintas Hari</span>
              <?php endif; ?>
            </span>
          <?php else: ?>
            <span class="info-box-number text-muted">Tidak ada shift</span>
          <?php endif; ?>
        </div>
      </div>
      
      <?php if (!empty($config->toleransi_terlambat)): ?>
        <div class="alert alert-warning py-2">
          <i class="fa fa-exclamation-circle"></i> Toleransi Terlambat: <strong><?= $config->toleransi_terlambat ?> menit</strong>
        </div>
      <?php endif; ?>
    </div>

    <!-- GPS Location Status -->
    <?php 
    $gps_enabled = !empty($config->enable_gps);
    $qr_enabled = !empty($config->enable_qr);
    $manual_enabled = !empty($config->enable_manual);
    ?>
    <?php if ($gps_enabled): ?>
    <div class="card card-outline card-info mb-3" id="locationCard">
      <div class="card-header py-2">
        <h5 class="card-title mb-0"><i class="fa fa-map-marker-alt"></i> Lokasi Anda</h5>
      </div>
      <div class="card-body py-2">
        <div id="locationStatus">
          <span class="text-muted"><i class="fa fa-spinner fa-spin"></i> Mendeteksi lokasi...</span>
        </div>
        <div id="locationCoords" class="d-none">
          <small class="text-muted">
            Lat: <span id="latDisplay">-</span>, Lng: <span id="lngDisplay">-</span>
          </small>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-2 d-none" id="btnRefreshLocation" onclick="refreshLocation()">
          <i class="fa fa-sync"></i> Refresh Lokasi
        </button>
      </div>
    </div>
    <?php endif; ?>

    <!-- Method Selection (if multiple methods enabled) -->
    <?php 
    $method_count = ($gps_enabled ? 1 : 0) + ($qr_enabled ? 1 : 0) + ($manual_enabled ? 1 : 0);
    ?>
    <?php if ($method_count > 1): ?>
    <div class="form-group mb-3">
      <label><i class="fa fa-cog"></i> Metode Absensi</label>
      <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
        <?php if ($gps_enabled): ?>
        <label class="btn btn-outline-primary active" id="labelGPS">
          <input type="radio" name="absenMethod" id="methodGPS" value="GPS" checked> 
          <i class="fa fa-map-marker-alt"></i> GPS
        </label>
        <?php endif; ?>
        <?php if ($qr_enabled): ?>
        <label class="btn btn-outline-primary <?= !$gps_enabled ? 'active' : '' ?>" id="labelQR">
          <input type="radio" name="absenMethod" id="methodQR" value="QR" <?= !$gps_enabled ? 'checked' : '' ?>> 
          <i class="fa fa-qrcode"></i> QR Code
        </label>
        <?php endif; ?>
        <?php if ($manual_enabled): ?>
        <label class="btn btn-outline-primary <?= !$gps_enabled && !$qr_enabled ? 'active' : '' ?>" id="labelManual">
          <input type="radio" name="absenMethod" id="methodManual" value="Manual" <?= !$gps_enabled && !$qr_enabled ? 'checked' : '' ?>> 
          <i class="fa fa-keyboard"></i> Manual
        </label>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Hidden fields for data -->
    <input type="hidden" id="lat" value="">
    <input type="hidden" id="lng" value="">
    <input type="hidden" id="foto" value="">
    <input type="hidden" id="csrf_token_name" value="<?= $this->security->get_csrf_token_name() ?>">
    <input type="hidden" id="csrf_token_hash" value="<?= $this->security->get_csrf_hash() ?>">

    <!-- Tombol Check-in / Check-out -->
    <div class="row">
      <?php if (!$log || !$log->jam_masuk): ?>
        <div class="col-md-6 mb-2">
          <button type="button" class="btn btn-lg btn-success btn-block" id="btnCheckin" onclick="doCheckin()">
            <i class="fa fa-sign-in-alt fa-lg"></i> Check-in Sekarang
          </button>
        </div>
      <?php elseif (!$log->jam_pulang): ?>
        <div class="col-md-6 mb-2">
          <button type="button" class="btn btn-lg btn-primary btn-block" id="btnCheckout" onclick="doCheckout()">
            <i class="fa fa-sign-out-alt fa-lg"></i> Check-out Sekarang
          </button>
        </div>
      <?php endif; ?>
      
      <div class="col-md-6 mb-2">
        <a href="<?= site_url('absensi/jadwal') ?>" class="btn btn-lg btn-default btn-block">
          <i class="fa fa-calendar"></i> Lihat Jadwal
        </a>
      </div>
      <div class="col-md-12">
        <a href="<?= site_url('absensi/riwayat') ?>" class="btn btn-default btn-block">
          <i class="fa fa-history"></i> Lihat Riwayat
        </a>
      </div>
    </div>
  </div>
</div>

<!-- JavaScript untuk Geolocation & Interaksi -->
<script>
const GPS_ENABLED = <?= $gps_enabled ? 'true' : 'false' ?>;
const QR_ENABLED = <?= $qr_enabled ? 'true' : 'false' ?>;
const MANUAL_ENABLED = <?= $manual_enabled ? 'true' : 'false' ?>;
let currentLat = null;
let currentLng = null;
let locationReady = false;

// Get CSRF token
function getCsrf() {
    return {
        name: document.getElementById('csrf_token_name').value,
        hash: document.getElementById('csrf_token_hash').value
    };
}

// Update CSRF hash after each request
function updateCsrf(newHash) {
    if (newHash) {
        document.getElementById('csrf_token_hash').value = newHash;
    }
}

// Get selected method
function getSelectedMethod() {
    const selected = document.querySelector('input[name="absenMethod"]:checked');
    if (selected) return selected.value;
    // Default based on what's enabled
    if (GPS_ENABLED) return 'GPS';
    if (QR_ENABLED) return 'QR';
    return 'Manual';
}

// Initialize GPS on page load
document.addEventListener('DOMContentLoaded', function() {
    if (GPS_ENABLED) {
        initGeolocation();
    }
});

function initGeolocation() {
    if (!navigator.geolocation) {
        showLocationError('Browser tidak mendukung GPS');
        return;
    }
    
    const statusEl = document.getElementById('locationStatus');
    statusEl.innerHTML = '<span class="text-info"><i class="fa fa-spinner fa-spin"></i> Mendeteksi lokasi...</span>';
    
    navigator.geolocation.getCurrentPosition(
        onLocationSuccess,
        onLocationError,
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000
        }
    );
}

function onLocationSuccess(position) {
    currentLat = position.coords.latitude;
    currentLng = position.coords.longitude;
    locationReady = true;
    
    // Update hidden fields
    document.getElementById('lat').value = currentLat;
    document.getElementById('lng').value = currentLng;
    
    // Update display
    document.getElementById('locationStatus').innerHTML = '<span class="text-success"><i class="fa fa-check-circle"></i> Lokasi terdeteksi</span>';
    document.getElementById('latDisplay').textContent = currentLat.toFixed(6);
    document.getElementById('lngDisplay').textContent = currentLng.toFixed(6);
    document.getElementById('locationCoords').classList.remove('d-none');
    document.getElementById('btnRefreshLocation').classList.remove('d-none');
    
    // Update card color
    document.getElementById('locationCard').classList.remove('card-info', 'card-danger');
    document.getElementById('locationCard').classList.add('card-success');
}

function onLocationError(error) {
    locationReady = false;
    let message = 'Gagal mendapatkan lokasi';
    
    switch(error.code) {
        case error.PERMISSION_DENIED:
            message = 'Izin lokasi ditolak. Aktifkan GPS di pengaturan browser.';
            break;
        case error.POSITION_UNAVAILABLE:
            message = 'Lokasi tidak tersedia';
            break;
        case error.TIMEOUT:
            message = 'Timeout mendeteksi lokasi';
            break;
    }
    
    showLocationError(message);
}

function showLocationError(message) {
    const statusEl = document.getElementById('locationStatus');
    statusEl.innerHTML = '<span class="text-danger"><i class="fa fa-exclamation-triangle"></i> ' + message + '</span>';
    document.getElementById('btnRefreshLocation').classList.remove('d-none');
    
    // Update card color
    const card = document.getElementById('locationCard');
    if (card) {
        card.classList.remove('card-info', 'card-success');
        card.classList.add('card-danger');
    }
}

function refreshLocation() {
    const btn = document.getElementById('btnRefreshLocation');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Memuat...';
    
    initGeolocation();
    
    setTimeout(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-sync"></i> Refresh Lokasi';
    }, 2000);
}

function doCheckin() {
    const method = getSelectedMethod();
    
    // Validate GPS location if GPS method selected
    if (method === 'GPS' && !locationReady) {
        Swal.fire({
            icon: 'warning',
            title: 'Lokasi Belum Terdeteksi',
            text: 'Mohon tunggu hingga lokasi GPS terdeteksi atau pilih metode lain.',
            confirmButtonColor: '#3085d6'
        });
        return;
    }
    
    Swal.fire({
        title: 'Konfirmasi Check-in',
        text: 'Apakah Anda yakin ingin check-in sekarang?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fa fa-check"></i> Ya, Check-in',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            performCheckin(method);
        }
    });
}

function performCheckin(method) {
    const btn = document.getElementById('btnCheckin');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Memproses...';
    
    const csrf = getCsrf();
    const data = {
        method: method,
        lat: currentLat || '',
        lng: currentLng || '',
        foto: document.getElementById('foto').value
    };
    data[csrf.name] = csrf.hash;
    
    $.ajax({
        url: '<?= site_url('absensi/doCheckin') ?>',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (response.csrf_hash) {
                updateCsrf(response.csrf_hash);
            }
            
            if (response.status) {
                Swal.fire({
                    icon: 'success',
                    title: 'Check-in Berhasil!',
                    text: response.message || 'Anda telah berhasil check-in.',
                    timer: 2000,
                    showConfirmButton: false
                }).then(function() {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Check-in',
                    text: response.message || 'Terjadi kesalahan saat check-in.',
                    confirmButtonColor: '#d33'
                });
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        },
        error: function(xhr) {
            let message = 'Terjadi kesalahan koneksi. Silakan coba lagi.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonColor: '#d33'
            });
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });
}

function doCheckout() {
    Swal.fire({
        title: 'Konfirmasi Check-out',
        text: 'Apakah Anda yakin ingin check-out sekarang?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#007bff',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fa fa-sign-out-alt"></i> Ya, Check-out',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            performCheckout();
        }
    });
}

function performCheckout() {
    const btn = document.getElementById('btnCheckout');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Memproses...';
    
    const csrf = getCsrf();
    const data = {
        method: getSelectedMethod(),
        lat: currentLat || '',
        lng: currentLng || '',
        foto: document.getElementById('foto').value
    };
    data[csrf.name] = csrf.hash;
    
    $.ajax({
        url: '<?= site_url('absensi/doCheckout') ?>',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (response.csrf_hash) {
                updateCsrf(response.csrf_hash);
            }
            
            if (response.status) {
                Swal.fire({
                    icon: 'success',
                    title: 'Check-out Berhasil!',
                    text: response.message || 'Anda telah berhasil check-out.',
                    timer: 2000,
                    showConfirmButton: false
                }).then(function() {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Check-out',
                    text: response.message || 'Terjadi kesalahan saat check-out.',
                    confirmButtonColor: '#d33'
                });
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        },
        error: function(xhr) {
            let message = 'Terjadi kesalahan koneksi. Silakan coba lagi.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonColor: '#d33'
            });
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });
}
</script>
