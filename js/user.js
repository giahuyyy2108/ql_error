/* DATA TABLES */
var table;		
function init_DataTables() {	
	//var url = $("#ULocal").val()+'user/getData/';
	//alert(url);
	// console.log('run_datatables');	
	table = $('#datatable-user').DataTable({
		"ajax": 
		{
			url: $("#ULocal").val()+'user/getData/',
			type: 'POST',
			data: function ( d ) {
				return $.extend( {}, d, {
					"fullName": $("#fullName").val(),
					"userName": $("#userName").val()					
				} );
			},
			error: function (response) {
				  alert(JSON.stringify(response))
			},	
		},	
		"pageLength": $('#pageLength').val(),	
		"searching": false,
		"order": [[ 0, "desc" ]],
		scrollY:        '50vh',
        scrollCollapse: true,
		dom: '<"dt-toolbar">frtlpi',
		destroy: true,
		buttons: [						
			{
			  extend: "excel",
			  className: "btn-sm"
			},
			{
			  extend: "pdfHtml5",
			  className: "btn-sm"
			},
			{
			  extend: "print",
			  className: "btn-sm"
			}            
		],			
		responsive: true,
		bAutoWidth: false,	
		"columnDefs": [				
			{
				"targets": 0,
				"width": '5%', 
				"className": "text-center",
				"sortable": false,
				"render": function ( data, type, row, meta ) {	
					return (meta.row + 1);//[row].join('');
				}								
			},
			{
				"targets": 1,
				"width": '25%', 								
				"data" : "hoTen"	
			},
			{
				"targets": 2,
				"width": '15%',
				"data" : "username",
				"render": function ( data, type, row ) {
					var str = "";
					str = '<input type="hidden" data-field="Input" value="'+data+'">'+data;
					
					return [str].join('');
				}
			},
			{
				"targets": 3,
				"width": '15%',
				"data" : "password",
				"render": function ( data, type, row ) {					
					return '***';
				},
				"createdCell": function (td, cellData, rowData, row, col) {
					$(td).html('***');
				}
			},
			{
				"targets": 4,
				"width": '15%',
				"data" : "maNQ",
				"render": function ( data, type, row ) {
					var valueSelect = $("#nhomquyen option[value='"+data+"']").text();
					return '<input type="hidden" id = "selectid" value="'+data+'">'+valueSelect;
				}
			},	
			
			{                
				"targets": 5,
				"width": '5%',
				"data" : "id",
				"sortable": false,
				"render": function ( data, type, row ) {
					var save = "",update = "", del = "", id = "", polyci = "", lock="";
					id = '<input type="hidden" name = "id" id = "id" value="'+data+'">';
					// save = '<a id="btn-update" class="add" title="Lưu" data-toggle="tooltip"><i class="glyphicon glyphicon-floppy-disk"></i></a>';
					update = '<a class="edit" title="Sửa" data-toggle="tooltip"><i class="glyphicon glyphicon-pencil" style="color: #bc7bff;"></i></a> &nbsp';
					if(row["adminType"] == '0'){
						del = '<a class="delete" title="Xóa" data-toggle="tooltip"><i class="glyphicon glyphicon-trash" style="color: red;"></i></a>';					
						polyci = '<a class="polici" title="Phân quyền" data-toggle="tooltip"><i class="glyphicon glyphicon-globe" style="color: blue;"></i></a> &nbsp';
						lock = '<a class="lock" title="Mở Khóa người dùng" data-toggle="tooltip" data-id="'+row["nd_block"]+'"><i class="fa fa-lock" style="color: #dbb14e;"></i></a> &nbsp';
						if(row["nd_block"]=='0') {
							lock = '<a class="lock" title="Khóa người dùng" data-toggle="tooltip" data-id="'+row["nd_block"]+'"><i class="fa fa-unlock" style="color: #dbb14e;"></i></a> &nbsp';
						}						
					}
					return [id, update, polyci, lock, del].join('');
				}										
			}				
		],
		"language": {
			"lengthMenu": "Hiển thị _MENU_ dòng trên một trang",
			"zeroRecords": "Xin lỗi không tím thấy dữ liệu",
			"info": "Hiển thị trang _PAGE_ of _PAGES_ trang",
			"infoEmpty": "Không có dữ liệu",
			"infoFiltered": "(filtered from _MAX_ total records)",
			"paginate": {
				"first":      "First",
				"last":       "Last",
				"next":       "Sau",
				"previous":   "Trước"
			}
		},
		initComplete: function(){
			$("div.dt-toolbar").html('<button type="button" class="btn btn-info add-new"><i class="fa fa-plus"></i> Thêm mới</button>' +
			 '<button type="button" class="btn btn-info save"><i class="fa fa-floppy-o"></i> Cập nhật</button>');   
			hiddenButton();	
			// Append table with add row form on add new button click
			$(".add-new").click(function(){				
				addRowInput($('#datatable-user'),1);				
			});
			
			// save new row
			$(".save").click(function(){	
				if(confirm("Bạn có chắc muốn cập nhật dữ liệu không !")) {							
					if(saveData($('#datatable-user'),$("#ULocal").val()+'user/saveUser/',"'Người dùng đã được cập nhật thành công.'")){
						$('#datatable-user').DataTable().ajax.reload(hiddenButton);
					}							
				}				
			});
		}	
	});	
};

$(document).ready(function() {				
		
	init_DataTables();		
	
	$('[data-toggle="tooltip"]').tooltip();				
	// update data
	$(document).on("click", ".add", function(){			
		if(confirm("Bạn có chắc muốn cập nhật dữ liệu này không !")){
			updateData($(this), $("#ULocal").val()+'user/saveUser/','Ngươi dùng đã được cập nhật thành công.');	
			$('#datatable-user').DataTable().ajax.reload(hiddenButton);
		}
	});
	
	// Edit row on edit button click
	$(document).on("click", ".edit", function(){	
		save = '<a id="btn-update" class="add" title="Lưu" data-toggle="tooltip"><i class="glyphicon glyphicon-floppy-disk" style="color: green;"></i></a>';
		addInput($('#datatable-user'),$(this).closest('tr'));					
		$(this).closest('tr').find(".edit, .polici, .lock, .delete").toggle();			
		$(this).closest('tr').find("td:eq(5)").append(save);
	});
	
	// Delete row on delete button click
	$(document).on("click", ".delete", function(){
		if(confirm("Bạn có chắc muốn xóa dữ liệu này không !")){
			deleteData(table,$(this),$("#ULocal").val()+'user/deleteUser/','Ngươi dùng đã được xóa thành công.')
			//table.ajax.reload();				
		}		
	});
	
	// Lock user
	$(document).on("click", ".lock", function(){
		if(confirm("Bạn có chắc muốn khóa/mở khóa người dùng này không !")){
			var obj = $(this);
			var maUser = getId($(this)); 
			var lock = $(this).attr("data-id");
			url = $("#ULocal").val()+'user/lockUser/';
				// Save data
			$.ajax({
				url: url,
				data: {						
						'maUser' : maUser,
						'lock' : lock
					  },
				type: 'POST',
				dataType : "json",
				cache: false,
				//async: false,
				error: function(htmlText){		
					alert("loi :"+JSON.stringify(htmlText));
				},
				success : function(json) {	
					table.ajax.reload(hiddenButton);
				}
			});			
		}		
	});

	$(document).on("click", ".polici", function(){
		var tr = $(this).closest('tr');
		var row = table.row( tr );
		
		$("#modal-phanquyen").modal({
			backdrop: "static"					
		});

		$("#modal-phanquyen .modal-title").html("Phân quyền người dùng : " + row.data().username);
		
		var maUser = getId($(this)); 
		
		url = $("#ULocal").val()+'user/phanquyen/';
			// Save data
		$.ajax({
			url: url,
			data: {						
					'maUser' : maUser
				  },
			type: 'POST',
			dataType : "text",
			cache: false,
			//async: false,
			error: function(htmlText){		
				alert("loi :"+JSON.stringify(htmlText));
			},
			success : function(htmlText) {	
				$("#modal-phanquyen .modal-body").html(htmlText);
				$("#username").val(row.data().username);
			}
		});
	});		
	
	$(document).on("click", ".save-hang-modal", function(){
		if(confirm("Bạn có chắc muốn phân quyền cho người dùng ("+ $("#username").val() +") không !")){
			url = $("#ULocal").val()+'user/savePhanquyen/';
			$.ajax({
				type: 'post',
				url: url,
				data: $('#frmQuyen').serialize(),
				dataType : "JSON",
				cache: false,			
				error: function(json){		
					alert("loi :"+JSON.stringify(json));
					console.log(JSON.stringify(json));
				},
				success: function (json) {
					alert('Đã lưu quyền cho người dùng :' + $("#username").val());
				}
			});
		}		
	});
	
	$(document).on("click", '.level0 input[type="checkbox"]', function(){
		var id = $(this).attr("level");
		checked = $(this).prop( "checked");
		
		var level1 = $(this).parents("table").find('.level1 input[level="'+id+'"]');
		level1.each(function(){
			$(this).prop( "checked",checked);
		});
		
		var level2 = $(this).parents("table").find('.level2 input[level="'+id+'"]');
		
		level2.each(function(){
			$(this).prop( "checked",checked);			
			$(this).trigger('change');
		});
	});
	
	$(document).on("click", '.level1 input[type="checkbox"]', function(){
		var id = $(this).attr("data-chucnang");
		var level = $(this).attr("level");
		checked = $(this).prop( "checked");
		//alert(checked);
		var level2 = $(this).parents("table").find('.level2 input[data-chucnang="'+id+'"]');
		
		level2.each(function(){
			if(checked) $(this).prop( "checked",true);
			else $(this).prop( "checked",false);
			//$(this).click();
			$(this).trigger('change');
		});
		
		var level0 = $(this).parents("table").find('.level0 input[level="'+level+'"]');
		level0.each(function(){
			if(checked) $(this).prop( "checked",true);
			else {
				var level1 = $(this).parents("table").find('.level1 input[level="'+level+'"]');
				var tmp = true;
				level1.each(function(){
					if($(this).prop( "checked")){
						tmp = false;
						return false;	
					} 				
				});
				if(tmp) $(this).prop( "checked",false);
			}			
		});
	});
	
	$(document).on("change", '.level2 input[type="checkbox"]', function(){		
		var id = $(this).attr("data-chucnang");
		var level = $(this).attr("level");
		checked = $(this).prop( "checked");
		
		var level1 = $(this).parents("table").find('.level1 input[data-chucnang="'+id+'"]');
		level1.each(function(){
			if(checked) $(this).prop( "checked",true);
			else {
				var level2 = $(this).parents("table").find('.level2 input[data-chucnang="'+id+'"]');
				var tmp = true;
				level2.each(function(){
					if($(this).prop( "checked")){
						tmp = false;
						return false;	
					} 				
				});
				if(tmp) $(this).prop( "checked",false);
			}			
		});
		
		var level0 = $(this).parents("table").find('.level0 input[level="'+level+'"]');
		level0.each(function(){
			if(checked) $(this).prop( "checked",true);
			else {
				var level1 = $(this).parents("table").find('.level1 input[level="'+level+'"]');
				var tmp = true;
				level1.each(function(){
					if($(this).prop( "checked")){
						tmp = false;
						return false;	
					} 				
				});
				if(tmp) $(this).prop( "checked",false);
			}			
		});
	});
	
	$(document).on("change", '.level3 input[type="checkbox"]', function(){		
		var id = $(this).attr("data-chucnang");
		var level = $(this).attr("level");
		checked = $(this).prop( "checked");
		
		var level1 = $(this).parents("table").find('.level1 input[data-chucnang="'+id+'"]');
		level1.each(function(){
			if(checked) $(this).prop( "checked",true);
			else {
				var level2 = $(this).parents("table").find('.level2 input[data-chucnang="'+id+'"]');
				var tmp = true;
				level2.each(function(){
					if($(this).prop( "checked")){
						tmp = false;
						return false;	
					} 				
				});
				if(tmp) $(this).prop( "checked",false);
			}
		});
		
		var level0 = $(this).parents("table").find('.level0 input[level="'+level+'"]');
		level0.each(function(){
			if(checked) $(this).prop( "checked",true);
			else {
				var level1 = $(this).parents("table").find('.level1 input[level="'+level+'"]');
				var tmp = true;
				level1.each(function(){
					if($(this).prop( "checked")){
						tmp = false;
						return false;	
					} 				
				});
				if(tmp) $(this).prop( "checked",false);
			}			
		});		
	});
	
	$("#btnSearch").click(function(){
		$('#datatable-user').DataTable().ajax.reload(hiddenButton);		
	});

	$("#btnReset").click(function(){		
		$("#fullName").val("");
		$("#userName").val("");
    });
});	
function hiddenButton(){
	if($("#role-user-saveUser").val() == "false"){
		$(".add-new").hide();
		$(".edit").hide();
		$(".save").hide();
	}
	if($("#role-user-deleteUser").val() == "false"){
		$(".delete").hide();				
	}
	if($("#role-user-phanquyen").val() == "false"){
		$(".polici").hide();
	}	
}	