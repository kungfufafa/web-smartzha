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
      "<'row'<'col-sm-3'l><'col-sm-6 text-center'B><'col-sm-3'f>>" +
      "<'row'<'col-sm-12'tr>>" +
      "<'row'<'col-sm-5'i><'col-sm-7'p>>",
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
        data: "id",
        className: "text-center",
        orderable: false,
        searchable: false
      },
      {
        data: "nama_lengkap",
        render: function(data, type, row, meta) {
          let foto = row.foto ? base_url + row.foto : base_url + "assets/img/siswa.png";
          return `
            <div class="media">
              <img class="img-circle img-thumbnail" style="width: 40px; height: 40px;"
                   src="${foto}"
                   onerror="this.src='${base_url}assets/img/siswa.png'">
              <div class="media-body ml-3">
                <p class="mb-0 font-weight-bold">${data}</p>
                <p class="mb-0 small text-muted">${row.no_hp || '-'}</p>
              </div>
            </div>
          `;
        }
      },
      { data: "username" },
      { data: "no_hp" },
      { data: "email" },
      {
        data: "active",
        className: "text-center",
        orderable: true,
        searchable: false,
        render: function(data, type, row, meta) {
          if (data > 0) {
            return `<span class="badge badge-success">Aktif</span>`;
          } else {
            return `<span class="badge badge-danger">Tidak Aktif</span>`;
          }
        }
      },
      {
        searchable: false,
        className: "text-center",
        targets: 6,
        data: {
          username: "username",
          nama_lengkap: "nama_lengkap",
          reset: "reset"
        },
        render: function(data, type, row, meta) {
          return `<button type="button" class="btn btn-reset btn-default btn-xs ${data.reset == 0 ? 'btn-disabled' : ''}"
                                data-username="${data.username}" data-nama="${data.nama_lengkap}" data-toggle="tooltip" title="Reset Login"
                                ${data.reset == 0 ? 'disabled' : ''}>
                                <i class="fa fa-sync m-1"></i>
                            </button>`;
        }
      },
      {
        searchable: false,
        className: "text-center",
        targets: 7,
        data: {
          id: "id",
          nama_lengkap: "nama_lengkap",
          aktif: "active"
        },
        render: function(data, type, row, meta) {
          let btn;
          if (data.aktif > 0) {
            btn = `<button type="button" class="btn btn-nonaktif btn-danger btn-xs" data-id="${data.id}" data-nama="${data.nama_lengkap}" data-toggle="tooltip" title="Nonaktifkan">
              <i class="fa fa-ban m-1"></i>
            </button>`;
          } else {
            btn = `<button type="button" class="btn btn-aktif btn-success btn-xs" data-id="${data.id}" data-toggle="tooltip" title="Aktifkan">
              <i class="fa fa-user-plus m-1"></i>
            </button>`;
          }
          return btn;
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

    Swal.fire({
      title: "Aktifkan Akun",
      text: "Aktifkan akun ini?",
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Ya, Aktifkan"
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: base_url + "userorangtua/activate",
          type: "POST",
          data: { id: id },
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

  $("#users").on("click", ".btn-nonaktif", function() {
    let id = $(this).data("id");
    let nama = $(this).data("nama");

    Swal.fire({
      title: "Nonaktifkan Akun",
      text: "Nonaktifkan akun ini?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Ya, Nonaktifkan"
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: base_url + "userorangtua/deactivate",
          type: "POST",
          data: { id: id },
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

  $("#users").on("click", ".btn-reset", function() {
    let username = $(this).data("username");
    let nama = $(this).data("nama");

    Swal.fire({
      title: "Reset Login",
      text: "Reset login untuk " + nama + "?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Ya, Reset"
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: base_url + "userorangtua/reset_login",
          type: "POST",
          data: { username: username },
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
      if (result.isConfirmed) {
        $.ajax({
          url: base_url + "userorangtua/activate_all",
          type: "POST",
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
      if (result.isConfirmed) {
        $.ajax({
          url: base_url + "userorangtua/deactivate_all",
          type: "POST",
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
