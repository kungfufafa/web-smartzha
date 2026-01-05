<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-sm-flex justify-content-between mb-2">
                <h1><?= $subjudul ?></h1>
                <a href="<?= base_url('dataorangtua') ?>" type="button" class="btn btn-sm btn-danger">
                    <i class="fas fa-arrow-circle-left"></i><span class="d-none d-sm-inline-block ml-1">Kembali</span>
                </a>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <?= form_open('', array('id' => 'formorangtua')); ?>
            <input type="hidden" name="id_orangtua" value="<?= $orangtua->id_orangtua ?>">
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
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="nama_lengkap">Nama Lengkap :</label>
                                </div>
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <input id="nama_lengkap" type="text" class="form-control" name="nama_lengkap"
                                               value="<?= $orangtua->nama_lengkap ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="nik">NIK :</label>
                                </div>
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        </div>
                                        <input type="number" id="nik" class="form-control" name="nik"
                                               value="<?= $orangtua->nik ?>" maxlength="16">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="no_hp">No. HP :</label>
                                </div>
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fa fa-phone"></i></span>
                                        </div>
                                        <input type="number" id="no_hp" class="form-control" name="no_hp"
                                               value="<?= $orangtua->no_hp ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="email">Email :</label>
                                </div>
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="far fa-envelope"></i></span>
                                        </div>
                                        <input type="email" id="email" class="form-control" name="email"
                                               value="<?= $orangtua->email ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="jenis_kelamin">Jenis Kelamin :</label>
                                </div>
                                <div class="col-md-8">
                                    <select id="jenis_kelamin" name="jenis_kelamin" class="form-control">
                                        <option value="">Pilih</option>
                                        <option value="L" <?= $orangtua->jenis_kelamin == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                        <option value="P" <?= $orangtua->jenis_kelamin == 'P' ? 'selected' : '' ?>>Perempuan</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="agama">Agama :</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" id="agama" class="form-control" name="agama"
                                           value="<?= $orangtua->agama ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="pendidikan_terakhir">Pendidikan Terakhir :</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" id="pendidikan_terakhir" class="form-control" name="pendidikan_terakhir"
                                           value="<?= $orangtua->pendidikan_terakhir ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="pekerjaan">Pekerjaan :</label>
                                </div>
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                                        </div>
                                        <input type="text" id="pekerjaan" class="form-control" name="pekerjaan"
                                               value="<?= $orangtua->pekerjaan ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="alamat">Alamat :</label>
                                </div>
                                <div class="col-md-8">
                                    <textarea id="alamat" class="form-control" name="alamat" rows="3"
                                    ><?= $orangtua->alamat ?></textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="kota">Kota :</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" id="kota" class="form-control" name="kota"
                                           value="<?= $orangtua->kota ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="provinsi">Provinsi :</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" id="provinsi" class="form-control" name="provinsi"
                                           value="<?= $orangtua->provinsi ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="kode_pos">Kode Pos :</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="number" id="kode_pos" class="form-control" name="kode_pos"
                                           value="<?= $orangtua->kode_pos ?>">
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
    $('#formorangtua').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '<?= base_url('dataorangtua/update') ?>',
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
                        window.location.href = '<?= base_url('dataorangtua') ?>';
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
