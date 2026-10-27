<div class="x_panel">
    <div class="x_title">
        <h2>Cấu hình hệ thống</h2>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <p class="text-muted">Bật hoặc tắt từng chức năng áp dụng khi xem nội dung file XML.</p>

        <div id="hethong-settings" class="hethong-settings">
            <div class="hethong-loading text-muted">Đang tải cấu hình...</div>
        </div>

        <div class="hethong-path-section">
            <div class="hethong-path-title">Đường dẫn đầu vào XML</div>
            <div class="hethong-path-note">
                Giá trị lưu tại bảng <code>path</code>, dòng <strong>Đầu vào</strong> (ID 1).
                XML watcher sẽ dùng đường dẫn này khi không truyền tham số <code>--dir</code>.
            </div>
            <div class="hethong-path-card">
                <div class="hethong-path-field">
                    <label for="xml-watcher-storage-path"><code>_XML_WATCHER_STORAGE_PATH_</code></label>
                    <input type="text"
                           id="xml-watcher-storage-path"
                           class="form-control"
                           maxlength="255"
                           autocomplete="off"
                           placeholder="Ví dụ: D:\xml hoặc storage/xml">
                </div>
                <button type="button" id="save-xml-watcher-path" class="btn btn-success">
                    <i class="fa fa-save"></i> Lưu đường dẫn
                </button>
            </div>
            <div id="xml-watcher-path-message" class="hethong-path-message" aria-live="polite"></div>
        </div>
    </div>
</div>

<input type="hidden" id="hethong-csrf" value="<?=htmlspecialchars($request->getAttribute('hethongCsrf'), ENT_QUOTES, 'UTF-8')?>">

<style>
    .hethong-settings {
        max-width: 900px;
    }
    .hethong-setting-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        padding: 18px 20px;
        margin-bottom: 12px;
        background: #fff;
        border: 1px solid #e6e9ed;
        border-radius: 6px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
    }
    .hethong-setting-name {
        font-size: 15px;
        font-weight: 600;
        color: #34495e;
    }
    .hethong-setting-state {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }
    .hethong-state-label {
        min-width: 54px;
        text-align: right;
        font-size: 13px;
        font-weight: 600;
        color: #7f8c8d;
    }
    .hethong-state-label.is-enabled { color: #26b99a; }
    .system-switch {
        display: inline-block;
        position: relative;
        width: 46px;
        height: 24px;
        margin: 0;
        vertical-align: middle;
    }
    .system-switch input {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
    }
    .system-switch-slider {
        position: absolute;
        inset: 0;
        cursor: pointer;
        border-radius: 24px;
        background: #b7b7b7;
        transition: background-color .2s;
    }
    .system-switch-slider:before {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        left: 2px;
        top: 2px;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .3);
        transition: transform .2s;
    }
    .system-switch input:checked + .system-switch-slider { background: #26b99a; }
    .system-switch input:checked + .system-switch-slider:before { transform: translateX(22px); }
    .system-switch input:focus + .system-switch-slider { box-shadow: 0 0 0 2px rgba(38, 185, 154, .25); }
    .system-switch input:disabled + .system-switch-slider { cursor: wait; opacity: .6; }
    .hethong-loading,
    .hethong-empty,
    .hethong-error {
        padding: 20px;
        border: 1px dashed #d9d9d9;
        border-radius: 6px;
        text-align: center;
    }
    .hethong-error { color: #a94442; }
    .hethong-path-section {
        max-width: 900px;
        margin-top: 28px;
        padding-top: 22px;
        border-top: 1px solid #e6e9ed;
    }
    .hethong-path-title {
        margin-bottom: 5px;
        font-size: 16px;
        font-weight: 600;
        color: #34495e;
    }
    .hethong-path-note {
        margin-bottom: 12px;
        color: #7f8c8d;
    }
    .hethong-path-card {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        padding: 18px 20px;
        background: #fff;
        border: 1px solid #e6e9ed;
        border-radius: 6px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
    }
    .hethong-path-field {
        flex: 1;
        min-width: 0;
    }
    .hethong-path-field label {
        display: block;
        margin-bottom: 7px;
        color: #34495e;
        font-weight: 600;
    }
    .hethong-path-card .btn {
        margin-bottom: 0;
        white-space: nowrap;
    }
    .hethong-path-message {
        min-height: 20px;
        margin-top: 8px;
        font-size: 13px;
    }
    .hethong-path-message.is-success { color: #26b99a; }
    .hethong-path-message.is-error { color: #a94442; }
    @media (max-width: 767px) {
        .hethong-setting-item { padding: 15px; gap: 12px; }
        .hethong-setting-name { font-size: 14px; }
        .hethong-path-card {
            display: block;
            padding: 15px;
        }
        .hethong-path-card .btn {
            width: 100%;
            margin-top: 10px;
        }
    }
</style>
