<div class="x_panel">
    <div class="x_title">
        <h2>Danh sách file XML</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li>
                <button type="button" id="btn-export-file-excel" class="btn btn-success">
                    <i class="fa fa-file-excel-o"></i> Xuất Excel
                </button>
            </li>
            <li>
                <button type="button" id="btn-revalidate-all" class="btn btn-warning">
                    <i class="fa fa-refresh"></i> Quét lại tất cả
                </button>
            </li>
            <li>
                <button type="button" id="btn-delete-selected" class="btn btn-danger" disabled>
                    <i class="fa fa-trash"></i> Xóa đã chọn
                </button>
            </li>
            <li>
                <button type="button" id="btn-open-upload" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Thêm file
                </button>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <div class="row file-search-row">
            <div class="col-md-8 col-sm-9 col-xs-12">
                <div class="input-group">
                    <input type="search" id="file-search" class="form-control"
                           placeholder="Nhập tên file, MA_LK, trạng thái hoặc thời gian cập nhật..."
                           autocomplete="off" aria-label="Tìm kiếm file XML">
                    <span class="input-group-btn">
                        <button type="button" id="btn-search-file" class="btn btn-primary">
                            <i class="fa fa-search"></i> Tìm
                        </button>
                        <button type="button" id="btn-reset-file-search" class="btn btn-default">
                            <i class="fa fa-refresh"></i> Làm mới
                        </button>
                    </span>
                </div>
            </div>
        </div>
        <div class="file-table-scroll" tabindex="0" aria-label="Danh sách file có thể cuộn">
        <table id="datatable-file" class="table table-striped table-bordered" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th class="text-center"><input type="checkbox" id="select-all-files" title="Chọn tất cả file"></th>
                    <th>STT</th>
                    <th>Tên file</th>
                    <th>MA_LK</th>
                    <th>Dung lượng</th>
                    <th>Trạng thái</th>
                    <th>Cập nhật lúc</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        </div>
    </div>
</div>

<input type="hidden" id="xml-file-csrf" value="<?=htmlspecialchars($request->getAttribute('xmlFileCsrf'), ENT_QUOTES, 'UTF-8')?>">

<div class="modal fade" id="modal-upload-xml" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="form-upload-xml" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm file XML</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="xmlFile">Chọn file <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="xmlFile" name="xmlFile" accept=".xml,text/xml,application/xml" required>
                        <small class="form-text text-muted">Chỉ nhận file .xml hợp lệ, dung lượng tối đa 5 MB.</small>
                    </div>
                    <div id="upload-message" class="alert" style="display:none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" id="btn-upload-xml" class="btn btn-primary"><i class="fa fa-upload"></i> Tải lên</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-view-xml" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xem file XML: 
                    <span id="view-file-name"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="decoded-file-select"><strong>Chọn nội dung hồ sơ</strong></label>
                    <select id="decoded-file-select" class="form-control"></select>
                </div>
                <div id="validation-summary" class="alert" style="display:none"></div>
                <div id="validation-valid-summary" class="alert alert-success" style="display:none"></div>
                <div id="decoded-json-tree" class="json-tree"></div>
            </div>
            <div class="modal-footer">
                <a id="btn-download-view-file" class="btn btn-success" href="#" download rel="noopener">
                    <i class="fa fa-download"></i> Tải file XML
                </a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<style>
    .file-search-row { margin-bottom: 14px; }
    #datatable-file_wrapper .dataTables_filter { display: none; }
    .file-table-scroll {
        width: 100%;
        overflow-x: hidden;
    }
    #datatable-file_wrapper { width:100%; }
    #datatable-file_wrapper .dataTables_scrollBody {
        min-height:260px;
        border-bottom:1px solid #ddd;
        overflow-x:hidden !important;
        overscroll-behavior:contain;
        -webkit-overflow-scrolling:touch;
    }
    #datatable-file_wrapper .dataTables_scrollHead { background:#fff; }
    #datatable-file_wrapper .dataTables_info { padding-top:10px; }
    .file-table-scroll:focus { outline: 2px solid rgba(51, 122, 183, .25); }
    #datatable-file td.file-name-cell {
        max-width: 0;
    }
    #datatable-file .file-name-ellipsis {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    #datatable-file td.file-status-cell {
        vertical-align: middle;
    }
    #datatable-file {
        width: 100% !important;
        table-layout: fixed;
    }
    #datatable-file th,
    #datatable-file td {
        padding: 7px 6px;
        vertical-align: middle;
    }
    #datatable-file th:nth-child(1), #datatable-file td:nth-child(1) { width: 4%; }
    #datatable-file th:nth-child(2), #datatable-file td:nth-child(2) { width: 4%; }
    #datatable-file th:nth-child(3), #datatable-file td:nth-child(3) { width: 25%; }
    #datatable-file th:nth-child(4), #datatable-file td:nth-child(4) {
        width: 20%;
        overflow-wrap: anywhere;
        font-size: 12px;
    }
    #datatable-file th:nth-child(5), #datatable-file td:nth-child(5) { width: 9%; }
    #datatable-file th:nth-child(6), #datatable-file td:nth-child(6) { width: 14%; }
    #datatable-file th:nth-child(7), #datatable-file td:nth-child(7) { width: 14%; }
    #datatable-file th:nth-child(8), #datatable-file td:nth-child(8) { width: 10%; }
    #datatable-file td:last-child { white-space: nowrap; }
    #datatable-file .btn-file-action {
        width: 30px;
        height: 28px;
        padding: 4px 6px;
        margin: 0 1px;
    }
    .file-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        width: 108px;
        min-width: 108px;
        box-sizing: border-box;
        padding: 6px 8px;
        border: 1px solid transparent;
        border-radius: 18px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        letter-spacing: .25px;
        white-space: nowrap;
        box-shadow: 0 2px 5px rgba(0, 0, 0, .12);
    }
    .file-status i { font-size: 14px; }
    .file-status-success {
        color: #176b3a;
        background: #dff4e7;
        border-color: #8fd3a9;
    }
    .file-status-failed {
        color: #a12622;
        background: #fde3e2;
        border-color: #ef9a96;
    }
    .file-status-processing {
        color: #875600;
        background: #fff1c9;
        border-color: #f0c861;
    }
    @media (max-width: 1199px) {
        #datatable-file th:nth-child(5), #datatable-file td:nth-child(5) { display: none; }
        #datatable-file th:nth-child(3), #datatable-file td:nth-child(3) { width: 28%; }
        #datatable-file th:nth-child(4), #datatable-file td:nth-child(4) { width: 22%; }
    }
    @media (max-width: 767px) {
        #datatable-file th:nth-child(1), #datatable-file td:nth-child(1),
        #datatable-file th:nth-child(2), #datatable-file td:nth-child(2),
        #datatable-file th:nth-child(7), #datatable-file td:nth-child(7) { display: none; }
        #datatable-file th:nth-child(3), #datatable-file td:nth-child(3) { width: 31%; }
        #datatable-file th:nth-child(4), #datatable-file td:nth-child(4) { width: 27%; }
        #datatable-file th:nth-child(6), #datatable-file td:nth-child(6) { width: 24%; }
        #datatable-file th:nth-child(8), #datatable-file td:nth-child(8) { width: 18%; }
        .file-status {
            width: 92px;
            min-width: 92px;
            padding: 6px;
            letter-spacing: 0;
        }
    }
    #modal-view-xml .modal-dialog {
        height: calc(100vh - 40px);
        margin-top: 20px;
        margin-bottom: 20px;
    }
    #modal-view-xml .modal-content {
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
    }
    #modal-view-xml .modal-header,
    #modal-view-xml .modal-footer {
        flex: 0 0 auto;
        background: #fff;
        z-index: 2;
    }
    #modal-view-xml .modal-header {
        border-bottom: 1px solid #e5e5e5;
    }
    #modal-view-xml .modal-footer {
        border-top: 1px solid #e5e5e5;
    }
    #modal-view-xml .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
    }
    .json-tree {
        max-height: none;
        overflow-x: auto;
        overflow-y: visible;
        padding: 12px;
        border: 1px solid #d8d8d8;
        border-radius: 4px;
        background: #f7f7f7;
        font-family: Consolas, Monaco, monospace;
        font-size: 13px;
    }
    @media (max-width: 767px) {
        #modal-view-xml .modal-dialog {
            height: calc(100vh - 20px);
            margin: 10px;
        }
    }
    .json-tree details {
        margin-left: 18px;
        border-left: 1px dotted #bdbdbd;
        padding-left: 8px;
    }
    .json-tree > details {
        margin-left: 0;
        border-left: 0;
        padding-left: 0;
    }
    .json-tree summary {
        cursor: pointer;
        color: #1b4f72;
        font-weight: 600;
        padding: 2px 0;
    }
    .json-tree .json-value {
        margin-left: 27px;
        padding: 2px 0;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .json-tree .json-key { color: #922b21; font-weight: 600; }
    .json-tree .json-string { color: #196f3d; }
    .json-tree .json-number { color: #7d3c98; }
    .json-tree .json-null { color: #777; font-style: italic; }
    .json-tree .json-field-error {
        margin: 3px 0 6px 27px;
        padding: 5px 8px;
        color: #a94442;
        background: #f2dede;
        border-left: 3px solid #a94442;
        font-family: Arial, sans-serif;
    }
    .json-tree .json-error-key { color: #a94442; }
    .json-tree .json-warning-key { color: #8a6d3b; }
    .json-tree .json-error-count,
    .json-tree .json-warning-count {
        display: inline-block;
        margin-left: 8px;
        padding: 1px 7px;
        border-radius: 10px;
        color: #fff;
        font-family: Arial, sans-serif;
        font-size: 11px;
        font-weight: 600;
        line-height: 16px;
    }
    .json-tree .json-error-count { background: #d9534f; }
    .json-tree .json-warning-count { background: #f0ad4e; }
    .json-tree .json-valid-key { color: #218838; }
    .json-tree .json-field-valid {
        margin: 3px 0 6px 27px;
        padding: 5px 8px;
        color: #155724;
        background: #d4edda;
        border-left: 3px solid #28a745;
        font-family: Arial, sans-serif;
    }
    .json-tree .json-field-warning {
        color: #8a6d3b;
        background: #fcf8e3;
        border-left-color: #8a6d3b;
    }
    #validation-valid-summary .validation-valid-title {
        display: block;
        color: #155724;
    }
</style>

<div class="modal fade" id="modal-delete-xml" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">Bạn có chắc muốn xóa file <strong id="delete-file-name"></strong>?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" id="btn-confirm-delete" class="btn btn-danger"><i class="fa fa-trash"></i> Xóa</button>
            </div>
        </div>
    </div>
</div>
