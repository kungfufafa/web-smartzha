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
            <!-- Filter Card -->
            <div class="card collapsed-card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filter</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Tanggal Mulai</label>
                                <input type="date" class="form-control" id="filterStartDate" value="<?= date('Y-m-01') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Tanggal Akhir</label>
                                <input type="date" class="form-control" id="filterEndDate" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" id="filterStatus">
                                    <option value="">Semua Status</option>
                                    <option value="Hadir">Hadir</option>
                                    <option value="Terlambat">Terlambat</option>
                                    <option value="Alpha">Alpha</option>
                                    <option value="Izin">Izin</option>
                                    <option value="Sakit">Sakit</option>
                                    <option value="Cuti">Cuti</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Shift</label>
                                <select class="form-control" id="filterShift">
                                    <option value="">Semua Shift</option>
                                    <?php foreach ($shifts as $shift): ?>
                                    <option value="<?= $shift->id_shift ?>"><?= $shift->nama_shift ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary" id="btnFilter">
                        <i class="fas fa-search"></i> Terapkan Filter
                    </button>
                    <button type="button" class="btn btn-secondary" id="btnReset">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>
            </div>

            <!-- Data Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list-alt mr-1"></i> Log Absensi</h3>
                    <div class="card-tools">
                        <a href="<?= base_url('absensi/exportExcel') ?>?bulan=<?= date('m') ?>&tahun=<?= date('Y') ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tableLogs" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Nama</th>
                                <th>Shift</th>
                                <th>Jam Masuk</th>
                                <th>Jam Pulang</th>
                                <th>Status</th>
                                <th>Metode</th>
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

<!-- Modal Edit Log -->
<div class="modal fade" id="modalEditLog" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Status Log</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="formEditLog">
                <div class="modal-body">
                    <input type="hidden" name="id_log" id="edit_id_log">
                    <div class="form-group">
                        <label>Status Kehadiran</label>
                        <select class="form-control" name="status_kehadiran" id="edit_status" required>
                            <option value="Hadir">Hadir</option>
                            <option value="Terlambat">Terlambat</option>
                            <option value="Alpha">Alpha</option>
                            <option value="Izin">Izin</option>
                            <option value="Sakit">Sakit</option>
                            <option value="Cuti">Cuti</option>
                            <option value="Dinas Luar">Dinas Luar</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea class="form-control" name="keterangan" id="edit_keterangan" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var tableLogs;

$(document).ready(function() {
    tableLogs = $('#tableLogs').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "<?= base_url('absensi/dataLogs') ?>",
            "type": "GET",
            "data": function(d) {
                d.start_date = $('#filterStartDate').val();
                d.end_date = $('#filterEndDate').val();
                d.status = $('#filterStatus').val();
                d.id_shift = $('#filterShift').val();
            }
        },
        "columns": [
            { "data": "tanggal" },
            { "data": "nama_user" },
            { "data": "nama_shift" },
            { 
                "data": "jam_masuk",
                "render": function(data) {
                    return data ? data.substring(11, 16) : '-';
                }
            },
            { 
                "data": "jam_pulang",
                "render": function(data) {
                    return data ? data.substring(11, 16) : '-';
                }
            },
            { 
                "data": "status_kehadiran",
                "render": function(data) {
                    var badge = 'secondary';
                    if (data == 'Hadir') badge = 'success';
                    else if (data == 'Terlambat') badge = 'warning';
                    else if (data == 'Alpha') badge = 'danger';
                    else if (['Izin', 'Sakit', 'Cuti'].indexOf(data) >= 0) badge = 'info';
                    return '<span class="badge badge-' + badge + '">' + data + '</span>';
                }
            },
            { "data": "metode_masuk" },
            {
                "data": "id_log",
                "orderable": false,
                "render": function(data, type, row) {
                    return '<button type="button" class="btn btn-warning btn-xs" onclick="editLog(' + data + ', \'' + row.status_kehadiran + '\')"><i class="fas fa-edit"></i></button>';
                }
            }
        ],
        "order": [[0, 'desc']],
        "responsive": true,
        "autoWidth": false
    });

    $('#btnFilter').on('click', function() {
        tableLogs.ajax.reload();
    });

    $('#btnReset').on('click', function() {
        $('#filterStartDate').val('<?= date("Y-m-01") ?>');
        $('#filterEndDate').val('<?= date("Y-m-d") ?>');
        $('#filterStatus').val('');
        $('#filterShift').val('');
        tableLogs.ajax.reload();
    });

    $('#formEditLog').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&<?= $this->security->get_csrf_token_name() ?>=<?= $this->security->get_csrf_hash() ?>';
        
        $.ajax({
            url: '<?= base_url("absensi/updateLog") ?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.status) {
                    $('#modalEditLog').modal('hide');
                    Swal.fire('Berhasil', res.message, 'success');
                    tableLogs.ajax.reload();
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Terjadi kesalahan server', 'error');
            }
        });
    });
});

function editLog(id, status) {
    $('#edit_id_log').val(id);
    $('#edit_status').val(status);
    $('#edit_keterangan').val('');
    $('#modalEditLog').modal('show');
}
</script>
