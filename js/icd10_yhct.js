(function ($) {
    'use strict';
    function compact(value, type) {
        var text = value == null ? '' : String(value);
        if (type !== 'display') return text;
        return $('<span>').addClass('catalog-text').attr('title', text).text(text).prop('outerHTML');
    }
    var table = $('#datatable-icd10-yhct').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        ordering: false,
        responsive: false,
        autoWidth: false,
        ajax: {
            url: $('#ULocal').val() + 'icd10_yhct/getData/',
            type: 'POST',
            data: function (data) { data.searchText = $('#icd10-yhct-search').val().trim(); }
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
        createdRow: function (row, data) {
            if (data.ma_dung_chung_cha) $(row).addClass('clinical-row');
        },
        columns: [
            { data: 'ma_dung_chung', width: '9%', className: 'text-center code-cell' },
            { data: 'ma_icd10', width: '8%', defaultContent: '', className: 'text-center code-cell' },
            { data: 'benh_danh_yhct', width: '15%', defaultContent: '', render: compact },
            { data: 'ma_u', width: '11%', defaultContent: '', className: 'text-center code-cell' },
            { data: 'the_lam_sang', width: '20%', defaultContent: '', render: compact },
            { data: 'ma_hoa', width: '13%', defaultContent: '', className: 'text-center code-cell' },
            { data: 'ten_benh_y_hoc_hien_dai', width: '18%', defaultContent: '', render: compact },
            { data: 'is_active', width: '6%', className: 'text-center', render: function (value) {
                return Number(value) === 1
                    ? '<i class="fa fa-check-circle text-success catalog-status" title="Hiện hành"></i>'
                    : '<i class="fa fa-ban text-danger catalog-status" title="Ngừng dùng"></i>';
            }}
        ],
        language: {
            processing: 'Đang tải dữ liệu...', lengthMenu: 'Hiển thị _MENU_ dòng',
            zeroRecords: 'Không tìm thấy mã ICD-10 YHCT',
            info: 'Hiển thị _START_ đến _END_ trong _TOTAL_ mã', infoEmpty: 'Không có dữ liệu',
            infoFiltered: '(lọc từ _MAX_ mã)',
            paginate: { first: 'Đầu', last: 'Cuối', next: 'Sau', previous: 'Trước' }
        }
    });
    function reload() { table.ajax.reload(); }
    $('#btn-search-icd10-yhct').on('click', reload);
    $('#btn-reset-icd10-yhct').on('click', function () {
        $('#icd10-yhct-search').val('').focus(); reload();
    });
    $('#icd10-yhct-search').on('keydown', function (event) {
        if (event.key === 'Enter' || event.which === 13) { event.preventDefault(); reload(); }
    });
})(jQuery);
