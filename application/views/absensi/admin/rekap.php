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
                        <li class="breadcrumb-item active">Rekap</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Tabs -->
            <ul class="nav nav-tabs" id="rekapTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="harian-tab" data-toggle="tab" href="#harian" role="tab">
                        <i class="fas fa-calendar-day"></i> Rekap Harian
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="bulanan-tab" data-toggle="tab" href="#bulanan" role="tab">
                        <i class="fas fa-calendar-alt"></i> Rekap Bulanan
                    </a>
                </li>
            </ul>

            <div class="tab-content" id="rekapTabContent">
                <!-- Rekap Harian -->
                <div class="tab-pane fade show active" id="harian" role="tabpanel">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-3">
                                    <label>Tanggal</label>
                                    <input type="date" class="form-control" id="filterHarianDate" value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-2">
                                    <label>&nbsp;</label>
                                    <button class="btn btn-primary btn-block" id="btnFilterHarian">
                                        <i class="fas fa-search"></i> Tampilkan
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <label>&nbsp;</label>
                                    <button class="btn btn-success btn-block" id="btnExportHarian">
                                        <i class="fas fa-file-excel"></i> Export
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tableHarian" class="table table-bordered table-striped table-sm">
                                    <thead class="bg-primary">
                                        <tr>
                                            <th>No</th>
                                            <th>NIP</th>
                                            <th>Nama</th>
                                            <th>Shift</th>
                                            <th>Jam Masuk</th>
                                            <th>Jam Pulang</th>
                                            <th>Status</th>
                                            <th>Terlambat</th>
                                            <th>Pulang Awal</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($rekap_harian)): ?>
                                            <?php $no = 1; foreach ($rekap_harian as $r): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= htmlspecialchars($r->nip ?? '-') ?></td>
                                                <td><?= htmlspecialchars($r->nama_lengkap ?? $r->username) ?></td>
                                                <td><?= $r->nama_shift ?? '-' ?></td>
                                                <td><?= $r->jam_masuk ? date('H:i', strtotime($r->jam_masuk)) : '-' ?></td>
                                                <td><?= $r->jam_pulang ? date('H:i', strtotime($r->jam_pulang)) : '-' ?></td>
                                                <td>
                                                    <?php
                                                    $badge = 'badge-success';
                                                    if (strpos($r->status_kehadiran, 'Terlambat') !== false) $badge = 'badge-warning';
                                                    if ($r->status_kehadiran == 'Alpha') $badge = 'badge-danger';
                                                    if (in_array($r->status_kehadiran, ['Izin', 'Sakit', 'Cuti'])) $badge = 'badge-info';
                                                    ?>
                                                    <span class="badge <?= $badge ?>"><?= $r->status_kehadiran ?></span>
                                                </td>
                                                <td><?= $r->terlambat_menit > 0 ? $r->terlambat_menit . ' mnt' : '-' ?></td>
                                                <td><?= $r->pulang_awal_menit > 0 ? $r->pulang_awal_menit . ' mnt' : '-' ?></td>
                                                <td><small><?= htmlspecialchars($r->keterangan ?? '') ?></small></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rekap Bulanan -->
                <div class="tab-pane fade" id="bulanan" role="tabpanel">
                    <div class="card card-info card-outline">
                        <div class="card-header">
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
                                    <button class="btn btn-info btn-block" id="btnFilterBulanan">
                                        <i class="fas fa-search"></i> Tampilkan
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <label>&nbsp;</label>
                                    <button class="btn btn-success btn-block" id="btnExportBulanan">
                                        <i class="fas fa-file-excel"></i> Export
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tableBulanan" class="table table-bordered table-striped table-sm">
                                    <thead class="bg-info">
                                        <tr>
                                            <th>No</th>
                                            <th>NIP</th>
                                            <th>Nama</th>
                                            <th>Hadir</th>
                                            <th>Terlambat</th>
                                            <th>Alpha</th>
                                            <th>Izin/Sakit/Cuti</th>
                                            <th>Total Menit Terlambat</th>
                                            <th>%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($rekap_bulanan)): ?>
                                            <?php $no = 1; foreach ($rekap_bulanan as $r): ?>
                                            <?php
                                            $total = $r->hadir + $r->terlambat + $r->alpha + $r->izin_total;
                                            $persen = $total > 0 ? round((($r->hadir + $r->terlambat) / $total) * 100, 1) : 0;
                                            ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= htmlspecialchars($r->nip ?? '-') ?></td>
                                                <td><?= htmlspecialchars($r->nama_lengkap) ?></td>
                                                <td class="text-center"><span class="badge badge-success"><?= $r->hadir ?></span></td>
                                                <td class="text-center"><span class="badge badge-warning"><?= $r->terlambat ?></span></td>
                                                <td class="text-center"><span class="badge badge-danger"><?= $r->alpha ?></span></td>
                                                <td class="text-center"><span class="badge badge-info"><?= $r->izin_total ?></span></td>
                                                <td class="text-center"><?= $r->total_menit_terlambat ?></td>
                                                <td>
                                                    <div class="progress progress-sm">
                                                        <div class="progress-bar bg-success" style="width: <?= $persen ?>%"></div>
                                                    </div>
                                                    <small><?= $persen ?>%</small>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    $('#tableHarian').DataTable({
        responsive: true,
        pageLength: 25,
        dom: 'Bfrtip',
        buttons: ['copy', 'excel', 'pdf', 'print'],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json'
        }
    });

    $('#tableBulanan').DataTable({
        responsive: true,
        pageLength: 25,
        dom: 'Bfrtip',
        buttons: ['copy', 'excel', 'pdf', 'print'],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json'
        }
    });

    $('#btnFilterHarian').on('click', function() {
        var date = $('#filterHarianDate').val();
        window.location.href = '<?= base_url("absensi/rekap") ?>?date=' + date + '#harian';
    });

    $('#btnFilterBulanan').on('click', function() {
        var bulan = $('#filterBulan').val();
        var tahun = $('#filterTahun').val();
        window.location.href = '<?= base_url("absensi/rekap") ?>?bulan=' + bulan + '&tahun=' + tahun + '#bulanan';
    });

    $('#btnExportHarian').on('click', function() {
        var date = $('#filterHarianDate').val();
        window.location.href = '<?= base_url("absensi/exportExcel") ?>?type=harian&date=' + date;
    });

    $('#btnExportBulanan').on('click', function() {
        var bulan = $('#filterBulan').val();
        var tahun = $('#filterTahun').val();
        window.location.href = '<?= base_url("absensi/exportExcel") ?>?type=bulanan&bulan=' + bulan + '&tahun=' + tahun;
    });

    if (window.location.hash === '#bulanan') {
        $('#bulanan-tab').tab('show');
    }
});
</script>
