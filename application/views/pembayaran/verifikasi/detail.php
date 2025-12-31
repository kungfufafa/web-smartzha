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
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informasi Tagihan</h3>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm">
                    <tr>
                        <td width="35%">Kode Tagihan</td>
                        <td><strong><?= $transaksi->kode_tagihan ?></strong></td>
                    </tr>
                    <tr>
                        <td>Siswa</td>
                        <td><?= $transaksi->nama_siswa ?> (<?= $transaksi->nis ?>)</td>
                    </tr>
                    <tr>
                        <td>Kelas</td>
                        <td><?= $transaksi->nama_kelas ?></td>
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
                        <td>Nominal Tagihan</td>
                        <td>Rp <?= number_format($transaksi->nominal_tagihan, 0, ',', '.') ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Detail Transaksi</h3>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm">
                    <tr>
                        <td width="35%">Kode Transaksi</td>
                        <td><strong><?= $transaksi->kode_transaksi ?></strong></td>
                    </tr>
                    <tr>
                        <td>Metode Pembayaran</td>
                        <td><span class="badge badge-info"><?= strtoupper($transaksi->metode_bayar) ?></span></td>
                    </tr>
                    <tr>
                        <td>Nominal Bayar</td>
                        <td class="text-success"><strong>Rp <?= number_format($transaksi->nominal_bayar, 0, ',', '.') ?></strong></td>
                    </tr>
                    <tr>
                        <td>Tanggal Bayar</td>
                        <td><?= $transaksi->tanggal_bayar ? date('d M Y', strtotime($transaksi->tanggal_bayar)) : '-' ?></td>
                    </tr>
                    <tr>
                        <td>Tanggal Upload</td>
                        <td><?= date('d M Y H:i', strtotime($transaksi->created_at)) ?></td>
                    </tr>
                    <tr>
                        <td>Catatan Siswa</td>
                        <td><?= $transaksi->catatan_siswa ?: '-' ?></td>
                    </tr>
                    <tr>
                        <td>Status</td>
                        <td>
                            <?php
                            $badges = [
                                'pending' => 'warning',
                                'verified' => 'success',
                                'rejected' => 'danger',
                                'cancelled' => 'dark'
                            ];
                            $badge = $badges[$transaksi->status] ?? 'secondary';
                            ?>
                            <span class="badge badge-<?= $badge ?>"><?= strtoupper($transaksi->status) ?></span>
                            <?php if ($transaksi->reject_count > 0): ?>
                                <small class="text-danger">(Ditolak <?= $transaksi->reject_count ?>x)</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <?php if ($transaksi->status == 'pending'): ?>
                <hr>
                <div class="row">
                    <div class="col-6">
                        <button type="button" class="btn btn-success btn-block" data-toggle="modal" data-target="#modalApprove">
                            <i class="fas fa-check"></i> Setujui
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-danger btn-block" data-toggle="modal" data-target="#modalReject">
                            <i class="fas fa-times"></i> Tolak
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($logs)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Riwayat Aktivitas</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Aksi</th>
                            <th>Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($log->created_at)) ?></td>
                            <td><?= ucwords(str_replace('_', ' ', $log->action)) ?></td>
                            <td><?= $log->actor_name ?> <small class="text-muted">(<?= $log->actor_type ?>)</small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Bukti Pembayaran</h3>
                <div class="card-tools">
                    <a href="<?= base_url($transaksi->bukti_bayar) ?>" target="_blank" class="btn btn-primary btn-sm">
                        <i class="fas fa-external-link-alt"></i> Buka di Tab Baru
                    </a>
                </div>
            </div>
            <div class="card-body text-center">
                <?php 
                $ext = pathinfo($transaksi->bukti_bayar, PATHINFO_EXTENSION);
                if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                    <img src="<?= base_url($transaksi->bukti_bayar) ?>" class="img-fluid" style="max-height: 500px;" alt="Bukti Pembayaran">
                <?php elseif (strtolower($ext) == 'pdf'): ?>
                    <embed src="<?= base_url($transaksi->bukti_bayar) ?>" type="application/pdf" width="100%" height="500px">
                <?php else: ?>
                    <div class="text-muted py-5">
                        <i class="fas fa-file fa-3x mb-2"></i>
                        <p>Format file tidak dapat ditampilkan langsung</p>
                        <a href="<?= base_url($transaksi->bukti_bayar) ?>" class="btn btn-primary" download>
                            <i class="fas fa-download"></i> Download File
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <a href="<?= base_url('pembayaran/verifikasi') ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Antrian
                </a>
            </div>
        </div>
    </div>
</div>

        </div>
    </section>

<!-- Modal Approve -->
<div class="modal fade" id="modalApprove">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Setujui Pembayaran</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="formApprove">
                <div class="modal-body">
                    <input type="hidden" name="id_transaksi" value="<?= $transaksi->id_transaksi ?>">
                    <p>Yakin ingin menyetujui pembayaran ini?</p>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td>Siswa</td>
                            <td><strong><?= $transaksi->nama_siswa ?></strong></td>
                        </tr>
                        <tr>
                            <td>Nominal</td>
                            <td><strong>Rp <?= number_format($transaksi->nominal_bayar, 0, ',', '.') ?></strong></td>
                        </tr>
                    </table>
                    <div class="form-group">
                        <label>Catatan (opsional)</label>
                        <textarea name="catatan" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Ya, Setujui
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Reject -->
<div class="modal fade" id="modalReject">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Tolak Pembayaran</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="formReject">
                <div class="modal-body">
                    <input type="hidden" name="id_transaksi" value="<?= $transaksi->id_transaksi ?>">
                    <?php if ($transaksi->reject_count >= 2): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Ini adalah penolakan ke-<?= $transaksi->reject_count + 1 ?>. Pembayaran akan <strong>DIBATALKAN PERMANEN</strong> dan siswa tidak dapat upload ulang.
                    </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label>Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="catatan" class="form-control" rows="3" required placeholder="Jelaskan alasan penolakan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Tolak Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    ajaxcsrf(); // Init CSRF for AJAX

    $('#formApprove').on('submit', function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');

        var data = $(this).serialize() + "&" + '<?= $this->security->get_csrf_token_name() ?>' + "=" + '<?= $this->security->get_csrf_hash() ?>';

        $.ajax({
            url: '<?= base_url('pembayaran/approve') ?>',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    Swal.fire({
                        title: 'Berhasil',
                        text: response.message,
                        icon: 'success'
                    }).then(() => {
                        window.location.href = '<?= base_url('pembayaran/verifikasi') ?>';
                    });
                } else {
                    Swal.fire('Gagal', response.message, 'error');
                    btn.prop('disabled', false).html('<i class="fas fa-check"></i> Ya, Setujui');
                }
            },
            error: function() {
                Swal.fire('Error', 'Terjadi kesalahan pada server', 'error');
                btn.prop('disabled', false).html('<i class="fas fa-check"></i> Ya, Setujui');
            }
        });
    });

    $('#formReject').on('submit', function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');

        var data = $(this).serialize() + "&" + '<?= $this->security->get_csrf_token_name() ?>' + "=" + '<?= $this->security->get_csrf_hash() ?>';

        $.ajax({
            url: '<?= base_url('pembayaran/reject') ?>',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    Swal.fire({
                        title: 'Ditolak',
                        text: response.message,
                        icon: 'info'
                    }).then(() => {
                        window.location.href = '<?= base_url('pembayaran/verifikasi') ?>';
                    });
                } else {
                    Swal.fire('Gagal', response.message, 'error');
                    btn.prop('disabled', false).html('<i class="fas fa-times"></i> Tolak Pembayaran');
                }
            },
            error: function() {
                Swal.fire('Error', 'Terjadi kesalahan pada server', 'error');
                btn.prop('disabled', false).html('<i class="fas fa-times"></i> Tolak Pembayaran');
            }
        });
    });
});
</script>
