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
        <h3 class="card-title">Jenis Tagihan</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalJenis" onclick="resetForm()">
                <i class="fas fa-plus"></i> Tambah Jenis
            </button>
        </div>
    </div>
    <div class="card-body">
        <table id="tableJenis" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Kode</th>
                    <th>Nama Jenis</th>
                    <th>Nominal Default</th>
                    <th>Tipe</th>
                    <th>Status</th>
                    <th width="15%">Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalJenis">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Form Jenis Tagihan</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <?= form_open('', array('id' => 'formJenis')); ?>
                <div class="modal-body">
                    <input type="hidden" name="id_jenis" id="id_jenis">
                    <div class="form-group">
                        <label>Kode Jenis <span class="text-danger">*</span></label>
                        <input type="text" name="kode_jenis" id="kode_jenis" class="form-control" required maxlength="20" style="text-transform: uppercase;">
                    </div>
                    <div class="form-group">
                        <label>Nama Jenis <span class="text-danger">*</span></label>
                        <input type="text" name="nama_jenis" id="nama_jenis" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Nominal Default</label>
                        <input type="text" name="nominal_default" id="nominal_default" class="form-control rupiah" value="0">
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="keterangan" id="keterangan" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_recurring" id="is_recurring" class="form-check-input" value="1">
                        <label class="form-check-label" for="is_recurring">Tagihan Berulang (Bulanan)</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" checked>
                        <label class="form-check-label" for="is_active">Aktif</label>
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

$(document).ready(function() {
    ajaxcsrf(); // Init CSRF for AJAX

    table = $('#tableJenis').DataTable({
        processing: true,
        serverSide: true,
        ajax: '<?= base_url('pembayaran/dataJenis') ?>',
        columns: [
            {data: null, render: function(data, type, row, meta) { return meta.row + 1; }},
            {data: 'kode_jenis'},
            {data: 'nama_jenis'},
            {data: 'nominal_default', render: function(data) { return 'Rp ' + parseInt(data).toLocaleString('id-ID'); }},
            {data: 'is_recurring', render: function(data) { return data == 1 ? '<span class="badge badge-info">Bulanan</span>' : '<span class="badge badge-secondary">Sekali</span>'; }},
            {data: 'is_active', render: function(data) { return data == 1 ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Nonaktif</span>'; }},
            {data: 'id_jenis', render: function(data) {
                return '<button class="btn btn-warning btn-xs" onclick="editJenis(' + data + ')"><i class="fas fa-edit"></i></button> ' +
                       '<button class="btn btn-danger btn-xs" onclick="deleteJenis(' + data + ')"><i class="fas fa-trash"></i></button>';
            }}
        ]
    });

    $('.rupiah').on('keyup', function() {
        var val = $(this).val().replace(/[^\d]/g, '');
        $(this).val(parseInt(val || 0).toLocaleString('id-ID'));
    });

    $('#formJenis').on('submit', function(e) {
        e.preventDefault();
        var data = $(this).serialize();
        $.ajax({
            url: '<?= base_url('pembayaran/saveJenis') ?>',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    $('#modalJenis').modal('hide');
                    table.ajax.reload();
                    showSuccessToast(response.message);
                } else {
                    showDangerToast(response.message);
                }
            }
        });
    });
});

function resetForm() {
    $('#formJenis')[0].reset();
    $('#id_jenis').val('');
    $('#is_active').prop('checked', true);
}

function editJenis(id) {
    $.get('<?= base_url('pembayaran/getJenis/') ?>' + id, function(response) {
        if (response.status) {
            var data = response.data;
            $('#id_jenis').val(data.id_jenis);
            $('#kode_jenis').val(data.kode_jenis);
            $('#nama_jenis').val(data.nama_jenis);
            $('#nominal_default').val(parseInt(data.nominal_default).toLocaleString('id-ID'));
            $('#keterangan').val(data.keterangan);
            $('#is_recurring').prop('checked', data.is_recurring == 1);
            $('#is_active').prop('checked', data.is_active == 1);
            $('#modalJenis').modal('show');
        }
    });
}

function deleteJenis(id) {
    Swal.fire({
        title: 'Hapus Jenis Tagihan?',
        text: 'Data yang sudah dihapus tidak dapat dikembalikan',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Ya, Hapus'
    }).then((result) => {
        if (result.value || result.isConfirmed) {
            $.post('<?= base_url('pembayaran/deleteJenis') ?>', {ids: [id], '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>'}, function(response) {
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
</script>
