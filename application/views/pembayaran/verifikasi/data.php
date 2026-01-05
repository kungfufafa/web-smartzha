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
        <h3 class="card-title">
            Antrian Verifikasi
            <?php if (isset($pending_count) && $pending_count > 0): ?>
                <span class="badge badge-danger"><?= $pending_count ?></span>
            <?php endif; ?>
        </h3>
        <div class="card-tools">
            <a href="<?= base_url('pembayaran/riwayat') ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-history"></i> Riwayat
            </a>
        </div>
    </div>
    <div class="card-body">
        <table id="tableVerifikasi" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Kode Transaksi</th>
                    <th>Siswa</th>
                    <th>Kelas</th>
                    <th>Jenis</th>
                    <th>Nominal</th>
                    <th>Tanggal Upload</th>
                    <th width="15%">Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

        </div>
    </section>

<script>
var table;

$(document).ready(function() {
    ajaxcsrf(); // Init CSRF for AJAX

    table = $('#tableVerifikasi').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url('pembayaran/dataVerifikasi') ?>',
            type: 'POST'
        },
        columns: [
            {data: null, render: function(data, type, row, meta) { return meta.row + 1; }},
            {data: 'kode_transaksi'},
            {data: 'nama_siswa'},
            {data: 'nama_kelas'},
            {data: 'nama_jenis'},
            {data: 'nominal_bayar', render: function(data) { return 'Rp ' + parseInt(data).toLocaleString('id-ID'); }},
            {data: 'waktu_upload', render: function(data) {
                var date = new Date(data);
                return date.toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'});
            }},
            {data: 'id_transaksi', render: function(data) {
                return '<a href="<?= base_url('pembayaran/detailTransaksi/') ?>' + data + '" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Detail</a>';
            }}
        ],
        order: [[6, 'asc']]
    });
});
</script>
