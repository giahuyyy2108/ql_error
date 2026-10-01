(function ($) {
    'use strict';

    var baseUrl = $('#ULocal').val();
    var csrfToken = $('#rule-catalog-csrf').val();
    var itemToDelete = null;

    function can(roleId) {
        var field = $(roleId);
        return field.length === 0 || field.val() === 'true';
    }

    function resetForm() {
        $('#form-rule-type')[0].reset();
        $('#rule-type-id').val('');
        $('#catalog-active').prop('checked', true);
        $('#rule-type-message').hide();
    }

    function showMessage(message, success) {
        $('#rule-type-message')
            .removeClass('alert-success alert-danger')
            .addClass(success ? 'alert-success' : 'alert-danger')
            .text(message)
            .show();
    }

    function rowData(button) {
        var row = $(button).closest('tr');
        if (row.hasClass('child')) row = row.prev();
        return table.row(row).data();
    }

    function compactText(value, type) {
        var text = value == null ? '' : String(value);
        if (type !== 'display') return text;
        var shortText = text.length > 55 ? text.substring(0, 55).trim() + '…' : text;
        return $('<span>')
            .addClass('rule-catalog-ellipsis')
            .attr('title', text)
            .text(shortText)
            .prop('outerHTML');
    }

    var table = $('#datatable-rule-types').DataTable({
        ajax: { url: baseUrl + 'rule/getData/', type: 'POST' },
        pageLength: Number($('#pageLength').val()) || 25,
        order: [[1, 'asc']],
        responsive: false,
        autoWidth: false,
        columns: [
            {
                data: null,
                orderable: false,
                className: 'text-center',
                width: '4%',
                render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; }
            },
            { data: 'code', width: '12%', render: compactText },
            { data: 'display_name', width: '14%', render: compactText },
            { data: 'description', width: '24%', render: compactText },
            { data: 'value_hint', width: '19%', defaultContent: '', render: compactText },
            {
                data: 'requires_value',
                width: '8%',
                className: 'text-center',
                render: function (value) { return Number(value) === 1 ? 'Có' : 'Không'; }
            },
            {
                data: 'is_active',
                width: '9%',
                className: 'text-center',
                render: function (value) {
                    return Number(value) === 1
                        ? '<i class="fa fa-check-circle text-success rule-catalog-status" title="Hoạt động" aria-label="Hoạt động"></i>'
                        : '<i class="fa fa-pause-circle text-muted rule-catalog-status" title="Tạm dừng" aria-label="Tạm dừng"></i>';
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                width: '10%',
                className: 'rule-catalog-actions',
                render: function () {
                    var html = '';
                    if (can('#role-rule-update')) html += '<button class="btn btn-sm btn-info btn-edit-rule-type" title="Sửa"><i class="fa fa-pencil"></i></button> ';
                    if (can('#role-rule-delete')) html += '<button class="btn btn-sm btn-danger btn-delete-rule-type" title="Xóa"><i class="fa fa-trash"></i></button>';
                    return html;
                }
            }
        ],
        language: {
            search: 'Tìm kiếm:', lengthMenu: 'Hiển thị _MENU_ dòng', zeroRecords: 'Không có loại rule',
            info: 'Hiển thị _START_ đến _END_ trong _TOTAL_ loại rule', infoEmpty: 'Không có dữ liệu',
            infoFiltered: '(lọc từ _MAX_ loại rule)',
            paginate: { first: 'Đầu', last: 'Cuối', next: 'Sau', previous: 'Trước' }
        },
        initComplete: function () {
            if (!can('#role-rule-save')) $('#btn-add-rule-type').hide();
        }
    });

    $('#btn-add-rule-type').on('click', function () {
        resetForm();
        $('#modal-rule-type .modal-title').text('Thêm loại rule');
        $('#modal-rule-type').modal('show');
    });

    $('#datatable-rule-types').on('click', '.btn-edit-rule-type', function () {
        var item = rowData(this);
        resetForm();
        $('#rule-type-id').val(item.id);
        $('#catalog-code').val(item.code);
        $('#catalog-display-name').val(item.display_name);
        $('#catalog-description').val(item.description);
        $('#catalog-value-hint').val(item.value_hint || '');
        $('#catalog-requires-value').prop('checked', Number(item.requires_value) === 1);
        $('#catalog-active').prop('checked', Number(item.is_active) === 1);
        $('#modal-rule-type .modal-title').text('Sửa loại rule');
        $('#modal-rule-type').modal('show');
    });

    $('#form-rule-type').on('submit', function (event) {
        event.preventDefault();
        var id = $('#rule-type-id').val();
        $('#btn-save-rule-type').prop('disabled', true);
        $.ajax({
            url: baseUrl + 'rule/' + (id ? 'update/' : 'save/'),
            type: 'POST',
            dataType: 'json',
            data: {
                id: id,
                code: $('#catalog-code').val(),
                display_name: $('#catalog-display-name').val(),
                description: $('#catalog-description').val(),
                value_hint: $('#catalog-value-hint').val(),
                requires_value: $('#catalog-requires-value').is(':checked') ? '1' : '0',
                is_active: $('#catalog-active').is(':checked') ? '1' : '0',
                csrf_token: csrfToken
            }
        }).done(function (response) {
            showMessage(response.message, response.success);
            if (response.success) {
                table.ajax.reload(null, false);
                setTimeout(function () { $('#modal-rule-type').modal('hide'); }, 600);
            }
        }).fail(function () { showMessage('Có lỗi xảy ra khi lưu loại rule.', false); })
          .always(function () { $('#btn-save-rule-type').prop('disabled', false); });
    });

    $('#datatable-rule-types').on('click', '.btn-delete-rule-type', function () {
        itemToDelete = rowData(this);
        $('#delete-rule-type-name').text(itemToDelete.code);
        $('#modal-delete-rule-type').modal('show');
    });

    $('#btn-confirm-delete-rule-type').on('click', function () {
        if (!itemToDelete) return;
        var button = $(this).prop('disabled', true);
        $.ajax({
            url: baseUrl + 'rule/delete/', type: 'POST', dataType: 'json',
            data: { id: itemToDelete.id, csrf_token: csrfToken }
        }).done(function (response) {
            if (response.success) {
                $('#modal-delete-rule-type').modal('hide');
                table.ajax.reload(null, false);
                itemToDelete = null;
            } else {
                alert(response.message);
            }
        }).fail(function () { alert('Có lỗi xảy ra khi xóa loại rule.'); })
          .always(function () { button.prop('disabled', false); });
    });
})(jQuery);
