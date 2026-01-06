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

            <!-- Form Pengajuan Baru -->
            <div class="card card-primary my-shadow mb-4">
                <div class="card-header">
                    <h6 class="card-title"><i class="fas fa-plus-circle"></i> Buat Pengajuan Baru</h6>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form action="<?= base_url('pengajuan/create') ?>" method="post" id="formPengajuan">
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tipe_pengajuan">Tipe Pengajuan <span class="text-danger">*</span></label>
                                    <select class="form-control" id="tipe_pengajuan" name="tipe_pengajuan" required>
                                        <option value="">-- Pilih Tipe --</option>
                                        <option value="Izin">Izin</option>
                                        <option value="Sakit">Sakit</option>
                                        <option value="Cuti">Cuti</option>
                                        <option value="Dinas">Dinas Luar</option>
                                        <option value="Lembur">Lembur</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4" id="wrap_jenis_izin" style="display:none;">
                                <div class="form-group">
                                    <label for="id_jenis_izin">Jenis Izin</label>
                                    <select class="form-control" id="id_jenis_izin" name="id_jenis_izin">
                                        <option value="">-- Pilih Jenis --</option>
                                        <?php foreach ($jenis_izin as $j): ?>
                                            <option value="<?= $j->id_jenis ?>"><?= htmlspecialchars($j->nama_izin) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tgl_mulai">Tanggal Mulai <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tgl_mulai" name="tgl_mulai" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tgl_selesai">Tanggal Selesai <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tgl_selesai" name="tgl_selesai" required>
                                </div>
                            </div>
                            <div class="col-md-3" id="wrap_jam_mulai" style="display:none;">
                                <div class="form-group">
                                    <label for="jam_mulai">Jam Mulai</label>
                                    <input type="time" class="form-control" id="jam_mulai" name="jam_mulai">
                                </div>
                            </div>
                            <div class="col-md-3" id="wrap_jam_selesai" style="display:none;">
                                <div class="form-group">
                                    <label for="jam_selesai">Jam Selesai</label>
                                    <input type="time" class="form-control" id="jam_selesai" name="jam_selesai">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="keterangan">Keterangan <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="3" maxlength="500" required placeholder="Jelaskan alasan pengajuan..."></textarea>
                            <small class="text-muted">Maksimal 500 karakter</small>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Kirim Pengajuan
                        </button>
                    </form>
                </div>
            </div>

            <!-- Riwayat Pengajuan -->
            <div class="card card-default my-shadow mb-4">
                <div class="card-header">
                    <h6 class="card-title"><i class="fas fa-history"></i> Riwayat Pengajuan Saya</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="tbl-pengajuan">
                            <thead class="bg-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Tipe</th>
                                    <th>Jenis Izin</th>
                                    <th>Tanggal</th>
                                    <th>Jumlah Hari</th>
                                    <th>Keterangan</th>
                                    <th>Status</th>
                                    <th>Diajukan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($list_pengajuan)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Belum ada pengajuan</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($list_pengajuan as $p): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
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
                                            <td>
                                                <?= htmlspecialchars($p->keterangan) ?>
                                                <?php if ($p->status === 'Ditolak' && !empty($p->alasan_tolak)): ?>
                                                    <br><small class="text-danger"><strong>Alasan ditolak:</strong> <?= htmlspecialchars($p->alasan_tolak) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = [
                                                    'Pending' => 'badge-warning',
                                                    'Disetujui' => 'badge-success',
                                                    'Ditolak' => 'badge-danger',
                                                    'Dibatalkan' => 'badge-secondary'
                                                ];
                                                $s_class = $status_class[$p->status] ?? 'badge-secondary';
                                                ?>
                                                <span class="badge <?= $s_class ?>"><?= $p->status ?></span>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($p->created_at)) ?></td>
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

<script>
$(document).ready(function() {
    $('#tbl-pengajuan').DataTable({
        "responsive": true,
        "autoWidth": false,
        "order": [[7, 'desc']],
        "language": {
            "url": "<?= base_url('assets/app/js/dataTables.indonesian.json') ?>"
        }
    });

    // Show/hide fields based on tipe_pengajuan
    $('#tipe_pengajuan').on('change', function() {
        var tipe = $(this).val();
        
        // Jenis izin only for Izin
        if (tipe === 'Izin') {
            $('#wrap_jenis_izin').show();
        } else {
            $('#wrap_jenis_izin').hide();
            $('#id_jenis_izin').val('');
        }
        
        // Jam fields only for Lembur
        if (tipe === 'Lembur') {
            $('#wrap_jam_mulai, #wrap_jam_selesai').show();
        } else {
            $('#wrap_jam_mulai, #wrap_jam_selesai').hide();
            $('#jam_mulai, #jam_selesai').val('');
        }
    });

    // Auto-set tgl_selesai when tgl_mulai changes
    $('#tgl_mulai').on('change', function() {
        var tglMulai = $(this).val();
        var tglSelesai = $('#tgl_selesai').val();
        if (!tglSelesai || tglSelesai < tglMulai) {
            $('#tgl_selesai').val(tglMulai);
        }
    });

    // Validate tgl_selesai >= tgl_mulai
    $('#formPengajuan').on('submit', function(e) {
        var tglMulai = $('#tgl_mulai').val();
        var tglSelesai = $('#tgl_selesai').val();
        
        if (tglSelesai < tglMulai) {
            e.preventDefault();
            Swal.fire('Perhatian', 'Tanggal selesai tidak boleh kurang dari tanggal mulai', 'warning');
            return false;
        }
    });
});
</script>
