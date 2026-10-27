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
                <button type="button" id="btn-portal-history" class="btn btn-info"><i class="fa fa-history"></i> Lịch sử đẩy cổng</button>
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
                <div class="file-stat-card file-stat-warning">
                    <i class="fa fa-warning" aria-hidden="true"></i>
                    <div><strong id="file-stat-warning">0</strong><span>Cảnh báo</span></div>
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

<div class="modal fade" id="modal-portal-history" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Lịch sử đẩy cổng</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row" style="margin-bottom:15px">
                    <div class="col-md-4">
                        <label for="portal-history-date">Ngày đẩy</label>
                        <input type="date" id="portal-history-date" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="portal-history-malk">Mã liên kết</label>
                        <input type="text" id="portal-history-malk" class="form-control" placeholder="Nhập MALK">
                    </div>
                    <div class="col-md-4" style="padding-top:25px">
                        <button type="button" id="btn-search-portal-history" class="btn btn-primary"><i class="fa fa-search"></i> Tìm</button>
                        <button type="button" id="btn-reset-portal-history" class="btn btn-default"><i class="fa fa-refresh"></i> Làm mới</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-condensed">
                        <thead>
                            <tr>
                                <th>Thời gian</th>
                                <th>Người đẩy</th>
                                <th>MALK</th>
                                <th>Tên file</th>
                                <th>Mã BN</th>
                                <th>Khoa</th>
                            </tr>
                        </thead>
                        <tbody id="portal-history-list"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="xml-file-csrf" value="<?=htmlspecialchars($request->getAttribute('xmlFileCsrf'), ENT_QUOTES, 'UTF-8')?>">
<input type="hidden" id="current-user-khoa" value="<?=htmlspecialchars((string) $request->getAttribute('currentUserKhoa'), ENT_QUOTES, 'UTF-8')?>">

<?php require __DIR__ . '/partials/upload-modal.php'; ?>
<?php require __DIR__ . '/partials/view-modal.php'; ?>
<?php require __DIR__ . '/partials/delete-modal.php'; ?>

<div class="modal fade" id="modal-push-warning" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Xác nhận đẩy cổng</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body">
            <p>Hồ sơ có cảnh báo. Bạn vẫn muốn đẩy cổng?</p>
            <div class="table-responsive"><table class="table table-bordered table-condensed">
                <thead><tr><th>Rule</th><th>XML</th><th>Tên trường</th><th>Giá trị</th><th>Nội dung cảnh báo</th></tr></thead>
                <tbody id="push-warning-details"><tr><td colspan="5" class="text-center">Đang tải...</td></tr></tbody>
            </table></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Không</button>
            <button type="button" id="btn-confirm-push-warning" class="btn btn-warning">Đồng ý</button>
        </div>
    </div></div>
</div>
