(function ($) {
    function exportXlsx(html, filename) {
        var doc = new DOMParser().parseFromString(html, 'text/html'), rows = [];
        doc.querySelectorAll('table tr').forEach(function (tr) { rows.push(Array.prototype.map.call(tr.children, function (c) { return c.textContent; })); });
        function esc(v) { return String(v == null ? '' : v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
        var sheet = rows.map(function (row, r) { var moneyRow = row.some(function (v) { return /TỔNG\s*(BHYT|BHTT)/i.test(String(v || '')); }); return '<row r="' + (r + 1) + '">' + row.map(function (v, c) { var col = '', n = c + 1; while (n) { var x = (n - 1) % 26; col = String.fromCharCode(65 + x) + col; n = Math.floor((n - 1) / 26); } var text = String(v == null ? '' : v).trim(); if (/^-?\d+(\.\d+)?$/.test(text)) return '<c r="' + col + (r + 1) + '"' + (moneyRow && c > 1 ? ' s="1"' : '') + '><v>' + text + '</v></c>'; return '<c r="' + col + (r + 1) + '" t="inlineStr"><is><t>' + esc(v) + '</t></is></c>'; }).join('') + '</row>'; }).join('');
        var zip = new JSZip();
        zip.file('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        zip.file('_rels/.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        zip.file('xl/workbook.xml', '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Bao cao" sheetId="1" r:id="rId1"/></sheets></workbook>');
        zip.file('xl/_rels/workbook.xml.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        zip.file('xl/styles.xml', '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0"/></numFmts><fonts count="1"><font><sz val="11"/><name val="Arial"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="164" fontId="0" fillId="0" borderId="0" applyNumberFormat="1"/></cellXfs></styleSheet>');
        zip.file('xl/worksheets/sheet1.xml', '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' + sheet + '</sheetData></worksheet>');
        var save = function (blob) { var a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = filename; a.click(); window.setTimeout(function () { URL.revokeObjectURL(a.href); }, 1000); };
        if (typeof zip.generateAsync === 'function') {
            zip.generateAsync({type: 'blob', mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'}).then(save);
        } else {
            save(zip.generate({type: 'blob', mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'}));
        }
    }
    var now = new Date();
    if ($.fn.datepicker) {
        $.fn.datepicker.dates.vi = { days: ['Chủ nhật','Thứ hai','Thứ ba','Thứ tư','Thứ năm','Thứ sáu','Thứ bảy'], daysShort: ['CN','T2','T3','T4','T5','T6','T7'], daysMin: ['CN','T2','T3','T4','T5','T6','T7'], months: ['Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6','Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12'], monthsShort: ['T1','T2','T3','T4','T5','T6','T7','T8','T9','T10','T11','T12'], today: 'Hôm nay', clear: 'Xóa', titleFormat: 'MM yyyy' };
        $('#report-date').datepicker({ language: 'vi', autoclose: true, todayHighlight: true, format: 'dd/mm/yyyy' }).datepicker('setDate', now);
        $('#report-month').datepicker({ language: 'vi', autoclose: true, minViewMode: 'months', format: 'mm/yyyy' }).datepicker('setDate', now);
    } else {
        $('#report-date').val(String(now.getDate()).padStart(2, '0') + '/' + String(now.getMonth() + 1).padStart(2, '0') + '/' + now.getFullYear());
        $('#report-month').val(String(now.getMonth() + 1).padStart(2, '0') + '/' + now.getFullYear());
    }
    $('#report-type').on('change', function () {
        var month = this.value === 'month';
        $('#report-date').toggle(!month);
        $('#report-month').toggle(month);
    });
    $('#btn-export-report').on('click', function () {
        var type = $('#report-type').val();
        var raw = type === 'month' ? $('#report-month').val() : $('#report-date').val();
        var parts = raw.split('/');
        var value = type === 'month' ? (parts[1] + '-' + parts[0]) : (parts[2] + '-' + parts[1] + '-' + parts[0]);
        if (!value) { $('#report-message').text('Vui lòng chọn thời gian.').addClass('alert-danger'); return; }
        var button = $(this).prop('disabled', true);
        $.getJSON('../baocao/getErrorReport/', { date: value, period: type })
            .done(function (response) {
                if (!response.success) { $('#report-message').text(response.message).addClass('alert-danger'); return; }
                function cell(value) { return '<td>' + String(value == null ? '' : value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</td>'; }
                var html = '<html><head><meta charset="utf-8"></head><body>';
                if (type === 'month') {
                    var dates = [], grouped = {};
                    (response.data || []).forEach(function (r) {
                        var d = r.date || 'Không rõ ngày';
                        if (dates.indexOf(d) < 0) dates.push(d);
                        var e = r.error || 'Lỗi validation';
                        if (!grouped[e]) grouped[e] = {};
                        grouped[e][d] = (grouped[e][d] || 0) + 1;
                    });
                    dates.sort(function (a, b) { return a.split('.').reverse().join('').localeCompare(b.split('.').reverse().join('')); });
                    html += '<h2>BÁO CÁO THEO THÁNG ' + value + '</h2><table border="1"><tr><th>STT</th><th>TÊN LỖI</th>' + dates.map(function (d) { return '<th>' + d + '</th>'; }).join('') + '</tr>';
                    Object.keys(grouped).sort().forEach(function (e, i) { html += '<tr>' + cell(i + 1) + cell(e) + dates.map(function (d) { return cell(grouped[e][d] || 0); }).join('') + '</tr>'; });
                    html += '<tr>' + cell('') + cell('TỔNG FILE LỖI THEO NGÀY') + dates.map(function (d) { return cell((response.data || []).filter(function (r) { return (r.date || 'Không rõ ngày') === d; }).length); }).join('') + '</tr>';
                    html += '<tr>' + cell('') + cell('TỔNG BHTT') + dates.map(function (d) { var total = 0; (response.files || []).forEach(function (f) { if (f.date === d) total += Number(f.total_amount) || 0; }); return cell(total); }).join('') + '</tr></table></body></html>';
                    exportXlsx(html, 'bao-cao-' + value + '.xlsx');
                    $('#report-message').text('Đã xuất báo cáo tháng thành công.').removeClass('alert-danger').addClass('alert-success');
                    return;
                }
                if (window.exportErrorReportSheets) {
                    window.exportErrorReportSheets(response.data || [], response.files || [], 'bao-cao-' + value + '.xlsx');
                    $('#report-message').text('Đã xuất báo cáo thành công.').removeClass('alert-danger').addClass('alert-success');
                    return;
                }
                html += '<h2>BÁO CÁO LỖI</h2><table border="1"><tr><th>STT</th><th>Mã liên kết</th><th>XML</th><th>Trường lỗi</th><th>Giá trị lỗi</th><th>Lỗi</th><th>Ngày</th></tr>';
                (response.data || []).forEach(function (r, i) { html += '<tr>' + [i + 1, r.ma_lk, r.xml, r.field, r.value, r.error, r.date].map(cell).join('') + '</tr>'; });
                html += '</table><br><h2>TỔNG HỢP FILE</h2><table border="1"><tr><th>STT</th><th>Mã liên kết</th><th>Mã BN</th><th>Mã thẻ BHYT</th><th>Điều trị từ</th><th>Điều trị đến</th><th>Số lượng lỗi</th><th>Số tiền</th></tr>';
                (response.files || []).forEach(function (r, i) { html += '<tr>' + [i + 1, r.ma_lk, r.ma_bn, r.ma_the_bhyt, r.ngay_vao, r.ngay_ra, r.error_count, r.total_amount].map(cell).join('') + '</tr>'; });
                html += '</table></body></html>';
                exportXlsx(html, 'bao-cao-' + value + '.xlsx');
                $('#report-message').text('Đã xuất báo cáo thành công.').removeClass('alert-danger').addClass('alert-success');
            }).fail(function () { $('#report-message').text('Không thể tải dữ liệu báo cáo.').addClass('alert-danger'); })
            .always(function () { button.prop('disabled', false); });
    });
})(jQuery);
