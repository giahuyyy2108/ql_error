(function ($) {
    'use strict';
    function textCell(value, type) {
        var text = value == null ? '' : String(value);
        if (type !== 'display') return text;
        return $('<span>').addClass('td-text').attr('title', text).text(text).prop('outerHTML');
    }
    var table = $('#datatable-tanduoc').DataTable({
        processing: true, serverSide: true, scrollX: true, scrollCollapse: false,
        ajax: {url: $('#ULocal').val() + 'tanduoc/getData/', type: 'POST', data: function (data) {
            data.searchText = $('#tanduoc-search').val().trim(); data.province = $('#tanduoc-province').val();
        }},
        pageLength: 25, lengthMenu: [[10,25,50,100,200],[10,25,50,100,200]], searching: false, ordering: false, autoWidth: false,
        columnDefs: [
            {targets: 0, width: '48px'},
            {targets: 1, width: '85px'},
            {targets: [2,3,5,7], width: '180px'},
            {targets: 4, width: '115px'},
            {targets: 6, width: '120px'},
            {targets: 8, width: '65px'},
            {targets: [9,10], width: '55px'}
        ],
        columns: [
            {data:'stt_nguon',className:'text-center'}, {data:'ma_hoat_chat'}, {data:'ten_hoat_chat',render:textCell},
            {data:'duong_dung_dang_bao_che',render:textCell}, {data:'nong_do_ham_luong',render:textCell}, {data:'ten_thuoc',render:textCell},
            {data:'sdk_chuan_hoa',render:textCell},
            {data:'quy_cach_dong_goi',render:textCell}, {data:'don_vi_tinh'},
            {data:'nhom_tieu_chi',className:'text-center'}, {data:'goi_thau',className:'text-center'}
        ],
        initComplete: function () {
            this.api().columns.adjust();
        },
        language: {processing:'Đang tải dữ liệu...',lengthMenu:'Hiển thị _MENU_ dòng',zeroRecords:'Không tìm thấy thuốc',info:'Hiển thị _START_ đến _END_ trong _TOTAL_ dòng',infoEmpty:'Không có dữ liệu',infoFiltered:'(lọc từ _MAX_ dòng)',paginate:{first:'Đầu',last:'Cuối',next:'Sau',previous:'Trước'}}
    });
    table.on('draw.dt', function () { table.columns.adjust(); });
    $(window).on('load resize', function () { table.columns.adjust(); });
    function reload(){ table.ajax.reload(); }
    $('#btn-search-tanduoc').on('click', reload);
    $('#tanduoc-province').on('change', reload);
    $('#btn-reset-tanduoc').on('click', function(){ $('#tanduoc-search,#tanduoc-province').val(''); reload(); });
    $('#tanduoc-search').on('keydown', function(event){ if(event.key==='Enter'||event.which===13){event.preventDefault();reload();} });
})(jQuery);
