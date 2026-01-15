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
                        <button type="button" data-toggle="modal" data-target="#createLiburModal" class="btn btn-sm btn-primary">
                            <i class="fa fa-plus"></i> <span class="d-none d-sm-inline-block ml-1">Tambah Libur</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" id="bulk_delete">
                            <i class="fa fa-trash"></i> <span class="d-none d-sm-inline-block ml-1">Hapus Terpilih</span>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!$has_table): ?>
                    <div class="alert alert-warning">
                        <i class="icon fas fa-exclamation-triangle"></i> Tabel 'presensi_hari_libur' belum tersedia. Silahkan jalankan update database.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <?= form_open('', array('id' => 'bulk')) ?>
                        <table id="table-libur" class="table table-striped table-bordered table-hover" style="width: 100%">
                            <thead>
                                <tr>
                                    <th width="30" class="text-center p-0 align-middle">
                                        <div class="icheck-primary d-inline">
                                            <input type="checkbox" id="check_all">
                                            <label for="check_all"></label>
                                        </div>
                                    </th>
                                    <th width="50" class="text-center p-0 align-middle">No.</th>
                                    <th>Tanggal</th>
                                    <th>Nama Libur</th>
                                    <th>Tipe</th>
                                    <th>Berulang</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                        </table>
                        <?= form_close() ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal -->
<div class="modal fade" id="createLiburModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Tambah Hari Libur</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?= form_open('presensi/hari_libur_save', array('id' => 'formLibur')) ?>
            <div class="modal-body">
                <input type="hidden" name="method" id="method" value="add">
                <input type="hidden" name="id_libur" id="id_libur">
                
                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" id="tanggal" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Nama Libur</label>
                    <input type="text" name="nama_libur" id="nama_libur" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Tipe Libur</label>
                    <select name="tipe_libur" id="tipe_libur" class="form-control">
                        <option value="NASIONAL">NASIONAL</option>
                        <option value="AKADEMIK">AKADEMIK</option>
                        <option value="KANTOR">KANTOR</option>
                    </select>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="is_recurring" name="is_recurring" value="1">
                        <label class="custom-control-label" for="is_recurring">Berulang Setiap Tahun</label>
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

        table = $('#table-libur').DataTable({
            "processing": true,
            "serverSide": true,
            "order": [],
            "ajax": {
                "url": base_url + "presensi/hari_libur_data",
                "type": "POST"
            },
            "columnDefs": [
                { "targets": [0, 1, 7], "orderable": false },
                { "targets": [0, 1, 7], "className": "text-center" }
            ],
            "columns": [
                { 
                    "data": "id_libur",
                    "render": function(data, type, row) {
                        return '<div class="icheck-primary d-inline"><input type="checkbox" class="check-item" name="checked[]" value="'+data+'" id="check'+data+'"><label for="check'+data+'"></label></div>';
                    }
                },
                { "data": null, "render": function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                { 
                    "data": "tanggal",
                    "render": function(data) {
                        // Simple date formatting
                        var date = new Date(data);
                        return date.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                    }
                },
                { "data": "nama_libur" },
                { 
                    "data": "tipe_libur",
                    "render": function(data) {
                        var cls = data == 'NASIONAL' ? 'danger' : (data == 'AKADEMIK' ? 'info' : 'warning');
                        return '<span class="badge badge-'+cls+'">'+data+'</span>';
                    }
                },
                { 
                    "data": "is_recurring",
                    "render": function(data) {
                        return data == 1 ? '<i class="fa fa-check text-success"></i>' : '-';
                    }
                },
                { 
                    "data": "is_active",
                    "render": function(data, type, row) {
                        return data == 1 ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-secondary">Non-Aktif</span>';
                    }
                },
                { 
                    "data": "id_libur",
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

        $('#createLiburModal').on('hidden.bs.modal', function() {
            $('#formLibur')[0].reset();
            $('#method').val('add');
            $('#id_libur').val('');
            $('#createModalLabel').text('Tambah Hari Libur');
            $('#is_active').prop('checked', true);
        });

        $('#table-libur').on('click', '.btn-edit', function() {
            var data = table.row($(this).parents('tr')).data();
            $('#method').val('edit');
            $('#id_libur').val(data.id_libur);
            $('#tanggal').val(data.tanggal);
            $('#nama_libur').val(data.nama_libur);
            $('#tipe_libur').val(data.tipe_libur);
            $('#is_recurring').prop('checked', data.is_recurring == 1);
            $('#is_active').prop('checked', data.is_active == 1);
            
            $('#createModalLabel').text('Edit Hari Libur');
            $('#createLiburModal').modal('show');
        });

        $('#formLibur').on('submit', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'JSON',
                success: function(data) {
                    if (data.status) {
                        $('#createLiburModal').modal('hide');
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
                        url: base_url + "presensi/hari_libur_delete",
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