<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $judul ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('absensi') ?>">Absensi</a></li>
                        <li class="breadcrumb-item active"><?= $subjudul ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clock mr-1"></i> Daftar Shift</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalShift" onclick="resetForm()">
                            <i class="fas fa-plus"></i> Tambah Shift
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tableShift" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Shift</th>
                                <th>Kode</th>
                                <th>Jam Masuk</th>
                                <th>Jam Pulang</th>
                                <th>Lintas Hari</th>
                                <th>Toleransi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($shifts as $shift): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= $shift->nama_shift ?></td>
                                <td><code><?= $shift->kode_shift ?></code></td>
                                <td><?= $shift->jam_masuk ?></td>
                                <td><?= $shift->jam_pulang ?></td>
                                <td>
                                    <?php if (isset($shift->lintas_hari) && $shift->lintas_hari): ?>
                                    <span class="badge badge-info">Ya</span>
                                    <?php else: ?>
                                    <span class="badge badge-secondary">Tidak</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= isset($shift->toleransi_terlambat) ? $shift->toleransi_terlambat : 0 ?> menit</td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-xs" onclick="editShift(<?= htmlspecialchars(json_encode($shift)) ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-xs" onclick="deleteShift(<?= $shift->id_shift ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Shift -->
<div class="modal fade" id="modalShift" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalShiftTitle">Tambah Shift</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="formShift">
                <div class="modal-body">
                    <input type="hidden" name="id_shift" id="id_shift">
                    <div class="form-group">
                        <label>Nama Shift <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_shift" id="nama_shift" required>
                    </div>
                    <div class="form-group">
                        <label>Kode Shift <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="kode_shift" id="kode_shift" required style="text-transform: uppercase">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Jam Masuk <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" name="jam_masuk" id="jam_masuk" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Jam Pulang <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" name="jam_pulang" id="jam_pulang" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Toleransi Terlambat (menit)</label>
                        <input type="number" class="form-control" name="toleransi_terlambat" id="toleransi_terlambat" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" name="lintas_hari" id="lintas_hari" value="1">
                            <label class="custom-control-label" for="lintas_hari">Shift Lintas Hari (Malam)</label>
                        </div>
                        <small class="text-muted">Centang jika jam pulang melewati tengah malam</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tableShift').DataTable({
        "responsive": true,
        "autoWidth": false
    });

    $('#formShift').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&<?= $this->security->get_csrf_token_name() ?>=<?= $this->security->get_csrf_hash() ?>';
        
        $.ajax({
            url: '<?= base_url("absensi/saveShift") ?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.status) {
                    Swal.fire('Berhasil', res.message, 'success').then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Terjadi kesalahan server', 'error');
            }
        });
    });
});

function resetForm() {
    $('#formShift')[0].reset();
    $('#id_shift').val('');
    $('#modalShiftTitle').text('Tambah Shift');
}

function editShift(data) {
    $('#modalShiftTitle').text('Edit Shift');
    $('#id_shift').val(data.id_shift);
    $('#nama_shift').val(data.nama_shift);
    $('#kode_shift').val(data.kode_shift);
    $('#jam_masuk').val(data.jam_masuk);
    $('#jam_pulang').val(data.jam_pulang);
    $('#toleransi_terlambat').val(data.toleransi_terlambat || 0);
    $('#lintas_hari').prop('checked', data.lintas_hari == 1);
    $('#modalShift').modal('show');
}

function deleteShift(id) {
    Swal.fire({
        title: 'Hapus Shift?',
        text: 'Data yang dihapus tidak dapat dikembalikan',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Hapus',
        cancelButtonText: 'Batal'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: '<?= base_url("absensi/deleteShift") ?>',
                type: 'POST',
                data: {
                    id_shift: id,
                    <?= $this->security->get_csrf_token_name() ?>: '<?= $this->security->get_csrf_hash() ?>'
                },
                dataType: 'json',
                success: function(res) {
                    if (res.status) {
                        Swal.fire('Berhasil', 'Shift berhasil dihapus', 'success').then(function() {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Gagal', res.message || 'Gagal menghapus', 'error');
                    }
                }
            });
        }
    });
}
</script>
