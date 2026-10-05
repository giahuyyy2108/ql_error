(function ($) {
    'use strict';
    function compact(value, type) {
        var text = value == null ? '' : String(value);
        if (type !== 'display') return text;
        return $('<span>').addClass('catalog-text').attr('title', text).text(text).prop('outerHTML');
    }
    var table = $('#datatable-doituong-kcb').DataTable({
        processing:true,serverSide:true,searching:false,ordering:false,responsive:false,autoWidth:false,
        ajax:{url:$('#ULocal').val()+'doituong_kcb/getData/',type:'POST',data:function(d){d.searchText=$('#doituong-kcb-search').val().trim();}},
        pageLength:25,lengthMenu:[[10,25,50,100,200],[10,25,50,100,200]],
        columns:[
            {data:'ma',width:'7%',className:'text-center'},
            {data:'truong_hop',width:'27%',render:compact},
            {data:'quy_dinh',width:'20%',defaultContent:'',render:compact},
            {data:'muc_huong',width:'20%',defaultContent:'',render:compact},
            {data:'ghi_chu',width:'8%',defaultContent:'',render:compact},
            {data:'ngay_hieu_luc',width:'9%',className:'text-center'},
            {data:'is_active',width:'9%',className:'text-center',render:function(v){return Number(v)===1?'<i class="fa fa-check-circle text-success catalog-status" title="Hiện hành"></i>':'<i class="fa fa-ban text-danger catalog-status" title="Ngừng dùng"></i>';}}
        ],
        language:{processing:'Đang tải dữ liệu...',lengthMenu:'Hiển thị _MENU_ dòng',zeroRecords:'Không tìm thấy mã loại hình KCB',info:'Hiển thị _START_ đến _END_ trong _TOTAL_ mã',infoEmpty:'Không có dữ liệu',infoFiltered:'(lọc từ _MAX_ mã)',paginate:{first:'Đầu',last:'Cuối',next:'Sau',previous:'Trước'}}
    });
    $('#btn-search-doituong-kcb').on('click',function(){table.ajax.reload();});
    $('#btn-reset-doituong-kcb').on('click',function(){$('#doituong-kcb-search').val('');table.ajax.reload();$('#doituong-kcb-search').focus();});
    $('#doituong-kcb-search').on('keydown',function(e){if(e.key==='Enter'||e.which===13){e.preventDefault();table.ajax.reload();}});
})(jQuery);
