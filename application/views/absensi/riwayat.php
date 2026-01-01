<!-- Content Wrapper -->
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1><?= $judul ?></h1>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Filter -->
            <div class="card collapsed-card">
                <div class="card-header">
                    <h3 class="card-title">Filter Periode</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fa fa-plus"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <form action="" method="get">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Bulan</label>
                                    <select class="form-control" name="bulan">
                                        <?php for($i=1; $i<=12; $i++): ?>
                                            <option value="<?= sprintf('%02d', $i) ?>" <?= $bulan == sprintf('%02d', $i) ? 'selected' : '' ?>>
                                                <?= date('F', mktime(0, 0, 0, $i, 10)) ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tahun</label>
                                    <select class="form-control" name="tahun">
                                        <?php for($i=date('Y'); $i>=date('Y')-5; $i--): ?>
                                            <option value="<?= $i ?>" <?= $tahun == $i ? 'selected' : '' ?>><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">Tampilkan</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary -->
            <div class="row">
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-info elevation-1"><i class="fa fa-check"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Hadir</span>
                            <span class="info-box-number"><?= $rekap->hadir ?? 0 ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-danger elevation-1"><i class="fa fa-clock-o"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Terlambat</span>
                            <span class="info-box-number"><?= $rekap->terlambat ?? 0 ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-warning elevation-1"><i class="fa fa-exclamation"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Izin/Sakit</span>
                            <span class="info-box-number"><?= ($rekap->izin ?? 0) + ($rekap->sakit ?? 0) ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-success elevation-1"><i class="fa fa-calendar"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Cuti</span>
                            <span class="info-box-number"><?= $rekap->cuti ?? 0 ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Log Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Riwayat Absensi</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Shift</th>
                                <th>Masuk</th>
                                <th>Pulang</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($logs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada data absensi.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($logs as $log): ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($log->tanggal)) ?></td>
                                    <td><?= $log->nama_shift ?> (<?= substr($log->shift_masuk,0,5) ?>-<?= substr($log->shift_pulang,0,5) ?>)</td>
                                    <td>
                                        <?= $log->jam_masuk ? date('H:i', strtotime($log->jam_masuk)) : '-' ?>
                                        <?php if($log->terlambat_menit > 0): ?>
                                            <span class="badge badge-danger" title="Terlambat <?= $log->terlambat_menit ?> menit">+<?= $log->terlambat_menit ?>m</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $log->jam_pulang ? date('H:i', strtotime($log->jam_pulang)) : '-' ?>
                                        <?php if($log->pulang_awal_menit > 0): ?>
                                            <span class="badge badge-warning" title="Pulang Awal <?= $log->pulang_awal_menit ?> menit">-<?= $log->pulang_awal_menit ?>m</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $badge = 'secondary';
                                            if($log->status_kehadiran == 'Hadir') $badge = 'success';
                                            if($log->status_kehadiran == 'Terlambat') $badge = 'warning';
                                            if($log->status_kehadiran == 'Alpha') $badge = 'danger';
                                            if(in_array($log->status_kehadiran, ['Izin','Sakit','Cuti'])) $badge = 'info';
                                        ?>
                                        <span class="badge badge-<?= $badge ?>"><?= $log->status_kehadiran ?></span>
                                    </td>
                                    <td><?= $log->keterangan ?></td>
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
