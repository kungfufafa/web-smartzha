<div class="content-wrapper" style="margin-top: -1px;">
    <div class="sticky"></div>
    <section class="content overlap p-4">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <?php $this->load->view('members/siswa/templates/top'); ?>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card card-primary card-outline my-shadow">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-file-invoice-dollar"></i> Tagihan Belum Dibayar</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($tagihan_belum)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-check-circle fa-3x text-success mb-2"></i>
                                    <p>Tidak ada tagihan yang belum dibayar</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>Jenis</th>
                                                <th>Periode</th>
                                                <th>Total</th>
                                                <th>Jatuh Tempo</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $namaBulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
                                            foreach ($tagihan_belum as $t): 
                                            ?>
                                            <tr>
                                                <td class="align-middle"><strong><?= $t->nama_jenis ?></strong></td>
                                                <td class="align-middle"><?= $t->bulan ? $namaBulan[$t->bulan] . ' ' . $t->tahun : '-' ?></td>
                                                <td class="align-middle text-danger"><strong>Rp <?= number_format($t->total, 0, ',', '.') ?></strong></td>
                                                <td class="align-middle">
                                                    <?= date('d M Y', strtotime($t->jatuh_tempo)) ?>
                                                    <?php if (strtotime($t->jatuh_tempo) < time()): ?>
                                                        <span class="badge badge-danger">Terlambat</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="align-middle">
                                                    <?php if ($t->status == 'ditolak'): ?>
                                                        <span class="badge badge-danger">Ditolak</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">Belum Bayar</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="align-middle">
                                                    <a href="<?= base_url('tagihanku/bayar/' . $t->id_tagihan) ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-credit-card"></i> Bayar
                                                    </a>
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
            </div>

            <?php if (!empty($tagihan_proses)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card card-info card-outline my-shadow">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-hourglass-half"></i> Menunggu Verifikasi</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Jenis</th>
                                            <th>Periode</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tagihan_proses as $t): ?>
                                        <tr>
                                            <td class="align-middle"><strong><?= $t->nama_jenis ?></strong></td>
                                            <td class="align-middle"><?= $t->bulan ? $namaBulan[$t->bulan] . ' ' . $t->tahun : '-' ?></td>
                                            <td class="align-middle">Rp <?= number_format($t->total, 0, ',', '.') ?></td>
                                            <td class="align-middle"><span class="badge badge-info"><i class="fas fa-spinner fa-spin"></i> Menunggu Verifikasi</span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($tagihan_lunas)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card card-success card-outline collapsed-card my-shadow">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-check-circle"></i> Tagihan Lunas (<?= count($tagihan_lunas) ?>)</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>Jenis</th>
                                            <th>Periode</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tagihan_lunas as $t): ?>
                                        <tr>
                                            <td class="align-middle"><?= $t->nama_jenis ?></td>
                                            <td class="align-middle"><?= $t->bulan ? $namaBulan[$t->bulan] . ' ' . $t->tahun : '-' ?></td>
                                            <td class="align-middle">Rp <?= number_format($t->total, 0, ',', '.') ?></td>
                                            <td class="align-middle"><span class="badge badge-success"><i class="fas fa-check"></i> Lunas</span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-12">
                    <div class="card my-shadow">
                        <div class="card-body text-center">
                            <a href="<?= base_url('tagihanku/riwayat') ?>" class="btn btn-secondary">
                                <i class="fas fa-history"></i> Lihat Riwayat Pembayaran
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>
