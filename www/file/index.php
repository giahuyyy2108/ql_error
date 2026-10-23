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
                    <i class="fa fa-refresh"></i> Quét lại ngày đã chọn
                </button>
            </li>
            <li>
                <button type="button" id="btn-push-portal" class="btn btn-warning"><i class="fa fa-upload"></i> Đẩy cổng</button>
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
        <section class="file-statistics-section" aria-label="Thống kê hồ sơ XML theo ngày">
            <div class="file-statistics-header">
                <h3>Thống kê theo ngày</h3>
                <label for="file-stat-date" class="sr-only">Chọn ngày thống kê</label>
                <input type="text" id="file-stat-date" class="form-control" autocomplete="off">
            </div>
            <div class="file-statistics">
                <div class="file-stat-card file-stat-total">
                    <i class="fa fa-files-o" aria-hidden="true"></i>
                    <div><strong id="file-stat-total">0</strong><span>Tổng hồ sơ</span></div>
                </div>
                <div class="file-stat-card file-stat-success">
                    <i class="fa fa-check-circle" aria-hidden="true"></i>
                    <div><strong id="file-stat-success">0</strong><span>Thành công</span></div>
                </div>
                <div class="file-stat-card file-stat-failed">
                    <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                    <div><strong id="file-stat-failed">0</strong><span>Có lỗi</span></div>
                </div>
                <div class="file-stat-card file-stat-processing">
                    <i class="fa fa-refresh" aria-hidden="true"></i>
                    <div><strong id="file-stat-processing">0</strong><span>Đang xử lý</span></div>
                </div>
            </div>
        </section>
        <div class="row file-search-row">
            <div class="col-md-8 col-sm-9 col-xs-12">
                <div class="input-group">
                    <input type="search" id="file-search" class="form-control"
                           placeholder="Nhập mã BN, mã thẻ BHYT, thời gian điều trị hoặc số tiền..."
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
                        <th>Mã BN</th>
                        <th>Mã thẻ BHYT</th>
                        <th>Điều trị từ</th>
                        <th>Đến</th>
                        <th>Số lượng lỗi</th>
                        <th>Tên trường</th>
                        <th>Số tiền</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div id="file-table-controls" class="file-table-controls"></div>
    </div>
</div>

<input type="hidden" id="xml-file-csrf" value="<?=htmlspecialchars($request->getAttribute('xmlFileCsrf'), ENT_QUOTES, 'UTF-8')?>">
<input type="hidden" id="current-user-khoa" value="<?=htmlspecialchars((string) $request->getAttribute('currentUserKhoa'), ENT_QUOTES, 'UTF-8')?>">

<?php require __DIR__ . '/partials/upload-modal.php'; ?>
<?php require __DIR__ . '/partials/view-modal.php'; ?>
<?php require __DIR__ . '/partials/delete-modal.php'; ?>
