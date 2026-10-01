(function ($) {
    'use strict';
    var baseUrl = $('#ULocal').val();
    var csrf = $('#api-validation-csrf').val();
    var deleting = null;

    function can(id) { var input = $(id); return !input.length || input.val() === 'true'; }
    function rowData(button) { var row = $(button).closest('tr'); if (row.hasClass('child')) row = row.prev(); return table.row(row).data(); }
    function message(text, ok) { $('#api-config-message').removeClass('alert-success alert-danger').addClass(ok ? 'alert-success' : 'alert-danger').text(text).show(); }
    function reset() {
        $('#form-api-config')[0].reset(); $('#api-config-id').val(''); $('#api-timeout').val(10);
        $('#api-headers').val('{}'); $('#api-active').prop('checked', true); $('#api-config-message').hide();
    }

    var table = $('#datatable-api-config').DataTable({
        ajax: { url: baseUrl + 'apiValidation/getData/', type: 'POST' },
        pageLength: Number($('#pageLength').val()) || 25,
        order: [[1, 'desc']], responsive: true, autoWidth: false,
        columns: [
            { data: null, orderable: false, className: 'text-center', render: function (d, t, r, m) { return m.row + m.settings._iDisplayStart + 1; } },
            { data: 'id', className: 'text-center' },
            { data: 'name' }, { data: 'endpoint' }, { data: 'method', className: 'text-center' },
            { data: 'timeout_seconds', className: 'text-center', render: function (v) { return v + ' giây'; } },
            { data: 'response_field' },
            { data: 'is_active', className: 'text-center', render: function (v) { return Number(v) === 1 ? '<span class="label label-success">Hoạt động</span>' : '<span class="label label-default">Tạm dừng</span>'; } },
            { data: null, orderable: false, searchable: false, className: 'text-center', render: function () {
                var html = '';
                if (can('#role-apiValidation-test')) html += '<button class="btn btn-sm btn-success btn-test-api" title="Test API"><i class="fa fa-play"></i></button> ';
                if (can('#role-apiValidation-update')) html += '<button class="btn btn-sm btn-info btn-edit-api" title="Sửa"><i class="fa fa-pencil"></i></button> ';
                if (can('#role-apiValidation-delete')) html += '<button class="btn btn-sm btn-danger btn-delete-api" title="Xóa"><i class="fa fa-trash"></i></button>';
                return html;
            }}
        ],
        language: { search: 'Tìm kiếm:', lengthMenu: 'Hiển thị _MENU_ dòng', zeroRecords: 'Không có cấu hình API', info: 'Hiển thị _START_ đến _END_ trong _TOTAL_ cấu hình', infoEmpty: 'Không có dữ liệu', paginate: { next: 'Sau', previous: 'Trước' } },
        initComplete: function () { if (!can('#role-apiValidation-save')) $('#btn-add-api-config').hide(); }
    });

    $('#btn-add-api-config').on('click', function () { reset(); $('#modal-api-config .modal-title').text('Thêm cấu hình API'); $('#modal-api-config').modal('show'); });
    $('#datatable-api-config').on('click', '.btn-test-api', function () {
        var item = rowData(this);
        $('#test-api-id').val(item.id); $('#test-api-name').text(item.name);
        $('#test-path-parameters, #test-query-parameters, #test-body-parameters').val('{}');
        $('#test-api-message').hide(); $('#test-api-response').text('');
        $('#modal-test-api').modal('show');
    });
    $('#btn-run-api-test').on('click', function () {
        var button = $(this).prop('disabled', true);
        $('#test-api-message').hide(); $('#test-api-response').text('Đang gọi API...');
        $.ajax({ url: baseUrl + 'apiValidation/test/', type: 'POST', dataType: 'json', data: {
            id: $('#test-api-id').val(), path_parameters: $('#test-path-parameters').val(),
            query_parameters: $('#test-query-parameters').val(), body_parameters: $('#test-body-parameters').val(), csrf_token: csrf
        }}).done(function (response) {
            $('#test-api-message').removeClass('alert-success alert-danger').addClass(response.success ? 'alert-success' : 'alert-danger').text(response.message).show();
            $('#test-api-response').text(response.success ? JSON.stringify(response.response, null, 2) : '');
        }).fail(function () {
            $('#test-api-message').removeClass('alert-success').addClass('alert-danger').text('Không thể thực hiện test API.').show();
            $('#test-api-response').text('');
        }).always(function () { button.prop('disabled', false); });
    });
    $('#datatable-api-config').on('click', '.btn-edit-api', function () {
        var item = rowData(this); reset(); $('#api-config-id').val(item.id); $('#api-name').val(item.name);
        $('#api-endpoint').val(item.endpoint); $('#api-method').val(item.method); $('#api-timeout').val(item.timeout_seconds);
        $('#api-response-field').val(item.response_field); $('#api-headers').val(item.headers || '{}');
        $('#api-active').prop('checked', Number(item.is_active) === 1); $('#modal-api-config .modal-title').text('Sửa cấu hình API'); $('#modal-api-config').modal('show');
    });
    $('#form-api-config').on('submit', function (event) {
        event.preventDefault(); var id = $('#api-config-id').val(); $('#btn-save-api-config').prop('disabled', true);
        $.ajax({ url: baseUrl + 'apiValidation/' + (id ? 'update/' : 'save/'), type: 'POST', dataType: 'json', data: {
            id: id, name: $('#api-name').val(), endpoint: $('#api-endpoint').val(), method: $('#api-method').val(),
            timeout_seconds: $('#api-timeout').val(), response_field: $('#api-response-field').val(), headers: $('#api-headers').val(),
            is_active: $('#api-active').is(':checked') ? '1' : '0', csrf_token: csrf
        }}).done(function (response) { message(response.message, response.success); if (response.success) { table.ajax.reload(null, false); setTimeout(function () { $('#modal-api-config').modal('hide'); }, 600); } })
          .fail(function () { message('Có lỗi xảy ra khi lưu cấu hình API.', false); }).always(function () { $('#btn-save-api-config').prop('disabled', false); });
    });
    $('#datatable-api-config').on('click', '.btn-delete-api', function () { deleting = rowData(this); $('#delete-api-config-name').text(deleting.name); $('#modal-delete-api-config').modal('show'); });
    $('#btn-confirm-delete-api-config').on('click', function () {
        if (!deleting) return; var button = $(this).prop('disabled', true);
        $.ajax({ url: baseUrl + 'apiValidation/delete/', type: 'POST', dataType: 'json', data: { id: deleting.id, csrf_token: csrf } })
          .done(function (response) { if (response.success) { $('#modal-delete-api-config').modal('hide'); table.ajax.reload(null, false); deleting = null; } else alert(response.message); })
          .fail(function () { alert('Có lỗi xảy ra khi xóa cấu hình API.'); }).always(function () { button.prop('disabled', false); });
    });
})(jQuery);
