</div>
<!-- /.content-wrapper -->

<!-- Main Footer -->
<footer class="main-footer">
    <strong>SMARTZHA</strong> v.<?= APP_VERSION ?>
    <div class="float-right d-none d-sm-inline-block">
        <strong>Copyright &copy; 1995-<?= date('Y') ?> Smartzha.</strong>
    </div>
</footer>

<!-- Control Sidebar -->
<aside class="control-sidebar control-sidebar-dark">
</aside>

</div>

<!-- Required JS -->
<script src="<?= base_url() ?>/assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/jszip/jszip.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/pdfmake/pdfmake.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/pdfmake/vfs_fonts.js"></script>
<script src="<?= base_url() ?>/assets/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/pace-progress/pace.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/moment/moment.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/summernote/summernote-bs4.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/sweetalert2/sweetalert2.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/toastr/toastr.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/select2/js/select2.full.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/multiselect/js/jquery.multi-select.js"></script>
<script src="<?= base_url() ?>/assets/plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/inputmask/min/jquery.inputmask.bundle.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/moment/moment-with-locales.min.js"></script>
<script src="<?= base_url() ?>/assets/plugins/jquery-datetimepicker/jquery.datetimepicker.full.js"></script>
<script src="<?= base_url() ?>/assets/plugins/jquery-timeago/jquery.timeago.js" type="text/javascript"></script>
<script src="<?= base_url() ?>/assets/app/js/show.toast.js"></script>

<!-- AdminLTE App -->
<script src="<?= base_url() ?>/assets/adminlte/dist/js/adminlte.js"></script>
<script src="<?= base_url() ?>/assets/adminlte/dist/js/pages/dashboard.js"></script>
<script src="<?= base_url() ?>/assets/adminlte/dist/js/demo.js"></script>

<!-- datetimepicker -->
<script src="<?= base_url() ?>/assets/plugins/jquery-datetimepicker/jquery.datetimepicker.full.js"></script>

<!-- Custom JS -->
<script>
    function ajaxcsrf() {
        var csrfname = '<?= $this->security->get_csrf_token_name() ?>';
        var csrfhash = '<?= $this->security->get_csrf_hash() ?>';
        var csrf = {};
        csrf[csrfname] = csrfhash;
        $.ajaxSetup({
            "data": csrf
        });
    }

    $(document).ready(function() {
        ajaxcsrf();
    });
</script>

</body>
</html>
