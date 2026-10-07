(function ($) {
    'use strict';

    function compact(value, type) {
        var text = value == null ? '' : String(value);
        if (type !== 'display') return text;
        return $('<span>')
            .addClass('khoa-text')
            .attr('title', text)
            .text(text)
            .prop('outerHTML');
    }

    var table = $('#datatable-khoa').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: $('#ULocal').val() + 'khoa/getData/',
            type: 'POST',
            data: function (data) {
                data.searchText = $('#khoa-search').val().trim();
            }
        },
        paging: false,
        scrollY: '60vh',
        scrollCollapse: true,
        deferRender: true,
        searching: false,
        ordering: false,
        responsive: false,
        autoWidth: false,
        columns: [
            { data: 'stt', width: '5%', defaultContent: '', className: 'text-center' },
            { data: 'ma', width: '9%', className: 'text-center' },
            { data: 'ten', width: '25%', render: compact },
            { data: 'ma_khoa_goc', width: '8%', defaultContent: '', className: 'text-center' },
            { data: 'ghi_chu', width: '34%', defaultContent: '', render: compact },
            { data: 'ngay_hieu_luc', width: '10%', className: 'text-center' },
            {
                data: 'is_active',
                width: '9%',
                className: 'text-center',
                render: function (value) {
                    return Number(value) === 1
                        ? '<i class="fa fa-check-circle text-success khoa-status" title="Hiện hành"></i>'
                        : '<i class="fa fa-ban text-danger khoa-status" title="Ngừng dùng"></i>';
                }
            }
        ],
        language: {
            processing: 'Đang tải dữ liệu...',
            lengthMenu: 'Hiển thị _MENU_ dòng',
            zeroRecords: 'Không tìm thấy mã khoa',
            info: 'Hiển thị _START_ đến _END_ trong _TOTAL_ mã',
            infoEmpty: 'Không có dữ liệu',
            infoFiltered: '(lọc từ _MAX_ mã)',
            paginate: { first: 'Đầu', last: 'Cuối', next: 'Sau', previous: 'Trước' }
        }
    });

    $('#btn-search-khoa').on('click', function () {
        table.ajax.reload(function () { $('#datatable-khoa_wrapper .dataTables_scrollBody').scrollTop(0); });
    });
    $('#btn-reset-khoa').on('click', function () {
        $('#khoa-search').val('');
        table.ajax.reload(function () { $('#datatable-khoa_wrapper .dataTables_scrollBody').scrollTop(0); });
        $('#khoa-search').focus();
    });
    $('#khoa-search').on('keydown', function (event) {
        if (event.key === 'Enter' || event.which === 13) {
            event.preventDefault();
            table.ajax.reload(function () { $('#datatable-khoa_wrapper .dataTables_scrollBody').scrollTop(0); });
        }
    });
})(jQuery);
