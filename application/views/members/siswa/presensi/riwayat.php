<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper" style="margin-top: -1px;">
    <div class="sticky"></div>
    <section class="content overlap p-4">
        <div class="container">
            <?php $this->load->view('members/siswa/templates/top'); ?>

            <div class="row">
                <div class="col-12">
                    <div class="card card-success">
                        <div class="card-header">
                            <div class="card-title text-white">
                                <i class="fas fa-history mr-2"></i><?= $subjudul ?>
                            </div>
                            <div class="card-tools">
                                <form method="get" class="form-inline">
                                    <select name="month" class="form-control form-control-sm mr-1">
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?= sprintf('%02d', $m) ?>" <?= $month == sprintf('%02d', $m) ? 'selected' : '' ?>>
                                                <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                    <select name="year" class="form-control form-control-sm mr-1">
                                        <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
                                            <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <button type="submit" class="btn btn-light btn-sm">Filter</button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="bg-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Jam Masuk</th>
                                        <th>Jam Pulang</th>
                                        <th>Status</th>
                                        <th>Keterangan</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($logs)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Tidak ada data untuk bulan ini</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($logs as $log): ?>
                                            <tr>
                                                <td><?= date('d/m/Y', strtotime($log->tanggal)) ?></td>
                                                <td><?= $log->jam_masuk ? date('H:i', strtotime($log->jam_masuk)) : '-' ?></td>
                                                <td><?= $log->jam_pulang ? date('H:i', strtotime($log->jam_pulang)) : '-' ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = [
                                                        'Hadir' => 'success',
                                                        'Terlambat' => 'warning',
                                                        'Alpha' => 'danger',
                                                        'Izin' => 'info',
                                                        'Sakit' => 'secondary',
                                                        'Cuti' => 'primary',
                                                        'Dinas Luar' => 'dark',
                                                        'Pulang Awal' => 'warning'
                                                    ];
                                                    $class = $status_class[$log->status_kehadiran] ?? 'secondary';
                                                    ?>
                                                    <span class="badge badge-<?= $class ?>"><?= $log->status_kehadiran ?></span>
                                                </td>
                                                <td><?= $log->keterangan ?: '-' ?></td>
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
