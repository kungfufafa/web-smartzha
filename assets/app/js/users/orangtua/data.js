var table;

$(document).ready(function() {
  ajaxcsrf();

  table = $("#users").DataTable({
    initComplete: function() {
      var api = this.api();
      $("#users_filter input")
        .off(".DT")
        .on("keyup.DT", function(e) {
          api.search(this.value).draw();
        });
    },
    dom:
      "<'row'<'col-sm-3'l><'col-sm-9'f>>" +
      "<'row'<'col-sm-12'tr>>" +
      "<'row'<'col-sm-5'i><'col-sm-7'p>>",
    buttons: [],
    oLanguage: {
      sProcessing: "loading..."
    },
    processing: true,
    serverSide: true,
    ajax: {
      url: base_url + "userorangtua/data",
      type: "POST"
    },
    columns: [
      {
        data: "id_orangtua",
        className: "text-center",
        orderable: false,
        searchable: false
      },
      {
        data: "nama_lengkap",
        render: function(data, type, row, meta) {
          return data || "";
        }
      },
      { data: "username" },
      {
        data: "username",
        render: function(data, type, row, meta) {
          return row.username || "";
        }
      },
      {
        data: "anak",
        render: function(data, type, row, meta) {
          return data || "-";
        }
      },
      {
        data: "peran",
        render: function(data, type, row, meta) {
          return data || "-";
        }
      },
      {
        data: null,
        searchable: false,
        className: "text-center",
        orderable: false,
        render: function(data, type, row, meta) {
          return `<button type="button" class="btn btn-reset btn-default btn-xs ${row.reset == 0 ? 'btn-disabled' : ''}"
                                data-username="${row.username}" data-nama="${row.nama_lengkap}" data-toggle="tooltip" title="Reset Login"
                                ${row.reset == 0 ? 'disabled' : ''}>
                                <i class="fa fa-sync m-1"></i>
                            </button>`;
        }
      },
      {
        data: "aktif",
        className: "text-center",
        orderable: true,
        searchable: false,
        render: function(data, type, row, meta) {
          if (data > 0) {
            return `<span class="badge badge-success">Aktif</span>`;
          }
          return `<span class="badge badge-danger">Tidak Aktif</span>`;
        }
      },
      {
        data: null,
        searchable: false,
        className: "text-center",
        orderable: false,
        render: function(data, type, row, meta) {
          if (row.aktif > 0) {
            return `<button type="button" class="btn btn-nonaktif btn-danger btn-xs" data-id="${row.id}" data-nama="${row.nama_lengkap}" data-toggle="tooltip" title="Nonaktifkan">
              <i class="fa fa-ban m-1"></i>
            </button>`;
          }
          return `<button type="button" class="btn btn-aktif btn-success btn-xs" data-id="${row.id_orangtua}" data-toggle="tooltip" title="Aktifkan">
              <i class="fa fa-user-plus m-1"></i>
            </button>`;
        }
      }
    ],
    order: [[1, "asc"]],
    rowId: function(a) {
      return a;
    },
    rowCallback: function(row, data, iDisplayIndex) {
      var info = this.fnPagingInfo();
      var page = info.iPage;
      var length = info.iLength;
      var index = page * length + (iDisplayIndex + 1);
      $("td:eq(0)", row).html(index);
    },
    createdRow: function(row, data, dataIndex) {
    }
  });

  $("#users").on("click", ".btn-aktif", function() {
    let id = $(this).data("id");
    let nama = $(this).data("nama");

    $.ajax({
      type: "GET",
      url: base_url + "userorangtua/activate/" + id,
      success: function(response) {
        if (response.status) {
          Swal.fire({
            icon: "success",
            title: "Berhasil",
            text: response.msg,
            timer: 1500,
            showConfirmButton: false
          });
          table.ajax.reload(null, false);
        } else {
          Swal.fire({
            icon: "error",
            title: "Gagal",
            text: response.msg
          });
        }
      }
    });
  });

  $("#users").on("click", ".btn-nonaktif", function() {
    let id = $(this).data("id");
    let nama = $(this).data("nama");

    $.ajax({
      type: "GET",
      url: base_url + "userorangtua/deactivate/" + id,
      success: function(response) {
        if (response.status) {
          Swal.fire({
            icon: "success",
            title: "Berhasil",
            text: response.msg,
            timer: 1500,
            showConfirmButton: false
          });
          table.ajax.reload(null, false);
        } else {
          Swal.fire({
            icon: "error",
            title: "Gagal",
            text: response.msg
          });
        }
      }
    });
  });

  $("#users").on("click", ".btn-reset", function() {
    let username = $(this).data("username");
    let nama = $(this).data("nama");

    $.ajax({
      type: "GET",
      url: base_url + "userorangtua/reset_login?username=" + username,
      success: function(response) {
        if (response.status) {
          Swal.fire({
            icon: "success",
            title: "Berhasil",
            text: response.msg,
            timer: 1500,
            showConfirmButton: false
          });
          table.ajax.reload(null, false);
        } else {
          Swal.fire({
            icon: "error",
            title: "Gagal",
            text: response.msg
          });
        }
      }
    });
  });

  $("[data-action='aktifkan']").on("click", function() {
    Swal.fire({
      title: "Aktifkan Semua",
      text: "Aktifkan semua akun orang tua?",
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Ya, Aktifkan Semua"
    }).then((result) => {
      if (result.value) {
        $.ajax({
            url: base_url + "userorangtua/aktifkansemua",
          type: "GET",
          success: function(response) {
            if (response.status) {
              Swal.fire({
                icon: "success",
                title: "Berhasil",
                text: response.msg,
                timer: 1500,
                showConfirmButton: false
              });
              table.ajax.reload(null, false);
            } else {
              Swal.fire({
                icon: "error",
                title: "Gagal",
                text: response.msg
              });
            }
          }
        });
      }
    });
  });

  $("[data-action='nonaktifkan']").on("click", function() {
    Swal.fire({
      title: "Nonaktifkan Semua",
      text: "Nonaktifkan semua akun orang tua?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Ya, Nonaktifkan Semua"
    }).then((result) => {
      if (result.value) {
        $.ajax({
            url: base_url + "userorangtua/nonaktifkansemua",
          type: "GET",
          success: function(response) {
            if (response.status) {
              Swal.fire({
                icon: "success",
                title: "Berhasil",
                text: response.msg,
                timer: 1500,
                showConfirmButton: false
              });
              table.ajax.reload(null, false);
            } else {
              Swal.fire({
                icon: "error",
                title: "Gagal",
                text: response.msg
              });
            }
          }
        });
      }
    });
  });
});
