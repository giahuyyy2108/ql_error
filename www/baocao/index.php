<div class="x_panel report-page">
    <div class="x_title">
        <h2><i class="fa fa-line-chart"></i> Báo cáo và thống kê</h2>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <div id="report-loading-overlay" class="report-loading-overlay" aria-hidden="true">
            <div class="report-loading-box" role="status" aria-live="polite">
                <i class="fa fa-spinner fa-spin" aria-hidden="true"></i>
                <strong id="report-loading-text">Đang xử lý báo cáo...</strong>
                <span>Vui lòng chờ và không đóng trang.</span>
            </div>
        </div>
        <section class="report-card">
            <div class="row report-filter-row">
                <div class="col-sm-3 col-md-2">
                    <label for="report-type">Loại báo cáo</label>
                    <select id="report-type" class="form-control">
                        <option value="day">Theo ngày</option>
                        <option value="month">Theo tháng</option>
                        <option value="range">Từ ngày - đến ngày</option>
                    </select>
                </div>
                <div id="report-single-time-wrap" class="col-sm-3 col-md-2">
                    <label for="report-date">Thời gian</label>
                    <input id="report-date" type="text" class="form-control" autocomplete="off">
                    <input id="report-month" type="text" class="form-control" style="display:none" autocomplete="off">
                </div>
                <div id="report-range-wrap" class="col-sm-6 col-md-4" style="display:none">
                    <div class="row">
                        <div class="col-xs-6">
                            <label for="report-from-date">Từ ngày</label>
                            <input id="report-from-date" type="text" class="form-control" autocomplete="off">
                        </div>
                        <div class="col-xs-6">
                            <label for="report-to-date">Đến ngày</label>
                            <input id="report-to-date" type="text" class="form-control" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="col-sm-3 col-md-2">
                    <label for="report-scope">Phạm vi</label>
                    <select id="report-scope" class="form-control">
                        <option value="hospital">Toàn viện</option>
                        <option value="khoa">Theo khoa</option>
                    </select>
                </div>
                <div id="report-khoa-wrap" class="col-sm-3 col-md-3 report-khoa-slot">
                    <label for="report-khoa">Khoa</label>
                    <select id="report-khoa" class="form-control">
                        <option value="0">-- Chọn khoa --</option>
                        <?php foreach (($request->getAttribute('reportKhoaList') ?: array()) as $khoa) { ?>
                            <option value="<?=htmlspecialchars((string) $khoa['id'], ENT_QUOTES, 'UTF-8')?>"><?=htmlspecialchars((string) $khoa['ten'], ENT_QUOTES, 'UTF-8')?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-sm-3 col-md-3 report-actions">
                    <label for="report"> </label>
                    <button id="btn-export-report" type="button" class="btn btn-success btn-block">
                        <i class="fa fa-download"></i> Xuất Excel
                    </button>
                    <button id="btn-show-chart" type="button" class="btn btn-primary btn-block">
                        <i class="fa fa-refresh"></i> Xem biểu đồ
                    </button>
                </div>
            </div>
        </section>

        <section class="file-statistics-section" aria-label="Thống kê trạng thái hồ sơ">
            <div class="file-statistics-header">
                <h3>Thống kê theo báo cáo</h3>
            </div>
            <div class="file-statistics report-file-statistics">
                <div class="file-stat-card file-stat-total">
                    <i class="fa fa-files-o" aria-hidden="true"></i>
                    <div><strong id="report-stat-total">0</strong><span>Tổng file</span></div>
                </div>
                <div class="file-stat-card file-stat-success">
                    <i class="fa fa-check-circle" aria-hidden="true"></i>
                    <div><strong id="report-stat-fixed">0</strong><span>Đã sửa</span></div>
                </div>
                <div class="file-stat-card file-stat-failed">
                    <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                    <div><strong id="report-stat-unfixed">0</strong><span>Chưa sửa</span></div>
                </div>
            </div>
        </section>

        <section class="report-card report-chart-card">
            <div class="chart-container">
                <canvas id="report-error-chart"></canvas>
                <div id="report-chart-empty" class="report-chart-empty">Chọn phạm vi rồi bấm “Xem biểu đồ”.</div>
            </div>
            <div class="report-chart-legend"><span class="legend-error"></span> Lỗi <span class="legend-warning"></span> Cảnh báo</div>
            <div id="report-file-list" class="report-file-list"></div>
        </section>

        <div id="report-message" class="alert alert-info report-message">Chọn thời gian và chức năng cần thực hiện.</div>
    </div>
</div>
