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
                    <h3 class="card-title"><i class="fas fa-users-cog mr-1"></i> <?= $subjudul ?></h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#groupConfigModal" onclick="clearGroupConfigForm()">
                            <i class="fas fa-plus"></i> Tambah Konfigurasi
                        </button>
                    </div>
                </div>
                <div class="card-body">
		                    <div class="alert alert-info">
		                        <i class="fas fa-info-circle mr-1"></i>
		                        Konfigurasi group presensi hanya untuk <strong>Guru</strong>, <strong>Siswa</strong>, dan <strong>Tendik</strong>.
		                        <small class="d-block text-muted mt-1">Catatan: Satpam termasuk Tendik (tipe tendik), bukan group terpisah. Untuk beda jam kerja antar orang (mis. satpam pagi vs malam), atur di <strong>Jadwal User</strong>. Untuk pola per tipe tendik (opsional), gunakan <strong>Jadwal Tendik (Per Tipe)</strong>. Hari kerja ditentukan di <strong>Jadwal Presensi</strong>; jika tidak ada jadwal maka dianggap <strong>Libur</strong>. Admin/Orangtua tidak perlu jadwal presensi.</small>
		                    </div>
                    <?php if (empty($configs)): ?>
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-users-cog fa-3x mb-3"></i>
                            <h4>Tidak Ada Konfigurasi</h4>
                            <p class="mb-0">Belum ada konfigurasi group yang dibuat</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Group</th>
                                        <th>Nama Konfigurasi</th>
                                        <th>Mode Validasi</th>
                                        <th>Shift Default</th>
                                        <th>Lokasi Default</th>
                                        <th>Require Photo</th>
                                        <th>Holiday Mode</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($configs as $config): ?>
                                    <?php $is_allowed_group = empty($allowed_group_names) ? true : in_array($config->group_name, $allowed_group_names, true); ?>
                                    <tr>
                                        <td>
                                            <strong><?= $config->group_name ?></strong>
                                            <?php if (!$is_allowed_group): ?>
                                                <span class="badge badge-secondary ml-1">Tidak digunakan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $config->nama_konfigurasi ?></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?= $config->validation_mode ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $shift = !empty($shifts) ? array_filter($shifts, function($s) use ($config) { return $s->id_shift == $config->id_shift_default; }) : [];
                                            $default_shift = !empty($shift) ? reset($shift) : null;
                                            echo $default_shift ? $default_shift->nama_shift : '-';
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $lokasi_arr = !empty($lokasi) ? array_filter($lokasi, function($l) use ($config) { return $l->id_lokasi == $config->id_lokasi_default; }) : [];
                                            $default_lokasi = !empty($lokasi_arr) ? reset($lokasi_arr) : null;
                                            echo $default_lokasi ? $default_lokasi->nama_lokasi : '-';
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            if ($config->require_photo === null) {
                                                echo '<span class="badge badge-secondary">Default Sistem</span>';
                                            } elseif ($config->require_photo == 1) {
                                                echo '<span class="badge badge-success">Ya</span>';
                                            } else {
                                                echo '<span class="badge badge-danger">Tidak</span>';
                                            }
                                            ?>
                                        </td>
                                        <td><?= $config->holiday_mode ?></td>
                                        <td>
                                            <?php if ($is_allowed_group): ?>
                                                <button type="button" class="btn btn-sm btn-info" onclick="editGroupConfig(<?= $config->id ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteGroupConfig(<?= $config->id ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Group Config Modal -->
<div class="modal fade" id="groupConfigModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="groupConfigModalTitle">Tambah Konfigurasi Group</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="groupConfigForm">
                    <input type="hidden" name="id" id="group-config-id">
                    
	                    <div class="form-group">
	                        <label>Group *</label>
	                        <select class="form-control" name="id_group" id="group-config-group" required>
	                            <option value="">Pilih Group</option>
	                            <?php if (!empty($groups)): ?>
	                                <?php foreach ($groups as $g): ?>
	                                <option value="<?= $g->id ?>"><?= $g->name ?></option>
	                                <?php endforeach; ?>
	                            <?php endif; ?>
	                        </select>
	                    </div>
                    
                    <div class="form-group">
                        <label>Nama Konfigurasi</label>
                        <input type="text" class="form-control" name="nama_konfigurasi" id="group-config-nama">
                    </div>
                    
                    <div class="form-group">
                        <label>Mode Validasi</label>
                        <select class="form-control" name="validation_mode" id="group-config-validation">
                            <option value="gps">GPS Saja</option>
                            <option value="qr">QR Saja</option>
                            <option value="gps_or_qr">GPS atau QR</option>
                            <option value="manual">Manual</option>
                            <option value="any">Apa saja</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
	                                <label>Shift Default (Opsional)</label>
		                                <select class="form-control" name="id_shift_default" id="group-config-shift">
		                                    <option value="">-- Pilih Shift --</option>
		                                    <?php if (!empty($shifts)): ?>
		                                        <?php foreach ($shifts as $s): ?>
		                                        <option value="<?= $s->id_shift ?>"><?= $s->nama_shift ?> (<?= $s->kode_shift ?>)</option>
		                                        <?php endforeach; ?>
		                                    <?php endif; ?>
		                                </select>
		                                <small class="text-muted">Tidak menentukan hari kerja; hari kerja diatur di menu Jadwal Presensi.</small>
		                            </div>
	                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Lokasi Default</label>
                                <select class="form-control" name="id_lokasi_default" id="group-config-lokasi">
                                    <option value="">-- Pilih Lokasi --</option>
                                    <?php if (!empty($lokasi)): ?>
                                        <?php foreach ($lokasi as $l): ?>
                                        <option value="<?= $l->id_lokasi ?>"><?= $l->nama_lokasi ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Holiday Mode</label>
                                <select class="form-control" name="holiday_mode" id="group-config-holiday">
                                    <option value="all">Semua</option>
                                    <option value="national_only">Hanya Nasional</option>
                                    <option value="none">Tidak Ada</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="follow_academic_calendar" id="group-config-calendar">
                                    Ikuti Kalender Akademik
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Wajib Foto</label>
                                <select class="form-control form-control-sm" name="require_photo" id="group-config-photo">
                                    <option value="1">Ya (Wajib)</option>
                                    <option value="0">Tidak (Opsional)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Wajib Pulang</label>
                                <select class="form-control form-control-sm" name="require_checkout" id="group-config-checkout">
                                    <option value="1">Ya (Wajib)</option>
                                    <option value="0">Tidak (Opsional)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Izinkan Bypass</label>
                                <select class="form-control form-control-sm" name="allow_bypass" id="group-config-bypass">
                                    <option value="1">Ya (Izinkan)</option>
                                    <option value="0">Tidak (Tolak)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveGroupConfig()">Simpan</button>
            </div>
        </div>
    </div>
</div>

<script>
var configsData = <?= json_encode($configs) ?>;
var shiftsData = <?= json_encode($shifts) ?>;
var lokasiData = <?= json_encode($lokasi) ?>;
var csrfName = '<?= $this->security->get_csrf_token_name() ?>';
var csrfHash = '<?= $this->security->get_csrf_hash() ?>';

function appendCsrf(formData) {
    formData.append(csrfName, csrfHash);
    return formData;
}

function clearGroupConfigForm() {
    document.getElementById('group-config-id').value = '';
    document.getElementById('group-config-group').value = '';
    document.getElementById('group-config-nama').value = '';
    document.getElementById('group-config-validation').value = 'gps_or_qr';
    document.getElementById('group-config-shift').value = '';
    document.getElementById('group-config-lokasi').value = '';
    document.getElementById('group-config-holiday').value = 'all';
    document.getElementById('group-config-calendar').checked = false;
    // Default toggles
    document.getElementById('group-config-photo').value = '1';
    document.getElementById('group-config-checkout').value = '1';
    document.getElementById('group-config-bypass').value = '1';
    document.getElementById('groupConfigModalTitle').textContent = 'Tambah Konfigurasi Group';
}

function findConfigById(id) {
    var target = String(id);

    if (!Array.isArray(configsData)) {
        return null;
    }

    for (var i = 0; i < configsData.length; i++) {
        if (String(configsData[i].id) === target) {
            return configsData[i];
        }
    }

    return null;
}

function editGroupConfig(id) {
    var config = findConfigById(id);
    
    if (config) {
        document.getElementById('group-config-id').value = config.id;
        document.getElementById('group-config-group').value = config.id_group;
        document.getElementById('group-config-nama').value = config.nama_konfigurasi;
        document.getElementById('group-config-validation').value = config.validation_mode;
        document.getElementById('group-config-shift').value = config.id_shift_default || '';
        document.getElementById('group-config-lokasi').value = config.id_lokasi_default || '';
        document.getElementById('group-config-holiday').value = config.holiday_mode;
        document.getElementById('group-config-calendar').checked = String(config.follow_academic_calendar) === '1';
        // NULL = default system
        document.getElementById('group-config-photo').value = config.require_photo === null ? '1' : String(config.require_photo);
        document.getElementById('group-config-checkout').value = config.require_checkout === null ? '1' : String(config.require_checkout);
        document.getElementById('group-config-bypass').value = config.allow_bypass === null ? '1' : String(config.allow_bypass);
        document.getElementById('groupConfigModalTitle').textContent = 'Edit Konfigurasi Group';
        
        $('#groupConfigModal').modal('show');
    }
    else {
        alert('Data konfigurasi tidak ditemukan');
    }
}

function saveGroupConfig() {
    var form = document.getElementById('groupConfigForm');
    var formData = appendCsrf(new FormData(form));
    
    fetch('<?= base_url('presensi/save_group_config') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.message);
            $('#groupConfigModal').modal('hide');
            location.reload();
        } else {
            alert('Gagal menyimpan: ' + result.message);
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan: ' + error.message);
    });
}

function deleteGroupConfig(id) {
    if (confirm('Apakah Anda yakin ingin menghapus konfigurasi ini?')) {
        window.location.href = '<?= base_url('presensi/delete_group_config') ?>/' + id;
    }
}
</script>
