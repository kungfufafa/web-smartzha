<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $judul ?></h1>
                </div>
                <div class="col-sm-6">
                    <button type="button" class="btn btn-primary float-right" data-toggle="modal" data-target="#modalLokasi">
                        <i class="fa fa-plus"></i> Tambah Lokasi
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Daftar Lokasi Kantor</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tableLokasi" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th width="5%">No</th>
                                            <th>Nama Lokasi</th>
                                            <th>Kode</th>
                                            <th>Alamat</th>
                                            <th>Radius</th>
                                            <th>Default</th>
                                            <th width="15%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; foreach ($locations as $loc) : ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($loc->nama_lokasi) ?></td>
                                            <td><code><?= htmlspecialchars($loc->kode_lokasi) ?></code></td>
                                            <td><?= htmlspecialchars($loc->alamat) ?></td>
                                            <td><?= $loc->radius_meter ?> m</td>
                                            <td>
                                                <?php if ($loc->is_default): ?>
                                                <span class="badge badge-success"><i class="fa fa-check"></i> Ya</span>
                                                <?php else: ?>
                                                <span class="badge badge-secondary">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info btn-view" 
                                                    data-lat="<?= $loc->latitude ?>" 
                                                    data-lng="<?= $loc->longitude ?>"
                                                    data-name="<?= htmlspecialchars($loc->nama_lokasi) ?>"
                                                    data-radius="<?= $loc->radius_meter ?>"
                                                    title="Lihat Peta">
                                                    <i class="fa fa-map-marker-alt"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning btn-edit" 
                                                    data-id="<?= $loc->id_lokasi ?>" 
                                                    data-json='<?= json_encode($loc) ?>'
                                                    title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                <?php if (!$loc->is_default): ?>
                                                <button class="btn btn-sm btn-danger btn-delete" 
                                                    data-id="<?= $loc->id_lokasi ?>"
                                                    title="Hapus">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fa fa-map"></i> Preview Lokasi</h3>
                        </div>
                        <div class="card-body p-0">
                            <div id="mapPreview" style="height: 400px; background: #eee;">
                                <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                    <div class="text-center">
                                        <i class="fa fa-map-marker-alt fa-3x mb-2"></i>
                                        <p>Klik tombol <i class="fa fa-map-marker-alt"></i> untuk melihat lokasi</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer" id="mapInfo" style="display: none;">
                            <strong id="mapName">-</strong><br>
                            <small class="text-muted">
                                <span id="mapCoords">-</span><br>
                                Radius: <span id="mapRadius">-</span> meter
                            </small>
                        </div>
                    </div>

                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fa fa-info-circle"></i> Informasi</h3>
                        </div>
                        <div class="card-body">
                            <p class="mb-2"><strong>Radius</strong>: Area valid untuk check-in/check-out GPS.</p>
                            <p class="mb-2"><strong>Default</strong>: Lokasi utama yang digunakan jika tidak ada lokasi spesifik.</p>
                            <p class="mb-0"><strong>Kode</strong>: Identifier unik untuk integrasi sistem.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Lokasi -->
<div class="modal fade" id="modalLokasi" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">Data Lokasi Kantor</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <?= form_open('absensi/saveLokasi', ['id' => 'formLokasi']) ?>
            <div class="modal-body">
                <input type="hidden" name="id_lokasi" id="id_lokasi">
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Nama Lokasi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_lokasi" id="nama_lokasi" required placeholder="Contoh: Kantor Pusat">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Kode Lokasi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="kode_lokasi" id="kode_lokasi" required placeholder="HQ" maxlength="20" style="text-transform: uppercase;">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Alamat</label>
                    <textarea class="form-control" name="alamat" id="alamat" rows="2" placeholder="Jl. Contoh No. 123, Kota"></textarea>
                </div>

                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Latitude <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="latitude" id="latitude" required placeholder="-6.17539200">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Longitude <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="longitude" id="longitude" required placeholder="106.82715300">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-info btn-block" id="btnGetLocation" title="Ambil lokasi saat ini">
                                <i class="fa fa-crosshairs"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> 
                    Klik tombol <i class="fa fa-crosshairs"></i> untuk mengambil koordinat lokasi saat ini, 
                    atau masukkan koordinat secara manual.
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Radius (meter) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="radius_meter" id="radius_meter" required value="100" min="10" max="1000">
                            <small class="text-muted">Jarak maksimal dari titik koordinat untuk check-in valid</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="custom-control custom-switch mt-2">
                                <input type="checkbox" class="custom-control-input" id="is_default" name="is_default" value="1">
                                <label class="custom-control-label" for="is_default">Jadikan lokasi default</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="mapModal" style="height: 250px; background: #eee; border-radius: 4px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> Simpan
                </button>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<script>
var mapPreview = null;
var mapModal = null;
var markerPreview = null;
var circlePreview = null;
var markerModal = null;
var circleModal = null;

$(document).ready(function() {
    $('#tableLokasi').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json'
        }
    });

    $('.btn-view').on('click', function() {
        var lat = parseFloat($(this).data('lat'));
        var lng = parseFloat($(this).data('lng'));
        var name = $(this).data('name');
        var radius = parseInt($(this).data('radius'));
        
        showPreviewMap(lat, lng, name, radius);
    });

    $('.btn-edit').on('click', function() {
        var data = $(this).data('json');
        $('#id_lokasi').val(data.id_lokasi);
        $('#nama_lokasi').val(data.nama_lokasi);
        $('#kode_lokasi').val(data.kode_lokasi);
        $('#alamat').val(data.alamat);
        $('#latitude').val(data.latitude);
        $('#longitude').val(data.longitude);
        $('#radius_meter').val(data.radius_meter);
        $('#is_default').prop('checked', data.is_default == 1);
        $('#modalLokasi').modal('show');
    });

    $('#modalLokasi').on('shown.bs.modal', function() {
        setTimeout(function() {
            initModalMap();
        }, 300);
    });

    $('#modalLokasi').on('hidden.bs.modal', function() {
        $('#formLokasi')[0].reset();
        $('#id_lokasi').val('');
        if (mapModal) {
            mapModal.remove();
            mapModal = null;
        }
    });

    $('#btnGetLocation').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
        
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                $('#latitude').val(position.coords.latitude.toFixed(8));
                $('#longitude').val(position.coords.longitude.toFixed(8));
                updateModalMap();
                btn.prop('disabled', false).html('<i class="fa fa-crosshairs"></i>');
            }, function(error) {
                toastr.error('Gagal mengambil lokasi: ' + error.message);
                btn.prop('disabled', false).html('<i class="fa fa-crosshairs"></i>');
            });
        } else {
            toastr.error('Browser tidak mendukung geolocation');
            btn.prop('disabled', false).html('<i class="fa fa-crosshairs"></i>');
        }
    });

    $('#latitude, #longitude, #radius_meter').on('change', function() {
        updateModalMap();
    });

    $('#formLokasi').on('submit', function(e) {
        e.preventDefault();
        
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
        
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
                    }, 1000);
                } else {
                    toastr.error(res.message);
                    btn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan');
                }
            },
            error: function() {
                toastr.error('Terjadi kesalahan sistem');
                btn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan');
            }
        });
    });

    $('.btn-delete').on('click', function() {
        var id = $(this).data('id');
        
        Swal.fire({
            title: 'Hapus Lokasi?',
            text: 'Lokasi akan dinonaktifkan dan tidak bisa digunakan lagi.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= base_url("absensi/deleteLokasi") ?>',
                    type: 'POST',
                    data: {
                        id_lokasi: id,
                        '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>'
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status) {
                            toastr.success(res.message);
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            toastr.error(res.message);
                        }
                    },
                    error: function() {
                        toastr.error('Terjadi kesalahan sistem');
                    }
                });
            }
        });
    });
});

function showPreviewMap(lat, lng, name, radius) {
    $('#mapPreview').html('');
    $('#mapInfo').show();
    $('#mapName').text(name);
    $('#mapCoords').text(lat.toFixed(6) + ', ' + lng.toFixed(6));
    $('#mapRadius').text(radius);
    
    mapPreview = L.map('mapPreview').setView([lat, lng], 17);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(mapPreview);
    
    markerPreview = L.marker([lat, lng]).addTo(mapPreview)
        .bindPopup('<b>' + name + '</b><br>Radius: ' + radius + 'm')
        .openPopup();
    
    circlePreview = L.circle([lat, lng], {
        color: 'blue',
        fillColor: '#30f',
        fillOpacity: 0.2,
        radius: radius
    }).addTo(mapPreview);
}

function initModalMap() {
    var lat = parseFloat($('#latitude').val()) || -6.175392;
    var lng = parseFloat($('#longitude').val()) || 106.827153;
    var radius = parseInt($('#radius_meter').val()) || 100;
    
    if (mapModal) {
        mapModal.remove();
    }
    
    mapModal = L.map('mapModal').setView([lat, lng], 17);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(mapModal);
    
    markerModal = L.marker([lat, lng], {draggable: true}).addTo(mapModal);
    circleModal = L.circle([lat, lng], {
        color: 'red',
        fillColor: '#f03',
        fillOpacity: 0.2,
        radius: radius
    }).addTo(mapModal);
    
    markerModal.on('dragend', function(e) {
        var pos = markerModal.getLatLng();
        $('#latitude').val(pos.lat.toFixed(8));
        $('#longitude').val(pos.lng.toFixed(8));
        circleModal.setLatLng(pos);
    });
    
    mapModal.on('click', function(e) {
        markerModal.setLatLng(e.latlng);
        circleModal.setLatLng(e.latlng);
        $('#latitude').val(e.latlng.lat.toFixed(8));
        $('#longitude').val(e.latlng.lng.toFixed(8));
    });
}

function updateModalMap() {
    if (!mapModal) return;
    
    var lat = parseFloat($('#latitude').val());
    var lng = parseFloat($('#longitude').val());
    var radius = parseInt($('#radius_meter').val()) || 100;
    
    if (lat && lng) {
        var latlng = L.latLng(lat, lng);
        markerModal.setLatLng(latlng);
        circleModal.setLatLng(latlng);
        circleModal.setRadius(radius);
        mapModal.setView(latlng, 17);
    }
}
</script>

<style>
#mapPreview, #mapModal {
    z-index: 1;
}
.modal-body #mapModal {
    z-index: 1;
}
</style>
