<div class="x_panel">
    <div class="x_title">
        <h2>Cấu hình hệ thống</h2>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <p class="text-muted">Mỗi công tắc áp dụng một lần cho tất cả trường khi xem nội dung file XML.</p>
        <table id="datatable-hethong" class="table table-striped table-bordered" width="100%">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Chức năng</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<input type="hidden" id="hethong-csrf" value="<?=htmlspecialchars($request->getAttribute('hethongCsrf'), ENT_QUOTES, 'UTF-8')?>">

<style>
    #datatable-hethong th { text-align: center; }
    #datatable-hethong td { vertical-align: middle; }
    #datatable-hethong .system-switch {
        display: inline-block;
        position: relative;
        width: 46px;
        height: 24px;
        margin: 0;
        vertical-align: middle;
    }
    #datatable-hethong .system-switch input {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
    }
    #datatable-hethong .system-switch-slider {
        position: absolute;
        inset: 0;
        cursor: pointer;
        border-radius: 24px;
        background: #b7b7b7;
        transition: background-color .2s;
    }
    #datatable-hethong .system-switch-slider:before {
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
    #datatable-hethong .system-switch input:checked + .system-switch-slider { background: #26b99a; }
    #datatable-hethong .system-switch input:checked + .system-switch-slider:before { transform: translateX(22px); }
    #datatable-hethong .system-switch input:focus + .system-switch-slider { box-shadow: 0 0 0 2px rgba(38, 185, 154, .25); }
    #datatable-hethong .system-switch input:disabled + .system-switch-slider { cursor: wait; opacity: .6; }
</style>
