<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $subjudul ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Beranda</a></li>
                        <li class="breadcrumb-item active"><?= $subjudul ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Current Shift Info -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-clock mr-1"></i>
                                Shift Aktif Saat Ini
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if ($current_shift): ?>
                            <div class="d-flex align-items-center">
                                <div class="mr-3">
                                    <i class="fas fa-business-time fa-3x text-primary"></i>
                                </div>
                                <div>
                                    <h4 class="mb-1"><?= $current_shift->nama_shift ?></h4>
                                    <p class="mb-0 text-muted">
                                        <i class="fas fa-sign-in-alt text-success"></i> 
                                        Masuk: <strong><?= date('H:i', strtotime($current_shift->jam_masuk)) ?></strong>
                                        &nbsp;&nbsp;
                                        <i class="fas fa-sign-out-alt text-danger"></i> 
                                        Pulang: <strong><?= date('H:i', strtotime($current_shift->jam_pulang)) ?></strong>
                                    </p>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                                <p class="mb-0">Anda belum memiliki jadwal shift yang diatur.</p>
                                <small>Hubungi administrator untuk pengaturan shift.</small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle mr-1"></i>
                                Informasi
                            </h3>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0">
                                <li>Jadwal shift dapat berubah sewaktu-waktu oleh Admin</li>
                                <li>Pastikan hadir tepat waktu sesuai jam masuk</li>
                                <li>Jika ada kendala, ajukan izin melalui menu <strong>Pengajuan Izin/Cuti</strong></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- This Week Schedule -->
            <div class="row">
                <div class="col-12">
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calendar-week mr-1"></i>
                                Jadwal Minggu Ini 
                                <small class="text-muted">(<?= date('d M', strtotime($start_of_week)) ?> - <?= date('d M Y', strtotime($end_of_week)) ?>)</small>
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th width="15%">Hari</th>
                                            <th width="15%">Tanggal</th>
                                            <th>Shift</th>
                                            <th width="15%">Jam Masuk</th>
                                            <th width="15%">Jam Pulang</th>
                                            <th width="10%">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($schedule_this_week as $day): ?>
                                        <?php 
                                            $is_today = ($day['tanggal'] == date('Y-m-d'));
                                            $is_weekend = in_array($day['hari'], ['Sabtu', 'Minggu']);
                                        ?>
                                        <tr class="<?= $is_today ? 'table-primary' : ($is_weekend ? 'table-secondary' : '') ?>">
                                            <td>
                                                <strong><?= $day['hari'] ?></strong>
                                                <?php if ($is_today): ?>
                                                    <span class="badge badge-info ml-1">Hari ini</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($day['tanggal'])) ?></td>
                                            <td>
                                                <?php if ($day['shift']): ?>
                                                    <span class="badge badge-success"><?= $day['shift']->nama_shift ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= $day['shift'] ? date('H:i', strtotime($day['shift']->jam_masuk)) : '-' ?>
                                            </td>
                                            <td>
                                                <?= $day['shift'] ? date('H:i', strtotime($day['shift']->jam_pulang)) : '-' ?>
                                            </td>
                                            <td>
                                                <?php if ($day['shift']): ?>
                                                    <span class="badge badge-primary">Terjadwal</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Libur</span>
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
            </div>

            <!-- Next Week Schedule -->
            <div class="row">
                <div class="col-12">
                    <div class="card card-outline card-warning collapsed-card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calendar-alt mr-1"></i>
                                Jadwal Minggu Depan 
                                <small class="text-muted">(<?= date('d M', strtotime($start_next_week)) ?> - <?= date('d M Y', strtotime($end_next_week)) ?>)</small>
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0" style="display: none;">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th width="15%">Hari</th>
                                            <th width="15%">Tanggal</th>
                                            <th>Shift</th>
                                            <th width="15%">Jam Masuk</th>
                                            <th width="15%">Jam Pulang</th>
                                            <th width="10%">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($schedule_next_week as $day): ?>
                                        <?php $is_weekend = in_array($day['hari'], ['Sabtu', 'Minggu']); ?>
                                        <tr class="<?= $is_weekend ? 'table-secondary' : '' ?>">
                                            <td><strong><?= $day['hari'] ?></strong></td>
                                            <td><?= date('d/m/Y', strtotime($day['tanggal'])) ?></td>
                                            <td>
                                                <?php if ($day['shift']): ?>
                                                    <span class="badge badge-success"><?= $day['shift']->nama_shift ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= $day['shift'] ? date('H:i', strtotime($day['shift']->jam_masuk)) : '-' ?>
                                            </td>
                                            <td>
                                                <?= $day['shift'] ? date('H:i', strtotime($day['shift']->jam_pulang)) : '-' ?>
                                            </td>
                                            <td>
                                                <?php if ($day['shift']): ?>
                                                    <span class="badge badge-primary">Terjadwal</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Libur</span>
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
            </div>
        </div>
    </section>
</div>
