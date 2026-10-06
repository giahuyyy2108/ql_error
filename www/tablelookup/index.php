<?php $databaseSchema = (array) $request->getAttribute('databaseSchema'); ?>
<div class="x_panel">
    <div class="x_title">
        <h2>Nguồn đối chiếu TABLE_EXISTS</h2>
        <ul class="nav navbar-right panel_toolbox"><li><button id="btn-add-lookup" class="btn btn-primary"><i class="fa fa-plus"></i> Đăng ký nguồn</button></li></ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <div class="alert alert-info">Đăng ký bảng và giới hạn các cột được phép dùng trong rule TABLE_EXISTS.</div>
        <table id="datatable-tablelookup" class="table table-striped table-bordered" width="100%">
            <thead><tr><th>STT</th><th>Mã nguồn</th><th>Tên hiển thị</th><th>Bảng dữ liệu</th><th>Cột đối chiếu</th><th>Cột điều kiện</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>
<input type="hidden" id="tablelookup-csrf" value="<?=htmlspecialchars($request->getAttribute('tableLookupCsrf'), ENT_QUOTES, 'UTF-8')?>">
<script type="application/json" id="tablelookup-schema"><?=json_encode($databaseSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?></script>

<div class="modal fade" id="modal-tablelookup" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content"><form id="form-tablelookup">
        <div class="modal-header"><h5 class="modal-title">Đăng ký nguồn đối chiếu</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body">
            <input type="hidden" id="lookup-id">
            <div class="row">
                <div class="form-group col-md-6"><label>Mã nguồn <span class="text-danger">*</span></label><input id="lookup-key" class="form-control" maxlength="64" placeholder="Ví dụ: danhmuc_thuoc" required></div>
                <div class="form-group col-md-6"><label>Tên hiển thị <span class="text-danger">*</span></label><input id="lookup-name" class="form-control" maxlength="150" required></div>
            </div>
            <div class="form-group"><label>Bảng dữ liệu <span class="text-danger">*</span></label><select id="lookup-table" class="form-control" required><option value="">-- Chọn bảng --</option><?php foreach ($databaseSchema as $table => $columns): ?><option value="<?=htmlspecialchars($table, ENT_QUOTES, 'UTF-8')?>"><?=htmlspecialchars($table, ENT_QUOTES, 'UTF-8')?></option><?php endforeach; ?></select></div>
            <div class="row">
                <div class="form-group col-md-6"><label>Cột đối chiếu <span class="text-danger">*</span></label><select id="lookup-columns" class="form-control" multiple size="9" required></select><p class="help-block">Giữ Ctrl để chọn nhiều cột.</p></div>
                <div class="form-group col-md-6"><label>Cột điều kiện</label><select id="lookup-conditions" class="form-control" multiple size="9"></select><p class="help-block">Các cột được phép xuất hiện trong conditions.</p></div>
            </div>
            <div class="checkbox"><label><input type="checkbox" id="lookup-active" checked> Đang hoạt động</label></div>
            <div id="tablelookup-message" class="alert" style="display:none"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button><button type="submit" id="btn-save-lookup" class="btn btn-primary"><i class="fa fa-save"></i> Lưu</button></div>
    </form></div></div>
</div>

<style>
    #datatable-tablelookup { table-layout:fixed; font-size:12px; }
    #datatable-tablelookup th,#datatable-tablelookup td { padding:6px; vertical-align:middle; word-break:break-word; }
    #datatable-tablelookup th { text-align:center; font-size:11px; }
    #datatable-tablelookup .lookup-actions { text-align:center; white-space:nowrap; }
    #datatable-tablelookup .lookup-switch { position:relative; display:inline-block; width:42px; height:22px; margin:0; vertical-align:middle; }
    #datatable-tablelookup .lookup-switch input { opacity:0; width:0; height:0; }
    #datatable-tablelookup .lookup-slider { position:absolute; cursor:pointer; inset:0; background:#bbb; border-radius:22px; transition:.2s; }
    #datatable-tablelookup .lookup-slider:before { content:""; position:absolute; width:16px; height:16px; left:3px; top:3px; background:#fff; border-radius:50%; transition:.2s; box-shadow:0 1px 3px rgba(0,0,0,.3); }
    #datatable-tablelookup .lookup-switch input:checked + .lookup-slider { background:#26b99a; }
    #datatable-tablelookup .lookup-switch input:checked + .lookup-slider:before { transform:translateX(20px); }
    #datatable-tablelookup .lookup-switch input:disabled + .lookup-slider { cursor:not-allowed; opacity:.55; }
    #lookup-columns,#lookup-conditions { min-height:190px; }
</style>
