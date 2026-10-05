(function ($) {
    'use strict';

    var baseUrl = $('#ULocal').val();
    var csrfToken = $('#hethong-csrf').val();

    var table = $('#datatable-hethong').DataTable({
        ajax: {
            url: baseUrl + 'hethong/getData/',
            type: 'POST',
            error: function () { alert('Không thể tải cấu hình hệ thống.'); }
        },
        paging: false,
        searching: false,
        info: false,
        ordering: false,
        autoWidth: false,
        columns: [
            {
                data: null,
                width: '10%',
                className: 'text-center',
                render: function (data, type, row, meta) { return meta.row + 1; }
            },
            { data: 'chucnang', width: '70%' },
            {
                data: 'trangthai',
                width: '20%',
                className: 'text-center',
                render: function (value, type, row) {
                    if (type !== 'display') return Number(value);
                    var enabled = Number(value) === 1;
                    return '<label class="system-switch" title="' + (enabled ? 'Đang bật' : 'Đang tắt') + '">'
                        + '<input type="checkbox" class="system-status-switch" data-id="' + Number(row.id) + '"'
                        + (enabled ? ' checked' : '') + ' aria-label="Bật hoặc tắt ' + $('<div>').text(row.chucnang).html() + '">'
                        + '<span class="system-switch-slider"></span></label>';
                }
            }
        ],
        language: { zeroRecords: 'Chưa có cấu hình hệ thống' }
    });

    $('#datatable-hethong').on('change', '.system-status-switch', function () {
        var toggle = $(this);
        var enabled = toggle.is(':checked');
        var row = table.row(toggle.closest('tr'));
        var data = row.data();
        toggle.prop('disabled', true);

        $.ajax({
            url: baseUrl + 'hethong/updateStatus/',
            type: 'POST',
            dataType: 'json',
            data: {
                id: toggle.attr('data-id'),
                trangthai: enabled ? '1' : '0',
                csrf_token: csrfToken
            }
        }).done(function (response) {
            if (!response.success) {
                toggle.prop('checked', !enabled);
                alert(response.message);
                return;
            }
            data.trangthai = enabled ? 1 : 0;
            row.data(data).invalidate().draw(false);
        }).fail(function () {
            toggle.prop('checked', !enabled);
            alert('Có lỗi xảy ra khi cập nhật cấu hình hệ thống.');
        }).always(function () {
            toggle.prop('disabled', false);
        });
    });
})(jQuery);
