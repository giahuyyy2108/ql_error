<div class="x_panel">
    <div class="x_title"><h2>Danh mục tỉnh thành</h2><div class="clearfix"></div></div>
    <div class="x_content">
        <div class="alert alert-info">Tra cứu mã tỉnh cũ và mã tỉnh sau sáp nhập. Danh mục chỉ đọc để bảo toàn dữ liệu chuẩn.</div>
        <div class="row" style="margin-bottom:12px"><div class="col-md-7 col-sm-8 col-xs-12"><div class="input-group">
            <input type="text" id="tinhthanh-search" class="form-control" placeholder="Nhập mã hoặc tên tỉnh thành...">
            <span class="input-group-btn">
                <button type="button" id="btn-search-tinhthanh" class="btn btn-primary"><i class="fa fa-search"></i> Tìm</button>
                <button type="button" id="btn-reset-tinhthanh" class="btn btn-default"><i class="fa fa-refresh"></i> Làm mới</button>
            </span>
        </div></div></div>
        <table id="datatable-tinhthanh" class="table table-striped table-bordered" width="100%">
            <thead><tr><th>Mã cũ</th><th>Tên tỉnh thành</th><th>Mã sau sáp nhập</th></tr></thead><tbody></tbody>
        </table>
    </div>
</div>
<style>
    #datatable-tinhthanh{width:100%!important;table-layout:fixed;font-size:12px}
    #datatable-tinhthanh th,#datatable-tinhthanh td{padding:6px 5px;vertical-align:middle}
    #datatable-tinhthanh th{text-align:center;font-size:11px}
    #datatable-tinhthanh .catalog-text{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
</style>
