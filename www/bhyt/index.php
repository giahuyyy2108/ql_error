<div class="x_panel">
    <div class="x_title">
        <h2>Danh mục nhóm BHYT</h2>
        <ul class="nav navbar-right panel_toolbox"><li><button id="btn-add-bhyt" class="btn btn-primary"><i class="fa fa-plus"></i> Thêm nhóm</button></li></ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
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
</style>

<input type="hidden" id="bhyt-csrf" value="<?=htmlspecialchars($request->getAttribute('bhytCsrf'), ENT_QUOTES, 'UTF-8')?>">

<div class="modal fade" id="modal-bhyt" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document"><div class="modal-content"><form id="form-bhyt">
        <div class="modal-header"><h5 class="modal-title">Thêm nhóm BHYT</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body">
            <input type="hidden" id="bhyt-original-id">
            <div class="row">
                <div class="form-group col-md-3"><label for="bhyt-id">Mã nhóm <span class="text-danger">*</span></label><input id="bhyt-id" class="form-control" maxlength="10" placeholder="DN" required></div>
                <div class="form-group col-md-6"><label for="bhyt-ten">Tên nhóm <span class="text-danger">*</span></label><input id="bhyt-ten" class="form-control" maxlength="255" required></div>
                <div class="form-group col-md-3"><label for="bhyt-dien">Diện <span class="text-danger">*</span></label><select id="bhyt-dien" class="form-control" required><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option></select></div>
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

