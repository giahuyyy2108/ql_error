<?php $provinces = $request->getAttribute('provinces'); ?>
<div class="x_panel">
    <div class="x_title"><h2>Danh mục tân dược <small>Kết quả trúng thầu BHXH</small></h2><div class="clearfix"></div></div>
    <div class="x_content">
        <div class="alert alert-info">Tra cứu theo mã hoạt chất, hoạt chất, tên thuốc, số đăng ký hoặc nhà sản xuất.</div>
        <div class="row" style="margin-bottom:12px">
            <div class="col-md-6 col-sm-8 col-xs-12">
                <div class="input-group">
                    <input type="text" id="tanduoc-search" class="form-control" placeholder="Nhập mã, hoạt chất, tên thuốc, SĐK...">
                    <span class="input-group-btn"><button type="button" id="btn-search-tanduoc" class="btn btn-primary"><i class="fa fa-search"></i> Tìm</button></span>
                </div>
            </div>
            <div class="col-md-3 col-sm-4 col-xs-12">
                <select id="tanduoc-province" class="form-control">
                    <option value="">Tất cả tỉnh/thành</option>
                    <?php foreach ((array) $provinces as $province): ?>
                        <option value="<?=htmlspecialchars($province, ENT_QUOTES, 'UTF-8')?>"><?=htmlspecialchars($province, ENT_QUOTES, 'UTF-8')?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-4 col-xs-12"><button type="button" id="btn-reset-tanduoc" class="btn btn-default"><i class="fa fa-refresh"></i> Làm mới</button></div>
        </div>
        <div class="tanduoc-table-wrap">
            <table id="datatable-tanduoc" class="table table-striped table-bordered" width="100%">
                <thead><tr>
                    <th>STT</th><th>Mã hoạt chất</th><th>Tên hoạt chất</th><th>Đường dùng, dạng bào chế</th>
                    <th>Nồng độ, hàm lượng</th><th>Tên thuốc</th><th>SĐK chuẩn hóa</th>
                    <th>Quy cách đóng gói</th><th>ĐVT</th><th>Nhóm</th><th>Gói</th>
                </tr></thead><tbody></tbody>
            </table>
        </div>
    </div>
</div>

