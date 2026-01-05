<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-sm-flex justify-content-between mb-2">
                <h1><?= $subjudul ?></h1>
                <a href="<?= base_url('datatendik') ?>" type="button" class="btn btn-sm btn-danger">
                    <i class="fas fa-arrow-circle-left"></i><span class="d-none d-sm-inline-block ml-1">Kembali</span>
                </a>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <?= form_open('', array('id' => 'formtendik')); ?>
            <input type="hidden" name="id_tendik" value="<?= $tendik->id_tendik ?>">
            <div class="card my-shadow mb-4">
                <div class="card-header">
                    <h6 class="card-title">Edit Data</h6>
                    <div class="card-tools">
                        <button type="reset" class="btn btn-sm bg-warning text-white">
                            <i class="fa fa-sync mr-1"></i> Reset
                        </button>
                        <button type="submit" id="submit" class="btn btn-sm bg-primary text-white">
                            <i class="fas fa-save mr-1"></i> Simpan
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group row">
                                <label for="nama_tendik" class="col-12 col-md-4 col-form-label">Nama Tendik :</label>
                                <div class="col-12 col-md-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <input id="nama_tendik" type="text" class="form-control" name="nama_tendik"
                                               value="<?= $tendik->nama_tendik ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="nip" class="col-12 col-md-4 col-form-label">NIP :</label>
                                <div class="col-12 col-md-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        </div>
                                        <input type="number" id="nip" class="form-control" name="nip"
                                               value="<?= $tendik->nip ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="jenis_kelamin" class="col-12 col-md-4 col-form-label">Jenis Kelamin :</label>
                                <div class="col-12 col-md-8">
                                    <select id="jenis_kelamin" name="jenis_kelamin" class="form-control">
                                        <option value="">Pilih</option>
                                        <option value="L" <?= $tendik->jenis_kelamin == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                        <option value="P" <?= $tendik->jenis_kelamin == 'P' ? 'selected' : '' ?>>Perempuan</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="tipe_tendik" class="col-12 col-md-4 col-form-label">Tipe Tendik :</label>
                                <div class="col-12 col-md-8">
                                    <select id="tipe_tendik" name="tipe_tendik" class="form-control">
                                        <option value="">Pilih</option>
                                        <?php foreach ($tipe_tendik_list as $tipe): ?>
                                            <option value="<?= $tipe ?>" <?= $tendik->tipe_tendik == $tipe ? 'selected' : '' ?>><?= $tipe ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="status_kepegawaian" class="col-12 col-md-4 col-form-label">Status Kepegawaian :</label>
                                <div class="col-12 col-md-8">
                                    <select id="status_kepegawaian" name="status_kepegawaian" class="form-control">
                                        <option value="">Pilih</option>
                                        <option value="PNS" <?= $tendik->status_kepegawaian == 'PNS' ? 'selected' : '' ?>>PNS</option>
                                        <option value="PPPK" <?= $tendik->status_kepegawaian == 'PPPK' ? 'selected' : '' ?>>PPPK</option>
                                        <option value="Honorer" <?= $tendik->status_kepegawaian == 'Honorer' ? 'selected' : '' ?>>Honorer</option>
                                        <option value="Kontrak" <?= $tendik->status_kepegawaian == 'Kontrak' ? 'selected' : '' ?>>Kontrak</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group row">
                                <label for="no_hp" class="col-12 col-md-4 col-form-label">No. HP :</label>
                                <div class="col-12 col-md-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fa fa-phone"></i></span>
                                        </div>
                                        <input type="number" id="no_hp" class="form-control" name="no_hp"
                                               value="<?= $tendik->no_hp ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="email" class="col-12 col-md-4 col-form-label">Email :</label>
                                <div class="col-12 col-md-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="far fa-envelope"></i></span>
                                        </div>
                                        <input type="email" id="email" class="form-control" name="email"
                                               value="<?= $tendik->email ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="tempat_lahir" class="col-12 col-md-4 col-form-label">Tempat Lahir :</label>
                                <div class="col-12 col-md-8">
                                    <input type="text" id="tempat_lahir" class="form-control" name="tempat_lahir"
                                           value="<?= $tendik->tempat_lahir ?>">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="tgl_lahir" class="col-12 col-md-4 col-form-label">Tanggal Lahir :</label>
                                <div class="col-12 col-md-8">
                                    <input type="date" id="tgl_lahir" class="form-control" name="tgl_lahir"
                                           value="<?= $tendik->tgl_lahir ?>">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="agama" class="col-12 col-md-4 col-form-label">Agama :</label>
                                <div class="col-12 col-md-8">
                                    <input type="text" id="agama" class="form-control" name="agama"
                                           value="<?= $tendik->agama ?>">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="tanggal_masuk" class="col-12 col-md-4 col-form-label">Tanggal Masuk :</label>
                                <div class="col-12 col-md-8">
                                    <input type="date" id="tanggal_masuk" class="form-control" name="tanggal_masuk"
                                           value="<?= $tendik->tanggal_masuk ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-12">
                            <div class="row">
                                <label for="jabatan" class="col-12 col-md-2 col-form-label">Jabatan :</label>
                                <div class="col-12 col-md-10">
                                    <input type="text" id="jabatan" class="form-control" name="jabatan"
                                           value="<?= $tendik->jabatan ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-12">
                            <div class="row">
                                <label for="alamat" class="col-12 col-md-2 col-form-label">Alamat :</label>
                                <div class="col-12 col-md-10">
                                    <textarea id="alamat" class="form-control" name="alamat" rows="3"
                                    ><?= $tendik->alamat ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?= form_close(); ?>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    $('#formtendik').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '<?= base_url('datatendik/update') ?>',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.msg,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(function() {
                        window.location.href = '<?= base_url('datatendik') ?>';
                    });
                } else {
                    if (response.errors) {
                        var errorMsg = '';
                        for (var key in response.errors) {
                            if (response.errors[key]) {
                                errorMsg += response.errors[key] + '<br>';
                            }
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            html: errorMsg
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: response.msg
                        });
                    }
                }
            }
        });
    });
});
</script>
