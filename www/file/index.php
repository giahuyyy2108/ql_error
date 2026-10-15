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

<?php require __DIR__ . '/partials/upload-modal.php'; ?>
<?php require __DIR__ . '/partials/view-modal.php'; ?>
<?php require __DIR__ . '/partials/delete-modal.php'; ?>
