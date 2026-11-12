<?php $hieuLucList = $request->getAttribute('hieuLucList'); ?>
<div class="x_panel">
    <div class="x_title">
        <h2>Vật tư y tế <small>Danh mục vật tư từ bảng vattuyte</small></h2>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <div class="alert alert-info">
            Tra cứu theo mã vật tư, tên vật tư, mã hiệu, quy cách, hãng sản xuất, nhà thầu, quyết định hoặc mã vật tư bệnh viện.
        </div>

        <div class="row" style="margin-bottom:12px">
            <div class="col-md-6 col-sm-8 col-xs-12">
                <div class="input-group">
                    <input type="search" id="vattu-yte-search" class="form-control"
                           placeholder="Nhập mã, tên vật tư, mã hiệu, hãng sản xuất..." autocomplete="off">
                    <span class="input-group-btn">
                        <button type="button" id="btn-search-vattu-yte" class="btn btn-primary">
                            <i class="fa fa-search"></i> Tìm
                        </button>
                    </span>
                </div>
            </div>
            <div class="col-md-3 col-sm-4 col-xs-12">
                <select id="vattu-yte-hieu-luc" class="form-control">
                    <option value="">Tất cả hiệu lực</option>
                    <?php foreach ((array) $hieuLucList as $hieuLuc): ?>
                        <option value="<?=htmlspecialchars($hieuLuc, ENT_QUOTES, 'UTF-8')?>">
                            <?=htmlspecialchars($hieuLuc, ENT_QUOTES, 'UTF-8')?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-4 col-xs-12">
                <button type="button" id="btn-reset-vattu-yte" class="btn btn-default">
                    <i class="fa fa-refresh"></i> Làm mới
                </button>
            </div>
        </div>

        <div class="vattu-yte-table-wrap">
            <table id="datatable-vattu-yte" class="table table-striped table-bordered" width="100%">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã vật tư</th>
                        <th>Nhóm vật tư</th>
                        <th>Tên vật tư</th>
                        <th>Mã hiệu</th>
                        <th>Quy cách</th>
                        <th>Hãng SX</th>
                        <th>Nước SX</th>
                        <th>ĐVT</th>
                        <th>Đơn giá</th>
                        <th>Đơn giá BH</th>
                        <th>Tỷ lệ TT BH</th>
                        <th>Nhà thầu</th>
                        <th>Mã CSKCB</th>
                        <th>Quyết định</th>
                        <th>Hiệu lực</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <div class="vattu-yte-page-nav">
            <button type="button" id="btn-vattu-yte-prev" class="btn btn-default">
                <i class="fa fa-chevron-left"></i> Trang trước
            </button>
            <span id="vattu-yte-page-info" class="vattu-yte-page-info">Trang 1 / 1</span>
            <button type="button" id="btn-vattu-yte-next" class="btn btn-primary">
                Trang tiếp <i class="fa fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>

<style>
    .vattu-yte-page-nav { display:flex; justify-content:center; align-items:center; gap:12px; margin-top:14px; }
    .vattu-yte-page-info { min-width:110px; text-align:center; font-weight:600; }
    .vattu-yte-page-nav .btn[disabled] { opacity:.45; }
    #datatable-vattu-yte .td-text {
        display:block;
        max-width:260px;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }
</style>
<?php
if (empty($_SESSION['catalog_csrf'])) {
    $_SESSION['catalog_csrf'] = bin2hex(random_bytes(32));
}
?>
<div class="catalog-crud x_panel" data-module="vattu_yte"
     data-table-id="datatable-vattu-yte"
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
