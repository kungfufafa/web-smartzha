<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper" style="margin-top: -1px;">
    <div class="sticky"></div>
    <section class="content overlap p-4">
        <div class="container">
            <!-- Student Info Card -->
            <div class="info-box bg-transparent shadow-none">
                <?php 
                $foto = $siswa->foto ?? 'assets/img/siswa.png';
                ?>
                <img class="avatar" src="<?= base_url($foto) ?>" width="120" height="120">
                <div class="info-box-content">
                    <h5 class="info-box-text text-white text-wrap"><b><?= e($selected_anak->nama) ?></b></h5>
                    <span class="info-box-text text-white"><?= e($siswa->nis ?? '-') ?></span>
                    <span class="info-box-text text-white mb-1"><?= e($siswa->nama_kelas ?? 'Belum ada kelas') ?></span>
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
                <div class="col-lg-6">
                    <div class="card my-shadow">
                        <div class="card-header bg-primary text-white">
                            <h3 class="card-title"><i class="fas fa-file-invoice"></i> Detail Tagihan</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="40%">Kode Tagihan</td>
                                    <td><strong><?= e($tagihan->kode_tagihan) ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Jenis</td>
                                    <td><?= e($tagihan->nama_jenis) ?></td>
                                </tr>
                                <?php if ($tagihan->bulan): ?>
                                <tr>
                                    <td>Periode</td>
                                    <td><?= $tagihan->bulan ?>/<?= $tagihan->tahun ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td>Nominal</td>
                                    <td>Rp <?= number_format($tagihan->nominal, 0, ',', '.') ?></td>
                                </tr>
                                <?php if ($tagihan->diskon > 0): ?>
                                <tr>
                                    <td>Diskon</td>
                                    <td class="text-success">- Rp <?= number_format($tagihan->diskon, 0, ',', '.') ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($tagihan->denda > 0): ?>
                                <tr>
                                    <td>Denda</td>
                                    <td class="text-danger">+ Rp <?= number_format($tagihan->denda, 0, ',', '.') ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr class="border-top">
                                    <td><strong>Total Bayar</strong></td>
                                    <td class="text-primary"><strong style="font-size: 1.5em;">Rp <?= number_format($tagihan->total, 0, ',', '.') ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Jatuh Tempo</td>
                                    <td>
                                        <?= date('d M Y', strtotime($tagihan->jatuh_tempo)) ?>
                                        <?php if (strtotime($tagihan->jatuh_tempo) < time()): ?>
                                            <span class="badge badge-danger">Terlambat</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>

                            <?php if ($tagihan->status == 'ditolak' && $transaksi_terakhir): ?>
                            <div class="alert alert-danger">
                                <h5><i class="fas fa-exclamation-circle"></i> Pembayaran Sebelumnya Ditolak</h5>
                                <p><strong>Alasan:</strong> <?= e($transaksi_terakhir->catatan_admin ?: 'Tidak ada keterangan') ?></p>
                                <?php if ($transaksi_terakhir->reject_count >= 3): ?>
                                    <p class="mb-0 text-danger"><strong>Pembayaran sudah ditolak 3 kali. Silakan hubungi admin.</strong></p>
                                <?php else: ?>
                                    <p class="mb-0">Silakan upload ulang bukti pembayaran yang benar. (Percobaan ke-<?= $transaksi_terakhir->reject_count + 1 ?> dari 3)</p>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card my-shadow">
                        <div class="card-body">
                            <a href="<?= base_url('orangtua/tagihan') ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <?php if ($config && $config->qris_image): ?>
                    <div class="card my-shadow">
                        <div class="card-header bg-success text-white">
                            <h3 class="card-title"><i class="fas fa-qrcode"></i> Scan QRIS untuk Membayar</h3>
                        </div>
                        <div class="card-body text-center">
                            <img src="<?= base_url($config->qris_image) ?>" class="img-fluid" style="max-height: 300px;" alt="QRIS">
                            <?php if ($config->qris_merchant_name): ?>
                                            <p class="mt-2 mb-0"><strong><?= e($config->qris_merchant_name) ?></strong></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($config && $config->bank_name): ?>
                    <div class="card my-shadow">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-university"></i> Transfer Bank</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td>Bank</td>
                                    <td><strong><?= e($config->bank_name) ?></strong></td>
                                </tr>
                                <tr>
                                    <td>No. Rekening</td>
                                    <td><strong style="font-size: 1.2em;"><?= e($config->bank_account) ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Atas Nama</td>
                                    <td><strong><?= e($config->bank_holder) ?></strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($config && $config->payment_instruction): ?>
                    <div class="card my-shadow">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-info-circle"></i> Petunjuk Pembayaran</h3>
                        </div>
                        <div class="card-body">
                            <?= nl2br(e($config->payment_instruction)) ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!isset($transaksi_terakhir) || $transaksi_terakhir->reject_count < 3): ?>
                    <div class="card my-shadow">
                        <div class="card-header bg-warning">
                            <h3 class="card-title"><i class="fas fa-upload"></i> Upload Bukti Pembayaran</h3>
                        </div>
                        <?= form_open_multipart('', array('id' => 'formBayar')); ?>
                            <div class="card-body">
                                <input type="hidden" name="id_tagihan" value="<?= $tagihan->id_tagihan ?>">
                                <div class="form-group">
                                    <label>Metode Pembayaran</label>
                                    <select name="metode_bayar" class="form-control">
                                        <option value="qris">QRIS</option>
                                        <option value="transfer">Transfer Bank</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Tanggal Pembayaran</label>
                                    <input type="date" name="tanggal_bayar" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Bukti Pembayaran <span class="text-danger">*</span></label>
                                    <div class="custom-file">
                                        <input type="file" name="bukti" id="bukti" class="custom-file-input" accept="image/*,.pdf" required>
                                        <label class="custom-file-label" for="bukti">Pilih file...</label>
                                    </div>
                                    <small class="text-muted">Format: JPG, PNG, PDF. Maks: 2MB</small>
                                </div>
                                <div class="form-group">
                                    <label>Catatan (opsional)</label>
                                    <textarea name="catatan_siswa" class="form-control" rows="2" placeholder="Misal: Transfer dari rekening atas nama..."></textarea>
                                </div>
                                <div id="previewBukti" class="text-center mb-3" style="display: none;">
                                    <img id="previewImg" src="" class="img-fluid" style="max-height: 200px;">
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-success btn-block" id="btnSubmit">
                                    <i class="fas fa-paper-plane"></i> Kirim Bukti Pembayaran
                                </button>
                            </div>
                        <?= form_close(); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    ajaxcsrf(); // Init CSRF for AJAX

    $('#bukti').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);

        var file = this.files[0];
        if (file && file.type.startsWith('image/')) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#previewImg').attr('src', e.target.result);
                $('#previewBukti').show();
            };
            reader.readAsDataURL(file);
        } else {
            $('#previewBukti').hide();
        }
    });

    $('#formBayar').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);
        var btn = $('#btnSubmit');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Mengirim...');

        $.ajax({
            url: '<?= base_url('orangtua/uploadBukti') ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: response.message,
                        icon: 'success'
                    }).then(() => {
                        window.location.href = '<?= base_url('orangtua/tagihan') ?>';
                    });
                } else {
                    Swal.fire('Gagal', response.message, 'error');
                    btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Kirim Bukti Pembayaran');
                }
            },
            error: function() {
                Swal.fire('Error', 'Terjadi kesalahan pada server', 'error');
                btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Kirim Bukti Pembayaran');
            }
        });
    });
});
</script>
