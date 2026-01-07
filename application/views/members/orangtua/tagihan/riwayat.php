<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper" style="margin-top: -1px;">
    <div class="sticky"></div>
    <section class="content overlap p-4">
        <div class="container">
            <!-- Student Info Card -->
            <div class="info-box bg-transparent shadow-none">
                <?php 
                $foto = $selected_anak->foto ?? 'assets/img/siswa.png';
                ?>
                <img class="avatar" src="<?= base_url($foto) ?>" width="120" height="120">
                <div class="info-box-content">
                    <h5 class="info-box-text text-white text-wrap"><b><?= $selected_anak->nama ?></b></h5>
                    <span class="info-box-text text-white"><?= $selected_anak->nis ?? '-' ?></span>
                    <span class="info-box-text text-white mb-1"><?= $selected_anak->nama_kelas ?? 'Belum ada kelas' ?></span>
                </div>
            </div>

            <script>
                $(`.avatar`).each(function () {
                    $(this).on("error", function () {
                        var src = $(this).attr('src').replace('profiles', 'foto_siswa');
                        $(this).attr("src", src);
                        $(this).on("error", function () {
                            $(this).attr("src", base_url + 'assets/img/siswa.png');
                        });
                    });
                });
            </script>

            <div class="row">
                <div class="col-12">
                    <div class="card card-primary card-outline my-shadow">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-history"></i> Riwayat Pembayaran</h3>
                            <div class="card-tools">
                                <a href="<?= base_url('orangtua/tagihan') ?>" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($transaksi)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-receipt fa-3x mb-3"></i>
                                    <p>Belum ada riwayat pembayaran.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-hover">
                                        <thead class="bg-primary text-white">
                                            <tr>
                                                <th width="50" class="text-center">No</th>
                                                <th>Kode Transaksi</th>
                                                <th>Tagihan</th>
                                                <th class="text-right">Jumlah</th>
                                                <th class="text-center">Tanggal</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $no = 1;
                                            foreach ($transaksi as $t): 
                                            ?>
                                            <tr>
                                                <td class="text-center"><?= $no++ ?></td>
                                                <td><strong><?= $t->kode_transaksi ?></strong></td>
                                                <td><?= $t->nama_jenis ?? 'N/A' ?></td>
                                                <td class="text-right">Rp <?= number_format($t->jumlah ?? 0, 0, ',', '.') ?></td>
                                                <td class="text-center"><?= date('d M Y', strtotime($t->created_at)) ?></td>
                                                <td class="text-center">
                                                    <?php 
                                                    switch ($t->status) {
                                                        case 'menunggu_verifikasi':
                                                            echo '<span class="badge badge-info"><i class="fas fa-spinner fa-spin"></i> Menunggu</span>';
                                                            break;
                                                        case 'diverifikasi':
                                                            echo '<span class="badge badge-success"><i class="fas fa-check"></i> Diverifikasi</span>';
                                                            break;
                                                        case 'ditolak':
                                                            echo '<span class="badge badge-danger"><i class="fas fa-times"></i> Ditolak</span>';
                                                            break;
                                                        default:
                                                            echo '<span class="badge badge-secondary">' . $t->status . '</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="text-center">
                                                    <a href="<?= base_url('orangtua/detailTransaksi/' . $t->id_transaksi) ?>" class="btn btn-sm btn-info">
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
