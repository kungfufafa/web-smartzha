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
                    <div class="card my-shadow">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-history"></i> Riwayat Pembayaran</h3>
                            <div class="card-tools">
                                <a href="<?= base_url('tagihanku') ?>" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($transaksi)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-receipt fa-3x mb-2"></i>
                                    <p>Belum ada riwayat pembayaran</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>Kode</th>
                                                <th>Jenis</th>
                                                <th>Periode</th>
                                                <th>Nominal</th>
                                                <th>Tanggal</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $namaBulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
                                            foreach ($transaksi as $t): 
                                            ?>
                                            <tr>
                                                <td class="align-middle"><small><?= $t->kode_transaksi ?></small></td>
                                                <td class="align-middle"><?= $t->nama_jenis ?></td>
                                                <td class="align-middle"><?= $t->bulan ? $namaBulan[$t->bulan] . ' ' . $t->tahun : '-' ?></td>
                                                <td class="align-middle">Rp <?= number_format($t->nominal_bayar, 0, ',', '.') ?></td>
                                                <td class="align-middle"><?= date('d M Y', strtotime($t->created_at)) ?></td>
                                                <td class="align-middle">
                                                    <?php
                                                    $badges = [
                                                        'pending' => '<span class="badge badge-warning">Menunggu</span>',
                                                        'verified' => '<span class="badge badge-success">Disetujui</span>',
                                                        'rejected' => '<span class="badge badge-danger">Ditolak</span>',
                                                        'cancelled' => '<span class="badge badge-dark">Dibatalkan</span>'
                                                    ];
                                                    echo $badges[$t->status] ?? $t->status;
                                                    ?>
                                                </td>
                                                <td class="align-middle">
                                                    <a href="<?= base_url('tagihanku/detailTransaksi/' . $t->id_transaksi) ?>" class="btn btn-info btn-xs">
                                                        <i class="fas fa-eye"></i>
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
        </div>
    </section>
</div>
