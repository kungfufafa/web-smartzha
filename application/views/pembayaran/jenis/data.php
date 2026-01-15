<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="content-wrapper bg-white pt-4">
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
        <h3 class="card-title">Jenis Tagihan</h3>
        <div class="card-tools">
            <button type="button" onclick="reload_ajax()" class="btn btn-sm btn-default">
                <i class="fa fa-sync"></i> <span class="d-none d-sm-inline-block ml-1">Reload</span>
            </button>
            <button type="button" data-from="add" data-toggle="modal" data-target="#modalJenis"
                            class="btn btn-sm bg-gradient-primary"><i
                                    class="fas fa-plus"></i><span class="d-none d-sm-inline-block ml-1">Tambah Jenis</span>
                        </button>
            <button type="button" id="btn-delete-selected" class="btn btn-sm btn-danger" style="display:none;">
                <i class="fa fa-trash"></i> Hapus Terpilih
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12 table-responsive">
                <?= form_open('', array('id' => 'tableForm')) ?>
                <table id="tableJenis" class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th width="5%">
                                <input type="checkbox" id="checkAll" onclick="toggleAllCheckboxes(this)">
                            </th>
                            <th>Kode</th>
                            <th>Nama Jenis</th>
                            <th>Nominal Default</th>
                            <th>Tipe</th>
                            <th>Status</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $jenis_list = $this->pembayaran->getAllJenisTagihan(false);
                        foreach ($jenis_list as $key => $value):
                        ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="jenis-checkbox" value="<?= $value->id_jenis ?>">
                                </td>
                                <td><?= ($key + 1) ?></td>
                                <td><?= $value->kode_jenis ?></td>
                                <td><?= $value->nama_jenis ?></td>
                                <td><?= 'Rp ' . number_format($value->nominal_default, 0, ',', '.') ?></td>
                                <td>
                                    <?= $value->is_recurring == 1 ? '<span class="badge badge-info">Bulanan</span>' : '<span class="badge badge-secondary">Sekali</span>' ?>
                                </td>
                                <td>
                                    <?php if ($value->is_active): ?>
                                        <span class="text-success"><i class="fa fa-check mr-2"></i>AKTIF</span>
                                    <?php else: ?>
                                        <button type="button" data-id="<?= $value->id_jenis ?>"
                                                        class="btn btn-xs btn-primary btn-aktif">AKTIFKAN
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" data-id="<?= $value->id_jenis ?>"
                                                        data-kode="<?= $value->kode_jenis ?>"
                                                        data-nama="<?= $value->nama_jenis ?>"
                                                        data-nominal="<?= $value->nominal_default ?>"
                                                        data-keterangan="<?= $value->keterangan ?>"
                                                        data-recurring="<?= $value->is_recurring ?>"
                                                        data-aktif="<?= $value->is_active ?>"
                                                        data-from="edit"
                                                        data-toggle="modal" data-target="#modalJenis"
                                                        class="btn btn-xs btn-warning btn-edit">Edit
                                                </button>
                                        <button type="button" data-id="<?= $value->id_jenis ?>"
                                                        class="btn btn-xs btn-danger btn-hapus">Hapus
                                                </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

</div>
        </div>
    </section>

 <?= form_open('pembayaran/saveJenis', array('id' => 'formJenis')) ?>
<div class="modal fade" id="modalJenis" tabindex="-1" role="dialog" aria-labelledby="editModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Form Jenis Tagihan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_jenis" id="id_jenis">
                <div class="form-group">
                    <label>Kode Jenis <span class="text-danger">*</span></label>
                    <input type="text" name="kode_jenis" id="kode_jenis" class="form-control" required maxlength="20" style="text-transform: uppercase;">
                </div>
                <div class="form-group">
                    <label>Nama Jenis <span class="text-danger">*</span></label>
                    <input type="text" name="nama_jenis" id="nama_jenis" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Nominal Default</label>
                    <input type="text" name="nominal_default" id="nominal_default" class="form-control rupiah" value="0">
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <textarea name="keterangan" id="keterangan" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="is_recurring" id="is_recurring" class="form-check-input" value="1">
                    <label class="form-check-label" for="is_recurring">Tagihan Berulang (Bulanan)</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" checked>
                    <label class="form-check-label" for="is_active">Aktif</label>
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" id="editIdJenis" class="form-control">
                <input type="hidden" id="method" name="method" class="form-control">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>

<script type="text/javascript"
        src="<?= base_url() ?>/assets/plugins/jquery-table2json/src/tabletojson-cell.js"></script>
<script type="text/javascript" src="<?= base_url() ?>/assets/plugins/jquery-table2json/src/tabletojson-row.js"></script>
<script type="text/javascript" src="<?= base_url() ?>/assets/plugins/jquery-table2json/src/tabletojson.js"></script>
<script>
    // CRITICAL FIX: Define functions in global scope so they can be called from onclick attributes
    function toggleAllCheckboxes(source) {
        var checkboxes = $('.jenis-checkbox');
        checkboxes.prop('checked', $(source).prop('checked'));
        toggleDeleteButton();
    }

    function toggleDeleteButton() {
        var checkedCount = $('.jenis-checkbox:checked').length;
        if (checkedCount > 0) {
            $('#btn-delete-selected').show();
        } else {
            $('#btn-delete-selected').hide();
        }
    }

    function reload_ajax() {
        window.location.href = base_url + 'pembayaran/jenis';
    }

    $(document).ready(function() {
        ajaxcsrf();

        $('.jenis-checkbox').on('change', function() {
            toggleDeleteButton();
        });

        $('#btn-delete-selected').on('click', function() {
            var checked = $('.jenis-checkbox:checked');
            var ids = [];
            checked.each(function() {
                ids.push($(this).val());
            });

            if (ids.length === 0) {
                return;
            }

            Swal.fire({
                title: 'Hapus Jenis Tagihan Terpilih?',
                text: 'Data yang sudah dihapus tidak dapat dikembalikan',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus'
            }).then((result) => {
                if (result.value || result.isConfirmed) {
                    $.post('<?= base_url('pembayaran/hapusJenis') ?>',
                        {
                            checked: ids,
                            '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>'
                        },
                        function(response) {
                            if (response.status) {
                                window.location.href = '<?= base_url('pembayaran/jenis') ?>';
                                Swal.fire('Berhasil', response.message, 'success');
                            } else {
                                Swal.fire('Gagal', response.message, 'error');
                            }
                        }, 'json');
                }
            });
         });

         // Handler untuk tombol hapus individual
         $("#tableJenis").on("click", ".btn-hapus", function () {
             let id = $(this).data("id");

             Swal.fire({
                 title: 'Hapus Jenis Tagihan?',
                 text: 'Data yang sudah dihapus tidak dapat dikembalikan',
                 icon: 'warning',
                 showCancelButton: true,
                 confirmButtonColor: '#d33',
                 confirmButtonText: 'Ya, Hapus',
                 cancelButtonText: 'Batal'
             }).then((result) => {
                 if (result.value || result.isConfirmed) {
                     $.post('<?= base_url('pembayaran/deleteJenis') ?>',
                         {
                             id: id,
                             '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>'
                         },
                         function(response) {
                             if (response.status) {
                                 window.location.href = '<?= base_url('pembayaran/jenis') ?>';
                                 Swal.fire('Berhasil', response.message, 'success');
                             } else {
                                 Swal.fire('Gagal', response.message, 'error');
                             }
                         },
                         'json'
                     );
                 }
             });
         });

         $("#tableJenis").on("click", ".btn-aktif", function () {
            let id = $(this).data("id");
            var dataJenis = JSON.stringify($('#tableJenis').tableToJSON());
            var replaced = dataJenis.replace(/pembayaran_jenis/g, "jenis");

            swal.fire({
                text: "Silahkan tunggu....",
                button: false,
                closeOnClickOutside: false,
                closeOnEsc: false,
                allowEscapeKey: false,
                allowOutsideClick: false,
                onOpen: () => {
                    swal.showLoading();
                }
            });
            $.ajax({
                url: base_url + "pembayaran/activateJenis",
                data: $('#tableForm').serialize() + "&active=" + id + "&jenis=" + replaced,
                type: "POST",
                success: function (response) {
                    var title = response.status ? "Berhasil" : "Gagal";
                    var type = response.status ? "success" : "error";

                    swal.fire({
                        title: title,
                        text: response.msg,
                        icon: type
                    }).then((result) => {
                        if (result.value) {
                            if (response.status) {
                                window.location.href = base_url + 'pembayaran/jenis';
                            }
                        }
                    });
                }, error: function (xhr, status, error) {
                    console.log("error", xhr.responseText);
                    const err = JSON.parse(xhr.responseText)
                    swal.fire({
                        title: "Error",
                        text: err.Message,
                        icon: "error"
                    });
                }
            });
        });

        // CRITICAL FIX: Format rupiah on keyup
        $('.rupiah').on('keyup', function() {
            var val = $(this).val().replace(/[^\d]/g, '');
            $(this).val(parseInt(val || 0).toLocaleString('id-ID'));
        });

        $('#modalJenis').on('show.bs.modal', function (e) {
            var method = $(e.relatedTarget).data('from');

            if (method === 'edit') {
                $('#editModalLabel').text('Edit Jenis');
                var id = $(e.relatedTarget).data('id');
                var kode = $(e.relatedTarget).data('kode');
                var nama = $(e.relatedTarget).data('nama');
                var nominal = $(e.relatedTarget).data('nominal');
                var keterangan = $(e.relatedTarget).data('keterangan');
                var recurring = $(e.relatedTarget).data('recurring');
                var aktif = $(e.relatedTarget).data('aktif');

                $('#id_jenis').val(id);
                $('#kode_jenis').val(kode);
                $('#nama_jenis').val(nama);
                $('#nominal_default').val(nominal);
                $('#keterangan').val(keterangan);
                $('#is_recurring').prop('checked', recurring == 1);
                $('#is_active').prop('checked', aktif == 1);

                // CRITICAL FIX: Set method to 'edit'
                $('#method').val('edit');
            } else {
                $('#editModalLabel').text('Tambah Jenis');
                $('#id_jenis').val('');
                $('#kode_jenis').val('');
                $('#nama_jenis').val('');
                $('#nominal_default').val(0);
                $('#keterangan').val('');
                $('#is_recurring').prop('checked', false);
                $('#is_active').prop('checked', true);

                // CRITICAL FIX: Set method to empty for create
                $('#method').val('');
            }
        });

         $('#formJenis').on('submit', function (e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            var formData = $(this).serialize();
            var url = $(this).attr('action');

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    if (response.status) {
                        $('#modalJenis').modal('hide');
                        window.location.href = base_url + 'pembayaran/jenis';
                    } else {
                        showDangerToast(response.message);
                    }
                },
                error: function (xhr, status, error) {
                    showDangerToast('Gagal menyimpan data: ' + (xhr.responseJSON?.message || error));
                    console.error('AJAX Error:', xhr.responseText);
                }
            });

            return false;
        });
    });
</script>
