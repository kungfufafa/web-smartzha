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
                        <button type="button" data-toggle="modal" data-target="#createLokasiModal" class="btn btn-sm btn-primary">
                            <i class="fa fa-plus"></i> <span class="d-none d-sm-inline-block ml-1">Tambah Lokasi</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" id="bulk_delete">
                            <i class="fa fa-trash"></i> <span class="d-none d-sm-inline-block ml-1">Hapus Terpilih</span>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <?= form_open('presensi/lokasi_delete', array('id' => 'bulk')) ?>
                        <table id="table-lokasi" class="table table-striped table-bordered table-hover" style="width: 100%">
                            <thead>
                                <tr>
                                    <th width="30" class="text-center p-0 align-middle">
                                        <div class="icheck-primary d-inline">
                                            <input type="checkbox" id="check_all">
                                            <label for="check_all"></label>
                                        </div>
                                    </th>
                                    <th width="50" class="text-center p-0 align-middle">No.</th>
                                    <th>Nama Lokasi</th>
                                    <th>Kode</th>
                                    <th>Koordinat</th>
                                    <th>Radius</th>
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
<div class="modal fade" id="createLokasiModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Tambah Lokasi</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?= form_open('presensi/lokasi_save', array('id' => 'formLokasi')) ?>
            <div class="modal-body">
                <input type="hidden" name="method" id="method" value="add">
                <input type="hidden" name="id_lokasi" id="id_lokasi">
                
                <div class="form-group">
                    <label>Nama Lokasi</label>
                    <input type="text" name="nama_lokasi" id="nama_lokasi" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Kode Lokasi</label>
                    <input type="text" name="kode_lokasi" id="kode_lokasi" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat" id="alamat" class="form-control" rows="2"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Latitude</label>
                            <input type="text" name="latitude" id="latitude" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Longitude</label>
                            <input type="text" name="longitude" id="longitude" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Radius (Meter)</label>
                    <input type="number" name="radius_meter" id="radius_meter" class="form-control" value="100">
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="is_default" name="is_default" value="1">
                        <label class="custom-control-label" for="is_default">Set sebagai Lokasi Default</label>
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

        table = $('#table-lokasi').DataTable({
            "processing": true,
            "serverSide": true,
            "order": [],
            "ajax": {
                "url": base_url + "presensi/lokasi_data",
                "type": "POST"
            },
            "columnDefs": [
                { "targets": [0, 1, 7], "orderable": false },
                { "targets": [0, 1, 7], "className": "text-center" }
            ],
            "columns": [
                { 
                    "data": "id_lokasi",
                    "render": function(data, type, row) {
                        return '<div class="icheck-primary d-inline"><input type="checkbox" class="check-item" name="checked[]" value="'+data+'" id="check'+data+'"><label for="check'+data+'"></label></div>';
                    }
                },
                { "data": null, "render": function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                { 
                    "data": "nama_lokasi",
                    "render": function(data, type, row) {
                        return data + (row.is_default == 1 ? ' <span class="badge badge-primary">Default</span>' : '');
                    }
                },
                { "data": "kode_lokasi" },
                { 
                    "data": null,
                    "render": function(data, type, row) {
                        return row.latitude + ', ' + row.longitude;
                    }
                },
                { "data": "radius_meter" },
                { 
                    "data": "is_active",
                    "render": function(data, type, row) {
                        return data == 1 ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-secondary">Non-Aktif</span>';
                    }
                },
                { 
                    "data": "id_lokasi",
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

        $('#createLokasiModal').on('hidden.bs.modal', function() {
            $('#formLokasi')[0].reset();
            $('#method').val('add');
            $('#id_lokasi').val('');
            $('#createModalLabel').text('Tambah Lokasi');
            $('#is_active').prop('checked', true);
        });

        $('#table-lokasi').on('click', '.btn-edit', function() {
            var data = table.row($(this).parents('tr')).data();
            $('#method').val('edit');
            $('#id_lokasi').val(data.id_lokasi);
            $('#nama_lokasi').val(data.nama_lokasi);
            $('#kode_lokasi').val(data.kode_lokasi);
            $('#alamat').val(data.alamat); 
            $('#latitude').val(data.latitude);
            $('#longitude').val(data.longitude);
            $('#radius_meter').val(data.radius_meter);
            $('#is_default').prop('checked', data.is_default == 1);
            $('#is_active').prop('checked', data.is_active == 1);
            
            $('#createModalLabel').text('Edit Lokasi');
            $('#createLokasiModal').modal('show');
        });

        $('#formLokasi').on('submit', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'JSON',
                success: function(data) {
                    if (data.status) {
                        $('#createLokasiModal').modal('hide');
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
                        url: base_url + "presensi/lokasi_delete",
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