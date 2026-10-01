<div class="x_panel">
    <div class="x_title">
        <h2>XML Validation Rules</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><button type="button" id="btn-add-rule" class="btn btn-primary"><i class="fa fa-plus"></i> Thêm rule</button></li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <table id="datatable-rules" class="table table-striped table-bordered rules-table" width="100%">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Loại file</th>
                    <th>Tên trường</th>
                    <th>Tên hiển thị</th>
                    <th>Loại rule</th>
                    <th>Giá trị</th>
                    <th>Thông báo lỗi</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<style>
    #datatable-rules {
        width: 100% !important;
        table-layout: fixed;
        font-size: 12px;
    }
    #datatable-rules th,
    #datatable-rules td {
        padding: 5px 4px;
        vertical-align: middle;
        white-space: normal;
        word-break: break-word;
    }
    #datatable-rules th {
        text-align: center;
        font-size: 11px;
    }
    #datatable-rules .rule-cell-ellipsis {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    #datatable-rules .rule-actions {
        white-space: nowrap;
        text-align: center;
        background: #fff;
        position: sticky;
        right: 0;
        z-index: 2;
        box-shadow: -3px 0 4px rgba(0, 0, 0, 0.08);
    }
    #datatable-rules thead th:last-child {
        position: sticky;
        right: 0;
        z-index: 3;
        background: #f2f2f2;
        box-shadow: -3px 0 4px rgba(0, 0, 0, 0.08);
    }
    #datatable-rules tbody tr:nth-child(odd) .rule-actions { background: #f9f9f9; }
    #datatable-rules tbody tr:nth-child(even) .rule-actions { background: #fff; }
    #datatable-rules .rule-actions .btn {
        margin: 0 1px;
        padding: 3px 6px;
        min-width: 27px;
    }
    #datatable-rules .rule-status-icon {
        font-size: 18px;
        cursor: help;
    }
    #datatable-rules_wrapper .dataTables_length,
    #datatable-rules_wrapper .dataTables_filter {
        font-size: 12px;
    }
    #datatable-rules_wrapper .dataTables_scrollBody,
    #datatable-rules_wrapper .dataTables_scrollHead {
        overflow: visible !important;
    }
</style>

<input type="hidden" id="rules-csrf" value="<?=htmlspecialchars($request->getAttribute('rulesCsrf'), ENT_QUOTES, 'UTF-8')?>">

<div class="modal fade" id="modal-rule" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="form-rule">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm rule</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="rule-id" name="id">
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label for="rule-file-type">Loại file <span class="text-danger">*</span></label>
                            <input type="text" id="rule-file-type" name="file_type" class="form-control" maxlength="10" placeholder="XML1" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="rule-field-name">Tên trường <span class="text-danger">*</span></label>
                            <input type="text" id="rule-field-name" name="field_name" class="form-control" maxlength="100" placeholder="MA_LK" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="rule-display-name">Tên hiển thị <span class="text-danger">*</span></label>
                            <input type="text" id="rule-display-name" name="display_name" class="form-control" maxlength="255" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label for="rule-type">Loại rule <span class="text-danger">*</span></label>
                            <select id="rule-type" name="rule_type" class="form-control" required>
                                <option value="">-- Chọn loại rule --</option>
                            </select>
                            <small id="rule-type-description" class="form-text text-muted"></small>
                        </div>
                        <div class="form-group col-md-8">
                            <label for="rule-value">Giá trị rule</label>
                            <textarea id="rule-value" name="rule_value" class="form-control" rows="4" placeholder="Ví dụ: 15 hoặc /^\\d{8}$/"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="rule-error-message">Thông báo lỗi <span class="text-danger">*</span></label>
                        <textarea id="rule-error-message" name="error_message" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="checkbox">
                        <label><input type="checkbox" id="rule-active" checked> Đang hoạt động</label>
                    </div>
                    <div id="rule-message" class="alert" style="display:none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" id="btn-save-rule" class="btn btn-primary"><i class="fa fa-save"></i> Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-delete-rule" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Xác nhận xóa</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">Bạn có chắc muốn xóa rule <strong id="delete-rule-name"></strong>?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" id="btn-confirm-delete-rule" class="btn btn-danger"><i class="fa fa-trash"></i> Xóa</button>
            </div>
        </div>
    </div>
</div>
