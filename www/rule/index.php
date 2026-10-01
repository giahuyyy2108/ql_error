<div class="x_panel">
    <div class="x_title">
        <h2>Danh mục loại Rule</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><button type="button" id="btn-add-rule-type" class="btn btn-primary"><i class="fa fa-plus"></i> Thêm loại rule</button></li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <table id="datatable-rule-types" class="table table-striped table-bordered rule-catalog-table" width="100%">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã rule</th>
                    <th>Tên hiển thị</th>
                    <th>Mô tả</th>
                    <th>Gợi ý giá trị</th>
                    <th>Cần giá trị</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<style>
    #datatable-rule-types {
        width: 100% !important;
        table-layout: fixed;
        font-size: 12px;
    }
    #datatable-rule-types th,
    #datatable-rule-types td {
        padding: 5px 4px;
        vertical-align: middle;
        white-space: normal;
        word-break: break-word;
    }
    #datatable-rule-types th {
        text-align: center;
        font-size: 11px;
    }
    #datatable-rule-types .rule-catalog-ellipsis {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    #datatable-rule-types .rule-catalog-actions {
        position: sticky;
        right: 0;
        z-index: 2;
        white-space: nowrap;
        text-align: center;
        background: #fff;
        box-shadow: -3px 0 4px rgba(0, 0, 0, 0.08);
    }
    #datatable-rule-types thead th:last-child {
        position: sticky;
        right: 0;
        z-index: 3;
        background: #f2f2f2;
        box-shadow: -3px 0 4px rgba(0, 0, 0, 0.08);
    }
    #datatable-rule-types tbody tr:nth-child(odd) .rule-catalog-actions { background: #f9f9f9; }
    #datatable-rule-types tbody tr:nth-child(even) .rule-catalog-actions { background: #fff; }
    #datatable-rule-types .rule-catalog-actions .btn {
        margin: 0 1px;
        padding: 3px 6px;
        min-width: 27px;
    }
    #datatable-rule-types .rule-catalog-status {
        font-size: 18px;
        cursor: help;
    }
</style>

<input type="hidden" id="rule-catalog-csrf" value="<?=htmlspecialchars($request->getAttribute('ruleCatalogCsrf'), ENT_QUOTES, 'UTF-8')?>">

<div class="modal fade" id="modal-rule-type" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="form-rule-type">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm loại rule</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="rule-type-id">
                    <div class="row">
                        <div class="form-group col-md-5">
                            <label for="catalog-code">Mã rule <span class="text-danger">*</span></label>
                            <input type="text" id="catalog-code" class="form-control" maxlength="50" placeholder="Ví dụ: REQUIRED" required>
                        </div>
                        <div class="form-group col-md-7">
                            <label for="catalog-display-name">Tên hiển thị <span class="text-danger">*</span></label>
                            <input type="text" id="catalog-display-name" class="form-control" maxlength="100" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="catalog-description">Mô tả <span class="text-danger">*</span></label>
                        <textarea id="catalog-description" class="form-control" rows="3" maxlength="500" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="catalog-value-hint">Gợi ý giá trị</label>
                        <input type="text" id="catalog-value-hint" class="form-control" maxlength="255" placeholder="Ví dụ: 15 hoặc /^\\d{8}$/">
                    </div>
                    <div class="row">
                        <div class="checkbox col-md-6">
                            <label><input type="checkbox" id="catalog-requires-value"> Bắt buộc nhập giá trị rule</label>
                        </div>
                        <div class="checkbox col-md-6">
                            <label><input type="checkbox" id="catalog-active" checked> Đang hoạt động</label>
                        </div>
                    </div>
                    <div id="rule-type-message" class="alert" style="display:none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" id="btn-save-rule-type" class="btn btn-primary"><i class="fa fa-save"></i> Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-delete-rule-type" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Xác nhận xóa</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">
                Bạn có chắc muốn xóa loại rule <strong id="delete-rule-type-name"></strong>?
                <p class="text-muted">Không thể xóa loại rule đang được sử dụng trong XML Validation Rules.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" id="btn-confirm-delete-rule-type" class="btn btn-danger"><i class="fa fa-trash"></i> Xóa</button>
            </div>
        </div>
    </div>
</div>
