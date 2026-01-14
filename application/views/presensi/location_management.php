<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

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
                    <div class="card-title"><i class="fas fa-map-marker-alt mr-1"></i> <?= $subjudul ?></div>
                    <div class="card-tools">
                        <button type="button" onclick="reloadPage()" class="btn btn-sm btn-default">
                            <i class="fa fa-sync"></i> <span class="d-none d-sm-inline-block ml-1">Reload</span>
                        </button>
                        <button type="button" id="btn-add-lokasi" class="btn btn-sm bg-gradient-primary">
                            <i class="fas fa-plus"></i><span class="d-none d-sm-inline-block ml-1">Tambah Lokasi</span>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($lokasi)): ?>
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-map-marker-alt fa-3x mb-3"></i>
                            <h4>Tidak Ada Lokasi</h4>
                            <p class="mb-0">Belum ada lokasi yang dibuat</p>
                        </div>
                    <?php else: ?>
                        <?= form_open('', array('id' => 'lokasiTableForm')) ?>
                        <div class="table-responsive">
                            <table id="lokasiTable" class="table table-striped table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th class="d-none">ID</th>
                                    <th width="50" height="50" class="text-center p-0 align-middle">No.</th>
                                    <th class="text-center p-0 align-middle">Kode</th>
                                    <th class="text-center p-0 align-middle">Nama Lokasi</th>
                                    <th class="text-center p-0 align-middle">Alamat</th>
                                    <th class="text-center p-0 align-middle">Latitude</th>
                                    <th class="text-center p-0 align-middle">Longitude</th>
                                    <th class="text-center p-0 align-middle">Radius (meter)</th>
                                    <th class="text-center p-0 align-middle">Default</th>
                                    <th class="text-center p-0 align-middle">Status</th>
                                    <th class="text-center p-0 align-middle">Aksi</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($lokasi as $key => $loc): ?>
                                    <tr data-id="<?= (int) $loc->id_lokasi ?>">
                                        <td class="d-none row-id"><?= (int) $loc->id_lokasi ?></td>
                                        <td class="text-center"><?= ($key + 1) ?></td>
                                        <td class="text-center"><strong><?= htmlspecialchars((string) $loc->kode_lokasi, ENT_QUOTES, 'UTF-8') ?></strong></td>
                                        <td><?= htmlspecialchars((string) $loc->nama_lokasi, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) $loc->alamat, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-center"><?= htmlspecialchars((string) $loc->latitude, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-center"><?= htmlspecialchars((string) $loc->longitude, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-center"><?= (int) $loc->radius_meter ?></td>
                                        <td class="text-center">
                                            <?= !empty($loc->is_default) ? '<span class="text-success"><i class="fa fa-check mr-1"></i>Ya</span>' : '<span class="text-muted">Tidak</span>' ?>
                                        </td>
                                        <td class="text-center">
                                            <?= !empty($loc->is_active) ? '<span class="text-success"><i class="fa fa-check mr-1"></i>Aktif</span>' : '<span class="text-muted">Nonaktif</span>' ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button"
                                                        class="btn btn-xs btn-warning btn-edit"
                                                        data-id="<?= (int) $loc->id_lokasi ?>"
                                                        data-nama="<?= htmlspecialchars((string) $loc->nama_lokasi, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-kode="<?= htmlspecialchars((string) $loc->kode_lokasi, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-alamat="<?= htmlspecialchars((string) $loc->alamat, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-lat="<?= htmlspecialchars((string) $loc->latitude, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-lng="<?= htmlspecialchars((string) $loc->longitude, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-radius="<?= (int) $loc->radius_meter ?>"
                                                        data-default="<?= (int) $loc->is_default ?>"
                                                        data-active="<?= (int) $loc->is_active ?>">
                                                    Edit
                                                </button>
                                                <button type="button"
                                                        class="btn btn-xs btn-danger btn-delete"
                                                        data-id="<?= (int) $loc->id_lokasi ?>">
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?= form_close() ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<?= form_open('', array('id' => 'lokasiForm')) ?>
<div class="modal fade" id="lokasiModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lokasiModalTitle">Tambah Lokasi</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_lokasi" id="lokasi-id">
                <input type="hidden" name="method" id="lokasi-method">

                <div class="form-group">
                    <label>Nama Lokasi *</label>
                    <input type="text" class="form-control" name="nama_lokasi" id="lokasi-nama" required>
                </div>

                <div class="form-group">
                    <label>Kode Lokasi *</label>
                    <input type="text" class="form-control" name="kode_lokasi" id="lokasi-kode" required>
                </div>

                <div class="form-group">
                    <label>Alamat</label>
                    <textarea class="form-control" name="alamat" id="lokasi-alamat" rows="2"></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Latitude *</label>
                            <input type="text" class="form-control" name="latitude" id="lokasi-lat" step="any" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Longitude *</label>
                            <input type="text" class="form-control" name="longitude" id="lokasi-lng" step="any" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Radius (meter)</label>
                            <input type="number" class="form-control" name="radius_meter" id="lokasi-radius" value="100">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Status</label>
                            <select class="form-control" name="is_active" id="lokasi-active">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_default" id="lokasi-default">
                        Jadikan Lokasi Default
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>

<script>
function reloadPage() {
    window.location.reload();
}

function getLokasiId(source) {
    var id = $(source).data('id');
    if (!id) {
        id = $(source).closest('tr').data('id');
    }
    if (!id) {
        id = $(source).closest('tr').find('.row-id').text();
    }
    return id;
}

function resetLokasiForm() {
    $('#lokasiForm')[0].reset();
    $('#lokasi-id').val('');
    $('#lokasi-method').val('add');
    $('#lokasiModalTitle').text('Tambah Lokasi');
    $('#lokasi-radius').val('100');
    $('#lokasi-default').prop('checked', false);
    $('#lokasi-active').val('1');
}

$(document).ready(function () {
    ajaxcsrf();
    $('#lokasiModal').appendTo('body');

    $('#btn-add-lokasi').on('click', function () {
        resetLokasiForm();
        $('#lokasiModal').modal('show');
    });

    $('#lokasiTable').on('click', '.btn-edit', function () {
        var lokasiId = getLokasiId(this);
        if (!lokasiId) {
            swal.fire({ title: 'Gagal', text: 'ID lokasi tidak ditemukan', icon: 'error' });
            return;
        }

        $('#lokasi-method').val('edit');
        $('#lokasiModalTitle').text('Edit Lokasi');
        $('#lokasi-id').val(lokasiId);
        $('#lokasi-nama').val($(this).data('nama'));
        $('#lokasi-kode').val($(this).data('kode'));
        $('#lokasi-alamat').val($(this).data('alamat'));
        $('#lokasi-lat').val($(this).data('lat'));
        $('#lokasi-lng').val($(this).data('lng'));
        $('#lokasi-radius').val($(this).data('radius'));
        $('#lokasi-default').prop('checked', String($(this).data('default')) === '1');
        $('#lokasi-active').val(String($(this).data('active')));

        $('#lokasiModal').modal('show');
    });

    $('#lokasiForm').on('submit', function (e) {
        e.preventDefault();
        e.stopPropagation();

        $.ajax({
            url: base_url + 'presensi/save_location',
            type: 'POST',
            dataType: 'JSON',
            data: $(this).serialize(),
            success: function (response) {
                var title = response.status ? 'Berhasil' : 'Gagal';
                var type = response.status ? 'success' : 'error';
                var message = response.msg || 'Gagal menyimpan lokasi';

                swal.fire({ title: title, text: message, icon: type }).then((result) => {
                    if (result.value) {
                        if (response.status) {
                            window.location.href = base_url + 'presensi/location_management';
                        }
                    }
                });
            },
            error: function (xhr) {
                var message = 'Terjadi kesalahan saat menyimpan lokasi';
                if (xhr.responseText) {
                    try {
                        var err = JSON.parse(xhr.responseText);
                        message = err.msg || err.message || message;
                    } catch (e) {
                        message = xhr.responseText;
                    }
                }
                swal.fire({ title: 'Error', text: message, icon: 'error' });
            }
        });
    });

    $('#lokasiTable').on('click', '.btn-delete', function () {
        var id = getLokasiId(this);
        if (!id) {
            swal.fire({ title: 'Gagal', text: 'ID lokasi tidak ditemukan', icon: 'error' });
            return;
        }

        swal.fire({
            title: 'Hapus Lokasi',
            text: 'Anda yakin akan menghapus lokasi ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Hapus'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: base_url + 'presensi/delete_location',
                    type: 'POST',
                    dataType: 'JSON',
                    data: $('#lokasiTableForm').serialize() + '&id_lokasi=' + encodeURIComponent(id),
                    success: function (response) {
                        var title = response.status ? 'Berhasil' : 'Gagal';
                        var type = response.status ? 'success' : 'error';
                        var message = response.msg || 'Gagal menghapus lokasi';

                        swal.fire({ title: title, text: message, icon: type }).then((result) => {
                            if (result.value) {
                                if (response.status) {
                                    window.location.href = base_url + 'presensi/location_management';
                                }
                            }
                        });
                    },
                    error: function (xhr) {
                        var message = 'Terjadi kesalahan saat menghapus lokasi';
                        if (xhr.responseText) {
                            try {
                                var err = JSON.parse(xhr.responseText);
                                message = err.msg || err.message || message;
                            } catch (e) {
                                message = xhr.responseText;
                            }
                        }
                        swal.fire({ title: 'Error', text: message, icon: 'error' });
                    }
                });
            }
        });
    });
});
</script>
