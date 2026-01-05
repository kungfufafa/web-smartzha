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
            <div class="card card-default my-shadow mb-4">
                <div class="card-header">
                    <h6 class="card-title"><?= $subjudul ?></h6>
                    <div class="card-tools">
                        <button type="button" data-toggle="modal" data-target="#createOrangtuaModal"
                                class="btn btn-sm btn-primary"><i
                                    class="fas fa-plus"></i><span
                                    class="d-none d-sm-inline-block ml-1">Tambah Orang Tua</span>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="dataTables_wrapper dt-bootstrap4 no-footer">
                        <div class="row">
                            <div class="col-sm-12 col-md-6">
                                <div class="dataTables_length">
                                    <label>Show
                                        <select id="users_length" aria-controls="users" class="custom-select custom-select-sm form-control form-control-sm">
                                            <option value="10">10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6">
                                <div class="dataTables_filter">
                                    <button id="btn-clear" type="button" class="btn btn-sm btn-light m-0" data-toggle="tooltip" title="hapus pencarian" disabled="disabled">
                                        <i class="fa fa-times"></i>
                                    </button>
                                    <label>
                                        <input id="input-search" type="search" class="form-control form-control-sm" placeholder="" aria-controls="users">
                                    </label>
                                    <button id="btn-search" type="button" class="btn btn-sm btn-light" data-toggle="tooltip" title="Cari" onclick="applySearch()" disabled="disabled">
                                        <i class="fa fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 mb-2">
                                <button id="hapusterpilih" onclick="bulk_delete()" type="button" class="btn btn-danger" data-toggle="tooltip" title="Hapus Terpilh" disabled="disabled">
                                    <i class="far fa-trash-alt"></i>
                                </button>
                            </div>
                            <div class="col-12 mb-3">
                                <?= form_open('dataorangtua/delete', array('id' => 'bulk')); ?>
                                <div class="table-responsive">
                                    <table id="table-orangtua" class="w-100 table table-striped table-bordered table-hover">
                                        <thead>
                                        <tr>
                                            <th class="align-middle text-center p-0">
                                                <input class="select_all" type="checkbox">
                                            </th>
                                            <th class="align-middle text-center p-0">No.</th>
                                            <th>NAMA LENGKAP</th>
                                            <th>NO. HP</th>
                                            <th>EMAIL</th>
                                            <th>JENIS KELAMIN</th>
                                            <th>STATUS</th>
                                            <th>JML. ANAK</th>
                                            <th class="align-middle text-center p-0">Aksi</th>
                                        </tr>
                                        </thead>
                                        <tbody id="table-body">
                                        </tbody>
                                    </table>
                                </div>
                                <?= form_close() ?>
                            </div>
                            <div class="col-12">
                                <nav aria-label="Page navigation" class="float-right">
                                    <ul class="pagination" id="pagination"></ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="overlay" id="loading">
                    <div class="spinner-grow"></div>
                </div>
            </div>
        </div>
    </section>
</div>

<?= form_open('', array('id' => 'formorangtua')); ?>
<div class="modal fade" id="createOrangtuaModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Tambah Orang Tua</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="nama_lengkap">Nama Lengkap :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <input id="nama_lengkap" type="text" class="form-control" name="nama_lengkap"
                                   placeholder="Nama Lengkap" required>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="nik">NIK :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            </div>
                            <input type="text" id="nik" class="form-control" name="nik" placeholder="NIK">
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="no_hp">No. HP :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            </div>
                            <input type="text" id="no_hp" class="form-control" name="no_hp" placeholder="No. HP" required>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="email">Email :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            </div>
                            <input type="email" id="email" class="form-control" name="email" placeholder="Email">
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="jenis_kelamin">Jenis Kelamin :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                            </div>
                            <select id="jenis_kelamin" class="form-control" name="jenis_kelamin">
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>
