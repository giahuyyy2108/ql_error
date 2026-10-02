(function ($) {
    'use strict';

    var baseUrl = $('#ULocal').val();
    var csrfToken = $('#rules-csrf').val();
    var ruleToDelete = null;
    var supportedRuleTypes = {};

    function loadRuleTypes() {
        $('#btn-save-rule').prop('disabled', true);
        $.ajax({
            url: baseUrl + 'rules/getRuleTypes/',
            type: 'POST',
            dataType: 'json'
        }).done(function (response) {
            var select = $('#rule-type');
            select.find('option:not(:first)').remove();
            supportedRuleTypes = {};
            (response.data || []).forEach(function (item) {
                supportedRuleTypes[item.code] = item;
                $('<option>').val(item.code).text(item.code + ' - ' + item.display_name).appendTo(select);
            });
        }).fail(function () {
            $('#rule-type-description').text('Không thể tải danh mục loại rule.');
        }).always(function () {
            $('#btn-save-rule').prop('disabled', false);
        });
    }

    function updateRuleTypeHelp() {
        var type = supportedRuleTypes[$('#rule-type').val()];
        var valueInput = $('#rule-value');
        if (!type) {
            $('#rule-type-description').text('');
            valueInput.prop('required', false).attr('placeholder', '');
            return;
        }
        $('#rule-type-description').text(type.description);
        valueInput
            .prop('required', Number(type.requires_value) === 1)
            .attr('placeholder', type.value_hint || 'Không cần giá trị');
    }

    function can(roleId) {
        var field = $(roleId);
        return field.length === 0 || field.val() === 'true';
    }

    function showMessage(message, success) {
        $('#rule-message')
            .removeClass('alert-success alert-danger')
            .addClass(success ? 'alert-success' : 'alert-danger')
            .text(message)
            .show();
    }

    function resetForm() {
        $('#form-rule')[0].reset();
        $('#rule-id').val('');
        $('#rule-active').prop('checked', true);
        $('#rule-message').hide();
    }

    function rowDataFromButton(button) {
        var rowElement = $(button).closest('tr');
        if (rowElement.hasClass('child')) rowElement = rowElement.prev();
        return table.row(rowElement).data();
    }

    function compactText(value, type) {
        var text = value == null ? '' : String(value);
        if (type !== 'display') return text;
        return $('<span>')
            .addClass('rule-cell-ellipsis')
            .attr('title', text)
            .text(text)
            .prop('outerHTML');
    }

    function compactErrorMessage(value, type) {
        var text = value == null ? '' : String(value);
        if (type !== 'display') return text;
        var shortText = text.length > 55 ? text.substring(0, 55).trim() + '…' : text;
        return $('<span>')
            .addClass('rule-cell-ellipsis')
            .attr('title', text)
            .text(shortText)
            .prop('outerHTML');
    }

    var table = $('#datatable-rules').DataTable({
        ajax: {
            url: baseUrl + 'rules/getData/',
            type: 'POST',
            error: function () { alert('Không thể tải danh sách rules.'); }
        },
        dom: 'lrtip',
        pageLength: Number($('#pageLength').val()) || 25,
        order: [[1, 'asc'], [2, 'asc']],
        responsive: false,
        autoWidth: false,
        columns: [
            { data: 'id', width: '7%', render: compactText },
            { data: 'file_type', width: '7%', render: compactText },
            { data: 'field_name', width: '11%', render: compactText },
            { data: 'display_name', width: '12%', render: compactText },
            { data: 'rule_type', width: '10%', render: compactText },
            {
                data: 'rule_value',
                width: '13%',
                defaultContent: '',
                render: compactText
            },
            { data: 'error_message', width: '18%', render: compactErrorMessage },
            {
                data: 'is_active',
                width: '9%',
                className: 'text-center',
                render: function (data) {
                    return Number(data) === 1
                        ? '<i class="fa fa-check-circle text-success rule-status-icon" title="Hoạt động" aria-label="Hoạt động"></i>'
                        : '<i class="fa fa-pause-circle text-muted rule-status-icon" title="Tạm dừng" aria-label="Tạm dừng"></i>';
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                width: '9%',
                className: 'rule-actions',
                render: function (data, type, row) {
                    var buttons = '';
                    if (can('#role-rules-update')) {
                        buttons += '<button type="button" class="btn btn-sm btn-info btn-edit-rule" title="Sửa"><i class="fa fa-pencil"></i></button> ';
                    }
                    if (can('#role-rules-delete')) {
                        buttons += '<button type="button" class="btn btn-sm btn-danger btn-delete-rule" title="Xóa"><i class="fa fa-trash"></i></button>';
                    }
                    return buttons;
                }
            }
        ],
        language: {
            search: 'Tìm kiếm:',
            lengthMenu: 'Hiển thị _MENU_ dòng',
            zeroRecords: 'Không có rule phù hợp',
            info: 'Hiển thị _START_ đến _END_ trong _TOTAL_ rule',
            infoEmpty: 'Không có dữ liệu',
            infoFiltered: '(lọc từ _MAX_ rule)',
            paginate: { first: 'Đầu', last: 'Cuối', next: 'Sau', previous: 'Trước' }
        },
        initComplete: function () {
            if (!can('#role-rules-save')) $('#btn-add-rule').hide();
        }
    });

    loadRuleTypes();
    $('#rule-type').on('change', updateRuleTypeHelp);

    $('#btn-search-rules').on('click', function () {
        table.search($('#rules-search').val().trim()).draw();
    });

    $('#btn-reset-rules').on('click', function () {
        $('#rules-search').val('');
        table.search('').draw();
        $('#rules-search').focus();
    });

    $('#rules-search').on('keydown', function (event) {
        if (event.key === 'Enter' || event.which === 13) {
            event.preventDefault();
            table.search($(this).val().trim()).draw();
        }
    });

    $('#btn-add-rule').on('click', function () {
        resetForm();
        $('#modal-rule .modal-title').text('Thêm rule');
        $('#modal-rule').modal('show');
    });

    $('#datatable-rules').on('click', '.btn-edit-rule', function () {
        if (!Object.keys(supportedRuleTypes).length) {
            alert('Danh mục loại rule chưa tải xong. Vui lòng thử lại.');
            return;
        }
        var row = rowDataFromButton(this);
        resetForm();
        $('#rule-id').val(row.id);
        $('#rule-file-type').val(row.file_type);
        $('#rule-field-name').val(row.field_name);
        $('#rule-display-name').val(row.display_name);
        $('#rule-type').val(row.rule_type);
        updateRuleTypeHelp();
        $('#rule-value').val(row.rule_value == null ? '' : row.rule_value);
        $('#rule-error-message').val(row.error_message);
        $('#rule-active').prop('checked', Number(row.is_active) === 1);
        $('#modal-rule .modal-title').text('Sửa rule');
        $('#modal-rule').modal('show');
    });

    $('#form-rule').on('submit', function (event) {
        event.preventDefault();
        var id = $('#rule-id').val();
        var endpoint = id ? 'update/' : 'save/';
        var payload = {
            id: id,
            file_type: $('#rule-file-type').val(),
            field_name: $('#rule-field-name').val(),
            display_name: $('#rule-display-name').val(),
            rule_type: $('#rule-type').val(),
            rule_value: $('#rule-value').val(),
            error_message: $('#rule-error-message').val(),
            is_active: $('#rule-active').is(':checked') ? '1' : '0',
            csrf_token: csrfToken
        };

        $('#btn-save-rule').prop('disabled', true);
        $.ajax({
            url: baseUrl + 'rules/' + endpoint,
            type: 'POST',
            dataType: 'json',
            data: payload
        }).done(function (response) {
            showMessage(response.message, response.success);
            if (response.success) {
                table.ajax.reload(null, false);
                setTimeout(function () { $('#modal-rule').modal('hide'); }, 600);
            }
        }).fail(function () {
            showMessage('Có lỗi xảy ra khi lưu rule.', false);
        }).always(function () {
            $('#btn-save-rule').prop('disabled', false);
        });
    });

    $('#datatable-rules').on('click', '.btn-delete-rule', function () {
        ruleToDelete = rowDataFromButton(this);
        $('#delete-rule-name').text(ruleToDelete.file_type + ' / ' + ruleToDelete.field_name);
        $('#modal-delete-rule').modal('show');
    });

    $('#btn-confirm-delete-rule').on('click', function () {
        if (!ruleToDelete) return;
        var button = $(this).prop('disabled', true);
        $.ajax({
            url: baseUrl + 'rules/delete/',
            type: 'POST',
            dataType: 'json',
            data: { id: ruleToDelete.id, csrf_token: csrfToken }
        }).done(function (response) {
            if (response.success) {
                $('#modal-delete-rule').modal('hide');
                table.ajax.reload(null, false);
                ruleToDelete = null;
            } else {
                alert(response.message);
            }
        }).fail(function () {
            alert('Có lỗi xảy ra khi xóa rule.');
        }).always(function () {
            button.prop('disabled', false);
        });
    });
})(jQuery);
