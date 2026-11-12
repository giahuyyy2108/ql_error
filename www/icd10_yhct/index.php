<div class="x_panel">
    <div class="x_title">
        <h2>Danh mục ICD-10 Y học cổ truyền <small>Quyết định 2552/QĐ-BYT</small></h2>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <div class="alert alert-info">
            Phụ lục I – Danh mục thể lâm sàng và mã thể lâm sàng theo bệnh danh Y học cổ truyền.
            Chỉ người có quyền quản lý mới được thay đổi dữ liệu chuẩn.
        </div>
        <div class="row icd10-yhct-search-row">
            <div class="col-md-8 col-sm-10 col-xs-12">
                <div class="input-group">
                    <input type="search" id="icd10-yhct-search" class="form-control"
                           placeholder="Tìm mã dùng chung, ICD-10, mã U, mã hóa hoặc tên bệnh...">
                    <span class="input-group-btn">
                        <button type="button" id="btn-search-icd10-yhct" class="btn btn-primary">
                            <i class="fa fa-search"></i> Tìm
                        </button>
                        <button type="button" id="btn-reset-icd10-yhct" class="btn btn-default">
                            <i class="fa fa-refresh"></i> Làm mới
                        </button>
                    </span>
                </div>
            </div>
        </div>
        <div class="icd10-yhct-table-wrap">
            <table id="datatable-icd10-yhct" class="table table-striped table-bordered" width="100%">
                <thead><tr>
                    <th>Mã dùng chung</th>
                    <th>Mã ICD-10</th>
                    <th>Bệnh danh YHCT</th>
                    <th>Mã U</th>
                    <th>Thể lâm sàng</th>
                    <th>Mã hóa</th>
                    <th>Tên bệnh YHHĐ</th>
                    <th>Trạng thái</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .icd10-yhct-search-row { margin-bottom: 12px; }
    .icd10-yhct-table-wrap { width: 100%; overflow-x: auto; }
    #datatable-icd10-yhct { width: 100% !important; font-size: 12px; }
    #datatable-icd10-yhct th, #datatable-icd10-yhct td { padding: 6px 5px; vertical-align: middle; }
    #datatable-icd10-yhct th { text-align: center; font-size: 11px; white-space: nowrap; }
    #datatable-icd10-yhct .catalog-text {
        display: block; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    #datatable-icd10-yhct .clinical-row { background: #f8fbff; }
    #datatable-icd10-yhct .code-cell { white-space: nowrap; font-family: Consolas, monospace; }
    #datatable-icd10-yhct .catalog-status { font-size: 16px; }
</style>
<?php
if (empty($_SESSION['catalog_csrf'])) {
    $_SESSION['catalog_csrf'] = bin2hex(random_bytes(32));
}
?>
<div class="catalog-crud x_panel" data-module="icd10_yhct"
     data-table-id="datatable-icd10-yhct"
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
