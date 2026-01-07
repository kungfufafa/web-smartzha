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
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calendar-times mr-1"></i> <?= $subjudul ?></h3>
                    <?php if (!empty($has_table)): ?>
                        <div class="card-tools">
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#holidayModal" onclick="clearHolidayForm()">
                                <i class="fas fa-plus"></i> Tambah Hari Libur
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
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Nama Libur</th>
                                        <th>Tipe</th>
                                        <th>Berulang</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($hari_libur as $hl): ?>
                                        <tr>
                                            <td><strong><?= date('d/m/Y', strtotime($hl->tanggal)) ?></strong></td>
                                            <td><?= $hl->nama_libur ?></td>
                                            <td>
                                                <?php
                                                $badge = 'secondary';
                                                if ($hl->tipe_libur === 'NASIONAL') $badge = 'danger';
                                                elseif ($hl->tipe_libur === 'AKADEMIK') $badge = 'info';
                                                elseif ($hl->tipe_libur === 'KANTOR') $badge = 'warning';
                                                ?>
                                                <span class="badge badge-<?= $badge ?>"><?= $hl->tipe_libur ?></span>
                                            </td>
                                            <td><?= !empty($hl->is_recurring) ? '<span class="badge badge-success">Ya</span>' : '<span class="badge badge-secondary">Tidak</span>' ?></td>
                                            <td><?= !empty($hl->is_active) ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-secondary">Nonaktif</span>' ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info" onclick="editHoliday(<?= (int) $hl->id_libur ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteHoliday(<?= (int) $hl->id_libur ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<?php if (!empty($has_table)): ?>
<!-- Holiday Modal -->
<div class="modal fade" id="holidayModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="holidayModalTitle">Tambah Hari Libur</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="holidayForm">
                    <input type="hidden" name="id_libur" id="holiday-id">

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
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveHoliday()">Simpan</button>
            </div>
        </div>
    </div>
</div>

<script>
var holidaysData = <?= json_encode($hari_libur ?? []) ?>;
var csrfName = '<?= $this->security->get_csrf_token_name() ?>';
var csrfHash = '<?= $this->security->get_csrf_hash() ?>';

function appendCsrf(formData) {
    formData.append(csrfName, csrfHash);
    return formData;
}

function clearHolidayForm() {
    document.getElementById('holiday-id').value = '';
    document.getElementById('holiday-tanggal').value = '';
    document.getElementById('holiday-nama').value = '';
    document.getElementById('holiday-tipe').value = 'NASIONAL';
    document.getElementById('holiday-recurring').checked = false;
    document.getElementById('holiday-active').checked = true;
    document.getElementById('holidayModalTitle').textContent = 'Tambah Hari Libur';
}

function findHolidayById(id) {
    var target = String(id);
    if (!Array.isArray(holidaysData)) return null;
    for (var i = 0; i < holidaysData.length; i++) {
        if (String(holidaysData[i].id_libur) === target) {
            return holidaysData[i];
        }
    }
    return null;
}

function editHoliday(id) {
    var holiday = findHolidayById(id);
    if (!holiday) {
        alert('Data hari libur tidak ditemukan');
        return;
    }

    document.getElementById('holiday-id').value = holiday.id_libur;
    document.getElementById('holiday-tanggal').value = holiday.tanggal;
    document.getElementById('holiday-nama').value = holiday.nama_libur;
    document.getElementById('holiday-tipe').value = holiday.tipe_libur || 'NASIONAL';
    document.getElementById('holiday-recurring').checked = String(holiday.is_recurring) === '1';
    document.getElementById('holiday-active').checked = String(holiday.is_active) === '1';
    document.getElementById('holidayModalTitle').textContent = 'Edit Hari Libur';

    $('#holidayModal').modal('show');
}

function saveHoliday() {
    var form = document.getElementById('holidayForm');
    var formData = new FormData(form);

    formData.set('is_recurring', document.getElementById('holiday-recurring').checked ? '1' : '0');
    formData.set('is_active', document.getElementById('holiday-active').checked ? '1' : '0');

    appendCsrf(formData);

    fetch('<?= base_url('presensi/save_hari_libur') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.message);
            $('#holidayModal').modal('hide');
            location.reload();
        } else {
            alert('Gagal menyimpan: ' + result.message);
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan: ' + error.message);
    });
}

function deleteHoliday(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus hari libur ini?')) {
        return;
    }

    var formData = new FormData();
    formData.append('id_libur', id);
    appendCsrf(formData);

    fetch('<?= base_url('presensi/delete_hari_libur') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert('Gagal menghapus: ' + result.message);
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan: ' + error.message);
    });
}
</script>
<?php endif; ?>

