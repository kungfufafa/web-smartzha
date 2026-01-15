<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper" style="margin-top: -1px;">
    <div class="sticky"></div>
    <section class="content overlap p-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 offset-lg-2">
                    <div class="card card-primary card-outline my-shadow">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-receipt"></i> Detail Transaksi</h3>
                            <div class="card-tools">
                                <a href="<?= base_url('orangtua/riwayat') ?>" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="150">Kode Transaksi</td>
                                            <td><strong><?= $transaksi->kode_transaksi ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td>Tanggal</td>
                                            <td><?= date('d M Y H:i', strtotime($transaksi->created_at)) ?></td>
                                        </tr>
                                        <tr>
                                            <td>Metode Bayar</td>
                                            <td><?= strtoupper($transaksi->metode_bayar ?? '-') ?></td>
                                        </tr>
                                        <tr>
                                             <td>Jumlah</td>
                                             <td><strong class="text-primary">Rp <?= number_format($transaksi->nominal_tagihan ?? $transaksi->nominal_bayar, 0, ',', '.') ?></strong></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="150">Status</td>
                                            <td>
                                                <?php 
                                                switch ($transaksi->status) {
                                                    case 'menunggu_verifikasi':
                                                        echo '<span class="badge badge-info badge-lg"><i class="fas fa-spinner fa-spin"></i> Menunggu Verifikasi</span>';
                                                        break;
                                                    case 'diverifikasi':
                                                        echo '<span class="badge badge-success badge-lg"><i class="fas fa-check"></i> Diverifikasi</span>';
                                                        break;
                                                    case 'ditolak':
                                                        echo '<span class="badge badge-danger badge-lg"><i class="fas fa-times"></i> Ditolak</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge badge-secondary">' . $transaksi->status . '</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <?php if ($transaksi->verified_at): ?>
                                        <tr>
                                            <td>Diverifikasi pada</td>
                                            <td><?= date('d M Y H:i', strtotime($transaksi->verified_at)) ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if ($transaksi->catatan_siswa): ?>
                                        <tr>
                                            <td>Catatan Anda</td>
                                            <td><?= $transaksi->catatan_siswa ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if ($transaksi->catatan_admin): ?>
                                        <tr>
                                            <td>Catatan Admin</td>
                                            <td class="<?= $transaksi->status == 'ditolak' ? 'text-danger' : '' ?>"><?= $transaksi->catatan_admin ?></td>
                                        </tr>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            </div>

                            <?php if ($transaksi->bukti_bayar): ?>
                            <hr>
                            <h5><i class="fas fa-image"></i> Bukti Pembayaran</h5>
                            <div class="text-center">
                                <?php 
                                $ext = pathinfo($transaksi->bukti_bayar, PATHINFO_EXTENSION);
                                if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif'])): 
                                ?>
                                    <img src="<?= base_url($transaksi->bukti_bayar) ?>" class="img-fluid" style="max-height: 400px;" alt="Bukti Pembayaran">
                                <?php else: ?>
                                    <a href="<?= base_url($transaksi->bukti_bayar) ?>" target="_blank" class="btn btn-primary">
                                        <i class="fas fa-file-pdf"></i> Lihat Bukti Pembayaran (PDF)
                                    </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>
