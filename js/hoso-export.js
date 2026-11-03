/* Tạo Excel Hồ sơ khoa từ dữ liệu API; không phụ thuộc trang DataTable đang lọc/tìm. */
(function (root) {
    'use strict';

    function treatmentDate(value) {
        var text = String(value == null ? '' : value);
        var digits = text.replace(/\D/g, '');
        if (digits.length < 8) return text;
        return digits.substr(6, 2) + '/' + digits.substr(4, 2) + '/' + digits.substr(0, 4) +
            (digits.length >= 12 ? ' ' + digits.substr(8, 2) + ':' + digits.substr(10, 2) : '');
    }

    function birthDate(value) {
        var text = String(value == null ? '' : value);
        var match = /^(\d{4})[-\/]?(\d{2})[-\/]?(\d{2})/.exec(text);
        if (match) return match[3] + '/' + match[2] + '/' + match[1];
        return text;
    }

    function buildDaySheets(files, errors) {
        return [{
            name: 'TỔNG HỢP HỒ SƠ',
            header: ['STT', 'MÃ LIÊN KẾT', 'MÃ BN', 'HỌ TÊN', 'MÃ THẺ BHYT', 'ĐỊA CHỈ', 'NGÀY SINH',
                'GIỚI TÍNH', 'ĐIỀU TRỊ TỪ', 'ĐIỀU TRỊ ĐẾN', 'SỐ LƯỢNG LỖI', 'SỐ CẢNH BÁO',
                'TÊN TRƯỜNG LỖI', 'SỐ TIỀN', 'NGÀY CẬP NHẬT', 'ĐÃ ĐẨY CỔNG'],
            rows: (files || []).map(function (r, i) {
                return [i + 1, r.ma_lk || '', r.ma_bn || '', r.ho_ten || '', r.ma_the_bhyt || '',
                    r.dia_chi || '', birthDate(r.ngay_sinh), String(r.gioi_tinh) === '1' ? 'Nam' :
                        (String(r.gioi_tinh) === '2' ? 'Nữ' : (r.gioi_tinh || '')),
                    treatmentDate(r.ngay_vao), treatmentDate(r.ngay_ra), Number(r.error_count) || 0,
                    Number(r.warning_count) || 0, r.error_fields || '', Number(r.total_amount) || 0,
                    r.update_date || '', r.da_day_cong ? 'Có' : 'Chưa'];
            })
        }, {
            name: 'TỔNG HỢP LỖI',
            header: ['STT', 'MÃ LIÊN KẾT', 'MÃ BN', 'LOẠI XML', 'TÊN TRƯỜNG', 'TÊN HIỂN THỊ',
                'GIÁ TRỊ LỖI', 'NỘI DUNG LỖI', 'HƯỚNG XỬ LÝ', 'MÃ QUY TẮC', 'NGÀY CẬP NHẬT'],
            rows: (errors || []).map(function (r, i) {
                return [i + 1, r.ma_lk || '', r.ma_bn || '', r.xml || '', r.field || '',
                    r.display_name || '', r.value || '', r.error || '', r.huongxuly || '',
                    r.rule_id || '', r.update_date || ''];
            })
        }];
    }

    /** Tên lỗi theo hàng, ngày theo cột; cuối bảng là tổng hồ sơ lỗi và chi phí từng ngày. */
    function buildMonthSheets(month, errors, files) {
        var parts = month.split('-');
        var numberOfDays = new Date(Number(parts[0]), Number(parts[1]), 0).getDate();
        var dates = [], labels = [];
        for (var d = 1; d <= numberOfDays; d++) {
            var day = ('0' + d).slice(-2);
            dates.push(month + '-' + day);
            labels.push(day + '/' + parts[1]);
        }
        var grouped = {}, order = [];
        (errors || []).forEach(function (r) {
            var name = String(r.error || r.display_name || r.field || 'Lỗi validation');
            if (!grouped[name]) {
                grouped[name] = { total: 0, days: {} };
                order.push(name);
            }
            grouped[name].total += Number(r.total) || 0;
            dates.forEach(function (date) {
                grouped[name].days[date] = (grouped[name].days[date] || 0) +
                    (Number(r.days && r.days[date]) || 0);
            });
        });

        var errorTotals = dates.map(function () { return 0; });
        var rows = order.map(function (name) {
            var counts = dates.map(function (date, i) {
                var value = grouped[name].days[date] || 0;
                errorTotals[i] += value;
                return value;
            });
            return [name].concat(counts, [grouped[name].total]);
        });

        var costTotals = dates.map(function () { return 0; });
        (files || []).forEach(function (file) {
            if ((Number(file.error_count) || 0) <= 0) return;
            var index = dates.indexOf(String(file.update_date || ''));
            if (index < 0) return;
            costTotals[index] += Number(file.total_amount) || 0;
        });
        function sum(values) {
            return values.reduce(function (total, value) { return total + value; }, 0);
        }
        rows.push(['TỔNG SỐ LỖI'].concat(errorTotals, [sum(errorTotals)]));
        rows.push(['ƯỚC TÍNH CHI PHÍ'].concat(costTotals, [sum(costTotals)]));
        return [{
            name: 'THỐNG KÊ THÁNG',
            header: ['TÊN LỖI'].concat(labels, ['TỔNG THÁNG']),
            rows: rows,
            accountingRows: [rows.length - 1]
        }];
    }

    function xmlText(value) {
        return String(value == null ? '' : value).replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F]/g, '')
            .substring(0, 32767).replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&apos;');
    }

    function excelColumn(index) {
        var column = '';
        for (var i = index + 1; i > 0; i = Math.floor((i - 1) / 26)) {
            column = String.fromCharCode(65 + (i - 1) % 26) + column;
        }
        return column;
    }

    function worksheet(sheet) {
        var entries = [sheet.header].concat(sheet.rows);
        var body = entries.map(function (values, index) {
            var rowNumber = index + 1;
            var cells = values.map(function (value, col) {
                var ref = excelColumn(col) + rowNumber;
                var style = index === 0 ? 1 :
                    (col > 0 && sheet.accountingRows && sheet.accountingRows.indexOf(index - 1) !== -1 ? 2 : 0);
                var styleAttribute = style ? ' s="' + style + '"' : '';
                if (typeof value === 'number' && isFinite(value)) {
                    return '<c r="' + ref + '"' + styleAttribute + '><v>' + value + '</v></c>';
                }
                return '<c r="' + ref + '" t="inlineStr"' + styleAttribute +
                    '><is><t xml:space="preserve">' + xmlText(value) + '</t></is></c>';
            }).join('');
            return '<row r="' + rowNumber + '">' + cells + '</row>';
        }).join('');
        var last = excelColumn(sheet.header.length - 1);
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>' +
            '<sheetFormatPr defaultColWidth="17" defaultRowHeight="15"/>' + '<sheetData>' + body + '</sheetData>' +
            '<autoFilter ref="A1:' + last + entries.length + '"/></worksheet>';
    }

    function saveBlob(blob, filename) {
        if (root.navigator && typeof root.navigator.msSaveOrOpenBlob === 'function') {
            root.navigator.msSaveOrOpenBlob(blob, filename);
            return;
        }
        if (root.document && root.URL && typeof root.URL.createObjectURL === 'function') {
            var url = root.URL.createObjectURL(blob);
            var link = root.document.createElement('a');
            link.href = url;
            link.download = filename;
            link.style.display = 'none';
            root.document.body.appendChild(link);
            link.click();
            root.document.body.removeChild(link);
            root.setTimeout(function () { root.URL.revokeObjectURL(url); }, 1000);
            return;
        }
        if (root.jQuery && root.jQuery.fn.dataTable &&
            typeof root.jQuery.fn.dataTable.fileSave === 'function') {
            root.jQuery.fn.dataTable.fileSave(blob, filename);
            return;
        }
        throw new Error('Trình duyệt không hỗ trợ tải file.');
    }

    function download(sheets, filename) {
        if (!root.JSZip) {
            throw new Error('Trình duyệt chưa tải thư viện tạo Excel.');
        }
        var zip = new root.JSZip();
        var types = [], sheetTags = [], relationships = [];
        sheets.forEach(function (sheet, index) {
            var id = index + 1;
            types.push('<Override PartName="/xl/worksheets/sheet' + id + '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>');
            sheetTags.push('<sheet name="' + xmlText(sheet.name.substring(0, 31)) + '" sheetId="' + id + '" r:id="rId' + id + '"/>');
            relationships.push('<Relationship Id="rId' + id + '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' + id + '.xml"/>');
            zip.file('xl/worksheets/sheet' + id + '.xml', worksheet(sheet));
        });
        zip.file('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' + types.join('') + '</Types>');
        zip.file('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        zip.file('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>' + sheetTags.join('') + '</sheets></workbook>');
        zip.file('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' + relationships.join('') + '<Relationship Id="rId' + (sheets.length + 1) + '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        zip.file('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="_(* #,##0_);_(* \\(#,##0\\);_(* &quot;-&quot;_);_(@_)"/></numFmts><fonts count="2"><font><sz val="11"/><name val="Arial"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Arial"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF337AB7"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/><xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
        var options = { type: 'blob', mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' };
        if (typeof zip.generateAsync === 'function') {
            return zip.generateAsync(options).then(function (blob) {
                saveBlob(blob, filename);
            });
        }
        var deferred = root.jQuery.Deferred();
        try {
            saveBlob(zip.generate(options), filename);
            deferred.resolve();
        } catch (error) { deferred.reject(error); }
        return deferred.promise();
    }

    root.hosoExcelExport = { buildDaySheets: buildDaySheets, buildMonthSheets: buildMonthSheets, download: download };
})(typeof window !== 'undefined' ? window : this);
