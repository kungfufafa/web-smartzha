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
                        <button type="button" data-toggle="modal" data-target="#createTendikModal"
                                class="btn btn-sm btn-primary"><i
                                    class="fas fa-plus"></i><span
                                    class="d-none d-sm-inline-block ml-1">Tambah Tendik</span>
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
                                <button id="hapusterpilih" onclick="bulk_delete()" type="button" class="btn btn-danger" data-toggle="tooltip" title="Hapus Terpilih" disabled="disabled">
                                    <i class="far fa-trash-alt"></i>
                                </button>
                            </div>
                            <div class="col-12 mb-3">
                                <?= form_open('datatendik/delete', array('id' => 'bulk')); ?>
                                <div class="table-responsive">
                                    <table id="table-tendik" class="w-100 table table-striped table-bordered table-hover">
                                        <thead>
                                        <tr>
                                            <th class="align-middle text-center p-0">
                                                <input class="select_all" type="checkbox">
                                            </th>
                                            <th class="align-middle text-center p-0">No.</th>
                                            <th>NAMA & TENDIK</th>
                                            <th>NIP</th>
                                            <th>TIPE</th>
                                            <th>JABATAN</th>
                                            <th>STATUS</th>
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
                <div class="overlay d-none" id="loading">
                    <div class="spinner-grow"></div>
                </div>
            </div>
        </div>
    </section>
</div>

<?= form_open('', array('id' => 'formtendik')); ?>
<div class="modal fade" id="createTendikModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Tambah Tendik</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="nama_tendik">Nama Tendik :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <input id="nama_tendik" type="text" class="form-control" name="nama_tendik"
                                   placeholder="Nama Tendik" required>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="nip">NIP :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            </div>
                            <input type="text" id="nip" class="form-control" name="nip" placeholder="NIP">
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
                        <label for="tipe_tendik">Tipe :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                            </div>
                            <select id="tipe_tendik" class="form-control" name="tipe_tendik">
                                <option value="">Pilih Tipe</option>
                                <option value="TU">TU</option>
                                <option value="PUSTAKAWAN">Pustakawan</option>
                                <option value="LABORAN">Laboran</option>
                                <option value="SATPAM">Satpam</option>
                                <option value="KEBERSIHAN">Kebesihan</option>
                                <option value="PENJAGA">Penjaga</option>
                                <option value="TEKNISI">Teknisi</option>
                                <option value="DRIVER">Driver</option>
                                <option value="LAINNYA">Lainnya</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 col-sm-offset-4">
                        <label for="jabatan">Jabatan :</label>
                    </div>
                    <div class="col-md-8 col-sm-offset-8">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user-tie"></i></span>
                            </div>
                            <input type="text" id="jabatan" class="form-control" name="jabatan" placeholder="Jabatan">
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

<script src="<?= base_url() ?>/assets/app/js/master/tendik/data.js"></script>
