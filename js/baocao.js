(function ($) {
    var reportChart;
    function renderReportChart(rows) {
        if (typeof Chart === 'undefined' || !document.getElementById('report-error-chart')) return;
        var grouped = {}, ruleNames = {}, ruleSeverity = {}, ruleCosts = {}, ruleFiles = {};
        (rows || []).forEach(function (row) {
            var id = row.rule_id == null || row.rule_id === '' ? 'unknown' : String(row.rule_id);
            var key = id;
            ruleNames[key] = row.error || row.message || row.rule_name || row.rule_type || 'Lỗi validation';
            if (String(row.severity || 'error').toLowerCase() === 'warning') ruleSeverity[key] = 'warning';
            else if (!ruleSeverity[key]) ruleSeverity[key] = 'error';
            grouped[key] = (grouped[key] || 0) + 1;
            if (!ruleFiles[key]) ruleFiles[key] = {};
            var fileKey = String(row.ma_lk || row.ma_bn || '');
            if (fileKey && !ruleFiles[key][fileKey]) {
                ruleFiles[key][fileKey] = true;
                ruleCosts[key] = (ruleCosts[key] || 0) + (Number(row.total_amount) || 0);
            }
        });
        var items = Object.keys(grouped).sort(function (a, b) { return grouped[b] - grouped[a]; });
        var totalCount = Object.keys(grouped).reduce(function (sum, key) { return sum + grouped[key]; }, 0);
        if (reportChart) reportChart.destroy();
        $('#report-chart-empty').toggle(items.length === 0).text(items.length ? '' : 'Không có lỗi hoặc cảnh báo trong thời gian đã chọn.');
        if (!items.length) return;
        reportChart = new Chart(document.getElementById('report-error-chart').getContext('2d'), {
            type: 'bar',
            data: { labels: items.map(function (x) { return x === 'unknown' ? 'Rule: ?' : 'Rule: ' + x; }), datasets: [{ label: 'Tỷ lệ phát sinh', data: items.map(function (x) { return totalCount ? grouped[x] * 100 / totalCount : 0; }), backgroundColor: items.map(function (x) { return ruleSeverity[x] === 'warning' ? '#f0ad4e' : '#337ab7'; }) }] },
            options: { responsive: true, maintainAspectRatio: false, legend: { display: false }, scales: { yAxes: [{ ticks: { beginAtZero: true, stepSize: 10, callback: function (value) { return value + '%'; } }, scaleLabel: { display: true, labelString: 'Tỷ lệ (%)' } }] },
                tooltips: { callbacks: { label: function (tooltipItem) {
                    var index = tooltipItem && tooltipItem.index != null ? tooltipItem.index : -1;
                    var id = index >= 0 && items[index] ? items[index] : 'unknown';
                    var percentage = totalCount ? (grouped[id] * 100 / totalCount) : 0;
                    var cost = Number(ruleCosts[id] || 0).toLocaleString('vi-VN');
                    return ' ' + (ruleNames[id] || 'Rule ' + id) + ': ' + grouped[id] + ' lượt (' + percentage.toFixed(1) + '%) | Chi phí ước tính: ' + cost + ' đồng';
                } } } }
        });
    }
    function renderReportFiles(files) {
        var box = $('#report-file-list').empty();
        files = files || [];
        var statusCounts = { CHUA_SUA: 0, DA_SUA: 0 };
        files.forEach(function (file) {
            var status = String(file.correction_status || (Number(file.error_count || 0) > 0 ? 'CHUA_SUA' : 'KHONG_LOI'));
            if (status === 'CHUA_SUA' || status === 'DA_SUA') statusCounts[status]++;
        });
        $('#report-stat-total').text(files.length);
        $('#report-stat-unfixed').text(statusCounts.CHUA_SUA);
        $('#report-stat-fixed').text(statusCounts.DA_SUA);
        if (!files.length) { box.html('<div class="alert alert-warning">Không có file trong phạm vi đã chọn.</div>'); return; }
        function esc(value) { return $('<div>').text(value == null ? '' : value).html(); }
        function dateVi(value) {
            var text = String(value == null ? '' : value).replace(/\D/g, '');
            if (text.length >= 8) return text.substring(6, 8) + '/' + text.substring(4, 6) + '/' + text.substring(0, 4);
            return value == null ? '' : value;
        }
        files = files.slice().sort(function (a, b) {
            var levelA = Number(a.error_count || 0) > 0 ? 2 : (Number(a.warning_count || 0) > 0 ? 1 : 0);
            var levelB = Number(b.error_count || 0) > 0 ? 2 : (Number(b.warning_count || 0) > 0 ? 1 : 0);
            return levelB - levelA;
        });
        var html = '<h4>Danh sách file theo phạm vi thống kê (' + files.length + ')</h4><div class="table-responsive"><table id="report-file-table" class="table table-bordered table-striped table-condensed"><thead><tr><th>STT</th><th>Mã liên kết</th><th>Mã BN</th><th>Điều trị từ</th><th>Điều trị đến</th><th>Lỗi phát sinh</th><th>Trạng thái</th><th>Số tiền</th></tr></thead><tbody>';
        files.forEach(function (file, index) {
            var rowClass = Number(file.error_count || 0) > 0 ? 'report-file-error' : (Number(file.warning_count || 0) > 0 ? 'report-file-warning' : '');
            var status = String(file.correction_status || (Number(file.error_count || 0) > 0 ? 'CHUA_SUA' : 'KHONG_LOI'));
            var statusText = status === 'CHUA_SUA' ? 'Chưa sửa' : (status === 'DA_SUA' ? 'Đã sửa' : 'Không lỗi');
            var statusClass = status === 'CHUA_SUA' ? 'label-danger' : (status === 'DA_SUA' ? 'label-success' : 'label-default');
            var errorDetail = file.error_fields || file.previous_error_detail || '';
            html += '<tr class="' + rowClass + '" data-file-status="' + (rowClass || 'normal') + '"><td>' + (index + 1) + '</td><td>' + esc(file.ma_lk) + '</td><td>' + esc(file.ma_bn) + '</td><td>' + esc(dateVi(file.ngay_vao)) + '</td><td>' + esc(dateVi(file.ngay_ra)) + '</td><td>' + esc(errorDetail || '-') + '</td><td><span class="label ' + statusClass + '">' + statusText + '</span></td><td class="text-right">' + esc(file.total_amount) + '</td></tr>';
        });
        box.html(html + '</tbody></table></div>');
        if ($.fn.DataTable) {
            $('#report-file-table').DataTable({
                pageLength: 100,
                lengthChange: false,
                scrollY: '360px',
                scrollCollapse: true,
                searching: true,
                ordering: true,
                createdRow: function (row) {
                    var status = $(row).attr('data-file-status');
                    if (status === 'report-file-error') $(row).find('td').css({ backgroundColor: '#f8d7da', color: '#721c24' });
                    if (status === 'report-file-warning') $(row).find('td').css({ backgroundColor: '#fff3cd', color: '#856404' });
                },
                language: { search: 'Tìm:', info: 'Hiển thị _START_ đến _END_ trong _TOTAL_ file', infoEmpty: 'Không có file', zeroRecords: 'Không tìm thấy file' }
            });
        }
    }
    function exportXlsx(html, filename) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        function esc(v) { return String(v == null ? '' : v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
        function rowsFromTable(table) {
            return Array.prototype.map.call(table.querySelectorAll('tr'), function (tr) {
                return Array.prototype.map.call(tr.children, function (cell) { return cell.textContent; });
            });
        }
        function worksheetXml(rows) {
            var sheet = rows.map(function (row, r) { var moneyRow = row.some(function (v) { return /TỔNG\s*(BHYT|BHTT)/i.test(String(v || '')); }); return '<row r="' + (r + 1) + '">' + row.map(function (v, c) { var col = '', n = c + 1; while (n) { var x = (n - 1) % 26; col = String.fromCharCode(65 + x) + col; n = Math.floor((n - 1) / 26); } var text = String(v == null ? '' : v).trim(); if (/^-?\d+(\.\d+)?$/.test(text)) return '<c r="' + col + (r + 1) + '"' + (moneyRow && c > 1 ? ' s="1"' : '') + '><v>' + text + '</v></c>'; return '<c r="' + col + (r + 1) + '" t="inlineStr"><is><t>' + esc(v) + '</t></is></c>'; }).join('') + '</row>'; }).join('');
            return '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' + sheet + '</sheetData></worksheet>';
        }
        var tables = Array.prototype.slice.call(doc.querySelectorAll('table'));
        var hasNamedSheets = tables.some(function (table) { return table.getAttribute('data-sheet-name'); });
        var sheets = hasNamedSheets ? tables.map(function (table, index) {
            return { name: table.getAttribute('data-sheet-name') || ('Sheet ' + (index + 1)), rows: rowsFromTable(table) };
        }) : [{ name: 'Bao cao', rows: tables.reduce(function (all, table) { return all.concat(rowsFromTable(table)); }, []) }];
        var zip = new JSZip();
        var contentTypes = [], workbookSheets = [], relationships = [];
        sheets.forEach(function (sheet, index) {
            var number = index + 1;
            var safeName = String(sheet.name || ('Sheet ' + number)).replace(/[\\\/?*\[\]:]/g, ' ').substring(0, 31) || ('Sheet ' + number);
            contentTypes.push('<Override PartName="/xl/worksheets/sheet' + number + '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>');
            workbookSheets.push('<sheet name="' + esc(safeName) + '" sheetId="' + number + '" r:id="rId' + number + '"/>');
            relationships.push('<Relationship Id="rId' + number + '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' + number + '.xml"/>');
            zip.file('xl/worksheets/sheet' + number + '.xml', worksheetXml(sheet.rows));
        });
        relationships.push('<Relationship Id="rId' + (sheets.length + 1) + '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>');
        zip.file('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' + contentTypes.join('') + '</Types>');
        zip.file('_rels/.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        zip.file('xl/workbook.xml', '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>' + workbookSheets.join('') + '</sheets></workbook>');
        zip.file('xl/_rels/workbook.xml.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' + relationships.join('') + '</Relationships>');
        zip.file('xl/styles.xml', '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0"/></numFmts><fonts count="1"><font><sz val="11"/><name val="Arial"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="164" fontId="0" fillId="0" borderId="0" applyNumberFormat="1"/></cellXfs></styleSheet>');
        var save = function (blob) { var a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = filename; a.click(); window.setTimeout(function () { URL.revokeObjectURL(a.href); }, 1000); };
        if (typeof zip.generateAsync === 'function') {
            zip.generateAsync({type: 'blob', mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'}).then(save);
        } else {
            save(zip.generate({type: 'blob', mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'}));
        }
    }
    function reportFilename(value) {
        var filename = 'bao-cao-' + value;
        if ($('#report-scope').val() === 'khoa') {
            var khoaName = $('#report-khoa option:selected').text().trim();
            if (khoaName && selectedReportKhoa()) {
                khoaName = khoaName.replace(/[\\/:*?"<>|]/g, '').replace(/\s+/g, '-');
                filename += '-khoa-' + khoaName;
            }
        }
        return filename + '.xlsx';
    }
    function selectedReportKhoa() {
        var value = String($('#report-khoa').val() || '').trim();
        return value && value !== '0' ? value : '';
    }
    function reportIsoDate(raw) {
        var match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(String(raw || '').trim());
        if (!match) return '';
        var date = new Date(Number(match[3]), Number(match[2]) - 1, Number(match[1]));
        if (date.getFullYear() !== Number(match[3]) || date.getMonth() !== Number(match[2]) - 1 || date.getDate() !== Number(match[1])) return '';
        return match[3] + '-' + match[2] + '-' + match[1];
    }
    function selectedReportPeriod() {
        var type = $('#report-type').val();
        if (type === 'range') {
            var from = reportIsoDate($('#report-from-date').val());
            var to = reportIsoDate($('#report-to-date').val());
            if (!from || !to) return { error: 'Vui lòng chọn đầy đủ từ ngày và đến ngày.' };
            if (from > to) return { error: 'Từ ngày không được lớn hơn đến ngày.' };
            return { type: type, value: from + '-den-' + to, params: { date: from, from_date: from, to_date: to, period: type } };
        }
        if (type === 'month') {
            var month = /^(\d{2})\/(\d{4})$/.exec(String($('#report-month').val() || '').trim());
            if (!month || Number(month[1]) < 1 || Number(month[1]) > 12) return { error: 'Tháng được chọn không hợp lệ.' };
            return { type: type, value: month[2] + '-' + month[1], params: { date: month[2] + '-' + month[1], period: type } };
        }
        var date = reportIsoDate($('#report-date').val());
        if (!date) return { error: 'Ngày được chọn không hợp lệ.' };
        return { type: 'day', value: date, params: { date: date, period: 'day' } };
    }
    var now = new Date();
    if ($.fn.datepicker) {
        $.fn.datepicker.dates.vi = { days: ['Chủ nhật','Thứ hai','Thứ ba','Thứ tư','Thứ năm','Thứ sáu','Thứ bảy'], daysShort: ['CN','T2','T3','T4','T5','T6','T7'], daysMin: ['CN','T2','T3','T4','T5','T6','T7'], months: ['Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6','Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12'], monthsShort: ['T1','T2','T3','T4','T5','T6','T7','T8','T9','T10','T11','T12'], today: 'Hôm nay', clear: 'Xóa', titleFormat: 'MM yyyy' };
        $('#report-date, #report-from-date, #report-to-date').datepicker({ language: 'vi', autoclose: true, todayHighlight: true, format: 'dd/mm/yyyy' }).datepicker('setDate', now);
        $('#report-month').datepicker({ language: 'vi', autoclose: true, minViewMode: 'months', format: 'mm/yyyy' }).datepicker('setDate', now);
    } else {
        $('#report-date, #report-from-date, #report-to-date').val(String(now.getDate()).padStart(2, '0') + '/' + String(now.getMonth() + 1).padStart(2, '0') + '/' + now.getFullYear());
        $('#report-month').val(String(now.getMonth() + 1).padStart(2, '0') + '/' + now.getFullYear());
    }
    $('#report-type').on('change', function () {
        var month = this.value === 'month';
        var range = this.value === 'range';
        $('#report-single-time-wrap').toggle(!range);
        $('#report-range-wrap').toggle(range);
        $('#report-date').toggle(!month);
        $('#report-month').toggle(month);
    });
    $('#report-scope').on('change', function () {
        var byDepartment = this.value === 'khoa';
        $('#report-khoa-wrap').css('visibility', byDepartment ? 'visible' : 'hidden');
        if (!byDepartment) $('#report-khoa').val('0');
    }).trigger('change');
    $('#btn-show-chart').on('click', function () {
        if ($('#report-scope').val() === 'khoa' && !selectedReportKhoa()) {
            $('#report-message').text('Vui lòng chọn khoa cần thống kê.').removeClass('alert-info alert-success').addClass('alert-danger');
            return;
        }
        var selection = selectedReportPeriod();
        if (selection.error) {
            $('#report-message').text(selection.error).removeClass('alert-info alert-success').addClass('alert-danger');
            return;
        }
        var params = $.extend({}, selection.params, { report_scope: $('#report-scope').val(), report_khoa: selectedReportKhoa() });
        $.getJSON('../baocao/getErrorReport/', params)
            .done(function (r) {
                if (!r.success) {
                    $('#report-message').text(r.message || 'Không thể tải dữ liệu biểu đồ.').removeClass('alert-info alert-success').addClass('alert-danger');
                    return;
                }
                renderReportChart(r.data || []);
                renderReportFiles(r.files || []);
                $('#report-message').text('Đã cập nhật biểu đồ.').removeClass('alert-danger alert-info').addClass('alert-success');
            })
            .fail(function () {
                $('#report-message').text('Không thể tải dữ liệu biểu đồ.').removeClass('alert-info alert-success').addClass('alert-danger');
            });
    });
    $('#btn-export-report').on('click', function () {
        if ($('#report-scope').val() === 'khoa' && !selectedReportKhoa()) {
            $('#report-message').text('Vui lòng chọn khoa cần xuất báo cáo.').removeClass('alert-info alert-success').addClass('alert-danger');
            return;
        }
        var selection = selectedReportPeriod();
        if (selection.error) { $('#report-message').text(selection.error).removeClass('alert-info alert-success').addClass('alert-danger'); return; }
        var type = selection.type, value = selection.value;
        var button = $(this).prop('disabled', true);
        var params = $.extend({}, selection.params, { report_scope: $('#report-scope').val(), report_khoa: selectedReportKhoa() });
        $.getJSON('../baocao/getErrorReport/', params)
            .done(function (response) {
                if (!response.success) { $('#report-message').text(response.message).addClass('alert-danger'); return; }
                function cell(value) { return '<td>' + String(value == null ? '' : value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</td>'; }
                var html = '<html><head><meta charset="utf-8"></head><body>';
                if (type === 'month') {
                    var evaluation = { total: 0, fixed: 0, unfixed: 0 };
                    (response.files || []).forEach(function (file) {
                        evaluation.total++;
                        var status = String(file.correction_status || (Number(file.error_count || 0) > 0 ? 'CHUA_SUA' : 'KHONG_LOI'));
                        if (status === 'DA_SUA') evaluation.fixed++;
                        if (status === 'CHUA_SUA') evaluation.unfixed++;
                    });
                    var evaluatedTotal = evaluation.fixed + evaluation.unfixed;
                    var completionRate = evaluatedTotal ? (evaluation.fixed * 100 / evaluatedTotal).toFixed(1) + '%' : '100%';
                    html += '<table data-sheet-name="ĐÁNH GIÁ" border="1"><tr><th>CHỈ TIÊU</th><th>GIÁ TRỊ</th></tr>'
                        + '<tr>' + cell('TỔNG FILE') + cell(evaluation.total) + '</tr>'
                        + '<tr>' + cell('ĐÃ SỬA') + cell(evaluation.fixed) + '</tr>'
                        + '<tr>' + cell('CHƯA SỬA') + cell(evaluation.unfixed) + '</tr>'
                        + '<tr>' + cell('TỶ LỆ HOÀN THÀNH') + cell(completionRate) + '</tr>'
                        + '<tr>' + cell('KẾT LUẬN') + cell(evaluation.unfixed ? ('CÒN ' + evaluation.unfixed + ' FILE CHƯA SỬA') : 'ĐÃ HOÀN TẤT') + '</tr></table>';
                    var dates = [], grouped = {};
                    (response.data || []).forEach(function (r) {
                        var d = r.date || 'Không rõ ngày';
                        if (dates.indexOf(d) < 0) dates.push(d);
                        var e = r.error || 'Lỗi validation';
                        if (!grouped[e]) grouped[e] = {};
                        grouped[e][d] = (grouped[e][d] || 0) + 1;
                    });
                    dates.sort(function (a, b) { return a.split('.').reverse().join('').localeCompare(b.split('.').reverse().join('')); });
                    html += '<h2>BÁO CÁO THEO THÁNG ' + value + '</h2><table data-sheet-name="BÁO CÁO THÁNG" border="1"><tr><th>STT</th><th>TÊN LỖI</th>' + dates.map(function (d) { return '<th>' + d + '</th>'; }).join('') + '</tr>';
                    Object.keys(grouped).sort().forEach(function (e, i) { html += '<tr>' + cell(i + 1) + cell(e) + dates.map(function (d) { return cell(grouped[e][d] || 0); }).join('') + '</tr>'; });
                    html += '<tr>' + cell('') + cell('TỔNG FILE LỖI THEO NGÀY') + dates.map(function (d) { return cell((response.data || []).filter(function (r) { return (r.date || 'Không rõ ngày') === d; }).length); }).join('') + '</tr>';
                    html += '<tr>' + cell('') + cell('TỔNG BHTT') + dates.map(function (d) { var total = 0; (response.files || []).forEach(function (f) { if (f.date === d) total += Number(f.total_amount) || 0; }); return cell(total); }).join('') + '</tr></table></body></html>';
                    exportXlsx(html, reportFilename(value));
                    $('#report-message').text('Đã xuất báo cáo tháng thành công.').removeClass('alert-danger').addClass('alert-success');
                    return;
                }
                if (window.exportErrorReportSheets) {
                    window.exportErrorReportSheets(response.data || [], response.files || [], reportFilename(value));
                    $('#report-message').text('Đã xuất báo cáo thành công.').removeClass('alert-danger').addClass('alert-success');
                    return;
                }
                html += '<h2>BÁO CÁO LỖI</h2><table border="1"><tr><th>STT</th><th>Mã liên kết</th><th>XML</th><th>Trường lỗi</th><th>Giá trị lỗi</th><th>Lỗi</th><th>Ngày</th></tr>';
                (response.data || []).forEach(function (r, i) { html += '<tr>' + [i + 1, r.ma_lk, r.xml, r.field, r.value, r.error, r.date].map(cell).join('') + '</tr>'; });
                html += '</table><br><h2>TỔNG HỢP FILE</h2><table border="1"><tr><th>STT</th><th>Mã liên kết</th><th>Mã BN</th><th>Mã thẻ BHYT</th><th>Điều trị từ</th><th>Điều trị đến</th><th>Số lượng lỗi</th><th>Số tiền</th></tr>';
                (response.files || []).forEach(function (r, i) { html += '<tr>' + [i + 1, r.ma_lk, r.ma_bn, r.ma_the_bhyt, r.ngay_vao, r.ngay_ra, r.error_count, r.total_amount].map(cell).join('') + '</tr>'; });
                html += '</table></body></html>';
                exportXlsx(html, reportFilename(value));
                $('#report-message').text('Đã xuất báo cáo thành công.').removeClass('alert-danger').addClass('alert-success');
            }).fail(function () { $('#report-message').text('Không thể tải dữ liệu báo cáo.').addClass('alert-danger'); })
            .always(function () { button.prop('disabled', false); });
    });
})(jQuery);
