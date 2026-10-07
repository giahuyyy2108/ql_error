(function ($) {
    'use strict';

    var table = $('#datatable-nhanvienyte').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: $('#ULocal').val() + 'nhanvienyte/getData/',
            type: 'POST',
            data: function (data) {
                data.searchText = $.trim($('#nhanvienyte-search').val());
            }
        },
        paging: false,
        searching: false,
        scrollY: '60vh',
        scrollCollapse: true,
        deferRender: true,
        ordering: false,
        responsive: true,
        autoWidth: false,
        columns: [
            { data: 'id', width: '18%' },
            { data: 'ho_ten', width: '52%' },
            { data: 'macchn', width: '30%', defaultContent: '' }
        ],
        language: {
            processing: 'Đang tải dữ liệu...',
            search: 'Tìm kiếm:',
            lengthMenu: 'Hiển thị _MENU_ dòng',
            zeroRecords: 'Không tìm thấy nhân viên y tế',
            info: 'Hiển thị _START_ đến _END_ trong _TOTAL_ nhân viên',
            infoEmpty: 'Không có dữ liệu',
            infoFiltered: '(lọc từ _MAX_ nhân viên)',
            paginate: { first: 'Đầu', last: 'Cuối', next: 'Sau', previous: 'Trước' }
        }
    });

    function reloadTable() {
        table.ajax.reload(function () {
            $('#datatable-nhanvienyte_wrapper .dataTables_scrollBody').scrollTop(0);
        });
    }

    $('#btn-search-nhanvienyte').on('click', reloadTable);
    $('#btn-reset-nhanvienyte').on('click', function () {
        $('#nhanvienyte-search').val('').focus();
        reloadTable();
    });
    $('#nhanvienyte-search').on('keydown', function (event) {
        if (event.key === 'Enter' || event.which === 13) {
            event.preventDefault();
            reloadTable();
        }
    });
})(jQuery);
