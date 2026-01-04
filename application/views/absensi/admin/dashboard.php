<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Dashboard Absensi</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Absensi</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Pegawai Stats (Guru + Karyawan) -->
            <h5 class="text-muted mb-2"><i class="fas fa-user-tie mr-1"></i> Pegawai (Guru + Karyawan)</h5>
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= isset($stats->pegawai->hadir) ? $stats->pegawai->hadir : 0 ?></h3>
                            <p>Hadir</p>
                        </div>
                        <div class="icon"><i class="fas fa-user-check"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= isset($stats->pegawai->terlambat) ? $stats->pegawai->terlambat : 0 ?></h3>
                            <p>Terlambat</p>
                        </div>
                        <div class="icon"><i class="fas fa-clock"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= isset($stats->pegawai->belum_masuk) ? $stats->pegawai->belum_masuk : 0 ?></h3>
                            <p>Belum Check-in</p>
                        </div>
                        <div class="icon"><i class="fas fa-user-times"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= isset($stats->total_pegawai) ? $stats->total_pegawai : 0 ?></h3>
                            <p>Total Pegawai</p>
                        </div>
                        <div class="icon"><i class="fas fa-users"></i></div>
                    </div>
                </div>
            </div>

            <!-- Siswa Stats -->
            <h5 class="text-muted mb-2 mt-3"><i class="fas fa-user-graduate mr-1"></i> Siswa</h5>
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= isset($stats->siswa->hadir) ? $stats->siswa->hadir : 0 ?></h3>
                            <p>Hadir</p>
                        </div>
                        <div class="icon"><i class="fas fa-user-check"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= isset($stats->siswa->terlambat) ? $stats->siswa->terlambat : 0 ?></h3>
                            <p>Terlambat</p>
                        </div>
                        <div class="icon"><i class="fas fa-clock"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= isset($stats->siswa->belum_masuk) ? $stats->siswa->belum_masuk : 0 ?></h3>
                            <p>Belum Check-in</p>
                        </div>
                        <div class="icon"><i class="fas fa-user-times"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= isset($stats->total_siswa) ? $stats->total_siswa : 0 ?></h3>
                            <p>Total Siswa</p>
                        </div>
                        <div class="icon"><i class="fas fa-users"></i></div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-bolt mr-1"></i> Aksi Cepat</h3>
                        </div>
                        <div class="card-body">
                            <button type="button" class="btn btn-danger mr-2" id="btnMarkAlpha">
                                <i class="fas fa-user-times"></i> Tandai Alpha
                            </button>
                            <a href="<?= base_url('absensi/laporan') ?>" class="btn btn-primary mr-2">
                                <i class="fas fa-file-alt"></i> Laporan
                            </a>
                            <a href="<?= base_url('absensi/logs') ?>" class="btn btn-secondary">
                                <i class="fas fa-list"></i> Lihat Log
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Recent Logs -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-history mr-1"></i> Log Absensi Terbaru</h3>
                        </div>
                        <div class="card-body table-responsive p-0" style="max-height: 400px;">
                            <table class="table table-head-fixed table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Jam Masuk</th>
                                        <th>Jam Pulang</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_logs)): ?>
                                    <tr><td colspan="4" class="text-center text-muted">Belum ada log hari ini</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($recent_logs as $log): ?>
                                    <tr>
                                        <td><?= isset($log->nama_lengkap) ? $log->nama_lengkap : $log->username ?></td>
                                        <td><?= $log->jam_masuk ? date('H:i', strtotime($log->jam_masuk)) : '-' ?></td>
                                        <td><?= $log->jam_pulang ? date('H:i', strtotime($log->jam_pulang)) : '-' ?></td>
                                        <td>
                                            <?php
                                            $badge = 'secondary';
                                            if ($log->status_kehadiran == 'Hadir') $badge = 'success';
                                            elseif ($log->status_kehadiran == 'Terlambat') $badge = 'warning';
                                            elseif ($log->status_kehadiran == 'Alpha') $badge = 'danger';
                                            elseif (in_array($log->status_kehadiran, ['Izin', 'Sakit', 'Cuti'])) $badge = 'info';
                                            ?>
                                            <span class="badge badge-<?= $badge ?>"><?= $log->status_kehadiran ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sidebar: Late & Not Checked In -->
                <div class="col-md-4">
                    <!-- Late Today -->
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-1"></i> Terlambat Hari Ini</h3>
                        </div>
                        <div class="card-body p-0" style="max-height: 180px; overflow-y: auto;">
                            <ul class="list-group list-group-flush">
                                <?php if (empty($late_today)): ?>
                                <li class="list-group-item text-muted text-center">Tidak ada</li>
                                <?php else: ?>
                                <?php foreach ($late_today as $late): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= isset($late->nama_lengkap) ? $late->nama_lengkap : $late->username ?>
                                    <span class="badge badge-warning"><?= $late->terlambat_menit ?> menit</span>
                                </li>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Not Checked In -->
                    <div class="card card-danger">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-user-slash mr-1"></i> Belum Check-in</h3>
                        </div>
                        <div class="card-body p-0" style="max-height: 180px; overflow-y: auto;">
                            <ul class="list-group list-group-flush">
                                <?php if (empty($not_checked_in)): ?>
                                <li class="list-group-item text-muted text-center">Semua sudah check-in</li>
                                <?php else: ?>
                                <?php foreach ($not_checked_in as $user): ?>
                                <li class="list-group-item">
                                    <?= isset($user->nama_lengkap) ? $user->nama_lengkap : $user->username ?>
                                </li>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    $('#btnMarkAlpha').on('click', function() {
        Swal.fire({
            title: 'Tandai Alpha?',
            text: 'Semua karyawan yang belum check-in akan ditandai Alpha',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Tandai Alpha',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= base_url("absensi/markAlpha") ?>',
                    type: 'POST',
                    data: {
                        tanggal: '<?= date("Y-m-d") ?>',
                        <?= $this->security->get_csrf_token_name() ?>: '<?= $this->security->get_csrf_hash() ?>'
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status) {
                            Swal.fire('Berhasil', res.message, 'success').then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Gagal', res.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Terjadi kesalahan server', 'error');
                    }
                });
            }
        });
    });
});
</script>
