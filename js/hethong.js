(function ($) {
    'use strict';

    var baseUrl = $('#ULocal').val();
    var csrfToken = $('#hethong-csrf').val();
    var container = $('#hethong-settings');
    var pathInput = $('#xml-watcher-storage-path');
    var pathButton = $('#save-xml-watcher-path');
    var pathMessage = $('#xml-watcher-path-message');

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

    loadSettings();
    loadInputPath();
})(jQuery);
