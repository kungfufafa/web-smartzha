<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $judul ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('absensi') ?>">Absensi</a></li>
                        <li class="breadcrumb-item active">Kelola Bypass</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Filter -->
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-3">
                            <select class="form-control" id="filterStatus">
                                <option value="">Semua Status</option>
                                <option value="pending" <?= ($filter_status ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="approved" <?= ($filter_status ?? '') == 'approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="rejected" <?= ($filter_status ?? '') == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                <option value="used" <?= ($filter_status ?? '') == 'used' ? 'selected' : '' ?>>Used</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control" id="filterDate" value="<?= $filter_date ?? '' ?>">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary btn-block" id="btnFilter">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Requests -->
            <?php 
            $pending = array_filter($bypass_requests, function($r) { return $r->status == 'pending'; });
            if (count($pending) > 0): 
            ?>
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clock"></i> Menunggu Persetujuan (<?= count($pending) ?>)</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Nama</th>
                                    <th>Tipe</th>
                                    <th>Alasan</th>
                                    <th>Lokasi Alternatif</th>
                                    <th>Diajukan</th>
                                    <th width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending as $req): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($req->tanggal)) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($req->nama_lengkap ?? $req->username) ?></strong>
                                    </td>
                                    <td>
                                        <?php
                                        $badge = 'badge-primary';
                                        if ($req->tipe_bypass == 'checkin') $badge = 'badge-success';
                                        if ($req->tipe_bypass == 'checkout') $badge = 'badge-info';
                                        ?>
                                        <span class="badge <?= $badge ?>"><?= ucfirst($req->tipe_bypass) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars(substr($req->alasan, 0, 50)) ?><?= strlen($req->alasan) > 50 ? '...' : '' ?></td>
                                    <td><?= htmlspecialchars($req->lokasi_alternatif ?? '-') ?></td>
                                    <td><small><?= date('d/m H:i', strtotime($req->created_at)) ?></small></td>
                                    <td>
                                        <button class="btn btn-sm btn-success btn-approve" data-id="<?= $req->id_bypass ?>" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-reject" data-id="<?= $req->id_bypass ?>" title="Reject">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <button class="btn btn-sm btn-info btn-detail" data-json='<?= json_encode($req) ?>' title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- All Requests -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> Semua Request Bypass</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tableBypass" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Nama</th>
                                    <th>Tipe</th>
                                    <th>Alasan</th>
                                    <th>Status</th>
                                    <th>Diproses</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($bypass_requests as $req): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= date('d/m/Y', strtotime($req->tanggal)) ?></td>
                                    <td><?= htmlspecialchars($req->nama_lengkap ?? $req->username) ?></td>
                                    <td>
                                        <?php
                                        $badge = 'badge-primary';
                                        if ($req->tipe_bypass == 'checkin') $badge = 'badge-success';
                                        if ($req->tipe_bypass == 'checkout') $badge = 'badge-info';
                                        ?>
                                        <span class="badge <?= $badge ?>"><?= ucfirst($req->tipe_bypass) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars(substr($req->alasan, 0, 30)) ?><?= strlen($req->alasan) > 30 ? '...' : '' ?></td>
                                    <td>
                                        <?php
                                        $statusBadge = [
                                            'pending' => 'badge-warning',
                                            'approved' => 'badge-success',
                                            'rejected' => 'badge-danger',
                                            'used' => 'badge-info',
                                            'expired' => 'badge-secondary'
                                        ];
                                        ?>
                                        <span class="badge <?= $statusBadge[$req->status] ?? 'badge-secondary' ?>">
                                            <?= ucfirst($req->status) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($req->approved_by): ?>
                                        <small>
                                            <?= htmlspecialchars($req->approved_by_name ?? '-') ?><br>
                                            <?= date('d/m H:i', strtotime($req->approved_at)) ?>
                                        </small>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info btn-detail" data-json='<?= json_encode($req) ?>'>
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Pengajuan Bypass</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="40%">Nama</td>
                                <td><strong id="detailNama">-</strong></td>
                            </tr>
                            <tr>
                                <td>Tanggal</td>
                                <td id="detailTanggal">-</td>
                            </tr>
                            <tr>
                                <td>Tipe</td>
                                <td id="detailTipe">-</td>
                            </tr>
                            <tr>
                                <td>Status</td>
                                <td id="detailStatus">-</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="40%">Diajukan</td>
                                <td id="detailCreated">-</td>
                            </tr>
                            <tr>
                                <td>Diproses</td>
                                <td id="detailApproved">-</td>
                            </tr>
                            <tr>
                                <td>Oleh</td>
                                <td id="detailApprovedBy">-</td>
                            </tr>
                        </table>
                    </div>
                </div>
                <hr>
                <div class="form-group">
                    <label>Alasan Pengajuan:</label>
                    <p id="detailAlasan" class="border rounded p-2 bg-light">-</p>
                </div>
                <div class="form-group">
                    <label>Lokasi Alternatif:</label>
                    <p id="detailLokasi" class="mb-0">-</p>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label>Koordinat:</label>
                        <p id="detailKoordinat">-</p>
                    </div>
                    <div class="col-md-6" id="detailFotoWrapper" style="display: none;">
                        <label>Foto Bukti:</label>
                        <img id="detailFoto" src="" class="img-fluid rounded" style="max-height: 200px;">
                    </div>
                </div>
                <div class="form-group" id="detailCatatanWrapper" style="display: none;">
                    <label>Catatan Admin:</label>
                    <p id="detailCatatan" class="border rounded p-2 bg-light">-</p>
                </div>
            </div>
            <div class="modal-footer" id="detailActions">
            </div>
        </div>
    </div>
</div>

<!-- Modal Reject -->
<div class="modal fade" id="modalReject" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Tolak Pengajuan</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <?= form_open('absensi/rejectBypass', ['id' => 'formReject']) ?>
            <div class="modal-body">
                <input type="hidden" name="id_bypass" id="rejectId">
                <div class="form-group">
                    <label>Alasan Penolakan <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="catatan_admin" rows="3" required placeholder="Berikan alasan penolakan..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger">Tolak</button>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tableBypass').DataTable({
        responsive: true,
        order: [[1, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json'
        }
    });

    $('#btnFilter').on('click', function() {
        var status = $('#filterStatus').val();
        var date = $('#filterDate').val();
        var url = '<?= base_url("absensi/manageBypass") ?>?';
        if (status) url += 'status=' + status + '&';
        if (date) url += 'date=' + date;
        window.location.href = url;
    });

    $('.btn-detail').on('click', function() {
        var data = $(this).data('json');
        showDetail(data);
    });

    $('.btn-approve').on('click', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Approve Bypass?',
            text: 'User akan dapat absensi tanpa validasi lokasi GPS.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Ya, Approve',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                approveBypass(id);
            }
        });
    });

    $('.btn-reject').on('click', function() {
        var id = $(this).data('id');
        $('#rejectId').val(id);
        $('#modalReject').modal('show');
    });

    $('#formReject').on('submit', function(e) {
        e.preventDefault();
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status) {
                    toastr.success(res.message);
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    toastr.error(res.message);
                    btn.prop('disabled', false);
                }
            },
            error: function() {
                toastr.error('Terjadi kesalahan sistem');
                btn.prop('disabled', false);
            }
        });
    });
});

function showDetail(data) {
    $('#detailNama').text(data.nama_lengkap || data.username);
    $('#detailTanggal').text(formatDate(data.tanggal));
    $('#detailTipe').html('<span class="badge badge-primary">' + data.tipe_bypass.toUpperCase() + '</span>');
    
    var statusBadge = {
        'pending': 'badge-warning',
        'approved': 'badge-success',
        'rejected': 'badge-danger',
        'used': 'badge-info'
    };
    $('#detailStatus').html('<span class="badge ' + (statusBadge[data.status] || 'badge-secondary') + '">' + data.status.toUpperCase() + '</span>');
    
    $('#detailCreated').text(formatDateTime(data.created_at));
    $('#detailApproved').text(data.approved_at ? formatDateTime(data.approved_at) : '-');
    $('#detailApprovedBy').text(data.approved_by_name || '-');
    $('#detailAlasan').text(data.alasan);
    $('#detailLokasi').text(data.lokasi_alternatif || '-');
    
    if (data.latitude && data.longitude) {
        $('#detailKoordinat').html('<a href="https://maps.google.com/?q=' + data.latitude + ',' + data.longitude + '" target="_blank">' + data.latitude + ', ' + data.longitude + '</a>');
    } else {
        $('#detailKoordinat').text('-');
    }
    
    if (data.foto_bukti) {
        $('#detailFoto').attr('src', '<?= base_url() ?>' + data.foto_bukti);
        $('#detailFotoWrapper').show();
    } else {
        $('#detailFotoWrapper').hide();
    }
    
    if (data.catatan_admin) {
        $('#detailCatatan').text(data.catatan_admin);
        $('#detailCatatanWrapper').show();
    } else {
        $('#detailCatatanWrapper').hide();
    }
    
    var actions = '';
    if (data.status == 'pending') {
        actions = '<button class="btn btn-success" onclick="approveBypass(' + data.id_bypass + ')"><i class="fas fa-check"></i> Approve</button> ';
        actions += '<button class="btn btn-danger" onclick="$(\'#modalDetail\').modal(\'hide\');$(\'#rejectId\').val(' + data.id_bypass + ');$(\'#modalReject\').modal(\'show\');"><i class="fas fa-times"></i> Reject</button>';
    }
    actions += '<button type="button" class="btn btn-secondary ml-2" data-dismiss="modal">Tutup</button>';
    $('#detailActions').html(actions);
    
    $('#modalDetail').modal('show');
}

function approveBypass(id) {
    $.ajax({
        url: '<?= base_url("absensi/approveBypass") ?>',
        type: 'POST',
        data: {
            id_bypass: id,
            '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>'
        },
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                toastr.success(res.message);
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                toastr.error(res.message);
            }
        },
        error: function() {
            toastr.error('Terjadi kesalahan sistem');
        }
    });
}

function formatDate(dateStr) {
    var d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
}

function formatDateTime(dateStr) {
    var d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>
