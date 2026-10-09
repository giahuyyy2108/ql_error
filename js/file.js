(function ($) {
    'use strict';

    var baseUrl = $('#ULocal').val();
    var downloadBaseUrl = new URL(baseUrl, window.location.href).pathname;
    var csrfToken = $('#xml-file-csrf').val();
    var fileIdsToDelete = [];
    var selectedFileIds = {};
    var decodedFiles = [];
    var xmlDisplaySettings = { show_errors: true, show_valid: true };
    var revalidationInProgress = false;

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    function formatBytes(bytes) {
        var value = Number(bytes) || 0;
        if (value < 1024) return value + ' B';
        if (value < 1048576) return (value / 1024).toFixed(1) + ' KB';
        return (value / 1048576).toFixed(1) + ' MB';
    }

    function showUploadMessage(message, success) {
        $('#upload-message')
            .removeClass('alert-success alert-danger')
            .addClass(success ? 'alert-success' : 'alert-danger')
            .text(message)
            .show();
    }

    function updateSelectionControls() {
        var count = Object.keys(selectedFileIds).length;
        $('#btn-delete-selected').prop('disabled', count === 0).text(
            count ? 'Xóa đã chọn (' + count + ')' : 'Xóa đã chọn'
        );
        var pageCheckboxes = $('#datatable-file .file-select');
        var checkedOnPage = pageCheckboxes.filter(':checked').length;
        $('#select-all-files')
            .prop('checked', pageCheckboxes.length > 0 && checkedOnPage === pageCheckboxes.length)
            .prop('indeterminate', checkedOnPage > 0 && checkedOnPage < pageCheckboxes.length);
    }

    function errorsAtPath(errors, path) {
        return (errors || []).filter(function (error) { return error.path === path; });
    }

    function validFieldsAtPath(validFields, path) {
        return (validFields || []).filter(function (item) { return item.path === path; });
    }

    function validationCountsBelowPath(errors, path) {
        var counts = { errors: 0, warnings: 0 };
        (errors || []).forEach(function (error) {
            var errorPath = error.path || '';
            var belongsToNode = path === '' || errorPath === path
                || errorPath.indexOf(path + '.') === 0
                || errorPath.indexOf(path + '[') === 0;
            if (!belongsToNode) return;
            if (error.severity === 'warning') counts.warnings++;
            else counts.errors++;
        });
        return counts;
    }

    function appendErrorNotes(container, errors) {
        errors.forEach(function (error) {
            var note = error.message;
            var errorValue = error.value;
            if (errorValue === null || errorValue === undefined || String(errorValue) === '') {
                errorValue = '(trống)';
            } else if (typeof errorValue === 'object') {
                errorValue = JSON.stringify(errorValue);
            } else {
                errorValue = String(errorValue);
            }
            note += ' | Giá trị lỗi: ' + errorValue;
            if (error.substring !== undefined) note += ' (Giá trị cắt: ' + error.substring + ')';
            $('<div>')
                .addClass('json-field-error' + (error.severity === 'warning' ? ' json-field-warning' : ''))
                .text(error.display_name + ': ' + note)
                .appendTo(container);
        });
    }

    function appendValidNotes(container, validFields) {
        validFields.forEach(function (item) {
            $('<div>')
                .addClass('json-field-valid')
                .text(item.display_name + ': ' + (item.message || 'Hợp lệ') + ' (' + item.rule_type + ')')
                .appendTo(container);
        });
    }

    function appendJsonNode(container, key, value, depth, errors, validFields, path) {
        var isArray = Array.isArray(value);
        var isObject = value !== null && typeof value === 'object';
        var fieldErrors = path ? errorsAtPath(errors, path) : [];
        var fieldValids = path ? validFieldsAtPath(validFields, path) : [];

        if (isObject) {
            var branchCounts = validationCountsBelowPath(errors, path);
            var details = $('<details>').prop('open', depth < 2 || branchCounts.errors > 0 || branchCounts.warnings > 0);
            var count = isArray ? value.length : Object.keys(value).length;
            var summary = $('<summary>').appendTo(details);
            $('<span>').text(key + (isArray ? ' [' + count + ']' : ' {' + count + '}')).appendTo(summary);
            if (branchCounts.errors > 0) {
                $('<span>').addClass('json-error-count').text(branchCounts.errors + ' lỗi').appendTo(summary);
            }
            if (branchCounts.warnings > 0) {
                $('<span>').addClass('json-warning-count').text(branchCounts.warnings + ' cảnh báo').appendTo(summary);
            }
            if (branchCounts.errors > 0) summary.addClass('json-error-key');
            else if (branchCounts.warnings > 0) summary.addClass('json-warning-key');
            else if (fieldValids.length) summary.addClass('json-valid-key');

            if (isArray) {
                value.forEach(function (item, index) {
                    var childPath = path + '[' + index + ']';
                    appendJsonNode(details, '[' + index + ']', item, depth + 1, errors, validFields, childPath);
                });
            } else {
                Object.keys(value).forEach(function (childKey) {
                    var childPath = path ? path + '.' + childKey : childKey;
                    appendJsonNode(details, childKey, value[childKey], depth + 1, errors, validFields, childPath);
                });
            }
            container.append(details);
            appendErrorNotes(details, fieldErrors);
            appendValidNotes(details, fieldValids);
            return;
        }

        var row = $('<div>').addClass('json-value');
        var keyStateClass = fieldErrors.length ? ' json-error-key' : (fieldValids.length ? ' json-valid-key' : '');
        $('<span>').addClass('json-key' + keyStateClass).text(key + ': ').appendTo(row);
        var valueClass = value === null ? 'json-null' : (typeof value === 'number' ? 'json-number' : 'json-string');
        var displayValue = value === null ? 'null' : String(value);
        $('<span>').addClass(valueClass).text(displayValue).appendTo(row);
        container.append(row);
        appendErrorNotes(container, fieldErrors);
        appendValidNotes(container, fieldValids);
    }

    function collectDecodedFiles(decoded, validation) {
        var result = [];
        var hoSoList = decoded && decoded.DANHSACHHOSO && decoded.DANHSACHHOSO.HOSO;
        if (!Array.isArray(hoSoList)) return result;

        hoSoList.forEach(function (hoSo, hoSoIndex) {
            var files = hoSo && hoSo.FILEHOSO;
            if (!Array.isArray(files)) return;
            files.forEach(function (file, fileIndex) {
                var validationResult = null;
                (validation || []).some(function (item) {
                    if (Number(item.hoso_index) === hoSoIndex && Number(item.file_index) === fileIndex) {
                        validationResult = item;
                        return true;
                    }
                    return false;
                });
                var errors = validationResult && xmlDisplaySettings.show_errors ? validationResult.errors : [];
                var validFields = validationResult && xmlDisplaySettings.show_valid
                    ? (validationResult.valid_fields || []) : [];
                result.push({
                    label: 'Hồ sơ ' + (hoSoIndex + 1) + ' - ' + (file.LOAIHOSO || ('File ' + (fileIndex + 1)))
                        + (xmlDisplaySettings.show_errors && validationResult && validationResult.error_count ? ' (' + validationResult.error_count + ' lỗi)' : '')
                        + (xmlDisplaySettings.show_errors && validationResult && validationResult.warning_count ? ' (' + validationResult.warning_count + ' cảnh báo)' : '')
                        + (xmlDisplaySettings.show_valid && validationResult && validationResult.valid_count ? ' (' + validationResult.valid_count + ' hợp lệ)' : '')
                        + ((!validationResult || (!validationResult.error_count && !validationResult.warning_count
                            && !validationResult.valid_count)) ? ' (Hợp lệ)' : ''),
                    type: file.LOAIHOSO || '',
                    content: file.NOIDUNGFILE,
                    errors: errors,
                    validFields: validFields
                });
            });
        });
        return result;
    }

    function renderSelectedDecodedFile() {
        var index = Number($('#decoded-file-select').val());
        var tree = $('#decoded-json-tree').empty();
        if (!decodedFiles[index]) {
            tree.text('Không có nội dung đã giải mã.');
            return;
        }
        var selected = decodedFiles[index];
        var summary = $('#validation-summary').empty().toggle(xmlDisplaySettings.show_errors);
        var validSummary = $('#validation-valid-summary').empty().hide();
        if (xmlDisplaySettings.show_errors && selected.errors.length) {
            var errorCount = selected.errors.filter(function (error) { return error.severity !== 'warning'; }).length;
            var warningCount = selected.errors.length - errorCount;
            summary.removeClass('alert-success alert-warning alert-danger')
                .addClass(errorCount ? 'alert-danger' : 'alert-warning');
            $('<strong>').text('Có ' + errorCount + ' lỗi, ' + warningCount + ' cảnh báo').appendTo(summary);
        } else if (xmlDisplaySettings.show_errors) {
            summary.hide();
        }
        if (xmlDisplaySettings.show_valid && selected.validFields.length) {
            validSummary.show();
            $('<strong>')
                .addClass('validation-valid-title')
                .text('Có ' + selected.validFields.length + ' lượt kiểm tra hợp lệ')
                .appendTo(validSummary);
        }
        appendJsonNode(
            tree,
            selected.type || 'NOIDUNGFILE',
            selected.content,
            0,
            selected.errors,
            selected.validFields,
            ''
        );
    }

    var table = $('#datatable-file').DataTable({
        ajax: {
            url: baseUrl + 'file/getData/',
            type: 'POST',
            error: function (xhr, textStatus) {
                if (textStatus === 'abort' || revalidationInProgress) return;
                alert('Không thể tải danh sách file.');
            }
        },
        paging: false,
        scrollY: '60vh',
        scrollX: false,
        scrollCollapse: true,
        deferRender: true,
        order: [[6, 'desc']],
        responsive: false,
        autoWidth: false,
        "lengthChange": false,
        columns: [
            {
                data: 'id',
                orderable: false,
                searchable: false,
                className: 'text-center',
                width: '3%',
                render: function (data, type) {
                    if (type !== 'display') return data;
                    return '<input type="checkbox" class="file-select" value="' + Number(data) + '"'
                        + (selectedFileIds[data] ? ' checked' : '') + '>';
                }
            },
            {
                data: null,
                orderable: false,
                className: 'text-center',
                width: '6%',
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            {
                data: 'name',
                className: 'file-name-cell',
                width: '24%',
                render: function (data, type) {
                    if (type !== 'display') return data;
                    return $('<span>')
                        .addClass('file-name-ellipsis')
                        .attr('title', data || '')
                        .text(data || '')
                        .prop('outerHTML');
                }
            },
            { data: 'ma_lk' },
            {
                data: 'size',
                className: 'text-right',
                render: function (data, type) {
                    return type === 'display' ? formatBytes(data) : data;
                }
            },
            {
                data: 'status',
                className: 'text-center file-status-cell',
                render: function (data, type) {
                    if (type !== 'display') return data;
                    if (data === 'pending_revalidation' || data === 'revalidating') {
                        return '<span class="file-status file-status-processing">' +
                            '<i class="fa fa-refresh fa-spin" aria-hidden="true"></i>' +
                            '<span>Đang xử lý</span></span>';
                    }
                    return data === 'failed'
                        ? '<span class="file-status file-status-failed">' +
                            '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i>' +
                            '<span>Có lỗi</span></span>'
                        : '<span class="file-status file-status-success">' +
                            '<i class="fa fa-check-circle" aria-hidden="true"></i>' +
                            '<span>Thành công</span></span>';
                }
            },
            {
                data: 'timestamp',
                render: function (data, type, row) {
                    return type === 'display' || type === 'filter' ? row.modified : data;
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function (data, type, row) {
                    var name = escapeHtml(row.name);
                    var malk = escapeHtml(row.ma_lk);
                    return '<button type="button" class="btn btn-sm btn-info btn-file-action btn-view-file" data-id="' + row.id + '" data-malk="' + malk + '" title="Xem file" aria-label="Xem file">' +
                        '<i class="fa fa-eye"></i></button>' +
                        '<button type="button" class="btn btn-sm btn-danger btn-file-action btn-delete-file" data-id="' + row.id + '" data-name="' + name + '" title="Xóa file" aria-label="Xóa file">' +
                        '<i class="fa fa-trash"></i></button>';
                }
            }
        ],
        drawCallback: function () {
            updateSelectionControls();
        },
        language: {
            search: 'Tìm kiếm:',
            lengthMenu: 'Hiển thị _MENU_ dòng',
            zeroRecords: 'Không có file XML',
            info: 'Hiển thị _START_ đến _END_ trong _TOTAL_ file',
            infoEmpty: 'Không có dữ liệu',
            infoFiltered: '(lọc từ _MAX_ file)',
            paginate: { first: 'Đầu', last: 'Cuối', next: 'Sau', previous: 'Trước' }
        }
    });

    function searchFiles() {
        table.search($.trim($('#file-search').val())).draw();
    }

    $('#btn-search-file').on('click', searchFiles);

    $('#btn-export-file-excel').on('click', function () {
        var button = $(this).prop('disabled', true);
        $.ajax({
            url: baseUrl + 'file/getErrorReport/',
            type: 'GET',
            dataType: 'json'
        }).done(function (response) {
            if (!response.success) {
                alert(response.message || 'Không thể tải dữ liệu báo cáo lỗi.');
                return;
            }

            var exportTable = $('<table>').css('display', 'none').appendTo(document.body);
            var exportData = (response.data || []).map(function (row, index) {
                return [index + 1, row.ma_lk, row.xml, row.field, row.error, row.notes, row.date, row.status];
            });
            var exportDataTable = exportTable.DataTable({
                data: exportData,
                paging: false,
                searching: false,
                ordering: false,
                dom: 'B',
                columns: [
                    { title: 'STT' },
                    { title: 'MÃ LIÊN KẾT' },
                    { title: 'XML' },
                    { title: 'CHỖ LỖI' },
                    { title: 'LỖI' },
                    { title: 'GHI CHÚ' },
                    { title: 'NGÀY' },
                    { title: 'TÌNH TRẠNG' }
                ],
                buttons: [{
                    extend: 'excelHtml5',
                    title: null,
                    filename: 'danh-sach-loi',
                    sheetName: 'Danh sách lỗi',
                    customizeData: function (data) {
                        data.body.forEach(function (row) {
                            // Force long MA_LK values to text so Excel keeps every digit.
                            row[1] = '\u200B' + row[1];
                        });
                    }
                }]
            });
            exportDataTable.button('.buttons-excel').trigger();
            window.setTimeout(function () {
                exportDataTable.destroy();
                exportTable.remove();
            }, 1500);
        }).fail(function () {
            alert('Không thể xuất báo cáo Excel.');
        }).always(function () {
            button.prop('disabled', false);
        });
    });

    $('#btn-reset-file-search').on('click', function () {
        $('#file-search').val('').focus();
        table.search('').draw();
    });

    $('#file-search').on('keydown', function (event) {
        if (event.key === 'Enter' || event.which === 13) {
            event.preventDefault();
            searchFiles();
        }
    });

    $('#datatable-file').on('change', '.file-select', function () {
        var id = String($(this).val());
        if (this.checked) selectedFileIds[id] = true;
        else delete selectedFileIds[id];
        updateSelectionControls();
    });

    $('#select-all-files').on('change', function () {
        var checked = this.checked;
        table.rows({ page: 'current', search: 'applied' }).data().each(function (row) {
            var id = String(row.id);
            if (checked) selectedFileIds[id] = true;
            else delete selectedFileIds[id];
        });
        table.rows({ page: 'current' }).invalidate().draw(false);
    });

    $('#btn-delete-selected').on('click', function () {
        fileIdsToDelete = Object.keys(selectedFileIds).map(Number);
        if (!fileIdsToDelete.length) return;
        $('#delete-file-name').text(fileIdsToDelete.length + ' file đã chọn');
        $('#modal-delete-xml').modal('show');
    });

    $('#btn-revalidate-all').on('click', function () {
        if (revalidationInProgress) return;
        revalidationInProgress = true;
        var button = $(this).prop('disabled', true);
        var originalHtml = button.html();

        function finish(message) {
            revalidationInProgress = false;
            button.prop('disabled', false).html(originalHtml);
            table.ajax.reload(null, false);
            alert(message);
        }

        function processNextBatch(processed, errorCount) {
            button.html('<i class="fa fa-refresh fa-spin"></i> Đang quét... (' + processed + ')');
            $.ajax({
                url: baseUrl + 'file/processRevalidationBatch/',
                type: 'POST',
                dataType: 'json',
                data: { csrf_token: csrfToken }
            }).done(function (response) {
                if (!response.success) {
                    finish(response.message || 'Không thể quét lại file.');
                    return;
                }
                processed += Number(response.processed) || 0;
                errorCount += response.errors && response.errors.length ? response.errors.length : 0;
                if (Number(response.remaining) > 0) {
                    window.setTimeout(function () { processNextBatch(processed, errorCount); }, 100);
                    return;
                }
                var message = 'Đã quét lại ' + processed + ' file.';
                if (errorCount) {
                    message += '\nCó ' + errorCount + ' file không thể xử lý.';
                }
                finish(message);
            }).fail(function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message : 'Không thể xử lý hàng đợi quét lại.';
                finish(message);
            });
        }

        $.ajax({
            url: baseUrl + 'file/revalidateAll/',
            type: 'POST',
            dataType: 'json',
            data: { csrf_token: csrfToken }
        }).done(function (response) {
            if (!response.success) {
                finish(response.message || 'Không thể tạo hàng đợi quét lại.');
                return;
            }
            processNextBatch(0, 0);
        }).fail(function (xhr) {
            var message = xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message : 'Không thể tạo hàng đợi quét lại.';
            finish(message);
        });
    });

    // Files imported by the background watcher do not pass through this page,
    // so periodically refresh the table while the tab is visible.
    setInterval(function () {
        if (!document.hidden && !revalidationInProgress) {
            table.ajax.reload(null, false);
        }
    }, 5000);

    $('#btn-open-upload').on('click', function () {
        $('#form-upload-xml')[0].reset();
        $('#upload-message').hide();
        $('#modal-upload-xml').modal('show');
    });

    $('#form-upload-xml').on('submit', function (event) {
        event.preventDefault();
        var input = $('#xmlFile')[0];
        if (!input.files.length || !/\.xml$/i.test(input.files[0].name)) {
            showUploadMessage('Vui lòng chọn đúng file có đuôi .xml.', false);
            return;
        }

        var formData = new FormData(this);
        formData.append('csrf_token', csrfToken);
        $('#btn-upload-xml').prop('disabled', true);

        $.ajax({
            url: baseUrl + 'file/upload/',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function (response) {
            showUploadMessage(response.message, response.success);
            if (response.success) {
                table.ajax.reload(null, false);
                $('#form-upload-xml')[0].reset();
                setTimeout(function () { $('#modal-upload-xml').modal('hide'); }, 700);
            }
        }).fail(function () {
            showUploadMessage('Có lỗi xảy ra khi tải file lên.', false);
        }).always(function () {
            $('#btn-upload-xml').prop('disabled', false);
        });
    });

    $('#datatable-file').on('click', '.btn-view-file', function () {
        var id = $(this).attr('data-id');
        var name = $(this).attr('data-malk');
        $('#view-file-name').text(name);
        $('#btn-download-view-file').attr('href', downloadBaseUrl + 'file/download/?id=' + Number(id));
        $('#decoded-file-select').empty().prop('disabled', true);
        $('#validation-summary').hide().empty();
        $('#validation-valid-summary').hide().empty();
        $('#decoded-json-tree').text('Đang tải...');
        $('#modal-view-xml').modal('show');

        $.ajax({
            url: baseUrl + 'file/getContent/',
            type: 'POST',
            dataType: 'json',
            data: { id: id }
        }).done(function (response) {
            if (!response.success) {
                $('#decoded-json-tree').text(response.message);
                return;
            }

            xmlDisplaySettings = $.extend(
                { show_errors: true, show_valid: true },
                response.display_settings || {}
            );
            decodedFiles = collectDecodedFiles(response.decoded, response.validation);
            var select = $('#decoded-file-select').empty();
            decodedFiles.forEach(function (file, index) {
                $('<option>').val(index).text(file.label).appendTo(select);
            });
            select.prop('disabled', decodedFiles.length === 0);
            renderSelectedDecodedFile();
        }).fail(function (xhr) {
            var message = 'Không thể đọc nội dung đã giải mã.';
            if (xhr.status === 401) {
                message = 'Phiên đăng nhập đã hết hạn. Vui lòng tải lại trang và đăng nhập lại.';
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            } else if (/^\s*<!doctype|^\s*<html/i.test(xhr.responseText || '')) {
                message = 'Phiên đăng nhập đã hết hạn. Vui lòng tải lại trang và đăng nhập lại.';
            }
            $('#decoded-json-tree').text(message);
        });
    });

    $('#decoded-file-select').on('change', renderSelectedDecodedFile);

    $('#datatable-file').on('click', '.btn-delete-file', function () {
        fileIdsToDelete = [Number($(this).attr('data-id'))];
        $('#delete-file-name').text($(this).attr('data-name'));
        $('#modal-delete-xml').modal('show');
    });

    $('#btn-confirm-delete').on('click', function () {
        if (!fileIdsToDelete.length) return;
        var button = $(this).prop('disabled', true);

        $.ajax({
            url: baseUrl + 'file/deleteMany/',
            type: 'POST',
            dataType: 'json',
            data: { ids: fileIdsToDelete, csrf_token: csrfToken }
        }).done(function (response) {
            if (response.success) {
                $('#modal-delete-xml').modal('hide');
                fileIdsToDelete.forEach(function (id) { delete selectedFileIds[String(id)]; });
                fileIdsToDelete = [];
                table.ajax.reload(null, false);
                updateSelectionControls();
            } else {
                alert(response.message);
            }
        }).fail(function () {
            alert('Có lỗi xảy ra khi xóa file.');
        }).always(function () {
            button.prop('disabled', false);
        });
    });
})(jQuery);
