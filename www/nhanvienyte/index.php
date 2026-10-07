<div class="x_panel">
    <div class="x_title">
        <h2>Danh sách nhân viên y tế</h2>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <div class="row nhanvienyte-search-row">
            <div class="col-md-7 col-sm-9 col-xs-12">
                <div class="input-group">
                    <input type="search" id="nhanvienyte-search" class="form-control"
                           placeholder="Nhập ID, họ tên hoặc mã CCHN..." autocomplete="off">
                    <span class="input-group-btn">
                        <button type="button" id="btn-search-nhanvienyte" class="btn btn-primary">
                            <i class="fa fa-search"></i> Tìm
                        </button>
                        <button type="button" id="btn-reset-nhanvienyte" class="btn btn-default">
                            <i class="fa fa-refresh"></i> Làm mới
                        </button>
                    </span>
                </div>
            </div>
        </div>
        <table id="datatable-nhanvienyte" class="table table-striped table-bordered" width="100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Họ tên</th>
                    <th>Mã CCHN</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>


