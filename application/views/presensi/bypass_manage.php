<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
$requests = $requests ?? [];
$status_filter = $status_filter ?? 'pending';
$group_filter = $group_filter ?? null;

$status_labels = [
    'pending' => ['label' => 'Pending', 'class' => 'warning'],
    'approved' => ['label' => 'Approved', 'class' => 'success'],
    'rejected' => ['label' => 'Rejected', 'class' => 'danger'],
    'used' => ['label' => 'Used', 'class' => 'secondary'],
    'expired' => ['label' => 'Expired', 'class' => 'dark'],
];
?>

<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $judul ?></h1>
                    <?php if (!empty($subjudul)): ?>
                        <p class="text-muted mb-0"><?= $subjudul ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('presensi/dashboard_admin') ?>">Presensi</a></li>
                        <li class="breadcrumb-item active">Bypass</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filter</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label>Status</label>
                            <select class="form-control" id="filter-status">
                                <?php foreach ($status_labels as $key => $info): ?>
                                    <option value="<?= $key ?>" <?= $key === $status_filter ? 'selected' : '' ?>><?= $info['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Role</label>
                            <select class="form-control" id="filter-group">
                                <option value="">Semua</option>
                                <option value="guru" <?= $group_filter === 'guru' ? 'selected' : '' ?>>Guru</option>
                                <option value="tendik" <?= $group_filter === 'tendik' ? 'selected' : '' ?>>Tendik</option>
                                <option value="siswa" <?= $group_filter === 'siswa' ? 'selected' : '' ?>>Siswa</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-primary btn-block" onclick="applyFilter()">
                                <i class="fas fa-filter"></i> Terapkan
                            </button>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">Klik <strong>Approve</strong> agar user bisa melakukan presensi dengan metode <code>bypass</code> sesuai tipe request.</small>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clipboard-check mr-1"></i> Daftar Bypass Request</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="tbl-bypass">
                            <thead class="bg-light">
                                <tr>
                                    <th width="4%">No</th>
                                    <th>User</th>
                                    <th width="8%">Role</th>
                                    <th width="10%">Tanggal</th>
                                    <th width="8%">Tipe</th>
                                    <th>Alasan</th>
                                    <th>Lokasi</th>
                                    <th width="8%">Foto</th>
                                    <th width="12%">Diajukan</th>
                                    <th width="10%">Status</th>
                                    <th width="14%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($requests)): ?>
                                    <tr>
                                        <td colspan="11" class="text-center text-muted">Tidak ada data</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($requests as $r): ?>
                                        <?php
                                        $status_info = $status_labels[$r->status] ?? ['label' => $r->status, 'class' => 'secondary'];
                                        $foto_url = !empty($r->foto_bukti) ? base_url($r->foto_bukti) : null;
                                        $nama = $r->nama ?? '';
                                        $username = $r->username ?? '';
                                        ?>
                                        <tr>
                                            <td class="text-center"><?= $no++ ?></td>
                                            <td>
                                                <?= htmlspecialchars((string) $nama, ENT_QUOTES, 'UTF-8') ?><br>
                                                <small class="text-muted">@<?= htmlspecialchars((string) $username, ENT_QUOTES, 'UTF-8') ?></small>
                                            </td>
                                            <td class="text-center"><?= htmlspecialchars((string) ($r->role ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-center"><?= !empty($r->tanggal) ? date('d/m/Y', strtotime($r->tanggal)) : '-' ?></td>
                                            <td class="text-center">
                                                <span class="badge badge-info"><?= htmlspecialchars((string) ($r->tipe_bypass ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                            </td>
                                            <td><?= nl2br(htmlspecialchars((string) ($r->alasan ?? ''), ENT_QUOTES, 'UTF-8')) ?></td>
                                            <td><?= htmlspecialchars((string) ($r->lokasi_alternatif ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-center">
                                                <?php if ($foto_url): ?>
                                                    <a href="<?= $foto_url ?>" target="_blank" class="btn btn-xs btn-outline-secondary">
                                                        <i class="fas fa-image"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center"><?= !empty($r->created_at) ? date('d/m/Y H:i', strtotime($r->created_at)) : '-' ?></td>
                                            <td class="text-center">
                                                <span class="badge badge-<?= $status_info['class'] ?>"><?= $status_info['label'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?php if (($r->status ?? '') === 'pending'): ?>
                                                    <button
                                                        type="button"
                                                        class="btn btn-success btn-sm btn-approve"
                                                        data-id="<?= (int) $r->id_bypass ?>"
                                                        data-nama="<?= htmlspecialchars((string) $nama, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-tipe="<?= htmlspecialchars((string) ($r->tipe_bypass ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    >
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="btn btn-danger btn-sm btn-reject"
                                                        data-id="<?= (int) $r->id_bypass ?>"
                                                        data-nama="<?= htmlspecialchars((string) $nama, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-tipe="<?= htmlspecialchars((string) ($r->tipe_bypass ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    >
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Reject -->
<div class="modal fade" id="modalReject" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Tolak Bypass Request</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="reject_id_bypass">
                <p>Tolak request bypass dari <strong id="reject_nama"></strong>?</p>
                <div class="form-group">
                    <label for="reject_note">Catatan Admin <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="reject_note" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="btnConfirmReject">Tolak</button>
            </div>
        </div>
    </div>
</div>

<script>
function applyFilter() {
    var status = document.getElementById('filter-status').value;
    var group = document.getElementById('filter-group').value;

    var url = '<?= base_url('presensi/bypass_manage') ?>?status=' + encodeURIComponent(status);
    if (group) {
        url += '&group=' + encodeURIComponent(group);
    }
    window.location.href = url;
}

$(document).ready(function() {
    $('#tbl-bypass').DataTable({
        "responsive": true,
        "autoWidth": false,
        "order": [[8, 'asc']],
        "language": {
            "url": "<?= base_url('assets/app/js/dataTables.indonesian.json') ?>"
        }
    });

    $('#tbl-bypass').on('click', '.btn-approve', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        var tipe = $(this).data('tipe');

        Swal.fire({
            title: 'Approve bypass?',
            html: 'Approve bypass <strong>' + tipe + '</strong> dari <strong>' + nama + '</strong>?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Approve',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.value || result.isConfirmed) {
                $.ajax({
                    url: '<?= base_url('presensi/bypass_update_status') ?>',
                    type: 'POST',
                    data: {
                        id_bypass: id,
                        status: 'approved',
                        <?= $this->security->get_csrf_token_name() ?>: '<?= $this->security->get_csrf_hash() ?>'
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Berhasil!', 'Bypass berhasil di-approve.', 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Gagal!', res.message || 'Gagal approve bypass', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Terjadi kesalahan server', 'error');
                    }
                });
            }
        });
    });

    $('#tbl-bypass').on('click', '.btn-reject', function() {
        $('#reject_id_bypass').val($(this).data('id'));
        $('#reject_nama').text($(this).data('nama') + ' (' + $(this).data('tipe') + ')');
        $('#reject_note').val('');
        $('#modalReject').modal('show');
    });

    $('#btnConfirmReject').on('click', function() {
        var id = $('#reject_id_bypass').val();
        var note = $('#reject_note').val().trim();

        if (!note) {
            Swal.fire('Perhatian', 'Catatan admin wajib diisi', 'warning');
            return;
        }

        $.ajax({
            url: '<?= base_url('presensi/bypass_update_status') ?>',
            type: 'POST',
            data: {
                id_bypass: id,
                status: 'rejected',
                catatan_admin: note,
                <?= $this->security->get_csrf_token_name() ?>: '<?= $this->security->get_csrf_hash() ?>'
            },
            dataType: 'json',
            success: function(res) {
                $('#modalReject').modal('hide');
                if (res.success) {
                    Swal.fire('Berhasil!', 'Bypass berhasil ditolak.', 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Gagal!', res.message || 'Gagal menolak bypass', 'error');
                }
            },
            error: function() {
                Swal.fire('Error!', 'Terjadi kesalahan server', 'error');
            }
        });
    });
});
</script>
