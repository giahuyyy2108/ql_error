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

    function dateCell(value, type) {
        if (!value) return '';
        if (type !== 'display') return value;
        var parts = String(value).split('-');
        return parts.length === 3 ? parts[2] + '/' + parts[1] + '/' + parts[0] : value;
    }

    var table = $('#datatable-dichvu-bv').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        scrollCollapse: false,
        ajax: {
            url: $('#ULocal').val() + 'dichvu_bv/getData/',
            type: 'POST',
            data: function (data) {
                data.searchText = $('#dichvu-bv-search').val().trim();
                data.hieuLuc = $('#dichvu-bv-hieu-luc').val();
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
            { targets: [1, 9, 14], width: '130px' },
            { targets: [2, 3, 10], width: '260px' },
            { targets: 4, width: '100px' },
            { targets: [5, 11, 12], width: '110px' },
            { targets: [6, 13], width: '160px' },
            { targets: [7, 8], width: '100px' },
            { targets: [15, 16, 17], width: '120px' }
        ],
        columns: [
            { data: 'stt', className: 'text-center' },
            { data: 'ma_tuong_duong', render: textCell },
            { data: 'ten_dvkt_pheduyet', render: textCell },
            { data: 'ten_dvkt_gia', render: textCell },
            { data: 'phan_loai_pttt', className: 'text-center' },
            { data: 'don_gia', className: 'text-right', render: numberCell },
            { data: 'quyet_dinh', render: textCell },
            { data: 'tu_ngay', className: 'text-center', render: dateCell },
            { data: 'den_ngay', className: 'text-center', render: dateCell },
            { data: 'ma', render: textCell },
            { data: 'ten', render: textCell },
            { data: 'gia', className: 'text-right', render: numberCell },
            { data: 'gia_dvkt', className: 'text-right', render: numberCell },
            { data: 'so_quyet_dinh', render: textCell },
            { data: 'ma_nhom_5937', className: 'text-center' },
            { data: 'hieu_luc', className: 'text-center' },
            { data: 'trang_thai_gui', render: textCell },
            { data: 'ket_qua_giam_dinh', render: textCell }
        ],
        initComplete: function () {
            this.api().columns.adjust();
        },
        language: {
            processing: 'Đang tải dữ liệu...',
            lengthMenu: 'Hiển thị _MENU_ dòng',
            zeroRecords: 'Không tìm thấy dịch vụ bệnh viện',
            info: 'Hiển thị _START_ đến _END_ trong _TOTAL_ dòng',
            infoEmpty: 'Không có dữ liệu',
            infoFiltered: '(lọc từ _MAX_ dòng)',
            paginate: { first: 'Đầu', last: 'Cuối', next: 'Sau', previous: 'Trước' }
        }
    });

    function updatePageNavigation() {
        var page = table.page.info();
        var current = page.pages ? page.page + 1 : 0;
        $('#dichvu-bv-page-info').text('Trang ' + current + ' / ' + page.pages);
        $('#btn-dichvu-bv-prev').prop('disabled', page.page <= 0);
        $('#btn-dichvu-bv-next').prop('disabled', page.pages === 0 || page.page >= page.pages - 1);
    }

    function reload() {
        table.ajax.reload();
    }

    table.on('draw.dt', function () {
        table.columns.adjust();
        updatePageNavigation();
    });

    $('#btn-dichvu-bv-prev').on('click', function () {
        if (!this.disabled) table.page('previous').draw('page');
    });
    $('#btn-dichvu-bv-next').on('click', function () {
        if (!this.disabled) table.page('next').draw('page');
    });
    $('#btn-search-dichvu-bv').on('click', reload);
    $('#dichvu-bv-hieu-luc').on('change', reload);
    $('#btn-reset-dichvu-bv').on('click', function () {
        $('#dichvu-bv-search').val('');
        $('#dichvu-bv-hieu-luc').val('');
        reload();
    });
    $('#dichvu-bv-search').on('keydown', function (event) {
        if (event.key === 'Enter' || event.which === 13) {
            event.preventDefault();
            reload();
        }
    });
    $(window).on('load resize', function () {
        table.columns.adjust();
    });
})(jQuery);
