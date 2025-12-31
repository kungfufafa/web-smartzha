<div class="content-wrapper bg-white pt-4">
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

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= number_format($stats['total_tagihan']) ?></h3>
                <p>Total Tagihan</p>
            </div>
            <div class="icon"><i class="fas fa-file-invoice"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= number_format($stats['total_lunas']) ?></h3>
                <p>Lunas</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= number_format($stats['menunggu_verifikasi']) ?></h3>
                <p>Menunggu Verifikasi</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
            <a href="<?= base_url('pembayaran/verifikasi') ?>" class="small-box-footer">Lihat detail <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?= number_format($stats['belum_bayar']) ?></h3>
                <p>Belum Bayar</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie mr-1"></i> Ringkasan Keuangan</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <td>Total Terbayar (Lunas)</td>
                        <td class="text-right text-success font-weight-bold">Rp <?= number_format($stats['nominal_lunas'], 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td>Total Tunggakan</td>
                        <td class="text-right text-danger font-weight-bold">Rp <?= number_format($stats['nominal_tunggakan'], 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td>Antrian Verifikasi</td>
                        <td class="text-right">
                            <span class="badge badge-warning"><?= $stats['pending_verifikasi'] ?> transaksi</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-school mr-1"></i> Tunggakan per Kelas</h3>
            </div>
            <div class="card-body table-responsive p-0" style="max-height: 300px;">
                <table class="table table-head-fixed table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>Kelas</th>
                            <th class="text-center">Jumlah</th>
                            <th class="text-right">Total Tunggakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tunggakan_kelas)): ?>
                        <tr><td colspan="3" class="text-center text-muted">Tidak ada tunggakan</td></tr>
                        <?php else: ?>
                        <?php foreach ($tunggakan_kelas as $row): ?>
                        <tr>
                            <td><?= $row->nama_kelas ?></td>
                            <td class="text-center"><?= $row->jumlah_tagihan ?></td>
                            <td class="text-right">Rp <?= number_format($row->total_tunggakan, 0, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cogs mr-1"></i> Menu Pembayaran</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-6 mb-3">
                        <a href="<?= base_url('pembayaran/config') ?>" class="btn btn-outline-primary btn-block">
                            <i class="fas fa-qrcode fa-2x mb-2"></i><br>Konfigurasi QRIS
                        </a>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <a href="<?= base_url('pembayaran/jenis') ?>" class="btn btn-outline-info btn-block">
                            <i class="fas fa-tags fa-2x mb-2"></i><br>Jenis Tagihan
                        </a>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <a href="<?= base_url('pembayaran/tagihan') ?>" class="btn btn-outline-success btn-block">
                            <i class="fas fa-file-invoice-dollar fa-2x mb-2"></i><br>Kelola Tagihan
                        </a>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <a href="<?= base_url('pembayaran/verifikasi') ?>" class="btn btn-outline-warning btn-block">
                            <i class="fas fa-check-double fa-2x mb-2"></i><br>
                            Verifikasi
                            <?php if ($stats['pending_verifikasi'] > 0): ?>
                            <span class="badge badge-danger"><?= $stats['pending_verifikasi'] ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

        </div>
    </section>
