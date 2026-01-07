<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
$jenis = $jenis ?? 'guru';
if (!in_array($jenis, ['guru', 'tendik', 'siswa'], true)) {
    $jenis = 'guru';
}

$month = (int) ($month ?? date('n'));
$year = (int) ($year ?? date('Y'));
$days_in_month = (int) ($days_in_month ?? date('t'));
$kelas_selected = (int) ($kelas_selected ?? 0);
$kelas_selected_name = $kelas_selected_name ?? null;
$kelas_list = $kelas_list ?? [];

$users = $users ?? [];
$map = $map ?? [];

$month_names = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$month_label = $month_names[$month] ?? (string) $month;

$jenis_label = [
    'guru' => 'Guru',
    'tendik' => 'Tendik',
    'siswa' => 'Siswa'
][$jenis] ?? ucfirst($jenis);

$card_class = [
    'guru' => 'primary',
    'tendik' => 'warning',
    'siswa' => 'success'
][$jenis] ?? 'secondary';

$dates = [];
for ($d = 1; $d <= $days_in_month; $d++) {
    $dates[] = sprintf('%04d-%02d-%02d', $year, $month, $d);
}

$status_badges = [
    'Hadir' => ['label' => 'H', 'class' => 'success'],
    'Terlambat' => ['label' => 'T', 'class' => 'warning'],
    'Pulang Awal' => ['label' => 'PA', 'class' => 'warning'],
    'Terlambat + Pulang Awal' => ['label' => 'TP', 'class' => 'warning'],
    'Alpha' => ['label' => 'A', 'class' => 'danger'],
    'Izin' => ['label' => 'I', 'class' => 'info'],
    'Sakit' => ['label' => 'S', 'class' => 'secondary'],
    'Cuti' => ['label' => 'C', 'class' => 'primary'],
    'Dinas Luar' => ['label' => 'DL', 'class' => 'dark'],
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
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filter Rekap Bulanan</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label>Jenis Rekap</label>
                            <select class="form-control" id="jenis" onchange="toggleKelasFilter()">
                                <option value="guru" <?= $jenis === 'guru' ? 'selected' : '' ?>>Guru</option>
                                <option value="tendik" <?= $jenis === 'tendik' ? 'selected' : '' ?>>Tendik</option>
                                <option value="siswa" <?= $jenis === 'siswa' ? 'selected' : '' ?>>Siswa</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Bulan</label>
                            <select class="form-control" id="month">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>>
                                        <?= $month_names[$m] ?? $m ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Tahun</label>
                            <select class="form-control" id="year">
                                <?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 5; $y--): ?>
                                    <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Filter Kelas (khusus Siswa)</label>
                            <select class="form-control" id="kelas">
                                <option value="">Pilih Kelas...</option>
                                <?php if (!empty($kelas_list)): ?>
                                    <?php foreach ($kelas_list as $id => $nama): ?>
                                        <option value="<?= (int) $id ?>" <?= (int) $id === $kelas_selected ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string) $nama, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-primary btn-block" onclick="applyFilterRekapBulanan()">
                                <i class="fas fa-filter"></i> Terapkan
                            </button>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">Rekap ditampilkan per tanggal (1 s/d akhir bulan) berdasarkan data di <code>presensi_logs</code>.</small>
                </div>
            </div>

            <div class="card card-outline card-<?= $card_class ?>">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar mr-1"></i> Rekap <?= $jenis_label ?> - <?= $month_label ?> <?= $year ?>
                        <?php if ($jenis === 'siswa' && $kelas_selected_name): ?>
                            (<?= htmlspecialchars((string) $kelas_selected_name, ENT_QUOTES, 'UTF-8') ?>)
                        <?php endif; ?>
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-success btn-sm mr-2" onclick="exportRekapBulanan()">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                        <span class="badge badge-<?= $card_class ?>">User: <?= number_format((int) count($users)) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($jenis === 'siswa' && !$kelas_selected): ?>
                        <div class="alert alert-info text-center mb-0">
                            <i class="fas fa-info-circle mr-1"></i> Pilih kelas terlebih dahulu untuk menampilkan rekap siswa.
                        </div>
                    <?php elseif (empty($users)): ?>
                        <div class="alert alert-warning text-center mb-0">
                            <i class="fas fa-info-circle mr-1"></i> Tidak ada data untuk filter ini.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" style="min-width: 1200px;">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">No</th>
                                        <?php if ($jenis === 'siswa'): ?>
                                            <th style="width: 110px;">NIS</th>
                                        <?php endif; ?>
                                        <th style="min-width: 220px;">Nama</th>
                                        <?php foreach ($dates as $date_str): ?>
                                            <?php $day_num = (int) date('j', strtotime($date_str)); ?>
                                            <?php $is_weekend = in_array((int) date('N', strtotime($date_str)), [6, 7], true); ?>
                                            <th class="text-center <?= $is_weekend ? 'bg-light' : '' ?>" style="width: 36px;">
                                                <?= $day_num ?>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; ?>
                                    <?php foreach ($users as $u): ?>
                                    <?php $uid = (int) ($u->user_id ?? 0); ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <?php if ($jenis === 'siswa'): ?>
                                            <td><?= htmlspecialchars((string) ($u->nis ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <?php endif; ?>
                                        <td>
                                            <?= htmlspecialchars((string) ($u->nama ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                            <br>
                                            <small class="text-muted">@<?= htmlspecialchars((string) ($u->username ?? '-'), ENT_QUOTES, 'UTF-8') ?></small>
                                        </td>
                                        <?php foreach ($dates as $date_str): ?>
                                            <?php $status = $map[$uid][$date_str] ?? null; ?>
                                            <?php $badge = $status ? ($status_badges[$status] ?? null) : null; ?>
                                            <?php $is_weekend = in_array((int) date('N', strtotime($date_str)), [6, 7], true); ?>
                                            <td class="text-center <?= $is_weekend ? 'bg-light' : '' ?>">
                                                <?php if ($badge): ?>
                                                    <span class="badge badge-<?= $badge['class'] ?>" data-toggle="tooltip" title="<?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?>">
                                                        <?= $badge['label'] ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-2">
                            <small class="text-muted">
                                Keterangan:
                                <?php foreach ($status_badges as $status => $badge): ?>
                                    <span class="badge badge-<?= $badge['class'] ?> mr-1"><?= $badge['label'] ?></span>
                                    <?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?>&nbsp;
                                <?php endforeach; ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
function toggleKelasFilter() {
    var jenis = document.getElementById('jenis').value;
    var kelasEl = document.getElementById('kelas');

    if (!kelasEl) {
        return;
    }

    if (jenis === 'siswa') {
        kelasEl.disabled = false;
        return;
    }

    kelasEl.value = '';
    kelasEl.disabled = true;
}

function applyFilterRekapBulanan() {
    var jenis = document.getElementById('jenis').value;
    var month = document.getElementById('month').value;
    var year = document.getElementById('year').value;
    var kelas = document.getElementById('kelas').value;

    if (jenis === 'siswa' && !kelas) {
        Swal.fire('Info', 'Pilih kelas terlebih dahulu untuk rekap siswa.', 'info');
        return;
    }

    var url = '<?= base_url('presensi/rekap') ?>?jenis=' + encodeURIComponent(jenis) + '&month=' + encodeURIComponent(month) + '&year=' + encodeURIComponent(year);

    if (jenis === 'siswa' && kelas) {
        url += '&kelas=' + encodeURIComponent(kelas);
    }

    window.location.href = url;
}

function exportRekapBulanan() {
    var jenis = document.getElementById('jenis').value;
    var month = document.getElementById('month').value;
    var year = document.getElementById('year').value;
    var kelas = document.getElementById('kelas').value;

    if (jenis === 'siswa' && !kelas) {
        Swal.fire('Info', 'Pilih kelas terlebih dahulu untuk export rekap siswa.', 'info');
        return;
    }

    var url = '<?= base_url('presensi/rekap_export') ?>?jenis=' + encodeURIComponent(jenis) + '&month=' + encodeURIComponent(month) + '&year=' + encodeURIComponent(year);

    if (jenis === 'siswa' && kelas) {
        url += '&kelas=' + encodeURIComponent(kelas);
    }

    window.location.href = url;
}

$(function () {
    toggleKelasFilter();

    if ($('[data-toggle="tooltip"]').length) {
        $('[data-toggle="tooltip"]').tooltip();
    }
});
</script>
