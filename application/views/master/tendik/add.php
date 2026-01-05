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
            <div class="card my-shadow mb-4">
                <div class="card-header">
                    <h6 class="card-title">Tambah Manual</h6>
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
                                    <label for="nama_tendik">Nama Tendik :</label>
                                </div>
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <input id="nama_tendik" type="text" class="form-control" name="nama_tendik"
                                               placeholder="Nama Tendik" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="nip">NIP :</label>
                                </div>
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        </div>
                                        <input type="number" id="nip" class="form-control" name="nip" placeholder="NIP">
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
                                        <option value="L">Laki-laki</option>
                                        <option value="P">Perempuan</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="tipe_tendik">Tipe Tendik :</label>
                                </div>
                                <div class="col-md-8">
                                    <select id="tipe_tendik" name="tipe_tendik" class="form-control">
                                        <option value="">Pilih</option>
                                        <?php foreach ($tipe_tendik_list as $tipe): ?>
                                            <option value="<?= $tipe ?>"><?= $tipe ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="status_kepegawaian">Status Kepegawaian :</label>
                                </div>
                                <div class="col-md-8">
                                    <select id="status_kepegawaian" name="status_kepegawaian" class="form-control">
                                        <option value="">Pilih</option>
                                        <option value="PNS">PNS</option>
                                        <option value="PPPK">PPPK</option>
                                        <option value="Honorer" selected>Honorer</option>
                                        <option value="Kontrak">Kontrak</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="no_hp">No. HP :</label>
                                </div>
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fa fa-phone"></i></span>
                                        </div>
                                        <input type="number" id="no_hp" class="form-control" name="no_hp"
                                               placeholder="No. HP" required>
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
                                               placeholder="Email">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="tempat_lahir">Tempat Lahir :</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" id="tempat_lahir" class="form-control" name="tempat_lahir"
                                           placeholder="Tempat Lahir">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="tgl_lahir">Tanggal Lahir :</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="date" id="tgl_lahir" class="form-control" name="tgl_lahir">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="agama">Agama :</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" id="agama" class="form-control" name="agama"
                                           placeholder="Agama">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="tanggal_masuk">Tanggal Masuk :</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="date" id="tanggal_masuk" class="form-control" name="tanggal_masuk">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="row">
                                <div class="col-md-2">
                                    <label for="jabatan">Jabatan :</label>
                                </div>
                                <div class="col-md-10">
                                    <input type="text" id="jabatan" class="form-control" name="jabatan"
                                           placeholder="Jabatan">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="row">
                                <div class="col-md-2">
                                    <label for="alamat">Alamat :</label>
                                </div>
                                <div class="col-md-10">
                                    <textarea id="alamat" class="form-control" name="alamat" rows="3"
                                              placeholder="Alamat"></textarea>
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
            url: '<?= base_url('datatendik/create') ?>',
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
