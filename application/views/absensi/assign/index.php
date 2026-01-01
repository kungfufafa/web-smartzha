<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $subjudul ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Beranda</a></li>
                        <li class="breadcrumb-item active"><?= $subjudul ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user-clock mr-1"></i>
                                Daftar Guru dan Shift
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tabelGuru" class="table table-bordered table-striped table-hover">
                                    <thead>
                                        <tr class="bg-light">
                                            <th width="5%">No</th>
                                            <th>Nama Guru</th>
                                            <th>NIP</th>
                                            <th>Shift Saat Ini</th>
                                            <th>Jam Kerja</th>
                                            <th>Berlaku Sejak</th>
                                            <th width="15%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; foreach ($guru_list as $guru): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= $guru->nama_guru ?></td>
                                            <td><?= $guru->nip ?: '-' ?></td>
                                            <td>
                                                <?php if ($guru->nama_shift): ?>
                                                    <span class="badge badge-success"><?= $guru->nama_shift ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Belum diatur</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($guru->jam_masuk && $guru->jam_pulang): ?>
                                                    <?= date('H:i', strtotime($guru->jam_masuk)) ?> - <?= date('H:i', strtotime($guru->jam_pulang)) ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= $guru->tgl_efektif ? date('d/m/Y', strtotime($guru->tgl_efektif)) : '-' ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        onclick="editShift(<?= $guru->id_user ?>, '<?= htmlspecialchars($guru->nama_guru, ENT_QUOTES) ?>', <?= $guru->id_shift_fixed ?: 'null' ?>)">
                                                    <i class="fas fa-edit"></i> Atur
                                                </button>
                                                <?php if ($guru->id_shift_fixed): ?>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="hapusShift(<?= $guru->id_user ?>, '<?= htmlspecialchars($guru->nama_guru, ENT_QUOTES) ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Atur Shift -->
<div class="modal fade" id="modalShift" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title"><i class="fas fa-clock mr-2"></i>Atur Shift Guru</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="formShift">
                <div class="modal-body">
                    <input type="hidden" name="id_user" id="id_user">
                    <div class="form-group">
                        <label>Nama Guru</label>
                        <input type="text" id="nama_guru_display" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label>Pilih Shift <span class="text-danger">*</span></label>
                        <select name="id_shift" id="id_shift" class="form-control" required>
                            <option value="">-- Pilih Shift --</option>
                            <?php foreach ($shifts as $s): ?>
                            <option value="<?= $s->id_shift ?>">
                                <?= $s->nama_shift ?> (<?= date('H:i', strtotime($s->jam_masuk)) ?> - <?= date('H:i', strtotime($s->jam_pulang)) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Berlaku Mulai <span class="text-danger">*</span></label>
                        <input type="date" name="tgl_efektif" id="tgl_efektif" class="form-control" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tabelGuru').DataTable({
        "language": {
            "url": "<?= base_url('assets/plugins/datatables/indonesian.json') ?>"
        },
        "order": [[1, 'asc']]
    });
});

function editShift(id_user, nama_guru, current_shift) {
    $('#id_user').val(id_user);
    $('#nama_guru_display').val(nama_guru);
    if (current_shift) {
        $('#id_shift').val(current_shift);
    } else {
        $('#id_shift').val('');
    }
    $('#tgl_efektif').val('<?= date('Y-m-d') ?>');
    $('#modalShift').modal('show');
}

function hapusShift(id_user, nama_guru) {
    Swal.fire({
        title: 'Hapus Shift?',
        text: 'Shift untuk ' + nama_guru + ' akan dihapus',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '<?= base_url('absensimanager/delete_guru_shift') ?>',
                type: 'POST',
                data: {
                    id_user: id_user,
                    '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>'
                },
                dataType: 'json',
                success: function(res) {
                    if (res.status) {
                        Swal.fire('Berhasil!', res.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Gagal!', res.message, 'error');
                    }
                }
            });
        }
    });
}

$('#formShift').submit(function(e) {
    e.preventDefault();
    $.ajax({
        url: '<?= base_url('absensimanager/save_guru_shift') ?>',
        type: 'POST',
        data: $(this).serialize() + '&<?= $this->security->get_csrf_token_name() ?>=<?= $this->security->get_csrf_hash() ?>',
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                $('#modalShift').modal('hide');
                Swal.fire('Berhasil!', res.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Gagal!', res.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error!', 'Terjadi kesalahan server', 'error');
        }
    });
});
</script>
