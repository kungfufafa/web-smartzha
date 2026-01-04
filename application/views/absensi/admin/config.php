<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $judul ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('absensi') ?>">Absensi</a></li>
                        <li class="breadcrumb-item active"><?= $subjudul ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Group Config -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-users-cog mr-1"></i> Konfigurasi per Grup</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalGroupConfig" onclick="resetGroupForm()">
                            <i class="fas fa-plus"></i> Tambah Konfigurasi
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tableGroupConfig" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Grup</th>
                                <th>Nama Konfigurasi</th>
                                <th>Hari Kerja</th>
                                <th>GPS</th>
                                <th>QR</th>
                                <th>Foto</th>
                                <th>Toleransi</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($group_config)): ?>
                            <?php foreach ($group_config as $gc): ?>
                            <tr>
                                <td><?= isset($gc->group_name) ? $gc->group_name : '-' ?></td>
                                <td><?= $gc->nama_konfigurasi ?></td>
                                <td>
                                    <?php 
                                    $days = json_decode($gc->working_days, true);
                                    $day_names = ['', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
                                    if (is_array($days)) {
                                        $day_labels = array_map(function($d) use ($day_names) { 
                                            return isset($day_names[$d]) ? $day_names[$d] : ''; 
                                        }, $days);
                                        echo implode(', ', array_filter($day_labels));
                                    }
                                    ?>
                                </td>
                                <td><?= $gc->enable_gps ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>' ?></td>
                                <td><?= $gc->enable_qr ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>' ?></td>
                                <td><?= $gc->require_photo ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>' ?></td>
                                <td><?= $gc->toleransi_terlambat ? $gc->toleransi_terlambat . ' menit' : '-' ?></td>
                                <td>
                                    <?php if ($gc->is_active): ?>
                                    <span class="badge badge-success">Aktif</span>
                                    <?php else: ?>
                                    <span class="badge badge-secondary">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-xs" onclick="editGroupConfig(<?= htmlspecialchars(json_encode($gc)) ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-xs" onclick="deleteGroupConfig(<?= $gc->id ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Group Config -->
<div class="modal fade" id="modalGroupConfig" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalGroupTitle">Tambah Konfigurasi Grup</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="formGroupConfig">
                <div class="modal-body">
                    <input type="hidden" name="id" id="gc_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Grup <span class="text-danger">*</span></label>
                                <select class="form-control" name="id_group" id="gc_id_group" required>
                                    <option value="">-- Pilih Grup --</option>
                                    <?php foreach ($groups as $g): ?>
                                    <option value="<?= $g->id ?>"><?= ucfirst($g->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nama Konfigurasi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nama_konfigurasi" id="gc_nama" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Hari Kerja</label>
                        <div class="row">
                            <?php 
                            $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                            foreach ($days as $i => $day): 
                            ?>
                            <div class="col-md-3">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" name="working_days[]" id="day_<?= $i+1 ?>" value="<?= $i+1 ?>" <?= $i < 5 ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="day_<?= $i+1 ?>"><?= $day ?></label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" name="enable_gps" id="gc_enable_gps" value="1" checked>
                                    <label class="custom-control-label" for="gc_enable_gps">GPS Wajib</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" name="enable_qr" id="gc_enable_qr" value="1">
                                    <label class="custom-control-label" for="gc_enable_qr">QR Code</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" name="require_photo" id="gc_require_photo" value="1">
                                    <label class="custom-control-label" for="gc_require_photo">Wajib Foto</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" name="allow_bypass" id="gc_allow_bypass" value="1">
                                    <label class="custom-control-label" for="gc_allow_bypass">Izinkan Bypass</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" name="require_checkout" id="gc_require_checkout" value="1" checked>
                                    <label class="custom-control-label" for="gc_require_checkout">Wajib Checkout</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Toleransi Terlambat (menit)</label>
                                <input type="number" class="form-control" name="toleransi_terlambat" id="gc_toleransi" min="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var isEditMode = false;

$(document).ready(function() {
    $('#tableGroupConfig').DataTable({
        "responsive": true,
        "autoWidth": false
    });

    $('#formGroupConfig').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&<?= $this->security->get_csrf_token_name() ?>=<?= $this->security->get_csrf_hash() ?>';
        
        var url = isEditMode ? '<?= base_url("absensi/updateGroupConfig") ?>' : '<?= base_url("absensi/saveGroupConfig") ?>';
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.status) {
                    Swal.fire('Berhasil', res.message, 'success').then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Terjadi kesalahan server', 'error');
            }
        });
    });
});

function resetGroupForm() {
    isEditMode = false;
    $('#formGroupConfig')[0].reset();
    $('#gc_id').val('');
    $('#modalGroupTitle').text('Tambah Konfigurasi Grup');
    // Reset checkboxes to default
    $('input[name="working_days[]"]').prop('checked', false);
    $('#day_1, #day_2, #day_3, #day_4, #day_5').prop('checked', true);
    $('#gc_enable_gps').prop('checked', true);
    $('#gc_require_checkout').prop('checked', true);
}

function editGroupConfig(data) {
    isEditMode = true;
    $('#modalGroupTitle').text('Edit Konfigurasi Grup');
    $('#gc_id').val(data.id);
    $('#gc_id_group').val(data.id_group);
    $('#gc_nama').val(data.nama_konfigurasi);
    $('#gc_toleransi').val(data.toleransi_terlambat);
    
    // Checkboxes
    $('#gc_enable_gps').prop('checked', data.enable_gps == 1);
    $('#gc_enable_qr').prop('checked', data.enable_qr == 1);
    $('#gc_require_photo').prop('checked', data.require_photo == 1);
    $('#gc_allow_bypass').prop('checked', data.allow_bypass == 1);
    $('#gc_require_checkout').prop('checked', data.require_checkout == 1);
    
    // Working days
    $('input[name="working_days[]"]').prop('checked', false);
    var days = JSON.parse(data.working_days || '[]');
    days.forEach(function(d) {
        $('#day_' + d).prop('checked', true);
    });
    
    $('#modalGroupConfig').modal('show');
}

function deleteGroupConfig(id) {
    Swal.fire({
        title: 'Hapus Konfigurasi?',
        text: 'Data yang dihapus tidak dapat dikembalikan',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Hapus',
        cancelButtonText: 'Batal'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: '<?= base_url("absensi/deleteGroupConfig") ?>',
                type: 'POST',
                data: {
                    id: id,
                    <?= $this->security->get_csrf_token_name() ?>: '<?= $this->security->get_csrf_hash() ?>'
                },
                dataType: 'json',
                success: function(res) {
                    if (res.status) {
                        Swal.fire('Berhasil', res.message, 'success').then(function() {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                }
            });
        }
    });
}
</script>
