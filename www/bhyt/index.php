<div class="x_panel">
    <div class="x_title">
        <h2>Danh mục nhóm BHYT</h2>
        <ul class="nav navbar-right panel_toolbox"><li><button id="btn-add-bhyt" class="btn btn-primary"><i class="fa fa-plus"></i> Thêm nhóm</button></li></ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <div class="bhyt-search-bar">
            <div class="input-group">
                <input type="search" id="bhyt-search" class="form-control" placeholder="Nhập mã, tên, mô tả hoặc diện BHYT..." autocomplete="off">
                <span class="input-group-btn">
                    <button type="button" id="btn-search-bhyt" class="btn btn-primary"><i class="fa fa-search"></i> Tìm</button>
                    <button type="button" id="btn-clear-search-bhyt" class="btn btn-default" title="Xóa nội dung tìm kiếm"><i class="fa fa-times"></i></button>
                </span>
            </div>
        </div>
        <table id="datatable-bhyt" class="table table-striped table-bordered" width="100%">
            <thead><tr><th>STT</th><th>Mã</th><th>Tên nhóm</th><th>Mô tả</th><th>Diện</th><th>Thao tác</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<style>
    #datatable-bhyt { width:100%!important; table-layout:fixed; font-size:12px; }
    #datatable-bhyt th,#datatable-bhyt td { padding:6px 5px; vertical-align:middle; }
    #datatable-bhyt th { text-align:center; }
    #datatable-bhyt .bhyt-ellipsis { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    #datatable-bhyt .bhyt-actions { white-space:nowrap; text-align:center; }
    #datatable-bhyt .bhyt-actions .btn { padding:3px 7px; margin:0 1px; }
    #datatable-bhyt .bhyt-dien { display:inline-block; min-width:25px; font-size:13px; }
    .bhyt-search-bar { max-width:560px; margin:0 0 16px; }
    .bhyt-search-bar .form-control,.bhyt-search-bar .btn { height:34px; }
    #datatable-bhyt_wrapper .dataTables_filter { display:none; }
    #modal-bhyt .modal-content { border:0; border-radius:8px; overflow:hidden; box-shadow:0 12px 35px rgba(0,0,0,.22); }
    #modal-bhyt .modal-header { padding:16px 20px; color:#fff; background:#337ab7; border-bottom:0; }
    #modal-bhyt .modal-header .modal-title { display:inline-block; margin:0; color:#fff; font-size:18px; font-weight:600; }
    #modal-bhyt .modal-header .close { color:#fff; opacity:.85; text-shadow:none; }
    #modal-bhyt .modal-body { padding:22px 24px 12px; background:#fafbfc; }
    #modal-bhyt .modal-footer { padding:14px 24px; background:#fff; border-top:1px solid #e5e8eb; }
    #modal-bhyt .form-group > label { margin-bottom:7px; color:#34495e; font-weight:600; }
    #modal-bhyt .form-control { border-radius:5px; }
    #modal-bhyt #bhyt-mota { min-height:130px; resize:vertical; }
    .bhyt-dien-options { display:grid; grid-template-columns:repeat(5,minmax(32px,1fr)); gap:6px; }
    .bhyt-dien-option { position:relative; display:flex; align-items:center; justify-content:center; height:38px; margin:0; border:1px solid #ccd4dc; border-radius:6px; background:#fff; color:#4b5966; cursor:pointer; transition:.15s ease; }
    .bhyt-dien-option input { position:absolute; opacity:0; pointer-events:none; }
    .bhyt-dien-option:hover { border-color:#337ab7; color:#337ab7; }
    .bhyt-dien-option.is-selected { border-color:#337ab7; background:#337ab7; color:#fff; box-shadow:0 2px 7px rgba(51,122,183,.25); }
    #bhyt-dien-help { margin:7px 0 0; }
    #bhyt-message { margin:4px 0 10px; padding:10px 14px; }
    @media (max-width:767px) {
        .bhyt-search-bar { max-width:none; }
        #btn-search-bhyt { font-size:0; }
        #btn-search-bhyt .fa { margin:0; font-size:14px; }
        #modal-bhyt .modal-dialog { margin:10px; }
        #modal-bhyt .modal-body { padding:18px 16px 8px; }
        #modal-bhyt .modal-footer { padding:12px 16px; }
    }
</style>

<input type="hidden" id="bhyt-csrf" value="<?=htmlspecialchars($request->getAttribute('bhytCsrf'), ENT_QUOTES, 'UTF-8')?>">

<div class="modal fade" id="modal-bhyt" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg bhyt-modal-dialog" role="document"><div class="modal-content"><form id="form-bhyt">
        <div class="modal-header"><h5 class="modal-title">Thêm nhóm BHYT</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body">
            <input type="hidden" id="bhyt-original-id">
            <div class="row">
                <div class="form-group col-md-3"><label for="bhyt-id">Mã nhóm <span class="text-danger">*</span></label><input id="bhyt-id" class="form-control" maxlength="10" placeholder="DN" required></div>
                <div class="form-group col-md-6"><label for="bhyt-ten">Tên nhóm <span class="text-danger">*</span></label><input id="bhyt-ten" class="form-control" maxlength="255" required></div>
                <div class="form-group col-md-3"><label>Diện BHYT <span class="text-danger">*</span></label><div id="bhyt-dien" class="bhyt-dien-options"><label class="bhyt-dien-option"><input type="checkbox" value="1"><span>1</span></label><label class="bhyt-dien-option"><input type="checkbox" value="2"><span>2</span></label><label class="bhyt-dien-option"><input type="checkbox" value="3"><span>3</span></label><label class="bhyt-dien-option"><input type="checkbox" value="4"><span>4</span></label><label class="bhyt-dien-option"><input type="checkbox" value="5"><span>5</span></label></div><p id="bhyt-dien-help" class="help-block"><span id="bhyt-dien-count">0</span>/2 diện đã chọn</p></div>
            </div>
            <div class="form-group"><label for="bhyt-mota">Mô tả <span class="text-danger">*</span></label><textarea id="bhyt-mota" class="form-control" rows="6" required></textarea></div>
            <div id="bhyt-message" class="alert" style="display:none"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button><button id="btn-save-bhyt" type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Lưu</button></div>
    </form></div></div>
</div>

<div class="modal fade" id="modal-delete-bhyt" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Xác nhận xóa</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body">Bạn có chắc muốn xóa nhóm <strong id="delete-bhyt-name"></strong>?</div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button><button id="btn-confirm-delete-bhyt" class="btn btn-danger"><i class="fa fa-trash"></i> Xóa</button></div>
    </div></div>
</div>
