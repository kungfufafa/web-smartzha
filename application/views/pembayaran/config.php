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

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Konfigurasi Pembayaran</h3>
    </div>
    <div class="card-body">
        <form id="formConfig" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-qrcode mr-2"></i>QRIS Statis</h5>
                    <hr>
                    <div class="form-group">
                        <label>Gambar QRIS</label>
                        <?php if ($config && $config->qris_image): ?>
                        <div class="mb-2">
                            <img src="<?= base_url($config->qris_image) ?>" alt="QRIS" class="img-thumbnail" style="max-width: 200px;">
                        </div>
                        <?php endif; ?>
                        <input type="file" name="qris_image" class="form-control" accept="image/*">
                        <small class="text-muted">Format: JPG, PNG. Max 2MB</small>
                    </div>
                    <div class="form-group">
                        <label>Nama Merchant</label>
                        <input type="text" name="qris_merchant_name" class="form-control" value="<?= $config->qris_merchant_name ?? '' ?>" placeholder="Nama merchant yang tampil di QRIS">
                    </div>
                </div>
                <div class="col-md-6">
                    <h5><i class="fas fa-university mr-2"></i>Rekening Bank</h5>
                    <hr>
                    <div class="form-group">
                        <label>Nama Bank</label>
                        <input type="text" name="bank_name" class="form-control" value="<?= $config->bank_name ?? '' ?>" placeholder="BRI, BCA, Mandiri, dll">
                    </div>
                    <div class="form-group">
                        <label>Nomor Rekening</label>
                        <input type="text" name="bank_account" class="form-control" value="<?= $config->bank_account ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Atas Nama</label>
                        <input type="text" name="bank_holder" class="form-control" value="<?= $config->bank_holder ?? '' ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label>Instruksi Pembayaran</label>
                        <textarea name="payment_instruction" class="form-control" rows="3" placeholder="Instruksi yang akan ditampilkan ke siswa saat melakukan pembayaran"><?= $config->payment_instruction ?? '' ?></textarea>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan Konfigurasi</button>
        </form>
    </div>
</div>

        </div>
    </section>

<script>
$(document).ready(function() {
    ajaxcsrf(); // Init CSRF for AJAX

    $('#formConfig').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        // Append CSRF token for FormData
        formData.append('<?= $this->security->get_csrf_token_name() ?>', '<?= $this->security->get_csrf_hash() ?>');
        
        $.ajax({
            url: '<?= base_url('pembayaran/saveConfig') ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function() {
                $('button[type=submit]').attr('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
            },
            success: function(response) {
                if (response.status) {
                    Swal.fire('Berhasil', response.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Gagal', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
            },
            complete: function() {
                $('button[type=submit]').attr('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Konfigurasi');
            }
        });
    });
});
</script>
