(function ($) {
    'use strict';
    var baseUrl=$('#ULocal').val(), csrf=$('#tablelookup-csrf').val(), selected=null;
    var schema=JSON.parse($('#tablelookup-schema').text()||'{}');
    function can(id){var field=$(id);return field.length===0||field.val()==='true';}
    function esc(value){return $('<div>').text(value==null?'':String(value)).html();}
    function columnsForTable(table, selectedColumns, selectedConditions){
        var columns=schema[table]||[], a=$('#lookup-columns').empty(), c=$('#lookup-conditions').empty();
        selectedColumns=selectedColumns||[]; selectedConditions=selectedConditions||[];
        $.each(columns,function(_,column){
            $('<option>').val(column).text(column).prop('selected',selectedColumns.indexOf(column)>=0).appendTo(a);
            $('<option>').val(column).text(column).prop('selected',selectedConditions.indexOf(column)>=0).appendTo(c);
        });
    }
    function split(value){return value?String(value).split(',').map(function(v){return v.trim();}).filter(Boolean):[];}
    function reset(){
        $('#form-tablelookup')[0].reset(); $('#lookup-id').val(''); $('#lookup-active').prop('checked',true);
        $('#lookup-columns,#lookup-conditions').empty(); $('#tablelookup-message').hide();
    }
    function message(text,ok){$('#tablelookup-message').removeClass('alert-success alert-danger').addClass(ok?'alert-success':'alert-danger').text(text).show();}
    function row(button){var tr=$(button).closest('tr');return table.row(tr.hasClass('child')?tr.prev():tr).data();}
    var table=$('#datatable-tablelookup').DataTable({
        ajax:{url:baseUrl+'tablelookup/getData/',type:'POST'},pageLength:25,order:[[2,'asc']],responsive:false,autoWidth:false,
        columns:[
            {data:null,width:'5%',orderable:false,className:'text-center',render:function(d,t,r,m){return m.row+m.settings._iDisplayStart+1;}},
            {data:'source_key',width:'12%'},{data:'display_name',width:'15%'},{data:'table_name',width:'12%'},
            {data:'allowed_columns',width:'19%',render:esc},{data:'condition_columns',width:'19%',render:esc},
            {data:'is_active',width:'8%',className:'text-center',render:function(v,type,row){
                if(type!=='display')return Number(v);
                var checked=Number(v)===1?' checked':'',disabled=can('#role-tablelookup-toggleStatus')?'':' disabled';
                return '<label class="lookup-switch" title="'+(Number(v)===1?'Đang hoạt động':'Đang tắt')+'"><input type="checkbox" class="toggle-lookup" data-id="'+row.id+'"'+checked+disabled+'><span class="lookup-slider"></span></label>';
            }},
            {data:null,width:'10%',orderable:false,className:'lookup-actions',render:function(){var h='';if(can('#role-tablelookup-update'))h+='<button class="btn btn-sm btn-info edit-lookup" title="Sửa"><i class="fa fa-pencil"></i></button> ';if(can('#role-tablelookup-delete'))h+='<button class="btn btn-sm btn-danger delete-lookup" title="Xóa"><i class="fa fa-trash"></i></button>';return h;}}
        ],
        language:{search:'Tìm kiếm:',lengthMenu:'Hiển thị _MENU_ dòng',zeroRecords:'Không có nguồn đối chiếu',info:'Hiển thị _START_ đến _END_ trong _TOTAL_ nguồn',infoEmpty:'Không có dữ liệu',paginate:{next:'Sau',previous:'Trước'}},
        initComplete:function(){if(!can('#role-tablelookup-save'))$('#btn-add-lookup').hide();}
    });
    $('#lookup-table').on('change',function(){columnsForTable(this.value);});
    $('#datatable-tablelookup').on('change','.toggle-lookup',function(){
        var input=$(this),next=input.is(':checked'),previous=!next;
        input.prop('disabled',true);
        $.ajax({url:baseUrl+'tablelookup/toggleStatus/',type:'POST',dataType:'json',data:{id:input.data('id'),is_active:next?'1':'0',csrf_token:csrf}})
        .done(function(r){
            if(!r.success){input.prop('checked',previous);alert(r.message);return;}
            var data=table.row(input.closest('tr')).data();if(data)data.is_active=Number(r.is_active);
            input.closest('label').attr('title',next?'Đang hoạt động':'Đang tắt');
        }).fail(function(){input.prop('checked',previous);alert('Có lỗi xảy ra khi cập nhật trạng thái.');})
        .always(function(){input.prop('disabled',false);});
    });
    $('#btn-add-lookup').on('click',function(){reset();$('#modal-tablelookup .modal-title').text('Đăng ký nguồn đối chiếu');$('#modal-tablelookup').modal('show');});
    $('#datatable-tablelookup').on('click','.edit-lookup',function(){
        var item=row(this);reset();$('#lookup-id').val(item.id);$('#lookup-key').val(item.source_key);$('#lookup-name').val(item.display_name);$('#lookup-table').val(item.table_name);
        columnsForTable(item.table_name,split(item.allowed_columns),split(item.condition_columns));$('#lookup-active').prop('checked',Number(item.is_active)===1);$('#modal-tablelookup .modal-title').text('Sửa nguồn đối chiếu');$('#modal-tablelookup').modal('show');
    });
    $('#form-tablelookup').on('submit',function(e){e.preventDefault();var id=$('#lookup-id').val(),button=$('#btn-save-lookup').prop('disabled',true);
        $.ajax({url:baseUrl+'tablelookup/'+(id?'update/':'save/'),type:'POST',dataType:'json',data:{id:id,source_key:$('#lookup-key').val(),display_name:$('#lookup-name').val(),table_name:$('#lookup-table').val(),allowed_columns:$('#lookup-columns').val()||[],condition_columns:$('#lookup-conditions').val()||[],is_active:$('#lookup-active').is(':checked')?'1':'0',csrf_token:csrf}})
        .done(function(r){message(r.message,r.success);if(r.success){table.ajax.reload(null,false);setTimeout(function(){$('#modal-tablelookup').modal('hide');},500);}}).fail(function(){message('Có lỗi xảy ra khi lưu nguồn đối chiếu.',false);}).always(function(){button.prop('disabled',false);});
    });
    $('#datatable-tablelookup').on('click','.delete-lookup',function(){selected=row(this);if(!confirm('Xóa nguồn đối chiếu '+selected.source_key+'?'))return;
        $.post(baseUrl+'tablelookup/delete/',{id:selected.id,csrf_token:csrf},function(r){if(r.success)table.ajax.reload(null,false);else alert(r.message);},'json').fail(function(){alert('Có lỗi xảy ra khi xóa nguồn đối chiếu.');});
    });
})(jQuery);
