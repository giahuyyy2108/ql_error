<div class="x_panel">
    <div class="x_title">
        <h2>Danh mục mã khoa <small>Quyết định 1804/QĐ-BYT</small></h2>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <div class="alert alert-info">
            Tra cứu theo mã, tên khoa, mã khoa gốc hoặc ghi chú. Danh mục chỉ đọc để bảo toàn dữ liệu chuẩn.
            Nguồn:
            <a href="https://thuvienphapluat.vn/van-ban/Cong-nghe-thong-tin/Quyet-dinh-1804-QD-BYT-2026-Danh-muc-ma-loai-hinh-kham-benh-chua-benh-711300.aspx"
               target="_blank" rel="noopener noreferrer">Quyết định 1804/QĐ-BYT</a>.
        </div>
        <div class="row" style="margin-bottom:12px">
            <div class="col-md-7 col-sm-8 col-xs-12">
                <div class="input-group">
                    <input type="text" id="khoa-search" class="form-control"
                           placeholder="Nhập mã khoa, tên khoa hoặc ghi chú...">
                    <span class="input-group-btn">
                        <button type="button" id="btn-search-khoa" class="btn btn-primary">
                            <i class="fa fa-search"></i> Tìm
                        </button>
                        <button type="button" id="btn-reset-khoa" class="btn btn-default">
                            <i class="fa fa-refresh"></i> Làm mới
                        </button>
                    </span>
                </div>
            </div>
        </div>
        <table id="datatable-khoa" class="table table-striped table-bordered" width="100%">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã khoa</th>
                    <th>Tên khoa</th>
                    <th>Khoa gốc</th>
                    <th>Ghi chú</th>
                    <th>Hiệu lực</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<style>
    #datatable-khoa { width:100%!important; table-layout:fixed; font-size:12px; }
    #datatable-khoa th, #datatable-khoa td { padding:6px 5px; vertical-align:middle; }
    #datatable-khoa th { text-align:center; font-size:11px; }
    #datatable-khoa .khoa-text {
        display:block;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }
    #datatable-khoa .khoa-status { font-size:17px; cursor:help; }
    #datatable-khoa_wrapper .dataTables_scrollBody { border-bottom:1px solid #ddd; }
    #datatable-khoa_wrapper .dataTables_info { padding-top:10px; }
</style>
