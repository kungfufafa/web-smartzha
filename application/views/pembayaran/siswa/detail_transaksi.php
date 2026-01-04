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
                <div class="col-lg-6">
                    <div class="card my-shadow">
                        <div class="card-header">
                            <h3 class="card-title">Detail Transaksi</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="40%">Kode Transaksi</td>
                                    <td><strong><?= $transaksi->kode_transaksi ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Kode Tagihan</td>
                                    <td><?= $transaksi->kode_tagihan ?></td>
                                </tr>
                                <tr>
                                    <td>Jenis Tagihan</td>
                                    <td><?= $transaksi->nama_jenis ?></td>
                                </tr>
                                <?php if ($transaksi->bulan): ?>
                                <tr>
                                    <td>Periode</td>
                                    <td><?= $transaksi->bulan ?>/<?= $transaksi->tahun ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td>Metode Pembayaran</td>
                                    <td><span class="badge badge-info"><?= strtoupper($transaksi->metode_bayar) ?></span></td>
                                </tr>
                                <tr>
                                    <td>Nominal Bayar</td>
                                    <td><strong>Rp <?= number_format($transaksi->nominal_bayar, 0, ',', '.') ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Tanggal Bayar</td>
                                    <td><?= $transaksi->tanggal_bayar ? date('d M Y', strtotime($transaksi->tanggal_bayar)) : '-' ?></td>
                                </tr>
                                <tr>
                                    <td>Tanggal Upload</td>
                                    <td><?= date('d M Y H:i', strtotime($transaksi->waktu_upload ?: $transaksi->created_at)) ?></td>
                                </tr>
                                <tr>
                                    <td>Status</td>
                                    <td>
                                        <?php
                                        $badges = [
                                            'pending' => '<span class="badge badge-warning">Menunggu Verifikasi</span>',
                                            'verified' => '<span class="badge badge-success">Disetujui</span>',
                                            'rejected' => '<span class="badge badge-danger">Ditolak</span>',
                                            'cancelled' => '<span class="badge badge-dark">Dibatalkan</span>'
                                        ];
                                        echo $badges[$transaksi->status] ?? $transaksi->status;
                                        ?>
                                    </td>
                                </tr>
                                <?php if ($transaksi->catatan_siswa): ?>
                                <tr>
                                    <td>Catatan Anda</td>
                                    <td><?= $transaksi->catatan_siswa ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($transaksi->verified_at): ?>
                                <tr>
                                    <td>Tanggal Verifikasi</td>
                                    <td><?= date('d M Y H:i', strtotime($transaksi->verified_at)) ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($transaksi->catatan_admin): ?>
                                <tr>
                                    <td>Catatan Admin</td>
                                    <td class="<?= $transaksi->status == 'rejected' ? 'text-danger' : '' ?>">
                                        <?= $transaksi->catatan_admin ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>

                    <div class="card my-shadow">
                        <div class="card-body">
                            <a href="<?= base_url('tagihanku/riwayat') ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali ke Riwayat
                            </a>
                            <a href="<?= base_url('tagihanku') ?>" class="btn btn-primary">
                                <i class="fas fa-list"></i> Lihat Tagihan
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card my-shadow">
                        <div class="card-header">
                            <h3 class="card-title">Bukti Pembayaran</h3>
                            <div class="card-tools">
                                <a href="<?= base_url($transaksi->bukti_bayar) ?>" target="_blank" class="btn btn-primary btn-sm">
                                    <i class="fas fa-external-link-alt"></i> Buka
                                </a>
                            </div>
                        </div>
                        <div class="card-body text-center">
                            <?php 
                            $ext = pathinfo($transaksi->bukti_bayar, PATHINFO_EXTENSION);
                            if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                <img src="<?= base_url($transaksi->bukti_bayar) ?>" class="img-fluid" style="max-height: 400px;" alt="Bukti Pembayaran">
                            <?php elseif (strtolower($ext) == 'pdf'): ?>
                                <embed src="<?= base_url($transaksi->bukti_bayar) ?>" type="application/pdf" width="100%" height="400px">
                            <?php else: ?>
                                <div class="text-muted py-5">
                                    <i class="fas fa-file fa-3x mb-2"></i>
                                    <p>Format file tidak dapat ditampilkan</p>
                                    <a href="<?= base_url($transaksi->bukti_bayar) ?>" class="btn btn-primary" download>
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
