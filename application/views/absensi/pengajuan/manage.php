<div class="content-wrapper">
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
                    <h3 class="card-title">Daftar Pengajuan Menunggu Approval</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($list_pengajuan)): ?>
                    <p class="text-center text-muted">Tidak ada pengajuan yang menunggu approval.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Tipe</th>
                                    <th>Jenis Izin</th>
                                    <th>Tanggal</th>
                                    <th>Keterangan</th>
                                    <th>Diajukan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($list_pengajuan as $p): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= $p->first_name . ' ' . $p->last_name ?></td>
                                    <td><span class="badge badge-<?= $p->tipe_pengajuan == 'Izin' ? 'info' : 'warning' ?>"><?= $p->tipe_pengajuan ?></span></td>
                                    <td><?= $p->nama_izin ?? '-' ?></td>
                                    <td><?= date('d/m/Y', strtotime($p->tgl_mulai)) ?> - <?= date('d/m/Y', strtotime($p->tgl_selesai)) ?></td>
                                    <td><?= htmlspecialchars($p->keterangan) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($p->created_at)) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-success btn-approve" data-id="<?= $p->id_pengajuan ?>" data-status="Disetujui">
                                            <i class="fa fa-check"></i> Setuju
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-reject" data-id="<?= $p->id_pengajuan ?>" data-status="Ditolak">
                                            <i class="fa fa-times"></i> Tolak
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="modalReject" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Alasan Penolakan</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="reject_id">
                <div class="form-group">
                    <label>Alasan</label>
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
    $('.btn-approve').on('click', function() {
        if(confirm('Setujui pengajuan ini?')) {
            var id = $(this).data('id');
            $.ajax({
                url: '<?= base_url("pengajuan/approve") ?>',
                type: 'POST',
                data: {
                    id_pengajuan: id,
                    status: 'Disetujui',
                    '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>'
                },
                success: function(res) {
                    if(res.status) {
                        location.reload();
                    } else {
                        alert(res.message);
                    }
                }
            });
        }
    });

    $('.btn-reject').on('click', function() {
        $('#reject_id').val($(this).data('id'));
        $('#modalReject').modal('show');
    });

    $('#btnConfirmReject').on('click', function() {
        var id = $('#reject_id').val();
        var alasan = $('#alasan_tolak').val();
        if(!alasan) {
            alert('Alasan penolakan wajib diisi');
            return;
        }
        $.ajax({
            url: '<?= base_url("pengajuan/approve") ?>',
            type: 'POST',
            data: {
                id_pengajuan: id,
                status: 'Ditolak',
                alasan_tolak: alasan,
                '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>'
            },
            success: function(res) {
                if(res.status) {
                    location.reload();
                } else {
                    alert(res.message);
                }
            }
        });
    });
});
</script>
