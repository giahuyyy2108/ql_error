(function ($) {
    'use strict';

    var baseUrl = $('#ULocal').val();
    var csrfToken = $('#hethong-csrf').val();
    var container = $('#hethong-settings');
    var pathInput = $('#xml-watcher-storage-path');
    var pathButton = $('#save-xml-watcher-path');
    var pathMessage = $('#xml-watcher-path-message');
    var portalPathInput = $('#portal-storage-path');
    var portalPathButton = $('#save-portal-storage-path');
    var portalPathMessage = $('#portal-storage-path-message');
    var khoaOutputContainer = $('#khoa-output-settings');
    var khoaOutputButton = $('#save-khoa-output-paths');
    var khoaOutputMessage = $('#khoa-output-message');

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    function renderSettings(items) {
        container.empty();

        if (!items || !items.length) {
            container.append('<div class="hethong-empty text-muted">Chưa có cấu hình hệ thống.</div>');
            return;
        }

        items.forEach(function (item) {
            var enabled = Number(item.trangthai) === 1;
            var html = ''
                + '<div class="hethong-setting-item" data-id="' + Number(item.id) + '">'
                + '  <div class="hethong-setting-name">' + escapeHtml(item.chucnang) + '</div>'
                + '  <div class="hethong-setting-state">'
                + '    <span class="hethong-state-label' + (enabled ? ' is-enabled' : '') + '">' + (enabled ? 'Đang bật' : 'Đang tắt') + '</span>'
                + '    <label class="system-switch" title="' + (enabled ? 'Đang bật' : 'Đang tắt') + '">'
                + '      <input type="checkbox" class="system-status-switch" data-id="' + Number(item.id) + '"'
                + (enabled ? ' checked' : '') + ' aria-label="Bật hoặc tắt ' + escapeHtml(item.chucnang) + '">'
                + '      <span class="system-switch-slider"></span>'
                + '    </label>'
                + '  </div>'
                + '</div>';
            container.append(html);
        });
    }

    function loadSettings() {
        $.ajax({
            url: baseUrl + 'hethong/getData/',
            type: 'POST',
            dataType: 'json'
        }).done(function (response) {
            renderSettings(response.data || []);
        }).fail(function () {
            container.html('<div class="hethong-error">Không thể tải cấu hình hệ thống.</div>');
        });
    }

    function showPathMessage(message, isError) {
        pathMessage
            .removeClass('is-success is-error')
            .addClass(isError ? 'is-error' : 'is-success')
            .text(message || '');
    }

    function loadInputPath() {
        pathInput.prop('disabled', true);
        pathButton.prop('disabled', true);

        $.ajax({
            url: baseUrl + 'hethong/getPath/',
            type: 'POST',
            dataType: 'json'
        }).done(function (response) {
            if (!response.success) {
                showPathMessage(response.message || 'Không thể tải đường dẫn đầu vào XML.', true);
                return;
            }
            if (!response.data) {
                showPathMessage('Không tìm thấy dòng Đầu vào (ID 1) trong bảng path.', true);
                return;
            }
            pathInput.val(response.data.path || '');
            showPathMessage('', false);
        }).fail(function () {
            showPathMessage('Không thể tải đường dẫn đầu vào XML.', true);
        }).always(function () {
            pathInput.prop('disabled', false);
            pathButton.prop('disabled', false);
        });
    }

    function saveInputPath() {
        var path = $.trim(pathInput.val());
        if (!path) {
            showPathMessage('Đường dẫn đầu vào XML không được để trống.', true);
            pathInput.focus();
            return;
        }

        pathInput.prop('disabled', true);
        pathButton.prop('disabled', true);
        showPathMessage('Đang lưu...', false);

        $.ajax({
            url: baseUrl + 'hethong/updatePath/',
            type: 'POST',
            dataType: 'json',
            data: {
                path: path,
                csrf_token: csrfToken
            }
        }).done(function (response) {
            if (!response.success) {
                showPathMessage(response.message || 'Không thể lưu đường dẫn đầu vào XML.', true);
                return;
            }
            pathInput.val(response.path || path);
            showPathMessage(response.message || 'Đã lưu đường dẫn đầu vào XML.', false);
        }).fail(function () {
            showPathMessage('Có lỗi xảy ra khi lưu đường dẫn đầu vào XML.', true);
        }).always(function () {
            pathInput.prop('disabled', false);
            pathButton.prop('disabled', false);
        });
    }

    function showPortalPathMessage(message, isError) {
        portalPathMessage
            .removeClass('is-success is-error')
            .addClass(isError ? 'is-error' : 'is-success')
            .text(message || '');
    }

    function loadPortalPath() {
        portalPathInput.prop('disabled', true);
        portalPathButton.prop('disabled', true);

        $.ajax({
            url: baseUrl + 'hethong/getPortalPath/',
            type: 'POST',
            dataType: 'json'
        }).done(function (response) {
            if (!response.success) {
                showPortalPathMessage(response.message || 'Không thể tải đường dẫn Đẩy cổng.', true);
                return;
            }
            portalPathInput.val(response.data && response.data.path ? response.data.path : '');
            showPortalPathMessage('', false);
        }).fail(function () {
            showPortalPathMessage('Không thể tải đường dẫn Đẩy cổng.', true);
        }).always(function () {
            portalPathInput.prop('disabled', false);
            portalPathButton.prop('disabled', false);
        });
    }

    function savePortalPath() {
        var path = $.trim(portalPathInput.val());
        if (!path) {
            showPortalPathMessage('Đường dẫn Đẩy cổng không được để trống.', true);
            portalPathInput.focus();
            return;
        }

        portalPathInput.prop('disabled', true);
        portalPathButton.prop('disabled', true);
        showPortalPathMessage('Đang lưu...', false);

        $.ajax({
            url: baseUrl + 'hethong/updatePortalPath/',
            type: 'POST',
            dataType: 'json',
            data: {
                path: path,
                csrf_token: csrfToken
            }
        }).done(function (response) {
            if (!response.success) {
                showPortalPathMessage(response.message || 'Không thể lưu đường dẫn Đẩy cổng.', true);
                return;
            }
            portalPathInput.val(response.path || path);
            showPortalPathMessage(response.message || 'Đã lưu đường dẫn Đẩy cổng.', false);
        }).fail(function () {
            showPortalPathMessage('Có lỗi xảy ra khi lưu đường dẫn Đẩy cổng.', true);
        }).always(function () {
            portalPathInput.prop('disabled', false);
            portalPathButton.prop('disabled', false);
        });
    }

    function showKhoaOutputMessage(message, isError) {
        khoaOutputMessage
            .removeClass('is-success is-error')
            .addClass(isError ? 'is-error' : 'is-success')
            .text(message || '');
    }

    function renderKhoaOutputSettings(items) {
        khoaOutputContainer.empty();
        if (!items || !items.length) {
            khoaOutputContainer.html('<div class="hethong-empty text-muted">Chưa có khoa gốc để cấu hình.</div>');
            return;
        }

        items.forEach(function (item) {
            var row = $('<div>').addClass('khoa-output-row').attr('data-ma-khoa', item.ma || '');
            row.append(
                $('<div>').addClass('khoa-output-name')
                    .append($('<strong>').text(item.ma || ''))
                    .append(document.createTextNode(' ' + (item.ten || '')))
            );
            row.append(
                $('<input>').attr({
                    type: 'text',
                    maxlength: 500,
                    autocomplete: 'off',
                    placeholder: 'Ví dụ: D:\\Khoa'
                }).addClass('form-control khoa-output-path').val(item.output_path || '')
            );
            khoaOutputContainer.append(row);
        });
    }

    function loadKhoaOutputSettings() {
        $.ajax({
            url: baseUrl + 'hethong/getKhoaOutputPaths/',
            type: 'POST',
            dataType: 'json'
        }).done(function (response) {
            if (!response.success) {
                showKhoaOutputMessage(response.message || 'Không thể tải cấu hình đầu ra theo khoa.', true);
                return;
            }
            renderKhoaOutputSettings(response.data || []);
            showKhoaOutputMessage('', false);
        }).fail(function () {
            showKhoaOutputMessage('Không thể tải cấu hình đầu ra theo khoa.', true);
        });
    }

    container.on('change', '.system-status-switch', function () {
        var toggle = $(this);
        var enabled = toggle.is(':checked');
        var item = toggle.closest('.hethong-setting-item');
        var label = item.find('.hethong-state-label');

        toggle.prop('disabled', true);

        $.ajax({
            url: baseUrl + 'hethong/updateStatus/',
            type: 'POST',
            dataType: 'json',
            data: {
                id: toggle.attr('data-id'),
                trangthai: enabled ? '1' : '0',
                csrf_token: csrfToken
            }
        }).done(function (response) {
            if (!response.success) {
                toggle.prop('checked', !enabled);
                alert(response.message);
                return;
            }

            label
                .toggleClass('is-enabled', enabled)
                .text(enabled ? 'Đang bật' : 'Đang tắt');
            toggle.closest('.system-switch').attr('title', enabled ? 'Đang bật' : 'Đang tắt');
        }).fail(function () {
            toggle.prop('checked', !enabled);
            alert('Có lỗi xảy ra khi cập nhật cấu hình hệ thống.');
        }).always(function () {
            toggle.prop('disabled', false);
        });
    });

    pathButton.on('click', saveInputPath);
    pathInput.on('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            saveInputPath();
        }
    });

    portalPathButton.on('click', savePortalPath);
    portalPathInput.on('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            savePortalPath();
        }
    });

    khoaOutputButton.on('click', function () {
        var rows = khoaOutputContainer.find('.khoa-output-row').toArray();
        var index = 0;
        var saved = 0;

        if (!rows.length) {
            showKhoaOutputMessage('Không có khoa gốc để lưu cấu hình.', true);
            return;
        }

        khoaOutputButton.prop('disabled', true);
        khoaOutputContainer.find('.khoa-output-path').prop('disabled', true);
        showKhoaOutputMessage('Đang lưu cấu hình đầu ra...', false);

        function finish(message, isError) {
            khoaOutputButton.prop('disabled', false);
            khoaOutputContainer.find('.khoa-output-path').prop('disabled', false);
            showKhoaOutputMessage(message, isError);
        }

        function saveNext() {
            if (index >= rows.length) {
                finish('Đã lưu cấu hình đầu ra cho ' + saved + ' khoa.', false);
                return;
            }

            var row = $(rows[index]);
            var input = row.find('.khoa-output-path');
            var maKhoa = row.attr('data-ma-khoa') || '';

            $.ajax({
                url: baseUrl + 'hethong/updateKhoaOutputPath/',
                type: 'POST',
                dataType: 'json',
                data: {
                    ma_khoa: maKhoa,
                    output_path: $.trim(input.val()),
                    csrf_token: csrfToken
                }
            }).done(function (response) {
                if (!response.success) {
                    finish(maKhoa + ': ' + (response.message || 'Không thể lưu đường dẫn đầu ra.'), true);
                    return;
                }
                input.val(response.output_path || '');
                saved += 1;
                index += 1;
                saveNext();
            }).fail(function () {
                finish(maKhoa + ': Có lỗi khi lưu đường dẫn đầu ra.', true);
            });
        }

        saveNext();
    });

    loadSettings();
    loadInputPath();
    loadPortalPath();
    loadKhoaOutputSettings();
})(jQuery);
