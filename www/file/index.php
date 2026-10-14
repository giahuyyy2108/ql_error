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
                    <th>Mã LK</th>
                    <th>Mã bệnh nhân</th>
                    <th>Tên bệnh nhân</th>
                    <th>Trạng thái</th>
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
                        <div id="xml-drop-zone" class="xml-drop-zone" role="button" tabindex="0"
                             aria-label="Kéo thả hoặc chọn file XML để tải lên">
                            <i class="fa fa-cloud-upload xml-drop-zone-icon" aria-hidden="true"></i>
                            <strong>Kéo và thả một hoặc nhiều file XML vào đây</strong>
                            <span>hoặc bấm để chọn các file</span>
                            <span id="xml-drop-file-name" class="xml-drop-file-name"></span>
                            <input type="file" id="xmlFile" name="xmlFile"
                                   accept=".xml,text/xml,application/xml" multiple required>
                        </div>
                        <small class="form-text text-muted">Các file sẽ tự động tải lần lượt. Dung lượng tối đa mỗi file là 5 MB.</small>
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
                <h5 class="modal-title">Xem hồ sơ bệnh nhân
                    <span id="view-file-name"></span></h5>
                <div class="xml-view-toolbar btn-group" role="group" aria-label="Chế độ xem XML">
                    <button type="button" class="btn btn-sm btn-primary active" data-xml-view="source"><i class="fa fa-code"></i> XML</button>
                    <button type="button" class="btn btn-sm btn-default" data-xml-view="tree"><i class="fa fa-sitemap"></i> Dạng cây</button>
                    <button type="button" class="btn btn-sm btn-default" data-xml-view="table"><i class="fa fa-table"></i> Dạng bảng</button>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="xml-view-layout">
                    <div class="xml-view-content">
                        <div class="form-group xml-file-selector">
                            <div class="xml-file-picker">
                                <label for="decoded-file-select" class="sr-only">Chọn nội dung hồ sơ</label>
                                <select id="decoded-file-select" class="sr-only" aria-hidden="true" tabindex="-1"></select>
                                <div id="decoded-file-tabs" class="decoded-file-tabs" role="tablist"
                                     aria-label="Chọn loại XML"></div>
                            </div>
                        </div>
                        <div id="validation-summary" class="alert" style="display:none"></div>
                        <div id="validation-valid-summary" class="alert alert-success" style="display:none"></div>
                        <div id="decoded-json-tree" class="json-tree"></div>
                        <div id="decoded-xml-source" class="xml-source-view" aria-label="Nội dung XML chỉ đọc"></div>
                        <div id="decoded-table-view" class="decoded-table-view" aria-label="Nội dung XML dạng bảng"></div>
                    </div>
                    <aside id="cost-reconciliation" class="cost-reconciliation">
                        <div class="cost-reconciliation-panel">
                            <h4>Đối chiếu chi phí</h4>
                            <div id="cost-reconciliation-content" class="text-muted">Đang tính...</div>
                        </div>
                        <div id="xml-source-navigation" class="xml-source-navigation">
                            <h4>Cây nội dung XML</h4>
                            <input type="text" id="xml-navigation-search" class="form-control"
                                   placeholder="Tìm kiếm" aria-label="Tìm nhóm trong XML">
                            <div id="xml-navigation-items" class="xml-navigation-items"></div>
                        </div>
                    </aside>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btn-export-view-excel" class="btn btn-primary" disabled>
                    <i class="fa fa-file-excel-o"></i> Xuất Excel
                </button>
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
    .xml-drop-zone {
        display: flex;
        min-height: 175px;
        padding: 24px;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 7px;
        color: #52606d;
        background: #f8fbfe;
        border: 2px dashed #8bb8db;
        border-radius: 8px;
        cursor: pointer;
        text-align: center;
        transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease;
    }
    .xml-drop-zone:hover,
    .xml-drop-zone:focus,
    .xml-drop-zone.is-dragging {
        color: #245580;
        background: #eaf5fd;
        border-color: #337ab7;
        box-shadow: 0 0 0 3px rgba(51, 122, 183, .15);
        outline: 0;
    }
    .xml-drop-zone.is-uploading { cursor: wait; opacity: .7; }
    .xml-drop-zone-icon { font-size: 42px; color: #337ab7; }
    .xml-drop-file-name {
        display: none;
        max-width: 100%;
        margin-top: 5px;
        padding: 5px 10px;
        overflow: hidden;
        color: #245580;
        background: #d9edf7;
        border-radius: 12px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    #xmlFile {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
    }
    #upload-message { white-space: pre-line; }
    #datatable-file_wrapper .dataTables_filter { display: none; }
    .file-table-scroll {
        position: relative;
        width: 100%;
        overflow-x: hidden;
        overflow-y: auto;
        max-height: 65vh;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        border-bottom: 1px solid #ddd;
    }
    .file-table-scroll.is-dragging-file {
        background: #eaf5fd;
        box-shadow: inset 0 0 0 3px #337ab7;
    }
    .file-table-scroll.is-dragging-file:after {
        content: 'Thả file XML để tải lên';
        position: absolute;
        z-index: 10;
        top: 50%;
        left: 50%;
        padding: 12px 20px;
        color: #245580;
        background: rgba(255, 255, 255, .95);
        border: 2px dashed #337ab7;
        border-radius: 6px;
        font-size: 16px;
        font-weight: 600;
        pointer-events: none;
        transform: translate(-50%, -50%);
    }
    #datatable-file_wrapper { width:100%; }
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
    #datatable-file thead th {
        position: sticky;
        top: 0;
        z-index: 3;
        background: #fff;
        box-shadow: 0 1px 0 #ddd;
    }
    #datatable-file.dataTable thead th.sorting,
    #datatable-file.dataTable thead th.sorting_asc,
    #datatable-file.dataTable thead th.sorting_desc {
        padding-right: 28px;
        cursor: pointer;
    }
    #datatable-file.dataTable thead th.sorting:after,
    #datatable-file.dataTable thead th.sorting_asc:after,
    #datatable-file.dataTable thead th.sorting_desc:after {
        position: absolute;
        top: 50%;
        right: 8px;
        bottom: auto;
        display: block;
        width: 14px;
        color: #337ab7;
        font-family: Arial, sans-serif;
        font-size: 11px;
        line-height: 1;
        text-align: center;
        transform: translateY(-50%);
    }
    #datatable-file.dataTable thead th.sorting:after {
        content: "\2195" !important;
        opacity: .45;
    }
    #datatable-file.dataTable thead th.sorting_asc:after {
        content: "\25B2" !important;
        opacity: 1;
    }
    #datatable-file.dataTable thead th.sorting_desc:after {
        content: "\25BC" !important;
        opacity: 1;
    }
    #datatable-file th:nth-child(1), #datatable-file td:nth-child(1) { width: 4% !important; }
    #datatable-file th:nth-child(2), #datatable-file td:nth-child(2) { width: 4% !important; }
    #datatable-file th:nth-child(3), #datatable-file td:nth-child(3) { width: 25% !important; }
    #datatable-file th:nth-child(4), #datatable-file td:nth-child(4) {
        width: 20% !important;
        overflow-wrap: anywhere;
        font-size: 12px;
    }
    #datatable-file th:nth-child(5), #datatable-file td:nth-child(5) { width: 9% !important; }
    #datatable-file th:nth-child(6), #datatable-file td:nth-child(6) { width: 14% !important; }
    #datatable-file th:nth-child(7), #datatable-file td:nth-child(7) { width: 14% !important; }
    #datatable-file th:nth-child(8), #datatable-file td:nth-child(8) { width: 10% !important; }
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
        #datatable-file th:nth-child(3), #datatable-file td:nth-child(3) { width: 28% !important; }
        #datatable-file th:nth-child(4), #datatable-file td:nth-child(4) { width: 22% !important; }
    }
    @media (max-width: 767px) {
        #datatable-file th:nth-child(1), #datatable-file td:nth-child(1),
        #datatable-file th:nth-child(2), #datatable-file td:nth-child(2),
        #datatable-file th:nth-child(7), #datatable-file td:nth-child(7) { display: none; }
        #datatable-file th:nth-child(3), #datatable-file td:nth-child(3) { width: 31% !important; }
        #datatable-file th:nth-child(4), #datatable-file td:nth-child(4) { width: 27% !important; }
        #datatable-file th:nth-child(6), #datatable-file td:nth-child(6) { width: 24% !important; }
        #datatable-file th:nth-child(8), #datatable-file td:nth-child(8) { width: 18% !important; }
        .file-status {
            width: 92px;
            min-width: 92px;
            padding: 6px;
            letter-spacing: 0;
        }
    }
    /* Bảng file hiện có 7 cột, bao gồm mã và tên bệnh nhân. */
    .file-table-scroll { overflow-x: auto; }
    #datatable-file { min-width: 850px; }
    #datatable-file th:nth-child(1), #datatable-file td:nth-child(1) { width: 5% !important; }
    #datatable-file th:nth-child(2), #datatable-file td:nth-child(2) { width: 6% !important; }
    #datatable-file th:nth-child(3), #datatable-file td:nth-child(3) { width: 25% !important; }
    #datatable-file th:nth-child(4), #datatable-file td:nth-child(4) { width: 16% !important; }
    #datatable-file th:nth-child(5), #datatable-file td:nth-child(5) { width: 24% !important; }
    #datatable-file th:nth-child(6), #datatable-file td:nth-child(6) { width: 14% !important; }
    #datatable-file th:nth-child(7), #datatable-file td:nth-child(7) { width: 10% !important; }
    @media (max-width: 1199px) {
        #datatable-file th, #datatable-file td { display: table-cell !important; }
    }

    #modal-view-xml .modal-dialog {
        width: calc(100vw - 40px);
        max-width: 1500px;
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
        position: relative;
        display: block;
        border-bottom: 1px solid #e5e5e5;
    }
    #modal-view-xml .modal-header .modal-title {
        padding-right: 40px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    #modal-view-xml .modal-header .close {
        position: absolute;
        top: 14px;
        right: 15px;
        margin: 0;
    }
    #modal-view-xml .modal-header .xml-view-toolbar {
        display: inline-flex;
        margin-top: 10px;
    }
    #modal-view-xml .modal-footer {
        border-top: 1px solid #e5e5e5;
    }
    #modal-view-xml .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden;
        overscroll-behavior: contain;
    }
    .xml-view-layout {
        display: grid;
        grid-template-columns: minmax(420px, 1fr) minmax(0, 2fr);
        gap: 15px;
        height: 100%;
    }
    .xml-view-layout.is-content-only { grid-template-columns: minmax(0, 1fr); }
    .xml-view-content {
        min-width: 0;
        min-height: 0;
        overflow-y: auto;
        order: 2;
    }
    .xml-view-content.is-table-mode {
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .xml-view-content.is-table-mode .xml-file-selector { flex: 0 0 auto; }
    .xml-file-selector {
        position: sticky;
        top: 0;
        z-index: 5;
        margin: 0 0 12px;
        padding: 2px 0 10px;
        background: #fff;
        border-bottom: 1px solid #e5e5e5;
    }
    .xml-view-toolbar { margin-top: 8px; }
    .decoded-file-tabs {
        display: flex;
        width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        border-bottom: 1px solid #c8d0d8;
        scrollbar-width: thin;
    }
    .decoded-file-tab {
        flex: 0 0 auto;
        min-width: 74px;
        padding: 9px 15px 8px;
        color: #0066cc;
        background: transparent;
        border: 1px solid transparent;
        border-bottom: 0;
        border-radius: 9px 9px 0 0;
        font-family: "Times New Roman", serif;
        font-size: 14px;
        font-weight: 700;
        text-align: center;
    }
    .decoded-file-tab:hover,
    .decoded-file-tab:focus { color: #004b99; background: #f3f6f9; outline: 0; }
    .decoded-file-tab.is-active {
        color: #111;
        background: #f3f4f6;
        border-color: #c8d0d8;
        box-shadow: 0 2px 0 #fff;
    }
    .xml-source-view {
        min-width: max-content;
        padding: 8px 0;
        color: #202124;
        background: #fff;
        font: 13px/1.55 Consolas, "Courier New", monospace;
        counter-reset: xml-line;
    }
    .xml-source-line {
        display: block;
        min-height: 20px;
        padding: 0 14px 0 62px;
        white-space: pre;
        counter-increment: xml-line;
    }
    .xml-source-line:before {
        content: counter(xml-line);
        display: inline-block;
        width: 45px;
        margin-left: -58px;
        margin-right: 13px;
        color: #999;
        text-align: right;
        user-select: none;
        border-right: 1px solid #e5e5e5;
        padding-right: 8px;
    }
    .xml-source-line:hover { background: #f5f7fa; }
    .xml-source-line.is-target-line {
        min-width: 0;
        padding-top: 4px;
        padding-bottom: 4px;
        background: #fff2cc;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }
    .xml-token-tag { color: #005cc5; }
    .xml-token-value { color: #24292e; }
    .decoded-table-view {
        flex: 1 1 auto;
        min-height: 0;
        width: 100%;
        overflow: auto;
        border: 1px solid #dfe3e8;
    }
    .decoded-table-view table {
        width: max-content;
        min-width: 100%;
        margin: 0;
        background: #fff;
        font-family: "Times New Roman", serif;
        font-size: 13px;
    }
    .decoded-table-view th,
    .decoded-table-view td {
        min-width: 110px;
        max-width: 360px;
        padding: 7px 9px !important;
        overflow: hidden;
        vertical-align: top !important;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
    .decoded-table-view th {
        position: sticky;
        top: 0;
        z-index: 4;
        color: #1f2d3d;
        background: #eef3f7;
        border-bottom: 2px solid #aeb8c2 !important;
    }
    .decoded-table-view .decoded-table-sort {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        width: 100%;
        padding: 0;
        color: inherit;
        background: transparent;
        border: 0;
        font: inherit;
        font-weight: 700;
        text-align: left;
        cursor: pointer;
    }
    .decoded-table-view .decoded-table-sort:focus {
        outline: 2px solid rgba(51, 122, 183, .45);
        outline-offset: 2px;
    }
    .decoded-table-view .decoded-table-sort-icon {
        flex: 0 0 14px;
        color: #337ab7;
        font-family: Arial, sans-serif;
        font-size: 10px;
        text-align: center;
    }
    .decoded-table-view td:hover {
        overflow: visible;
        white-space: normal;
        overflow-wrap: anywhere;
    }
    .decoded-table-view td.table-number-cell {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }
    .decoded-table-view tbody tr.table-row-warning > td { border-top-color: #e5cf86; border-bottom-color: #e5cf86; }
    .decoded-table-view td.table-cell-error {
        color: #a94442 !important;
        background: #f4cccc !important;
        font-weight: 700;
        box-shadow: inset 0 0 0 2px #d9534f;
    }
    .decoded-table-view td.table-cell-warning {
        color: #8a6d3b !important;
        background: #fff2cc !important;
        font-weight: 700;
        box-shadow: inset 0 0 0 2px #f0ad4e;
    }
    .decoded-table-view .xml2-frozen-stt {
        position: sticky;
        left: 0;
        z-index: 3;
        min-width: 70px;
        width: 70px;
        max-width: 70px;
        background: #fff;
        box-shadow: 1px 0 0 #ccd4db;
    }
    .decoded-table-view .xml2-frozen-ten-thuoc {
        position: sticky;
        left: 70px;
        z-index: 3;
        min-width: 260px;
        width: 260px;
        max-width: 260px;
        background: #fff;
        box-shadow: 2px 0 3px rgba(0, 0, 0, .14);
    }
    .decoded-table-view .xml2-frozen-ma-thuoc {
        position: sticky;
        left: 330px;
        z-index: 3;
        min-width: 130px;
        width: 130px;
        max-width: 130px;
        background: #fff;
        box-shadow: 2px 0 3px rgba(0, 0, 0, .14);
    }
    .decoded-table-view thead .xml2-frozen-stt,
    .decoded-table-view thead .xml2-frozen-ten-thuoc,
    .decoded-table-view thead .xml2-frozen-ma-thuoc {
        z-index: 5;
        background: #eef3f7;
    }
    .decoded-table-view tbody tr:nth-of-type(odd) .xml2-frozen-stt,
    .decoded-table-view tbody tr:nth-of-type(odd) .xml2-frozen-ten-thuoc,
    .decoded-table-view tbody tr:nth-of-type(odd) .xml2-frozen-ma-thuoc { background: #f9f9f9; }
    .decoded-table-view .xml3-frozen-stt {
        position: sticky;
        left: 0;
        z-index: 3;
        min-width: 70px;
        width: 70px;
        max-width: 70px;
        background: #fff;
        box-shadow: 1px 0 0 #ccd4db;
    }
    .decoded-table-view .xml3-frozen-ten-dich-vu {
        position: sticky;
        left: 70px;
        z-index: 3;
        min-width: 300px;
        width: 300px;
        max-width: 300px;
        background: #fff;
        box-shadow: 2px 0 3px rgba(0, 0, 0, .14);
    }
    .decoded-table-view thead .xml3-frozen-stt,
    .decoded-table-view thead .xml3-frozen-ten-dich-vu {
        z-index: 5;
        background: #eef3f7;
    }
    .decoded-table-view tbody tr:nth-of-type(odd) .xml3-frozen-stt,
    .decoded-table-view tbody tr:nth-of-type(odd) .xml3-frozen-ten-dich-vu { background: #f9f9f9; }
    .cost-reconciliation {
        display: flex;
        flex-direction: column;
        gap: 14px;
        min-width: 0;
        min-height: 0;
        order: 1;
        overflow-y: auto;
        overflow-x: hidden;
    }
    .cost-reconciliation-panel,
    .xml-source-navigation {
        padding: 12px;
        background: #f8fbfe;
        border: 1px solid #d9e6ef;
        border-radius: 5px;
    }
    .cost-reconciliation-panel { overflow-x: auto; }
    .cost-reconciliation h4 { margin: 0 0 10px; }
    .cost-reconciliation .table { margin-bottom: 8px; background: #fff; }
    .cost-reconciliation th,
    .cost-reconciliation td {
        padding: 5px 6px !important;
        text-align: right;
        white-space: nowrap;
        font-size: 11px;
    }
    .cost-reconciliation th:first-child,
    .cost-reconciliation td:first-child { text-align: left; }
    .cost-reconciliation .cost-match { color: #218838; font-weight: 700; }
    .cost-reconciliation .cost-mismatch { color: #a94442; font-weight: 700; }
    .xml-source-navigation {
        flex: 1 1 auto;
        min-height: 180px;
        overflow-y: auto;
    }
    .xml-navigation-items { margin-top: 9px; }
    .xml-navigation-item {
        display: block;
        width: 100%;
        padding: 5px 7px;
        overflow: hidden;
        color: #1f2d3d;
        background: transparent;
        border: 0;
        text-align: left;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .xml-navigation-item:hover,
    .xml-navigation-item:focus { color: #005cc5; background: #eaf5fd; outline: 0; }
    .xml-navigation-item.is-active { color: #fff; background: #337ab7; }
    .xml-navigation-item.is-active .fa { color: #fff; }
    .xml-navigation-item .fa { width: 20px; color: #54a7ff; }
    .xml-navigation-folder { font-weight: 600; }
    .xml-navigation-folder .xml-folder-toggle { color: #7aaeff; }
    .xml-navigation-children {
        display: none;
        margin-left: 17px;
        padding-left: 8px;
        border-left: 1px dotted #b7c5d1;
    }
    .xml-navigation-group.is-open > .xml-navigation-children { display: block; }
    .xml-navigation-field { font-weight: 400; }
    .xml-navigation-field .fa { color: #67c7cf; }
    @media (max-width: 1100px) {
        #modal-view-xml .modal-body { overflow-y: auto; }
        .xml-view-layout { grid-template-columns: 1fr; height: auto; }
        .cost-reconciliation { order: 1; overflow-x: hidden; }
        .xml-view-content { order: 2; overflow-y: visible; }
        .xml-file-selector { top: -15px; }
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
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    .json-tree .json-error-key { color: #a94442; }
    .json-tree .json-error-row .json-key,
    .json-tree .json-error-row .json-string,
    .json-tree .json-error-row .json-number,
    .json-tree .json-error-row .json-null { color: #a94442; }
    .json-tree .copy-error-value {
        color: #a94442;
        font-weight: 700;
        cursor: copy;
        user-select: none;
    }
    .json-tree .copy-error-value.copy-success { color: #218838; }
    .json-tree .copy-valid-value {
        color: #218838;
        font-weight: 700;
        cursor: copy;
        user-select: none;
    }
    .json-tree .copy-valid-value.copy-success { color: #155724; }
    .json-tree .copy-error-status { margin-left: 5px; color: #218838; font-weight: 600; }
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
