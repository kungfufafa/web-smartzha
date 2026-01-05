var table;

$(document).ready(function() {
  ajaxcsrf();

  table = $("#table-tendik").DataTable({
    initComplete: function() {
      var api = this.api();
      $("#table-tendik_filter input")
        .off(".DT")
        .on("keyup.DT", function(e) {
          api.search(this.value).draw();
        });
    },
    dom:
      "<'row'<'col-sm-3'l><'col-sm-6 text-center'B><'col-sm-3'f>>" +
      "<'row'<'col-sm-12'tr>>" +
      "<'row'<'col-sm-5'i><'col-sm-7'p>>",
    buttons: [
      {
        extend: "copy",
        exportOptions: { columns: [2, 3, 4, 5] }
      },
      {
        extend: "print",
        exportOptions: { columns: [2, 3, 4, 5] }
      },
      {
        extend: "excel",
        exportOptions: { columns: [2, 3, 4, 5] }
      },
      {
        extend: "pdf",
        exportOptions: { columns: [2, 3, 4, 5] }
      }
    ],
    oLanguage: {
      sProcessing: "loading..."
    },
    processing: true,
    serverSide: true,
    ajax: {
      url: base_url + "datatendik/data",
      type: "POST"
    },
    columns: [
      {
        data: "id_tendik",
        orderable: false,
        searchable: false
      },
      { data: "nama_tendik" },
      { data: "nip" },
      { data: "tipe_tendik" },
      { data: "jabatan" },
      {
        data: "is_active",
        render: function(data, type, row, meta) {
          if (data == 1) {
            return '<span class="badge badge-success">Aktif</span>';
          } else {
            return '<span class="badge badge-secondary">Non-aktif</span>';
          }
        }
      },
      {
        searchable: false,
        targets: 7,
        data: {
          id_tendik: "id_tendik",
          nama_tendik: "nama_tendik"
        },
        render: function(data, type, row, meta) {
          return `<div class="text-center">
									<a class="btn btn-xs btn-warning" href="${base_url}datatendik/edit/${data.id_tendik}">
										<i class="fa fa-pencil"></i>
									</a>
									<button data-id="${data.id_tendik}" data-nama="${data.nama_tendik}" type="button" class="btn btn-xs btn-danger btn-delete">
										<i class="fa fa-trash"></i>
									</button>
								</div>`;
        }
      }
    ],
    columnDefs: [
      {
        targets: 0,
        data: "id_tendik",
        render: function(data, type, row, meta) {
          return `<div class="text-center">
									<input name="checked[]" class="check" value="${data}" type="checkbox">
								</div>`;
        }
      }
    ],
    order: [[2, "asc"]],
    rowId: function(a) {
      return a;
    },
    rowCallback: function(row, data, iDisplayIndex) {
      var info = this.fnPagingInfo();
      var page = info.iPage;
      var length = info.iLength;
      var index = page * length + (iDisplayIndex +1);
      $("td:eq(1)", row).html(index);
    },
    createdRow: function(row, data, dataIndex) {
    }
  });

  table
    .buttons()
    .container()
    .appendTo("#table-tendik_wrapper .col-md-6:eq(0)");

  $("#table-tendik").on("click", ".btn-delete", function() {
    let id = $(this).data("id");
    let nama = $(this).data("nama");

    Swal.fire({
      title: "Hapus Data?",
      text: "Apakah Anda yakin ingin menghapus data tendik: " + nama + "?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Ya, Hapus!",
      cancelButtonText: "Batal"
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: base_url + "datatendik/delete",
          type: "POST",
          data: { checked: [id] },
          success: function(response) {
            if (response.status) {
              Swal.fire({
                icon: "success",
                title: "Berhasil",
                text: "Data tendik berhasil dihapus",
                timer: 1500,
                showConfirmButton: false
              });
              reload_ajax();
            } else {
              Swal.fire({
                icon: "error",
                title: "Gagal",
                text: "Data tendik gagal dihapus"
              });
            }
          }
        });
      }
    });
  });
});

function reload_ajax() {
  table.ajax.reload(null, false);
}

function bulk_delete() {
  let checked = [];
  $(".check:checked").each(function() {
    checked.push($(this).val());
  });

  if (checked.length === 0) {
    Swal.fire({
      icon: "warning",
      title: "Peringatan",
      text: "Pilih data yang akan dihapus"
    });
    return;
  }

  Swal.fire({
    title: "Hapus Data?",
    text: "Apakah Anda yakin ingin menghapus " + checked.length + " data tendik?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Ya, Hapus!",
    cancelButtonText: "Batal"
  }).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        url: base_url + "datatendik/delete",
        type: "POST",
        data: { checked: checked },
        success: function(response) {
          if (response.status) {
            Swal.fire({
              icon: "success",
              title: "Berhasil",
              text: checked.length + " data tendik berhasil dihapus",
              timer: 1500,
              showConfirmButton: false
            });
            reload_ajax();
          } else {
            Swal.fire({
              icon: "error",
              title: "Gagal",
              text: "Data tendik gagal dihapus"
            });
          }
        }
      });
    }
  });
}

function applySearch() {
  let val = $("#input-search").val();
  table.search(val).draw();
}
