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
                        <li class="breadcrumb-item active">Dashboard Absensi</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Stats Cards -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= $stats->total_pegawai ?? 0 ?></h3>
                            <p>Total Pegawai</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <a href="<?= base_url('absensi/monitoring') ?>" class="small-box-footer">
                            Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= $stats->sudah_masuk ?? 0 ?></h3>
                            <p>Sudah Check-in</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <a href="#tabel-hari-ini" class="small-box-footer">
                            Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= $stats->terlambat ?? 0 ?></h3>
                            <p>Terlambat</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <a href="#tabel-terlambat" class="small-box-footer">
                            Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= $stats->belum_masuk ?? 0 ?></h3>
                            <p>Belum Check-in</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-user-times"></i>
                        </div>
                        <a href="#tabel-belum-masuk" class="small-box-footer">
                            Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-bolt"></i> Aksi Cepat</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-2 col-6 mb-2">
                                    <a href="<?= base_url('absensi/config') ?>" class="btn btn-outline-primary btn-block">
                                        <i class="fas fa-cog fa-2x mb-1"></i><br>
                                        <small>Konfigurasi</small>
                                    </a>
                                </div>
                                <div class="col-md-2 col-6 mb-2">
                                    <a href="<?= base_url('absensi/lokasi') ?>" class="btn btn-outline-success btn-block">
                                        <i class="fas fa-map-marker-alt fa-2x mb-1"></i><br>
                                        <small>Lokasi</small>
                                    </a>
                                </div>
                                <div class="col-md-2 col-6 mb-2">
                                    <a href="<?= base_url('absensi/shift') ?>" class="btn btn-outline-info btn-block">
                                        <i class="fas fa-clock fa-2x mb-1"></i><br>
                                        <small>Shift</small>
                                    </a>
                                </div>
                                <div class="col-md-2 col-6 mb-2">
                                    <a href="<?= base_url('absensi/qrcode') ?>" class="btn btn-outline-secondary btn-block">
                                        <i class="fas fa-qrcode fa-2x mb-1"></i><br>
                                        <small>QR Code</small>
                                    </a>
                                </div>
                                <div class="col-md-2 col-6 mb-2">
                                    <a href="<?= base_url('absensi/manualEntry') ?>" class="btn btn-outline-warning btn-block">
                                        <i class="fas fa-edit fa-2x mb-1"></i><br>
                                        <small>Input Manual</small>
                                    </a>
                                </div>
                                <div class="col-md-2 col-6 mb-2">
                                    <a href="<?= base_url('absensi/rekap') ?>" class="btn btn-outline-danger btn-block">
                                        <i class="fas fa-chart-bar fa-2x mb-1"></i><br>
                                        <small>Rekap</small>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Approvals -->
            <?php if (($stats->pending_bypass ?? 0) > 0 || ($stats->pending_pengajuan ?? 0) > 0): ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-warning alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h5><i class="icon fas fa-exclamation-triangle"></i> Perlu Persetujuan!</h5>
                        <?php if (($stats->pending_bypass ?? 0) > 0): ?>
                        <a href="<?= base_url('absensi/manageBypass') ?>" class="btn btn-sm btn-warning mr-2">
                            <i class="fas fa-map-marker-alt"></i> <?= $stats->pending_bypass ?> Bypass Request
                        </a>
                        <?php endif; ?>
                        <?php if (($stats->pending_pengajuan ?? 0) > 0): ?>
                        <a href="<?= base_url('absensi/pengajuan/manage') ?>" class="btn btn-sm btn-warning">
                            <i class="fas fa-file-alt"></i> <?= $stats->pending_pengajuan ?> Pengajuan Izin
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row">
                <!-- Recent Activity -->
                <div class="col-md-8">
                    <div class="card" id="tabel-hari-ini">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-list"></i> 
                                Kehadiran Hari Ini - <?= date('d F Y') ?>
                            </h3>
                            <div class="card-tools">
                                <a href="<?= base_url('absensi/monitoring') ?>" class="btn btn-tool">
                                    <i class="fas fa-expand"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Nama</th>
                                            <th>Shift</th>
                                            <th>Masuk</th>
                                            <th>Pulang</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($recent_logs)): ?>
                                            <?php foreach ($recent_logs as $log): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($log->nama_lengkap ?? $log->username) ?></td>
                                                <td><small><?= $log->nama_shift ?? '-' ?></small></td>
                                                <td>
                                                    <?php if ($log->jam_masuk): ?>
                                                    <span class="text-success"><?= date('H:i', strtotime($log->jam_masuk)) ?></span>
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
                                                    $status = $log->status_kehadiran ?? 'Hadir';
                                                    if (strpos($status, 'Terlambat') !== false) $badge = 'badge-warning';
                                                    if ($status == 'Alpha') $badge = 'badge-danger';
                                                    if (in_array($status, ['Izin', 'Sakit', 'Cuti'])) $badge = 'badge-info';
                                                    ?>
                                                    <span class="badge <?= $badge ?>"><?= $status ?></span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">
                                                    Belum ada data absensi hari ini
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php if (count($recent_logs ?? []) > 0): ?>
                        <div class="card-footer text-center">
                            <a href="<?= base_url('absensi/monitoring') ?>">Lihat Semua</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Side Info -->
                <div class="col-md-4">
                    <!-- Late Today -->
                    <div class="card card-warning" id="tabel-terlambat">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-clock"></i> Terlambat Hari Ini</h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php if (!empty($late_today)): ?>
                                    <?php foreach (array_slice($late_today, 0, 5) as $late): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <?= htmlspecialchars($late->nama_lengkap ?? $late->username) ?>
                                            <small class="text-muted d-block"><?= $late->nama_shift ?? '' ?></small>
                                        </span>
                                        <span class="badge badge-warning"><?= $late->terlambat_menit ?> menit</span>
                                    </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="list-group-item text-center text-muted">
                                        <i class="fas fa-check-circle text-success"></i> Tidak ada yang terlambat
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Not Checked In -->
                    <div class="card card-danger" id="tabel-belum-masuk">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-user-times"></i> Belum Check-in</h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php if (!empty($not_checked_in)): ?>
                                    <?php foreach (array_slice($not_checked_in, 0, 5) as $notyet): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <?= htmlspecialchars($notyet->nama_lengkap ?? $notyet->username) ?>
                                            <small class="text-muted d-block"><?= $notyet->nama_shift ?? 'Belum assign' ?></small>
                                        </span>
                                        <?php if (!empty($notyet->jam_masuk)): ?>
                                        <small class="text-muted">Shift: <?= substr($notyet->jam_masuk, 0, 5) ?></small>
                                        <?php endif; ?>
                                    </li>
                                    <?php endforeach; ?>
                                    <?php if (count($not_checked_in) > 5): ?>
                                    <li class="list-group-item text-center">
                                        <a href="<?= base_url('absensi/monitoring?status=belum_masuk') ?>">
                                            +<?= count($not_checked_in) - 5 ?> lainnya
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <li class="list-group-item text-center text-muted">
                                        <i class="fas fa-check-circle text-success"></i> Semua sudah check-in
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-info-circle"></i> Info Hari Ini</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td>Izin/Sakit/Cuti</td>
                                    <td class="text-right"><strong><?= $stats->izin_sakit ?? 0 ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Sudah Pulang</td>
                                    <td class="text-right"><strong><?= $stats->sudah_pulang ?? 0 ?></strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    setInterval(function() {
        location.reload();
    }, 300000);
});
</script>
