<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$days = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
?>

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
                    <h3 class="card-title"><i class="fas fa-calendar-alt mr-1"></i> <?= $subjudul ?></h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-secondary btn-sm mr-2" onclick="filterByGroup()">
                            <i class="fas fa-filter"></i> Filter Group
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" onclick="clearJadwalForm()">
                            <i class="fas fa-plus"></i> Tambah Jadwal
                        </button>
                    </div>
                </div>
                <div class="card-body">
	                    <div class="alert alert-info">
	                        <i class="fas fa-info-circle mr-1"></i>
	                        Jadwal presensi group dipakai untuk <strong>Guru</strong>, <strong>Siswa</strong>, dan <strong>Tendik (default)</strong>.
	                        <small class="d-block text-muted mt-1">Untuk beda jam kerja antar tipe tendik (Satpam, Penjaga, Kebersihan, dll), gunakan <strong>Jadwal Tendik (Per Tipe)</strong> di bawah. Admin/Orangtua tidak perlu jadwal presensi.</small>
	                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Filter Group:</label>
                            <select class="form-control" id="filter-group" onchange="filterJadwal()">
                                <option value="">Semua Group</option>
                                <?php if (!empty($groups)): ?>
                                    <?php foreach ($groups as $g): ?>
                                    <option value="<?= $g->id ?>"><?= $g->name ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
	                    <?php if (empty($jadwal)): ?>
	                        <div class="alert alert-warning text-center">
	                            <i class="fas fa-calendar-alt fa-3x mb-3"></i>
	                            <h4>Tidak Ada Jadwal</h4>
	                            <p class="mb-0">Belum ada jadwal presensi yang dibuat</p>
	                        </div>
	                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Group</th>
                                        <th>Hari</th>
                                        <th>Shift</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="jadwal-tbody">
                                    <?php foreach ($jadwal as $j): ?>
                                    <?php $is_allowed_group = empty($allowed_group_names) ? true : in_array($j->group_name, $allowed_group_names, true); ?>
                                    <tr data-group="<?= $j->id_group ?>">
                                        <td>
                                            <?= $j->group_name ?>
                                            <?php if (!$is_allowed_group): ?>
                                                <span class="badge badge-secondary ml-1">Tidak digunakan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $days[$j->day_of_week] ?></td>
                                        <td>
                                            <strong><?= $j->nama_shift ?></strong>
                                            <br>
                                            <small class="text-muted">(<?= $j->jam_masuk ?> - <?= $j->jam_pulang ?>)</small>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= $j->is_active ? 'success' : 'secondary' ?>">
                                                <?= $j->is_active ? 'Aktif' : 'Non-Aktif' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteJadwal('<?= $j->id ?>', '<?= $j->id_group ?>', <?= $j->day_of_week ?>)">
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

            <div class="card mt-3" id="jadwal-tendik">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-shield mr-1"></i> Jadwal Tendik (Per Tipe)</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-secondary btn-sm mr-2" onclick="filterByTipeTendik()" <?= !empty($has_jadwal_tendik_table) ? '' : 'disabled' ?>>
                            <i class="fas fa-filter"></i> Filter Tipe
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" onclick="clearJadwalTendikForm()" <?= !empty($has_jadwal_tendik_table) ? '' : 'disabled' ?>>
                            <i class="fas fa-plus"></i> Tambah Jadwal
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($has_jadwal_tendik_table)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Fitur jadwal tendik per tipe belum aktif karena tabel <code>presensi_jadwal_tendik</code> belum ada.
                            <small class="d-block text-muted mt-1">Jalankan SQL update di <code>assets/app/db/presensi.sql</code> (bagian tabel presensi_jadwal_tendik), lalu reload halaman ini.</small>
                        </div>
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Filter Tipe Tendik:</label>
                            <select class="form-control" id="filter-tipe-tendik" onchange="filterJadwalTendik()" <?= !empty($has_jadwal_tendik_table) ? '' : 'disabled' ?>>
                                <option value="">Semua Tipe</option>
                                <?php if (!empty($tipe_tendik_list)): ?>
                                    <?php foreach ($tipe_tendik_list as $tipe): ?>
                                    <option value="<?= $tipe ?>"><?= $tipe ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted">Contoh: SATPAM, TU, PENJAGA, KEBERSIHAN, dll.</small>
                        </div>
                    </div>

	                    <?php if (empty($jadwal_tendik)): ?>
	                        <div class="alert alert-warning text-center">
	                            <i class="fas fa-user-clock fa-3x mb-3"></i>
	                            <h4>Tidak Ada Jadwal Tendik</h4>
	                            <p class="mb-0">Belum ada jadwal presensi per tipe tendik yang dibuat</p>
	                        </div>
	                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Tipe Tendik</th>
                                        <th>Hari</th>
                                        <th>Shift</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="jadwal-tendik-tbody">
                                    <?php foreach ($jadwal_tendik as $jt): ?>
                                    <tr data-tipe="<?= $jt->tipe_tendik ?>">
                                        <td><strong><?= $jt->tipe_tendik ?></strong></td>
                                        <td><?= $days[(int) $jt->day_of_week] ?? '-' ?></td>
                                        <td>
                                            <strong><?= $jt->nama_shift ?></strong>
                                            <br>
                                            <small class="text-muted">(<?= $jt->jam_masuk ?> - <?= $jt->jam_pulang ?>)</small>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= $jt->is_active ? 'success' : 'secondary' ?>">
                                                <?= $jt->is_active ? 'Aktif' : 'Non-Aktif' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteJadwalTendik('<?= $jt->tipe_tendik ?>', <?= (int) $jt->day_of_week ?>)" <?= !empty($has_jadwal_tendik_table) ? '' : 'disabled' ?>>
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

            <div class="card mt-3" id="jadwal-user">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-cog mr-1"></i> Jadwal User (Override Mingguan)</h3>
                </div>
                <div class="card-body">
	                    <div class="alert alert-info">
	                        <i class="fas fa-info-circle mr-1"></i>
	                        Gunakan ini untuk kasus khusus per orang: <strong>Satpam Pagi vs Satpam Malam</strong>, <strong>Guru Panggilan</strong>, <strong>Siswa Sesi 1/2</strong>, dll.
	                        <small class="d-block text-muted mt-1">Prioritas jadwal: Override Tanggal → Jadwal User → Jadwal Tendik (Per Tipe) → Jadwal Presensi. Jika tidak ada jadwal: Libur.</small>
	                    </div>

                    <?php if (empty($has_jadwal_user_table)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Fitur jadwal user belum aktif karena tabel <code>presensi_jadwal_user</code> belum ada.
                            <small class="d-block text-muted mt-1">Jalankan SQL update di <code>assets/app/db/presensi.sql</code> (bagian tabel presensi_jadwal_user), lalu reload halaman ini.</small>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Filter Group</label>
                                <select class="form-control" id="jadwal-user-filter-group" <?= !empty($has_jadwal_user_table) ? '' : 'disabled' ?>>
                                    <option value="">Semua</option>
                                    <option value="guru">Guru</option>
                                    <option value="siswa">Siswa</option>
                                    <option value="tendik">Tendik</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Pilih User</label>
                                <select id="jadwal-user-select" class="form-control" style="width: 100%;" <?= !empty($has_jadwal_user_table) ? '' : 'disabled' ?>></select>
                                <small class="text-muted">Ketik nama/username untuk mencari.</small>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="jadwal-user-table">
                            <thead>
                                <tr>
                                    <th style="width: 120px;">Hari</th>
                                    <th>Override</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($d = 1; $d <= 7; $d++): ?>
                                <tr data-day="<?= $d ?>">
                                    <td><strong><?= $days[$d] ?></strong></td>
                                    <td>
                                        <select class="form-control form-control-sm jadwal-user-shift" data-day="<?= $d ?>" disabled>
                                            <option value="">Inherit (ikut jadwal default)</option>
                                            <option value="OFF">Libur (override)</option>
                                            <?php if (!empty($shifts)): ?>
                                                <?php foreach ($shifts as $s): ?>
                                                <option value="<?= $s->id_shift ?>"><?= $s->nama_shift ?> (<?= $s->jam_masuk ?> - <?= $s->jam_pulang ?>)</option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </td>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex">
                        <button type="button" class="btn btn-primary mr-2" id="btn-save-jadwal-user" onclick="saveJadwalUserWeekly()" disabled>
                            <i class="fas fa-save mr-1"></i> Simpan Jadwal User
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="btn-clear-jadwal-user" onclick="clearJadwalUserWeekly()" disabled>
                            <i class="fas fa-undo mr-1"></i> Reset Override
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Jadwal Modal -->
<div class="modal fade" id="jadwalModal" tabindex="-1">
    <div class="modal-dialog">
	        <div class="modal-content">
	            <div class="modal-header bg-primary text-white">
	                <h5 class="modal-title">Tambah Jadwal Presensi</h5>
	                <button type="button" class="close" data-dismiss="modal">&times;</button>
	            </div>
            <div class="modal-body">
                <form id="jadwalForm">
                    <div class="form-group">
                        <label>Group *</label>
                        <select class="form-control" name="id_group" id="jadwal-group" required>
                            <option value="">Pilih Group</option>
                            <?php if (!empty($groups)): ?>
                                <?php foreach ($groups as $g): ?>
                                <option value="<?= $g->id ?>"><?= $g->name ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Hari *</label>
                        <select class="form-control" name="day_of_week" id="jadwal-day" required>
                            <option value="1">Senin</option>
                            <option value="2">Selasa</option>
                            <option value="3">Rabu</option>
                            <option value="4">Kamis</option>
                            <option value="5">Jumat</option>
                            <option value="6">Sabtu</option>
                            <option value="7">Minggu</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Shift *</label>
                        <select class="form-control" name="id_shift" id="jadwal-shift" required>
                            <option value="">-- Pilih Shift --</option>
                            <?php if (!empty($shifts)): ?>
                                <?php foreach ($shifts as $s): ?>
                                <option value="<?= $s->id_shift ?>"><?= $s->nama_shift ?> (<?= $s->jam_masuk ?> - <?= $s->jam_pulang ?>)</option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveJadwal()">Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Jadwal Tendik Modal -->
<div class="modal fade" id="jadwalTendikModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Tambah Jadwal Tendik</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="jadwalTendikForm">
                    <div class="form-group">
                        <label>Tipe Tendik *</label>
                        <select class="form-control" name="tipe_tendik" id="jadwal-tendik-tipe" required>
                            <option value="">Pilih Tipe</option>
                            <?php if (!empty($tipe_tendik_list)): ?>
                                <?php foreach ($tipe_tendik_list as $tipe): ?>
                                <option value="<?= $tipe ?>"><?= $tipe ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Hari *</label>
                        <select class="form-control" name="day_of_week" id="jadwal-tendik-day" required>
                            <option value="1">Senin</option>
                            <option value="2">Selasa</option>
                            <option value="3">Rabu</option>
                            <option value="4">Kamis</option>
                            <option value="5">Jumat</option>
                            <option value="6">Sabtu</option>
                            <option value="7">Minggu</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Shift *</label>
                        <select class="form-control" name="id_shift" id="jadwal-tendik-shift" required>
                            <option value="">-- Pilih Shift --</option>
                            <?php if (!empty($shifts)): ?>
                                <?php foreach ($shifts as $s): ?>
                                <option value="<?= $s->id_shift ?>"><?= $s->nama_shift ?> (<?= $s->jam_masuk ?> - <?= $s->jam_pulang ?>)</option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveJadwalTendik()">Simpan</button>
            </div>
        </div>
    </div>
</div>

<script>
var csrfName = '<?= $this->security->get_csrf_token_name() ?>';
var csrfHash = '<?= $this->security->get_csrf_hash() ?>';
var hasJadwalUserTable = <?= !empty($has_jadwal_user_table) ? 'true' : 'false' ?>;

function appendCsrf(formData) {
    formData.append(csrfName, csrfHash);
    return formData;
}

function clearJadwalForm() {
    document.getElementById('jadwal-group').value = '';
    document.getElementById('jadwal-day').value = '';
    document.getElementById('jadwal-shift').value = '';
    $('#jadwalModal').modal('show');
}

function saveJadwal() {
    var form = document.getElementById('jadwalForm');
    var formData = appendCsrf(new FormData(form));
    
    fetch('<?= base_url('presensi/save_jadwal_kerja') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.message);
            $('#jadwalModal').modal('hide');
            location.reload();
        } else {
            alert('Gagal menyimpan: ' + result.message);
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan: ' + error.message);
    });
}

function deleteJadwal(id, groupId, dayOfWeek) {
    if (confirm('Apakah Anda yakin ingin menghapus jadwal ini?')) {
        var formData = new FormData();
        formData.append('id_group', groupId);
        formData.append('day_of_week', dayOfWeek);
        appendCsrf(formData);
        
        fetch('<?= base_url('presensi/delete_jadwal_kerja') ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert(result.message);
                location.reload();
            } else {
                alert('Gagal menghapus: ' + result.message);
            }
        })
        .catch(error => {
            alert('Terjadi kesalahan: ' + error.message);
        });
    }
}

function filterJadwal() {
    var groupFilter = document.getElementById('filter-group').value;
    var rows = document.querySelectorAll('#jadwal-tbody tr');
    
    rows.forEach(row => {
        if (groupFilter === '' || row.dataset.group === groupFilter) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function filterByGroup() {
    var groupFilter = document.getElementById('filter-group').value;
    
    if (groupFilter !== '') {
        clearJadwalForm();
        document.getElementById('jadwal-group').value = groupFilter;
    }
}

function clearJadwalTendikForm() {
    document.getElementById('jadwal-tendik-tipe').value = '';
    document.getElementById('jadwal-tendik-day').value = '';
    document.getElementById('jadwal-tendik-shift').value = '';
    $('#jadwalTendikModal').modal('show');
}

function saveJadwalTendik() {
    var form = document.getElementById('jadwalTendikForm');
    var formData = appendCsrf(new FormData(form));

    fetch('<?= base_url('presensi/save_jadwal_tendik') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.message);
            $('#jadwalTendikModal').modal('hide');
            location.reload();
        } else {
            alert('Gagal menyimpan: ' + result.message);
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan: ' + error.message);
    });
}

function deleteJadwalTendik(tipeTendik, dayOfWeek) {
    if (confirm('Apakah Anda yakin ingin menghapus jadwal ini?')) {
        var formData = new FormData();
        formData.append('tipe_tendik', tipeTendik);
        formData.append('day_of_week', dayOfWeek);
        appendCsrf(formData);

        fetch('<?= base_url('presensi/delete_jadwal_tendik') ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert(result.message);
                location.reload();
            } else {
                alert('Gagal menghapus: ' + result.message);
            }
        })
        .catch(error => {
            alert('Terjadi kesalahan: ' + error.message);
        });
    }
}

function filterJadwalTendik() {
    var tipeFilter = document.getElementById('filter-tipe-tendik').value;
    var rows = document.querySelectorAll('#jadwal-tendik-tbody tr');

    rows.forEach(row => {
        if (tipeFilter === '' || row.dataset.tipe === tipeFilter) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function filterByTipeTendik() {
    var tipeFilter = document.getElementById('filter-tipe-tendik').value;
    clearJadwalTendikForm();

    if (tipeFilter !== '') {
        document.getElementById('jadwal-tendik-tipe').value = tipeFilter;
    }
}

function setJadwalUserEnabled(enabled) {
    document.querySelectorAll('.jadwal-user-shift').forEach(function(el) {
        el.disabled = !enabled;
    });
    document.getElementById('btn-save-jadwal-user').disabled = !enabled;
    document.getElementById('btn-clear-jadwal-user').disabled = !enabled;
}

function resetJadwalUserForm() {
    document.querySelectorAll('.jadwal-user-shift').forEach(function(el) {
        el.value = '';
    });
}

function loadJadwalUser(idUser) {
    if (!hasJadwalUserTable) {
        return;
    }

    resetJadwalUserForm();
    setJadwalUserEnabled(false);

    fetch(base_url + 'presensi/get_jadwal_user?id_user=' + encodeURIComponent(idUser))
    .then(response => response.json())
    .then(result => {
        if (!result.success) {
            alert(result.message || 'Gagal mengambil jadwal user');
            return;
        }

        resetJadwalUserForm();
        (result.overrides || []).forEach(function(item) {
            var day = String(item.day_of_week);
            var el = document.querySelector('.jadwal-user-shift[data-day="' + day + '"]');
            if (!el) {
                return;
            }

            if (item.id_shift === null) {
                el.value = 'OFF';
            } else {
                el.value = String(item.id_shift);
            }
        });

        setJadwalUserEnabled(true);
    })
    .catch(error => {
        alert('Terjadi kesalahan: ' + error.message);
    });
}

function saveJadwalUserWeekly() {
    if (!hasJadwalUserTable) {
        return;
    }

    var idUser = document.getElementById('jadwal-user-select').value;
    if (!idUser) {
        alert('Silakan pilih user terlebih dahulu');
        return;
    }

    var formData = appendCsrf(new FormData());
    formData.append('id_user', idUser);

    document.querySelectorAll('.jadwal-user-shift').forEach(function(el) {
        var day = el.getAttribute('data-day');
        formData.append('schedule[' + day + ']', el.value);
    });

    fetch(base_url + 'presensi/save_jadwal_user_weekly', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.message);
            loadJadwalUser(idUser);
            return;
        }

        alert('Gagal menyimpan: ' + (result.message || 'Unknown error'));
    })
    .catch(error => {
        alert('Terjadi kesalahan: ' + error.message);
    });
}

function clearJadwalUserWeekly() {
    if (!hasJadwalUserTable) {
        return;
    }

    var idUser = document.getElementById('jadwal-user-select').value;
    if (!idUser) {
        alert('Silakan pilih user terlebih dahulu');
        return;
    }

    if (!confirm('Reset semua override jadwal untuk user ini?')) {
        return;
    }

    var formData = appendCsrf(new FormData());
    formData.append('id_user', idUser);

    fetch(base_url + 'presensi/clear_jadwal_user_weekly', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.message);
            resetJadwalUserForm();
            return;
        }

        alert('Gagal reset: ' + (result.message || 'Unknown error'));
    })
    .catch(error => {
        alert('Terjadi kesalahan: ' + error.message);
    });
}

if (hasJadwalUserTable) {
    $(document).ready(function() {
        $('#jadwal-user-select').select2({
            theme: 'bootstrap4',
            placeholder: 'Cari user...',
            allowClear: true,
            ajax: {
                url: base_url + 'presensi/search_users',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term || '',
                        group: $('#jadwal-user-filter-group').val() || ''
                    };
                },
                processResults: function(data) {
                    return {
                        results: (data && data.results) ? data.results : []
                    };
                },
                cache: true
            }
        });

        $('#jadwal-user-select').on('select2:select', function(e) {
            var idUser = e.params.data && e.params.data.id ? e.params.data.id : null;
            if (idUser) {
                loadJadwalUser(idUser);
            }
        });

        $('#jadwal-user-select').on('select2:clear', function() {
            resetJadwalUserForm();
            setJadwalUserEnabled(false);
        });

        $('#jadwal-user-filter-group').on('change', function() {
            $('#jadwal-user-select').val(null).trigger('change');
            resetJadwalUserForm();
            setJadwalUserEnabled(false);
        });
    });
}
</script>
