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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title"><?= $subjudul ?></h6>
                    <button type="button" class="btn btn-sm btn-primary" onclick="saveGlobalConfig()">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($configs)): ?>
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-cog fa-3x mb-3"></i>
                            <h4>Tidak Ada Konfigurasi</h4>
                            <p class="mb-0">Belum ada konfigurasi global yang dibuat</p>
                        </div>
                    <?php else: ?>
                        <form id="globalConfigForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="mb-3">Validasi</h5>
                                    
                                    <div class="form-group">
                                        <label>Mode Validasi Default</label>
                                        <select class="form-control" name="config[validation_mode]">
                                            <option value="gps" <?= isset($configs['validation_mode']) && $configs['validation_mode'] === 'gps' ? 'selected' : '' ?>>GPS Only</option>
                                            <option value="qr" <?= isset($configs['validation_mode']) && $configs['validation_mode'] === 'qr' ? 'selected' : '' ?>>QR Only</option>
                                            <option value="gps_or_qr" <?= isset($configs['validation_mode']) && $configs['validation_mode'] === 'gps_or_qr' ? 'selected' : '' ?>>GPS OR QR</option>
                                            <option value="manual" <?= isset($configs['validation_mode']) && $configs['validation_mode'] === 'manual' ? 'selected' : '' ?>>Manual</option>
                                            <option value="any" <?= isset($configs['validation_mode']) && $configs['validation_mode'] === 'any' ? 'selected' : '' ?>>Any</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="config[require_photo]" <?= isset($configs['require_photo']) && $configs['require_photo'] ? 'checked' : '' ?>>
                                            Wajib Foto Selfie
                                        </label>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="config[require_checkout]" <?= isset($configs['require_checkout']) && $configs['require_checkout'] ? 'checked' : '' ?>>
                                            Wajib Check-Out
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5 class="mb-3">Bypass</h5>
                                    
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="config[allow_bypass]" <?= isset($configs['allow_bypass']) && $configs['allow_bypass'] ? 'checked' : '' ?>>
                                            Izinkan Request Bypass
                                        </label>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Max Bypass per Bulan</label>
                                        <input type="number" class="form-control" name="config[max_bypass_per_month]" value="<?= $configs['max_bypass_per_month'] ?? 3 ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="config[bypass_auto_approve]" <?= isset($configs['bypass_auto_approve']) && $configs['bypass_auto_approve'] ? 'checked' : '' ?>>
                                            Auto-Approve Bypass
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="mb-3">QR Code</h5>
                                    
                                    <div class="form-group">
                                        <label>QR Validity (menit)</label>
                                        <input type="number" class="form-control" name="config[qr_validity_minutes]" value="<?= $configs['qr_validity_minutes'] ?? 5 ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>QR Refresh Interval (detik)</label>
                                        <input type="number" class="form-control" name="config[qr_refresh_interval]" value="<?= $configs['qr_refresh_interval'] ?? 60 ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5 class="mb-3">Lembur</h5>
                                    
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="config[enable_overtime]" <?= isset($configs['enable_overtime']) && $configs['enable_overtime'] ? 'checked' : '' ?>>
                                            Aktifkan Lembur
                                        </label>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="config[overtime_require_approval]" <?= isset($configs['overtime_require_approval']) && $configs['overtime_require_approval'] ? 'checked' : '' ?>>
                                            Lembur Perlu Persetujuan
                                        </label>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Min Overtime (menit)</label>
                                        <input type="number" class="form-control" name="config[min_overtime_minutes]" value="<?= $configs['min_overtime_minutes'] ?? 30 ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="mb-3">Auto-Alpha</h5>
                                    
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="config[auto_alpha_enabled]" <?= isset($configs['auto_alpha_enabled']) && $configs['auto_alpha_enabled'] ? 'checked' : '' ?>>
                                            Aktifkan Auto-Mark Alpha
                                        </label>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Auto-Alpha Time</label>
                                        <input type="time" class="form-control" name="config[auto_alpha_time]" value="<?= $configs['auto_alpha_time'] ?? '23:00' ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5 class="mb-3">System</h5>
                                    
                                    <div class="form-group">
                                        <label>Timezone</label>
                                        <select class="form-control" name="config[timezone]">
                                            <option value="Asia/Jakarta" <?= isset($configs['timezone']) && $configs['timezone'] === 'Asia/Jakarta' ? 'selected' : '' ?>>Asia/Jakarta</option>
                                            <option value="Asia/Makassar" <?= isset($configs['timezone']) && $configs['timezone'] === 'Asia/Makassar' ? 'selected' : '' ?>>Asia/Makassar</option>
                                            <option value="Asia/Singapore" <?= isset($configs['timezone']) && $configs['timezone'] === 'Asia/Singapore' ? 'selected' : '' ?>>Asia/Singapore</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
function saveGlobalConfig() {
    var form = document.getElementById('globalConfigForm');
    var formData = new FormData(form);
    
    fetch('<?= base_url('presensi/save_global_config') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.message);
        } else {
            alert('Gagal menyimpan: ' + result.message);
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan: ' + error.message);
    });
}
</script>
