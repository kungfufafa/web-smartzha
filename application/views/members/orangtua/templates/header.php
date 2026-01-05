<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= $judul ?></title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <?php $logo_app = $setting->logo_kiri == null ? base_url() . 'assets/img/favicon.png' : base_url() . $setting->logo_kiri; ?>
    <link rel="shortcut icon" href="<?= $logo_app ?>" type="image/x-icon">

    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/fontawesome-free/css/fontawesome.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/Ionicons/css/ionicons.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/pace-progress/themes/silver/pace-theme-center-circle.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/app/css/jquery.toast.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/toastr/toastr.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/dropify/css/dropify.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/adminlte/dist/css/montserrat.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/adminlte/dist/css/fonts.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/adminlte/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/app/css/mystyle.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/app/css/show.toast.css">

    <script src="<?= base_url() ?>/assets/plugins/jquery/jquery.min.js"></script>
    <script src="<?= base_url() ?>/assets/plugins/jquery-ui/jquery-ui.min.js"></script>
    <script src="<?= base_url() ?>/assets/plugins/sweetalert2/sweetalert2.min.js"></script>

    <style>
        .child-switcher { background: rgba(255,255,255,0.1); border-radius: 5px; padding: 5px 10px; }
        .child-switcher select { background: transparent; border: 1px solid rgba(255,255,255,0.3); color: white; padding: 5px 10px; border-radius: 3px; }
        .child-switcher select option { color: #333; }
    </style>
</head>

<script type="text/javascript">
    let base_url = '<?=base_url()?>';
</script>

<?php
$dash = $this->uri->segment(1);
$dnone = $dash == "orangtua" && $this->uri->segment(2) == "" ? 'invisible' : '';

function buat_tanggal($str)
{
    $str = str_replace("Jan", "Januari", $str);
    $str = str_replace("Feb", "Februari", $str);
    $str = str_replace("Mar", "Maret", $str);
    $str = str_replace("Apr", "April", $str);
    $str = str_replace("May", "Mei", $str);
    $str = str_replace("Jun", "Juni", $str);
    $str = str_replace("Jul", "Juli", $str);
    $str = str_replace("Aug", "Agustus", $str);
    $str = str_replace("Sep", "September", $str);
    $str = str_replace("Oct", "Oktober", $str);
    $str = str_replace("Nov", "Nopember", $str);
    $str = str_replace("Dec", "Desember", $str);
    $str = str_replace("Mon", "Senin", $str);
    $str = str_replace("Tue", "Selasa", $str);
    $str = str_replace("Wed", "Rabu", $str);
    $str = str_replace("Thu", "Kamis", $str);
    $str = str_replace("Fri", "Jumat", $str);
    $str = str_replace("Sat", "Sabtu", $str);
    $str = str_replace("Sun", "Minggu", $str);
    return $str;
}

function singkat_tanggal($str)
{
    $str = str_replace("Jan", "Jan", $str);
    $str = str_replace("Feb", "Feb", $str);
    $str = str_replace("Mar", "Mar", $str);
    $str = str_replace("Apr", "Apr", $str);
    $str = str_replace("May", "Mei", $str);
    $str = str_replace("Jun", "Jun", $str);
    $str = str_replace("Jul", "Jul", $str);
    $str = str_replace("Aug", "Aug", $str);
    $str = str_replace("Sep", "Sep", $str);
    $str = str_replace("Oct", "Okt", $str);
    $str = str_replace("Nov", "Nov", $str);
    $str = str_replace("Dec", "Des", $str);
    $str = str_replace("Mon", "Sen", $str);
    $str = str_replace("Tue", "Sel", $str);
    $str = str_replace("Wed", "Rab", $str);
    $str = str_replace("Thu", "Kam", $str);
    $str = str_replace("Fri", "Jum", $str);
    $str = str_replace("Sat", "Sab", $str);
    $str = str_replace("Sun", "Min", $str);
    return $str;
}
?>

<body class="layout-top-nav layout-navbar-fixed">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand-md navbar-dark navbar-green border-bottom-0">
        <ul class="navbar-nav ml-2 <?= $dnone ?>">
            <li class="nav-item">
                <a href="<?= base_url('orangtua') ?>" type="button" class="btn btn-success">
                    <i class="fas fa-arrow-left mr-2"></i><span class="d-none d-sm-inline-block ml-1">Beranda</span>
                </a>
            </li>
        </ul>
        <div class="mx-auto text-white text-center" style="line-height: 1">
            <span class="text-lg p-0"><?= $setting->nama_aplikasi ?></span>
            <br>
            <small>Portal Orang Tua | TP: <?= $tp_active->tahun ?> Smt:<?= $smt_active->smt ?></small>
        </div>
        
        <?php if (count($anak_list) > 1): ?>
        <div class="child-switcher mr-3">
            <select onchange="switchAnak(this.value)" class="form-control-sm">
                <?php foreach ($anak_list as $anak): ?>
                <option value="<?= $anak->id_siswa ?>" <?= $selected_anak && $selected_anak->id_siswa == $anak->id_siswa ? 'selected' : '' ?>>
                    <?= $anak->nama ?> (<?= $anak->nama_kelas ?: 'Belum ada kelas' ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        
        <ul class="navbar-nav">
            <li class="nav-item">
                <button onclick="logout()" class="btn btn-danger btn-outline-light">
                    <span class="d-none d-sm-inline-block mr-2">Logout</span><i class="fas fa-sign-out-alt"></i>
                </button>
            </li>
        </ul>
    </nav>

<script>
function switchAnak(id_siswa) {
    window.location.href = base_url + 'orangtua/switchAnak/' + id_siswa + '?redirect=' + encodeURIComponent(window.location.pathname);
}

function logout() {
    Swal.fire({
        title: 'Logout',
        text: 'Anda yakin ingin logout?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Logout!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.value || result.isConfirmed) {
            window.location.href = base_url + 'auth/logout';
        }
    });
}
</script>
