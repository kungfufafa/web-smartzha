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
                <div class="card-header">
                    <h6 class="card-title"><?= $subjudul ?></h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label>Tanggal Mulai:</label>
                            <input type="date" class="form-control" id="start-date" value="<?= $start_date ?>">
                        </div>
                        <div class="col-md-4">
                            <label>Tanggal Selesai:</label>
                            <input type="date" class="form-control" id="end-date" value="<?= $end_date ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" class="btn btn-primary" onclick="filterHistory()">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>
                    
                    <?php if (empty($logs)): ?>
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-calendar-times fa-3x mb-3"></i>
                            <h4>Tidak Ada Data</h4>
                            <p class="mb-0">Tidak ada riwayat presensi untuk periode ini</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Shift</th>
                                        <th>Jam Masuk</th>
                                        <th>Jam Pulang</th>
                                        <th>Status</th>
                                        <th>Telat (menit)</th>
                                        <th>Metode Masuk</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($log->tanggal)) ?></td>
                                        <td><?= $log->nama_shift ?></td>
                                        <td>
                                            <?php if ($log->jam_masuk): ?>
                                                <?= date('H:i:s', strtotime($log->jam_masuk)) ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($log->jam_pulang): ?>
                                                <?= date('H:i:s', strtotime($log->jam_pulang)) ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= $log->status_kehadiran === 'Hadir' ? 'success' : 'warning' ?>">
                                                <?= $log->status_kehadiran ?>
                                            </span>
                                        </td>
                                        <td><?= $log->terlambat_menit ?></td>
                                        <td>
                                            <?php if ($log->metode_masuk): ?>
                                                <i class="fas fa-<?= $log->metode_masuk === 'gps' ? 'map-marker-alt' : ($log->metode_masuk === 'qr' ? 'qrcode' : 'hand-pointer') ?>"></i>
                                                <?= ucfirst($log->metode_masuk) ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
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

<script>
function filterHistory() {
    var startDate = document.getElementById('start-date').value;
    var endDate = document.getElementById('end-date').value;
    
    window.location.href = '<?= base_url('presensi/history') ?>?start_date=' + startDate + '&end_date=' + endDate;
}
</script>
