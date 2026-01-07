<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper" style="margin-top: -1px;">
    <div class="sticky"></div>
    <section class="content overlap p-4">
        <div class="container">
            <!-- Profile Card -->
            <div class="info-box bg-transparent shadow-none">
                <?php 
                $foto = 'assets/adminlte/dist/img/avatar5.png';
                ?>
                <img class="avatar rounded-circle" src="<?= base_url($foto) ?>" width="80" height="80" style="object-fit: cover;">
                <div class="info-box-content">
                    <h5 class="info-box-text text-white text-wrap"><b><?= isset($profile) ? $profile->nama_lengkap : 'Tendik' ?></b></h5>
                    <span class="info-box-text text-white"><?= $judul ?></span>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card card-secondary">
                        <div class="card-header">
                            <div class="card-title text-white">
                                <i class="fas fa-user mr-2"></i><?= $subjudul ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($this->session->flashdata('success')): ?>
                                <div class="alert alert-success"><?= $this->session->flashdata('success') ?></div>
                            <?php endif; ?>
                            <?php if ($this->session->flashdata('error')): ?>
                                <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-4 text-center mb-4">
                                    <img src="<?= base_url($foto) ?>" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                    <h4><?= isset($profile) ? $profile->nama_lengkap : 'Unknown' ?></h4>
                                    <p class="text-muted"><?= isset($profile) ? $profile->jabatan : '' ?></p>
                                </div>
                                <div class="col-md-8">
                                    <div class="table-responsive">
                                        <table class="table table-borderless">
                                            <tr>
                                                <td width="200"><i class="fas fa-user mr-2"></i>Nama Lengkap</td>
                                                <td><strong><?= isset($tendik) && $tendik ? $tendik->nama_tendik : '-' ?></strong></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-id-card mr-2"></i>NIP/NIK</td>
                                                <td><?= isset($tendik) && $tendik ? ($tendik->nip ?: '-') : '-' ?></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-briefcase mr-2"></i>Jabatan</td>
                                                <td><?= isset($tendik) && $tendik ? ($tendik->tipe_tendik ?: '-') : '-' ?></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-envelope mr-2"></i>Email</td>
                                                <td><?= isset($user) ? $user->email : '-' ?></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-phone mr-2"></i>No. Telepon</td>
                                                <td><?= isset($tendik) && $tendik ? ($tendik->no_telepon ?: '-') : '-' ?></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-map-marker-alt mr-2"></i>Alamat</td>
                                                <td><?= isset($tendik) && $tendik ? ($tendik->alamat ?: '-') : '-' ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Akun -->
            <div class="row">
                <div class="col-12">
                    <div class="card card-info">
                        <div class="card-header">
                            <div class="card-title text-white">
                                <i class="fas fa-key mr-2"></i>Informasi Akun
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="200"><i class="fas fa-user-circle mr-2"></i>Username</td>
                                        <td><strong><?= isset($user) ? $user->username : '-' ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-clock mr-2"></i>Terakhir Login</td>
                                        <td><?= isset($user) && $user->last_login ? date('d/m/Y H:i', $user->last_login) : '-' ?></td>
                                    </tr>
                                </table>
	                            </div>
	                            <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#modalChangePassword">
	                                <i class="fas fa-lock mr-1"></i> Ubah Password
	                            </button>
	                        </div>
	                    </div>
	                </div>
	            </div>
	        </div>
	    </section>
	</div>

<!-- Modal: Change Password -->
<div class="modal fade" id="modalChangePassword" tabindex="-1" role="dialog" aria-labelledby="modalChangePasswordLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="modalChangePasswordLabel">
                    <i class="fas fa-lock mr-2"></i>Ubah Password
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formChangePassword">
                <div class="modal-body">
                    <?php
                    $min_password_length = (int) $this->config->item('min_password_length', 'ion_auth');
                    if ($min_password_length <= 0) {
                        $min_password_length = 6;
                    }
                    ?>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>Password minimal <?= $min_password_length ?> karakter.
                    </div>

                    <div class="form-group">
                        <label>Password Lama <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="old" id="cp-old" required autocomplete="current-password">
                        <small id="cp-err-old" class="text-danger d-none"></small>
                    </div>

                    <div class="form-group">
                        <label>Password Baru <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="new" id="cp-new" required minlength="<?= $min_password_length ?>" autocomplete="new-password">
                        <small id="cp-err-new" class="text-danger d-none"></small>
                    </div>

                    <div class="form-group">
                        <label>Konfirmasi Password Baru <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="new_confirm" id="cp-new-confirm" required minlength="<?= $min_password_length ?>" autocomplete="new-password">
                        <small id="cp-err-new-confirm" class="text-danger d-none"></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning" id="btnSavePassword">
                        <i class="fas fa-save mr-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var csrfName = '<?= $this->security->get_csrf_token_name() ?>';
var csrfHash = '<?= $this->security->get_csrf_hash() ?>';

function clearChangePasswordErrors() {
    var fields = [
        { input: '#cp-old', err: '#cp-err-old' },
        { input: '#cp-new', err: '#cp-err-new' },
        { input: '#cp-new-confirm', err: '#cp-err-new-confirm' }
    ];

    fields.forEach(function(f) {
        $(f.input).removeClass('is-invalid');
        $(f.err).text('').addClass('d-none');
    });
}

function resetChangePasswordForm() {
    var form = document.getElementById('formChangePassword');
    if (form) {
        form.reset();
    }
    clearChangePasswordErrors();
}

function showChangePasswordFieldErrors(errors) {
    if (!errors) {
        return;
    }

    if (errors.old) {
        $('#cp-old').addClass('is-invalid');
        $('#cp-err-old').text(errors.old).removeClass('d-none');
    }
    if (errors.new) {
        $('#cp-new').addClass('is-invalid');
        $('#cp-err-new').text(errors.new).removeClass('d-none');
    }
    if (errors.new_confirm) {
        $('#cp-new-confirm').addClass('is-invalid');
        $('#cp-err-new-confirm').text(errors.new_confirm).removeClass('d-none');
    }
}

$(document).ready(function() {
    $('#modalChangePassword').on('shown.bs.modal', function() {
        resetChangePasswordForm();
        $('#cp-old').trigger('focus');
    });

    $('#modalChangePassword').on('hidden.bs.modal', function() {
        resetChangePasswordForm();
    });

    $('#cp-old, #cp-new, #cp-new-confirm').on('input', function() {
        clearChangePasswordErrors();
    });

    $('#formChangePassword').on('submit', function(e) {
        e.preventDefault();
        clearChangePasswordErrors();

        var form = this;
        if (!form.reportValidity()) {
            return;
        }

        var formData = new FormData(form);
        formData.append(csrfName, csrfHash);

        $('#btnSavePassword').prop('disabled', true);

        $.ajax({
            url: base_url + 'tendik/change_password',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                if (res && res.status) {
                    Swal.fire('Berhasil', res.message || 'Password berhasil diubah', 'success').then(function(result) {
                        if (result.value || result.isConfirmed) {
                            $('#modalChangePassword').modal('hide');
                        }
                    });
                    return;
                }

                if (res && res.errors) {
                    showChangePasswordFieldErrors(res.errors);
                }

                Swal.fire('Gagal', (res && res.message) ? res.message : 'Gagal mengubah password', 'error');
            },
            error: function() {
                Swal.fire('Error', 'Terjadi kesalahan server', 'error');
            },
            complete: function() {
                $('#btnSavePassword').prop('disabled', false);
            }
        });
    });

    if (window.location.hash === '#change-password') {
        $('#modalChangePassword').modal('show');
    }
});
</script>
