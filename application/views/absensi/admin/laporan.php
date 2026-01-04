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
            <!-- Filter -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filter Laporan</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Bulan</label>
                                <select class="form-control" id="filterBulan">
                                    <?php 
                                    $bulan_names = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                                    for ($i = 1; $i <= 12; $i++): 
                                    ?>
                                    <option value="<?= sprintf('%02d', $i) ?>" <?= date('m') == $i ? 'selected' : '' ?>><?= $bulan_names[$i] ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Tahun</label>
                                <select class="form-control" id="filterTahun">
                                    <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                    <option value="<?= $y ?>"><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="button" class="btn btn-primary" id="btnGenerate">
                                        <i class="fas fa-search"></i> Tampilkan
                                    </button>
                                    <button type="button" class="btn btn-success" id="btnExport">
                                        <i class="fas fa-file-excel"></i> Export Excel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Result -->
            <div class="card" id="cardResult" style="display: none;">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-table mr-1"></i> Rekap Absensi</h3>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped table-sm" id="tableRekap">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th class="text-center bg-success text-white">Hadir</th>
                                <th class="text-center bg-warning">Terlambat</th>
                                <th class="text-center bg-danger text-white">Alpha</th>
                                <th class="text-center bg-info text-white">Izin</th>
                                <th class="text-center bg-info text-white">Sakit</th>
                                <th class="text-center bg-info text-white">Cuti</th>
                                <th class="text-center">Total Hari</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyRekap"></tbody>
                    </table>
                </div>
            </div>

            <!-- Loading -->
            <div class="text-center py-5" id="loadingIndicator" style="display: none;">
                <i class="fas fa-spinner fa-spin fa-3x"></i>
                <p class="mt-2">Memuat data...</p>
            </div>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    $('#btnGenerate').on('click', function() {
        var bulan = $('#filterBulan').val();
        var tahun = $('#filterTahun').val();
        
        $('#cardResult').hide();
        $('#loadingIndicator').show();
        
        $.ajax({
            url: '<?= base_url("absensi/rekapBulanan") ?>',
            type: 'GET',
            data: { bulan: bulan, tahun: tahun },
            dataType: 'json',
            success: function(res) {
                $('#loadingIndicator').hide();
                if (res.status && res.data) {
                    renderTable(res.data);
                    $('#cardResult').show();
                } else {
                    Swal.fire('Info', 'Tidak ada data untuk periode ini', 'info');
                }
            },
            error: function() {
                $('#loadingIndicator').hide();
                Swal.fire('Error', 'Gagal memuat data', 'error');
            }
        });
    });

    $('#btnExport').on('click', function() {
        var bulan = $('#filterBulan').val();
        var tahun = $('#filterTahun').val();
        window.location.href = '<?= base_url("absensi/exportExcel") ?>?bulan=' + bulan + '&tahun=' + tahun;
    });
});

function renderTable(data) {
    var html = '';
    var no = 1;
    
    data.forEach(function(row) {
        var hadir = parseInt(row.hadir) || 0;
        var terlambat = parseInt(row.terlambat) || 0;
        var alpha = parseInt(row.alpha) || 0;
        var izin = parseInt(row.izin) || 0;
        var sakit = parseInt(row.sakit) || 0;
        var cuti = parseInt(row.cuti) || 0;
        var total = hadir + terlambat + alpha + izin + sakit + cuti;
        
        html += '<tr>';
        html += '<td>' + no++ + '</td>';
        html += '<td>' + (row.nama_user || row.username) + '</td>';
        html += '<td class="text-center">' + hadir + '</td>';
        html += '<td class="text-center">' + terlambat + '</td>';
        html += '<td class="text-center">' + alpha + '</td>';
        html += '<td class="text-center">' + izin + '</td>';
        html += '<td class="text-center">' + sakit + '</td>';
        html += '<td class="text-center">' + cuti + '</td>';
        html += '<td class="text-center font-weight-bold">' + total + '</td>';
        html += '</tr>';
    });
    
    if (html === '') {
        html = '<tr><td colspan="9" class="text-center text-muted">Tidak ada data</td></tr>';
    }
    
    $('#tbodyRekap').html(html);
}
</script>
