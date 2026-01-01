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
                        <li class="breadcrumb-item active">Monitoring</li>
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
                        <div class="col-md-3">
                            <div class="form-group mb-0">
                                <label>Tanggal</label>
                                <input type="date" class="form-control" id="filterDate" value="<?= $filter_date ?? date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-0">
                                <label>Status</label>
                                <select class="form-control" id="filterStatus">
                                    <option value="">Semua Status</option>
                                    <option value="Hadir">Hadir</option>
                                    <option value="Terlambat">Terlambat</option>
                                    <option value="Pulang Awal">Pulang Awal</option>
                                    <option value="Izin">Izin</option>
                                    <option value="Sakit">Sakit</option>
                                    <option value="Cuti">Cuti</option>
                                    <option value="Alpha">Alpha</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group mb-0">
                                <label>&nbsp;</label>
                                <button class="btn btn-primary btn-block" id="btnFilter">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group mb-0">
                                <label>&nbsp;</label>
                                <button class="btn btn-success btn-block" id="btnRefresh">
                                    <i class="fas fa-sync"></i> Refresh
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group mb-0">
                                <label>&nbsp;</label>
                                <div class="custom-control custom-switch mt-2">
                                    <input type="checkbox" class="custom-control-input" id="autoRefresh" checked>
                                    <label class="custom-control-label" for="autoRefresh">Auto Refresh</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row">
                <div class="col-md-2 col-6">
                    <div class="info-box bg-info">
                        <span class="info-box-icon"><i class="fas fa-users"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total</span>
                            <span class="info-box-number" id="statTotal"><?= count($logs ?? []) ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="info-box bg-success">
                        <span class="info-box-icon"><i class="fas fa-check"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Hadir</span>
                            <span class="info-box-number" id="statHadir">0</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="info-box bg-warning">
                        <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Terlambat</span>
                            <span class="info-box-number" id="statTerlambat">0</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="info-box bg-primary">
                        <span class="info-box-icon"><i class="fas fa-sign-out-alt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Sudah Pulang</span>
                            <span class="info-box-number" id="statPulang">0</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="info-box bg-secondary">
                        <span class="info-box-icon"><i class="fas fa-file-alt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Izin/Sakit</span>
                            <span class="info-box-number" id="statIzin">0</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="info-box bg-danger">
                        <span class="info-box-icon"><i class="fas fa-times"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Alpha</span>
                            <span class="info-box-number" id="statAlpha">0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> Data Kehadiran 
                        <span id="displayDate"><?= date('d F Y', strtotime($filter_date ?? 'now')) ?></span>
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-light" id="lastUpdate">-</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tableMonitoring" class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NIP</th>
                                    <th>Nama</th>
                                    <th>Shift</th>
                                    <th>Jam Masuk</th>
                                    <th>Jam Pulang</th>
                                    <th>Status</th>
                                    <th>Terlambat</th>
                                    <th>Lokasi</th>
                                    <th>Metode</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <?php if (!empty($logs)): ?>
                                    <?php $no = 1; foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><small><?= htmlspecialchars($log->nip ?? '-') ?></small></td>
                                        <td><?= htmlspecialchars($log->nama_lengkap ?? $log->username) ?></td>
                                        <td><small><?= $log->nama_shift ?? '-' ?></small></td>
                                        <td>
                                            <?php if ($log->jam_masuk): ?>
                                            <span class="text-success font-weight-bold"><?= date('H:i', strtotime($log->jam_masuk)) ?></span>
                                            <?php else: ?>
                                            <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($log->jam_pulang): ?>
                                            <span class="text-info"><?= date('H:i', strtotime($log->jam_pulang)) ?></span>
                                            <?php else: ?>
                                            <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $badge = 'badge-success';
                                            $status = $log->status_kehadiran;
                                            if (strpos($status, 'Terlambat') !== false) $badge = 'badge-warning';
                                            if ($status == 'Pulang Awal') $badge = 'badge-info';
                                            if ($status == 'Alpha') $badge = 'badge-danger';
                                            if (in_array($status, ['Izin', 'Sakit', 'Cuti', 'Dinas Luar'])) $badge = 'badge-secondary';
                                            ?>
                                            <span class="badge <?= $badge ?>"><?= $status ?></span>
                                        </td>
                                        <td>
                                            <?php if ($log->terlambat_menit > 0): ?>
                                            <span class="text-danger"><?= $log->terlambat_menit ?> mnt</span>
                                            <?php else: ?>
                                            <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?= $log->nama_lokasi ?? '-' ?></small></td>
                                        <td>
                                            <?php if ($log->metode_masuk): ?>
                                            <span class="badge badge-light"><?= $log->metode_masuk ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">
                                            Belum ada data absensi
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
var autoRefreshInterval = null;
var dataTable = null;

$(document).ready(function() {
    dataTable = $('#tableMonitoring').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[4, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json'
        }
    });

    updateStats();
    updateLastUpdate();

    if ($('#autoRefresh').is(':checked')) {
        startAutoRefresh();
    }

    $('#autoRefresh').on('change', function() {
        if ($(this).is(':checked')) {
            startAutoRefresh();
        } else {
            stopAutoRefresh();
        }
    });

    $('#btnFilter').on('click', function() {
        loadData();
    });

    $('#btnRefresh').on('click', function() {
        loadData();
    });

    $('#filterDate').on('change', function() {
        loadData();
    });
});

function startAutoRefresh() {
    autoRefreshInterval = setInterval(function() {
        loadData();
    }, 30000);
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

function loadData() {
    var date = $('#filterDate').val();
    var status = $('#filterStatus').val();
    
    $.ajax({
        url: '<?= base_url("absensi/dataMonitoring") ?>',
        type: 'GET',
        data: { date: date, status: status },
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                updateTable(res.data);
                updateStats();
                updateLastUpdate();
                
                var d = new Date(date);
                $('#displayDate').text(d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }));
            }
        }
    });
}

function updateTable(data) {
    dataTable.clear();
    
    data.forEach(function(log, index) {
        var badge = 'badge-success';
        var status = log.status_kehadiran;
        if (status.indexOf('Terlambat') !== -1) badge = 'badge-warning';
        if (status == 'Pulang Awal') badge = 'badge-info';
        if (status == 'Alpha') badge = 'badge-danger';
        if (['Izin', 'Sakit', 'Cuti', 'Dinas Luar'].indexOf(status) !== -1) badge = 'badge-secondary';
        
        var row = [
            index + 1,
            '<small>' + (log.nip || '-') + '</small>',
            log.nama_lengkap || log.username,
            '<small>' + (log.nama_shift || '-') + '</small>',
            log.jam_masuk ? '<span class="text-success font-weight-bold">' + log.jam_masuk.substring(11, 16) + '</span>' : '<span class="text-muted">-</span>',
            log.jam_pulang ? '<span class="text-info">' + log.jam_pulang.substring(11, 16) + '</span>' : '<span class="text-muted">-</span>',
            '<span class="badge ' + badge + '">' + status + '</span>',
            log.terlambat_menit > 0 ? '<span class="text-danger">' + log.terlambat_menit + ' mnt</span>' : '<span class="text-muted">-</span>',
            '<small>' + (log.nama_lokasi || '-') + '</small>',
            log.metode_masuk ? '<span class="badge badge-light">' + log.metode_masuk + '</span>' : ''
        ];
        
        dataTable.row.add(row);
    });
    
    dataTable.draw();
}

function updateStats() {
    var rows = dataTable.rows().data();
    var total = rows.length;
    var hadir = 0, terlambat = 0, pulang = 0, izin = 0, alpha = 0;
    
    rows.each(function(row) {
        var status = $(row[6]).text();
        if (status == 'Hadir') hadir++;
        if (status.indexOf('Terlambat') !== -1) terlambat++;
        if (row[5].indexOf('text-info') !== -1) pulang++;
        if (['Izin', 'Sakit', 'Cuti', 'Dinas Luar'].indexOf(status) !== -1) izin++;
        if (status == 'Alpha') alpha++;
    });
    
    $('#statTotal').text(total);
    $('#statHadir').text(hadir);
    $('#statTerlambat').text(terlambat);
    $('#statPulang').text(pulang);
    $('#statIzin').text(izin);
    $('#statAlpha').text(alpha);
}

function updateLastUpdate() {
    var now = new Date();
    $('#lastUpdate').text('Update: ' + now.toLocaleTimeString('id-ID'));
}
</script>
