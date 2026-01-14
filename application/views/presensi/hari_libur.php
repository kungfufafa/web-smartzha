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
                    <div class="card-title"><i class="fas fa-calendar-times mr-1"></i> <?= $subjudul ?></div>
                    <?php if (!empty($has_table)): ?>
                        <div class="card-tools">
                            <button type="button" onclick="reloadPage()" class="btn btn-sm btn-default">
                                <i class="fa fa-sync"></i> <span class="d-none d-sm-inline-block ml-1">Reload</span>
                            </button>
                            <button type="button" id="btn-add-holiday" class="btn btn-sm bg-gradient-primary">
                                <i class="fas fa-plus"></i><span class="d-none d-sm-inline-block ml-1">Tambah Hari Libur</span>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($has_table)): ?>
                        <div class="alert alert-warning text-center mb-0">
                            <i class="fas fa-database fa-3x mb-3"></i>
                            <h4>Tabel Hari Libur Belum Ada</h4>
                            <p class="mb-0">Jalankan update SQL Presensi terlebih dahulu (`presensi_hari_libur`).</p>
                        </div>
                    <?php elseif (empty($hari_libur)): ?>
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-calendar-times fa-3x mb-3"></i>
                            <h4>Belum Ada Hari Libur</h4>
                            <p class="mb-0">Tambahkan tanggal libur agar tidak dihitung Alpha (Auto-Alpha akan skip).</p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-1"></i>
                            Jika ada log <strong>Alpha (Auto-Alpha)</strong> di tanggal libur, setelah Anda simpan hari libur di sini sistem akan otomatis membersihkan log Auto-Alpha yang seharusnya libur.
                        </div>
                        <?= form_open('', array('id' => 'holidayTableForm')) ?>
                        <div class="table-responsive">
                            <table id="holidayTable" class="table table-striped table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th class="d-none">ID</th>
                                    <th width="50" height="50" class="text-center p-0 align-middle">No.</th>
                                    <th class="text-center p-0 align-middle">Tanggal</th>
                                    <th class="text-center p-0 align-middle">Nama Libur</th>
                                    <th class="text-center p-0 align-middle">Tipe</th>
                                    <th class="text-center p-0 align-middle">Berulang</th>
                                    <th class="text-center p-0 align-middle">Status</th>
                                    <th class="text-center p-0 align-middle">Aksi</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($hari_libur as $key => $hl): ?>
                                    <tr data-id="<?= (int) $hl->id_libur ?>">
                                        <td class="d-none row-id"><?= (int) $hl->id_libur ?></td>
                                        <td class="text-center"><?= ($key + 1) ?></td>
                                        <td class="text-center"><strong><?= date('d/m/Y', strtotime($hl->tanggal)) ?></strong></td>
                                        <td><?= htmlspecialchars((string) $hl->nama_libur, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-center">
                                            <?php
                                            $badge = 'secondary';
                                            if ($hl->tipe_libur === 'NASIONAL') $badge = 'danger';
                                            elseif ($hl->tipe_libur === 'AKADEMIK') $badge = 'info';
                                            elseif ($hl->tipe_libur === 'KANTOR') $badge = 'warning';
                                            ?>
                                            <span class="badge badge-<?= $badge ?>"><?= htmlspecialchars((string) $hl->tipe_libur, ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?= !empty($hl->is_recurring) ? '<span class="text-success"><i class="fa fa-check mr-1"></i>Ya</span>' : '<span class="text-muted">Tidak</span>' ?>
                                        </td>
                                        <td class="text-center">
                                            <?= !empty($hl->is_active) ? '<span class="text-success"><i class="fa fa-check mr-1"></i>Aktif</span>' : '<span class="text-muted">Nonaktif</span>' ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button"
                                                        class="btn btn-xs btn-warning btn-edit"
                                                        data-id="<?= (int) $hl->id_libur ?>"
                                                        data-tanggal="<?= htmlspecialchars((string) $hl->tanggal, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-nama="<?= htmlspecialchars((string) $hl->nama_libur, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-tipe="<?= htmlspecialchars((string) $hl->tipe_libur, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-recurring="<?= (int) $hl->is_recurring ?>"
                                                        data-active="<?= (int) $hl->is_active ?>">
                                                    Edit
                                                </button>
                                                <button type="button"
                                                        class="btn btn-xs btn-danger btn-delete"
                                                        data-id="<?= (int) $hl->id_libur ?>">
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

<?php if (!empty($has_table)): ?>
<?= form_open('', array('id' => 'holidayForm')) ?>
<div class="modal fade" id="holidayModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="holidayModalTitle">Tambah Hari Libur</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_libur" id="holiday-id">
                <input type="hidden" name="method" id="holiday-method">

                <div class="form-group">
                    <label>Tanggal *</label>
                    <input type="date" class="form-control" name="tanggal" id="holiday-tanggal" required>
                    <small class="text-muted">Gunakan format tanggal (YYYY-MM-DD).</small>
                </div>

                <div class="form-group">
                    <label>Nama Libur *</label>
                    <input type="text" class="form-control" name="nama_libur" id="holiday-nama" placeholder="Contoh: Hari Lahir Pancasila" required>
                </div>

                <div class="form-group">
                    <label>Tipe Libur</label>
                    <select class="form-control" name="tipe_libur" id="holiday-tipe">
                        <option value="NASIONAL">NASIONAL</option>
                        <option value="AKADEMIK">AKADEMIK</option>
                        <option value="KANTOR">KANTOR</option>
                    </select>
                    <small class="text-muted">Pengaruh ke group: `national_only` hanya menghitung NASIONAL; `all` menghitung semua.</small>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_recurring" id="holiday-recurring">
                        Berulang tiap tahun
                    </label>
                    <small class="d-block text-muted">Contoh: tanggal merah tahunan (1 Juni). Sistem akan mengenali tanggal yang sama di tahun berbeda.</small>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" id="holiday-active" checked>
                        Aktif
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
<?php endif; ?>

<script>
function reloadPage() {
    window.location.reload();
}

function getHolidayId(source) {
    var id = $(source).data('id');
    if (!id) {
        id = $(source).closest('tr').data('id');
    }
    if (!id) {
        id = $(source).closest('tr').find('.row-id').text();
    }
    return id;
}

function resetHolidayForm() {
    $('#holidayForm')[0].reset();
    $('#holiday-id').val('');
    $('#holiday-method').val('add');
    $('#holidayModalTitle').text('Tambah Hari Libur');
    $('#holiday-tipe').val('NASIONAL');
    $('#holiday-recurring').prop('checked', false);
    $('#holiday-active').prop('checked', true);
}

$(document).ready(function () {
    ajaxcsrf();
    $('#holidayModal').appendTo('body');

    $('#btn-add-holiday').on('click', function () {
        resetHolidayForm();
        $('#holidayModal').modal('show');
    });

    $('#holidayTable').on('click', '.btn-edit', function () {
        var holidayId = getHolidayId(this);
        if (!holidayId) {
            swal.fire({ title: 'Gagal', text: 'ID hari libur tidak ditemukan', icon: 'error' });
            return;
        }

        $('#holiday-method').val('edit');
        $('#holidayModalTitle').text('Edit Hari Libur');
        $('#holiday-id').val(holidayId);
        $('#holiday-tanggal').val($(this).data('tanggal'));
        $('#holiday-nama').val($(this).data('nama'));
        $('#holiday-tipe').val($(this).data('tipe') || 'NASIONAL');
        $('#holiday-recurring').prop('checked', String($(this).data('recurring')) === '1');
        $('#holiday-active').prop('checked', String($(this).data('active')) === '1');

        $('#holidayModal').modal('show');
    });

    $('#holidayForm').on('submit', function (e) {
        e.preventDefault();
        e.stopPropagation();

        $.ajax({
            url: base_url + 'presensi/save_hari_libur',
            type: 'POST',
            dataType: 'JSON',
            data: $(this).serialize(),
            success: function (response) {
                var title = response.status ? 'Berhasil' : 'Gagal';
                var type = response.status ? 'success' : 'error';
                var message = response.msg || 'Gagal menyimpan hari libur';

                swal.fire({ title: title, text: message, icon: type }).then((result) => {
                    if (result.value) {
                        if (response.status) {
                            window.location.href = base_url + 'presensi/hari_libur';
                        }
                    }
                });
            },
            error: function (xhr) {
                var message = 'Terjadi kesalahan saat menyimpan hari libur';
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

    $('#holidayTable').on('click', '.btn-delete', function () {
        var id = getHolidayId(this);
        if (!id) {
            swal.fire({ title: 'Gagal', text: 'ID hari libur tidak ditemukan', icon: 'error' });
            return;
        }

        swal.fire({
            title: 'Hapus Hari Libur',
            text: 'Anda yakin akan menghapus hari libur ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Hapus'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: base_url + 'presensi/delete_hari_libur',
                    type: 'POST',
                    dataType: 'JSON',
                    data: $('#holidayTableForm').serialize() + '&id_libur=' + encodeURIComponent(id),
                    success: function (response) {
                        var title = response.status ? 'Berhasil' : 'Gagal';
                        var type = response.status ? 'success' : 'error';
                        var message = response.msg || 'Gagal menghapus hari libur';

                        swal.fire({ title: title, text: message, icon: type }).then((result) => {
                            if (result.value) {
                                if (response.status) {
                                    window.location.href = base_url + 'presensi/hari_libur';
                                }
                            }
                        });
                    },
                    error: function (xhr) {
                        var message = 'Terjadi kesalahan saat menghapus hari libur';
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
