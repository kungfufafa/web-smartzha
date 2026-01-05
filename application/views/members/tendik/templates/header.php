<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= $judul ?></title>
    <meta name="renderer" content="webkit"/>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0" name="viewport">
    <?php $logo_app = $setting->logo_kiri == null ? base_url() . 'assets/img/favicon.png' : base_url() . $setting->logo_kiri; ?>
    <link rel="shortcut icon" href="<?= $logo_app ?>" type="image/x-icon">

    <!-- Required CSS -->
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/fontawesome-free/css/fontawesome.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/fontawesome-free/css/v4-shims.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/Ionicons/css/ionicons.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/jqvmap/jqvmap.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/pace-progress/themes/silver/pace-theme-center-circle.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/multiselect/css/multi-select.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/jquery-datetimepicker/jquery.datetimepicker.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/toastr/toastr.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/adminlte/dist/css/montserrat.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/adminlte/dist/css/scheherazade.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/adminlte/dist/css/uthmanic.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/adminlte/dist/css/fonts.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/app/css/show.toast.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/adminlte/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/summernote/summernote-bs4.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/summernote/plugin/audio/summernote-audio.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/app/css/mystyle.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/app/css/font-material.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/bootstrap-icon/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/dropify/css/dropify.min.css">

    <style>
        .linker-list p {
            margin-bottom: .5rem;
            margin-top: .5rem;
        }
    </style>
</head>

<script>
    let reloadCOunt = 0;
    let base_url = '<?=base_url()?>';

    let globalToken;
    var adaJadwalUjian;

    function getToken(func) {
        $.ajax({
            url: base_url + "dashboard/checktokenjadwal",
            type: "GET",
            success: function (response) {
                console.log('getToken', response.token);
                globalToken = response.token;
                adaJadwalUjian = response.ada_jadwal;
                if (reloadCOunt > 0) {
                    showSuccessToast('Token: <b>' + globalToken.token + '</b>');
                }
                reloadCOunt ++;

                if (func && (typeof func == "function")) {
                    func(response);
                }
            },
            error: function (xhr, status, error) {
                console.log(xhr);
            }
        });
    }
</script>

<body class="hold-transition sidebar-mini text-sm" spellcheck="false" onload="startTime()">
<div class="wrapper">

    <!-- Navbar -->
    <?php require_once("navbar.php"); ?>

    <!-- Sidebar -->
    <?php require_once("sidebar.php"); ?>
