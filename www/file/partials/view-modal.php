<div class="modal fade" id="modal-view-xml" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xem hồ sơ bệnh nhân
                    <span id="view-file-name"></span></h5>
                <div class="xml-view-toolbar btn-group" role="group" aria-label="Chế độ xem XML">
                    <button type="button" class="btn btn-sm btn-primary active" data-xml-view="errors"><i class="fa fa-list-alt"></i> Thống kê lỗi</button>
                    <button type="button" class="btn btn-sm btn-default" data-xml-view="source"><i class="fa fa-code"></i> XML</button>
                    <button type="button" class="btn btn-sm btn-default" data-xml-view="tree"><i class="fa fa-sitemap"></i> Dạng cây</button>
                    <button type="button" class="btn btn-sm btn-default" data-xml-view="table"><i class="fa fa-table"></i> Dạng bảng</button>
                    <button type="button" id="btn-toggle-cost-panel" class="btn btn-sm btn-default" title="Hiện/ẩn đối chiếu chi phí"><i class="fa fa-calculator"></i> Chi phí</button>
                    <button type="button" id="btn-view-audit" class="btn btn-sm btn-default" data-xml-view="audit"><i class="fa fa-history"></i> Nhật ký</button>
                </div>
                <div class="cost-reconciliation-panel">
                    <div class="cost-reconciliation-header">
                        <h4>Đối chiếu chi phí</h4>
                        <div class="cost-reconciliation-actions">
                            <button type="button" class="btn-close-cost-panel" title="Đóng" aria-label="Đóng"><i class="fa fa-times"></i></button>
                        </div>
                    </div>
                    <div id="cost-reconciliation-content" class="text-muted">Đang tính...</div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <section id="patient-info" class="patient-info-panel" aria-label="Thông tin bệnh nhân">
                    <h4><i class="fa fa-user"></i> Thông tin bệnh nhân</h4>
                    <div class="patient-info-grid">
                        <div><span>Họ tên</span><strong id="patient-info-name">-</strong></div>
                        <div><span>Mã bệnh nhân</span><strong id="patient-info-code" class="copy-error-value copy-error-trigger" role="button" tabindex="0" title="Bấm để sao chép">-</strong></div>
                        <div><span>Mã BHYT</span><strong id="patient-info-insurance">-</strong></div>
                        <div><span>Ngày sinh</span><strong id="patient-info-birth">-</strong></div>
                        <div><span>Giới tính</span><strong id="patient-info-gender">-</strong></div>
                        <div><span>Địa chỉ</span><strong id="patient-info-address">-</strong></div>
                        <div class="patient-info-treatment"><span>Thời gian điều trị</span><strong id="patient-info-treatment">-</strong></div>
                    </div>
                </section>
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
                        <div id="validation-summary" class="alert"></div>
                        <div id="validation-valid-summary" class="alert alert-success"></div>
                        <div id="decoded-json-tree" class="json-tree"></div>
                        <div id="decoded-xml-source" class="xml-source-view" aria-label="Nội dung XML chỉ đọc"></div>
                        <div id="decoded-table-view" class="decoded-table-view" aria-label="Nội dung XML dạng bảng"></div>
                        <div id="decoded-error-statistics" class="decoded-error-statistics" aria-label="Thống kê lỗi"></div>
                    </div>
                    <aside id="cost-reconciliation" class="cost-reconciliation">
                        <div id="xml-source-navigation" class="xml-source-navigation">
                            <h4>Cây nội dung XML</h4>
                            <input type="text" id="xml-navigation-search" class="form-control"
                                   placeholder="Tìm kiếm" aria-label="Tìm nhóm trong XML">
                            <div id="xml-navigation-items" class="xml-navigation-items"></div>
                        </div>
                    </aside>
                </div>
            </div>
            <div class="modal-body audit-panel" id="audit-panel" style="display:none">
                <div class="audit-panel-heading"><i class="fa fa-history"></i><div><h4>Lịch sử xử lý hồ sơ</h4><p>Theo dõi các lần cập nhật và lỗi của hồ sơ này.</p></div></div>
                <div class="audit-table-wrap"><table class="table audit-table"><thead><tr><th>Thời gian</th><th>Thao tác</th><th>Người dùng</th><th>Khoa</th><th>Tên file</th><th>Đường dẫn file</th><th>Chi tiết lỗi</th></tr></thead><tbody id="audit-list"></tbody></table></div>
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
