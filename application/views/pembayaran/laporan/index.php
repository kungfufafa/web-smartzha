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

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-day"></i> Laporan Harian</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Pilih Tanggal</label>
                    <input type="date" id="tanggalHarian" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <button type="button" class="btn btn-primary btn-block" onclick="loadLaporanHarian()">
                    <i class="fas fa-search"></i> Lihat Laporan
                </button>
                <hr>
                <div id="resultHarian">
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-chart-line fa-3x mb-2"></i>
                        <p>Pilih tanggal dan klik "Lihat Laporan"</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exclamation-triangle"></i> Laporan Tunggakan</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Filter Kelas</label>
                    <select id="filterKelas" class="form-control">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($kelas as $k): ?>
                            <option value="<?= $k->id_kelas ?>"><?= $k->nama_kelas ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="btn btn-warning btn-block" onclick="loadLaporanTunggakan()">
                    <i class="fas fa-search"></i> Lihat Tunggakan
                </button>
                <hr>
                <div id="resultTunggakan">
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-money-bill-wave fa-3x mb-2"></i>
                        <p>Klik "Lihat Tunggakan" untuk melihat data</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

        </div>
    </section>

<script>
function loadLaporanHarian() {
    var tanggal = $('#tanggalHarian').val();
    $('#resultHarian').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');

    $.get('<?= base_url('pembayaran/laporanHarian') ?>', {tanggal: tanggal}, function(response) {
        if (response.status) {
            var html = '<div class="alert alert-info"><strong>Tanggal:</strong> ' + response.tanggal + '</div>';
            
            if (response.data.length > 0) {
                html += '<table class="table table-sm table-bordered">';
                html += '<thead><tr><th>Kode</th><th>Siswa</th><th>Jenis</th><th>Nominal</th></tr></thead>';
                html += '<tbody>';
                response.data.forEach(function(row) {
                    html += '<tr>';
                    html += '<td>' + row.kode_transaksi + '</td>';
                    html += '<td>' + row.nama_siswa + '</td>';
                    html += '<td>' + row.nama_jenis + '</td>';
                    html += '<td>Rp ' + parseInt(row.nominal_bayar).toLocaleString('id-ID') + '</td>';
                    html += '</tr>';
                });
                html += '</tbody>';
                html += '<tfoot><tr class="bg-success text-white"><td colspan="3"><strong>TOTAL</strong></td>';
                html += '<td><strong>Rp ' + parseInt(response.total).toLocaleString('id-ID') + '</strong></td></tr></tfoot>';
                html += '</table>';
            } else {
                html += '<div class="text-center text-muted py-3"><p>Tidak ada pembayaran yang diverifikasi pada tanggal ini</p></div>';
            }
            
            $('#resultHarian').html(html);
        }
    });
}

function loadLaporanTunggakan() {
    var id_kelas = $('#filterKelas').val();
    $('#resultTunggakan').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');

    $.get('<?= base_url('pembayaran/laporanTunggakan') ?>', {id_kelas: id_kelas}, function(response) {
        if (response.status) {
            var html = '';
            
            if (response.data.length > 0) {
                html += '<table class="table table-sm table-bordered">';
                html += '<thead><tr><th>Siswa</th><th>Kelas</th><th>Tagihan</th><th>Total</th></tr></thead>';
                html += '<tbody>';
                response.data.forEach(function(row) {
                    html += '<tr>';
                    html += '<td>' + row.nama + '</td>';
                    html += '<td>' + row.nama_kelas + '</td>';
                    html += '<td>' + row.jumlah_tagihan + ' tagihan</td>';
                    html += '<td class="text-danger">Rp ' + parseInt(row.total).toLocaleString('id-ID') + '</td>';
                    html += '</tr>';
                });
                html += '</tbody>';
                html += '<tfoot><tr class="bg-danger text-white"><td colspan="3"><strong>TOTAL TUNGGAKAN</strong></td>';
                html += '<td><strong>Rp ' + parseInt(response.total).toLocaleString('id-ID') + '</strong></td></tr></tfoot>';
                html += '</table>';
            } else {
                html += '<div class="text-center text-success py-3"><i class="fas fa-check-circle fa-2x mb-2"></i><p>Tidak ada tunggakan</p></div>';
            }
            
            $('#resultTunggakan').html(html);
        }
    });
}
</script>
