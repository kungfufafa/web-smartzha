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
        <h3 class="card-title">Data Tagihan</h3>
        <div class="card-tools">
            <a href="<?= base_url('pembayaran/createTagihan') ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Buat Tagihan
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3">
                <label>Filter Kelas</label>
                <select id="filterKelas" class="form-control form-control-sm">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($kelas as $k): ?>
                        <option value="<?= $k->id_kelas ?>"><?= $k->nama_kelas ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label>Filter Jenis</label>
                <select id="filterJenis" class="form-control form-control-sm">
                    <option value="">Semua Jenis</option>
                    <?php foreach ($jenis as $j): ?>
                        <option value="<?= $j->id_jenis ?>"><?= $j->nama_jenis ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label>Filter Status</label>
                <select id="filterStatus" class="form-control form-control-sm">
                    <option value="">Semua Status</option>
                    <option value="belum_bayar">Belum Bayar</option>
                    <option value="menunggu_verifikasi">Menunggu Verifikasi</option>
                    <option value="lunas">Lunas</option>
                    <option value="ditolak">Ditolak</option>
                </select>
            </div>
            <div class="col-md-3">
                <label>Filter Bulan</label>
                <select id="filterBulan" class="form-control form-control-sm">
                    <option value="">Semua Bulan</option>
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
        <table id="tableTagihan" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th width="3%"><input type="checkbox" id="checkAll"></th>
                    <th>Kode</th>
                    <th>Siswa</th>
                    <th>Kelas</th>
                    <th>Jenis</th>
                    <th>Bulan</th>
                    <th>Total</th>
                    <th>Jatuh Tempo</th>
                    <th>Status</th>
                    <th width="10%">Aksi</th>
                </tr>
            </thead>
        </table>
        <div class="mt-3">
            <button type="button" class="btn btn-danger btn-sm" onclick="deleteSelected()">
                <i class="fas fa-trash"></i> Hapus Terpilih
            </button>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEdit">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Tagihan</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <?= form_open('', array('id' => 'formEdit')); ?>
                <div class="modal-body">
                    <input type="hidden" name="id_tagihan" id="edit_id_tagihan">
                    <div class="form-group">
                        <label>Nominal <span class="text-danger">*</span></label>
                        <input type="text" name="nominal" id="edit_nominal" class="form-control rupiah" required>
                    </div>
                    <div class="form-group">
                        <label>Diskon</label>
                        <input type="text" name="diskon" id="edit_diskon" class="form-control rupiah" value="0">
                    </div>
                    <div class="form-group">
                        <label>Denda</label>
                        <input type="text" name="denda" id="edit_denda" class="form-control rupiah" value="0">
                    </div>
                    <div class="form-group">
                        <label>Jatuh Tempo <span class="text-danger">*</span></label>
                        <input type="date" name="jatuh_tempo" id="edit_jatuh_tempo" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="keterangan" id="edit_keterangan" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>

        </div>
    </section>

<script>
var table;
var namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

$(document).ready(function() {
    ajaxcsrf(); // Init CSRF for AJAX

    table = $('#tableTagihan').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url('pembayaran/dataTagihan') ?>',
            type: 'POST',
            data: function(d) {
                d.id_kelas = $('#filterKelas').val();
                d.id_jenis = $('#filterJenis').val();
                d.status = $('#filterStatus').val();
                d.bulan = $('#filterBulan').val();
            }
        },
        columns: [
            {data: 'id_tagihan', orderable: false, searchable: false, render: function(data) { return '<input type="checkbox" class="check-item" value="' + data + '">'; }},
            {data: 'kode_tagihan'},
            {data: 'nama_siswa'},
            {data: 'nama_kelas'},
            {data: 'nama_jenis'},
            {data: 'bulan', render: function(data, type, row) {
                return data ? namaBulan[parseInt(data)] + ' ' + row.tahun : '-';
            }},
            {data: 'total', render: function(data) { return 'Rp ' + parseInt(data).toLocaleString('id-ID'); }},
            {data: 'jatuh_tempo', render: function(data) {
                var date = new Date(data);
                return date.toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'});
            }},
            {data: 'status', render: function(data) {
                var badges = {
                    'belum_bayar': '<span class="badge badge-warning">Belum Bayar</span>',
                    'menunggu_verifikasi': '<span class="badge badge-info">Menunggu Verifikasi</span>',
                    'lunas': '<span class="badge badge-success">Lunas</span>',
                    'ditolak': '<span class="badge badge-danger">Ditolak</span>'
                };
                return badges[data] || data;
            }},
            {data: 'id_tagihan', orderable: false, searchable: false, render: function(data, type, row) {
                var btns = '<button class="btn btn-info btn-xs" onclick="editTagihan(' + data + ')"><i class="fas fa-edit"></i></button> ';
                if (row.status !== 'lunas') {
                    btns += '<button class="btn btn-danger btn-xs" onclick="deleteTagihan(' + data + ')"><i class="fas fa-trash"></i></button>';
                }
                return btns;
            }}
        ],
        order: [[1, 'desc']]
    });

    $('#filterKelas, #filterJenis, #filterStatus, #filterBulan').on('change', function() {
        table.ajax.reload();
    });

    // Handle "Select All" click
    $('#checkAll').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('.check-item').prop('checked', isChecked);
    });

    // Sync "Select All" state when individual row is clicked
    $('#tableTagihan tbody').on('change', '.check-item', function() {
        var total = $('.check-item').length;
        var checked = $('.check-item:checked').length;
        $('#checkAll').prop('checked', total > 0 && total === checked);
    });

    // Reset "Select All" when table redraws (pagination, sort, filter)
    table.on('draw', function() {
        $('#checkAll').prop('checked', false);
    });

    $('.rupiah').on('keyup', function() {
        var val = $(this).val().replace(/[^\d]/g, '');
        $(this).val(parseInt(val || 0).toLocaleString('id-ID'));
    });

    $('#formEdit').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '<?= base_url('pembayaran/updateTagihan') ?>',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    $('#modalEdit').modal('hide');
                    table.ajax.reload();
                    showSuccessToast(response.message);
                } else {
                    showDangerToast(response.message);
                }
            }
        });
    });
});

function editTagihan(id) {
    $.get('<?= base_url('pembayaran/getTagihan/') ?>' + id, function(response) {
        if (response.status) {
            var data = response.data;
            $('#edit_id_tagihan').val(data.id_tagihan);
            $('#edit_nominal').val(parseInt(data.nominal).toLocaleString('id-ID'));
            $('#edit_diskon').val(parseInt(data.diskon).toLocaleString('id-ID'));
            $('#edit_denda').val(parseInt(data.denda).toLocaleString('id-ID'));
            $('#edit_jatuh_tempo').val(data.jatuh_tempo);
            $('#edit_keterangan').val(data.keterangan);
            $('#modalEdit').modal('show');
        }
    });
}

function deleteTagihan(id) {
    Swal.fire({
        title: 'Hapus Tagihan?',
        text: 'Data yang sudah dihapus tidak dapat dikembalikan',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Ya, Hapus'
    }).then((result) => {
        if (result.value || result.isConfirmed) {
            $.post('<?= base_url('pembayaran/deleteTagihan') ?>', {ids: [id], '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>'}, function(response) {
                if (response.status) {
                    table.ajax.reload();
                    Swal.fire('Berhasil', response.message, 'success');
                } else {
                    Swal.fire('Gagal', response.message, 'error');
                }
            }, 'json');
        }
    });
}

function deleteSelected() {
    var ids = [];
    $('.check-item:checked').each(function() {
        ids.push($(this).val());
    });

    if (ids.length === 0) {
        Swal.fire('Perhatian', 'Pilih minimal satu data', 'warning');
        return;
    }

    Swal.fire({
        title: 'Hapus ' + ids.length + ' Tagihan?',
        text: 'Data yang sudah dihapus tidak dapat dikembalikan',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Ya, Hapus'
    }).then((result) => {
        if (result.value || result.isConfirmed) {
            $.post('<?= base_url('pembayaran/deleteTagihan') ?>', {ids: ids, '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>'}, function(response) {
                if (response.status) {
                    table.ajax.reload();
                    $('#checkAll').prop('checked', false);
                    Swal.fire('Berhasil', response.message, 'success');
                } else {
                    Swal.fire('Gagal', response.message, 'error');
                }
            }, 'json');
        }
    });
}
</script>
