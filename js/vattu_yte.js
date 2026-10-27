(function ($) {
    'use strict';

    function textCell(value, type) {
        var text = value == null ? '' : String(value);
        if (type !== 'display') return text;
        return $('<span>').addClass('td-text').attr('title', text).text(text).prop('outerHTML');
    }

    function numberCell(value, type) {
        if (value === null || value === undefined || value === '') return '';
        var number = Number(value);
        if (type !== 'display' || isNaN(number)) return value;
        return number.toLocaleString('vi-VN', { maximumFractionDigits: 3 });
    }

    var table = $('#datatable-vattu-yte').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        scrollCollapse: false,
        ajax: {
            url: $('#ULocal').val() + 'vattu_yte/getData/',
            type: 'POST',
            data: function (data) {
                data.searchText = $('#vattu-yte-search').val().trim();
                data.hieuLuc = $('#vattu-yte-hieu-luc').val();
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
            { targets: 1, width: '180px' },
            { targets: [2, 3, 6, 12], width: '220px' },
            { targets: [4, 5], width: '150px' },
            { targets: 7, width: '100px' },
            { targets: 8, width: '70px' },
            { targets: [9, 10, 11], width: '110px' },
            { targets: [13, 14, 15], width: '100px' }
        ],
        columns: [
            { data: 'stt', className: 'text-center' },
            { data: 'ma_vat_tu', render: textCell },
            { data: 'nhom_vat_tu', render: textCell },
            { data: 'ten_vat_tu', render: textCell },
            { data: 'ma_hieu', render: textCell },
            { data: 'quy_cach', render: textCell },
            { data: 'hang_sx', render: textCell },
            { data: 'nuoc_sx', render: textCell },
            { data: 'don_vi_tinh', className: 'text-center' },
            { data: 'don_gia', className: 'text-right', render: numberCell },
            { data: 'don_gia_bh', className: 'text-right', render: numberCell },
            { data: 'tyle_tt_bh', className: 'text-right', render: numberCell },
            { data: 'nha_thau', render: textCell },
            { data: 'ma_cskcb', className: 'text-center' },
            { data: 'quyet_dinh', className: 'text-center' },
            { data: 'hieu_luc', className: 'text-center' }
        ],
        initComplete: function () {
            this.api().columns.adjust();
        },
        language: {
            processing: 'Đang tải dữ liệu...',
            lengthMenu: 'Hiển thị _MENU_ dòng',
            zeroRecords: 'Không tìm thấy vật tư y tế',
            info: 'Hiển thị _START_ đến _END_ trong _TOTAL_ dòng',
            infoEmpty: 'Không có dữ liệu',
            infoFiltered: '(lọc từ _MAX_ dòng)',
            paginate: { first: 'Đầu', last: 'Cuối', next: 'Sau', previous: 'Trước' }
        }
    });

    function updatePageNavigation() {
        var page = table.page.info();
        var current = page.pages ? page.page + 1 : 0;
        $('#vattu-yte-page-info').text('Trang ' + current + ' / ' + page.pages);
        $('#btn-vattu-yte-prev').prop('disabled', page.page <= 0);
        $('#btn-vattu-yte-next').prop('disabled', page.pages === 0 || page.page >= page.pages - 1);
    }

    function reload() {
        table.ajax.reload();
    }

    table.on('draw.dt', function () {
        table.columns.adjust();
        updatePageNavigation();
    });

    $('#btn-vattu-yte-prev').on('click', function () {
        if (!this.disabled) table.page('previous').draw('page');
    });

    $('#btn-vattu-yte-next').on('click', function () {
        if (!this.disabled) table.page('next').draw('page');
    });

    $('#btn-search-vattu-yte').on('click', reload);
    $('#vattu-yte-hieu-luc').on('change', reload);
    $('#btn-reset-vattu-yte').on('click', function () {
        $('#vattu-yte-search').val('');
        $('#vattu-yte-hieu-luc').val('');
        reload();
    });
    $('#vattu-yte-search').on('keydown', function (event) {
        if (event.key === 'Enter' || event.which === 13) {
            event.preventDefault();
            reload();
        }
    });
    $(window).on('load resize', function () {
        table.columns.adjust();
    });
})(jQuery);
