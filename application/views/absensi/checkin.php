<!-- Content Wrapper -->
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1><?= $judul ?></h1>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <!-- Info Section -->
                <div class="col-md-4">
                    <div class="card card-primary card-outline">
                        <div class="card-body box-profile">
                            <h3 class="profile-username text-center"><?= $user->first_name ?> <?= $user->last_name ?></h3>
                            <p class="text-muted text-center"><?= $shift ? $shift->nama_shift : 'Tidak ada jadwal' ?></p>

                            <ul class="list-group list-group-unbordered mb-3">
                                <li class="list-group-item">
                                    <b>Tanggal</b> <a class="float-right"><?= date('d M Y') ?></a>
                                </li>
                                <li class="list-group-item">
                                    <b>Jam Masuk</b> <a class="float-right"><?= $shift ? substr($shift->jam_masuk, 0, 5) : '-' ?></a>
                                </li>
                                <li class="list-group-item">
                                    <b>Jam Pulang</b> <a class="float-right"><?= $shift ? substr($shift->jam_pulang, 0, 5) : '-' ?></a>
                                </li>
                                <li class="list-group-item">
                                    <b>Status</b> 
                                    <a class="float-right">
                                        <?php if(!$log): ?>
                                            <span class="badge badge-secondary">Belum Absen</span>
                                        <?php elseif($log->jam_masuk && !$log->jam_pulang): ?>
                                            <span class="badge badge-warning">Sudah Check-in</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Selesai (Pulang)</span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            </ul>

                            <?php if($shift): ?>
                                <?php if(!$log): ?>
                                    <button onclick="doCheckIn()" class="btn btn-primary btn-block"><b>CHECK-IN</b></button>
                                <?php elseif($log->jam_masuk && !$log->jam_pulang): ?>
                                    <button onclick="doCheckOut()" class="btn btn-danger btn-block"><b>CHECK-OUT</b></button>
                                <?php else: ?>
                                    <button disabled class="btn btn-secondary btn-block"><b>SELESAI</b></button>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-warning">Anda tidak memiliki jadwal shift hari ini.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Map Section -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Lokasi Anda</h3>
                        </div>
                        <div class="card-body">
                            <div id="map" style="height: 400px; width: 100%;"></div>
                            <input type="hidden" id="lat">
                            <input type="hidden" id="lng">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    var map, marker;

    $(document).ready(function() {
        initMap();
        getLocation();
    });

    function initMap() {
        // Default to Jakarta
        map = L.map('map').setView([-6.200000, 106.816666], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
    }

    function getLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(showPosition, showError);
        } else { 
            alert("Geolocation is not supported by this browser.");
        }
    }

    function showPosition(position) {
        var lat = position.coords.latitude;
        var lng = position.coords.longitude;
        
        $('#lat').val(lat);
        $('#lng').val(lng);

        var latlng = [lat, lng];
        
        if (marker) {
            marker.setLatLng(latlng);
        } else {
            marker = L.marker(latlng).addTo(map);
        }
        
        map.setView(latlng, 16);
    }

    function showError(error) {
        switch(error.code) {
            case error.PERMISSION_DENIED:
                alert("User denied the request for Geolocation.")
                break;
            case error.POSITION_UNAVAILABLE:
                alert("Location information is unavailable.")
                break;
            case error.TIMEOUT:
                alert("The request to get user location timed out.")
                break;
            case error.UNKNOWN_ERROR:
                alert("An unknown error occurred.")
                break;
        }
    }

    function doCheckIn() {
        var lat = $('#lat').val();
        var lng = $('#lng').val();

        if(!lat || !lng) {
            Swal.fire('Error', 'Lokasi belum terdeteksi. Pastikan GPS aktif.', 'error');
            return;
        }

        $.ajax({
            url: '<?= base_url("absensi/do_checkin") ?>',
            type: 'POST',
            data: {
                lat: lat, 
                lng: lng,
                '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>'
            },
            success: function(res) {
                if(res.status) {
                    Swal.fire('Sukses', res.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Terjadi kesalahan server', 'error');
            }
        });
    }

    function doCheckOut() {
        var lat = $('#lat').val();
        var lng = $('#lng').val();

        if(!lat || !lng) {
            Swal.fire('Error', 'Lokasi belum terdeteksi. Pastikan GPS aktif.', 'error');
            return;
        }

        Swal.fire({
            title: 'Konfirmasi',
            text: "Apakah anda yakin ingin Check-out sekarang?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Check-out'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= base_url("absensi/do_checkout") ?>',
                    type: 'POST',
                    data: {
                        lat: lat, 
                        lng: lng,
                        '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>'
                    },
                    success: function(res) {
                        if(res.status) {
                            Swal.fire('Sukses', res.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Gagal', res.message, 'error');
                        }
                    }
                });
            }
        })
    }
</script>
