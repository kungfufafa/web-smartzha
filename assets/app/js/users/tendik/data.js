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
        oLanguage: {
            sProcessing: "loading..."
        },
        processing: true,
        serverSide: true,
        ajax: {
            url: base_url + "usertendik/data",
            type: "POST"
        },
        columns: [
            {
                data: "id",
                className: "text-center",
                orderable: false,
                searchable: false
            },
            { data: "nama_tendik" },
            { data: "username" },
            { data: "nip" },
            { data: "tipe_tendik" },
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
            }
        ],
        columnDefs: [
            {
                searchable: false,
                className: "text-center",
                orderable: false,
                targets: 6,
                data: {
                    username: "username",
                    nama_tendik: "nama_tendik",
                    reset: "reset"
                },
                render: function(data, type, row, meta) {
                    return `<button type="button" class="btn btn-reset btn-default btn-xs ${data.reset == 0 ? 'btn-disabled' : ''}"
                                data-username="${data.username}" data-nama="${data.nama_tendik}" data-toggle="tooltip" title="Reset Login"
                                ${data.reset == 0 ? 'disabled' : ''}>
                                <i class="fa fa-sync m-1"></i>
                            </button>`;
                }
            },
            {
                searchable: false,
                className: "text-center",
                orderable: false,
                targets: 7,
                data: {
                    id: "id",
                    nama_tendik: "nama_tendik",
                    aktif: "active"
                },
                render: function(data, type, row, meta) {
                    let btn;
                    if (data.aktif > 0) {
                        btn = `<button type="button" class="btn btn-nonaktif btn-danger btn-xs" data-id="${data.id}" data-nama="${data.nama_tendik}" data-toggle="tooltip" title="Nonaktifkan">
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
        }
    });

    table
        .buttons()
        .container()
        .appendTo("#users_wrapper .col-md-6:eq(0)");

    $("#users").on("click", ".btn-aktif", function() {
        let id = $(this).data("id");
        $('#loading').removeClass('d-none');
        $.ajax({
            url: base_url + "usertendik/activate",
            type: "POST",
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                $('#loading').addClass('d-none');
                if (response.msg) {
                    if (response.status) {
                        swal.fire({
                            title: "Sukses",
                            text: response.msg,
                            icon: "success"
                        });
                        reload_ajax();
                    } else {
                        swal.fire({
                            title: "Error",
                            text: response.msg,
                            icon: "error"
                        });
                    }
                }
            },
            error: function(xhr, status, error) {
                $('#loading').addClass('d-none');
                console.log(xhr);
                Swal.fire({
                    title: "Gagal",
                    html: xhr.responseText,
                    icon: "error"
                });
            }
        });
    });

    $("#users").on("click", ".btn-nonaktif", function() {
        let id = $(this).data("id");
        let nama = $(this).data("nama");
        $('#loading').removeClass('d-none');
        $.ajax({
            url: base_url + "usertendik/deactivate",
            type: "POST",
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                $('#loading').addClass('d-none');
                if (response.msg) {
                    if (response.status) {
                        swal.fire({
                            title: "Sukses",
                            text: nama + ' ' + response.msg,
                            icon: "success"
                        });
                        reload_ajax();
                    } else {
                        swal.fire({
                            title: "Error",
                            text: response.msg,
                            icon: "error"
                        });
                    }
                }
            },
            error: function(xhr, status, error) {
                $('#loading').addClass('d-none');
                console.log(xhr);
                Swal.fire({
                    title: "Gagal",
                    html: xhr.responseText,
                    icon: "error"
                });
            }
        });
    });

    $("#users").on("click", ".btn-reset", function() {
        let username = $(this).data("username");
        let nama = $(this).data("nama");
        $('#loading').removeClass('d-none');
        $.ajax({
            url: base_url + "usertendik/reset_login",
            type: "POST",
            data: { username: username },
            dataType: 'json',
            success: function(response) {
                $('#loading').addClass('d-none');
                if (response.msg) {
                    if (response.status) {
                        swal.fire({
                            title: "Sukses",
                            html: "<b>" + nama + " " + response.msg + "</b>",
                            icon: "success"
                        });
                        reload_ajax();
                    } else {
                        swal.fire({
                            title: "Error",
                            html: "<b>" + nama + " " + response.msg + "</b>",
                            icon: "error"
                        });
                    }
                }
            },
            error: function(xhr, status, error) {
                $('#loading').addClass('d-none');
                console.log(xhr);
                Swal.fire({
                    title: "Gagal",
                    html: xhr.responseText,
                    icon: "error"
                });
            }
        });
    });

    $(".btn-action").on("click", function() {
        let action = $(this).data("action");
        let uri = action === 'aktifkan' ? base_url + "usertendik/activate_all" : base_url + "usertendik/deactivate_all";

        swal.fire({
            title: action === 'aktifkan' ? "Aktifkan Semua Tendik" : "Nonaktifkan Semua Tendik",
            text: "",
            icon: "info",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Lanjutkan"
        }).then(result => {
            if (result.value) {
                $('#loading').removeClass('d-none');
                $.ajax({
                    url: uri,
                    type: "POST",
                    dataType: "json",
                    success: function(response) {
                        $('#loading').addClass('d-none');
                        swal.fire({
                            title: response.status ? "Sukses" : "Gagal",
                            text: response.msg,
                            icon: response.status ? "success" : "error"
                        }).then(result => {
                            reload_ajax();
                        });
                    },
                    error: function(xhr, status, error) {
                        $('#loading').addClass('d-none');
                        console.log(xhr);
                        Swal.fire({
                            title: "Gagal",
                            html: xhr.responseText,
                            icon: "error"
                        });
                    }
                });
            }
        });
    });
});

function reload_ajax() {
    table.ajax.reload();
}
