<div class="x_panel">
    <div class="x_title">
        <h2>Danh mục mã khoa <small>Quyết định 1804/QĐ-BYT</small></h2>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <div class="alert alert-info">
            Tra cứu theo mã, tên khoa, mã khoa gốc hoặc ghi chú. Chỉ người có quyền quản lý mới được thay đổi dữ liệu chuẩn.
            Nguồn:
            <a href="https://thuvienphapluat.vn/van-ban/Cong-nghe-thong-tin/Quyet-dinh-1804-QD-BYT-2026-Danh-muc-ma-loai-hinh-kham-benh-chua-benh-711300.aspx"
               target="_blank" rel="noopener noreferrer">Quyết định 1804/QĐ-BYT</a>.
        </div>
        <div class="row" style="margin-bottom:12px">
            <div class="col-md-7 col-sm-8 col-xs-12">
                <div class="input-group">
                    <input type="text" id="khoa-search" class="form-control"
                           placeholder="Nhập mã khoa, tên khoa hoặc ghi chú...">
                    <span class="input-group-btn">
                        <button type="button" id="btn-search-khoa" class="btn btn-primary">
                            <i class="fa fa-search"></i> Tìm
                        </button>
                        <button type="button" id="btn-reset-khoa" class="btn btn-default">
                            <i class="fa fa-refresh"></i> Làm mới
                        </button>
                    </span>
                </div>
            </div>
        </div>
        <table id="datatable-khoa" class="table table-striped table-bordered" width="100%">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã khoa</th>
                    <th>Tên khoa</th>
                    <th>Khoa gốc</th>
                    <th>Ghi chú</th>
                    <th>Hiệu lực</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<style>
    #datatable-khoa { width:100%!important; table-layout:fixed; font-size:12px; }
    #datatable-khoa th, #datatable-khoa td { padding:6px 5px; vertical-align:middle; }
    #datatable-khoa th { text-align:center; font-size:11px; }
    #datatable-khoa .khoa-text {
        display:block;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }
    #datatable-khoa .khoa-status { font-size:17px; cursor:help; }
    #datatable-khoa_wrapper .dataTables_scrollBody { border-bottom:1px solid #ddd; }
    #datatable-khoa_wrapper .dataTables_info { padding-top:10px; }
</style>
<?php
if (empty($_SESSION['catalog_csrf'])) {
    $_SESSION['catalog_csrf'] = bin2hex(random_bytes(32));
}
?>
<div class="catalog-crud x_panel" data-module="khoa"
     data-table-id="datatable-khoa"
     data-csrf="<?= htmlspecialchars($_SESSION['catalog_csrf'], ENT_QUOTES, 'UTF-8') ?>">
    <div class="catalog-crud-toolbar">
        <button type="button" class="btn btn-success catalog-add" disabled><i class="fa fa-plus"></i> Thêm mới</button>
        <button type="button" class="btn btn-info catalog-view" disabled><i class="fa fa-eye"></i> Xem</button>
        <button type="button" class="btn btn-primary catalog-edit" disabled><i class="fa fa-pencil"></i> Sửa</button>
        <button type="button" class="btn btn-danger catalog-delete" disabled><i class="fa fa-trash"></i> Xóa</button>
        <button type="button" class="btn btn-default catalog-template" disabled><i class="fa fa-download"></i> Excel mẫu</button>
        <button type="button" class="btn btn-warning catalog-import" disabled><i class="fa fa-upload"></i> Import Excel</button>
        <input type="file" class="catalog-import-file" accept=".xlsx" style="display:none">
        <span class="catalog-selection text-muted">Chọn một dòng trong bảng để xem, sửa hoặc xóa.</span>
    </div>
    <div class="modal fade catalog-editor" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Thông tin danh mục</h4>
                </div>
                <form class="catalog-form">
                    <div class="modal-body"><div class="row catalog-fields"></div></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary catalog-submit">Lưu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<style>
    .catalog-crud { margin-top: 12px; }
    .catalog-crud-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:6px; }
    .catalog-crud-toolbar .btn { margin-bottom:0; }
    .catalog-crud-toolbar .catalog-selection { margin-left:8px; }
    .catalog-crud .catalog-fields .form-group { min-height:68px; }
    .catalog-crud .catalog-fields textarea { resize:vertical; }
    table.dataTable tbody tr.catalog-selected, table.dataTable tbody tr.catalog-selected td { background-color:#def3eb !important; }
    table.dataTable tbody tr { cursor:pointer; }
</style>
