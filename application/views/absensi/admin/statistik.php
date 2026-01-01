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
                        <li class="breadcrumb-item active">Statistik</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Filter -->
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2">
                            <label>Bulan</label>
                            <select class="form-control" id="filterBulan">
                                <?php
                                $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                                          'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                                for ($i = 1; $i <= 12; $i++):
                                ?>
                                <option value="<?= $i ?>" <?= $i == date('n') ? 'selected' : '' ?>><?= $bulan[$i] ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Tahun</label>
                            <select class="form-control" id="filterTahun">
                                <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
                                <option value="<?= $y ?>"><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>&nbsp;</label>
                            <button class="btn btn-primary btn-block" id="btnFilter">
                                <i class="fas fa-chart-bar"></i> Tampilkan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3 id="totalHadir">0</h3>
                            <p>Total Hadir</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3 id="totalTerlambat">0</h3>
                            <p>Total Terlambat</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3 id="totalIzin">0</h3>
                            <p>Izin/Sakit/Cuti</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3 id="totalAlpha">0</h3>
                            <p>Total Alpha</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-user-times"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Pie Chart - Status Distribution -->
                <div class="col-md-4">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-chart-pie"></i> Distribusi Status</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="chartStatus" style="height: 300px;"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Line Chart - Daily Trend -->
                <div class="col-md-8">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-chart-line"></i> Tren Kehadiran Harian</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="chartDaily" style="height: 300px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Top Late -->
                <div class="col-md-6">
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-exclamation-triangle"></i> Top 10 Terlambat</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama</th>
                                        <th>Jumlah</th>
                                        <th>Total Menit</th>
                                    </tr>
                                </thead>
                                <tbody id="tableTopLate">
                                    <?php if (!empty($top_late)): ?>
                                        <?php $no = 1; foreach ($top_late as $t): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($t->nama_lengkap) ?></td>
                                            <td><span class="badge badge-warning"><?= $t->jumlah_terlambat ?>x</span></td>
                                            <td><?= $t->total_menit ?> menit</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center text-muted py-3">Tidak ada data</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Top Absent -->
                <div class="col-md-6">
                    <div class="card card-danger">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-user-times"></i> Top 10 Alpha</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama</th>
                                        <th>Jumlah Alpha</th>
                                    </tr>
                                </thead>
                                <tbody id="tableTopAbsent">
                                    <?php if (!empty($top_absent)): ?>
                                        <?php $no = 1; foreach ($top_absent as $a): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($a->nama_lengkap) ?></td>
                                            <td><span class="badge badge-danger"><?= $a->jumlah_alpha ?>x</span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="text-center text-muted py-3">Tidak ada data</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bar Chart - Comparison -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-chart-bar"></i> Perbandingan Kehadiran per Minggu</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="chartWeekly" style="height: 300px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
var chartStatus = null;
var chartDaily = null;
var chartWeekly = null;

var initialData = {
    status: <?= json_encode($statistik_status ?? []) ?>,
    daily: <?= json_encode($statistik_daily ?? []) ?>,
    summary: <?= json_encode($summary ?? (object)['hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'alpha' => 0]) ?>
};

$(document).ready(function() {
    initCharts();
    updateSummary(initialData.summary);

    $('#btnFilter').on('click', function() {
        loadStatistik();
    });
});

function initCharts() {
    var ctxStatus = document.getElementById('chartStatus').getContext('2d');
    var ctxDaily = document.getElementById('chartDaily').getContext('2d');
    var ctxWeekly = document.getElementById('chartWeekly').getContext('2d');

    chartStatus = new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['Hadir', 'Terlambat', 'Izin/Sakit/Cuti', 'Alpha'],
            datasets: [{
                data: getStatusData(initialData.status),
                backgroundColor: ['#28a745', '#ffc107', '#17a2b8', '#dc3545'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    chartDaily = new Chart(ctxDaily, {
        type: 'line',
        data: {
            labels: getDailyLabels(initialData.daily),
            datasets: [{
                label: 'Hadir',
                data: getDailyData(initialData.daily, 'hadir'),
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                fill: true,
                tension: 0.3
            }, {
                label: 'Terlambat',
                data: getDailyData(initialData.daily, 'terlambat'),
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                fill: true,
                tension: 0.3
            }, {
                label: 'Alpha',
                data: getDailyData(initialData.daily, 'alpha'),
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    chartWeekly = new Chart(ctxWeekly, {
        type: 'bar',
        data: {
            labels: ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4', 'Minggu 5'],
            datasets: [{
                label: 'Hadir',
                data: getWeeklyData(initialData.daily, 'hadir'),
                backgroundColor: '#28a745'
            }, {
                label: 'Terlambat',
                data: getWeeklyData(initialData.daily, 'terlambat'),
                backgroundColor: '#ffc107'
            }, {
                label: 'Alpha',
                data: getWeeklyData(initialData.daily, 'alpha'),
                backgroundColor: '#dc3545'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function loadStatistik() {
    var bulan = $('#filterBulan').val();
    var tahun = $('#filterTahun').val();

    $.ajax({
        url: '<?= base_url("absensi/getStatistikData") ?>',
        type: 'GET',
        data: { bulan: bulan, tahun: tahun },
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                updateCharts(res.data);
                updateSummary(res.data.summary);
                updateTables(res.data);
            }
        }
    });
}

function updateCharts(data) {
    chartStatus.data.datasets[0].data = getStatusData(data.status);
    chartStatus.update();

    chartDaily.data.labels = getDailyLabels(data.daily);
    chartDaily.data.datasets[0].data = getDailyData(data.daily, 'hadir');
    chartDaily.data.datasets[1].data = getDailyData(data.daily, 'terlambat');
    chartDaily.data.datasets[2].data = getDailyData(data.daily, 'alpha');
    chartDaily.update();

    chartWeekly.data.datasets[0].data = getWeeklyData(data.daily, 'hadir');
    chartWeekly.data.datasets[1].data = getWeeklyData(data.daily, 'terlambat');
    chartWeekly.data.datasets[2].data = getWeeklyData(data.daily, 'alpha');
    chartWeekly.update();
}

function updateSummary(summary) {
    $('#totalHadir').text(summary.hadir || 0);
    $('#totalTerlambat').text(summary.terlambat || 0);
    $('#totalIzin').text(summary.izin || 0);
    $('#totalAlpha').text(summary.alpha || 0);
}

function updateTables(data) {
    var lateHtml = '';
    if (data.top_late && data.top_late.length > 0) {
        data.top_late.forEach(function(t, i) {
            lateHtml += '<tr><td>' + (i + 1) + '</td><td>' + t.nama_lengkap + '</td>';
            lateHtml += '<td><span class="badge badge-warning">' + t.jumlah_terlambat + 'x</span></td>';
            lateHtml += '<td>' + t.total_menit + ' menit</td></tr>';
        });
    } else {
        lateHtml = '<tr><td colspan="4" class="text-center text-muted py-3">Tidak ada data</td></tr>';
    }
    $('#tableTopLate').html(lateHtml);

    var absentHtml = '';
    if (data.top_absent && data.top_absent.length > 0) {
        data.top_absent.forEach(function(a, i) {
            absentHtml += '<tr><td>' + (i + 1) + '</td><td>' + a.nama_lengkap + '</td>';
            absentHtml += '<td><span class="badge badge-danger">' + a.jumlah_alpha + 'x</span></td></tr>';
        });
    } else {
        absentHtml = '<tr><td colspan="3" class="text-center text-muted py-3">Tidak ada data</td></tr>';
    }
    $('#tableTopAbsent').html(absentHtml);
}

function getStatusData(status) {
    var hadir = 0, terlambat = 0, izin = 0, alpha = 0;
    if (status) {
        status.forEach(function(s) {
            if (s.status_kehadiran == 'Hadir') hadir = parseInt(s.jumlah);
            if (s.status_kehadiran == 'Terlambat' || s.status_kehadiran == 'Terlambat + Pulang Awal') terlambat += parseInt(s.jumlah);
            if (['Izin', 'Sakit', 'Cuti', 'Dinas Luar'].indexOf(s.status_kehadiran) !== -1) izin += parseInt(s.jumlah);
            if (s.status_kehadiran == 'Alpha') alpha = parseInt(s.jumlah);
        });
    }
    return [hadir, terlambat, izin, alpha];
}

function getDailyLabels(daily) {
    if (!daily) return [];
    return daily.map(function(d) {
        var date = new Date(d.tanggal);
        return date.getDate();
    });
}

function getDailyData(daily, field) {
    if (!daily) return [];
    return daily.map(function(d) {
        return parseInt(d[field] || 0);
    });
}

function getWeeklyData(daily, field) {
    var weeks = [0, 0, 0, 0, 0];
    if (!daily) return weeks;
    
    daily.forEach(function(d) {
        var date = new Date(d.tanggal);
        var weekNum = Math.ceil(date.getDate() / 7) - 1;
        if (weekNum >= 0 && weekNum < 5) {
            weeks[weekNum] += parseInt(d[field] || 0);
        }
    });
    return weeks;
}
</script>
