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
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-map-marker-alt mr-1"></i> Daftar Lokasi</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalLokasi" onclick="resetForm()">
                            <i class="fas fa-plus"></i> Tambah Lokasi
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tableLokasi" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Lokasi</th>
                                <th>Kode</th>
                                <th>Alamat</th>
                                <th>Koordinat</th>
                                <th>Radius (m)</th>
                                <th>Default</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($locations as $loc): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= $loc->nama_lokasi ?></td>
                                <td><code><?= $loc->kode_lokasi ?></code></td>
                                <td><?= $loc->alamat ?></td>
                                <td><small><?= $loc->latitude ?>, <?= $loc->longitude ?></small></td>
                                <td><?= $loc->radius_meter ?></td>
                                <td>
                                    <?php if ($loc->is_default): ?>
                                    <span class="badge badge-success">Default</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-xs" onclick="editLokasi(<?= htmlspecialchars(json_encode($loc)) ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-xs" onclick="deleteLokasi(<?= $loc->id_lokasi ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Lokasi -->
<div class="modal fade" id="modalLokasi" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLokasiTitle">Tambah Lokasi</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="formLokasi">
                <div class="modal-body">
                    <input type="hidden" name="id_lokasi" id="id_lokasi">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nama Lokasi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nama_lokasi" id="nama_lokasi" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Kode Lokasi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="kode_lokasi" id="kode_lokasi" required style="text-transform: uppercase">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea class="form-control" name="alamat" id="alamat" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Latitude <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="latitude" id="latitude" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Longitude <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="longitude" id="longitude" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Radius (meter)</label>
                                <input type="number" class="form-control" name="radius_meter" id="radius_meter" value="100">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" name="is_default" id="is_default" value="1">
                            <label class="custom-control-label" for="is_default">Jadikan Lokasi Default</label>
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
$(document).ready(function() {
    $('#tableLokasi').DataTable({
        "responsive": true,
        "autoWidth": false
    });

    $('#formLokasi').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&<?= $this->security->get_csrf_token_name() ?>=<?= $this->security->get_csrf_hash() ?>';
        
        $.ajax({
            url: '<?= base_url("absensi/saveLokasi") ?>',
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

function resetForm() {
    $('#formLokasi')[0].reset();
    $('#id_lokasi').val('');
    $('#modalLokasiTitle').text('Tambah Lokasi');
}

function editLokasi(data) {
    $('#modalLokasiTitle').text('Edit Lokasi');
    $('#id_lokasi').val(data.id_lokasi);
    $('#nama_lokasi').val(data.nama_lokasi);
    $('#kode_lokasi').val(data.kode_lokasi);
    $('#alamat').val(data.alamat);
    $('#latitude').val(data.latitude);
    $('#longitude').val(data.longitude);
    $('#radius_meter').val(data.radius_meter);
    $('#is_default').prop('checked', data.is_default == 1);
    $('#modalLokasi').modal('show');
}

function deleteLokasi(id) {
    Swal.fire({
        title: 'Hapus Lokasi?',
        text: 'Data yang dihapus tidak dapat dikembalikan',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Hapus',
        cancelButtonText: 'Batal'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: '<?= base_url("absensi/deleteLokasi") ?>',
                type: 'POST',
                data: {
                    id_lokasi: id,
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
