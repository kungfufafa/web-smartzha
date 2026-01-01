<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $judul ?></h1>
                </div>
                <div class="col-sm-6">
                    <button type="button" class="btn btn-primary float-right" data-toggle="modal" data-target="#modalKaryawan">
                        <i class="fa fa-plus"></i> Tambah Karyawan
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tableKaryawan" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>NIP</th>
                                    <th>Jabatan</th>
                                    <th>No HP</th>
                                    <th>Akun User</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($karyawan as $k) : ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= $k->nama_karyawan ?></td>
                                    <td><?= $k->nip ?></td>
                                    <td><?= $k->jabatan ?></td>
                                    <td><?= $k->no_hp ?></td>
                                    <td>
                                        <?php if($k->username): ?>
                                            <span class="badge badge-success"><?= $k->username ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Belum Linked</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info btn-shift" data-id="<?= $k->id_user ?>" data-nama="<?= $k->nama_karyawan ?>"><i class="fa fa-clock-o"></i> Shift</button>
                                        <button class="btn btn-sm btn-warning btn-edit" data-id="<?= $k->id_karyawan ?>" data-json='<?= json_encode($k) ?>'><i class="fa fa-edit"></i></button>
                                        <button class="btn btn-sm btn-danger btn-delete" data-id="<?= $k->id_karyawan ?>"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal -->
<div class="modal fade" id="modalKaryawan" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Data Karyawan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?= form_open('absensimanager/save_karyawan', ['id' => 'formKaryawan']) ?>
            <div class="modal-body">
                <input type="hidden" name="id_karyawan" id="id_karyawan">
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Nama Lengkap</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="nama_karyawan" id="nama_karyawan" required>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label">NIP/NIK</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="nip" id="nip">
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Jabatan</label>
                    <div class="col-sm-9">
                        <select class="form-control" name="jabatan" id="jabatan">
                            <option value="Satpam">Satpam</option>
                            <option value="Kebersihan">Kebersihan</option>
                            <option value="Tata Usaha">Tata Usaha</option>
                            <option value="Staf Lain">Staf Lain</option>
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label">No HP</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="no_hp" id="no_hp">
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Alamat</label>
                    <div class="col-sm-9">
                        <textarea class="form-control" name="alamat" id="alamat"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>

<!-- Modal Shift -->
<div class="modal fade" id="modalShift" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Atur Shift Karyawan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?= form_open('absensimanager/save_shift_assignment', ['id' => 'formShift']) ?>
            <div class="modal-body">
                <input type="hidden" name="id_user" id="shift_id_user">
                <div class="form-group">
                    <label>Nama Karyawan</label>
                    <input type="text" class="form-control" id="shift_nama_karyawan" readonly>
                </div>
                <div class="form-group">
                    <label>Pilih Shift (Fixed)</label>
                    <select class="form-control" name="id_shift" required>
                        <option value="">-- Pilih Shift --</option>
                        <?php foreach($shifts as $s): ?>
                            <option value="<?= $s->id_shift ?>"><?= $s->nama_shift ?> (<?= substr($s->jam_masuk,0,5) ?>-<?= substr($s->jam_pulang,0,5) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Berlaku Mulai</label>
                    <input type="date" class="form-control" name="tgl_efektif" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Shift</button>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.btn-shift').on('click', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        
        if(!id) {
            alert('User ID belum terhubung (Belum Linked). Silahkan hubungkan dengan User Akun terlebih dahulu.');
            return;
        }

        $('#shift_id_user').val(id);
        $('#shift_nama_karyawan').val(nama);
        $('#modalShift').modal('show');
    });

    $('#formShift').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if(res.status) {
                    alert(res.message);
                    $('#modalShift').modal('hide');
                } else {
                    alert(res.message);
                }
            }
        });
    });

    $('.btn-edit').on('click', function() {
        var data = $(this).data('json');
        $('#id_karyawan').val(data.id_karyawan);
        $('#nama_karyawan').val(data.nama_karyawan);
        $('#nip').val(data.nip);
        $('#jabatan').val(data.jabatan);
        $('#no_hp').val(data.no_hp);
        $('#alamat').val(data.alamat);
        $('#modalKaryawan').modal('show');
    });

    $('#modalKaryawan').on('hidden.bs.modal', function () {
        $('#formKaryawan')[0].reset();
        $('#id_karyawan').val('');
    });

    $('#formKaryawan').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                location.reload();
            }
        });
    });

    $('.btn-delete').on('click', function() {
        if(confirm('Apakah anda yakin ingin menghapus data ini?')) {
            var id = $(this).data('id');
            // Implement delete if needed
            alert('Fitur hapus belum aktif untuk keamanan data.');
        }
    });
});
</script>
