<!-- Content Wrapper -->
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $judul ?></h1>
                </div>
                <div class="col-sm-6">
                    <button type="button" class="btn btn-primary float-right" data-toggle="modal" data-target="#modalPengajuan">
                        <i class="fa fa-plus"></i> Buat Pengajuan
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>Tanggal Pengajuan</th>
                                <th>Tipe</th>
                                <th>Detail</th>
                                <th>Waktu</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($list_pengajuan)): ?>
                                <tr><td colspan="6" class="text-center">Belum ada pengajuan.</td></tr>
                            <?php else: ?>
                                <?php foreach($list_pengajuan as $p): ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($p->created_at)) ?></td>
                                    <td><?= $p->tipe_pengajuan ?></td>
                                    <td>
                                        <?= $p->tipe_pengajuan == 'Izin' ? $p->nama_izin : 'Lembur' ?>
                                    </td>
                                    <td>
                                        <?php if($p->tipe_pengajuan == 'Izin'): ?>
                                            <?= date('d/m', strtotime($p->tgl_mulai)) ?> - <?= date('d/m', strtotime($p->tgl_selesai)) ?>
                                        <?php else: ?>
                                            <?= date('d M', strtotime($p->tgl_mulai)) ?> (<?= substr($p->jam_mulai,0,5) ?>-<?= substr($p->jam_selesai,0,5) ?>)
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $badge = 'secondary';
                                            if($p->status == 'Disetujui') $badge = 'success';
                                            if($p->status == 'Ditolak') $badge = 'danger';
                                            if($p->status == 'Pending') $badge = 'warning';
                                        ?>
                                        <span class="badge badge-<?= $badge ?>"><?= $p->status ?></span>
                                    </td>
                                    <td><?= $p->keterangan ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal -->
<div class="modal fade" id="modalPengajuan" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buat Pengajuan Baru</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?= form_open('pengajuan/create') ?>
            <div class="modal-body">
                <div class="form-group">
                    <label>Tipe Pengajuan</label>
                    <select class="form-control" name="tipe_pengajuan" id="tipe_pengajuan" onchange="toggleForm()">
                        <option value="Izin">Izin / Cuti / Sakit</option>
                        <option value="Lembur">Lembur</option>
                    </select>
                </div>

                <div id="form-izin">
                    <div class="form-group">
                        <label>Jenis Izin</label>
                        <select class="form-control" name="id_jenis_izin">
                            <?php foreach($jenis_izin as $j): ?>
                                <option value="<?= $j->id_jenis ?>"><?= $j->nama_izin ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Dari Tanggal</label>
                                <input type="date" class="form-control" name="tgl_mulai" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Sampai Tanggal</label>
                                <input type="date" class="form-control" name="tgl_selesai" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div id="form-lembur" style="display:none;">
                    <div class="form-group">
                        <label>Tanggal Lembur</label>
                        <input type="date" class="form-control" name="tgl_lembur" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Jam Mulai</label>
                                <input type="time" class="form-control" name="jam_mulai">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Jam Selesai</label>
                                <input type="time" class="form-control" name="jam_selesai">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Keterangan / Alasan</label>
                    <textarea class="form-control" name="keterangan" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Kirim Pengajuan</button>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>

<script>
function toggleForm() {
    var tipe = document.getElementById('tipe_pengajuan').value;
    if(tipe == 'Izin') {
        document.getElementById('form-izin').style.display = 'block';
        document.getElementById('form-lembur').style.display = 'none';
    } else {
        document.getElementById('form-izin').style.display = 'none';
        document.getElementById('form-lembur').style.display = 'block';
    }
}
</script>
