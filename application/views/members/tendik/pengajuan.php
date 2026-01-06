<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper" style="margin-top: -1px;">
    <div class="sticky"></div>
    <section class="content overlap p-4">
        <div class="container">
            <!-- Profile Card -->
            <div class="info-box bg-transparent shadow-none">
                <?php 
                $foto = isset($tendik) && $tendik && $tendik->foto ? $tendik->foto : 'assets/adminlte/dist/img/avatar5.png';
                ?>
                <img class="avatar rounded-circle" src="<?= base_url($foto) ?>" width="80" height="80" style="object-fit: cover;">
                <div class="info-box-content">
                    <h5 class="info-box-text text-white text-wrap"><b><?= isset($profile) ? $profile->nama_lengkap : 'Tendik' ?></b></h5>
                    <span class="info-box-text text-white"><?= $judul ?></span>
                </div>
            </div>

            <!-- Form Pengajuan Baru -->
            <div class="row">
                <div class="col-12">
                    <div class="card card-info">
                        <div class="card-header">
                            <div class="card-title text-white">
                                <i class="fas fa-plus-circle mr-2"></i>Buat Pengajuan Baru
                            </div>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool text-white" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($this->session->flashdata('success')): ?>
                                <div class="alert alert-success"><?= $this->session->flashdata('success') ?></div>
                            <?php endif; ?>
                            <?php if ($this->session->flashdata('error')): ?>
                                <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
                            <?php endif; ?>

                            <form action="<?= base_url('pengajuan/create') ?>" method="post">
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Tipe Pengajuan <span class="text-danger">*</span></label>
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
                                    <div class="col-md-6" id="wrap_jenis_izin" style="display:none;">
                                        <div class="form-group">
                                            <label>Jenis Izin</label>
                                            <select class="form-control" id="id_jenis_izin" name="id_jenis_izin">
                                                <option value="">-- Pilih Jenis --</option>
                                                <?php if (isset($jenis_izin)): foreach ($jenis_izin as $j): ?>
                                                    <option value="<?= $j->id_jenis ?>"><?= htmlspecialchars($j->nama_izin) ?></option>
                                                <?php endforeach; endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Tanggal Mulai <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="tgl_mulai" name="tgl_mulai" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Tanggal Selesai <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="tgl_selesai" name="tgl_selesai" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3" id="wrap_jam_mulai" style="display:none;">
                                        <div class="form-group">
                                            <label>Jam Mulai</label>
                                            <input type="time" class="form-control" id="jam_mulai" name="jam_mulai">
                                        </div>
                                    </div>
                                    <div class="col-md-3" id="wrap_jam_selesai" style="display:none;">
                                        <div class="form-group">
                                            <label>Jam Selesai</label>
                                            <input type="time" class="form-control" id="jam_selesai" name="jam_selesai">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Keterangan <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="keterangan" rows="3" maxlength="500" required placeholder="Jelaskan alasan pengajuan..."></textarea>
                                </div>

                                <button type="submit" class="btn btn-info">
                                    <i class="fas fa-paper-plane mr-1"></i> Kirim Pengajuan
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Riwayat Pengajuan -->
            <div class="row">
                <div class="col-12">
                    <div class="card card-success">
                        <div class="card-header">
                            <div class="card-title text-white">
                                <i class="fas fa-history mr-2"></i>Riwayat Pengajuan
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Tipe</th>
                                            <th>Tanggal</th>
                                            <th>Keterangan</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($list_pengajuan)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">Belum ada pengajuan</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($list_pengajuan as $p): ?>
                                                <tr>
                                                    <td>
                                                        <?php
                                                        $badge_class = [
                                                            'Izin' => 'info', 'Sakit' => 'warning', 'Cuti' => 'primary',
                                                            'Dinas' => 'secondary', 'Lembur' => 'dark'
                                                        ];
                                                        $class = $badge_class[$p->tipe_pengajuan] ?? 'secondary';
                                                        ?>
                                                        <span class="badge badge-<?= $class ?>"><?= $p->tipe_pengajuan ?></span>
                                                    </td>
                                                    <td>
                                                        <?= date('d/m/Y', strtotime($p->tgl_mulai)) ?>
                                                        <?php if ($p->tgl_mulai !== $p->tgl_selesai): ?>
                                                            - <?= date('d/m/Y', strtotime($p->tgl_selesai)) ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($p->keterangan) ?></td>
                                                    <td>
                                                        <?php
                                                        $status_class = [
                                                            'Pending' => 'warning', 'Disetujui' => 'success',
                                                            'Ditolak' => 'danger', 'Dibatalkan' => 'secondary'
                                                        ];
                                                        $s_class = $status_class[$p->status] ?? 'secondary';
                                                        ?>
                                                        <span class="badge badge-<?= $s_class ?>"><?= $p->status ?></span>
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
            </div>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    $('#tipe_pengajuan').on('change', function() {
        var tipe = $(this).val();
        $('#wrap_jenis_izin').toggle(tipe === 'Izin');
        $('#wrap_jam_mulai, #wrap_jam_selesai').toggle(tipe === 'Lembur');
    });

    $('#tgl_mulai').on('change', function() {
        var tglMulai = $(this).val();
        var tglSelesai = $('#tgl_selesai').val();
        if (!tglSelesai || tglSelesai < tglMulai) {
            $('#tgl_selesai').val(tglMulai);
        }
    });
});
</script>
