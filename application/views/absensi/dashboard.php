<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper bg-white">
    <section class="content-header p-0 d-flex align-items-end"
             style="height: 400px; background: url('<?= base_url('assets/img/wall2.png') ?>')">
        <div class="container-fluid pl-0 pr-0 pb-0 pt-4" style="background-color: rgba(255,255,255,0.7)">
            <div class="row m-0">
                <div class="col-md-3 col-6">
                    <div class="shadow small-box bg-info">
                        <div class="inner">
                            <h5 class="mb-0"><b><?= $total_karyawan; ?></b></h5>
                            <span>Total Karyawan</span>
                        </div>
                        <div class="icon">
                            <i class="fa fa-users" style="top: 5px"></i>
                        </div>
                        <a href="<?= base_url('absensimanager/karyawan') ?>" class="small-box-footer">
                            Detail <i class="fa fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="shadow small-box bg-success">
                        <div class="inner">
                            <h5 class="mb-0"><b><?= $shift_active; ?></b></h5>
                            <span>Shift Aktif</span>
                        </div>
                        <div class="icon">
                            <i class="fa fa-clock-o" style="top: 5px"></i>
                        </div>
                        <a href="<?= base_url('absensimanager/shift') ?>" class="small-box-footer">
                            Detail <i class="fa fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="shadow small-box bg-warning">
                        <div class="inner">
                            <h5 class="mb-0"><b><?= isset($hadir_hari_ini) ? $hadir_hari_ini : 0; ?></b></h5>
                            <span>Hadir Hari Ini</span>
                        </div>
                        <div class="icon">
                            <i class="fa fa-check-square-o" style="top: 5px"></i>
                        </div>
                        <a href="#tabel-absensi" class="small-box-footer">
                            Detail <i class="fa fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="shadow small-box bg-danger">
                        <div class="inner">
                            <h5 class="mb-0"><b><?= isset($terlambat_hari_ini) ? $terlambat_hari_ini : 0; ?></b></h5>
                            <span>Terlambat</span>
                        </div>
                        <div class="icon">
                            <i class="fa fa-exclamation-triangle" style="top: 5px"></i>
                        </div>
                        <a href="#tabel-absensi" class="small-box-footer">
                            Detail <i class="fa fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content mt-4" id="tabel-absensi">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary my-shadow">
                        <div class="card-header">
                            <div class="card-title">Log Kehadiran Hari Ini - <?= date('d F Y') ?></div>
                        </div>
                        <div class="card-body">
                            <?php if (isset($logs_hari_ini) && count($logs_hari_ini) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama</th>
                                            <th>Shift</th>
                                            <th>Jam Masuk</th>
                                            <th>Jam Pulang</th>
                                            <th>Status</th>
                                            <th>Terlambat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; foreach ($logs_hari_ini as $log): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= $log->first_name . ' ' . $log->last_name ?></td>
                                            <td><?= $log->nama_shift ?></td>
                                            <td><?= $log->jam_masuk ? date('H:i', strtotime($log->jam_masuk)) : '-' ?></td>
                                            <td><?= $log->jam_pulang ? date('H:i', strtotime($log->jam_pulang)) : '-' ?></td>
                                            <td>
                                                <?php 
                                                $badge_class = 'badge-success';
                                                if ($log->status_kehadiran == 'Terlambat') $badge_class = 'badge-warning';
                                                if ($log->status_kehadiran == 'Pulang Awal') $badge_class = 'badge-info';
                                                if ($log->status_kehadiran == 'Alpha') $badge_class = 'badge-danger';
                                                ?>
                                                <span class="badge <?= $badge_class ?>"><?= $log->status_kehadiran ?></span>
                                            </td>
                                            <td><?= $log->terlambat_menit > 0 ? $log->terlambat_menit . ' menit' : '-' ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <p class="text-center text-muted">Belum ada data absensi hari ini.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
