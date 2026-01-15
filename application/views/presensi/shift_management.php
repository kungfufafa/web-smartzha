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
            <div class="card card-default my-shadow mb-4">
                <div class="card-header">
                    <h3 class="card-title"><?= $subjudul ?></h3>
                    <div class="card-tools">
                        <button type="button" onclick="reload_ajax()" class="btn btn-sm btn-default">
                            <i class="fa fa-sync"></i> <span class="d-none d-sm-inline-block ml-1">Reload</span>
                        </button>
                        <button type="button" data-toggle="modal" data-target="#createShiftModal" class="btn btn-sm btn-primary">
                            <i class="fa fa-plus"></i> <span class="d-none d-sm-inline-block ml-1">Tambah Shift</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" id="bulk_delete">
                            <i class="fa fa-trash"></i> <span class="d-none d-sm-inline-block ml-1">Hapus Terpilih</span>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <?= form_open('presensi/shift_delete', array('id' => 'bulk')) ?>
                        <table id="table-shift" class="table table-striped table-bordered table-hover" style="width: 100%">
                            <thead>
                                <tr>
                                    <th width="30" class="text-center p-0 align-middle">
                                        <div class="icheck-primary d-inline">
                                            <input type="checkbox" id="check_all">
                                            <label for="check_all"></label>
                                        </div>
                                    </th>
                                    <th width="50" class="text-center p-0 align-middle">No.</th>
                                    <th>Nama Shift</th>
                                    <th>Kode</th>
                                    <th>Jam Masuk</th>
                                    <th>Jam Pulang</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                        </table>
                        <?= form_close() ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal -->
<div class="modal fade" id="createShiftModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Tambah Shift</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?= form_open('presensi/shift_save', array('id' => 'formShift')) ?>
            <div class="modal-body">
                <input type="hidden" name="method" id="method" value="add">
                <input type="hidden" name="id_shift" id="id_shift">
                
                <div class="form-group">
                    <label>Nama Shift</label>
                    <input type="text" name="nama_shift" id="nama_shift" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Kode Shift</label>
                    <input type="text" name="kode_shift" id="kode_shift" class="form-control" required>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Jam Masuk</label>
                            <input type="time" name="jam_masuk" id="jam_masuk" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Jam Pulang</label>
                            <input type="time" name="jam_pulang" id="jam_pulang" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Toleransi Masuk (Menit)</label>
                            <input type="number" name="toleransi_masuk_menit" id="toleransi_masuk_menit" class="form-control" value="15">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Toleransi Pulang (Menit)</label>
                            <input type="number" name="toleransi_pulang_menit" id="toleransi_pulang_menit" class="form-control" value="0">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="is_lintas_hari" name="is_lintas_hari" value="1">
                        <label class="custom-control-label" for="is_lintas_hari">Lintas Hari (Pulang besoknya)</label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" checked>
                        <label class="custom-control-label" for="is_active">Aktif</label>
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
    var table;

    $(document).ready(function() {
        ajaxcsrf();

        table = $('#table-shift').DataTable({
            "processing": true,
            "serverSide": true,
            "order": [],
            "ajax": {
                "url": base_url + "presensi/shift_data",
                "type": "POST"
            },
            "columnDefs": [
                { "targets": [0, 1, 7], "orderable": false },
                { "targets": [0, 1, 7], "className": "text-center" }
            ],
            "columns": [
                { 
                    "data": "id_shift",
                    "render": function(data, type, row) {
                        return '<div class="icheck-primary d-inline"><input type="checkbox" class="check-item" name="checked[]" value="'+data+'" id="check'+data+'"><label for="check'+data+'"></label></div>';
                    }
                },
                { "data": null, "render": function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                { "data": "nama_shift" },
                { "data": "kode_shift" },
                { "data": "jam_masuk" },
                { "data": "jam_pulang" },
                { 
                    "data": "is_active",
                    "render": function(data, type, row) {
                        return data == 1 ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-secondary">Non-Aktif</span>';
                    }
                },
                { 
                    "data": "id_shift",
                    "render": function(data, type, row) {
                        var btn = '<button type="button" class="btn btn-xs btn-warning btn-edit" data-id="'+data+'" title="Edit"><i class="fa fa-pencil-alt"></i></button>';
                        return btn;
                    }
                }
            ]
        });

        $('#check_all').on('click', function() {
            if (this.checked) {
                $('.check-item').each(function() {
                    this.checked = true;
                });
            } else {
                $('.check-item').each(function() {
                    this.checked = false;
                });
            }
        });

        $('#createShiftModal').on('hidden.bs.modal', function() {
            $('#formShift')[0].reset();
            $('#method').val('add');
            $('#id_shift').val('');
            $('#createModalLabel').text('Tambah Shift');
            $('#is_active').prop('checked', true);
        });

        $('#table-shift').on('click', '.btn-edit', function() {
            var data = table.row($(this).parents('tr')).data();
            $('#method').val('edit');
            $('#id_shift').val(data.id_shift);
            $('#nama_shift').val(data.nama_shift);
            $('#kode_shift').val(data.kode_shift);
            $('#jam_masuk').val(data.jam_masuk);
            $('#jam_pulang').val(data.jam_pulang);
            $('#toleransi_masuk_menit').val(data.toleransi_masuk_menit);
            $('#toleransi_pulang_menit').val(data.toleransi_pulang_menit);
            $('#is_lintas_hari').prop('checked', data.is_lintas_hari == 1);
            $('#is_active').prop('checked', data.is_active == 1);
            
            $('#createModalLabel').text('Edit Shift');
            $('#createShiftModal').modal('show');
        });

        $('#formShift').on('submit', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'JSON',
                success: function(data) {
                    if (data.status) {
                        $('#createShiftModal').modal('hide');
                        reload_ajax();
                        swal.fire({
                            title: "Berhasil",
                            text: data.msg,
                            icon: "success"
                        });
                    } else {
                        swal.fire({
                            title: "Gagal",
                            text: data.msg,
                            icon: "error"
                        });
                    }
                },
                error: function(xhr, status, error) {
                    swal.fire({
                        title: "Error",
                        text: "Terjadi kesalahan server",
                        icon: "error"
                    });
                }
            });
        });

        $('#bulk_delete').on('click', function() {
            if ($('.check-item:checked').length === 0) {
                swal.fire({
                    title: "Peringatan",
                    text: "Silahkan pilih data yang akan dihapus",
                    icon: "warning"
                });
                return;
            }

            swal.fire({
                title: "Konfirmasi",
                text: "Anda yakin ingin menghapus data ini?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Ya, Hapus!"
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: base_url + "presensi/shift_delete",
                        type: "POST",
                        data: $('#bulk').serialize(),
                        dataType: "JSON",
                        success: function(data) {
                            if (data.status) {
                                reload_ajax();
                                swal.fire({
                                    title: "Berhasil",
                                    text: data.msg,
                                    icon: "success"
                                });
                            } else {
                                swal.fire({
                                    title: "Gagal",
                                    text: data.msg,
                                    icon: "error"
                                });
                            }
                        },
                        error: function() {
                            swal.fire({
                                title: "Error",
                                text: "Terjadi kesalahan server",
                                icon: "error"
                            });
                        }
                    });
                }
            });
        });
    });

    function reload_ajax() {
        table.ajax.reload(null, false);
    }
</script>