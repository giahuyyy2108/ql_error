<div class="x_panel">
    <div class="x_title">
        <h2>Cấu hình API kiểm tra XML</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><button id="btn-add-api-config" class="btn btn-primary"><i class="fa fa-plus"></i> Thêm cấu hình</button></li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <table id="datatable-api-config" class="table table-striped table-bordered dt-responsive nowrap" width="100%">
            <thead><tr>
                <th>STT</th><th>ID</th><th>Tên</th><th>Endpoint</th><th>Method</th><th>Timeout</th>
                <th>Response field</th><th>Trạng thái</th><th>Thao tác</th>
            </tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modal-test-api" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Test API: <span id="test-api-name"></span></h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body">
            <input type="hidden" id="test-api-id">
            <div class="row">
                <div class="form-group col-md-6"><label for="test-path-parameters">Path Parameters</label><textarea id="test-path-parameters" class="form-control" rows="4">{}</textarea></div>
                <div class="form-group col-md-6"><label for="test-query-parameters">Query Parameters</label><textarea id="test-query-parameters" class="form-control" rows="4">{}</textarea></div>
            </div>
            <div class="form-group"><label for="test-body-parameters">Body JSON</label><textarea id="test-body-parameters" class="form-control" rows="5">{}</textarea></div>
            <div id="test-api-message" class="alert" style="display:none"></div>
            <label>Response</label>
            <pre id="test-api-response" style="max-height:300px;overflow:auto;background:#f7f7f7;border:1px solid #ddd;padding:10px"></pre>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button><button id="btn-run-api-test" class="btn btn-success"><i class="fa fa-play"></i> Chạy test</button></div>
    </div></div>
</div>

<input type="hidden" id="api-validation-csrf" value="<?=htmlspecialchars($request->getAttribute('apiValidationCsrf'), ENT_QUOTES, 'UTF-8')?>">

<div class="modal fade" id="modal-api-config" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
        <form id="form-api-config">
            <div class="modal-header"><h5 class="modal-title">Thêm cấu hình API</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">
                <input type="hidden" id="api-config-id">
                <div class="form-group"><label for="api-name">Tên cấu hình <span class="text-danger">*</span></label><input id="api-name" class="form-control" maxlength="255" required></div>
                <div class="form-group"><label for="api-endpoint">Endpoint <span class="text-danger">*</span></label><input id="api-endpoint" type="text" class="form-control" maxlength="500" placeholder="https://api.example.com/patients/{id}" required><small class="form-text text-muted">Path parameter đặt trong dấu ngoặc nhọn, ví dụ {id}.</small></div>
                <div class="row">
                    <div class="form-group col-md-4"><label for="api-method">Method</label><select id="api-method" class="form-control"><option>POST</option><option>GET</option><option>PUT</option><option>PATCH</option></select></div>
                    <div class="form-group col-md-4"><label for="api-timeout">Timeout (giây)</label><input id="api-timeout" type="number" min="1" max="30" value="10" class="form-control" required></div>
                    <div class="form-group col-md-4"><label for="api-response-field">Response field <span class="text-danger">*</span></label><input id="api-response-field" class="form-control" placeholder="data.valid" required></div>
                </div>
                <div class="form-group">
                    <label for="api-headers">Headers JSON</label>
                    <textarea id="api-headers" class="form-control" rows="5" placeholder='{"Authorization":"env:API_ACCESS_TOKEN"}'>{}</textarea>
                    <small class="form-text text-muted">Dùng env:TEN_BIEN để không lưu token trực tiếp trong database.</small>
                </div>
                <div class="checkbox"><label><input id="api-active" type="checkbox" checked> Đang hoạt động</label></div>
                <div id="api-config-message" class="alert" style="display:none"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button><button id="btn-save-api-config" type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Lưu</button></div>
        </form>
    </div></div>
</div>

<div class="modal fade" id="modal-delete-api-config" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Xác nhận xóa</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body">Bạn có chắc muốn xóa cấu hình <strong id="delete-api-config-name"></strong>?</div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button><button id="btn-confirm-delete-api-config" class="btn btn-danger"><i class="fa fa-trash"></i> Xóa</button></div>
    </div></div>
</div>
