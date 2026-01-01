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
            <form id="formConfig" enctype="multipart/form-data">
                <div class="row">
                    <!-- Left Column: QRIS -->
                    <div class="col-md-6 d-flex align-items-stretch">
                        <div class="card card-primary card-outline w-100">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-qrcode mr-2"></i>QRIS Statis</h3>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-4 p-3 bg-light rounded border">
                                    <?php if ($config && $config->qris_image): ?>
                                        <img id="qrisPreview" src="<?= base_url($config->qris_image) ?>" alt="QRIS" class="img-fluid shadow-sm" style="max-height: 250px; border-radius: 8px;">
                                    <?php else: ?>
                                        <img id="qrisPreview" src="<?= base_url('assets/img/qr_placeholder.png') ?>" alt="QRIS Placeholder" class="img-fluid shadow-sm" style="max-height: 250px; border-radius: 8px; opacity: 0.5;">
                                    <?php endif; ?>
                                    <p class="text-muted mt-2 mb-0 small">Preview Tampilan</p>
                                </div>
                                
                                <div class="form-group">
                                    <label>Upload Gambar Baru</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="qrisInput" name="qris_image" accept="image/*">
                                        <label class="custom-file-label" for="qrisInput">Pilih file QRIS...</label>
                                    </div>
                                    <small class="text-muted">Format: JPG/PNG. Maksimal 2MB.</small>
                                </div>

                                <div class="form-group">
                                    <label>Nama Merchant</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-store"></i></span>
                                        </div>
                                        <input type="text" name="qris_merchant_name" class="form-control" value="<?= $config->qris_merchant_name ?? '' ?>" placeholder="Contoh: Koperasi Sekolah">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Bank Info -->
                    <div class="col-md-6 d-flex align-items-stretch">
                        <div class="card card-info card-outline w-100">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-university mr-2"></i>Rekening Bank Transfer</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Nama Bank</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-landmark"></i></span>
                                        </div>
                                        <input type="text" name="bank_name" class="form-control" value="<?= $config->bank_name ?? '' ?>" placeholder="Contoh: Bank BRI, Mandiri, BCA">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Nomor Rekening</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-credit-card"></i></span>
                                        </div>
                                        <input type="text" name="bank_account" class="form-control font-weight-bold text-primary" style="font-size: 1.1rem;" value="<?= $config->bank_account ?? '' ?>" placeholder="xxxx-xxxx-xxxx">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Atas Nama</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <input type="text" name="bank_holder" class="form-control" value="<?= $config->bank_holder ?? '' ?>" placeholder="Nama Pemilik Rekening">
                                    </div>
                                </div>

                                <div class="alert alert-light border mt-4">
                                    <div class="d-flex">
                                        <div class="mr-3 text-info"><i class="fas fa-info-circle fa-2x"></i></div>
                                        <small class="text-muted">Pastikan data rekening benar. Data ini akan ditampilkan kepada siswa saat memilih metode pembayaran Transfer Bank.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card card-outline card-secondary">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-list-ol mr-2"></i>Instruksi Pembayaran</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group mb-0">
                                    <label class="sr-only">Instruksi</label>
                                    <textarea name="payment_instruction" class="form-control" rows="4" placeholder="Tuliskan langkah-langkah pembayaran yang akan muncul di halaman siswa..."><?= $config->payment_instruction ?? '' ?></textarea>
                                    <small class="text-muted mt-2 d-block">* Gunakan baris baru untuk memisahkan setiap langkah.</small>
                                </div>
                            </div>
                            <div class="card-footer bg-light text-right">
                                <button type="submit" class="btn btn-primary btn-lg px-5">
                                    <i class="fas fa-save mr-2"></i> Simpan Konfigurasi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

<script>
$(document).ready(function() {
    ajaxcsrf(); // Init CSRF for AJAX

    // Custom file input label update & Preview
    $("#qrisInput").on("change", function() {
        var fileName = $(this).val().split("\\").pop();
        $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
        
        // Image preview logic
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#qrisPreview').attr('src', e.target.result).css('opacity', '1');
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

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
                    Swal.fire({
                        title: 'Berhasil',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Gagal', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
            },
            complete: function() {
                $('button[type=submit]').attr('disabled', false).html('<i class="fas fa-save mr-2"></i> Simpan Konfigurasi');
            }
        });
    });
});
</script>