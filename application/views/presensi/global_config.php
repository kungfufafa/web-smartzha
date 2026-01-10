<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="content-wrapper bg-white pt-4">
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
	            <div class="card">
	                <div class="card-header">
	                    <h3 class="card-title"><i class="fas fa-sliders-h mr-1"></i> <?= $subjudul ?></h3>
	                </div>
	                <div class="card-body">
                    <?php if (empty($configs)): ?>
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-cog fa-3x mb-3"></i>
                            <h4>Tidak Ada Konfigurasi</h4>
                            <p class="mb-0">Belum ada konfigurasi sistem yang dibuat</p>
                        </div>
                    <?php else: ?>
                        <form id="globalConfigForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="mb-3">Catatan</h5>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Pengaturan Sistem hanya untuk hal sistem yang tidak diatur di bawahnya (QR, Auto-Alpha, Timezone, limit bypass, dll).
                                        <small class="d-block text-muted mt-1">Mode validasi, wajib selfie, wajib pulang, dan izin bypass sekarang diatur di <a href="<?= base_url('presensi/group_config') ?>"><strong>Konfigurasi Group</strong></a>.</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5 class="mb-3">Limit Bypass (Sistem)</h5>
                                    
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
	                                        <small class="text-muted d-block mt-1">Tanpa cronjob: Auto-Alpha dijalankan saat admin membuka menu Presensi setelah jam ini.</small>
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
	                <?php if (!empty($configs)): ?>
	                    <div class="card-footer bg-light text-right">
	                        <button type="button" class="btn btn-primary btn-lg px-5" onclick="saveGlobalConfig()">
	                            <i class="fas fa-save mr-2"></i> Simpan Perubahan
	                        </button>
	                    </div>
	                <?php endif; ?>
	            </div>
	        </div>
	    </section>
</div>

<script>
var csrfName = '<?= $this->security->get_csrf_token_name() ?>';
var csrfHash = '<?= $this->security->get_csrf_hash() ?>';

function appendCsrf(formData) {
    formData.append(csrfName, csrfHash);
    return formData;
}

function saveGlobalConfig() {
    var form = document.getElementById('globalConfigForm');

    if (!form) {
        alert('Tidak ada konfigurasi untuk disimpan');
        return;
    }

    var formData = new FormData(form);

    [
        'bypass_auto_approve',
        'enable_overtime',
        'overtime_require_approval',
        'auto_alpha_enabled'
    ].forEach(function(key) {
        var checkbox = form.querySelector('input[name="config[' + key + ']"]');

        if (checkbox) {
            formData.set('config[' + key + ']', checkbox.checked ? '1' : '0');
        }
    });

    appendCsrf(formData);
    
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
