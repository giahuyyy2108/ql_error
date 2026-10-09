<div class="x_panel">
    <div class="x_title">
        <h2>Danh mục ICD-10 Y học cổ truyền <small>Quyết định 2552/QĐ-BYT</small></h2>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <div class="alert alert-info">
            Phụ lục I – Danh mục thể lâm sàng và mã thể lâm sàng theo bệnh danh Y học cổ truyền.
            Danh mục chỉ đọc để bảo toàn dữ liệu chuẩn.
        </div>
        <div class="row icd10-yhct-search-row">
            <div class="col-md-8 col-sm-10 col-xs-12">
                <div class="input-group">
                    <input type="search" id="icd10-yhct-search" class="form-control"
                           placeholder="Tìm mã dùng chung, ICD-10, mã U, mã hóa hoặc tên bệnh...">
                    <span class="input-group-btn">
                        <button type="button" id="btn-search-icd10-yhct" class="btn btn-primary">
                            <i class="fa fa-search"></i> Tìm
                        </button>
                        <button type="button" id="btn-reset-icd10-yhct" class="btn btn-default">
                            <i class="fa fa-refresh"></i> Làm mới
                        </button>
                    </span>
                </div>
            </div>
        </div>
        <div class="icd10-yhct-table-wrap">
            <table id="datatable-icd10-yhct" class="table table-striped table-bordered" width="100%">
                <thead><tr>
                    <th>Mã dùng chung</th>
                    <th>Mã ICD-10</th>
                    <th>Bệnh danh YHCT</th>
                    <th>Mã U</th>
                    <th>Thể lâm sàng</th>
                    <th>Mã hóa</th>
                    <th>Tên bệnh YHHĐ</th>
                    <th>Trạng thái</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .icd10-yhct-search-row { margin-bottom: 12px; }
    .icd10-yhct-table-wrap { width: 100%; overflow-x: auto; }
    #datatable-icd10-yhct { width: 100% !important; font-size: 12px; }
    #datatable-icd10-yhct th, #datatable-icd10-yhct td { padding: 6px 5px; vertical-align: middle; }
    #datatable-icd10-yhct th { text-align: center; font-size: 11px; white-space: nowrap; }
    #datatable-icd10-yhct .catalog-text {
        display: block; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    #datatable-icd10-yhct .clinical-row { background: #f8fbff; }
    #datatable-icd10-yhct .code-cell { white-space: nowrap; font-family: Consolas, monospace; }
    #datatable-icd10-yhct .catalog-status { font-size: 16px; }
</style>
