(function ($) {
    'use strict';
    var baseUrl = $('#ULocal').val();
    var csrf = $('#bhyt-csrf').val();
    var deleting = null;
    function can(id) { var input=$(id); return !input.length || input.val()==='true'; }
    function compact(value,type) { var text=value==null?'':String(value); if(type!=='display')return text; return $('<span>').addClass('bhyt-ellipsis').attr('title',text).text(text.length>90?text.substring(0,90).trim()+'…':text).prop('outerHTML'); }
    function rowData(button) { var row=$(button).closest('tr'); if(row.hasClass('child'))row=row.prev(); return table.row(row).data(); }
    function reset() { $('#form-bhyt')[0].reset(); $('#bhyt-original-id').val(''); $('#bhyt-dien').val('4'); $('#bhyt-message').hide(); }
    function message(text,ok) { $('#bhyt-message').removeClass('alert-success alert-danger').addClass(ok?'alert-success':'alert-danger').text(text).show(); }
    var table=$('#datatable-bhyt').DataTable({
        ajax:{url:baseUrl+'bhyt/getData/',type:'POST'}, pageLength:Number($('#pageLength').val())||25,
        order:[[4,'asc'],[1,'asc']], responsive:false, autoWidth:false,
        columns:[
            {data:null,orderable:false,className:'text-center',width:'5%',render:function(d,t,r,m){return m.row+m.settings._iDisplayStart+1;}},
            {data:'id',className:'text-center',width:'7%'},{data:'ten',width:'22%',render:compact},
            {data:'mota',width:'48%',render:compact},
            {data:'dien',className:'text-center',width:'7%',render:function(v){return '<span class="label label-info bhyt-dien" title="Diện '+v+'">'+v+'</span>'; }},
            {data:null,orderable:false,searchable:false,width:'11%',className:'bhyt-actions',render:function(){var h='';if(can('#role-bhyt-save'))h+='<button class="btn btn-sm btn-info btn-edit-bhyt" title="Sửa"><i class="fa fa-pencil"></i></button> ';if(can('#role-bhyt-delete'))h+='<button class="btn btn-sm btn-danger btn-delete-bhyt" title="Xóa"><i class="fa fa-trash"></i></button>';return h;}}
        ],
        language:{search:'Tìm kiếm:',lengthMenu:'Hiển thị _MENU_ dòng',zeroRecords:'Không có nhóm BHYT',info:'Hiển thị _START_ đến _END_ trong _TOTAL_ nhóm',infoEmpty:'Không có dữ liệu',paginate:{next:'Sau',previous:'Trước'}},
        initComplete:function(){if(!can('#role-bhyt-save'))$('#btn-add-bhyt').hide();}
    });
    $('#btn-add-bhyt').on('click',function(){reset();$('#modal-bhyt .modal-title').text('Thêm nhóm BHYT');$('#modal-bhyt').modal('show');});
    $('#datatable-bhyt').on('click','.btn-edit-bhyt',function(){var item=rowData(this);reset();$('#bhyt-original-id').val(item.id);$('#bhyt-id').val(item.id);$('#bhyt-ten').val(item.ten);$('#bhyt-mota').val(item.mota);$('#bhyt-dien').val(item.dien);$('#modal-bhyt .modal-title').text('Sửa nhóm BHYT');$('#modal-bhyt').modal('show');});
    $('#form-bhyt').on('submit',function(e){e.preventDefault();var button=$('#btn-save-bhyt').prop('disabled',true);$.ajax({url:baseUrl+'bhyt/save/',type:'POST',dataType:'json',data:{original_id:$('#bhyt-original-id').val(),id:$('#bhyt-id').val(),ten:$('#bhyt-ten').val(),mota:$('#bhyt-mota').val(),dien:$('#bhyt-dien').val(),csrf_token:csrf}}).done(function(r){message(r.message,r.success);if(r.success){table.ajax.reload(null,false);setTimeout(function(){$('#modal-bhyt').modal('hide');},600);}}).fail(function(){message('Có lỗi xảy ra khi lưu nhóm BHYT.',false);}).always(function(){button.prop('disabled',false);});});
    $('#datatable-bhyt').on('click','.btn-delete-bhyt',function(){deleting=rowData(this);$('#delete-bhyt-name').text(deleting.id+' - '+deleting.ten);$('#modal-delete-bhyt').modal('show');});
    $('#btn-confirm-delete-bhyt').on('click',function(){if(!deleting)return;var button=$(this).prop('disabled',true);$.ajax({url:baseUrl+'bhyt/delete/',type:'POST',dataType:'json',data:{id:deleting.id,csrf_token:csrf}}).done(function(r){if(r.success){$('#modal-delete-bhyt').modal('hide');table.ajax.reload(null,false);deleting=null;}else alert(r.message);}).fail(function(){alert('Có lỗi xảy ra khi xóa nhóm BHYT.');}).always(function(){button.prop('disabled',false);});});
})(jQuery);

