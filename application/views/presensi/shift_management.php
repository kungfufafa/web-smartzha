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
                    <div class="card-title"><i class="fas fa-business-time mr-1"></i> <?= $subjudul ?></div>
                    <div class="card-tools">
                        <button type="button" onclick="reloadPage()" class="btn btn-sm btn-default">
                            <i class="fa fa-sync"></i> <span class="d-none d-sm-inline-block ml-1">Reload</span>
                        </button>
                        <button type="button" id="btn-add-shift" class="btn btn-sm bg-gradient-primary">
                            <i class="fas fa-plus"></i><span class="d-none d-sm-inline-block ml-1">Tambah Shift</span>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($shifts)): ?>
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-clock fa-3x mb-3"></i>
                            <h4>Tidak Ada Shift</h4>
                            <p class="mb-0">Belum ada shift yang dibuat</p>
                        </div>
                    <?php else: ?>
                        <?= form_open('', array('id' => 'shiftTableForm')) ?>
                        <div class="table-responsive">
                            <table id="shiftTable" class="table table-striped table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th class="d-none">ID</th>
                                    <th width="50" height="50" class="text-center p-0 align-middle">No.</th>
                                    <th class="text-center p-0 align-middle">Kode</th>
                                    <th class="text-center p-0 align-middle">Nama Shift</th>
                                    <th class="text-center p-0 align-middle">Jam Masuk</th>
                                    <th class="text-center p-0 align-middle">Jam Pulang</th>
                                    <th class="text-center p-0 align-middle">Toleransi Masuk</th>
                                    <th class="text-center p-0 align-middle">Toleransi Pulang</th>
                                    <th class="text-center p-0 align-middle">Lintas Hari</th>
                                    <th class="text-center p-0 align-middle">Checkin Awal</th>
                                    <th class="text-center p-0 align-middle">Checkin Akhir</th>
                                    <th class="text-center p-0 align-middle">Checkout Awal</th>
                                    <th class="text-center p-0 align-middle">Status</th>
                                    <th class="text-center p-0 align-middle">Durasi (menit)</th>
                                    <th class="text-center p-0 align-middle">Aksi</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($shifts as $key => $shift): ?>
                                    <?php
                                    $earliest_checkin = !empty($shift->earliest_checkin) ? $shift->earliest_checkin : '';
                                    $latest_checkin = !empty($shift->latest_checkin) ? $shift->latest_checkin : '';
                                    $earliest_checkout = !empty($shift->earliest_checkout) ? $shift->earliest_checkout : '';
                                    ?>
                                    <tr data-id="<?= (int) $shift->id_shift ?>">
                                        <td class="d-none row-id"><?= (int) $shift->id_shift ?></td>
                                        <td class="text-center"><?= ($key + 1) ?></td>
                                        <td class="text-center"><strong><?= htmlspecialchars((string) $shift->kode_shift, ENT_QUOTES, 'UTF-8') ?></strong></td>
                                        <td><?= htmlspecialchars((string) $shift->nama_shift, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-center"><?= htmlspecialchars((string) $shift->jam_masuk, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-center"><?= htmlspecialchars((string) $shift->jam_pulang, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-center"><?= (int) $shift->toleransi_masuk_menit ?></td>
                                        <td class="text-center"><?= (int) $shift->toleransi_pulang_menit ?></td>
                                        <td class="text-center"><?= !empty($shift->is_lintas_hari) ? 'Ya' : 'Tidak' ?></td>
                                        <td class="text-center"><?= $earliest_checkin ?: '-' ?></td>
                                        <td class="text-center"><?= $latest_checkin ?: '-' ?></td>
                                        <td class="text-center"><?= $earliest_checkout ?: '-' ?></td>
                                        <td class="text-center">
                                            <?= !empty($shift->is_active) ? '<span class="text-success"><i class="fa fa-check mr-1"></i>Aktif</span>' : '<span class="text-muted">Nonaktif</span>' ?>
                                        </td>
                                        <td class="text-center"><?= $shift->durasi_kerja_menit !== null ? (int) $shift->durasi_kerja_menit : '-' ?></td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button"
                                                        class="btn btn-xs btn-warning btn-edit"
                                                        data-id="<?= (int) $shift->id_shift ?>"
                                                        data-nama="<?= htmlspecialchars((string) $shift->nama_shift, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-kode="<?= htmlspecialchars((string) $shift->kode_shift, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-deskripsi="<?= htmlspecialchars((string) ($shift->deskripsi ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                        data-jam-masuk="<?= htmlspecialchars((string) $shift->jam_masuk, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-jam-pulang="<?= htmlspecialchars((string) $shift->jam_pulang, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-toleransi-masuk="<?= (int) $shift->toleransi_masuk_menit ?>"
                                                        data-toleransi-pulang="<?= (int) $shift->toleransi_pulang_menit ?>"
                                                        data-lintas-hari="<?= (int) $shift->is_lintas_hari ?>"
                                                        data-earliest-checkin="<?= htmlspecialchars((string) $earliest_checkin, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-latest-checkin="<?= htmlspecialchars((string) $latest_checkin, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-earliest-checkout="<?= htmlspecialchars((string) $earliest_checkout, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-active="<?= (int) $shift->is_active ?>">
                                                    Edit
                                                </button>
                                                <button type="button"
                                                        class="btn btn-xs btn-danger btn-delete"
                                                        data-id="<?= (int) $shift->id_shift ?>">
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

<?= form_open('', array('id' => 'shiftForm')) ?>
<div class="modal fade" id="shiftModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shiftModalTitle">Tambah Shift</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_shift" id="shift-id">
                <input type="hidden" name="method" id="shift-method">

                <div class="form-group">
                    <label>Nama Shift *</label>
                    <input type="text" class="form-control" name="nama_shift" id="shift-nama" required>
                </div>

                <div class="form-group">
                    <label>Kode Shift *</label>
                    <input type="text" class="form-control" name="kode_shift" id="shift-kode" required>
                </div>

                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea class="form-control" name="deskripsi" id="shift-deskripsi" rows="2"></textarea>
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

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Checkin Awal (opsional)</label>
                            <input type="time" class="form-control" name="earliest_checkin" id="shift-earliest-checkin">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Checkin Akhir (opsional)</label>
                            <input type="time" class="form-control" name="latest_checkin" id="shift-latest-checkin">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Checkout Awal (opsional)</label>
                            <input type="time" class="form-control" name="earliest_checkout" id="shift-earliest-checkout">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Lintas Hari</label>
                            <select class="form-control" name="is_lintas_hari" id="shift-lintas-hari">
                                <option value="0">Tidak</option>
                                <option value="1">Ya</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Status</label>
                            <select class="form-control" name="is_active" id="shift-active">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                    </div>
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

function getShiftId(source) {
    var id = $(source).data('id');
    if (!id) {
        id = $(source).closest('tr').data('id');
    }
    if (!id) {
        id = $(source).closest('tr').find('.row-id').text();
    }
    return id;
}

function resetShiftForm() {
    $('#shiftForm')[0].reset();
    $('#shift-id').val('');
    $('#shift-method').val('add');
    $('#shiftModalTitle').text('Tambah Shift');
    $('#shift-toleransi-masuk').val('15');
    $('#shift-toleransi-pulang').val('0');
    $('#shift-lintas-hari').val('0');
    $('#shift-active').val('1');
}

$(document).ready(function () {
    ajaxcsrf();
    $('#shiftModal').appendTo('body');

    $('#btn-add-shift').on('click', function () {
        resetShiftForm();
        $('#shiftModal').modal('show');
    });

    $('#shiftTable').on('click', '.btn-edit', function () {
        var shiftId = getShiftId(this);
        if (!shiftId) {
            swal.fire({ title: 'Gagal', text: 'ID shift tidak ditemukan', icon: 'error' });
            return;
        }

        $('#shift-method').val('edit');
        $('#shiftModalTitle').text('Edit Shift');
        $('#shift-id').val(shiftId);
        $('#shift-nama').val($(this).data('nama'));
        $('#shift-kode').val($(this).data('kode'));
        $('#shift-deskripsi').val($(this).data('deskripsi'));
        $('#shift-jam-masuk').val($(this).data('jam-masuk'));
        $('#shift-jam-pulang').val($(this).data('jam-pulang'));
        $('#shift-toleransi-masuk').val($(this).data('toleransi-masuk'));
        $('#shift-toleransi-pulang').val($(this).data('toleransi-pulang'));
        $('#shift-lintas-hari').val(String($(this).data('lintas-hari')));
        $('#shift-earliest-checkin').val($(this).data('earliest-checkin'));
        $('#shift-latest-checkin').val($(this).data('latest-checkin'));
        $('#shift-earliest-checkout').val($(this).data('earliest-checkout'));
        $('#shift-active').val(String($(this).data('active')));

        $('#shiftModal').modal('show');
    });

    $('#shiftForm').on('submit', function (e) {
        e.preventDefault();
        e.stopPropagation();

        $.ajax({
            url: base_url + 'presensi/save_shift',
            type: 'POST',
            dataType: 'JSON',
            data: $(this).serialize(),
            success: function (response) {
                var title = response.status ? 'Berhasil' : 'Gagal';
                var type = response.status ? 'success' : 'error';
                var message = response.msg || 'Gagal menyimpan shift';

                swal.fire({ title: title, text: message, icon: type }).then((result) => {
                    if (result.value) {
                        if (response.status) {
                            window.location.href = base_url + 'presensi/shift_management';
                        }
                    }
                });
            },
            error: function (xhr) {
                var message = 'Terjadi kesalahan saat menyimpan shift';
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

    $('#shiftTable').on('click', '.btn-delete', function () {
        var id = getShiftId(this);
        if (!id) {
            swal.fire({ title: 'Gagal', text: 'ID shift tidak ditemukan', icon: 'error' });
            return;
        }

        swal.fire({
            title: 'Hapus Shift',
            text: 'Anda yakin akan menghapus shift ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Hapus'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: base_url + 'presensi/delete_shift',
                    type: 'POST',
                    dataType: 'JSON',
                    data: $('#shiftTableForm').serialize() + '&id_shift=' + encodeURIComponent(id),
                    success: function (response) {
                        var title = response.status ? 'Berhasil' : 'Gagal';
                        var type = response.status ? 'success' : 'error';
                        var message = response.msg || 'Gagal menghapus shift';

                        swal.fire({ title: title, text: message, icon: type }).then((result) => {
                            if (result.value) {
                                if (response.status) {
                                    window.location.href = base_url + 'presensi/shift_management';
                                }
                            }
                        });
                    },
                    error: function (xhr) {
                        var message = 'Terjadi kesalahan saat menghapus shift';
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
