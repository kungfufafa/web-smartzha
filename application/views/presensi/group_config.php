<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
function presensi_tristate_label($value)
{
    if ($value === null) {
        return '<span class="text-muted">Default Sistem</span>';
    }

    return ((int) $value === 1)
        ? '<span class="text-success"><i class="fa fa-check mr-1"></i>Ya</span>'
        : '<span class="text-danger">Tidak</span>';
}

function presensi_yesno_label($value)
{
    return !empty($value)
        ? '<span class="text-success"><i class="fa fa-check mr-1"></i>Ya</span>'
        : '<span class="text-muted">Tidak</span>';
}
?>

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
                    <div class="card-title"><i class="fas fa-users-cog mr-1"></i> <?= $subjudul ?></div>
                    <div class="card-tools">
                        <button type="button" onclick="reloadPage()" class="btn btn-sm btn-default">
                            <i class="fa fa-sync"></i> <span class="d-none d-sm-inline-block ml-1">Reload</span>
                        </button>
                        <button type="button" id="btn-add-group-config" class="btn btn-sm bg-gradient-primary">
                            <i class="fas fa-plus"></i><span class="d-none d-sm-inline-block ml-1">Tambah Konfigurasi</span>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-1"></i>
                        Konfigurasi group presensi hanya untuk <strong>Guru</strong>, <strong>Siswa</strong>, dan <strong>Tendik</strong>.
                        <small class="d-block text-muted mt-1">Catatan: Satpam termasuk Tendik (tipe tendik), bukan group terpisah. Untuk beda jam kerja antar orang (mis. satpam pagi vs malam), atur di <strong>Jadwal User</strong>. Untuk pola per tipe tendik (opsional), gunakan <strong>Jadwal Tendik (Per Tipe)</strong>. Hari kerja ditentukan di <strong>Jadwal Presensi</strong>; jika tidak ada jadwal maka dianggap <strong>Libur</strong>. Admin/Orangtua tidak perlu jadwal presensi.</small>
                    </div>
                    <?php if (empty($configs)): ?>
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-users-cog fa-3x mb-3"></i>
                            <h4>Tidak Ada Konfigurasi</h4>
                            <p class="mb-0">Belum ada konfigurasi group yang dibuat</p>
                        </div>
                    <?php else: ?>
                        <?= form_open('', array('id' => 'groupConfigTableForm')) ?>
                        <div class="table-responsive">
                            <table id="groupConfigTable" class="table table-striped table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th class="d-none">ID</th>
                                    <th width="50" height="50" class="text-center p-0 align-middle">No.</th>
                                    <th class="text-center p-0 align-middle">Group</th>
                                    <th class="text-center p-0 align-middle">Nama Konfigurasi</th>
                                    <th class="text-center p-0 align-middle">Mode Validasi</th>
                                    <th class="text-center p-0 align-middle">Shift Default</th>
                                    <th class="text-center p-0 align-middle">Lokasi Default</th>
                                    <th class="text-center p-0 align-middle">Wajib Foto</th>
                                    <th class="text-center p-0 align-middle">Wajib Pulang</th>
                                    <th class="text-center p-0 align-middle">Bypass</th>
                                    <th class="text-center p-0 align-middle">Lembur</th>
                                    <th class="text-center p-0 align-middle">Approve Lembur</th>
                                    <th class="text-center p-0 align-middle">Holiday Mode</th>
                                    <th class="text-center p-0 align-middle">Kalender Akademik</th>
                                    <th class="text-center p-0 align-middle">Status</th>
                                    <th class="text-center p-0 align-middle">Aksi</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($configs as $key => $config): ?>
                                    <?php $is_allowed_group = empty($allowed_group_names) ? true : in_array($config->group_name, $allowed_group_names, true); ?>
                                    <tr data-id="<?= (int) $config->id ?>">
                                        <td class="d-none row-id"><?= (int) $config->id ?></td>
                                        <td class="text-center"><?= ($key + 1) ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars((string) $config->group_name, ENT_QUOTES, 'UTF-8') ?></strong>
                                            <?php if (!$is_allowed_group): ?>
                                                <span class="badge badge-secondary ml-1">Tidak digunakan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars((string) $config->nama_konfigurasi, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-center"><span class="badge badge-info"><?= htmlspecialchars((string) $config->validation_mode, ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td class="text-center"><?= !empty($config->shift_name) ? htmlspecialchars((string) $config->shift_name, ENT_QUOTES, 'UTF-8') : '-' ?></td>
                                        <td class="text-center"><?= !empty($config->lokasi_name) ? htmlspecialchars((string) $config->lokasi_name, ENT_QUOTES, 'UTF-8') : '-' ?></td>
                                        <td class="text-center"><?= presensi_tristate_label($config->require_photo) ?></td>
                                        <td class="text-center"><?= presensi_tristate_label($config->require_checkout) ?></td>
                                        <td class="text-center"><?= presensi_tristate_label($config->allow_bypass) ?></td>
                                        <td class="text-center"><?= presensi_tristate_label($config->enable_overtime) ?></td>
                                        <td class="text-center"><?= presensi_tristate_label($config->overtime_require_approval) ?></td>
                                        <td class="text-center"><?= htmlspecialchars((string) $config->holiday_mode, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-center"><?= presensi_yesno_label($config->follow_academic_calendar) ?></td>
                                        <td class="text-center"><?= presensi_yesno_label($config->is_active) ?></td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($is_allowed_group): ?>
                                                    <button type="button"
                                                            class="btn btn-xs btn-warning btn-edit"
                                                            data-id="<?= (int) $config->id ?>"
                                                            data-group="<?= (int) $config->id_group ?>"
                                                            data-nama="<?= htmlspecialchars((string) $config->nama_konfigurasi, ENT_QUOTES, 'UTF-8') ?>"
                                                            data-validation="<?= htmlspecialchars((string) $config->validation_mode, ENT_QUOTES, 'UTF-8') ?>"
                                                            data-shift="<?= $config->id_shift_default !== null ? (int) $config->id_shift_default : '' ?>"
                                                            data-lokasi="<?= $config->id_lokasi_default !== null ? (int) $config->id_lokasi_default : '' ?>"
                                                            data-holiday="<?= htmlspecialchars((string) $config->holiday_mode, ENT_QUOTES, 'UTF-8') ?>"
                                                            data-calendar="<?= (int) $config->follow_academic_calendar ?>"
                                                            data-photo="<?= $config->require_photo === null ? '' : (int) $config->require_photo ?>"
                                                            data-checkout="<?= $config->require_checkout === null ? '' : (int) $config->require_checkout ?>"
                                                            data-bypass="<?= $config->allow_bypass === null ? '' : (int) $config->allow_bypass ?>"
                                                            data-overtime="<?= $config->enable_overtime === null ? '' : (int) $config->enable_overtime ?>"
                                                            data-approve-overtime="<?= $config->overtime_require_approval === null ? '' : (int) $config->overtime_require_approval ?>"
                                                            data-active="<?= (int) $config->is_active ?>">
                                                        Edit
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button"
                                                        class="btn btn-xs btn-danger btn-delete"
                                                        data-id="<?= (int) $config->id ?>">
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

<?= form_open('', array('id' => 'groupConfigForm')) ?>
<div class="modal fade" id="groupConfigModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="groupConfigModalTitle">Tambah Konfigurasi Group</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="group-config-id">
                <input type="hidden" name="method" id="group-config-method">

                <div class="form-group">
                    <label>Group *</label>
                    <select class="form-control" name="id_group" id="group-config-group" required>
                        <option value="">Pilih Group</option>
                        <?php if (!empty($groups)): ?>
                            <?php foreach ($groups as $g): ?>
                                <option value="<?= $g->id ?>"><?= $g->name ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Nama Konfigurasi</label>
                    <input type="text" class="form-control" name="nama_konfigurasi" id="group-config-nama">
                </div>

                <div class="form-group">
                    <label>Mode Validasi</label>
                    <select class="form-control" name="validation_mode" id="group-config-validation">
                        <option value="gps">GPS Saja</option>
                        <option value="qr">QR Saja</option>
                        <option value="gps_or_qr">GPS atau QR</option>
                        <option value="manual">Manual</option>
                        <option value="any">Apa saja</option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Shift Default (Opsional)</label>
                            <select class="form-control" name="id_shift_default" id="group-config-shift">
                                <option value="">-- Pilih Shift --</option>
                                <?php if (!empty($shifts)): ?>
                                    <?php foreach ($shifts as $s): ?>
                                        <option value="<?= $s->id_shift ?>"><?= $s->nama_shift ?> (<?= $s->kode_shift ?>)</option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted">Tidak menentukan hari kerja; hari kerja diatur di menu Jadwal Presensi.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Lokasi Default</label>
                            <select class="form-control" name="id_lokasi_default" id="group-config-lokasi">
                                <option value="">-- Pilih Lokasi --</option>
                                <?php if (!empty($lokasi)): ?>
                                    <?php foreach ($lokasi as $l): ?>
                                        <option value="<?= $l->id_lokasi ?>"><?= $l->nama_lokasi ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Holiday Mode</label>
                            <select class="form-control" name="holiday_mode" id="group-config-holiday">
                                <option value="all">Semua</option>
                                <option value="national_only">Hanya Nasional</option>
                                <option value="none">Tidak Ada</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="follow_academic_calendar" id="group-config-calendar">
                                Ikuti Kalender Akademik
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Wajib Foto</label>
                            <select class="form-control form-control-sm" name="require_photo" id="group-config-photo">
                                <option value="">Default Sistem</option>
                                <option value="1">Ya (Wajib)</option>
                                <option value="0">Tidak (Opsional)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Wajib Pulang</label>
                            <select class="form-control form-control-sm" name="require_checkout" id="group-config-checkout">
                                <option value="">Default Sistem</option>
                                <option value="1">Ya (Wajib)</option>
                                <option value="0">Tidak (Opsional)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Izinkan Bypass</label>
                            <select class="form-control form-control-sm" name="allow_bypass" id="group-config-bypass">
                                <option value="">Default Sistem</option>
                                <option value="1">Ya (Izinkan)</option>
                                <option value="0">Tidak (Tolak)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Aktifkan Lembur</label>
                            <select class="form-control form-control-sm" name="enable_overtime" id="group-config-overtime">
                                <option value="">Default Sistem</option>
                                <option value="1">Ya</option>
                                <option value="0">Tidak</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Approval Lembur</label>
                            <select class="form-control form-control-sm" name="overtime_require_approval" id="group-config-overtime-approval">
                                <option value="">Default Sistem</option>
                                <option value="1">Ya</option>
                                <option value="0">Tidak</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Status</label>
                            <select class="form-control form-control-sm" name="is_active" id="group-config-active">
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

function getGroupConfigId(source) {
    var id = $(source).data('id');
    if (!id) {
        id = $(source).closest('tr').data('id');
    }
    if (!id) {
        id = $(source).closest('tr').find('.row-id').text();
    }
    return id;
}

function resetGroupConfigForm() {
    $('#groupConfigForm')[0].reset();
    $('#group-config-id').val('');
    $('#group-config-method').val('add');
    $('#groupConfigModalTitle').text('Tambah Konfigurasi Group');
    $('#group-config-validation').val('gps_or_qr');
    $('#group-config-holiday').val('all');
    $('#group-config-calendar').prop('checked', false);
    $('#group-config-photo').val('');
    $('#group-config-checkout').val('');
    $('#group-config-bypass').val('');
    $('#group-config-overtime').val('');
    $('#group-config-overtime-approval').val('');
    $('#group-config-active').val('1');
}

$(document).ready(function () {
    ajaxcsrf();
    $('#groupConfigModal').appendTo('body');

    $('#btn-add-group-config').on('click', function () {
        resetGroupConfigForm();
        $('#groupConfigModal').modal('show');
    });

    $('#groupConfigTable').on('click', '.btn-edit', function () {
        var configId = getGroupConfigId(this);
        if (!configId) {
            swal.fire({ title: 'Gagal', text: 'ID konfigurasi tidak ditemukan', icon: 'error' });
            return;
        }

        $('#group-config-method').val('edit');
        $('#groupConfigModalTitle').text('Edit Konfigurasi Group');
        $('#group-config-id').val(configId);
        $('#group-config-group').val($(this).data('group'));
        $('#group-config-nama').val($(this).data('nama'));
        $('#group-config-validation').val($(this).data('validation') || 'gps_or_qr');
        $('#group-config-shift').val($(this).data('shift') || '');
        $('#group-config-lokasi').val($(this).data('lokasi') || '');
        $('#group-config-holiday').val($(this).data('holiday') || 'all');
        $('#group-config-calendar').prop('checked', String($(this).data('calendar')) === '1');
        $('#group-config-photo').val($(this).data('photo') === '' ? '' : String($(this).data('photo')));
        $('#group-config-checkout').val($(this).data('checkout') === '' ? '' : String($(this).data('checkout')));
        $('#group-config-bypass').val($(this).data('bypass') === '' ? '' : String($(this).data('bypass')));
        $('#group-config-overtime').val($(this).data('overtime') === '' ? '' : String($(this).data('overtime')));
        $('#group-config-overtime-approval').val($(this).data('approve-overtime') === '' ? '' : String($(this).data('approve-overtime')));
        $('#group-config-active').val(String($(this).data('active')));

        $('#groupConfigModal').modal('show');
    });

    $('#groupConfigForm').on('submit', function (e) {
        e.preventDefault();
        e.stopPropagation();

        $.ajax({
            url: base_url + 'presensi/save_group_config',
            type: 'POST',
            dataType: 'JSON',
            data: $(this).serialize(),
            success: function (response) {
                var title = response.status ? 'Berhasil' : 'Gagal';
                var type = response.status ? 'success' : 'error';
                var message = response.msg || 'Gagal menyimpan konfigurasi group';

                swal.fire({ title: title, text: message, icon: type }).then((result) => {
                    if (result.value) {
                        if (response.status) {
                            window.location.href = base_url + 'presensi/group_config';
                        }
                    }
                });
            },
            error: function (xhr) {
                var message = 'Terjadi kesalahan saat menyimpan konfigurasi group';
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

    $('#groupConfigTable').on('click', '.btn-delete', function () {
        var id = getGroupConfigId(this);
        if (!id) {
            swal.fire({ title: 'Gagal', text: 'ID konfigurasi tidak ditemukan', icon: 'error' });
            return;
        }

        swal.fire({
            title: 'Hapus Konfigurasi',
            text: 'Anda yakin akan menghapus konfigurasi ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Hapus'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: base_url + 'presensi/delete_group_config_ajax',
                    type: 'POST',
                    dataType: 'JSON',
                    data: $('#groupConfigTableForm').serialize() + '&id=' + encodeURIComponent(id),
                    success: function (response) {
                        var title = response.status ? 'Berhasil' : 'Gagal';
                        var type = response.status ? 'success' : 'error';
                        var message = response.msg || 'Gagal menghapus konfigurasi group';

                        swal.fire({ title: title, text: message, icon: type }).then((result) => {
                            if (result.value) {
                                if (response.status) {
                                    window.location.href = base_url + 'presensi/group_config';
                                }
                            }
                        });
                    },
                    error: function (xhr) {
                        var message = 'Terjadi kesalahan saat menghapus konfigurasi group';
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
