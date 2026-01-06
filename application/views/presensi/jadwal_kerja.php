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
                    <div>
                        <button type="button" class="btn btn-sm btn-secondary mr-2" onclick="filterByGroup()">
                            <i class="fas fa-filter"></i> Filter Group
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="clearJadwalForm()">
                            <i class="fas fa-plus"></i> Tambah Jadwal
                        </button>
                    </div>
                </div>
                <div class="card-body">
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
                            <p class="mb-0">Belum ada jadwal kerja yang dibuat</p>
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
                                    <?php 
                                    $days = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                                    foreach ($jadwal as $j): ?>
                                    <tr data-group="<?= $j->id_group ?>">
                                        <td><?= $j->group_name ?></td>
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
        </div>
    </section>
</div>

<!-- Jadwal Modal -->
<div class="modal fade" id="jadwalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Tambah Jadwal Kerja</h5>
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

<script>
function clearJadwalForm() {
    document.getElementById('jadwal-group').value = '';
    document.getElementById('jadwal-day').value = '';
    document.getElementById('jadwal-shift').value = '';
    $('#jadwalModal').modal('show');
}

function saveJadwal() {
    var form = document.getElementById('jadwalForm');
    var formData = new FormData(form);
    
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
        
        fetch('<?= base_url('presensi/save_jadwal_kerja') ?>', {
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
</script>
