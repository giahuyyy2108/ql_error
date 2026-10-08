<div class="x_panel">
    <div class="x_title">
        <h2>Danh mục loại khám chữa bệnh</h2>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <div class="alert alert-info">
            Danh mục có hiệu lực từ ngày 01/08/2026, dùng để đối chiếu mã loại khám chữa bệnh.
        </div>
        <div class="row loai-kcb-search-row">
            <div class="col-md-8 col-sm-9 col-xs-12">
                <div class="input-group">
                    <input type="search" id="loai-kcb-search" class="form-control"
                           placeholder="Nhập mã, trường hợp, quy định hoặc ghi chú..." autocomplete="off">
                    <span class="input-group-btn">
                        <button type="button" id="btn-search-loai-kcb" class="btn btn-primary">
                            <i class="fa fa-search"></i> Tìm
                        </button>
                        <button type="button" id="btn-reset-loai-kcb" class="btn btn-default">
                            <i class="fa fa-refresh"></i> Làm mới
                        </button>
                    </span>
                </div>
            </div>
        </div>
        <table id="datatable-loai-kcb" class="table table-striped table-bordered" width="100%">
            <thead><tr>
                <th>Mã</th>
                <th>Trường hợp</th>
                <th>Quy định</th>
                <th>Mức hưởng</th>
                <th>Ghi chú</th>
                <th>Hiệu lực</th>
                <th>Trạng thái</th>
            </tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<style>
    .loai-kcb-search-row { margin-bottom:14px; }
    #datatable-loai-kcb { width:100%!important; table-layout:fixed; font-size:12px; }
    #datatable-loai-kcb th,#datatable-loai-kcb td { padding:6px 5px; vertical-align:middle; }
    #datatable-loai-kcb th { text-align:center; font-size:11px; }
    #datatable-loai-kcb .loai-kcb-text { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    #datatable-loai-kcb .loai-kcb-status { font-size:17px; cursor:help; }
    #datatable-loai-kcb_wrapper .dataTables_filter { display:none; }
</style>
