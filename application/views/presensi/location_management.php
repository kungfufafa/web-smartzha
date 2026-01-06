<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="content-wrapper bg-white">
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title"><?= $subjudul ?></h6>
                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#lokasiModal" onclick="clearLokasiForm()">
                        <i class="fas fa-plus"></i> Tambah Lokasi
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($lokasi)): ?>
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-map-marker-alt fa-3x mb-3"></i>
                            <h4>Tidak Ada Lokasi</h4>
                            <p class="mb-0">Belum ada lokasi yang dibuat</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama Lokasi</th>
                                        <th>Alamat</th>
                                        <th>Latitude</th>
                                        <th>Longitude</th>
                                        <th>Radius (meter)</th>
                                        <th>Default</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lokasi as $loc): ?>
                                    <tr>
                                        <td><strong><?= $loc->kode_lokasi ?></strong></td>
                                        <td><?= $loc->nama_lokasi ?></td>
                                        <td><?= $loc->alamat ?></td>
                                        <td><?= $loc->latitude ?></td>
                                        <td><?= $loc->longitude ?></td>
                                        <td><?= $loc->radius_meter ?></td>
                                        <td><?= $loc->is_default ? '<span class="badge badge-success">Ya</span>' : '<span class="badge badge-secondary">Tidak</span>' ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info" onclick="editLokasi(<?= $loc->id_lokasi ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteLokasi(<?= $loc->id_lokasi ?>)">
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

<!-- Lokasi Modal -->
<div class="modal fade" id="lokasiModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="lokasiModalTitle">Tambah Lokasi</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="lokasiForm">
                    <input type="hidden" name="id_lokasi" id="lokasi-id">
                    
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
                    
                    <div class="form-group">
                        <label>Radius (meter)</label>
                        <input type="number" class="form-control" name="radius_meter" id="lokasi-radius" value="100">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_default" id="lokasi-default">
                            Jadikan Lokasi Default
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveLokasi()">Simpan</button>
            </div>
        </div>
    </div>
</div>

<script>
function clearLokasiForm() {
    document.getElementById('lokasi-id').value = '';
    document.getElementById('lokasi-nama').value = '';
    document.getElementById('lokasi-kode').value = '';
    document.getElementById('lokasi-alamat').value = '';
    document.getElementById('lokasi-lat').value = '';
    document.getElementById('lokasi-lng').value = '';
    document.getElementById('lokasi-radius').value = '100';
    document.getElementById('lokasi-default').checked = false;
    document.getElementById('lokasiModalTitle').textContent = 'Tambah Lokasi';
}

function editLokasi(id) {
    var lokasi = <?= json_encode($lokasi) ?>.find(l => l.id_lokasi === id);
    
    if (lokasi) {
        document.getElementById('lokasi-id').value = lokasi.id_lokasi;
        document.getElementById('lokasi-nama').value = lokasi.nama_lokasi;
        document.getElementById('lokasi-kode').value = lokasi.kode_lokasi;
        document.getElementById('lokasi-alamat').value = lokasi.alamat;
        document.getElementById('lokasi-lat').value = lokasi.latitude;
        document.getElementById('lokasi-lng').value = lokasi.longitude;
        document.getElementById('lokasi-radius').value = lokasi.radius_meter;
        document.getElementById('lokasi-default').checked = lokasi.is_default === 1;
        document.getElementById('lokasiModalTitle').textContent = 'Edit Lokasi';
        
        $('#lokasiModal').modal('show');
    }
}

function saveLokasi() {
    var form = document.getElementById('lokasiForm');
    var formData = new FormData(form);
    
    fetch('<?= base_url('presensi/save_location') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.message);
            $('#lokasiModal').modal('hide');
            location.reload();
        } else {
            alert('Gagal menyimpan: ' + result.message);
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan: ' + error.message);
    });
}

function deleteLokasi(id) {
    if (confirm('Apakah Anda yakin ingin menghapus lokasi ini?')) {
        var formData = new FormData();
        formData.append('id_lokasi', id);
        
        fetch('<?= base_url('presensi/delete_location') ?>', {
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
}
</script>
