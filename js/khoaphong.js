/* DATA TABLES */
var table;
function init_DataTables() {
    table = $('#datatable-khoaphong').DataTable({
        "ajax":
        {
            url: $("#ULocal").val() + 'KhoaPhong/getData/',
            type: 'POST',
        },
        error: function (response) {
            console.log(JSON.stringify(response));
        },
        dom: '<"dt-toolbar">frtlpi',
        destroy: true,
        searching: false,
        "columnDefs": [
            {
                "targets": 0,
                "width": '5%',
                "className": "text-center",
                "sortable": true,
                "render": function (data, type, row, meta) {
                    return (meta.row + 1);
                }
            },
            {
                "targets": 1,
                "width": '80%',
                "data": "TenKhoaPhong"
            },
            {
                "targets": 2,
                "width": '15%',
                "data": "MaKhoaPhong",
                "render": function (data, type, row) {
                    var save = "", update = "", del = "", id = "";
                    id = '<input type="hidden" name = "id" id = "id" value="' + data + '">';
                    save = '<button id="btn-update" class="add btn btn-primary btn-sm" title="Lưu" data-toggle="tooltip"><i class="fa fa-hdd-o"></i></button>';
                    update = '<button class="edit btn btn-warning btn-sm" title="Sửa" data-toggle="tooltip"><i class="glyphicon glyphicon-cog"></i></button>';

                    if (data != 2) {
                        del = '<button class="delete btn btn-danger btn-sm" title="Xóa" data-toggle="tooltip"><i class="glyphicon glyphicon-trash"></i></button>';
                    }
                    return [id, save, update, del].join('');
                }
            }

        ],
        "language": {
            "lengthMenu": "Hiển thị _MENU_ dòng trên một trang",
            "zeroRecords": "Xin lỗi không tìm thấy dữ liệu",
            "info": "Hiển thị trang _PAGE_ of _PAGES_ trang",
            "infoEmpty": "Không có dữ liệu",
            "infoFiltered": "(filtered from _MAX_ total records)",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Sau",
                "previous": "Trước"
            }
        },
        initComplete: function () {
            $("div.dt-toolbar").css({ display: "flex", justifyContent: "space-between", alignItems: "center" }).html(
                '<div class="left-toolbar">' +
                '<button type="button" class="btn btn-info add-new"><i class="fa fa-plus"></i> Thêm mới</button>' +
                '<button type="button" class="btn btn-info save"><i class="fa fa-floppy-o"></i> Lưu dữ liệu</button>' +
                '</div>' +
                '<div class="right-toolbar">' +
                '<button class="btn btn-info btn-sm" title="Lưu" data-toggle="tooltip"><i class="fa fa-floppy-o"></i></button>' +
                '<lable >&nbsp;Lưu dữ liệu khi thêm mới &nbsp;</lable>' +
                '<button class="btn btn-info btn-sm" title="Lưu" data-toggle="tooltip"><i class="fa fa-plus"></i></button>' +
                '<lable >&nbsp;Thêm mới khoa phòng &nbsp;</lable>' +
                '<button class="btn btn-primary btn-sm" title="Lưu" data-toggle="tooltip"><i class="fa fa-hdd-o"></i></button>' +
                '<lable >&nbsp;Lưu &nbsp;</lable>' +
                '<button class="btn btn-warning btn-sm" title="Sửa" data-toggle="tooltip"><i class="glyphicon glyphicon-cog"></i></button>' +
                '<lable>&nbsp;Sửa &nbsp;</lable>' +
                '<button class="btn btn-danger btn-sm" title="Xóa" data-toggle="tooltip"><i class="glyphicon glyphicon-trash"></i></button>' +
                '<lable>&nbsp;Xóa &nbsp;</lable>' +
                '</div>'
            );
            hiddenButton();
            $(".add-new").click(function () {
                addRowInput($('#datatable-khoaphong'), 1);
            });

            $(".save").click(function () {
                Swal.fire({
                    title: "Bạn có muốn cập nhập không?",
                    text: "Hành động này sẽ lưu dữ liệu mới vào hệ thống",
                    icon: "warning",
                    showCancelButton: true,
                    cancelButtonText: "Hủy",
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Lưu"
                }).then((result) => {
                    if (result.isConfirmed) {
                        saveData($('#datatable-khoaphong'), $("#ULocal").val() + 'khoaphong/saveKhoaPhong/', "'Bác sĩ đã được cập nhật thành công.'");
                        setTimeout(() => {
                            $('#datatable-khoaphong').DataTable().ajax.reload(hiddenButton);
                        }, 1000);
                    }
                });
            });
        }

    });
};

$(document).ready(function () {

    $('[data-toggle="tooltip"]').tooltip();
    $(document).on("click", ".add", function () {
        Swal.fire({
            title: "Bạn có muốn cập nhập không?",
            text: "Hành động này sẽ lưu dữ liệu mới vào hệ thống",
            icon: "warning",
            showCancelButton: true,
            cancelButtonText: "Hủy",
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Lưu"
        }).then((result) => {
            if (result.isConfirmed) {
                updateData($(this), $("#ULocal").val() + 'khoaphong/saveKhoaPhong/', 'Khoa Phòng đã được cập nhật thành công.');
                setTimeout(() => {
                    $('#datatable-khoaphong').DataTable().ajax.reload(hiddenButton);
                }, 1000);
            }
        });
    });

    $(document).on("click", ".edit", function () {
        addInput($('#datatable-khoaphong'), $(this).closest('tr'));
    });

    $(document).on("click", ".delete", function () {
        Swal.fire({
            icon: "warning",
            title: "Bạn có chắc muốn xóa khoa phòng này không?",
            text: "Hành động này sẽ xóa đi khoa phòng này trong hệ thống.",
            showCancelButton: true,
            showCancelText: "Hủy",
            confirmButtonText: "Xóa",
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33"
        }).then((result) => {
            if (result.isConfirmed) {
                deleteData(table, $(this), $("#ULocal").val() + 'khoaphong/deleteKhoaPhong/', 'Khoa Phòng đã được xóa thành công.')
                $('#datatable-khoaphong').DataTable().ajax.reload(hiddenButton);
            }
        });
    });

});

function hiddenButton() {
    if ($("#role-khoaphong-saveKhoaPhong").val() == "false") {
        $(".add-new").hide();
        $(".edit").hide();
        $(".add").hide();
        $(".save").hide();
    }
    if ($("#role-khoaphong-deleteKhoaPhong").val() == "false") {
        $(".delete").hide();
    }
}
