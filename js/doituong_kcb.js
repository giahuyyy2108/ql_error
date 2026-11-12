(function ($) {
    'use strict';
    function compact(value, type) {
        var text = value == null ? '' : String(value);
        if (type !== 'display') return text;
        return $('<span>').addClass('catalog-text').attr('title', text).text(text).prop('outerHTML');
    }
    var table = $('#datatable-doituong-kcb').DataTable({
        processing:true,serverSide:true,searching:false,ordering:false,responsive:false,autoWidth:false,
        ajax:{url:$('#ULocal').val()+'doituong_kcb/getData/',type:'POST',data:function(d){d.searchText=$('#doituong-kcb-search').val().trim();}},
        pageLength:25,lengthMenu:[[10,25,50,100,200],[10,25,50,100,200]],
        columns:[
            {data:'ma',width:'7%',className:'text-center'},
            {data:'truong_hop',width:'27%',render:compact},
            {data:'quy_dinh',width:'20%',defaultContent:'',render:compact},
            {data:'muc_huong',width:'20%',defaultContent:'',render:compact},
            {data:'ghi_chu',width:'8%',defaultContent:'',render:compact},
            {data:'ngay_hieu_luc',width:'9%',className:'text-center'},
            {data:'is_active',width:'9%',className:'text-center',render:function(v){return Number(v)===1?'<i class="fa fa-check-circle text-success catalog-status" title="Hiện hành"></i>':'<i class="fa fa-ban text-danger catalog-status" title="Ngừng dùng"></i>';}}
        ],
        language:{processing:'Đang tải dữ liệu...',lengthMenu:'Hiển thị _MENU_ dòng',zeroRecords:'Không tìm thấy mã loại hình KCB',info:'Hiển thị _START_ đến _END_ trong _TOTAL_ mã',infoEmpty:'Không có dữ liệu',infoFiltered:'(lọc từ _MAX_ mã)',paginate:{first:'Đầu',last:'Cuối',next:'Sau',previous:'Trước'}}
    });
    $('#btn-search-doituong-kcb').on('click',function(){table.ajax.reload();});
    $('#btn-reset-doituong-kcb').on('click',function(){$('#doituong-kcb-search').val('');table.ajax.reload();$('#doituong-kcb-search').focus();});
    $('#doituong-kcb-search').on('keydown',function(e){if(e.key==='Enter'||e.which===13){e.preventDefault();table.ajax.reload();}});
})(jQuery);

// CRUD for the doituong_kcb catalog.
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
        $('.catalog-crud[data-module="doituong_kcb"]').each(function () {
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
                    url: $('#ULocal').val() + 'doituong_kcb/' + method + '/',
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
                    .attr('action', $('#ULocal').val() + 'doituong_kcb/downloadExcelTemplate/')
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
                    url: $('#ULocal').val() + 'doituong_kcb/importExcel/',
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
