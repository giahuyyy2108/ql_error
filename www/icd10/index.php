<div class="x_panel">
    <div class="x_title"><h2>Danh mục ICD-10 tiếng Việt <small>Phiên bản 2026-07-01</small></h2><div class="clearfix"></div></div>
    <div class="x_content">
        <div class="alert alert-info">Tra cứu theo mã, tên bệnh tiếng Việt hoặc tiếng Anh. Danh mục chỉ đọc để bảo toàn dữ liệu chuẩn.</div>
        <div class="row" style="margin-bottom:12px">
            <div class="col-md-7 col-sm-8 col-xs-12">
                <div class="input-group">
                    <input type="text" id="icd10-search" class="form-control" placeholder="Nhập mã hoặc tên bệnh...">
                    <span class="input-group-btn">
                        <button type="button" id="btn-search-icd10" class="btn btn-primary"><i class="fa fa-search"></i> Tìm</button>
                        <button type="button" id="btn-reset-icd10" class="btn btn-default"><i class="fa fa-refresh"></i> Làm mới</button>
                    </span>
                </div>
            </div>
        </div>
        <table id="datatable-icd10" class="table table-striped table-bordered" width="100%">
            <thead><tr>
                <th>Mã</th><th>Tên bệnh tiếng Việt</th><th>Tên tiếng Anh</th><th>Chương</th>
                <th>Nhóm</th><th>Cấp</th><th>Mã lá</th><th>Trạng thái</th>
            </tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<style>
    #datatable-icd10 { width:100%!important; table-layout:fixed; font-size:12px; }
    #datatable-icd10 th,#datatable-icd10 td { padding:5px 4px; vertical-align:middle; }
    #datatable-icd10 th { text-align:center; font-size:11px; }
    #datatable-icd10 .icd10-text { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    #datatable-icd10 .icd10-status { font-size:17px; cursor:help; }
    #datatable-icd10_wrapper .dataTables_scrollBody { border-bottom:1px solid #ddd; }
    #datatable-icd10_wrapper .dataTables_info { padding-top:10px; }
</style>
