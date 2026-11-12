(function ($) {
    'use strict';

    function textCell(value, type) {
        var text = value == null ? '' : String(value);
        if (type !== 'display') return text;
        return $('<span>').addClass('td-text').attr('title', text).text(text).prop('outerHTML');
    }

    function numberCell(value, type) {
        if (value === null || value === undefined || value === '') return '';
        var number = Number(value);
        if (type !== 'display' || isNaN(number)) return value;
        return number.toLocaleString('vi-VN', { maximumFractionDigits: 3 });
    }

    function dateCell(value, type) {
        if (!value) return '';
        if (type !== 'display') return value;
        var parts = String(value).split('-');
        return parts.length === 3 ? parts[2] + '/' + parts[1] + '/' + parts[0] : value;
    }

    var table = $('#datatable-dichvu-bv').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        scrollCollapse: false,
        ajax: {
            url: $('#ULocal').val() + 'dichvu_bv/getData/',
            type: 'POST',
            data: function (data) {
                data.searchText = $('#dichvu-bv-search').val().trim();
                data.hieuLuc = $('#dichvu-bv-hieu-luc').val();
            }
        },
        pageLength: 25,
        pagingType: 'full_numbers',
        lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
        searching: false,
        ordering: false,
        autoWidth: false,
        columnDefs: [
            { targets: 0, width: '55px' },
            { targets: [1, 9, 14], width: '130px' },
            { targets: [2, 3, 10], width: '260px' },
            { targets: 4, width: '100px' },
            { targets: [5, 11, 12], width: '110px' },
            { targets: [6, 13], width: '160px' },
            { targets: [7, 8], width: '100px' },
            { targets: [15, 16, 17], width: '120px' }
        ],
        columns: [
            { data: 'stt', className: 'text-center' },
            { data: 'ma_tuong_duong', render: textCell },
            { data: 'ten_dvkt_pheduyet', render: textCell },
            { data: 'ten_dvkt_gia', render: textCell },
            { data: 'phan_loai_pttt', className: 'text-center' },
            { data: 'don_gia', className: 'text-right', render: numberCell },
            { data: 'quyet_dinh', render: textCell },
            { data: 'tu_ngay', className: 'text-center', render: dateCell },
            { data: 'den_ngay', className: 'text-center', render: dateCell },
            { data: 'ma', render: textCell },
            { data: 'ten', render: textCell },
            { data: 'gia', className: 'text-right', render: numberCell },
            { data: 'gia_dvkt', className: 'text-right', render: numberCell },
            { data: 'so_quyet_dinh', render: textCell },
            { data: 'ma_nhom_5937', className: 'text-center' },
            { data: 'hieu_luc', className: 'text-center' },
            { data: 'trang_thai_gui', render: textCell },
            { data: 'ket_qua_giam_dinh', render: textCell }
        ],
        initComplete: function () {
            this.api().columns.adjust();
        },
        language: {
            processing: 'Đang tải dữ liệu...',
            lengthMenu: 'Hiển thị _MENU_ dòng',
            zeroRecords: 'Không tìm thấy dịch vụ bệnh viện',
            info: 'Hiển thị _START_ đến _END_ trong _TOTAL_ dòng',
            infoEmpty: 'Không có dữ liệu',
            infoFiltered: '(lọc từ _MAX_ dòng)',
            paginate: { first: 'Đầu', last: 'Cuối', next: 'Sau', previous: 'Trước' }
        }
    });

    function updatePageNavigation() {
        var page = table.page.info();
        var current = page.pages ? page.page + 1 : 0;
        $('#dichvu-bv-page-info').text('Trang ' + current + ' / ' + page.pages);
        $('#btn-dichvu-bv-prev').prop('disabled', page.page <= 0);
        $('#btn-dichvu-bv-next').prop('disabled', page.pages === 0 || page.page >= page.pages - 1);
    }

    function reload() {
        table.ajax.reload();
    }

    table.on('draw.dt', function () {
        table.columns.adjust();
        updatePageNavigation();
    });

    $('#btn-dichvu-bv-prev').on('click', function () {
        if (!this.disabled) table.page('previous').draw('page');
    });
    $('#btn-dichvu-bv-next').on('click', function () {
        if (!this.disabled) table.page('next').draw('page');
    });
    $('#btn-search-dichvu-bv').on('click', reload);
    $('#dichvu-bv-hieu-luc').on('change', reload);
    $('#btn-reset-dichvu-bv').on('click', function () {
        $('#dichvu-bv-search').val('');
        $('#dichvu-bv-hieu-luc').val('');
        reload();
    });
    $('#dichvu-bv-search').on('keydown', function (event) {
        if (event.key === 'Enter' || event.which === 13) {
            event.preventDefault();
            reload();
        }
    });
    $(window).on('load resize', function () {
        table.columns.adjust();
    });
})(jQuery);

// CRUD for the dichvu_bv catalog.
(function ($) {
    'use strict';

    var labels = {
        id: 'ID', ma: 'Mã', ten: 'Tên', code: 'Mã ICD-10', display_vi: 'Tên tiếng Việt',
        display_en: 'Tên tiếng Anh', ho_ten: 'Họ và tên', source_url: 'Nguồn dữ liệu (URL)',
        is_active: 'Đang sử dụng', ghi_chu: 'Ghi chú', mo_ta: 'Mô tả', ngay_hieu_luc: 'Ngày hiệu lực',
        version: 'Phiên bản', source: 'Nguồn', source_id: 'ID nguồn', id_khoi: 'ID khối',
        ma_dung_chung: 'Mã dùng chung', parent_code: 'Mã bệnh cha'
    };

    function displayLabel(name) {
        if (labels[name]) return labels[name];
        return name.replace(/_/g, ' ').replace(/\b\w/g, function (letter) { return letter.toUpperCase(); });
    }

    function isDateTime(type) { return /^(datetime|timestamp)\b/.test(type); }
    function isDate(type) { return /^date\b/.test(type); }
    function isNumeric(type) { return /^(tinyint|smallint|mediumint|int|integer|bigint|decimal|float|double)\b/.test(type); }

    $(function () {
        $('.catalog-crud[data-module="dichvu_bv"]').each(function () {
            var root = $(this);
            var table = $('#' + root.attr('data-table-id'));
            var csrf = root.attr('data-csrf');
            var editor = root.find('.catalog-editor');
            var form = root.find('.catalog-form');
            var fields = root.find('.catalog-fields');
            var schema = null;
            var permissions = {save: false, update: false, delete: false};
            var selected = null;
            var mode = 'view';
            var busy = false;

            function request(method, payload) {
                return $.ajax({
                    url: $('#ULocal').val() + 'dichvu_bv/' + method + '/',
                    type: 'POST',
                    dataType: 'json',
                    data: $.extend({csrf: csrf}, payload || {})
                });
            }

            function errorMessage(xhr) {
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) return xhr.responseJSON.message;
                return 'Không thể kết nối hoặc xử lý yêu cầu. Vui lòng thử lại.';
            }

            function notify(message) { window.alert(message); }

            function currentGrid() {
                return $.fn.DataTable.isDataTable(table[0]) ? table.DataTable() : null;
            }

            function refresh() {
                selected = null;
                root.find('.catalog-selection').text('Chọn một dòng trong bảng để xem, sửa hoặc xóa.');
                updateButtons();
                var grid = currentGrid();
                if (grid && grid.ajax) grid.ajax.reload(null, false);
            }

            function updateButtons() {
                root.find('.catalog-template').prop('disabled', !schema);
                root.find('.catalog-import').prop('disabled', !schema || !permissions.save || busy);
                root.find('.catalog-add').prop('disabled', !schema || !permissions.save);
                root.find('.catalog-view').prop('disabled', !schema || !selected);
                root.find('.catalog-edit').prop('disabled', !schema || !selected || !permissions.update);
                root.find('.catalog-delete').prop('disabled', !schema || !selected || !permissions.delete);
                root.find('.catalog-import').toggle(!!permissions.save);
                root.find('.catalog-add').toggle(!!permissions.save);
                root.find('.catalog-edit').toggle(!!permissions.update);
                root.find('.catalog-delete').toggle(!!permissions.delete);
            }

            function fieldControl(column, value, readOnly) {
                var type = column.type;
                var name = column.name;
                var group = $('<div>').addClass(/(text|json|longtext|mediumtext)/.test(type) ? 'col-sm-12' : 'col-sm-6');
                var wrapper = $('<div>').addClass('form-group');
                var label = $('<label>').text(displayLabel(name) + (column.primary ? ' (khóa chính)' : ''));
                var input;
                if (name === 'is_active') {
                    input = $('<select>').addClass('form-control')
                        .append($('<option>').val('1').text('Có'))
                        .append($('<option>').val('0').text('Không'));
                } else if (/^enum\(/.test(type)) {
                    input = $('<select>').addClass('form-control');
                    if (column.nullable) input.append($('<option>').val('').text('-- Để trống --'));
                    var options = type.substring(5, type.length - 1).match(/'([^']*)'/g) || [];
                    options.forEach(function (item) { input.append($('<option>').val(item.slice(1, -1)).text(item.slice(1, -1))); });
                } else if (/(text|json|longtext|mediumtext)/.test(type) || /^(char|varchar)\(([3-9]\d\d|\d{4,})\)/.test(type)) {
                    input = $('<textarea rows="3">').addClass('form-control');
                } else {
                    input = $('<input>').addClass('form-control');
                    input.attr('type', isDate(type) ? 'date' : (isDateTime(type) ? 'datetime-local' : 'text'));
                    if (isNumeric(type)) input.attr('inputmode', 'decimal');
                }
                input.attr('data-field', name);
                if (isDateTime(type) && value) value = String(value).replace(' ', 'T');
                input.val(value == null ? '' : String(value));
                if (!readOnly && !column.nullable && column.default === null) input.prop('required', true);
                if (readOnly) input.prop('disabled', true);
                if (column.nullable && name !== 'is_active' && !/^enum\(/.test(type)) input.attr('placeholder', 'Có thể để trống');
                wrapper.append(label, input);
                group.append(wrapper);
                return group;
            }

            function renderForm(row) {
                fields.empty();
                schema.fields.forEach(function (column) {
                    // System-maintained columns are excluded from all forms.
                    if (!column.editable && !column.primary) return;
                    if (mode === 'create' && column.auto) return;
                    var readOnly = mode === 'view' || (mode === 'edit' && column.primary) || !column.editable;
                    var value = row ? row[column.name] : column.default;
                    fields.append(fieldControl(column, value, readOnly));
                });
                root.find('.catalog-submit').toggle(mode !== 'view');
                editor.find('.modal-title').text(mode === 'create' ? 'Thêm mới danh mục' :
                    mode === 'edit' ? 'Sửa thông tin danh mục' : 'Chi tiết danh mục');
                editor.modal('show');
            }

            function openRecord(action) {
                if (!selected || !schema) return;
                mode = action;
                var key = selected.key;
                request('getOne', {key: key}).done(function (result) {
                    if (!result.success) { notify(result.message); return; }
                    renderForm(result.row);
                }).fail(function (xhr) { notify(errorMessage(xhr)); });
            }

            function collectForm() {
                var payload = {};
                fields.find('[data-field]:enabled').each(function () {
                    var input = $(this);
                    var column = schema.fields.find(function (field) { return field.name === input.attr('data-field'); });
                    var value = input.val();
                    if (isDateTime(column.type) && value) {
                        value = value.replace('T', ' ');
                        if (value.length === 16) value += ':00';
                    }
                    payload[column.name] = value;
                });
                return payload;
            }

            request('getSchema').done(function (result) {
                if (!result.success) { notify(result.message); return; }
                schema = result.schema;
                permissions = result.permissions;
                updateButtons();
            }).fail(function (xhr) { notify(errorMessage(xhr)); });

            table.on('click', 'tbody tr', function () {
                if (!schema) return;
                var grid = currentGrid();
                if (!grid) return;
                var tr = $(this);
                if (tr.hasClass('child')) tr = tr.prev();
                var row = grid.row(tr).data();
                if (!row || row[schema.rowKey] === undefined || row[schema.rowKey] === null) return;
                selected = {key: String(row[schema.rowKey])};
                table.find('tbody tr').removeClass('catalog-selected');
                tr.addClass('catalog-selected');
                root.find('.catalog-selection').text('Đã chọn: ' + selected.key);
                updateButtons();
            });

            table.on('draw.dt', function () {
                selected = null;
                root.find('.catalog-selection').text('Chọn một dòng trong bảng để xem, sửa hoặc xóa.');
                updateButtons();
            });


            root.find('.catalog-template').on('click', function () {
                if (!schema) return;
                var downloadForm = $('<form method="post">')
                    .attr('action', $('#ULocal').val() + 'dichvu_bv/downloadExcelTemplate/')
                    .append($('<input type="hidden" name="csrf">').val(csrf));
                $('body').append(downloadForm);
                downloadForm[0].submit();
                window.setTimeout(function () { downloadForm.remove(); }, 1000);
            });
            root.find('.catalog-import').on('click', function () {
                if (!schema || !permissions.save || busy) return;
                root.find('.catalog-import-file').trigger('click');
            });
            root.find('.catalog-import-file').on('change', function () {
                var picker = this;
                var file = picker.files && picker.files[0];
                if (!file) return;
                if (!/\.xlsx$/i.test(file.name) || file.size > 5242880) {
                    notify('Chỉ hỗ trợ file .xlsx tối đa 5 MB.');
                    picker.value = '';
                    return;
                }
                if (busy || !permissions.save) { picker.value = ''; return; }
                var data = new FormData();
                data.append('csrf', csrf);
                data.append('file', file);
                busy = true;
                root.find('.catalog-import').prop('disabled', true);
                $.ajax({
                    url: $('#ULocal').val() + 'dichvu_bv/importExcel/',
                    type: 'POST',
                    data: data,
                    processData: false,
                    contentType: false,
                    dataType: 'json'
                }).done(function (result) {
                    if (result.success) { refresh(); notify(result.message); }
                    else notify(result.message);
                }).fail(function (xhr) { notify(errorMessage(xhr)); })
                    .always(function () {
                        busy = false;
                        picker.value = '';
                        updateButtons();
                    });
            });

            root.find('.catalog-add').on('click', function () {
                if (!permissions.save) return;
                mode = 'create';
                renderForm(null);
            });
            root.find('.catalog-view').on('click', function () { openRecord('view'); });
            root.find('.catalog-edit').on('click', function () { if (permissions.update) openRecord('edit'); });
            root.find('.catalog-delete').on('click', function () {
                if (!selected || !permissions.delete || busy) return;
                var key = selected.key;
                if (!window.confirm('Bạn chắc chắn muốn xóa bản ghi ' + key + '?')) return;
                busy = true;
                request('delete', {key: key}).done(function (result) {
                    if (result.success) { refresh(); notify(result.message); }
                    else notify(result.message);
                }).fail(function (xhr) { notify(errorMessage(xhr)); })
                    .always(function () { busy = false; });
            });

            form.on('submit', function (event) {
                event.preventDefault();
                if (busy || mode === 'view') return;
                if ((mode === 'create' && !permissions.save) || (mode === 'edit' && !permissions.update)) return;
                busy = true;
                root.find('.catalog-submit').prop('disabled', true);
                var method = mode === 'create' ? 'save' : 'update';
                request(method, {key: selected ? selected.key : '', data: JSON.stringify(collectForm())})
                    .done(function (result) {
                        if (result.success) { editor.modal('hide'); refresh(); notify(result.message); }
                        else notify(result.message);
                    }).fail(function (xhr) { notify(errorMessage(xhr)); })
                    .always(function () { busy = false; root.find('.catalog-submit').prop('disabled', false); });
            });
        });
    });
})(jQuery);
