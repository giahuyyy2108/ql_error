(function ($) {
    'use strict';

    function textCell(value, type) {
        var text = value == null ? '' : String(value);
        if (type !== 'display') return text;
        return $('<span>').addClass('td-text').attr('title', text).text(text).prop('outerHTML');
    }

    function dateCell(value, type) {
        if (!value) return '';
        if (type !== 'display') return value;
        var parts = String(value).split('-');
        return parts.length === 3 ? parts[2] + '/' + parts[1] + '/' + parts[0] : value;
    }

    var table = $('#datatable-trang-thiet-bi').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        scrollCollapse: false,
        ajax: {
            url: $('#ULocal').val() + 'trang_thiet_bi/getData/',
            type: 'POST',
            data: function (data) {
                data.searchText = $('#trang-thiet-bi-search').val().trim();
                data.nuocSx = $('#trang-thiet-bi-nuoc-sx').val();
            }
        },
        pageLength: 25,
        pagingType: 'full_numbers',
        lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
        searching: false,
        ordering: false,
        autoWidth: false,
        columnDefs: [
            { targets: 0, width: '55px' },
            { targets: 1, width: '300px' },
            { targets: [2, 3], width: '160px' },
            { targets: 4, width: '110px' },
            { targets: [5, 6], width: '85px' },
            { targets: [7, 8], width: '150px' },
            { targets: [9, 10, 11, 12], width: '100px' }
        ],
        columns: [
            { data: 'stt', className: 'text-center' },
            { data: 'ten_tb', render: textCell },
            { data: 'ky_hieu', render: textCell },
            { data: 'congty_sx', render: textCell },
            { data: 'nuoc_sx', render: textCell },
            { data: 'nam_sx', className: 'text-center' },
            { data: 'nam_sd', className: 'text-center' },
            { data: 'ma_may', render: textCell },
            { data: 'so_luu_hanh', render: textCell },
            { data: 'hd_tu', className: 'text-center', render: dateCell },
            { data: 'hd_den', className: 'text-center', render: dateCell },
            { data: 'tu_ngay', className: 'text-center', render: dateCell },
            { data: 'den_ngay', className: 'text-center', render: dateCell }
        ],
        initComplete: function () {
            this.api().columns.adjust();
        },
        language: {
            processing: 'Đang tải dữ liệu...',
            lengthMenu: 'Hiển thị _MENU_ dòng',
            zeroRecords: 'Không tìm thấy trang thiết bị y tế',
            info: 'Hiển thị _START_ đến _END_ trong _TOTAL_ dòng',
            infoEmpty: 'Không có dữ liệu',
            infoFiltered: '(lọc từ _MAX_ dòng)',
            paginate: { first: 'Đầu', last: 'Cuối', next: 'Sau', previous: 'Trước' }
        }
    });

    function updatePageNavigation() {
        var page = table.page.info();
        var current = page.pages ? page.page + 1 : 0;
        $('#trang-thiet-bi-page-info').text('Trang ' + current + ' / ' + page.pages);
        $('#btn-trang-thiet-bi-prev').prop('disabled', page.page <= 0);
        $('#btn-trang-thiet-bi-next').prop('disabled', page.pages === 0 || page.page >= page.pages - 1);
    }

    function reload() {
        table.ajax.reload();
    }

    table.on('draw.dt', function () {
        table.columns.adjust();
        updatePageNavigation();
    });

    $('#btn-trang-thiet-bi-prev').on('click', function () {
        if (!this.disabled) table.page('previous').draw('page');
    });

    $('#btn-trang-thiet-bi-next').on('click', function () {
        if (!this.disabled) table.page('next').draw('page');
    });

    $('#btn-search-trang-thiet-bi').on('click', reload);
    $('#trang-thiet-bi-nuoc-sx').on('change', reload);
    $('#btn-reset-trang-thiet-bi').on('click', function () {
        $('#trang-thiet-bi-search').val('');
        $('#trang-thiet-bi-nuoc-sx').val('');
        reload();
    });
    $('#trang-thiet-bi-search').on('keydown', function (event) {
        if (event.key === 'Enter' || event.which === 13) {
            event.preventDefault();
            reload();
        }
    });
    $(window).on('load resize', function () {
        table.columns.adjust();
    });
})(jQuery);
