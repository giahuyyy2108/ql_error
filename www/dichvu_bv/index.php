<?php $hieuLucList = $request->getAttribute('hieuLucList'); ?>
<div class="x_panel">
    <div class="x_title">
        <h2>Dịch vụ bệnh viện <small>Danh mục từ bảng dichvu_bv</small></h2>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <div class="alert alert-info">
            Tra cứu theo mã tương đương, tên dịch vụ kỹ thuật, mã dịch vụ, quyết định hoặc mã nhóm 5937.
        </div>

        <div class="row" style="margin-bottom:12px">
            <div class="col-md-6 col-sm-8 col-xs-12">
                <div class="input-group">
                    <input type="search" id="dichvu-bv-search" class="form-control"
                           placeholder="Nhập mã, tên dịch vụ, quyết định..." autocomplete="off">
                    <span class="input-group-btn">
                        <button type="button" id="btn-search-dichvu-bv" class="btn btn-primary">
                            <i class="fa fa-search"></i> Tìm
                        </button>
                    </span>
                </div>
            </div>
            <div class="col-md-3 col-sm-4 col-xs-12">
                <select id="dichvu-bv-hieu-luc" class="form-control">
                    <option value="">Tất cả hiệu lực</option>
                    <?php foreach ((array) $hieuLucList as $hieuLuc): ?>
                        <option value="<?=htmlspecialchars($hieuLuc, ENT_QUOTES, 'UTF-8')?>">
                            <?=htmlspecialchars($hieuLuc, ENT_QUOTES, 'UTF-8')?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-4 col-xs-12">
                <button type="button" id="btn-reset-dichvu-bv" class="btn btn-default">
                    <i class="fa fa-refresh"></i> Làm mới
                </button>
            </div>
        </div>

        <div class="dichvu-bv-table-wrap">
            <table id="datatable-dichvu-bv" class="table table-striped table-bordered" width="100%">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã tương đương</th>
                        <th>Tên DVKT phê duyệt</th>
                        <th>Tên DVKT giá</th>
                        <th>Phân loại PTTT</th>
                        <th>Đơn giá</th>
                        <th>Quyết định</th>
                        <th>Từ ngày</th>
                        <th>Đến ngày</th>
                        <th>Mã</th>
                        <th>Tên</th>
                        <th>Giá</th>
                        <th>Giá DVKT</th>
                        <th>Số quyết định</th>
                        <th>Mã nhóm 5937</th>
                        <th>Hiệu lực</th>
                        <th>Trạng thái gửi</th>
                        <th>Kết quả giám định</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <div class="dichvu-bv-page-nav">
            <button type="button" id="btn-dichvu-bv-prev" class="btn btn-default">
                <i class="fa fa-chevron-left"></i> Trang trước
            </button>
            <span id="dichvu-bv-page-info" class="dichvu-bv-page-info">Trang 1 / 1</span>
            <button type="button" id="btn-dichvu-bv-next" class="btn btn-primary">
                Trang tiếp <i class="fa fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>

<style>
    .dichvu-bv-page-nav { display:flex; justify-content:center; align-items:center; gap:12px; margin-top:14px; }
    .dichvu-bv-page-info { min-width:110px; text-align:center; font-weight:600; }
    .dichvu-bv-page-nav .btn[disabled] { opacity:.45; }
    #datatable-dichvu-bv .td-text {
        display:block;
        max-width:280px;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }
</style>
