(function ($) {
    'use strict';
    function compact(value, type) {
        var text = value == null ? '' : String(value);
        if (type !== 'display') return text;
        return $('<span>').addClass('catalog-text').attr('title', text).text(text).prop('outerHTML');
    }
    var table = $('#datatable-tinhthanh').DataTable({
        processing:true,serverSide:true,searching:false,ordering:false,responsive:false,autoWidth:false,
        ajax:{url:$('#ULocal').val()+'tinhthanh/getData/',type:'POST',data:function(d){d.searchText=$('#tinhthanh-search').val().trim();}},
        pageLength:25,lengthMenu:[[10,25,50,100,200],[10,25,50,100,200]],
        columns:[
            {data:'ma_cu',width:'15%',className:'text-center'},
            {data:'ten_tinhthanh',width:'65%',render:compact},
            {data:'ma_sau_sapnhap',width:'20%',className:'text-center'}
        ],
        language:{processing:'Đang tải dữ liệu...',lengthMenu:'Hiển thị _MENU_ dòng',zeroRecords:'Không tìm thấy tỉnh thành',info:'Hiển thị _START_ đến _END_ trong _TOTAL_ mã',infoEmpty:'Không có dữ liệu',infoFiltered:'(lọc từ _MAX_ mã)',paginate:{first:'Đầu',last:'Cuối',next:'Sau',previous:'Trước'}}
    });
    $('#btn-search-tinhthanh').on('click',function(){table.ajax.reload();});
    $('#btn-reset-tinhthanh').on('click',function(){$('#tinhthanh-search').val('');table.ajax.reload();$('#tinhthanh-search').focus();});
    $('#tinhthanh-search').on('keydown',function(e){if(e.key==='Enter'||e.which===13){e.preventDefault();table.ajax.reload();}});
})(jQuery);
