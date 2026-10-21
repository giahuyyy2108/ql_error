(function ($) {
    'use strict';

    var baseUrl = $('#ULocal').val();
    var downloadBaseUrl = new URL(baseUrl, window.location.href).pathname;
    var csrfToken = $('#xml-file-csrf').val();
    var fileIdsToDelete = [];
    var selectedFileIds = {};
    var decodedFiles = [];
    var currentDecodedContent = null;
    var currentValidationResults = [];
    var currentViewFileName = '';
    var xmlViewMode = 'errors';
    var xmlDisplaySettings = { show_errors: true, show_valid: true };
    var revalidationInProgress = false;
    var uploadInProgress = false;
    var getDataInProgress = false;
    var pendingDataReload = false;
    var fileSearchTerm = '';
    var fileSearchTimer = null;

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
            var copyValue = errorValue;
            if (copyValue === null || copyValue === undefined) copyValue = '';
            else if (typeof copyValue === 'object') copyValue = JSON.stringify(copyValue);
            else copyValue = String(copyValue);
            if (errorValue === null || errorValue === undefined || String(errorValue) === '') {
                errorValue = '(trống)';
            } else if (typeof errorValue === 'object') {
                errorValue = JSON.stringify(errorValue);
            } else {
                errorValue = String(errorValue);
            }
            var errorBox = $('<div>')
                .addClass('json-field-error' + (error.severity === 'warning' ? ' json-field-warning' : ''))
                .appendTo(container);
            $('<span>').text(error.display_name + ': ' + note + ' | Giá trị lỗi: ').appendTo(errorBox);
            $('<span role="button" tabindex="0" title="Bấm để sao chép giá trị lỗi">')
                .addClass('copy-error-value copy-error-trigger')
                .attr('data-copy-value', copyValue)
                .text(errorValue)
                .appendTo(errorBox);
            if (error.substring !== undefined) {
                $('<span>').text(' (Giá trị cắt: ' + error.substring + ')').appendTo(errorBox);
            }
        });
    }

    function appendValidNotes(container, validFields) {
        validFields.forEach(function (item) {
            var validValue = item.value;
            if (validValue === null || validValue === undefined || String(validValue) === '') {
                validValue = '(trống)';
            } else if (typeof validValue === 'object') {
                validValue = JSON.stringify(validValue);
            } else validValue = String(validValue);
            var validBox = $('<div>')
                .addClass('json-field-valid')
                .appendTo(container);
            $('<span>').text(item.display_name + ': ' + (item.message || 'Hợp lệ')
                + ' (' + item.rule_type + ') | Giá trị hợp lệ: ').appendTo(validBox);
            $('<span>')
                .text(validValue)
                .appendTo(validBox);
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

        var row = $('<div>').addClass('json-value' + (fieldErrors.length ? ' json-error-row' : ''));
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

    function numericValue(value) {
        if (value === null || value === undefined || value === '') return 0;
        var number = Number(String(value).replace(/,/g, ''));
        return isFinite(number) ? number : 0;
    }

    function firstFieldValue(data, fieldName) {
        if (!data || typeof data !== 'object') return null;
        if (Object.prototype.hasOwnProperty.call(data, fieldName)) return data[fieldName];
        var keys = Object.keys(data);
        for (var index = 0; index < keys.length; index++) {
            var found = firstFieldValue(data[keys[index]], fieldName);
            if (found !== null) return found;
        }
        return null;
    }

    function detailRows(data, result) {
        result = result || [];
        if (!data || typeof data !== 'object') return result;
        if (!Array.isArray(data) && Object.prototype.hasOwnProperty.call(data, 'THANH_TIEN_BV')) {
            result.push(data);
            return result;
        }
        Object.keys(data).forEach(function (key) { detailRows(data[key], result); });
        return result;
    }

    function emptyCostRow() {
        return { thuoc: 0, vtyt: 0, bv: 0, bh: 0, bhtt: 0, bncct: 0, bntt: 0 };
    }

    function addDetailCosts(target, rows, includeMedicine, includeSupplies) {
        rows.forEach(function (row) {
            var bv = numericValue(row.THANH_TIEN_BV);
            if (includeMedicine) target.thuoc += bv;
            if (includeSupplies && String(row.MA_VAT_TU || '').trim() !== '') target.vtyt += bv;
            target.bv += bv;
            target.bh += numericValue(row.THANH_TIEN_BH);
            target.bhtt += numericValue(row.T_BHTT);
            target.bncct += numericValue(row.T_BNCCT);
            target.bntt += numericValue(row.T_BNTT);
        });
    }

    function calculateCostReconciliation(decoded) {
        var totals = { XML1: emptyCostRow(), XML2: emptyCostRow(), XML3: emptyCostRow() };
        var hoSoList = decoded && decoded.DANHSACHHOSO && decoded.DANHSACHHOSO.HOSO;
        if (!Array.isArray(hoSoList)) return totals;
        hoSoList.forEach(function (hoSo) {
            var files = hoSo && hoSo.FILEHOSO;
            if (!Array.isArray(files)) return;
            files.forEach(function (file) {
                var type = String(file.LOAIHOSO || '').toUpperCase();
                var content = file.NOIDUNGFILE || {};
                if (type === 'XML1') {
                    totals.XML1.thuoc += numericValue(firstFieldValue(content, 'T_THUOC'));
                    totals.XML1.vtyt += numericValue(firstFieldValue(content, 'T_VTYT'));
                    totals.XML1.bv += numericValue(firstFieldValue(content, 'T_TONGCHI_BV'));
                    totals.XML1.bh += numericValue(firstFieldValue(content, 'T_TONGCHI_BH'));
                    totals.XML1.bhtt += numericValue(firstFieldValue(content, 'T_BHTT'));
                    totals.XML1.bncct += numericValue(firstFieldValue(content, 'T_BNCCT'));
                    totals.XML1.bntt += numericValue(firstFieldValue(content, 'T_BNTT'));
                } else if (type === 'XML2') {
                    addDetailCosts(totals.XML2, detailRows(content), true, false);
                } else if (type === 'XML3') {
                    addDetailCosts(totals.XML3, detailRows(content), false, true);
                }
            });
        });
        return totals;
    }

    function formatCost(value) {
        return numericValue(value).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function renderCostReconciliation(decoded) {
        var totals = calculateCostReconciliation(decoded);
        var keys = ['thuoc', 'vtyt', 'bv', 'bh', 'bhtt', 'bncct', 'bntt'];
        var headers = ['THUỐC', 'VTYT', 'BV', 'BH', 'BHTT', 'BNCCT', 'BNTT'];
        var detail = emptyCostRow();
        keys.forEach(function (key) { detail[key] = totals.XML2[key] + totals.XML3[key]; });
        var difference = emptyCostRow();
        keys.forEach(function (key) { difference[key] = totals.XML1[key] - detail[key]; });

        var tableElement = $('<table>').addClass('table table-bordered table-condensed');
        var headerRow = $('<tr>').append($('<th>').text('Nguồn'));
        headers.forEach(function (header) { headerRow.append($('<th>').text(header)); });
        tableElement.append($('<thead>').append(headerRow));
        var body = $('<tbody>');
        [
            { label: 'XML1', values: totals.XML1 },
            { label: 'XML2', values: totals.XML2 },
            { label: 'XML3', values: totals.XML3 },
            { label: 'LỆCH', values: difference, difference: true }
        ].forEach(function (row) {
            var element = $('<tr>').append($('<th>').text(row.label));
            keys.forEach(function (key) {
                var cell = $('<td>').text(formatCost(row.values[key]));
                if (row.difference) {
                    cell.addClass(Math.abs(row.values[key]) <= 1 ? 'cost-match' : 'cost-mismatch');
                }
                element.append(cell);
            });
            body.append(element);
        });
        tableElement.append(body);
        $('#cost-reconciliation-content').empty().append(tableElement).append(
            $('<small>').addClass('text-muted').text('Lệch = XML1 − (XML2 + XML3); chấp nhận sai số ±1 đồng.')
        );
    }

    function initializeCostReconciliationDrag() {
        var panel = document.querySelector('#modal-view-xml .cost-reconciliation-panel');
        var container = document.querySelector('#modal-view-xml .modal-content');
        if (!panel || !container) return;

        if (panel.parentNode !== container) container.appendChild(panel);
        if (panel.dataset.dragInitialized === '1') return;
        panel.dataset.dragInitialized = '1';

        var handle = panel.querySelector('h4');
        var drag = null;
        handle.addEventListener('pointerdown', function (event) {
            if (event.button !== undefined && event.button !== 0) return;
            var panelRect = panel.getBoundingClientRect();
            var containerRect = container.getBoundingClientRect();
            drag = {
                pointerId: event.pointerId,
                offsetX: event.clientX - panelRect.left,
                offsetY: event.clientY - panelRect.top
            };
            panel.style.left = (panelRect.left - containerRect.left) + 'px';
            panel.style.top = (panelRect.top - containerRect.top) + 'px';
            panel.style.right = 'auto';
            panel.classList.add('is-dragging');
            handle.setPointerCapture(event.pointerId);
            event.preventDefault();
        });
        handle.addEventListener('pointermove', function (event) {
            if (!drag || event.pointerId !== drag.pointerId) return;
            var containerRect = container.getBoundingClientRect();
            var maxLeft = Math.max(0, container.clientWidth - panel.offsetWidth);
            var maxTop = Math.max(0, container.clientHeight - panel.offsetHeight);
            var left = event.clientX - containerRect.left - drag.offsetX;
            var top = event.clientY - containerRect.top - drag.offsetY;
            panel.style.left = Math.max(0, Math.min(left, maxLeft)) + 'px';
            panel.style.top = Math.max(0, Math.min(top, maxTop)) + 'px';
        });
        function stopDragging(event) {
            if (!drag || event.pointerId !== drag.pointerId) return;
            drag = null;
            panel.classList.remove('is-dragging');
        }
        handle.addEventListener('pointerup', stopDragging);
        handle.addEventListener('pointercancel', stopDragging);
    }

    function resetCostReconciliationPosition() {
        initializeCostReconciliationDrag();
        $('.cost-reconciliation-panel')
            .hide()
            .css({ left: 'auto', right: '18px', top: '58px', width: '560px', height: 'auto' });
        $('#btn-toggle-cost-panel').removeClass('active');
    }

    $('#modal-view-xml').on('click', '.btn-close-cost-panel', function (event) {
        event.preventDefault();
        event.stopPropagation();
        $('.cost-reconciliation-panel').hide();
        $('#btn-toggle-cost-panel').removeClass('active');
    });

    $('#btn-toggle-cost-panel').on('click', function () {
        var panel = $('.cost-reconciliation-panel');
        var willShow = !panel.is(':visible');
        panel.toggle(willShow);
        $(this).toggleClass('active', willShow);
    });

    function excelColumnName(index) {
        var name = '';
        var value = index + 1;
        while (value > 0) {
            value--;
            name = String.fromCharCode(65 + (value % 26)) + name;
            value = Math.floor(value / 26);
        }
        return name;
    }

    function excelXmlText(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&apos;')
            .replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F]/g, '');
    }

    function collectExcelRecords(data, records, path) {
        records = records || [];
        path = path || '';
        if (!data || typeof data !== 'object') return records;
        if (Array.isArray(data)) {
            data.forEach(function (item, index) {
                collectExcelRecords(item, records, path + '[' + index + ']');
            });
            return records;
        }
        if (Object.prototype.hasOwnProperty.call(data, 'MA_LK')) {
            records.push({ data: data, path: path, styles: {} });
            return records;
        }
        Object.keys(data).forEach(function (key) {
            collectExcelRecords(data[key], records, path ? path + '.' + key : key);
        });
        return records;
    }

    function excelSheetsFromDecoded(decoded, validation) {
        var grouped = {};
        var order = [];
        var hoSoList = decoded && decoded.DANHSACHHOSO && decoded.DANHSACHHOSO.HOSO;
        if (!Array.isArray(hoSoList)) return [];
        hoSoList.forEach(function (hoSo, hoSoIndex) {
            var files = hoSo && hoSo.FILEHOSO;
            if (!Array.isArray(files)) return;
            files.forEach(function (file, fileIndex) {
                var type = String(file.LOAIHOSO || 'XML').toUpperCase();
                if (!grouped[type]) {
                    grouped[type] = [];
                    order.push(type);
                }
                var newRecords = collectExcelRecords(file.NOIDUNGFILE || {}, []);
                var validationItem = null;
                (validation || []).some(function (item) {
                    if (Number(item.hoso_index) === hoSoIndex && Number(item.file_index) === fileIndex) {
                        validationItem = item;
                        return true;
                    }
                    return false;
                });
                newRecords.forEach(function (record) {
                    (validationItem && validationItem.errors || []).forEach(function (error) {
                        var field = String(error.field_name || '');
                        var expectedPath = record.path ? record.path + '.' + field : field;
                        if (String(error.path || '') === expectedPath) {
                            record.styles[field] = error.severity === 'warning' ? 3 : 2;
                        }
                    });
                    grouped[type].push(record);
                });
            });
        });
        order.sort(function (left, right) {
            var leftNumber = Number((/\d+/.exec(left) || [Number.MAX_SAFE_INTEGER])[0]);
            var rightNumber = Number((/\d+/.exec(right) || [Number.MAX_SAFE_INTEGER])[0]);
            return leftNumber - rightNumber || left.localeCompare(right);
        });
        return order.map(function (name) { return { name: name, rows: grouped[name] }; });
    }

    function buildWorksheetXml(records) {
        var headers = [];
        var knownHeaders = {};
        records.forEach(function (record) {
            Object.keys(record.data).forEach(function (key) {
                if (!knownHeaders[key] && record.data[key] !== null && typeof record.data[key] !== 'object') {
                    knownHeaders[key] = true;
                    headers.push(key);
                }
            });
        });
        if (!headers.length) headers.push('MA_LK');

        function shouldExportAsNumber(header, value) {
            var textValue = value == null ? '' : String(value).trim();
            if (!/^-?(?:\d+|\d*\.\d+)$/.test(textValue)) return false;

            // Mã, ngày và số định danh phải giữ dạng text để không mất
            // số 0 ở đầu hoặc bị Excel làm tròn quá 15 chữ số.
            if (/^(?:MA_|NGAY_|GT_|SO_CCCD$|SO_CMND$|TT_THAU$)/i.test(header)) return false;
            if (/^0\d+/.test(textValue) || textValue.replace(/[-.]/g, '').length > 15) return false;
            return true;
        }

        function excelNumberStyle(header, currentStyle) {
            // Giữ màu nền của ô validation; các ô bình thường dùng
            // Number Format thật thay vì General.
            if (currentStyle) return currentStyle;
            if (/^(?:STT|PHAM_VI|SỐ LƯỢNG LỖI|SO LUONG LOI)$/i.test(header)) return 5; // #,##0
            if (/^(?:SỐ TIỀN|SO TIEN)$/i.test(header)) return 5; // #,##0
            if (/^DON_GIA_/i.test(header)) return 7; // #,##0.000
            if (/^(?:TYLE_|MUC_HUONG$)/i.test(header)) return 8; // 0.##
            if (/^(?:SO_LUONG$|THANH_TIEN_|T_)/i.test(header)) return 6; // #,##0.00
            return 6;
        }

        function cellXml(columnIndex, rowIndex, value, style, header, forceText) {
            var reference = excelColumnName(columnIndex) + rowIndex;
            var textOnly = !forceText && /^(?:GIA_TRI_LOI|ĐIỀU TRỊ TỪ|ĐIỀU TRỊ ĐẾN)$/i.test(header);
            if (!forceText && /^(?:ĐIỀU TRỊ TỪ|ĐIỀU TRỊ ĐẾN)$/i.test(header)) {
                var treatmentDigits = String(value == null ? '' : value).replace(/\D/g, '');
                if (treatmentDigits.length >= 12) {
                    value = treatmentDigits.substr(6, 2) + '/' + treatmentDigits.substr(4, 2) + '/'
                        + treatmentDigits.substr(0, 4) + ' ' + treatmentDigits.substr(8, 2) + ':'
                        + treatmentDigits.substr(10, 2);
                }
            }
            if (!forceText && /^(?:SỐ TIỀN|SO TIEN)$/i.test(header) && typeof value === 'string') {
                // Chuẩn hóa tiền dạng 1.994.712 hoặc 1.994.712,50 thành số Excel.
                var moneyValue = value.trim().replace(/[\s\u00a0]/g, '').replace(/\./g, '').replace(',', '.');
                if (/^-?(?:\d+|\d*\.\d+)$/.test(moneyValue)) value = moneyValue;
            }
            var isMoneyNumber = /^(?:SỐ TIỀN|SO TIEN)$/i.test(header)
                && /^-?(?:\d+|\d*\.\d+)$/.test(String(value == null ? '' : value).trim());
            if (!forceText && !textOnly && (isMoneyNumber || shouldExportAsNumber(header, value))) {
                return '<c r="' + reference + '" s="' + excelNumberStyle(header, style) + '"><v>'
                    + String(value).trim() + '</v></c>';
            }
            if (textOnly) style = 9; // Excel Text format (@).
            return '<c r="' + reference + '" t="inlineStr" s="' + style + '"><is><t xml:space="preserve">'
                + excelXmlText(value) + '</t></is></c>';
        }

        var xmlRows = [];
        xmlRows.push('<row r="1">' + headers.map(function (header, index) {
            return cellXml(index, 1, header, 1, header, true);
        }).join('') + '</row>');
        records.forEach(function (record, recordIndex) {
            var rowNumber = recordIndex + 2;
            xmlRows.push('<row r="' + rowNumber + '">' + headers.map(function (header, columnIndex) {
                var value = record.data[header];
                if (value !== null && typeof value === 'object') value = JSON.stringify(value);
                return cellXml(columnIndex, rowNumber, value, record.styles[header] || 0, header, false);
            }).join('') + '</row>');
        });
        var lastColumn = excelColumnName(headers.length - 1);
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            + '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            + '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            + '<cols><col min="1" max="' + headers.length + '" width="18" customWidth="1"/></cols>'
            + '<sheetData>' + xmlRows.join('') + '</sheetData>'
            + '<autoFilter ref="A1:' + lastColumn + Math.max(1, records.length + 1) + '"/>'
            + '</worksheet>';
    }

    function exportDecodedToExcel(decoded, validation, filename) {
        var sheets = excelSheetsFromDecoded(decoded, validation);
        if (!sheets.length) return $.Deferred().reject('Không có dữ liệu để xuất.').promise();

        var warningRows = [];
        (validation || []).forEach(function (item) {
            (item.errors || []).forEach(function (error) {
                warningRows.push({ data: {
                    STT: warningRows.length + 1,
                    LOAI_XML: item.file_type || '',
                    MUC_DO: error.severity === 'warning' ? 'Cảnh báo' : 'Lỗi',
                    TEN_TRUONG: error.field_name || '',
                    TEN_HIEN_THI: error.display_name || '',
                    DUONG_DAN: error.path || '',
                    GIA_TRI_LOI: error.value == null ? '' : error.value,
                    THONG_BAO: error.message || ''
                }, path: '', styles: { MUC_DO: error.severity === 'warning' ? 3 : 2, THONG_BAO: error.severity === 'warning' ? 3 : 2 } });
            });
        });
        if (!warningRows.length) {
            warningRows.push({ data: {
                STT: '', LOAI_XML: '', MUC_DO: 'Hợp lệ', TEN_TRUONG: '',
                TEN_HIEN_THI: '', DUONG_DAN: '', GIA_TRI_LOI: '',
                THONG_BAO: 'Không có lỗi hoặc cảnh báo validation.'
            }, path: '', styles: { MUC_DO: 4, THONG_BAO: 4 } });
        }
        sheets.push({ name: 'CẢNH BÁO', rows: warningRows });

        var costTotals = calculateCostReconciliation(decoded);
        var costKeys = ['thuoc', 'vtyt', 'bv', 'bh', 'bhtt', 'bncct', 'bntt'];
        var costDetails = emptyCostRow();
        var costDifference = emptyCostRow();
        costKeys.forEach(function (key) {
            costDetails[key] = costTotals.XML2[key] + costTotals.XML3[key];
            costDifference[key] = costTotals.XML1[key] - costDetails[key];
        });
        function costRecord(label, values, difference) {
            var data = { NGUON: label };
            var styles = {};
            var names = { thuoc: 'THUOC', vtyt: 'VTYT', bv: 'BV', bh: 'BH', bhtt: 'BHTT', bncct: 'BNCCT', bntt: 'BNTT' };
            costKeys.forEach(function (key) {
                data[names[key]] = formatCost(values[key]);
                if (difference) styles[names[key]] = Math.abs(values[key]) <= 1 ? 4 : 2;
            });
            return { data: data, path: '', styles: styles };
        }
        sheets.push({ name: 'ĐỐI CHIẾU', rows: [
            costRecord('XML1', costTotals.XML1, false),
            costRecord('XML2', costTotals.XML2, false),
            costRecord('XML3', costTotals.XML3, false),
            costRecord('LỆCH', costDifference, true)
        ] });
        var zip = new JSZip();
        var contentTypes = [];
        var workbookSheets = [];
        var workbookRelationships = [];
        sheets.forEach(function (sheet, index) {
            var number = index + 1;
            contentTypes.push('<Override PartName="/xl/worksheets/sheet' + number + '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>');
            workbookSheets.push('<sheet name="' + excelXmlText(sheet.name.substring(0, 31)) + '" sheetId="' + number + '" r:id="rId' + number + '"/>');
            workbookRelationships.push('<Relationship Id="rId' + number + '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' + number + '.xml"/>');
            zip.file('xl/worksheets/sheet' + number + '.xml', buildWorksheetXml(sheet.rows));
        });
        workbookRelationships.push('<Relationship Id="rId' + (sheets.length + 1) + '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>');
        zip.file('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' + contentTypes.join('') + '</Types>');
        zip.file('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        zip.file('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>' + workbookSheets.join('') + '</sheets></workbook>');
        zip.file('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' + workbookRelationships.join('') + '</Relationships>');
        zip.file('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="2"><numFmt numFmtId="164" formatCode="#,##0.000"/><numFmt numFmtId="165" formatCode="0.##"/></numFmts><fonts count="3"><font><sz val="13"/><name val="Times New Roman"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="13"/><name val="Times New Roman"/></font><font><b/><sz val="13"/><name val="Times New Roman"/></font></fonts><fills count="6"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF337AB7"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF4CCCC"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFFF2CC"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFD9EAD3"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="10"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center"/></xf><xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1"/><xf numFmtId="0" fontId="2" fillId="4" borderId="0" xfId="0" applyFont="1" applyFill="1"/><xf numFmtId="0" fontId="2" fillId="5" borderId="0" xfId="0" applyFont="1" applyFill="1"/><xf numFmtId="3" fontId="0" fillId="0" borderId="0" xfId="0" applyFont="1" applyNumberFormat="1"/><xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyFont="1" applyNumberFormat="1"/><xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyFont="1" applyNumberFormat="1"/><xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyFont="1" applyNumberFormat="1"/><xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyFont="1" applyNumberFormat="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
        var options = {
            type: 'blob',
            mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        };
        if (typeof zip.generateAsync === 'function') {
            return zip.generateAsync(options).then(function (blob) {
                $.fn.dataTable.fileSave(blob, filename);
            });
        }

        var deferred = $.Deferred();
        try {
            $.fn.dataTable.fileSave(zip.generate(options), filename);
            deferred.resolve();
        } catch (error) {
            deferred.reject(error);
        }
        return deferred.promise();
    }

    // Xuất báo cáo tổng hợp: mỗi loại lỗi được đặt trên một sheet riêng.
    function exportErrorReportSheets(rows, fileSummaries, filename) {
        var grouped = {}, order = [];
        (rows || []).forEach(function (row, index) {
            var errorName = String(row.error || 'Lỗi validation').trim() || 'Lỗi validation';
            if (!grouped[errorName]) { grouped[errorName] = []; order.push(errorName); }
            grouped[errorName].push({ data: {
                STT: grouped[errorName].length + 1,
                'MÃ LIÊN KẾT': row.ma_lk || '', XML: row.xml || '', 'CHỈ LỖI': row.field || '',
                'GIÁ TRỊ LỖI': row.value || '', LỖI: row.error || '', 'GHI CHÚ': row.notes || '',
                NGÀY: row.date || ''
            }, styles: { 'GIÁ TRỊ LỖI': 2, LỖI: 2 } });
        });
        var allRows = [];
        (rows || []).forEach(function (row, index) {
            allRows.push({ data: {
                STT: index + 1, 'MÃ LIÊN KẾT': row.ma_lk || '', XML: row.xml || '', 'CHỈ LỖI': row.field || '',
                'GIÁ TRỊ LỖI': row.value || '', LỖI: row.error || '', 'GHI CHÚ': row.notes || '',
                NGÀY: row.date || ''
            }, styles: { 'GIÁ TRỊ LỖI': 2, LỖI: 2 } });
        });
        var files = {}, fileOrder = [];
        (fileSummaries && fileSummaries.length ? fileSummaries : rows || []).forEach(function (row) {
            var fileKey = String(row.ma_lk || '(Không có mã liên kết)');
            if (!files[fileKey]) {
                files[fileKey] = { ma_lk: fileKey, ma_bn: row.ma_bn || '', ma_the_bhyt: row.ma_the_bhyt || '',
                    ngay_vao: row.ngay_vao || '', ngay_ra: row.ngay_ra || '', total_amount: row.total_amount || '',
                    count: 0, errors: {}, date: row.date || '', status: row.status || '' };
                fileOrder.push(fileKey);
            }
            files[fileKey].count = row.error_count != null ? Number(row.error_count) : files[fileKey].count + (row.error ? 1 : 0);
            if (row.error) files[fileKey].errors[String(row.error)] = true;
            if (row.date) files[fileKey].date = row.date;
            if (row.status) files[fileKey].status = row.status;
        });
        var fileRows = fileOrder.map(function (fileKey, index) {
            var file = files[fileKey];
            return { data: {
                STT: index + 1, 'MÃ BN': file.ma_bn, 'MÃ THẺ BHYT': file.ma_the_bhyt,
                'ĐIỀU TRỊ TỪ': file.ngay_vao, 'ĐIỀU TRỊ ĐẾN': file.ngay_ra,
                'SỐ LƯỢNG LỖI': file.count, 'SỐ TIỀN': file.total_amount,
                'MÃ LIÊN KẾT': file.ma_lk
            }, styles: file.count > 0 ? { 'SỐ LƯỢNG LỖI': 2 } : {} };
        });
        var sheets = [
            { name: 'TỔNG HỢP FILE', rows: fileRows },
            { name: 'TỔNG HỢP LỖI', rows: allRows }
        ];
        order.forEach(function (name) { sheets.push({ name: name, rows: grouped[name] }); });
        if ((!rows || !rows.length) && (!fileSummaries || !fileSummaries.length)) {
            return $.Deferred().reject('Không có dữ liệu để xuất.').promise();
        }

        var zip = new JSZip(), contentTypes = [], workbookSheets = [], relationships = [], usedSheetNames = {};
        sheets.forEach(function (sheet, index) {
            var number = index + 1;
            var safeName = String(sheet.name || 'Sheet').replace(/[\\\/\?\*\[\]\:]/g, ' ').trim().substring(0, 31) || 'Sheet';
            var baseName = safeName, suffix = 2;
            while (usedSheetNames[safeName.toLowerCase()]) {
                var suffixText = ' (' + suffix++ + ')';
                safeName = baseName.substring(0, 31 - suffixText.length) + suffixText;
            }
            usedSheetNames[safeName.toLowerCase()] = true;
            contentTypes.push('<Override PartName="/xl/worksheets/sheet' + number + '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>');
            workbookSheets.push('<sheet name="' + excelXmlText(safeName) + '" sheetId="' + number + '" r:id="rId' + number + '"/>');
            relationships.push('<Relationship Id="rId' + number + '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' + number + '.xml"/>');
            zip.file('xl/worksheets/sheet' + number + '.xml', buildWorksheetXml(sheet.rows));
        });
        relationships.push('<Relationship Id="rId' + (sheets.length + 1) + '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>');
        zip.file('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' + contentTypes.join('') + '</Types>');
        zip.file('_rels/.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        zip.file('xl/workbook.xml', '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>' + workbookSheets.join('') + '</sheets></workbook>');
        zip.file('xl/_rels/workbook.xml.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' + relationships.join('') + '</Relationships>');
        zip.file('xl/styles.xml', '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="2"><numFmt numFmtId="164" formatCode="#,##0.000"/><numFmt numFmtId="165" formatCode="0.##"/></numFmts><fonts count="3"><font><sz val="11"/><name val="Arial"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Arial"/></font><font><b/><sz val="11"/><name val="Arial"/></font></fonts><fills count="6"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF337AB7"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF4CCCC"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFFF2CC"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFD9EAD3"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="10"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" applyAlignment="1"><alignment horizontal="center"/></xf><xf numFmtId="0" fontId="2" fillId="3" borderId="0"/><xf numFmtId="0" fontId="2" fillId="4" borderId="0"/><xf numFmtId="0" fontId="2" fillId="5" borderId="0"/><xf numFmtId="3" fontId="0" fillId="0" borderId="0" applyNumberFormat="1"/><xf numFmtId="4" fontId="0" fillId="0" borderId="0" applyNumberFormat="1"/><xf numFmtId="164" fontId="0" fillId="0" borderId="0" applyNumberFormat="1"/><xf numFmtId="165" fontId="0" fillId="0" borderId="0" applyNumberFormat="1"/><xf numFmtId="49" fontId="0" fillId="0" borderId="0" applyNumberFormat="1"/></cellXfs></styleSheet>');
        var deferred = $.Deferred();
        if (typeof zip.generateAsync === 'function') {
            zip.generateAsync({ type: 'blob', mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }).then(function (blob) {
                $.fn.dataTable.fileSave(blob, filename); deferred.resolve();
            }, function (error) { deferred.reject(error); });
        } else {
            try {
                $.fn.dataTable.fileSave(zip.generate({ type: 'blob', mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }), filename);
                deferred.resolve();
            } catch (error) { deferred.reject(error); }
        }
        return deferred.promise();
    }

    function renderSelectedDecodedFile() {
        var index = Number($('#decoded-file-select').val());
        $('#decoded-file-tabs .decoded-file-tab')
            .removeClass('is-active').attr('aria-selected', 'false')
            .filter('[data-file-index="' + index + '"]')
            .addClass('is-active').attr('aria-selected', 'true');
        var tree = $('#decoded-json-tree').empty();
        $('#decoded-xml-source, #decoded-table-view, #decoded-error-statistics').empty();
        if (!decodedFiles[index]) {
            tree.text('Không có nội dung đã giải mã.');
            $('#decoded-xml-source, #decoded-table-view').text('Không có nội dung đã giải mã.');
            applyXmlViewMode();
            return;
        }
        var selected = decodedFiles[index];
        renderXmlNavigation(
            xmlViewMode === 'source' ? currentDecodedContent : null,
            xmlViewMode === 'source' ? null : selected
        );
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
        renderXmlSource(selected);
        renderDecodedTable(selected);
        renderErrorStatistics(selected);
        applyXmlViewMode();
    }

    function renderPatientInfo(data) {
        function value(names) {
            for (var i = 0; i < names.length; i++) {
                var found = firstFieldValue(data, names[i]);
                if (found != null && String(found).trim() !== '') return String(found).trim();
            }
            return '-';
        }
        function formatDate(value) {
            var text = String(value || '').trim();
            var match = /^(\d{4})[-\/]?(\d{2})[-\/]?(\d{2})/.exec(text);
            return match ? match[3] + '/' + match[2] + '/' + match[1] : (text || '-');
        }
        $('#patient-info-name').text(value(['HO_TEN']));
        var patientCode = value(['MA_BN']);
        $('#patient-info-code').text(patientCode).attr('data-copy-value', patientCode === '-' ? '' : patientCode);
        $('#patient-info-insurance').text(value(['MA_THE_BHYT']));
        $('#patient-info-birth').text(value(['NGAY_SINH']));
        var gender = value(['GIOI_TINH']);
        $('#patient-info-gender').text(gender === '1' ? 'Nam' : (gender === '2' ? 'Nữ' : gender));
        $('#patient-info-address').text(value(['DIA_CHI']));
        $('#patient-info-treatment').text(formatDate(value(['NGAY_VAO'])) + ' - ' + formatDate(value(['NGAY_RA'])));
    }

    function renderErrorStatistics(selected) {
        var container = $('#decoded-error-statistics').empty();
        var errors = [];
        (decodedFiles || []).forEach(function (file) {
            (file.errors || []).forEach(function (error) {
                var severity = String(error.severity || error.level || error.muc_do || '').toLowerCase();
                if (severity === 'warning' || severity.indexOf('warn') !== -1 || severity.indexOf('cảnh báo') !== -1 || severity.indexOf('canh bao') !== -1) return;
                errors.push({ error: error, xml: file.type || '' });
            });
        });
        var table = $('<table class="table table-striped table-bordered error-statistics-table">');
        $('<thead><tr><th>ID lỗi</th><th>Tên lỗi</th><th>XML</th><th>Nguyên nhân</th><th>Hướng xử lý</th></tr></thead>').appendTo(table);
        var body = $('<tbody>').appendTo(table);
        if (!errors.length) {
            $('<tr><td colspan="5" class="text-center text-muted">Không có lỗi trong XML này.</td></tr>').appendTo(body);
        } else {
            errors.forEach(function (item) {
                var error = item.error;
                $('<tr>').append(
                    $('<td>').text(error.rule_id == null ? '' : error.rule_id),
                    $('<td>').text(error.display_name || error.field_name || ''),
                    $('<td>').text(item.xml),
                    $('<td>').text(error.message || ''),
                    $('<td>').text(error.huongxuly || 'Chưa cập nhật hướng xử lý')
                ).appendTo(body);
            });
        }
        container.append('<h4><i class="fa fa-bar-chart"></i> Thống kê các lỗi</h4>').append(table);
    }

    function xmlElementName(name) {
        var normalized = String(name == null ? 'ITEM' : name).replace(/[^A-Za-z0-9_.-]/g, '_');
        return /^[A-Za-z_]/.test(normalized) ? normalized : 'ITEM_' + normalized;
    }

    function decodedValueToXml(name, value, depth, lines) {
        var tag = xmlElementName(name);
        var indent = new Array(depth + 1).join('  ');
        if (Array.isArray(value)) {
            value.forEach(function (item) { decodedValueToXml(tag, item, depth, lines); });
            return;
        }
        if (value !== null && typeof value === 'object') {
            lines.push(indent + '<' + tag + '>');
            Object.keys(value).forEach(function (key) {
                decodedValueToXml(key, value[key], depth + 1, lines);
            });
            lines.push(indent + '</' + tag + '>');
            return;
        }
        var textValue = value == null ? '' : String(value);
        if (textValue === '') {
            lines.push(indent + '<' + tag + ' />');
        } else {
            if (/[<>&]/.test(textValue)) {
                textValue = '<![CDATA[' + textValue.replace(/\]\]>/g, ']]]]><![CDATA[>') + ']]>';
            }
            lines.push(indent + '<' + tag + '>' + textValue + '</' + tag + '>');
        }
    }

    function appendXmlSourceLine(container, line) {
        var element = $('<span>').addClass('xml-source-line').attr('data-xml-line', line.trim());
        var match = /^(\s*)(<[^>]+>)([\s\S]*?)(<\/[^>]+>)?$/.exec(line);
        if (!match) {
            element.text(line).appendTo(container);
            return;
        }
        element.append(document.createTextNode(match[1]));
        $('<span>').addClass('xml-token-tag').text(match[2]).appendTo(element);
        if (match[3]) $('<span>').addClass('xml-token-value').text(match[3]).appendTo(element);
        if (match[4]) $('<span>').addClass('xml-token-tag').text(match[4]).appendTo(element);
        element.appendTo(container);
    }

    function renderXmlSource(selected) {
        var lines = ['<?xml version="1.0" encoding="utf-8"?>'];
        if (currentDecodedContent) {
            decodedValueToXml('GIAMDINHHS', currentDecodedContent, 0, lines);
        } else {
            decodedValueToXml(selected.type || 'NOIDUNGFILE', selected.content, 0, lines);
        }
        var source = $('#decoded-xml-source').empty();
        lines.forEach(function (line) { appendXmlSourceLine(source, line); });
    }

    function renderXmlNavigation(decoded, selected) {
        var groups = [];
        var known = {};

        function collectFieldNames(value, fields, found) {
            if (Array.isArray(value)) {
                value.forEach(function (item) { collectFieldNames(item, fields, found); });
                return;
            }
            if (!value || typeof value !== 'object') return;
            Object.keys(value).forEach(function (key) {
                if (value[key] !== null && typeof value[key] === 'object') {
                    collectFieldNames(value[key], fields, found);
                } else if (!found[key]) {
                    found[key] = true;
                    fields.push(key);
                }
            });
        }

        if (selected && selected.content && typeof selected.content === 'object') {
            Object.keys(selected.content).forEach(function (key) {
                var fields = [];
                collectFieldNames(selected.content[key], fields, {});
                groups.push({ name: key, fields: fields });
            });
        } else {
            var hoSoList = decoded && decoded.DANHSACHHOSO && decoded.DANHSACHHOSO.HOSO;
            if (Array.isArray(hoSoList)) {
            hoSoList.forEach(function (hoSo) {
                (hoSo && Array.isArray(hoSo.FILEHOSO) ? hoSo.FILEHOSO : []).forEach(function (file) {
                    var content = file.NOIDUNGFILE || {};
                    Object.keys(content).forEach(function (key) {
                        if (!known[key]) {
                            known[key] = true;
                            var fields = [];
                            collectFieldNames(content[key], fields, {});
                            groups.push({ name: key, fields: fields });
                        }
                    });
                });
            });
            }
        }
        var items = $('#xml-navigation-items').empty();
        groups.forEach(function (group) {
            var wrapper = $('<div>').addClass('xml-navigation-group').appendTo(items);
            $('<button type="button">')
                .addClass('xml-navigation-item xml-navigation-folder')
                .attr('data-xml-tag', xmlElementName(group.name))
                .append($('<i>').addClass('fa fa-plus xml-folder-toggle'))
                .append($('<i>').addClass('fa fa-folder'))
                .append(document.createTextNode(group.name))
                .appendTo(wrapper);
            var children = $('<div>').addClass('xml-navigation-children').appendTo(wrapper);
            group.fields.forEach(function (field) {
                $('<button type="button">')
                    .addClass('xml-navigation-item xml-navigation-field')
                    .attr('data-xml-tag', xmlElementName(field))
                    .append($('<i>').addClass('fa fa-circle'))
                    .append(document.createTextNode(field))
                    .appendTo(children);
            });
        });
        if (!groups.length) items.append($('<div>').addClass('text-muted').text('Không có nhóm XML.'));
    }

    function formatDecodedTableValue(header, value) {
        var textValue = value == null ? '' : String(value).trim();
        if (!/^-?(?:\d+|\d*\.\d+)$/.test(textValue)) return null;
        if (/^(?:MA_|NGAY_|GT_|SO_CCCD$|SO_CMND$|TT_THAU$)/i.test(header)) return null;
        if (/^0\d+/.test(textValue) || textValue.replace(/[-.]/g, '').length > 15) return null;

        var minimumFractionDigits = 0;
        var maximumFractionDigits = 2;
        if (/^(?:STT|PHAM_VI)$/i.test(header)) maximumFractionDigits = 0;
        else if (/^DON_GIA_/i.test(header)) minimumFractionDigits = maximumFractionDigits = 3;
        else if (/^(?:SO_LUONG$|THANH_TIEN_|T_)/i.test(header)) {
            minimumFractionDigits = maximumFractionDigits = 2;
        }
        return Number(textValue).toLocaleString('vi-VN', {
            minimumFractionDigits: minimumFractionDigits,
            maximumFractionDigits: maximumFractionDigits
        });
    }

    function renderDecodedTable(selected) {
        var records = collectExcelRecords(selected.content || {}, []);
        var container = $('#decoded-table-view').empty();
        if (!records.length) {
            container.text('Không có dữ liệu dạng bảng.');
            return;
        }
        var headers = [];
        var known = {};
        records.forEach(function (record) {
            Object.keys(record.data).forEach(function (key) {
                if (!known[key] && (record.data[key] === null || typeof record.data[key] !== 'object')) {
                    known[key] = true;
                    headers.push(key);
                }
            });
        });
        if (String(selected.type || '').toUpperCase() === 'XML2') {
            var priorityHeaders = ['STT', 'TEN_THUOC' ,'MA_THUOC'];
            headers = priorityHeaders.filter(function (header) {
                return known[header];
            }).concat(headers.filter(function (header) {
                return priorityHeaders.indexOf(header) === -1;
            }));
        }
        if (String(selected.type || '').toUpperCase() === 'XML3') {
            var xml3PriorityHeaders = ['STT', 'TEN_DICH_VU'];
            headers = xml3PriorityHeaders.filter(function (header) {
                return known[header];
            }).concat(headers.filter(function (header) {
                return xml3PriorityHeaders.indexOf(header) === -1;
            }));
        }
        var tableElement = $('<table>').addClass('table table-bordered table-striped table-condensed');
        var headerRow = $('<tr>');
        headers.forEach(function (header) {
            var cell = $('<th>')
                .attr('aria-sort', 'none')
                .append(
                    $('<button>')
                        .attr({ type: 'button', title: 'Sắp xếp theo ' + header })
                        .addClass('decoded-table-sort')
                        .append($('<span>').addClass('decoded-table-sort-label').text(header))
                        .append($('<span>').addClass('decoded-table-sort-icon').attr('aria-hidden', 'true').text('↕'))
                );
            if (String(selected.type || '').toUpperCase() === 'XML2') {
                if (header === 'STT') cell.addClass('xml2-frozen-stt');
                if (header === 'TEN_THUOC') cell.addClass('xml2-frozen-ten-thuoc');
                if (header === 'MA_THUOC') cell.addClass('xml2-frozen-ma-thuoc');
            }
            if (String(selected.type || '').toUpperCase() === 'XML3') {
                if (header === 'STT') cell.addClass('xml3-frozen-stt');
                if (header === 'TEN_DICH_VU') cell.addClass('xml3-frozen-ten-dich-vu');
            }
            cell.appendTo(headerRow);
        });
        tableElement.append($('<thead>').append(headerRow));
        var body = $('<tbody>');
        records.forEach(function (record, recordIndex) {
            var row = $('<tr>').attr('data-original-index', recordIndex);
            var rowSeverity = '';
            headers.forEach(function (header) {
                var value = record.data[header];
                var rawValue = value == null ? '' : String(value);
                var formattedValue = formatDecodedTableValue(header, value);
                var cell = $('<td>')
                    .text(formattedValue === null ? rawValue : formattedValue)
                    .attr('title', rawValue);
                if (formattedValue !== null) cell.addClass('table-number-cell');
                if (String(selected.type || '').toUpperCase() === 'XML2') {
                    if (header === 'STT') cell.addClass('xml2-frozen-stt');
                    if (header === 'TEN_THUOC') cell.addClass('xml2-frozen-ten-thuoc');
                    if (header === 'MA_THUOC') cell.addClass('xml2-frozen-ma-thuoc');
                }
                if (String(selected.type || '').toUpperCase() === 'XML3') {
                    if (header === 'STT') cell.addClass('xml3-frozen-stt');
                    if (header === 'TEN_DICH_VU') cell.addClass('xml3-frozen-ten-dich-vu');
                }
                var fieldPath = record.path ? record.path + '.' + header : header;
                (selected.errors || []).some(function (error) {
                    if (String(error.path || '') !== fieldPath) return false;
                    var warning = error.severity === 'warning';
                    cell.addClass(warning ? 'table-cell-warning' : 'table-cell-error');
                    if (!warning) rowSeverity = 'error';
                    else if (!rowSeverity) rowSeverity = 'warning';
                    cell.attr('title', (error.message || '') + (value == null ? '' : ' | Giá trị: ' + value));
                    return true;
                });
                cell.appendTo(row);
            });
            if (rowSeverity) row.addClass('table-row-' + rowSeverity);
            body.append(row);
        });
        headerRow.on('click', '.decoded-table-sort', function () {
            var button = $(this);
            var selectedCell = button.closest('th');
            var columnIndex = selectedCell.index();
            var ascending = selectedCell.attr('aria-sort') !== 'ascending';
            var rows = body.children('tr').get();

            rows.sort(function (left, right) {
                var leftIndex = Number($(left).attr('data-original-index'));
                var rightIndex = Number($(right).attr('data-original-index'));
                var leftValue = records[leftIndex].data[headers[columnIndex]];
                var rightValue = records[rightIndex].data[headers[columnIndex]];
                var leftText = leftValue == null ? '' : String(leftValue).trim();
                var rightText = rightValue == null ? '' : String(rightValue).trim();
                var leftNumber = /^-?(?:\d+|\d*\.\d+)$/.test(leftText) ? Number(leftText) : null;
                var rightNumber = /^-?(?:\d+|\d*\.\d+)$/.test(rightText) ? Number(rightText) : null;
                var comparison;

                if (leftNumber !== null && rightNumber !== null) {
                    comparison = leftNumber - rightNumber;
                } else if (leftText === '' || rightText === '') {
                    comparison = leftText === rightText ? 0 : (leftText === '' ? 1 : -1);
                } else {
                    comparison = leftText.localeCompare(rightText, 'vi', { numeric: true, sensitivity: 'base' });
                }
                if (comparison === 0) comparison = leftIndex - rightIndex;
                return ascending ? comparison : -comparison;
            });

            headerRow.children('th').attr('aria-sort', 'none')
                .find('.decoded-table-sort-icon').text('↕');
            selectedCell.attr('aria-sort', ascending ? 'ascending' : 'descending');
            button.find('.decoded-table-sort-icon').text(ascending ? '▲' : '▼');
            $.each(rows, function (_, row) { body.append(row); });
        });
        tableElement.append(body).appendTo(container);
    }

    function applyXmlViewMode() {
        var sourceMode = xmlViewMode === 'source';
        $('#decoded-xml-source').toggle(sourceMode);
        $('#decoded-json-tree').toggle(xmlViewMode === 'tree');
        $('#decoded-table-view').toggle(xmlViewMode === 'table');
        $('#decoded-error-statistics').toggle(xmlViewMode === 'errors');
        $('#patient-info').toggle(xmlViewMode === 'errors');
        $('#validation-summary, #validation-valid-summary').toggle(xmlViewMode !== 'errors');
        $('.xml-file-picker').toggle(xmlViewMode !== 'errors' && !sourceMode);
        $('#xml-source-navigation').toggle(sourceMode);
        $('.cost-reconciliation').toggle(sourceMode);
        $('.xml-view-layout').toggleClass('is-content-only', !sourceMode);
        $('.xml-view-content').toggleClass('is-table-mode', xmlViewMode === 'table' || xmlViewMode === 'errors');
        $('[data-xml-view]').each(function () {
            var active = $(this).attr('data-xml-view') === xmlViewMode;
            $(this).toggleClass('btn-primary active', active).toggleClass('btn-default', !active);
        });
    }

    $('[data-xml-view]').on('click', function () {
        var requestedMode = $(this).attr('data-xml-view');
        xmlViewMode = requestedMode === 'tree' || requestedMode === 'table' || requestedMode === 'errors' ? requestedMode : 'source';
        renderSelectedDecodedFile();
    });

    $('#xml-navigation-search').on('input', function () {
        var keyword = String($(this).val() || '').trim().toLowerCase();
        $('.xml-navigation-group').each(function () {
            var group = $(this);
            var folder = group.children('.xml-navigation-folder');
            var folderMatches = !keyword || folder.text().toLowerCase().indexOf(keyword) !== -1;
            var childMatches = false;
            group.find('.xml-navigation-field').each(function () {
                var matches = !keyword || folderMatches || $(this).text().toLowerCase().indexOf(keyword) !== -1;
                $(this).toggle(matches);
                if (matches) childMatches = true;
            });
            group.toggle(folderMatches || childMatches);
            if (keyword && childMatches) {
                group.addClass('is-open');
                folder.find('.xml-folder-toggle').removeClass('fa-plus').addClass('fa-minus');
            }
        });
    });

    $('#xml-navigation-items').on('click', '.xml-navigation-item', function () {
        var selectedItem = $(this);
        if ($(this).hasClass('xml-navigation-folder')) {
            var group = $(this).closest('.xml-navigation-group').toggleClass('is-open');
            $(this).find('.xml-folder-toggle')
                .toggleClass('fa-plus', !group.hasClass('is-open'))
                .toggleClass('fa-minus', group.hasClass('is-open'));
        }
        var openingTag = '<' + $(this).attr('data-xml-tag') + '>';
        var selfClosingTag = '<' + $(this).attr('data-xml-tag');
        var target = $('#decoded-xml-source .xml-source-line').filter(function () {
            var line = String($(this).attr('data-xml-line') || '');
            return line === openingTag || line.indexOf(openingTag) === 0
                || (line.indexOf(selfClosingTag) === 0 && /\/>$/.test(line));
        }).first();
        if (!target.length) return;
        $('.xml-navigation-item').removeClass('is-active');
        selectedItem.addClass('is-active');
        var content = $('.xml-view-content');
        $('#decoded-xml-source .xml-source-line').removeClass('is-target-line');
        target.addClass('is-target-line');
        content.stop(true).animate({
            scrollTop: Math.max(
                0,
                content.scrollTop() + target.position().top
                    - $('.xml-file-selector').outerHeight() - content.innerHeight() / 3
            ),
            scrollLeft: 0
        }, 180);
        window.setTimeout(function () { target.removeClass('is-target-line'); }, 2500);
    });

    function localDateKey(date) {
        function twoDigits(value) { return value < 10 ? '0' + value : String(value); }
        return date.getFullYear() + '-' + twoDigits(date.getMonth() + 1) + '-' + twoDigits(date.getDate());
    }

    function formatTreatmentDate(value) {
        var digits = String(value == null ? '' : value).replace(/\D/g, '');
        if (digits.length < 8) return value || '';
        var result = digits.substr(6, 2) + '/' + digits.substr(4, 2) + '/' + digits.substr(0, 4);
        if (digits.length >= 12) result += ' ' + digits.substr(8, 2) + ':' + digits.substr(10, 2);
        return result;
    }

    function normalizeFileSearchText(value) {
        var text = String(value == null ? '' : value).toLowerCase();
        if (typeof text.normalize === 'function') {
            text = text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }
        return text.replace(/đ/g, 'd');
    }

    function updateFileStatistics(rows) {
        var statistics = { total: 0, success: 0, failed: 0, processing: 0 };
        var dateInput = $('#file-stat-date');
        var selectedDate = selectedFileDate();
        if (!selectedDate) {
            selectedDate = localDateKey(new Date());
            dateInput.val(selectedDate.split('-').reverse().join('/'));
        }
        $.each(rows || [], function (_, row) {
            var modified = new Date(Number(row.timestamp) * 1000);
            if (localDateKey(modified) !== selectedDate) return;
            statistics.total++;
            if (row.status === 'failed') statistics.failed++;
            else if (row.status === 'processed') statistics.success++;
            else statistics.processing++;
        });
        $('#file-stat-total').text(statistics.total);
        $('#file-stat-success').text(statistics.success);
        $('#file-stat-failed').text(statistics.failed);
        $('#file-stat-processing').text(statistics.processing);
    }

    if ($.fn.datepicker) {
        $.fn.datepicker.dates.vi = {
            days: ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'],
            daysShort: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
            daysMin: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
            months: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
            monthsShort: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
            today: 'Hôm nay', clear: 'Xóa', format: 'dd/mm/yyyy', titleFormat: 'MM yyyy', weekStart: 1
        };
        $('#file-stat-date').datepicker({ language: 'vi', autoclose: true, todayHighlight: true, format: 'dd/mm/yyyy' });
    }
    function selectedFileDate() {
        var value = String($('#file-stat-date').val() || '');
        var match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(value);
        return match ? match[3] + '-' + match[2] + '-' + match[1] : value;
    }
    $('#file-stat-date').val((function () {
        var date = new Date();
        return String(date.getDate()).padStart(2, '0') + '/' + String(date.getMonth() + 1).padStart(2, '0') + '/' + date.getFullYear();
    })());
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (!settings.nTable || settings.nTable.id !== 'datatable-file') return true;
        if (!fileSearchTerm) return true;

        var row = settings.aoData[dataIndex] ? settings.aoData[dataIndex]._aData : null;
        var status = row ? row.status : '';
        var statusText = status === 'failed' ? 'có lỗi chưa fix failed'
            : (status === 'processed' ? 'thành công đã fix processed'
                : 'đang xử lý processing pending revalidation revalidating');
        var searchableText = (data || []).join(' ');
        if (row) {
            searchableText += ' ' + [
                row.name,
                row.ma_lk,
                row.ma_bn,
                row.ma_the_bhyt,
                row.ngay_vao,
                row.ngay_ra,
                row.error_count,
                row.total_amount,
                row.modified,
                statusText
            ].join(' ');
        }
        return normalizeFileSearchText(searchableText).indexOf(fileSearchTerm) !== -1;
    });

    var table = $('#datatable-file').DataTable({
        ajax: {
            url: baseUrl + 'file/getData/',
            type: 'POST',
            data: function (request) {
                request.date = selectedFileDate();
            },
            beforeSend: function () {
                getDataInProgress = true;
            },
            complete: function () {
                getDataInProgress = false;
            },
            error: function (xhr, textStatus) {
                if (textStatus === 'abort' || revalidationInProgress) return;
                alert('Không thể tải danh sách file.');
            }
        },
        paging: true,
        pageLength: 100,
        lengthMenu: [[50, 100, 250, 500], [50, 100, 250, 500]],
        scrollX: false,
        deferRender: true,
        order: [],
        responsive: false,
        autoWidth: false,
        "lengthChange": true,
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
            { data: 'ma_bn', defaultContent: '' },
            {
                data: 'ma_the_bhyt',
                defaultContent: ''
            },
            {
                data: 'ngay_vao',
                className: 'text-center',
                render: function (data, type) {
                    return type === 'display' ? formatTreatmentDate(data) : data;
                }
            },
            {
                data: 'ngay_ra',
                className: 'text-center',
                render: function (data, type) {
                    return type === 'display' ? formatTreatmentDate(data) : data;
                }
            },
            {
                data: 'error_count',
                className: 'text-center',
                render: function (data, type) {
                    var count = Number(data) || 0;
                    if (type !== 'display') return count;
                    return count ? '<span class="label label-danger">' + count + '</span>' : '0';
                }
            },
            {
                data: 'total_amount',
                className: 'text-right',
                render: function (data, type) {
                    var amount = numericValue(data);
                    return type === 'display' ? amount.toLocaleString('vi-VN') + ' đ' : amount;
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
        createdRow: function (row, data) {
            $(row).toggleClass('file-row-has-error', Number(data.error_count) > 0);
        },
        drawCallback: function () {
            updateSelectionControls();
            updateFileStatistics(this.api().rows().data().toArray());
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
    // Đưa bộ chọn số dòng và phân trang ra ngoài vùng cuộn của bảng.
    $('#file-table-controls').append(
        $('#datatable-file_wrapper .dataTables_length'),
        $('#datatable-file_wrapper .dataTables_info'),
        $('#datatable-file_wrapper .dataTables_paginate')
    );

    $('#file-stat-date').on('change', function () {
        selectedFileIds = {};
        fileIdsToDelete = [];
        $('#select-all-files').prop('checked', false).prop('indeterminate', false);
        updateSelectionControls();
        requestFileData();
    });

    function dataReloadIsBlocked() {
        return getDataInProgress
            || uploadInProgress
            || document.hidden
            || $('.modal.in:visible').length > 0
            || $.active > 0;
    }

    function requestFileData(rememberIfBlocked) {
        if (dataReloadIsBlocked()) {
            if (rememberIfBlocked !== false) pendingDataReload = true;
            return false;
        }
        pendingDataReload = false;
        table.ajax.reload(null, false);
        return true;
    }

    $(document).ajaxStop(function () {
        if (pendingDataReload) requestFileData();
    });
    $(document).on('hidden.bs.modal', '.modal', function () {
        if (pendingDataReload) requestFileData();
    });
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && pendingDataReload) requestFileData();
    });

    function searchFiles() {
        fileSearchTerm = normalizeFileSearchText($.trim($('#file-search').val()));
        table.search('').draw();
    }

    $('#btn-search-file').on('click', searchFiles);

    $('#file-search').on('input', function () {
        window.clearTimeout(fileSearchTimer);
        fileSearchTimer = window.setTimeout(searchFiles, 200);
    });

    window.exportErrorReportSheets = exportErrorReportSheets;

    $('#btn-export-file-excel').on('click', function () {
        var button = $(this).prop('disabled', true);
        $.ajax({
            url: baseUrl + 'file/getErrorReport/',
            type: 'GET',
            dataType: 'json',
            data: { date: selectedFileDate() }
        }).done(function (response) {
            if (!response.success) {
                alert(response.message || 'Không thể tải dữ liệu báo cáo lỗi.');
                return;
            }

            exportErrorReportSheets(
                response.data || [], response.files || [],
                'danh-sach-loi-' + (selectedFileDate() || 'khong-ro-ngay') + '.xlsx'
            ).fail(function (message) { alert(message || 'Không có dữ liệu để xuất.'); });
            /*
            var exportData = (response.data || []).map(function (row, index) {
                return [index + 1, row.ma_lk, row.xml, row.field, row.value, row.error, row.notes, row.date, row.status];
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
                    { title: 'GIÁ TRỊ LỖI' },
                    { title: 'LỖI' },
                    { title: 'GHI CHÚ' },
                    { title: 'NGÀY' },
                    { title: 'TÌNH TRẠNG' }
                ],
                buttons: [{
                    extend: 'excelHtml5',
                    title: null,
                    filename: 'danh-sach-loi-' + (selectedFileDate() || 'khong-ro-ngay'),
                    sheetName: 'Danh sách lỗi',
                    customizeData: function (data) {
                        data.body.forEach(function (row) {
                            // Force long MA_LK values to text so Excel keeps every digit.
                            row[1] = '\u200B' + row[1];
                            // Preserve the exact invalid value, including leading zeroes
                            // and identifiers longer than Excel's numeric precision.
                            row[4] = '\u200B' + (row[4] == null ? '' : row[4]);
                        });
                    }
                }]
            });
            exportDataTable.button('.buttons-excel').trigger();
            window.setTimeout(function () {
                exportDataTable.destroy();
                exportTable.remove();
            }, 1500); */
        }).fail(function () {
            alert('Không thể xuất báo cáo Excel.');
        }).always(function () {
            button.prop('disabled', false);
        });
    });

    $('#btn-reset-file-search').on('click', function () {
        $('#file-search').val('').focus();
        fileSearchTerm = '';
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
        var selectedDate = selectedFileDate();
        if (!selectedDate) {
            revalidationInProgress = false;
            alert('Vui lòng chọn ngày cần quét lại.');
            return;
        }
        $('#file-stat-date').prop('disabled', true);
        var button = $(this).prop('disabled', true);
        var originalHtml = button.html();

        function finish(message) {
            revalidationInProgress = false;
            $('#file-stat-date').prop('disabled', false);
            button.prop('disabled', false).html(originalHtml);
            requestFileData();
            alert(message);
        }

        function watchProgress(total) {
            button.html('<i class="fa fa-refresh fa-spin"></i> Đang quét qua watcher...');
            $.ajax({
                url: baseUrl + 'file/revalidationProgress/', type: 'GET', dataType: 'json',
                data: { date: selectedDate }
            }).done(function (response) {
                if (!response.success) { finish(response.message || 'Không thể đọc tiến độ quét lại.'); return; }
                var progress = response.progress || {};
                var remaining = Number(progress.remaining) || 0;
                requestFileData(false);
                if (remaining > 0) {
                    button.html('<i class="fa fa-refresh fa-spin"></i> Đang quét qua watcher... (' + (total - remaining) + '/' + total + ')');
                    window.setTimeout(function () { watchProgress(total); }, 1000);
                    return;
                }
                finish('Đã đưa ' + total + ' file vào watcher để quét lại.');
            }).fail(function () { window.setTimeout(function () { watchProgress(total); }, 1500); });
        }
        /*
        function processNextBatch(total, completed, errorCount) {
            button.html('<i class="fa fa-refresh fa-spin"></i> Đang quét... ('
                + completed + '/' + total + ')');
            $.ajax({
                url: baseUrl + 'file/processRevalidationBatch/',
                type: 'POST',
                dataType: 'json',
                data: { csrf_token: csrfToken, date: selectedDate }
            }).done(function (response) {
                if (!response.success) {
                    finish(response.message || 'Không thể quét lại file.');
                    return;
                }
                errorCount += response.errors && response.errors.length ? response.errors.length : 0;
                var remaining = Number(response.remaining) || 0;
                completed = Math.max(0, total - remaining);
                if (remaining > 0) {
                    window.setTimeout(function () {
                        processNextBatch(total, completed, errorCount);
                    }, 250);
                    return;
                }
                var message = 'Đã quét lại ' + total + ' file.';
                if (errorCount) {
                    message += '\nCó ' + errorCount + ' file không thể xử lý.';
                }
                finish(message);
            }).fail(function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message : 'Không thể xử lý hàng đợi quét lại.';
                finish(message);
            });
        } */

        $.ajax({
            url: baseUrl + 'file/revalidateAll/',
            type: 'POST',
            dataType: 'json',
            data: { csrf_token: csrfToken, date: selectedDate }
        }).done(function (response) {
            if (!response.success) {
                finish(response.message || 'Không thể tạo hàng đợi quét lại.');
                return;
            }
            var total = Number(response.total) || Number(response.queued) || 0;
            watchProgress(total);
        }).fail(function (xhr) {
            var message = xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message : 'Không thể tạo hàng đợi quét lại.';
            finish(message);
        });
    });

    // Files imported by the background watcher do not pass through this page,
    // so periodically refresh the table while the tab is visible.
    setInterval(function () {
        requestFileData(false);
    }, 30000);

    $('#btn-open-upload').on('click', function () {
        $('#form-upload-xml')[0].reset();
        $('#xml-drop-file-name').hide().text('');
        $('#xml-drop-zone').removeClass('is-dragging is-uploading');
        $('#upload-message').hide();
        $('#modal-upload-xml').modal('show');
    });

    function uploadSingleXmlFile(file) {
        var formData = new FormData();
        formData.append('xmlFile', file, file.name);
        formData.append('id_khoa', $('#current-user-khoa').val() || '');
        formData.append('csrf_token', csrfToken);
        return $.ajax({
            url: baseUrl + 'file/upload/',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json'
        });
    }

    function uploadXmlFiles(fileList) {
        if (uploadInProgress) return;
        var files = Array.prototype.slice.call(fileList || []);
        if (!files.length) {
            showUploadMessage('Vui lòng chọn ít nhất một file XML.', false);
            return;
        }

        uploadInProgress = true;
        $('#xml-drop-zone').addClass('is-uploading');
        $('#btn-upload-xml').prop('disabled', true);
        var succeeded = 0;
        var failed = 0;
        var details = [];

        function finishQueue() {
            uploadInProgress = false;
            $('#xml-drop-zone').removeClass('is-uploading');
            $('#btn-upload-xml').prop('disabled', false);
            $('#form-upload-xml')[0].reset();
            $('#xml-drop-file-name').text('Đã tải ' + files.length + ' file').show();

            var summary = 'Đã đưa ' + succeeded + ' file vào hàng đợi, ' + failed + ' file tải thất bại.';
            if (details.length) summary += '\n' + details.join('\n');
            showUploadMessage(summary, failed === 0);
            requestFileData();
        }

        function processFile(index) {
            if (index >= files.length) {
                finishQueue();
                return;
            }

            var file = files[index];
            $('#xml-drop-file-name').text(file.name).show();
            showUploadMessage(
                'Đang tải file ' + (index + 1) + '/' + files.length + ': ' + file.name,
                true
            );

            if (!/\.xml$/i.test(file.name || '')) {
                failed++;
                details.push(file.name + ': không phải file .xml.');
                processFile(index + 1);
                return;
            }
            if (!file.size || file.size > 5242880) {
                failed++;
                details.push(file.name + ': dung lượng phải từ 1 byte đến 5 MB.');
                processFile(index + 1);
                return;
            }

            uploadSingleXmlFile(file).done(function (response) {
                if (response.success) {
                    succeeded++;
                } else {
                    failed++;
                    details.push(file.name + ': ' + (response.message || 'Không thể nhập file.'));
                }
            }).fail(function (xhr) {
                failed++;
                var message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message : 'Có lỗi xảy ra khi tải file lên.';
                details.push(file.name + ': ' + message);
            }).always(function () {
                processFile(index + 1);
            });
        }

        processFile(0);
    }

    $('#form-upload-xml').on('submit', function (event) {
        event.preventDefault();
        var input = $('#xmlFile')[0];
        uploadXmlFiles(input.files);
    });

    $('#xmlFile').on('change', function () {
        if (this.files.length) uploadXmlFiles(this.files);
    });

    $('#xml-drop-zone')
        .on('click', function (event) {
            if (!uploadInProgress && event.target.id !== 'xmlFile') $('#xmlFile').trigger('click');
        })
        .on('keydown', function (event) {
            if (!uploadInProgress && (event.key === 'Enter' || event.key === ' ')) {
                event.preventDefault();
                $('#xmlFile').trigger('click');
            }
        })
        .on('dragenter dragover', function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (!uploadInProgress) $(this).addClass('is-dragging');
        })
        .on('dragleave dragend', function (event) {
            event.preventDefault();
            event.stopPropagation();
            $(this).removeClass('is-dragging');
        })
        .on('drop', function (event) {
            event.preventDefault();
            event.stopPropagation();
            $(this).removeClass('is-dragging');
            if (uploadInProgress) return;
            var files = event.originalEvent.dataTransfer.files;
            uploadXmlFiles(files);
        });

    $('.file-table-scroll')
        .on('dragenter dragover', function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (!uploadInProgress) $(this).addClass('is-dragging-file');
        })
        .on('dragleave dragend', function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (!event.relatedTarget || !$.contains(this, event.relatedTarget)) {
                $(this).removeClass('is-dragging-file');
            }
        })
        .on('drop', function (event) {
            event.preventDefault();
            event.stopPropagation();
            $(this).removeClass('is-dragging-file');
            if (uploadInProgress) return;
            var files = event.originalEvent.dataTransfer.files;
            if (!files || !files.length) return;
            $('#modal-upload-xml').modal('show');
            uploadXmlFiles(files);
        });

    $(document).on('dragover drop', function (event) {
        if ($(event.target).closest('#xml-drop-zone, .file-table-scroll').length) return;
        event.preventDefault();
    });

    $('#datatable-file').on('click', '.btn-view-file', function () {
        var id = $(this).attr('data-id');
        var name = $(this).attr('data-malk');
        currentDecodedContent = null;
        currentValidationResults = [];
        currentViewFileName = '';
        $('#btn-export-view-excel').prop('disabled', true);
        $('#decoded-file-tabs').empty();
        $('#view-file-name').text('Đang tải...');
        $('#btn-download-view-file').attr('href', downloadBaseUrl + 'file/download/?id=' + Number(id));
        $('#decoded-file-select').empty().prop('disabled', true);
        $('#validation-summary').hide().empty();
        $('#validation-valid-summary').hide().empty();
        $('#decoded-json-tree').text('Đang tải...');
        $('#decoded-xml-source').empty().append(
            $('<span>').addClass('xml-source-line').text('Đang tải...')
        );
        $('#decoded-table-view').text('Đang tải...');
        $('#decoded-error-statistics').text('Đang tải...');
        applyXmlViewMode();
        $('#cost-reconciliation-content').text('Đang tính...');
        resetCostReconciliationPosition();
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
            currentDecodedContent = response.decoded;
            currentValidationResults = response.validation || [];
            renderPatientInfo(response.decoded);
            var patientName = firstFieldValue(response.decoded, 'HO_TEN');
            $('#view-file-name').text(
                patientName == null || String(patientName).trim() === '' ? name : String(patientName).trim()
            );
            $('#xml-navigation-search').val('');
            currentViewFileName = String(response.name || name || 'ho-so')
                .replace(/\.[^.]+$/, '') + '.xlsx';
            $('#btn-export-view-excel').prop('disabled', false);
            decodedFiles = collectDecodedFiles(response.decoded, response.validation);
            renderCostReconciliation(response.decoded);
            var select = $('#decoded-file-select').empty();
            var tabs = $('#decoded-file-tabs').empty();
            decodedFiles.forEach(function (file, index) {
                $('<option>').val(index).text(file.label).appendTo(select);
                $('<button type="button" role="tab">')
                    .addClass('decoded-file-tab')
                    .attr('data-file-index', index)
                    .attr('aria-selected', 'false')
                    .text(file.type || ('XML ' + (index + 1)))
                    .appendTo(tabs);
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

    $('#btn-export-view-excel').on('click', function () {
        if (!currentDecodedContent) return;
        var button = $(this).prop('disabled', true);
        exportDecodedToExcel(
            currentDecodedContent,
            currentValidationResults,
            currentViewFileName || 'ho-so.xlsx'
        )
            .then(function () {
                button.prop('disabled', false);
            }, function (error) {
                alert(typeof error === 'string' ? error : 'Không thể tạo file Excel.');
                button.prop('disabled', false);
            });
    });

    $('#decoded-file-select').on('change', renderSelectedDecodedFile);

    $('#decoded-file-tabs').on('click', '.decoded-file-tab', function () {
        $('#decoded-file-select').val($(this).attr('data-file-index')).trigger('change');
    });

    function copyErrorValue(element) {
        var target = $(element);
        var value = String(target.attr('data-copy-value') || '');
        function copied() {
            $('.copy-error-status').remove();
            target.addClass('copy-success');
            $('<span class="copy-error-status">Đã sao chép</span>').insertAfter(target);
            window.setTimeout(function () {
                target.removeClass('copy-success');
                $('.copy-error-status').fadeOut(150, function () { $(this).remove(); });
            }, 1200);
        }
        function copyWithEvent() {
            var successful = false;
            function handleCopy(event) {
                if (!event.clipboardData) return;
                event.clipboardData.setData('text/plain', value);
                event.preventDefault();
                successful = true;
            }
            document.addEventListener('copy', handleCopy);
            try { document.execCommand('copy'); } catch (ignore) {}
            document.removeEventListener('copy', handleCopy);
            if (successful) copied();
            else window.prompt('Nhấn Ctrl+C để sao chép giá trị:', value);
        }
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(value).then(copied).catch(copyWithEvent);
        } else copyWithEvent();
    }
    $('#modal-view-xml').on('click', '.copy-error-trigger', function (event) {
        event.preventDefault();
        event.stopPropagation();
        copyErrorValue(this);
    });
    $('#modal-view-xml').on('keydown', '.copy-error-value', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            copyErrorValue(this);
        }
    });


    $('#datatable-file').on('click', '.btn-delete-file', function () {
        fileIdsToDelete = [Number($(this).attr('data-id'))];
        $('#delete-file-name').text($(this).attr('data-name'));
        $('#modal-delete-xml').modal('show');
    });

    $('#btn-confirm-delete').on('click', function () {
        if (!fileIdsToDelete.length) return;
        var button = $(this).prop('disabled', true);
        var originalHtml = button.html();
        var ids = fileIdsToDelete.slice();
        var total = ids.length;
        var deletedIds = [];

        function finishDelete(errorMessage) {
            deletedIds.forEach(function (id) { delete selectedFileIds[String(id)]; });
            button.prop('disabled', false).html(originalHtml);
            fileIdsToDelete = [];
            $('#modal-delete-xml').modal('hide');
            requestFileData();
            updateSelectionControls();
            if (errorMessage) alert(errorMessage);
        }

        function deleteNextBatch(offset) {
            if (offset >= total) {
                finishDelete('Đã xóa ' + deletedIds.length + ' file.');
                return;
            }
            var batch = ids.slice(offset, offset + 500);
            button.html('<i class="fa fa-trash"></i> Đang xóa... ('
                + Math.min(offset + batch.length, total) + '/' + total + ')');
            $.ajax({
                url: baseUrl + 'file/deleteMany/',
                type: 'POST',
                dataType: 'json',
                // Send one POST variable to avoid PHP max_input_vars truncation.
                data: { ids: batch.join(','), csrf_token: csrfToken }
            }).done(function (response) {
                if (!response.success) {
                    finishDelete(response.message || 'Không thể xóa các file đã chọn.');
                    return;
                }
                deletedIds = deletedIds.concat(batch);
                deleteNextBatch(offset + batch.length);
            }).fail(function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message : 'Có lỗi xảy ra khi xóa file.';
                finishDelete(message);
            });
        }

        deleteNextBatch(0);
    });
})(jQuery);
