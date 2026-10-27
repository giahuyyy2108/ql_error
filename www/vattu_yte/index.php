<?php $hieuLucList = $request->getAttribute('hieuLucList'); ?>
<div class="x_panel">
    <div class="x_title">
        <h2>Vật tư y tế <small>Danh mục vật tư từ bảng vattuyte</small></h2>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <div class="alert alert-info">
            Tra cứu theo mã vật tư, tên vật tư, mã hiệu, quy cách, hãng sản xuất, nhà thầu, quyết định hoặc mã vật tư bệnh viện.
        </div>

        <div class="row" style="margin-bottom:12px">
            <div class="col-md-6 col-sm-8 col-xs-12">
                <div class="input-group">
                    <input type="search" id="vattu-yte-search" class="form-control"
                           placeholder="Nhập mã, tên vật tư, mã hiệu, hãng sản xuất..." autocomplete="off">
                    <span class="input-group-btn">
                        <button type="button" id="btn-search-vattu-yte" class="btn btn-primary">
                            <i class="fa fa-search"></i> Tìm
                        </button>
                    </span>
                </div>
            </div>
            <div class="col-md-3 col-sm-4 col-xs-12">
                <select id="vattu-yte-hieu-luc" class="form-control">
                    <option value="">Tất cả hiệu lực</option>
                    <?php foreach ((array) $hieuLucList as $hieuLuc): ?>
                        <option value="<?=htmlspecialchars($hieuLuc, ENT_QUOTES, 'UTF-8')?>">
                            <?=htmlspecialchars($hieuLuc, ENT_QUOTES, 'UTF-8')?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-4 col-xs-12">
                <button type="button" id="btn-reset-vattu-yte" class="btn btn-default">
                    <i class="fa fa-refresh"></i> Làm mới
                </button>
            </div>
        </div>

        <div class="vattu-yte-table-wrap">
            <table id="datatable-vattu-yte" class="table table-striped table-bordered" width="100%">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã vật tư</th>
                        <th>Nhóm vật tư</th>
                        <th>Tên vật tư</th>
                        <th>Mã hiệu</th>
                        <th>Quy cách</th>
                        <th>Hãng SX</th>
                        <th>Nước SX</th>
                        <th>ĐVT</th>
                        <th>Đơn giá</th>
                        <th>Đơn giá BH</th>
                        <th>Tỷ lệ TT BH</th>
                        <th>Nhà thầu</th>
                        <th>Mã CSKCB</th>
                        <th>Quyết định</th>
                        <th>Hiệu lực</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <div class="vattu-yte-page-nav">
            <button type="button" id="btn-vattu-yte-prev" class="btn btn-default">
                <i class="fa fa-chevron-left"></i> Trang trước
            </button>
            <span id="vattu-yte-page-info" class="vattu-yte-page-info">Trang 1 / 1</span>
            <button type="button" id="btn-vattu-yte-next" class="btn btn-primary">
                Trang tiếp <i class="fa fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>

<style>
    .vattu-yte-page-nav { display:flex; justify-content:center; align-items:center; gap:12px; margin-top:14px; }
    .vattu-yte-page-info { min-width:110px; text-align:center; font-weight:600; }
    .vattu-yte-page-nav .btn[disabled] { opacity:.45; }
    #datatable-vattu-yte .td-text {
        display:block;
        max-width:260px;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }
</style>
