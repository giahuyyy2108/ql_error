(function ($) {
    'use strict';
    var baseUrl=$('#ULocal').val(), csrf=$('#dvkt-ycmm-csrf').val();
    function can(operation){var field=$('#role-DVKT_YCMM-'+operation);return field.length===0||field.val()==='true';}
    function esc(value){return $('<div>').text(value==null?'':String(value)).html();}
    function request(method,data){return $.ajax({url:baseUrl+'DVKT_YCMM/'+method+'/',type:'POST',dataType:'json',data:$.extend({csrf_token:csrf},data||{})});}
    function message(text,ok){$('#dvkt-ycmm-message').removeClass('alert-success alert-danger').addClass(ok?'alert-success':'alert-danger').text(text).show();}
    function row(button){var tr=$(button).closest('tr');return table.row(tr.hasClass('child')?tr.prev():tr).data();}
    function viDate(value){if(!value)return '';var p=String(value).split('-');return p.length===3?p[2]+'/'+p[1]+'/'+p[0]:value;}
    var table=$('#datatable-dvkt-ycmm').DataTable({
        ajax:{url:baseUrl+'DVKT_YCMM/getData/',type:'POST'},pageLength:50,order:[[0,'asc']],responsive:false,autoWidth:false,
        columns:[
            {data:'stt',width:'6%',className:'text-center'},
            {data:'ma_dvkt',width:'13%'},
            {data:'ten_dvkt',width:'35%',render:function(v,t){return t==='display'?'<span class="dvkt-name" title="'+esc(v)+'">'+esc(v)+'</span>':v;}},
            {data:'ngay_hieu_luc',width:'10%',className:'text-center',render:function(v,t){return t==='display'?viDate(v):v;}},
            {data:'ngay_het_hieu_luc',width:'10%',className:'text-center',render:function(v,t){return t==='display'?viDate(v):v;}},
            {data:'tra_ket_qua_sau',width:'7%',className:'text-center',render:function(v,t){return t==='display'?(Number(v)?'<span class="label label-info">Có</span>':'Không'):Number(v);}},
            {data:'hieu_luc',width:'7%',className:'text-center',render:function(v,t,r){if(t!=='display')return Number(v);return '<input type="checkbox" class="toggle-dvkt-ycmm" data-id="'+r.id+'" '+(Number(v)?'checked ':'')+(!can('toggleStatus')?'disabled':'')+'>'; }},
            {data:null,width:'12%',orderable:false,className:'dvkt-ycmm-actions',render:function(){var h='<button class="btn btn-xs btn-info view-dvkt" title="Xem"><i class="fa fa-eye"></i></button> ';if(can('update'))h+='<button class="btn btn-xs btn-primary edit-dvkt" title="Sửa"><i class="fa fa-pencil"></i></button> ';if(can('delete'))h+='<button class="btn btn-xs btn-danger delete-dvkt" title="Xóa"><i class="fa fa-trash"></i></button>';return h;}}
        ],
        language:{search:'Tìm:',lengthMenu:'Hiển thị _MENU_ dòng',zeroRecords:'Không có dữ liệu',info:'Hiển thị _START_ đến _END_ trong _TOTAL_ dịch vụ',infoEmpty:'Không có dữ liệu',paginate:{next:'Sau',previous:'Trước'}},
        initComplete:function(){if(!can('save'))$('#btn-add-dvkt-ycmm').hide();}
    });
    function reset(){ $('#form-dvkt-ycmm')[0].reset();$('#dvkt-ycmm-id').val('');$('#dvkt-ycmm-active').val('1');$('#dvkt-ycmm-message').hide();$('#form-dvkt-ycmm :input').prop('disabled',false);$('#btn-save-dvkt-ycmm').show(); }
    function fill(item,readOnly){reset();$('#dvkt-ycmm-id').val(item.id);$('#dvkt-ycmm-stt').val(item.stt);$('#dvkt-ycmm-code').val(item.ma_dvkt);$('#dvkt-ycmm-name').val(item.ten_dvkt);$('#dvkt-ycmm-from').val(item.ngay_hieu_luc||'');$('#dvkt-ycmm-to').val(item.ngay_het_hieu_luc||'');$('#dvkt-ycmm-active').val(String(item.hieu_luc));$('#dvkt-ycmm-late').prop('checked',Number(item.tra_ket_qua_sau)===1);if(readOnly){$('#form-dvkt-ycmm :input').not('[data-dismiss]').prop('disabled',true);$('#btn-save-dvkt-ycmm').hide();}$('#modal-dvkt-ycmm .modal-title').text(readOnly?'Chi tiết DVKT':'Cập nhật DVKT');$('#modal-dvkt-ycmm').modal('show');}
    function open(button,readOnly){var item=row(button);request('getOne',{id:item.id}).done(function(r){if(r.success)fill(r.row,readOnly);else alert(r.message);}).fail(function(){alert('Không thể tải bản ghi.');});}
    $('#btn-add-dvkt-ycmm').on('click',function(){reset();$('#modal-dvkt-ycmm .modal-title').text('Thêm DVKT yêu cầu mã máy');$('#modal-dvkt-ycmm').modal('show');});
    $('#datatable-dvkt-ycmm').on('click','.view-dvkt',function(){open(this,true);}).on('click','.edit-dvkt',function(){open(this,false);}).on('click','.delete-dvkt',function(){var item=row(this);if(!confirm('Xóa mã DVKT '+item.ma_dvkt+'?'))return;request('delete',{id:item.id}).done(function(r){if(r.success)table.ajax.reload(null,false);alert(r.message);}).fail(function(){alert('Không thể xóa bản ghi.');});}).on('change','.toggle-dvkt-ycmm',function(){var input=$(this),active=input.is(':checked');input.prop('disabled',true);request('toggleStatus',{id:input.data('id'),hieu_luc:active?'1':'0'}).done(function(r){if(!r.success){input.prop('checked',!active);alert(r.message);}else table.ajax.reload(null,false);}).fail(function(){input.prop('checked',!active);alert('Không thể cập nhật hiệu lực.');}).always(function(){input.prop('disabled',false);});});
    $('#form-dvkt-ycmm').on('submit',function(e){e.preventDefault();var id=$('#dvkt-ycmm-id').val(),button=$('#btn-save-dvkt-ycmm').prop('disabled',true);request(id?'update':'save',{id:id,stt:$('#dvkt-ycmm-stt').val(),ma_dvkt:$('#dvkt-ycmm-code').val(),ten_dvkt:$('#dvkt-ycmm-name').val(),ngay_hieu_luc:$('#dvkt-ycmm-from').val(),ngay_het_hieu_luc:$('#dvkt-ycmm-to').val(),hieu_luc:$('#dvkt-ycmm-active').val(),tra_ket_qua_sau:$('#dvkt-ycmm-late').is(':checked')?'1':'0'}).done(function(r){message(r.message,r.success);if(r.success){table.ajax.reload(null,false);setTimeout(function(){$('#modal-dvkt-ycmm').modal('hide');},400);}}).fail(function(){message('Không thể lưu dữ liệu.',false);}).always(function(){button.prop('disabled',false);});});
    function search(){table.search($.trim($('#dvkt-ycmm-search').val())).draw();}
    $('#btn-search-dvkt-ycmm').on('click',search);$('#btn-reset-dvkt-ycmm').on('click',function(){$('#dvkt-ycmm-search').val('');table.search('').draw();});$('#dvkt-ycmm-search').on('keydown',function(e){if(e.which===13){e.preventDefault();search();}});
})(jQuery);
