<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $judul ?></h1>
                </div>
                <div class="col-sm-6">
                    <button type="button" class="btn btn-primary float-right" data-toggle="modal" data-target="#modalShift">
                        <i class="fa fa-plus"></i> Tambah Shift
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
                        <table id="tableShift" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Shift</th>
                                    <th>Kode</th>
                                    <th>Jam Kerja</th>
                                    <th>Check-in Range</th>
                                    <th>Lintas Hari</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($shifts as $s) : ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= $s->nama_shift ?></td>
                                    <td><?= $s->kode_shift ?></td>
                                    <td><?= substr($s->jam_masuk, 0, 5) ?> - <?= substr($s->jam_pulang, 0, 5) ?></td>
                                    <td><?= substr($s->jam_awal_checkin, 0, 5) ?> - <?= substr($s->jam_akhir_checkin, 0, 5) ?></td>
                                    <td><?= $s->lintas_hari ? '<span class="badge badge-warning">Ya</span>' : '<span class="badge badge-success">Tidak</span>' ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning btn-edit" data-id="<?= $s->id_shift ?>" data-json='<?= json_encode($s) ?>'><i class="fa fa-edit"></i></button>
                                        <button class="btn btn-sm btn-danger btn-delete" data-id="<?= $s->id_shift ?>"><i class="fa fa-trash"></i></button>
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
<div class="modal fade" id="modalShift" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Data Shift</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?= form_open('absensimanager/save_shift', ['id' => 'formShift']) ?>
            <div class="modal-body">
                <input type="hidden" name="id_shift" id="id_shift">
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Nama Shift</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="nama_shift" id="nama_shift" required>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Kode Shift</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="kode_shift" id="kode_shift" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Jam Masuk</label>
                            <input type="time" class="form-control" name="jam_masuk" id="jam_masuk" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Jam Pulang</label>
                            <input type="time" class="form-control" name="jam_pulang" id="jam_pulang" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="lintas_hari" name="lintas_hari">
                        <label class="custom-control-label" for="lintas_hari">Lintas Hari (Shift Malam)</label>
                    </div>
                </div>
                <hr>
                <h6>Batasan Waktu Check-in</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Awal Check-in</label>
                            <input type="time" class="form-control" name="jam_awal_checkin" id="jam_awal_checkin">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Akhir Check-in (Terlambat)</label>
                            <input type="time" class="form-control" name="jam_akhir_checkin" id="jam_akhir_checkin">
                        </div>
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

<script>
$(document).ready(function() {
    $('.btn-edit').on('click', function() {
        var data = $(this).data('json');
        $('#id_shift').val(data.id_shift);
        $('#nama_shift').val(data.nama_shift);
        $('#kode_shift').val(data.kode_shift);
        $('#jam_masuk').val(data.jam_masuk);
        $('#jam_pulang').val(data.jam_pulang);
        $('#jam_awal_checkin').val(data.jam_awal_checkin);
        $('#jam_akhir_checkin').val(data.jam_akhir_checkin);
        $('#lintas_hari').prop('checked', data.lintas_hari == 1);
        $('#modalShift').modal('show');
    });

    $('#modalShift').on('hidden.bs.modal', function () {
        $('#formShift')[0].reset();
        $('#id_shift').val('');
    });

    $('#formShift').on('submit', function(e) {
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
            $.ajax({
                url: '<?= base_url("absensimanager/delete_shift") ?>',
                type: 'POST',
                data: {
                    id_shift: id,
                    '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>'
                },
                success: function(res) {
                    location.reload();
                }
            });
        }
    });
});
</script>
