<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="content-wrapper bg-white">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $judul ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active"><?= $judul ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <?php if ($this->session->flashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= $this->session->flashdata('success') ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>
            <?php if ($this->session->flashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $this->session->flashdata('error') ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <div class="card card-default my-shadow mb-4">
                <div class="card-header">
                    <h6 class="card-title"><?= $subjudul ?></h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="tbl-pengajuan">
                            <thead class="bg-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Nama</th>
                                    <th>Tipe</th>
                                    <th>Jenis Izin</th>
                                    <th>Tanggal</th>
                                    <th>Jumlah Hari</th>
                                    <th>Keterangan</th>
                                    <th>Diajukan</th>
                                    <th width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($list_pengajuan)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">Tidak ada pengajuan pending</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($list_pengajuan as $p): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($p->first_name . ' ' . $p->last_name) ?></td>
                                            <td>
                                                <?php
                                                $badge_class = [
                                                    'Izin' => 'badge-info',
                                                    'Sakit' => 'badge-warning',
                                                    'Cuti' => 'badge-primary',
                                                    'Dinas' => 'badge-secondary',
                                                    'Lembur' => 'badge-dark',
                                                    'IzinKeluar' => 'badge-light'
                                                ];
                                                $class = $badge_class[$p->tipe_pengajuan] ?? 'badge-secondary';
                                                ?>
                                                <span class="badge <?= $class ?>"><?= $p->tipe_pengajuan ?></span>
                                            </td>
                                            <td><?= $p->nama_izin ?? '-' ?></td>
                                            <td>
                                                <?= date('d/m/Y', strtotime($p->tgl_mulai)) ?>
                                                <?php if ($p->tgl_mulai !== $p->tgl_selesai): ?>
                                                    - <?= date('d/m/Y', strtotime($p->tgl_selesai)) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center"><?= $p->jumlah_hari ?> hari</td>
                                            <td><?= htmlspecialchars($p->keterangan) ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($p->created_at)) ?></td>
                                            <td>
                                                <button type="button" class="btn btn-success btn-sm btn-approve" 
                                                    data-id="<?= $p->id_pengajuan ?>" 
                                                    data-nama="<?= htmlspecialchars($p->first_name . ' ' . $p->last_name) ?>"
                                                    data-tipe="<?= $p->tipe_pengajuan ?>">
                                                    <i class="fas fa-check"></i> Setuju
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm btn-reject" 
                                                    data-id="<?= $p->id_pengajuan ?>" 
                                                    data-nama="<?= htmlspecialchars($p->first_name . ' ' . $p->last_name) ?>"
                                                    data-tipe="<?= $p->tipe_pengajuan ?>">
                                                    <i class="fas fa-times"></i> Tolak
                                                </button>
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

<!-- Modal Tolak -->
<div class="modal fade" id="modalTolak" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Tolak Pengajuan</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="reject_id_pengajuan">
                <p>Tolak pengajuan <strong id="reject_nama"></strong>?</p>
                <div class="form-group">
                    <label for="alasan_tolak">Alasan Penolakan <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="alasan_tolak" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="btnConfirmReject">Tolak Pengajuan</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tbl-pengajuan').DataTable({
        "responsive": true,
        "autoWidth": false,
        "order": [[7, 'asc']],
        "language": {
            "url": "<?= base_url('assets/app/js/dataTables.indonesian.json') ?>"
        }
    });

    // Approve
    $('.btn-approve').on('click', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        var tipe = $(this).data('tipe');

        Swal.fire({
            title: 'Setujui Pengajuan?',
            html: 'Setujui pengajuan <strong>' + tipe + '</strong> dari <strong>' + nama + '</strong>?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Setujui',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= base_url('pengajuan/approve') ?>',
                    type: 'POST',
                    data: {
                        id_pengajuan: id,
                        status: 'Disetujui',
                        <?= $this->security->get_csrf_token_name() ?>: '<?= $this->security->get_csrf_hash() ?>'
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status) {
                            Swal.fire('Berhasil!', res.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Gagal!', res.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Terjadi kesalahan server', 'error');
                    }
                });
            }
        });
    });

    // Reject - show modal
    $('.btn-reject').on('click', function() {
        $('#reject_id_pengajuan').val($(this).data('id'));
        $('#reject_nama').text($(this).data('nama') + ' (' + $(this).data('tipe') + ')');
        $('#alasan_tolak').val('');
        $('#modalTolak').modal('show');
    });

    // Confirm reject
    $('#btnConfirmReject').on('click', function() {
        var id = $('#reject_id_pengajuan').val();
        var alasan = $('#alasan_tolak').val().trim();

        if (!alasan) {
            Swal.fire('Perhatian', 'Alasan penolakan wajib diisi', 'warning');
            return;
        }

        $.ajax({
            url: '<?= base_url('pengajuan/approve') ?>',
            type: 'POST',
            data: {
                id_pengajuan: id,
                status: 'Ditolak',
                alasan_tolak: alasan,
                <?= $this->security->get_csrf_token_name() ?>: '<?= $this->security->get_csrf_hash() ?>'
            },
            dataType: 'json',
            success: function(res) {
                $('#modalTolak').modal('hide');
                if (res.status) {
                    Swal.fire('Berhasil!', res.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Gagal!', res.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error!', 'Terjadi kesalahan server', 'error');
            }
        });
    });
});
</script>
