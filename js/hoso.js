(function ($) {
    'use strict';

    var baseUrl = $('#ULocal').val();
    var csrf = $('#hoso-csrf').val();
    var deletingAll = false;
    var currentRows = [];
    var searchTimer = null;
    var activeRequest = null;
    var loadingRows = false;
    var exporting = false;

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function formatTreatmentDate(value) {
        var digits = String(value == null ? '' : value).replace(/\D/g, '');
        if (digits.length < 8) return value || '';
        var result = digits.substr(6, 2) + '/' + digits.substr(4, 2) + '/' + digits.substr(0, 4);
        if (digits.length >= 12) result += ' ' + digits.substr(8, 2) + ':' + digits.substr(10, 2);
        return result;
    }

    function numberValue(value) {
        var amount = Number(String(value == null ? '0' : value).replace(/[^0-9.-]/g, ''));
        return isFinite(amount) ? amount : 0;
    }

    function selectedDate() {
        var match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec($('#hoso-date').val());
        return match ? match[3] + '-' + match[2] + '-' + match[1] : '';
    }

    function selectedMonth() {
        var match = /^(\d{2})\/(\d{4})$/.exec($('#hoso-export-month').val());
        return match ? match[2] + '-' + match[1] : '';
    }

    function displayDate(isoDate) {
        var match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(isoDate || ''));
        return match ? match[3] + '/' + match[2] + '/' + match[1] : '';
    }

    function searchText() {
        return $.trim($('#hoso-search').val());
    }

    function message(text, isError) {
        $('#hoso-message').toggleClass('alert-danger', !!isError)
            .toggleClass('alert-success', !isError).text(text).show();
    }

    function updateCounts() {
        var removable = currentRows.filter(function (row) { return !row.da_day_cong; }).length;
        $('#hoso-total').text(currentRows.length);
        $('#hoso-deletable').text(removable);
        // Xóa tất cả luôn chỉ áp dụng theo ngày; không xóa theo kết quả tìm toàn khoa.
        $('#hoso-delete-all').prop('disabled', deletingAll || loadingRows || !!searchText() || removable === 0);
        // Tháng được xuất bằng truy vấn riêng; ngày đang xem có thể chưa có hồ sơ.
        $('#hoso-export').prop('disabled', deletingAll || exporting);
    }

    if ($.fn.datepicker) {
        $.fn.datepicker.dates.vi = {
            days: ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'],
            daysShort: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
            daysMin: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
            months: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
                'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
            monthsShort: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
            today: 'Hôm nay', clear: 'Xóa', titleFormat: 'MM yyyy'
        };
        $('#hoso-date').datepicker({
            language: 'vi', autoclose: true, todayHighlight: true,
            format: 'dd/mm/yyyy', weekStart: 1
        });
        $('#hoso-export-month').datepicker({
            language: 'vi', autoclose: true, minViewMode: 'months',
            format: 'mm/yyyy'
        });
    }

    var table = $('#hoso-table').DataTable({
        ajax: {
            url: baseUrl + 'hoso/getData/',
            type: 'POST',
            data: function (data) {
                data.date = selectedDate();
                data.search = searchText();
            },
            beforeSend: function (xhr) {
                if (activeRequest && activeRequest.readyState !== 4) activeRequest.abort();
                activeRequest = xhr;
            },
            dataSrc: function (response) {
                currentRows = response && $.isArray(response.data) ? response.data : [];
                loadingRows = false;
                if (response && response.error) message(response.error, true);
                updateCounts();
                return currentRows;
            },
            error: function (xhr) {
                if (xhr.statusText === 'abort') return;
                currentRows = [];
                loadingRows = false;
                updateCounts();
                message(xhr.status === 401 ? 'Phiên đăng nhập đã hết hạn.' : 'Không thể tải danh sách hồ sơ.', true);
            }
        },
        paging: true,
        pageLength: 100,
        lengthMenu: [[50, 100, 250, 500], [50, 100, 250, 500]],
        autoWidth: false,
        order: [],
        dom: 'lrtip',
        columns: [
            { data: null, orderable: false, searchable: false, className: 'text-center',
                render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
            { data: 'ma_bn', defaultContent: '', render: function (value, type) {
                return type === 'display' ? escapeHtml(value) : (value || '');
            } },
            { data: 'ma_the_bhyt', defaultContent: '', render: function (value, type) {
                return type === 'display' ? escapeHtml(value) : (value || '');
            } },
            { data: 'dia_chi', defaultContent: '', className: 'hoso-address', render: function (value, type) {
                return type === 'display' ? escapeHtml(value) : (value || '');
            } },
            { data: 'ngay_sinh', defaultContent: '', className: 'text-center', render: function (value, type) {
                return type === 'display' || type === 'export' ? escapeHtml(patientBirth(value)) : (value || '');
            } },
            { data: 'gioi_tinh', defaultContent: '', className: 'text-center', render: function (value, type) {
                return type === 'display' || type === 'export' ? escapeHtml(patientGender(value)) : (value || '');
            } },
            { data: 'ngay_vao', defaultContent: '', className: 'text-center', render: function (value, type) {
                return type === 'display' || type === 'export' ? escapeHtml(formatTreatmentDate(value)) : (value || '');
            } },
            { data: 'ngay_ra', defaultContent: '', className: 'text-center', render: function (value, type) {
                return type === 'display' || type === 'export' ? escapeHtml(formatTreatmentDate(value)) : (value || '');
            } },
            { data: 'error_count', className: 'text-center', render: function (value, type) {
                var count = Number(value) || 0;
                return type === 'display' && count ? '<span class="label label-danger">' + count + '</span>' : count;
            } },
            { data: 'error_fields', defaultContent: '', className: 'hoso-error-fields', render: function (value, type) {
                return type === 'display' ? escapeHtml(value) : (value || '');
            } },
            { data: 'total_amount', className: 'text-right', render: function (value, type) {
                var amount = numberValue(value);
                return type === 'display' ? amount.toLocaleString('vi-VN') + ' đ' : amount;
            } },
            { data: null, orderable: false, searchable: false, className: 'text-center', render: function (data, type, row) {
                if (type !== 'display') return '';
                var id = Number(row.id);
                var view = '<button type="button" class="btn btn-info btn-sm hoso-view" data-id="' + id + '" title="Xem lỗi và cảnh báo"><i class="fa fa-eye"></i></button>';
                if (row.da_day_cong) return view + ' <span class="label label-success">Đã đẩy cổng</span>';
                return view + ' <button type="button" class="btn btn-danger btn-sm hoso-delete" data-id="' + id + '" data-date="' + escapeHtml(row.update_date) + '" title="Xóa hồ sơ"><i class="fa fa-trash"></i></button>';
            } }
        ],
        createdRow: function (tr, row) {
            $(tr).toggleClass('file-row-has-error', Number(row.error_count) > 0)
                .toggleClass('file-row-has-warning', Number(row.error_count) === 0 && Number(row.warning_count) > 0);
        },
        language: {
            zeroRecords: 'Không có hồ sơ XML',
            emptyTable: 'Không có hồ sơ XML phù hợp',
            info: 'Hiển thị _START_ đến _END_ trong _TOTAL_ hồ sơ',
            infoEmpty: 'Không có dữ liệu',
            infoFiltered: '(lọc từ _MAX_ hồ sơ)',
            lengthMenu: 'Hiển thị _MENU_ dòng',
            paginate: { first: 'Đầu', last: 'Cuối', next: 'Sau', previous: 'Trước' }
        }
    });

    $('#hoso-date').on('change', function () {
        $('#hoso-message').hide();
        if (searchText()) return; // Khi đang tìm, không giới hạn ngày.
        currentRows = [];
        loadingRows = true;
        updateCounts();
        table.ajax.reload();
    });
    $('#hoso-search').on('input', function () {
        clearTimeout(searchTimer);
        loadingRows = true;
        updateCounts();
        searchTimer = setTimeout(function () {
            $('#hoso-message').hide();
            table.ajax.reload();
        }, 300);
    });
    $('#hoso-refresh').on('click', function () {
        clearTimeout(searchTimer);
        $('#hoso-message').hide();
        loadingRows = true;
        updateCounts();
        table.ajax.reload(null, false);
    });
    $('#hoso-export-period').on('change', function () {
        var monthly = $(this).val() === 'month';
        $('#hoso-export-month').toggle(monthly).prop('disabled', !monthly);
    });
    $('#hoso-export').on('click', function () {
        if (exporting || deletingAll) return;
        if (!window.hosoExcelExport || !window.JSZip) {
            message('Trình duyệt chưa tải được thư viện xuất Excel.', true);
            return;
        }
        var period = $('#hoso-export-period').val();
        var date = period === 'month' ? selectedMonth() : selectedDate();
        if ((period === 'day' && !/^\d{4}-\d{2}-\d{2}$/.test(date)) ||
            (period === 'month' && !/^\d{4}-\d{2}$/.test(date))) {
            message('Vui lòng chọn ngày hoặc tháng cần xuất Excel.', true);
            return;
        }
        exporting = true;
        updateCounts();
        $('#hoso-message').hide();
        var exported = null;
        $.ajax({
            url: baseUrl + 'hoso/getExportData/',
            type: 'POST',
            dataType: 'json',
            data: { csrf_token: csrf, period: period, date: date }
        }).done(function (response) {
            if (!response || !response.success || !$.isArray(response.data) || !$.isArray(response.errors)) {
                message(response && response.message ? response.message : 'Không thể lấy dữ liệu xuất Excel.', true);
                return;
            }
            if (period === 'day' && !response.data.length) {
                message('Không có hồ sơ cập nhật trong ngày ' + date + '.', true);
                return;
            }
            try {
                var sheets = period === 'month'
                    ? window.hosoExcelExport.buildMonthSheets(date, response.errors, response.data)
                    : window.hosoExcelExport.buildDaySheets(response.data, response.errors);
                var filename = (period === 'month' ? 'thongkethang' : 'Thongkengay-') + date + '.xlsx';
                exported = window.hosoExcelExport.download(sheets, filename);
            } catch (error) {
                message('Không thể tạo file Excel: ' + error.message, true);
            }
            if (exported) exported.then(function () {
                message('Đã tạo Excel ' + (period === 'month' ? 'tổng hợp lỗi tháng ' : '2 sheet hồ sơ và lỗi ngày ') + date +
                    (period === 'month' && !response.errors.length ? ' (không có lỗi).' : '.'), false);
            }, function () {
                message('Không thể tạo file Excel. Vui lòng thử lại.', true);
            }).then(function () {
                exporting = false;
                updateCounts();
            });
        }).fail(function (xhr) {
            message(xhr.status === 401 ? 'Phiên đăng nhập đã hết hạn.' : 'Không thể tải dữ liệu xuất Excel.', true);
        }).always(function () {
            if (!exported) {
                exporting = false;
                updateCounts();
            }
        });
    });

    // Upload theo từng file vào hàng đợi chung, luôn gắn khoa theo tài khoản ở server.
    var uploading = false;
    var uploadPollTimer = null;
    var uploadGeneration = 0;

    function uploadMessage(text, isError) {
        $('#hoso-upload-message').toggleClass('alert-danger', !!isError)
            .toggleClass('alert-info', !isError).text(text).show();
    }

    function uploadProgressMessage(text, isError) {
        $('#hoso-upload-progress').toggleClass('alert-danger', !!isError)
            .toggleClass('alert-info', !isError).text(text).show();
    }

    function refreshUploadedRows() {
        // Hồ sơ mới có update_at theo ngày worker xử lý, không theo ngày đang lọc.
        if (!searchText() && selectedDate() !== $('#hoso-date').attr('data-today')) {
            $('#hoso-date').val(displayDate($('#hoso-date').attr('data-today')));
        }
        table.ajax.reload(null, false);
    }

    function watchUploadedFiles(names) {
        if (!names.length) return;
        var attempts = 0;
        var failures = 0;
        var generation = uploadGeneration;

        function poll() {
            if (generation !== uploadGeneration) return;
            $.ajax({
                url: baseUrl + 'hoso/uploadProgress/',
                type: 'POST', dataType: 'json',
                data: { csrf_token: csrf, files: JSON.stringify(names) }
            }).done(function (result) {
                if (generation !== uploadGeneration) return;
                if (!result || !result.success) {
                    uploadProgressMessage(result && result.message ? result.message : 'Không thể xem tiến độ xử lý XML.', true);
                    return;
                }
                failures = 0;
                var remaining = Number(result.remaining) || 0;
                if (remaining <= 0) {
                    uploadProgressMessage('Các file đã rời hàng đợi xử lý. Kiểm tra danh sách hồ sơ và kết quả kiểm tra XML.');
                    refreshUploadedRows();
                    return;
                }
                uploadProgressMessage('Đã đưa file vào hàng đợi. Đang chờ xử lý ' + remaining + '/' + names.length + ' file...');
                if (++attempts >= 120) {
                    uploadProgressMessage('Một số file vẫn đang chờ xử lý. Bạn có thể bấm Làm mới danh sách sau.', true);
                    return;
                }
                table.ajax.reload(null, false);
                uploadPollTimer = window.setTimeout(poll, 2500);
            }).fail(function () {
                if (generation !== uploadGeneration) return;
                if (++failures >= 3) {
                    uploadProgressMessage('Không thể kiểm tra tiến độ. File đã gửi vẫn đang được xử lý; hãy làm mới danh sách.', true);
                    return;
                }
                uploadPollTimer = window.setTimeout(poll, 3000);
            });
        }

        uploadPollTimer = window.setTimeout(poll, 1500);
    }

    function uploadXmlFiles(fileList) {
        if (uploading) return;
        var files = Array.prototype.slice.call(fileList || []);
        if (!files.length) return;
        if (files.length > 50) {
            uploadMessage('Chỉ được tải tối đa 50 file XML trong mỗi lượt.', true);
            return;
        }

        window.clearTimeout(uploadPollTimer);
        uploadGeneration++;
        uploading = true;
        $('#hoso-upload-progress').hide();
        $('#hoso-upload-zone').addClass('is-uploading');
        $('#hoso-open-upload').prop('disabled', true);
        var queued = [];
        var errors = [];
        var succeeded = 0;

        function finish() {
            uploading = false;
            $('#hoso-upload-zone').removeClass('is-uploading');
            $('#hoso-open-upload').prop('disabled', false);
            var summary = 'Đã gửi ' + succeeded + '/' + files.length + ' file vào hàng đợi xử lý.';
            if (errors.length) summary += '\n' + errors.join('\n');
            uploadMessage(summary, errors.length > 0);
            if (succeeded) {
                message('Đã tiếp nhận ' + succeeded + ' file XML vào hàng đợi xử lý nền.');
                watchUploadedFiles(queued);
            }
        }

        function next(index) {
            if (index === files.length) { finish(); return; }
            var file = files[index];
            uploadMessage('Đang gửi file ' + (index + 1) + '/' + files.length + ': ' + file.name);
            if (!/\.xml$/i.test(file.name || '')) {
                errors.push(file.name + ': chỉ nhận file .xml.');
                next(index + 1);
                return;
            }
            if (!file.size || file.size > 5242880) {
                errors.push(file.name + ': dung lượng phải từ 1 byte đến 5 MB.');
                next(index + 1);
                return;
            }

            var form = new FormData();
            form.append('xmlFile', file, file.name);
            form.append('csrf_token', csrf);
            $.ajax({
                url: baseUrl + 'hoso/upload/', type: 'POST',
                data: form, processData: false, contentType: false, dataType: 'json'
            }).done(function (response) {
                if (response && response.success) {
                    succeeded++;
                    queued.push(response.name || file.name);
                } else {
                    errors.push(file.name + ': ' + (response && response.message ? response.message : 'Không thể tải lên.'));
                }
            }).fail(function (xhr) {
                errors.push(file.name + ': ' + (xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message : 'Kết nối tải file thất bại.'));
            }).always(function () { next(index + 1); });
        }

        next(0);
    }

    $('#hoso-open-upload').on('click', function () {
        if (uploading) return;
        $('#hoso-upload-modal').modal('show');
    });
    $('#hoso-xml-files').on('change', function () {
        uploadXmlFiles(this.files);
        this.value = ''; // Cho phép chọn lại cùng tên file sau khi tải.
    });
    $('#hoso-upload-zone')
        .on('click', function (event) {
            if (!uploading && event.target.id !== 'hoso-xml-files') $('#hoso-xml-files').trigger('click');
        })
        .on('keydown', function (event) {
            if (!uploading && (event.key === 'Enter' || event.key === ' ')) {
                event.preventDefault();
                $('#hoso-xml-files').trigger('click');
            }
        })
        .on('dragenter dragover', function (event) {
            event.preventDefault(); event.stopPropagation();
            if (!uploading) $(this).addClass('is-dragging');
        })
        .on('dragleave dragend', function (event) {
            event.preventDefault(); event.stopPropagation();
            if (!event.relatedTarget || !$.contains(this, event.relatedTarget)) {
                $(this).removeClass('is-dragging');
            }
        })
        .on('drop', function (event) {
            event.preventDefault(); event.stopPropagation();
            $(this).removeClass('is-dragging');
            if (!uploading) uploadXmlFiles(event.originalEvent.dataTransfer.files);
        });

    $('#hoso-table-area')
        .on('dragenter dragover', function (event) {
            event.preventDefault(); event.stopPropagation();
            if (!uploading) $(this).addClass('is-dragging');
        })
        .on('dragleave dragend', function (event) {
            event.preventDefault(); event.stopPropagation();
            if (!event.relatedTarget || !$.contains(this, event.relatedTarget)) {
                $(this).removeClass('is-dragging');
            }
        })
        .on('drop', function (event) {
            event.preventDefault(); event.stopPropagation();
            $(this).removeClass('is-dragging');
            if (uploading || !event.originalEvent.dataTransfer.files.length) return;
            $('#hoso-upload-modal').modal('show');
            uploadXmlFiles(event.originalEvent.dataTransfer.files);
        });
    // Tránh trình duyệt mở file XML khi thả ngoài khu vực nhận file.
    $(document).on('dragover drop', function (event) {
        var transfer = event.originalEvent && event.originalEvent.dataTransfer;
        if (transfer && transfer.types && Array.prototype.indexOf.call(transfer.types, 'Files') !== -1) {
            event.preventDefault();
        }
    });

    function postDelete(action, data) {
        return $.ajax({
            url: baseUrl + 'hoso/' + action + '/',
            type: 'POST',
            dataType: 'json',
            data: $.extend({ csrf_token: csrf, date: selectedDate() }, data || {})
        });
    }

    $('#hoso-table').on('click', '.hoso-delete', function () {
        if (deletingAll) return;
        var id = Number($(this).attr('data-id'));
        var rowDate = $(this).attr('data-date');
        if (!window.confirm('Xóa hồ sơ #' + id + ' khỏi khoa của bạn? File sẽ được chuyển vào thư mục deleted.')) return;
        postDelete('delete', { id: id, date: rowDate }).done(function (response) {
            message(response.message || 'Đã thực hiện thao tác.', !response.success);
            table.ajax.reload(null, false);
        }).fail(function () { message('Không thể xóa hồ sơ.', true); });
    });

    $('#hoso-delete-all').on('click', function () {
        if (deletingAll || loadingRows || searchText()) return;
        var count = currentRows.filter(function (row) { return !row.da_day_cong; }).length;
        var date = selectedDate();
        if (!count || !window.confirm('Xóa TẤT CẢ hồ sơ CHƯA ĐẨY CỔNG thuộc khoa của bạn cập nhật ngày ' + date + '?\n\nFile sẽ được chuyển vào thư mục deleted.')) return;
        deletingAll = true;
        updateCounts();
        var deletedTotal = 0;
        var button = $('#hoso-delete-all');

        function finish(result, failed) {
            deletingAll = false;
            button.html('<i class="fa fa-trash"></i> Xóa tất cả (ngày đã chọn)');
            message(result, failed);
            table.ajax.reload(null, false);
        }

        function nextBatch() {
            if (selectedDate() !== date) {
                finish('Đã xóa ' + deletedTotal + ' hồ sơ. Ngày lọc đã thay đổi, thao tác dừng.', true);
                return;
            }
            button.text('Đang xóa... ' + deletedTotal);
            postDelete('deleteAll').done(function (response) {
                deletedTotal += Number(response.deleted) || 0;
                if (!response.success) {
                    finish('Đã xóa ' + deletedTotal + ' hồ sơ. ' + (response.message || 'Xóa chưa hoàn tất.'), true);
                    return;
                }
                if (response.done) {
                    finish('Đã xóa ' + deletedTotal + ' hồ sơ chưa đẩy cổng của khoa trong ngày ' + date + '.');
                } else if (Number(response.deleted) > 0) {
                    nextBatch();
                } else {
                    finish('Không thể tiếp tục xóa. Vui lòng làm mới và thử lại.', true);
                }
            }).fail(function () {
                finish('Đã xóa ' + deletedTotal + ' hồ sơ. Kết nối gián đoạn, vui lòng tải lại để kiểm tra.', true);
            });
        }
        nextBatch();
    });

    // Chỉ hiển thị thông tin bệnh nhân và lỗi/cảnh báo đã lưu; không tải hay giải mã XML.
    var detailRequest = null;
    var detailGeneration = 0;

    function patientText(value) {
        if (value == null || typeof value === 'object') return '-';
        var text = String(value).trim();
        return text || '-';
    }

    function patientBirth(value) {
        var text = patientText(value);
        // Chuan hoa ngay sinh ve dd/MM/yyyy, chi thay doi hien thi.
        var match = /^(\d{4})[-\/]?(\d{2})[-\/]?(\d{2})(?:\d{4}(?:\d{2})?|[T\s].*)?$/.exec(text);
        if (match) return match[3] + '/' + match[2] + '/' + match[1];
        match = /^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})$/.exec(text);
        return match ? ('0' + match[1]).slice(-2) + '/' + ('0' + match[2]).slice(-2) + '/' + match[3] : text;
    }

    function patientGender(value) {
        var gender = patientText(value);
        return gender === '1' ? 'Nam' : (gender === '2' ? 'Nữ' : gender);
    }

    function renderPatientInfo(response, row) {
        var patient = response.thong_tin_bn && typeof response.thong_tin_bn === 'object' ? response.thong_tin_bn : {};
        var khoa = response.khoa_quan_ly && typeof response.khoa_quan_ly === 'object' ? response.khoa_quan_ly : {};
        $('#hoso-patient-name').text(patientText(patient.hoTen));
        $('#hoso-patient-code').text(patientText(patient.maBn || response.ma_bn));
        // Ưu tiên mã thẻ ở chi tiết; nếu hồ sơ cũ thiếu, lấy mã đã có ở danh sách.
        var card = patientText(patient.maThe);
        $('#hoso-patient-insurance').text(card !== '-' ? card : patientText(row && row.ma_the_bhyt));
        $('#hoso-patient-birth').text(patientBirth(patientText(patient.ngaySinh) !== '-'
            ? patient.ngaySinh : (row && row.ngay_sinh)));
        $('#hoso-patient-gender').text(patientGender(patient.gioiTinh));
        $('#hoso-patient-address').text(patientText(patient.diaChi));
        var code = patientText(khoa.ma);
        var name = patientText(khoa.ten);
        $('#hoso-patient-khoa').text(name !== '-' && code !== '-' ? name + ' (' + code + ')' : (name !== '-' ? name : code));
        var from = patientText(patient.ngayVao);
        var to = patientText(patient.ngayRa);
        $('#hoso-patient-treatment').text(from !== '-' || to !== '-'
            ? (from === '-' ? '-' : formatTreatmentDate(from)) + ' - ' + (to === '-' ? '-' : formatTreatmentDate(to))
            : '-');
    }

    function detailText(value) {
        if (value == null) return '';
        return typeof value === 'object' ? JSON.stringify(value) : String(value);
    }

    function collectDetailErrors(validation) {
        var result = [];
        ($.isArray(validation) ? validation : []).forEach(function (group) {
            if (!group || typeof group !== 'object') return;
            ($.isArray(group.errors) ? group.errors : []).forEach(function (error) {
                if (error && typeof error === 'object') result.push(error);
            });
        });
        return result;
    }

    function renderDetailErrors(validation) {
        var errors = collectDetailErrors(validation);
        var body = $('#hoso-errors').empty();
        if (!errors.length) {
            $('<tr>').append($('<td colspan="5" class="text-center text-success">')
                .text('Không có lỗi hoặc cảnh báo.')).appendTo(body);
            return;
        }
        errors.forEach(function (e, index) {
            var warning = String(e.severity || '').toLowerCase() === 'warning';
            var row = $('<tr>').addClass(warning ? 'hoso-warning-row' : 'hoso-error-row').appendTo(body);
            $('<td class="text-center hoso-detail-index">').text(index + 1).appendTo(row);
            $('<td>').text(e.display_name || e.field_name || '').appendTo(row);
            $('<td class="hoso-detail-value">').text(detailText(e.value)).appendTo(row);
            $('<td>').text((warning ? 'Cảnh báo: ' : '') + (e.message || '')).appendTo(row);
            $('<td>').text(e.huongxuly || 'Chưa có hướng xử lý').appendTo(row);
        });
    }

    $('#hoso-view-modal').on('hidden.bs.modal', function () {
        detailGeneration++;
        if (detailRequest && detailRequest.readyState !== 4) detailRequest.abort();
        detailRequest = null;
    });

    $('#hoso-table').on('click', '.hoso-view', function () {
        var id = Number($(this).attr('data-id'));
        if (!Number.isSafeInteger(id) || id <= 0) return;
        var selectedRow = table.row($(this).closest('tr')).data() || {};
        var generation = ++detailGeneration;
        if (detailRequest && detailRequest.readyState !== 4) detailRequest.abort();
        $('#hoso-view-error').hide().empty();
        $('#hoso-view-content').hide();
        $('#hoso-view-modal').modal('show');
        detailRequest = $.ajax({ url: baseUrl + 'hoso/getContent/', type: 'POST', dataType: 'json', data: { id: id } })
            .done(function (response) {
                if (generation !== detailGeneration) return;
                if (!response || !response.success) {
                    $('#hoso-view-error').text(response && response.message ? response.message : 'Không thể tải kết quả kiểm tra.').show();
                    return;
                }
                renderPatientInfo(response, selectedRow);
                renderDetailErrors(response.validation);
                $('#hoso-view-content').show();
            }).fail(function (xhr, status) {
                if (generation !== detailGeneration || status === 'abort') return;
                $('#hoso-view-error').text(xhr.status === 401 || /^\s*<!doctype|^\s*<html/i.test(xhr.responseText || '')
                    ? 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.'
                    : 'Không thể tải kết quả kiểm tra. Vui lòng thử lại.').show();
            });
    });
})(jQuery);
