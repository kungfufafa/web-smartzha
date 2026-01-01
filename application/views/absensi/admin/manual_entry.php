<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $judul ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('absensi') ?>">Absensi</a></li>
                        <li class="breadcrumb-item active">Input Manual</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-edit"></i> Form Input Absensi Manual</h3>
                        </div>
                        <?= form_open('absensi/saveManualEntry', ['id' => 'formManual']) ?>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Perhatian!</strong> Input manual akan tercatat di audit log. 
                                Gunakan fitur ini hanya untuk kasus khusus seperti:
                                <ul class="mb-0 mt-2">
                                    <li>Lupa absen karena keadaan darurat</li>
                                    <li>Masalah teknis (device rusak, internet mati)</li>
                                    <li>Koreksi data yang salah</li>
                                </ul>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Pegawai <span class="text-danger">*</span></label>
                                        <select class="form-control select2" name="id_user" id="id_user" required style="width: 100%;">
                                            <option value="">-- Pilih Pegawai --</option>
                                            <?php foreach ($users as $u): ?>
                                            <option value="<?= $u->id ?>"><?= htmlspecialchars($u->nama_lengkap ?? $u->username) ?> (<?= $u->nip ?? '-' ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tanggal <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="tanggal" id="tanggal" required value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Shift</label>
                                        <select class="form-control" name="id_shift" id="id_shift">
                                            <option value="">-- Pilih Shift --</option>
                                            <?php foreach ($shifts as $s): ?>
                                            <option value="<?= $s->id_shift ?>"><?= htmlspecialchars($s->nama_shift) ?> (<?= substr($s->jam_masuk, 0, 5) ?> - <?= substr($s->jam_pulang, 0, 5) ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Lokasi</label>
                                        <select class="form-control" name="id_lokasi" id="id_lokasi">
                                            <option value="">-- Pilih Lokasi --</option>
                                            <?php foreach ($locations as $loc): ?>
                                            <option value="<?= $loc->id_lokasi ?>" <?= $loc->is_default ? 'selected' : '' ?>><?= htmlspecialchars($loc->nama_lokasi) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h5>Data Kehadiran</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Jam Masuk</label>
                                        <input type="time" class="form-control" name="jam_masuk" id="jam_masuk">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Jam Pulang</label>
                                        <input type="time" class="form-control" name="jam_pulang" id="jam_pulang">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Status Kehadiran <span class="text-danger">*</span></label>
                                        <select class="form-control" name="status_kehadiran" id="status_kehadiran" required>
                                            <option value="Hadir">Hadir</option>
                                            <option value="Terlambat">Terlambat</option>
                                            <option value="Pulang Awal">Pulang Awal</option>
                                            <option value="Terlambat + Pulang Awal">Terlambat + Pulang Awal</option>
                                            <option value="Izin">Izin</option>
                                            <option value="Sakit">Sakit</option>
                                            <option value="Cuti">Cuti</option>
                                            <option value="Dinas Luar">Dinas Luar</option>
                                            <option value="Alpha">Alpha (Tanpa Keterangan)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Terlambat (menit)</label>
                                        <input type="number" class="form-control" name="terlambat_menit" value="0" min="0">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Pulang Awal (menit)</label>
                                        <input type="number" class="form-control" name="pulang_awal_menit" value="0" min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Alasan Input Manual <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="manual_entry_reason" id="manual_entry_reason" rows="3" required placeholder="Jelaskan alasan melakukan input manual..."></textarea>
                                <small class="text-muted">Alasan ini akan tercatat di audit log</small>
                            </div>

                            <div class="form-group">
                                <label>Keterangan Tambahan</label>
                                <textarea class="form-control" name="keterangan" rows="2" placeholder="Keterangan tambahan (opsional)..."></textarea>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                            <a href="<?= base_url('absensi') ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                        <?= form_close() ?>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Check Existing -->
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-search"></i> Cek Data Existing</h3>
                        </div>
                        <div class="card-body" id="existingData">
                            <p class="text-muted text-center">
                                Pilih pegawai dan tanggal untuk melihat data absensi yang sudah ada.
                            </p>
                        </div>
                    </div>

                    <!-- Recent Manual Entries -->
                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-history"></i> Input Manual Terakhir</h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php if (!empty($recent_manual)): ?>
                                    <?php foreach (array_slice($recent_manual, 0, 5) as $entry): ?>
                                    <li class="list-group-item">
                                        <strong><?= htmlspecialchars($entry->nama_lengkap ?? $entry->username) ?></strong>
                                        <small class="float-right text-muted"><?= date('d/m/Y', strtotime($entry->tanggal)) ?></small>
                                        <br>
                                        <small class="text-muted">
                                            <?= $entry->status_kehadiran ?> - 
                                            oleh <?= $entry->manual_by_name ?? 'Admin' ?>
                                        </small>
                                    </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="list-group-item text-center text-muted">
                                        Belum ada input manual
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Tips -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h3 class="card-title"><i class="fas fa-lightbulb"></i> Tips</h3>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0 pl-3">
                                <li>Kosongkan jam masuk/pulang jika tidak diketahui</li>
                                <li>Status akan otomatis menentukan kehadiran</li>
                                <li>Untuk izin/sakit/cuti, jam bisa dikosongkan</li>
                                <li>Data existing akan ditimpa jika sudah ada</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap4',
        placeholder: '-- Pilih Pegawai --',
        allowClear: true
    });

    $('#id_user, #tanggal').on('change', function() {
        checkExisting();
    });

    $('#id_user').on('change', function() {
        loadUserShift();
    });

    $('#formManual').on('submit', function(e) {
        e.preventDefault();
        
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status) {
                    toastr.success(res.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    toastr.error(res.message);
                    btn.prop('disabled', false).html('<i class="fas fa-save"></i> Simpan');
                }
            },
            error: function() {
                toastr.error('Terjadi kesalahan sistem');
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Simpan');
            }
        });
    });
});

function checkExisting() {
    var userId = $('#id_user').val();
    var tanggal = $('#tanggal').val();
    
    if (!userId || !tanggal) {
        $('#existingData').html('<p class="text-muted text-center">Pilih pegawai dan tanggal untuk melihat data.</p>');
        return;
    }
    
    $.ajax({
        url: '<?= base_url("absensi/checkExistingLog") ?>',
        type: 'GET',
        data: { id_user: userId, tanggal: tanggal },
        dataType: 'json',
        success: function(res) {
            if (res.status && res.data) {
                var d = res.data;
                var html = '<div class="alert alert-info mb-0">';
                html += '<strong>Data Ditemukan!</strong><br>';
                html += 'Masuk: ' + (d.jam_masuk ? d.jam_masuk.substring(11, 16) : '-') + '<br>';
                html += 'Pulang: ' + (d.jam_pulang ? d.jam_pulang.substring(11, 16) : '-') + '<br>';
                html += 'Status: <span class="badge badge-primary">' + d.status_kehadiran + '</span><br>';
                if (d.is_manual_entry == 1) {
                    html += '<small class="text-warning"><i class="fas fa-edit"></i> Input manual</small>';
                }
                html += '</div>';
                $('#existingData').html(html);
            } else {
                $('#existingData').html('<div class="alert alert-secondary mb-0"><i class="fas fa-info-circle"></i> Belum ada data absensi untuk tanggal ini.</div>');
            }
        }
    });
}

function loadUserShift() {
    var userId = $('#id_user').val();
    var tanggal = $('#tanggal').val();
    
    if (!userId) return;
    
    $.ajax({
        url: '<?= base_url("absensi/getUserShift") ?>',
        type: 'GET',
        data: { id_user: userId, tanggal: tanggal },
        dataType: 'json',
        success: function(res) {
            if (res.status && res.data) {
                $('#id_shift').val(res.data.id_shift);
            }
        }
    });
}
</script>
