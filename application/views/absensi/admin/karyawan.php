<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $judul ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('absensi') ?>">Absensi</a></li>
                        <li class="breadcrumb-item active"><?= $subjudul ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-tie mr-1"></i> Daftar Karyawan</h3>
                    <div class="card-tools">
                        <a href="<?= base_url('absensi/assignShift') ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-clock"></i> Assign Shift
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tableKaryawan" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Nama</th>
                                <th>Grup</th>
                                <th>Shift</th>
                                <th>Tgl Efektif</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Karyawan</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <table class="table table-borderless">
                    <tr><th width="40%">Username</th><td id="detail_username"></td></tr>
                    <tr><th>Nama</th><td id="detail_nama"></td></tr>
                    <tr><th>Email</th><td id="detail_email"></td></tr>
                    <tr><th>Shift</th><td id="detail_shift"></td></tr>
                    <tr><th>Tgl Efektif</th><td id="detail_tgl_efektif"></td></tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
var tableKaryawan;

$(document).ready(function() {
    tableKaryawan = $('#tableKaryawan').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "<?= base_url('absensi/dataKaryawan') ?>",
            "type": "GET"
        },
        "columns": [
            { "data": "username" },
            { "data": "nama_user" },
            { 
                "data": "group_name",
                "render": function(data) {
                    return '<span class="badge badge-secondary">' + (data ? data.charAt(0).toUpperCase() + data.slice(1) : '-') + '</span>';
                }
            },
            { 
                "data": "shift_name",
                "render": function(data) {
                    return data ? '<span class="badge badge-info">' + data + '</span>' : '<span class="text-muted">-</span>';
                }
            },
            { 
                "data": "tgl_efektif",
                "render": function(data) {
                    return data ? data : '-';
                }
            },
            { 
                "data": "active",
                "render": function(data) {
                    return data == 1 ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-secondary">Nonaktif</span>';
                }
            },
            {
                "data": "id",
                "orderable": false,
                "render": function(data) {
                    return '<button type="button" class="btn btn-info btn-xs" onclick="showDetail(' + data + ')"><i class="fas fa-eye"></i></button>';
                }
            }
        ],
        "order": [[1, 'asc']],
        "responsive": true,
        "autoWidth": false
    });
});

function showDetail(id) {
    $.ajax({
        url: '<?= base_url("absensi/getKaryawan") ?>/' + id,
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                var data = res.data;
                $('#detail_username').text(data.username);
                $('#detail_nama').text(data.nama_user || data.username);
                $('#detail_email').text(data.email);
                $('#detail_shift').text(data.shift_name || '-');
                $('#detail_tgl_efektif').text(data.tgl_efektif || '-');
                $('#modalDetail').modal('show');
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }
    });
}
</script>
