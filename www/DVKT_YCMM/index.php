<div class="x_panel">
    <div class="x_title">
        <h2>Danh mục DVKT yêu cầu có mã máy</h2>
        <ul class="nav navbar-right panel_toolbox"><li>
            <button type="button" id="btn-add-dvkt-ycmm" class="btn btn-success"><i class="fa fa-plus"></i> Thêm mới</button>
        </li></ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <div class="row dvkt-ycmm-search-row"><div class="col-md-8 col-sm-10 col-xs-12"><div class="input-group">
            <input type="search" id="dvkt-ycmm-search" class="form-control" placeholder="Tìm mã hoặc tên dịch vụ kỹ thuật..." autocomplete="off">
            <span class="input-group-btn"><button id="btn-search-dvkt-ycmm" class="btn btn-primary"><i class="fa fa-search"></i> Tìm</button>
            <button id="btn-reset-dvkt-ycmm" class="btn btn-default"><i class="fa fa-refresh"></i> Làm mới</button></span>
        </div></div></div>
        <table id="datatable-dvkt-ycmm" class="table table-striped table-bordered" width="100%">
            <thead><tr><th>STT</th><th>Mã DVKT</th><th>Tên DVKT</th><th>Ngày hiệu lực</th><th>Ngày hết hiệu lực</th><th>Trả KQ sau</th><th>Hiệu lực</th><th>Thao tác</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>
<input type="hidden" id="dvkt-ycmm-csrf" value="<?=htmlspecialchars($request->getAttribute('dvktYcmmCsrf'), ENT_QUOTES, 'UTF-8')?>">

<div class="modal fade" id="modal-dvkt-ycmm" tabindex="-1" role="dialog" aria-hidden="true">
 <div class="modal-dialog modal-lg"><div class="modal-content"><form id="form-dvkt-ycmm">
  <div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button><h4 class="modal-title">Thông tin DVKT</h4></div>
  <div class="modal-body">
   <input type="hidden" id="dvkt-ycmm-id">
   <div class="row">
    <div class="form-group col-sm-3"><label>STT <span class="text-danger">*</span></label><input id="dvkt-ycmm-stt" type="number" min="1" class="form-control" required></div>
    <div class="form-group col-sm-9"><label>Mã DVKT <span class="text-danger">*</span></label><input id="dvkt-ycmm-code" maxlength="50" class="form-control" required></div>
   </div>
   <div class="form-group"><label>Tên DVKT <span class="text-danger">*</span></label><textarea id="dvkt-ycmm-name" class="form-control" rows="3" required></textarea></div>
   <div class="row">
    <div class="form-group col-sm-4"><label>Ngày hiệu lực</label><input id="dvkt-ycmm-from" type="date" class="form-control"></div>
    <div class="form-group col-sm-4"><label>Ngày hết hiệu lực</label><input id="dvkt-ycmm-to" type="date" class="form-control"></div>
    <div class="form-group col-sm-4"><label>Trạng thái</label><select id="dvkt-ycmm-active" class="form-control"><option value="1">Có hiệu lực</option><option value="0">Ngừng hiệu lực</option></select></div>
   </div>
   <div class="checkbox"><label><input id="dvkt-ycmm-late" type="checkbox"> Dịch vụ trả kết quả sau</label></div>
   <div id="dvkt-ycmm-message" class="alert" style="display:none"></div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button><button type="submit" id="btn-save-dvkt-ycmm" class="btn btn-primary"><i class="fa fa-save"></i> Lưu</button></div>
 </form></div></div>
</div>
<style>
.dvkt-ycmm-search-row{margin-bottom:14px}#datatable-dvkt-ycmm{table-layout:fixed;font-size:12px}#datatable-dvkt-ycmm th,#datatable-dvkt-ycmm td{padding:6px;vertical-align:middle}#datatable-dvkt-ycmm th{text-align:center;font-size:11px}#datatable-dvkt-ycmm .dvkt-name{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}#datatable-dvkt-ycmm_wrapper .dataTables_filter{display:none}.dvkt-ycmm-actions{white-space:nowrap;text-align:center}
</style>
