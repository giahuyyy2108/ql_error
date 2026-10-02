(function ($) {
    'use strict';

    function compact(value, type) {
        var text = value == null ? '' : String(value);
        if (type !== 'display') return text;
        return $('<span>')
            .addClass('dantoc-text')
            .attr('title', text)
            .text(text)
            .prop('outerHTML');
    }

    var table = $('#datatable-dantoc').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: $('#ULocal').val() + 'dantoc/getData/',
            type: 'POST',
            data: function (data) {
                data.searchText = $('#dantoc-search').val().trim();
            }
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
        searching: false,
        ordering: false,
        responsive: false,
        autoWidth: false,
        columns: [
            { data: 'ma', width: '8%', className: 'text-center' },
            { data: 'ten', width: '24%', render: compact },
            { data: 'ten_goi_khac', width: '58%', defaultContent: '', render: compact },
            {
                data: 'is_active',
                width: '10%',
                className: 'text-center',
                render: function (value) {
                    return Number(value) === 1
                        ? '<i class="fa fa-check-circle text-success dantoc-status" title="Hiện hành"></i>'
                        : '<i class="fa fa-ban text-danger dantoc-status" title="Ngừng dùng"></i>';
                }
            }
        ],
        language: {
            processing: 'Đang tải dữ liệu...',
            lengthMenu: 'Hiển thị _MENU_ dòng',
            zeroRecords: 'Không tìm thấy dân tộc',
            info: 'Hiển thị _START_ đến _END_ trong _TOTAL_ mã',
            infoEmpty: 'Không có dữ liệu',
            infoFiltered: '(lọc từ _MAX_ mã)',
            paginate: { first: 'Đầu', last: 'Cuối', next: 'Sau', previous: 'Trước' }
        }
    });

    $('#btn-search-dantoc').on('click', function () {
        table.ajax.reload();
    });
    $('#btn-reset-dantoc').on('click', function () {
        $('#dantoc-search').val('');
        table.ajax.reload();
        $('#dantoc-search').focus();
    });
    $('#dantoc-search').on('keydown', function (event) {
        if (event.key === 'Enter' || event.which === 13) {
            event.preventDefault();
            table.ajax.reload();
        }
    });
})(jQuery);
