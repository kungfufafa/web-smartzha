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
            <div class="card card-default my-shadow">
                <div class="card-header with-border">
                    <h3 class="card-title">Master <?= $subjudul ?></h3>
                    <div class="card-tools">
                        <button type="button" onclick="reload_ajax()" class="btn btn-sm btn-default">
                            <i class="fa fa-sync"></i> <span class="d-none d-sm-inline-block ml-1">Reload</span>
                        </button>
                        <button type="button" class="btn btn-action btn-success btn-sm" data-action="aktifkan"
                                data-toggle="tooltip" title="Aktifkan">
                            <i class="fa fa-users m-1"></i><span
                                    class="d-none d-sm-inline-block ml-1">Aktifkan Semua</span>
                        </button>
                        <button type="button" class="btn btn-action btn-danger btn-sm" data-action="nonaktifkan"
                                data-toggle="tooltip" title="Nonaktifkan">
                            <i class="fa fa-ban m-1"></i><span
                                    class="d-none d-sm-inline-block ml-1">Nonaktifkan Semua</span>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="users" class="w-100 table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 40px">No.</th>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>NIP</th>
                                <th>Tipe</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Reset Login</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="overlay d-none" id="loading">
                    <div class="spinner-grow"></div>
                </div>
            </div>
        </div>
    </section>
</div>

<script type="text/javascript">
    var user_id = '<?= $user->id ?>';
</script>

<script src="<?= base_url() ?>/assets/app/js/users/tendik/data.js?v=<?= time() ?>"></script>
