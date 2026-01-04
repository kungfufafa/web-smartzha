<div class="content-wrapper bg-white pt-4">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $judul ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('absensi') ?>">Absensi</a></li>
                        <li class="breadcrumb-item active"><?= $subjudul ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header p-0">
                    <ul class="nav nav-tabs" id="tabAssign" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#tabGuru" role="tab">
                                <i class="fas fa-chalkboard-teacher"></i> Guru
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tabKaryawan" role="tab">
                                <i class="fas fa-user-tie"></i> Karyawan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tabSiswa" role="tab">
                                <i class="fas fa-user-graduate"></i> Siswa
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <!-- Tab Guru -->
                        <div class="tab-pane fade show active" id="tabGuru" role="tabpanel">
                            <table class="table table-bordered table-striped dataTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>NIP</th>
                                        <th>Nama</th>
                                        <th>Shift Saat Ini</th>
                                        <th>Tgl Efektif</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; ?>
                                    <?php if (!empty($guru_list)): ?>
                                    <?php foreach ($guru_list as $guru): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= $guru->nip ?></td>
                                        <td><?= $guru->nama_guru ?></td>
                                        <td>
                                            <?php if (!empty($guru->nama_shift)): ?>
                                            <span class="badge badge-info"><?= $guru->nama_shift ?></span>
                                            <?php else: ?>
                                            <span class="text-muted">Belum diatur</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= !empty($guru->tgl_efektif) ? date('d/m/Y', strtotime($guru->tgl_efektif)) : '-' ?></td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-xs" onclick="openAssign(<?= $guru->id_user ?>, '<?= addslashes($guru->nama_guru) ?>', '<?= $guru->id_shift ?>')">
                                                <i class="fas fa-clock"></i> Assign
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Tab Karyawan -->
                        <div class="tab-pane fade" id="tabKaryawan" role="tabpanel">
                            <table class="table table-bordered table-striped dataTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Username</th>
                                        <th>Nama</th>
                                        <th>Shift Saat Ini</th>
                                        <th>Tgl Efektif</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; ?>
                                    <?php if (!empty($karyawan_list)): ?>
                                    <?php foreach ($karyawan_list as $kar): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= $kar->username ?></td>
                                        <td><?= isset($kar->nama_lengkap) ? $kar->nama_lengkap : $kar->username ?></td>
                                        <td>
                                            <?php if (!empty($kar->nama_shift)): ?>
                                            <span class="badge badge-info"><?= $kar->nama_shift ?></span>
                                            <?php else: ?>
                                            <span class="text-muted">Belum diatur</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= !empty($kar->tgl_efektif) ? date('d/m/Y', strtotime($kar->tgl_efektif)) : '-' ?></td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-xs" onclick="openAssign(<?= $kar->id ?>, '<?= addslashes(isset($kar->nama_lengkap) ? $kar->nama_lengkap : $kar->username) ?>', '<?= isset($kar->id_shift) ? $kar->id_shift : '' ?>')">
                                                <i class="fas fa-clock"></i> Assign
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Tab Siswa -->
                        <div class="tab-pane fade" id="tabSiswa" role="tabpanel">
                            <table class="table table-bordered table-striped dataTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>NIS</th>
                                        <th>Nama</th>
                                        <th>Kelas</th>
                                        <th>Shift Saat Ini</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; ?>
                                    <?php if (!empty($siswa_list)): ?>
                                    <?php foreach ($siswa_list as $siswa): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= $siswa->nis ?></td>
                                        <td><?= $siswa->nama ?></td>
                                        <td><?= isset($siswa->nama_kelas) ? $siswa->nama_kelas : '-' ?></td>
                                        <td>
                                            <?php if (!empty($siswa->nama_shift)): ?>
                                            <span class="badge badge-info"><?= $siswa->nama_shift ?></span>
                                            <?php else: ?>
                                            <span class="text-muted">Belum diatur</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-xs" onclick="openAssign(<?= $siswa->id_user ?>, '<?= addslashes($siswa->nama) ?>', '<?= isset($siswa->id_shift) ? $siswa->id_shift : '' ?>')">
                                                <i class="fas fa-clock"></i> Assign
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
        </div>
    </section>
</div>

<!-- Modal Assign Shift -->
<div class="modal fade" id="modalAssign" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Shift</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="formAssign">
                <div class="modal-body">
                    <input type="hidden" name="id_user" id="assign_id_user">
                    <div class="form-group">
                        <label>Nama</label>
                        <input type="text" class="form-control" id="assign_nama" readonly>
                    </div>
                    <div class="form-group">
                        <label>Shift <span class="text-danger">*</span></label>
                        <select class="form-control" name="id_shift" id="assign_id_shift" required>
                            <option value="">-- Pilih Shift --</option>
                            <?php foreach ($shifts as $shift): ?>
                            <option value="<?= $shift->id_shift ?>"><?= $shift->nama_shift ?> (<?= $shift->jam_masuk ?> - <?= $shift->jam_pulang ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Efektif <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="tgl_efektif" id="assign_tgl_efektif" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.dataTable').DataTable({
        "responsive": true,
        "autoWidth": false,
        "pageLength": 25
    });

    $('#formAssign').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&<?= $this->security->get_csrf_token_name() ?>=<?= $this->security->get_csrf_hash() ?>';
        
        $.ajax({
            url: '<?= base_url("absensi/saveAssignment") ?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.status) {
                    $('#modalAssign').modal('hide');
                    Swal.fire('Berhasil', res.message, 'success').then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Terjadi kesalahan server', 'error');
            }
        });
    });
});

function openAssign(id_user, nama, current_shift) {
    $('#assign_id_user').val(id_user);
    $('#assign_nama').val(nama);
    $('#assign_id_shift').val(current_shift || '');
    $('#assign_tgl_efektif').val('<?= date("Y-m-d") ?>');
    $('#modalAssign').modal('show');
}
</script>
