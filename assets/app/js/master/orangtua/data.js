var table;

$(document).ready(function() {
  ajaxcsrf();

  const $loading = $("#loading");
  const $table = $("#table-orangtua");

  $table.on("processing.dt", function(e, settings, processing) {
    if (!$loading.length) {
      return;
    }
    if (processing) {
      $loading.removeClass("d-none");
    } else {
      $loading.addClass("d-none");
    }
  });

  if ($table.length && $.fn.DataTable && $.fn.DataTable.isDataTable($table[0])) {
    $table.DataTable().clear().destroy();
  }

  table = $table.DataTable({
    dom:
      "<'row'<'col-sm-12'tr>>" +
      "<'row'<'col-sm-5'i><'col-sm-7'p>>",
    oLanguage: {
      sProcessing: "loading..."
    },
    processing: true,
    serverSide: true,
    ajax: {
      url: base_url + "dataorangtua/data",
      type: "POST"
    },
    columns: [
      {
        data: "id_orangtua",
        orderable: false,
        searchable: false
      },
      {
        data: "id_orangtua",
        orderable: false,
        searchable: false
      },
      { data: "nama_lengkap" },
      { data: "no_hp" },
      { data: "email" },
      { data: "jenis_kelamin" },
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
      { data: "jml_anak" },
      {
        searchable: false,
        data: {
          id_orangtua: "id_orangtua",
          nama_lengkap: "nama_lengkap"
        },
        render: function(data, type, row, meta) {
          return `<div class="text-center">
									<a class="btn btn-xs btn-warning" href="${base_url}dataorangtua/edit/${data.id_orangtua}">
										<i class="fa fa-pencil"></i>
									</a>
									<button data-id="${data.id_orangtua}" data-nama="${data.nama_lengkap}" type="button" class="btn btn-xs btn-danger btn-delete">
										<i class="fa fa-trash"></i>
									</button>
								</div>`;
        }
      }
    ],
    columnDefs: [
      {
        targets: 0,
        data: "id_orangtua",
        render: function(data, type, row, meta) {
          return `<div class="text-center">
									<input name="checked[]" class="check" value="${data}" type="checkbox">
								</div>`;
        }
      }
    ],
    order: [[3, "asc"]],
    rowId: function(a) {
      return a;
    },
    rowCallback: function(row, data, iDisplayIndex) {
      var info = this.fnPagingInfo();
      var page = info.iPage;
      var length = info.iLength;
      var index = page * length + (iDisplayIndex + 1);
      $("td:eq(1)", row).html(index);
    },
    createdRow: function(row, data, dataIndex) {
    }
  });

  function syncSearchButtons() {
    var val = $("#input-search").val() || "";
    var hasValue = val.trim().length > 0;
    if (hasValue) {
      $("#btn-search").removeAttr("disabled");
      $("#btn-clear").removeAttr("disabled");
    } else {
      $("#btn-search").attr("disabled", "disabled");
      $("#btn-clear").attr("disabled", "disabled");
    }
  }

  $("#users_length").on("change", function() {
    var length = parseInt($(this).val(), 10);
    if (!isNaN(length)) {
      table.page.len(length).draw();
    }
  });

  $("#input-search").on("input", syncSearchButtons);
  $("#input-search").on("keyup", function(e) {
    if (e.key === "Enter") {
      applySearch();
    }
  });

  $("#btn-clear").on("click", function() {
    $("#input-search").val("");
    table.search("").draw();
    syncSearchButtons();
  });

  syncSearchButtons();

  $(".select_all").on("click", function() {
    if (this.checked) {
      $("#table-orangtua tbody .check").each(function() {
        this.checked = true;
      });
      $(".select_all").prop("checked", true);
      $("#hapusterpilih").removeAttr("disabled");
    } else {
      $("#table-orangtua tbody .check").each(function() {
        this.checked = false;
      });
      $(".select_all").prop("checked", false);
      $("#hapusterpilih").attr("disabled", "disabled");
    }
  });

  $("#table-orangtua tbody").on("click", "tr .check", function() {
    var total = $("#table-orangtua tbody tr .check").length;
    var checked = $("#table-orangtua tbody tr .check:checked").length;
    $(".select_all").prop("checked", total > 0 && total === checked);
    if (checked === 0) {
      $("#hapusterpilih").attr("disabled", "disabled");
    } else {
      $("#hapusterpilih").removeAttr("disabled");
    }
  });

  table.on("draw", function() {
    $(".select_all").prop("checked", false);
    var checked = $("#table-orangtua tbody tr .check:checked").length;
    if (checked === 0) {
      $("#hapusterpilih").attr("disabled", "disabled");
    }
  });

  $("#formorangtua").on("submit", function(e) {
    e.preventDefault();
    $.ajax({
      url: base_url + "dataorangtua/create",
      type: "POST",
      data: $(this).serialize(),
      dataType: "json",
      success: function(response) {
        if (response && response.status) {
          Swal.fire({
            icon: "success",
            title: "Berhasil",
            text: response.msg,
            timer: 1500,
            showConfirmButton: false
          });
          $("#createOrangtuaModal").modal("hide");
          $("#formorangtua")[0].reset();
          reload_ajax();
        } else {
          var errorMsg = response && response.msg ? response.msg : "Gagal menambahkan data";
          if (response && response.errors) {
            errorMsg = "";
            for (var key in response.errors) {
              if (response.errors[key]) {
                errorMsg += response.errors[key] + "<br>";
              }
            }
          }
          Swal.fire({
            icon: "error",
            title: "Gagal",
            html: errorMsg
          });
        }
      }
    });
  });

  $("#table-orangtua").on("click", ".btn-delete", function() {
    let id = $(this).data("id");
    let nama = $(this).data("nama");

    Swal.fire({
      title: "Hapus Data?",
      text: "Apakah Anda yakin ingin menghapus data orang tua: " + nama + "?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Ya, Hapus!",
      cancelButtonText: "Batal"
    }).then((result) => {
      if (result.isConfirmed || result.value) {
        $.ajax({
          url: base_url + "dataorangtua/delete",
          type: "POST",
          data: { checked: [id] },
          success: function(response) {
            if (response.status) {
              Swal.fire({
                icon: "success",
                title: "Berhasil",
                text: "Data orang tua berhasil dihapus",
                timer: 1500,
                showConfirmButton: false
              });
              reload_ajax();
            } else {
              Swal.fire({
                icon: "error",
                title: "Gagal",
                text: "Data orang tua gagal dihapus"
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
    text: "Apakah Anda yakin ingin menghapus " + checked.length + " data orang tua?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Ya, Hapus!",
    cancelButtonText: "Batal"
  }).then((result) => {
    if (result.isConfirmed || result.value) {
      $.ajax({
        url: base_url + "dataorangtua/delete",
        type: "POST",
        data: { checked: checked },
        success: function(response) {
          if (response.status) {
            Swal.fire({
              icon: "success",
              title: "Berhasil",
              text: checked.length + " data orang tua berhasil dihapus",
              timer: 1500,
              showConfirmButton: false
            });
            reload_ajax();
          } else {
            Swal.fire({
              icon: "error",
              title: "Gagal",
              text: "Data orang tua gagal dihapus"
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
