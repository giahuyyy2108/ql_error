<?php $nuocSanXuatList = $request->getAttribute('nuocSanXuatList'); ?>
<div class="x_panel">
    <div class="x_title">
        <h2>Trang thiết bị y tế <small>Danh mục từ bảng trang_thiet_bi</small></h2>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <div class="alert alert-info">
            Tra cứu theo tên thiết bị, ký hiệu, công ty sản xuất, mã máy hoặc số lưu hành.
        </div>

        <div class="row" style="margin-bottom:12px">
            <div class="col-md-6 col-sm-8 col-xs-12">
                <div class="input-group">
                    <input type="search" id="trang-thiet-bi-search" class="form-control"
                           placeholder="Nhập tên thiết bị, ký hiệu, mã máy..." autocomplete="off">
                    <span class="input-group-btn">
                        <button type="button" id="btn-search-trang-thiet-bi" class="btn btn-primary">
                            <i class="fa fa-search"></i> Tìm
                        </button>
                    </span>
                </div>
            </div>
            <div class="col-md-3 col-sm-4 col-xs-12">
                <select id="trang-thiet-bi-nuoc-sx" class="form-control">
                    <option value="">Tất cả nước sản xuất</option>
                    <?php foreach ((array) $nuocSanXuatList as $nuocSx): ?>
                        <option value="<?=htmlspecialchars($nuocSx, ENT_QUOTES, 'UTF-8')?>">
                            <?=htmlspecialchars($nuocSx, ENT_QUOTES, 'UTF-8')?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-4 col-xs-12">
                <button type="button" id="btn-reset-trang-thiet-bi" class="btn btn-default">
                    <i class="fa fa-refresh"></i> Làm mới
                </button>
            </div>
        </div>

        <div class="trang-thiet-bi-table-wrap">
            <table id="datatable-trang-thiet-bi" class="table table-striped table-bordered" width="100%">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên thiết bị</th>
                        <th>Ký hiệu</th>
                        <th>Công ty SX</th>
                        <th>Nước SX</th>
                        <th>Năm SX</th>
                        <th>Năm sử dụng</th>
                        <th>Mã máy</th>
                        <th>Số lưu hành</th>
                        <th>HĐ từ</th>
                        <th>HĐ đến</th>
                        <th>Từ ngày</th>
                        <th>Đến ngày</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <div class="trang-thiet-bi-page-nav">
            <button type="button" id="btn-trang-thiet-bi-prev" class="btn btn-default">
                <i class="fa fa-chevron-left"></i> Trang trước
            </button>
            <span id="trang-thiet-bi-page-info" class="trang-thiet-bi-page-info">Trang 1 / 1</span>
            <button type="button" id="btn-trang-thiet-bi-next" class="btn btn-primary">
                Trang tiếp <i class="fa fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>

<style>
    .trang-thiet-bi-page-nav { display:flex; justify-content:center; align-items:center; gap:12px; margin-top:14px; }
    .trang-thiet-bi-page-info { min-width:110px; text-align:center; font-weight:600; }
    .trang-thiet-bi-page-nav .btn[disabled] { opacity:.45; }
    #datatable-trang-thiet-bi .td-text {
        display:block;
        max-width:280px;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }
</style>
