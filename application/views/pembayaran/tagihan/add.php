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
        <h3 class="card-title">Buat Tagihan Baru</h3>
        <div class="card-tools">
            <a href="<?= base_url('pembayaran/tagihan') ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
    <form id="formTagihan">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Jenis Tagihan <span class="text-danger">*</span></label>
                        <select name="id_jenis" id="id_jenis" class="form-control" required>
                            <option value="">-- Pilih Jenis --</option>
                            <?php foreach ($jenis as $j): ?>
                                <option value="<?= $j->id_jenis ?>" data-nominal="<?= $j->nominal_default ?>" data-recurring="<?= $j->is_recurring ?>">
                                    <?= $j->nama_jenis ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Kelas <span class="text-danger">*</span></label>
                        <select name="id_kelas" id="id_kelas" class="form-control" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($kelas as $k): ?>
                                <option value="<?= $k->id_kelas ?>"><?= $k->nama_kelas ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nominal <span class="text-danger">*</span></label>
                        <input type="text" name="nominal" id="nominal" class="form-control rupiah" required>
                    </div>
                    <div class="form-group">
                        <label>Diskon</label>
                        <input type="text" name="diskon" id="diskon" class="form-control rupiah" value="0">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Jatuh Tempo <span class="text-danger">*</span></label>
                        <input type="date" name="jatuh_tempo" id="jatuh_tempo" class="form-control" required>
                    </div>
                    <div class="row" id="periodeBulanan" style="display: none;">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Bulan</label>
                                <select name="bulan" id="bulan" class="form-control">
                                    <option value="">-- Pilih --</option>
                                    <option value="1">Januari</option>
                                    <option value="2">Februari</option>
                                    <option value="3">Maret</option>
                                    <option value="4">April</option>
                                    <option value="5">Mei</option>
                                    <option value="6">Juni</option>
                                    <option value="7">Juli</option>
                                    <option value="8">Agustus</option>
                                    <option value="9">September</option>
                                    <option value="10">Oktober</option>
                                    <option value="11">November</option>
                                    <option value="12">Desember</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tahun</label>
                                <select name="tahun" id="tahun" class="form-control">
                                    <option value="">-- Pilih --</option>
                                    <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                                        <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="keterangan" id="keterangan" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>

            <hr>
            <h5>Pilih Siswa</h5>
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="selectAll">
                    <label class="custom-control-label" for="selectAll">Pilih Semua Siswa</label>
                </div>
            </div>
            <div id="listSiswa" class="row">
                <div class="col-12 text-center text-muted py-4">
                    <i class="fas fa-users fa-3x mb-2"></i>
                    <p>Pilih kelas terlebih dahulu</p>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary" id="btnSubmit" disabled>
                <i class="fas fa-save"></i> Buat Tagihan
            </button>
        </div>
    </form>
</div>

        </div>
    </section>

<script>
$(document).ready(function() {
    ajaxcsrf(); // Init CSRF for AJAX

    $('.rupiah').on('keyup', function() {
        var val = $(this).val().replace(/[^\d]/g, '');
        $(this).val(parseInt(val || 0).toLocaleString('id-ID'));
    });

    $('#id_jenis').on('change', function() {
        var selected = $(this).find(':selected');
        var nominal = selected.data('nominal') || 0;
        var isRecurring = selected.data('recurring') == 1;

        $('#nominal').val(parseInt(nominal).toLocaleString('id-ID'));
        $('#periodeBulanan').toggle(isRecurring);
    });

    $('#id_kelas').on('change', function() {
        var id_kelas = $(this).val();
        if (!id_kelas) {
            $('#listSiswa').html('<div class="col-12 text-center text-muted py-4"><i class="fas fa-users fa-3x mb-2"></i><p>Pilih kelas terlebih dahulu</p></div>');
            updateSubmitButton();
            return;
        }

        $('#listSiswa').html('<div class="col-12 text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');

        $.get('<?= base_url('pembayaran/getSiswaByKelas/') ?>' + id_kelas, function(response) {
            if (response.status && response.data.length > 0) {
                var html = '';
                response.data.forEach(function(siswa) {
                    html += '<div class="col-md-4 col-sm-6 mb-2">';
                    html += '<div class="custom-control custom-checkbox">';
                    html += '<input type="checkbox" class="custom-control-input siswa-check" name="id_siswa[]" id="siswa_' + siswa.id_siswa + '" value="' + siswa.id_siswa + '">';
                    html += '<label class="custom-control-label" for="siswa_' + siswa.id_siswa + '">';
                    html += siswa.nis + ' - ' + siswa.nama;
                    html += '</label>';
                    html += '</div>';
                    html += '</div>';
                });
                $('#listSiswa').html(html);

                $('.siswa-check').on('change', function() {
                    updateSubmitButton();
                    updateSelectAll();
                });
            } else {
                $('#listSiswa').html('<div class="col-12 text-center text-muted py-4"><i class="fas fa-user-slash fa-3x mb-2"></i><p>Tidak ada siswa di kelas ini</p></div>');
            }
            updateSubmitButton();
        });
    });

    $('#selectAll').on('change', function() {
        $('.siswa-check').prop('checked', $(this).prop('checked'));
        updateSubmitButton();
    });

    function updateSelectAll() {
        var total = $('.siswa-check').length;
        var checked = $('.siswa-check:checked').length;
        $('#selectAll').prop('checked', total > 0 && total === checked);
    }

    function updateSubmitButton() {
        var hasChecked = $('.siswa-check:checked').length > 0;
        $('#btnSubmit').prop('disabled', !hasChecked);
    }

    $('#formTagihan').on('submit', function(e) {
        e.preventDefault();

        var form = this; // Capture form element explicitly
        var checkedCount = $('.siswa-check:checked').length;
        if (checkedCount === 0) {
            Swal.fire('Perhatian', 'Pilih minimal satu siswa', 'warning');
            return;
        }

        Swal.fire({
            title: 'Buat Tagihan?',
            text: 'Akan membuat ' + checkedCount + ' tagihan untuk siswa yang dipilih',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Buat'
        }).then((result) => {
            // Support SweetAlert2 older versions (result.value) and newer (result.isConfirmed)
            if (result.value || result.isConfirmed) {
                $('#btnSubmit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

                var formData = new FormData(form); // Use captured form element
                // Append CSRF token
                formData.append('<?= $this->security->get_csrf_token_name() ?>', '<?= $this->security->get_csrf_hash() ?>');

                $.ajax({
                    url: '<?= base_url('pembayaran/saveTagihan') ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status) {
                            Swal.fire({
                                title: 'Berhasil',
                                text: response.message,
                                icon: 'success'
                            }).then(() => {
                                window.location.href = '<?= base_url('pembayaran/tagihan') ?>';
                            });
                        } else {
                            Swal.fire('Gagal', response.message, 'error');
                            $('#btnSubmit').prop('disabled', false).html('<i class="fas fa-save"></i> Buat Tagihan');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Status: ' + status);
                        console.error('Error: ' + error);
                        console.error('Response: ' + xhr.responseText);
                        var msg = 'Terjadi kesalahan sistem';
                        if (xhr.status == 403) msg = 'Akses ditolak (CSRF Error). Silakan refresh halaman.';
                        Swal.fire('Error', msg, 'error');
                        $('#btnSubmit').prop('disabled', false).html('<i class="fas fa-save"></i> Buat Tagihan');
                    }
                });
            }
        });
    });
});
</script>
