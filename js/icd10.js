(function ($) {
    'use strict';
    function compact(value,type) {
        var text=value==null?'':String(value); if(type!=='display')return text;
        return $('<span>').addClass('icd10-text').attr('title',text).text(text).prop('outerHTML');
    }
    var table=$('#datatable-icd10').DataTable({
        processing:true, serverSide:true,
        ajax:{
            url:$('#ULocal').val()+'icd10/getData/',
            type:'POST',
            data:function(data){data.searchText=$('#icd10-search').val().trim();}
        },
        pageLength:25, lengthMenu:[[10,25,50,100,200],[10,25,50,100,200]],
        searching:false, ordering:false, responsive:false, autoWidth:false,
        columns:[
            {data:'code',width:'8%',className:'text-center'},
            {data:'display_vi',width:'29%',render:compact},
            {data:'display_en',width:'25%',defaultContent:'',render:compact},
            {data:'chapter_code',width:'7%',defaultContent:'',className:'text-center'},
            {data:'section_id',width:'10%',defaultContent:'',className:'text-center'},
            {data:'level',width:'8%',defaultContent:'',className:'text-center'},
            {data:'is_leaf',width:'6%',className:'text-center',render:function(v){return Number(v)===1?'<i class="fa fa-check text-success" title="Mã lá"></i>':'';}},
            {data:'is_active',width:'7%',className:'text-center',render:function(v){return Number(v)===1?'<i class="fa fa-check-circle text-success icd10-status" title="Hiện hành"></i>':'<i class="fa fa-ban text-danger icd10-status" title="Ngừng dùng"></i>';}}
        ],
        language:{processing:'Đang tải dữ liệu...',search:'Tìm mã hoặc tên bệnh:',lengthMenu:'Hiển thị _MENU_ dòng',zeroRecords:'Không tìm thấy mã ICD-10',info:'Hiển thị _START_ đến _END_ trong _TOTAL_ mã',infoEmpty:'Không có dữ liệu',infoFiltered:'(lọc từ _MAX_ mã)',paginate:{first:'Đầu',last:'Cuối',next:'Sau',previous:'Trước'}}
    });
    $('#btn-search-icd10').on('click',function(){table.ajax.reload();});
    $('#btn-reset-icd10').on('click',function(){$('#icd10-search').val('');table.ajax.reload();$('#icd10-search').focus();});
    $('#icd10-search').on('keydown',function(event){
        if(event.key==='Enter'||event.which===13){event.preventDefault();table.ajax.reload();}
    });
})(jQuery);
