<div class="x_panel hoso-page">
    <div class="x_title">
        <h2>Hồ sơ XML theo khoa</h2>
        <div class="pull-right">
            <button type="button" id="hoso-open-upload" class="btn btn-primary"><i class="fa fa-plus"></i> Thêm file XML</button>
            <span class="hoso-export-toolbar">
                <label class="sr-only" for="hoso-export-period">Hình thức xuất Excel</label>
                <select id="hoso-export-period" class="form-control">
                    <option value="day">Theo ngày</option>
                    <option value="month">Theo tháng</option>
                </select>
                <label class="sr-only" for="hoso-export-month">Tháng cần xuất</label>
                <input type="text" id="hoso-export-month" class="form-control" lang="vi-VN" autocomplete="off"
                       value="<?=htmlspecialchars(date('m/Y', strtotime($request->getAttribute('hosoDate'))), ENT_QUOTES, 'UTF-8')?>"
                       style="display:none" disabled>
                <button type="button" id="hoso-export" class="btn btn-success"><i class="fa fa-file-excel-o"></i> Xuất Excel</button>
            </span>
            <button type="button" id="hoso-delete-all" class="btn btn-danger" disabled><i class="fa fa-trash"></i> Xóa tất cả (ngày đã chọn)</button>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <p class="text-muted">Chỉ hiển thị hồ sơ của khoa được gán cho tài khoản. Hồ sơ đã đẩy cổng chỉ được xem, không thể xóa. Có thể kéo thả XML vào danh sách bên dưới để thêm hồ sơ.</p>
        <div class="row hoso-filters">
            <div class="col-sm-3">
                <label for="hoso-date">Ngày cập nhật hồ sơ</label>
                <input type="text" class="form-control" id="hoso-date" lang="vi-VN" autocomplete="off"
                       data-today="<?=htmlspecialchars($request->getAttribute('hosoDate'), ENT_QUOTES, 'UTF-8')?>"
                       value="<?=htmlspecialchars(date('d/m/Y', strtotime($request->getAttribute('hosoDate'))), ENT_QUOTES, 'UTF-8')?>">
            </div>
            <div class="col-sm-6">
                <label for="hoso-search">Tìm kiếm</label>
                <input type="search" class="form-control" id="hoso-search" placeholder="Mã BN, mã BHYT, địa chỉ, tên trường... (mọi ngày trong khoa)" maxlength="200" autocomplete="off">
                <small class="text-muted">Tìm kiếm trên toàn bộ ngày của khoa. Xóa trống ô tìm kiếm để dùng nút Xóa tất cả theo ngày.</small>
            </div>
            <div class="col-sm-3">
                <label>&nbsp;</label>
                <button type="button" id="hoso-refresh" class="btn btn-primary btn-block"><i class="fa fa-refresh"></i> Làm mới danh sách</button>
            </div>
        </div>
        <div id="hoso-message" role="alert" class="alert" style="display:none"></div>
        <div class="hoso-counts">
            Tổng hồ sơ: <strong id="hoso-total">0</strong> &nbsp;|&nbsp;
            Có thể xóa: <strong id="hoso-deletable">0</strong>
        </div>
        <div class="table-responsive hoso-drop-target" id="hoso-table-area">
            <table id="hoso-table" class="table table-striped table-bordered" width="100%">
                <thead><tr>
                    <th class="text-center">STT</th>
                    <th>Mã BN</th>
                    <th>Mã thẻ BHYT</th>
                    <th>Địa chỉ</th>
                    <th>Ngày sinh</th>
                    <th>Giới tính</th>
                    <th>Điều trị từ</th>
                    <th>Đến</th>
                    <th>Số lượng lỗi</th>
                    <th>Tên trường</th>
                    <th class="text-right">Số tiền</th>
                    <th class="text-center">Thao tác</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<input type="hidden" id="hoso-csrf" value="<?=htmlspecialchars($request->getAttribute('hosoCsrf'), ENT_QUOTES, 'UTF-8')?>">

<div class="modal fade" id="hoso-upload-modal" tabindex="-1" role="dialog" aria-labelledby="hoso-upload-title">
    <div class="modal-dialog" role="document"><div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
            <h4 id="hoso-upload-title" class="modal-title">Thêm hồ sơ XML vào khoa của bạn</h4>
        </div>
        <div class="modal-body">
            <div id="hoso-upload-zone" class="hoso-upload-zone" role="button" tabindex="0"
                 aria-label="Kéo thả hoặc chọn file XML">
                <i class="fa fa-cloud-upload hoso-upload-icon" aria-hidden="true"></i>
                <strong>Kéo thả một hoặc nhiều file XML vào đây</strong>
                <span>hoặc bấm để chọn file</span>
                <input type="file" id="hoso-xml-files" accept=".xml,text/xml,application/xml" multiple
                       aria-label="Chọn các file XML để tải lên">
            </div>
            <p class="help-block">Tối đa 5 MB/file, 50 file/lượt. Các file phải thuộc khoa của tài khoản (theo MA_KHOA trong XML1). File được đưa vào hàng đợi và xử lý tự động.</p>
            <div id="hoso-upload-message" class="alert" style="display:none" role="status" aria-live="polite"></div>
            <div id="hoso-upload-progress" class="alert alert-info" style="display:none" role="status" aria-live="polite"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
        </div>
    </div></div>
</div>

<div class="modal fade" id="hoso-view-modal" tabindex="-1" role="dialog" aria-labelledby="hoso-view-title">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="hoso-view-title" class="modal-title">Lỗi và cảnh báo hồ sơ</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;<
                /span></button>
            </div>
            <div class="modal-body">
                <div id="hoso-view-error" class="alert alert-danger" role="alert" style="display:none"></div>
                <div id="hoso-view-content" style="display:none">
                    <section class="hoso-patient-panel" aria-label="Thông tin bệnh nhân">
                        <h4><i class="fa fa-user" aria-hidden="true"></i> Thông tin bệnh nhân</h4>
                        <div class="hoso-patient-grid">
                            <div><span>Họ tên</span><strong id="hoso-patient-name">-</strong></div>
                            <div><span>Mã bệnh nhân</span><strong id="hoso-patient-code">-</strong></div>
                            <div><span>Mã thẻ BHYT</span><strong id="hoso-patient-insurance">-</strong></div>
                            <div><span>Ngày sinh</span><strong id="hoso-patient-birth">-</strong></div>
                            <div><span>Giới tính</span><strong id="hoso-patient-gender">-</strong></div>
                            <div><span>Địa chỉ</span><strong id="hoso-patient-address">-</strong></div>
                            <div><span>Khoa quản lý</span><strong id="hoso-patient-khoa">-</strong></div>
                            <div class="hoso-patient-treatment"><span>Thời gian điều trị</span><strong id="hoso-patient-treatment">-</strong></div>
                        </div>
                    </section>
                    <div class="table-responsive hoso-view-table-scroll">
                        <table class="table table-striped table-bordered table-condensed">
                            <thead><tr><th class="text-center">STT</th><th>Tên trường</th><th>Giá trị</th><th>Nội dung lỗi/cảnh báo</th><th>Hướng xử lý</th></tr></thead>
                            <tbody id="hoso-errors"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
