<div class="x_panel">
    <div class="x_title">
        <h2>Danh mục các dân tộc Việt Nam</h2>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <div class="alert alert-info">
            Tra cứu theo mã, tên dân tộc hoặc tên gọi khác. Danh mục chỉ đọc để bảo toàn dữ liệu chuẩn.
            Nguồn:
            <a href="https://www.nso.gov.vn/phuong-phap-luan-thong-ke/danh-muc/cac-dan-toc-viet-nam/"
               target="_blank" rel="noopener noreferrer">Cục Thống kê</a>.
        </div>
        <div class="row" style="margin-bottom:12px">
            <div class="col-md-7 col-sm-8 col-xs-12">
                <div class="input-group">
                    <input type="text" id="dantoc-search" class="form-control"
                           placeholder="Nhập mã, tên dân tộc hoặc tên gọi khác...">
                    <span class="input-group-btn">
                        <button type="button" id="btn-search-dantoc" class="btn btn-primary">
                            <i class="fa fa-search"></i> Tìm
                        </button>
                        <button type="button" id="btn-reset-dantoc" class="btn btn-default">
                            <i class="fa fa-refresh"></i> Làm mới
                        </button>
                    </span>
                </div>
            </div>
        </div>
        <table id="datatable-dantoc" class="table table-striped table-bordered" width="100%">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Tên dân tộc</th>
                    <th>Tên gọi khác</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<style>
    #datatable-dantoc { width:100%!important; table-layout:fixed; font-size:12px; }
    #datatable-dantoc th, #datatable-dantoc td { padding:6px 5px; vertical-align:middle; }
    #datatable-dantoc th { text-align:center; font-size:11px; }
    #datatable-dantoc .dantoc-text {
        display:block;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }
    #datatable-dantoc .dantoc-status { font-size:17px; cursor:help; }
</style>
