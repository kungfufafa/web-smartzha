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
                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#shiftModal" onclick="clearShiftForm()">
                        <i class="fas fa-plus"></i> Tambah Shift
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($shifts)): ?>
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-clock fa-3x mb-3"></i>
                            <h4>Tidak Ada Shift</h4>
                            <p class="mb-0">Belum ada shift yang dibuat</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama Shift</th>
                                        <th>Jam Masuk</th>
                                        <th>Jam Pulang</th>
                                        <th>Toleransi (menit)</th>
                                        <th>Lintas Hari</th>
                                        <th>Durasi (menit)</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($shifts as $shift): ?>
                                    <tr>
                                        <td><strong><?= $shift->kode_shift ?></strong></td>
                                        <td><?= $shift->nama_shift ?></td>
                                        <td><?= $shift->jam_masuk ?></td>
                                        <td><?= $shift->jam_pulang ?></td>
                                        <td><?= $shift->toleransi_masuk_menit ?></td>
                                        <td><?= $shift->is_lintas_hari ? 'Ya' : 'Tidak' ?></td>
                                        <td><?= $shift->durasi_kerja_menit ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info" onclick="editShift(<?= $shift->id_shift ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteShift(<?= $shift->id_shift ?>)">
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

<!-- Shift Modal -->
<div class="modal fade" id="shiftModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="shiftModalTitle">Tambah Shift</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="shiftForm">
                    <input type="hidden" name="id_shift" id="shift-id">
                    
                    <div class="form-group">
                        <label>Nama Shift *</label>
                        <input type="text" class="form-control" name="nama_shift" id="shift-nama" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Kode Shift *</label>
                        <input type="text" class="form-control" name="kode_shift" id="shift-kode" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Jam Masuk *</label>
                                <input type="time" class="form-control" name="jam_masuk" id="shift-jam-masuk" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Jam Pulang *</label>
                                <input type="time" class="form-control" name="jam_pulang" id="shift-jam-pulang" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Toleransi Masuk (menit)</label>
                                <input type="number" class="form-control" name="toleransi_masuk_menit" id="shift-toleransi-masuk" value="15">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Toleransi Pulang (menit)</label>
                                <input type="number" class="form-control" name="toleransi_pulang_menit" id="shift-toleransi-pulang" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_lintas_hari" id="shift-lintas-hari">
                            Shift Lintas Hari (melewati tengah malam)
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveShift()">Simpan</button>
            </div>
        </div>
    </div>
</div>

<script>
function clearShiftForm() {
    document.getElementById('shift-id').value = '';
    document.getElementById('shift-nama').value = '';
    document.getElementById('shift-kode').value = '';
    document.getElementById('shift-jam-masuk').value = '';
    document.getElementById('shift-jam-pulang').value = '';
    document.getElementById('shift-toleransi-masuk').value = '15';
    document.getElementById('shift-toleransi-pulang').value = '0';
    document.getElementById('shift-lintas-hari').checked = false;
    document.getElementById('shiftModalTitle').textContent = 'Tambah Shift';
}

function editShift(id) {
    var shift = <?= json_encode($shifts) ?>.find(s => s.id_shift === id);
    
    if (shift) {
        document.getElementById('shift-id').value = shift.id_shift;
        document.getElementById('shift-nama').value = shift.nama_shift;
        document.getElementById('shift-kode').value = shift.kode_shift;
        document.getElementById('shift-jam-masuk').value = shift.jam_masuk;
        document.getElementById('shift-jam-pulang').value = shift.jam_pulang;
        document.getElementById('shift-toleransi-masuk').value = shift.toleransi_masuk_menit;
        document.getElementById('shift-toleransi-pulang').value = shift.toleransi_pulang_menit;
        document.getElementById('shift-lintas-hari').checked = shift.is_lintas_hari === 1;
        document.getElementById('shiftModalTitle').textContent = 'Edit Shift';
        
        $('#shiftModal').modal('show');
    }
}

function saveShift() {
    var form = document.getElementById('shiftForm');
    var formData = new FormData(form);
    
    fetch('<?= base_url('presensi/save_shift') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.message);
            $('#shiftModal').modal('hide');
            location.reload();
        } else {
            alert('Gagal menyimpan: ' + result.message);
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan: ' + error.message);
    });
}

function deleteShift(id) {
    if (confirm('Apakah Anda yakin ingin menghapus shift ini?')) {
        var formData = new FormData();
        formData.append('id_shift', id);
        
        fetch('<?= base_url('presensi/delete_shift') ?>', {
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
</script>
