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
        <h3 class="card-title">Riwayat Verifikasi</h3>
        <div class="card-tools">
            <a href="<?= base_url('pembayaran/verifikasi') ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-list"></i> Antrian Verifikasi
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3">
                <label>Tanggal Dari</label>
                <input type="date" id="tanggalDari" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="col-md-3">
                <label>Tanggal Sampai</label>
                <input type="date" id="tanggalSampai" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-3">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-primary btn-sm btn-block" onclick="table.ajax.reload()">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
         </div>
         <div class="table-responsive">
             <table id="tableRiwayat" class="table table-bordered table-striped">
                 <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Kode Transaksi</th>
                    <th>Siswa</th>
                    <th>Jenis</th>
                    <th>Nominal</th>
                    <th>Status</th>
                    <th>Diverifikasi</th>
                    <th>Oleh</th>
                    <th width="10%">Aksi</th>
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

    table = $('#tableRiwayat').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= base_url('pembayaran/dataRiwayat') ?>',
            type: 'POST',
            data: function(d) {
                d.tanggal_dari = $('#tanggalDari').val();
                d.tanggal_sampai = $('#tanggalSampai').val();
            }
        },
        columns: [
            {data: null, render: function(data, type, row, meta) { return meta.row + 1; }},
            {data: 'kode_transaksi'},
            {data: 'nama_siswa'},
            {data: 'nama_jenis'},
            {data: 'nominal_bayar', render: function(data) { return 'Rp ' + parseInt(data).toLocaleString('id-ID'); }},
            {data: 'status', render: function(data) {
                var badges = {
                    'verified': '<span class="badge badge-success">Disetujui</span>',
                    'rejected': '<span class="badge badge-danger">Ditolak</span>',
                    'cancelled': '<span class="badge badge-dark">Dibatalkan</span>'
                };
                return badges[data] || data;
            }},
            {data: 'verified_at', render: function(data) {
                if (!data) return '-';
                var date = new Date(data);
                return date.toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'});
            }},
            {data: 'verified_by_name', render: function(data) { return data || '-'; }},
            {data: 'id_transaksi', render: function(data) {
                return '<a href="<?= base_url('pembayaran/detailTransaksi/') ?>' + data + '" class="btn btn-info btn-xs"><i class="fas fa-eye"></i></a>';
            }}
        ],
        order: [[6, 'desc']]
    });
});
</script>
