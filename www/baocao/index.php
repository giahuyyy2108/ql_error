<div class="x_panel">
    <div class="x_title"><h2>Báo cáo</h2><div class="clearfix"></div></div>
    <div class="x_content">
        <div class="row" style="margin-bottom:20px">
            <div class="col-md-3">
                <label for="report-type">Loại báo cáo</label>
                <select id="report-type" class="form-control">
                    <option value="day">Theo ngày</option>
                    <option value="month">Theo tháng</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="report-date">Thời gian</label>
                <input id="report-date" type="text" class="form-control" autocomplete="off">
                <input id="report-month" type="text" class="form-control" style="display:none" autocomplete="off">
            </div>
            <div class="col-md-3" style="padding-top:25px">
                <button id="btn-export-report" class="btn btn-success"><i class="fa fa-file-excel-o"></i> Xuất báo cáo</button>
            </div>
        </div>
        <div id="report-message" class="alert alert-info">Chọn thời gian rồi bấm Xuất báo cáo.</div>
    </div>
</div>
