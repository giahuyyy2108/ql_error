jQuery(function($){
	fieldsDay = ['typeDate']; 
	fieldsNumber = ['typeNumber'];
	fieldsFloat = ['typeFloat'];
	fieldsEmail = ['typeEmail'];
	fieldsNoNumber = ['typeNoNumber'];
	fieldsValidate = ['typeValidate']; // kiem tra rong	
	fieldsValidateLength = ['typeValidateLength']; // kiem tra rong	
	var objFocus ;
	
	$(document).ready(function() {
		initDay();	
		initNumber();
		initFloat();
		initEmail();
		initValidate();
		
		setInterval(checkSession, 10000);	
		//focus select all text
		$('body').on('focus', 'input', function() { 
			if(!$(this).is(objFocus)){
				if($(this).hasClass("typeNumber")||$(this).hasClass("typeFloat")){
					$(this).val(stripNonNumeric($(this).val()));
				}
				$(this).select();				
				objFocus = $(this);				
			}					
		});
		$('body').on('blur', 'input', function() { 			
			if($(this).hasClass("typeNumber")||$(this).hasClass("typeFloat")){
				$(this).val(numberFormat(stripNonNumeric($(this).val())));
			}				
		});	
		// enter focus
		$('body').on('keypress', '.form-control', function(e) {			
			if (e.which === 13) {
				var index = $('.form-control').index(this) + 1;
				$('.form-control').eq(index).focus();
			}
			key = e.which;
			if($(this).hasClass("typeFloat") && (key < 48 || key >57) && key != 46){	// kiem tra so thuc
				return false;
			}	
		});	
		
		
	});    
});
/*
window.onhashchange = function() {
	alert("da back");
};

window.addEventListener('popstate', function(event) {
    // The popstate event is fired each time when the current history entry changes.

    var r = confirm("You pressed a Back button! Are you sure?!");

    if (r == true) {
        // Call Back button programmatically as per user confirmation.
        history.back();
        // Uncomment below line to redirect to the previous page instead.
        // window.location = document.referrer // Note: IE11 is not supporting this.
    } else {
        // Stay on the current page.
        history.pushState(null, null, window.location.pathname);
    }

    history.pushState(null, null, window.location.pathname);

}, false);
*/
function initDay(){
	initType ('dd_mm_yyyy', fieldsDay);
}

function initNumber(){
	initType ('number', fieldsNumber);
	initType ('number', fieldsNoNumber);	
}

function initFloat(){	
	$.each( fieldsFloat, function (index, value) {		
		$('.'+value).addClass('form-control')					
					.parent();
		
	});
}

function initEmail(){
	initType ('email', fieldsEmail);
}

function initValidate(){
	$.each( fieldsValidate, function (index, value) {
		$('body').on('blur', '.'+value, function() { 
		//$('.'+value).on('blur', function (value) {
			return function (event) {
				$this = $(this);				
				if($this.val()==""){
					$this.parent()
							.removeClass('has-success has-error')
							.addClass('has-error')
							.children(':last');
					$this.focus();
				}
				else{
					$this.parent()
					   .removeClass('has-success has-error')
					   .addClass('has-success')
					   .children(':last');
				}				
			}
		}(value));
	});
}

function initValidateLength(){
	$.each( fieldsValidateLength, function (index, value) {
		$('body').on('blur', '.'+value, function() { 
		//$('.'+value).on('blur', function (value) {
			return function (event) {
				$this = $(this);
				var validateLength = $this.attr("length");	
				var validateLengthMin = $this.attr("lengthMin");
				var validateLengthMax = $this.attr("lengthMax");
				//alert(validateLengthMin);	
				if(validateLength != undefined){				
					if($this.val().length < validateLength || $this.val().length > validateLength){
						$this.parent()
								.removeClass('has-success has-error')
								.addClass('has-error')
								.children(':last');
						$this.focus();
					}
					else{
						$this.parent()
						   .removeClass('has-success has-error')
						   .addClass('has-success')
						   .children(':last');
					}	
				}	
				if(validateLengthMin != undefined){				
					if($this.val().length > validateLengthMin){
						$this.parent()
								.removeClass('has-success has-error')
								.addClass('has-error')
								.children(':last');
						$this.focus();
					}
					else{
						$this.parent()
						   .removeClass('has-success has-error')
						   .addClass('has-success')
						   .children(':last');
					}	
				}
				if(validateLengthMax != undefined){				
					if($this.val().length < validateLengthMax){
						$this.parent()
								.removeClass('has-success has-error')
								.addClass('has-error')
								.children(':last');
						$this.focus();
					}
					else{
						$this.parent()
						   .removeClass('has-success has-error')
						   .addClass('has-success')
						   .children(':last');
					}	
				}	
			}
		}(value));
	});
}

function initType(format, fields){
	$.each( fields, function (index, value) {				
		$('.'+value).formance('format_'+format)
					.addClass('form-control')
					//.wrap('<div/>')
					.parent();
		
		$('body').on('keyup change blur', '.'+value, function() {		
			return function (event) {
				$this = $(this);				
				if ($this.formance('validate_'+format) || ($this.hasClass("typeNumber") && stripNonNumeric($this.val())!="")) {
					$this.parent()
						   .removeClass('has-success has-error')
						   .addClass('has-success')
						   .children(':last');
				} else {					
					if($this.val()!=""){
						$this.parent()
								.removeClass('has-success has-error')
								.addClass('has-error')
								.children(':last');
						$this.focus();
					}
					else{
						$this.parent()
						   .removeClass('has-success has-error')
						   .addClass('has-success')
						   .children(':last');
					}
				}				
			}
		}(value));
	});
};

function addInput (table, obj, flag = "edit"){
	var arrayType = [0, 0, 0, 0];
	
	var row = table.find("tr").first();
	var listCol = row.find("th:not(:last-child)");
	var lstobj = obj.find("td:not(:last-child)");
	
	var i=0;
	lstobj.each(function(){
		var validate = $(listCol[i]).attr("validate-type");
		var validateLength = $(listCol[i]).attr("validate-length");
		var validateLengthMin = $(listCol[i]).attr("validate-length-min");
		var validateLengthMax = $(listCol[i]).attr("validate-length-max");
		var data_default = "";
		var tmp = "";
		if($(listCol[i]).attr("data-default")){
			data_default = $(listCol[i]).attr("data-default");			
		}
		var tmp1 = "";
		if(validate== "true"){				
			tmp1 = "typeValidate";
			arrayType[3] = 1;
		}
		length = "";
		if(validateLength > 0){				
			tmp = tmp1 +" typeValidateLength";
			length = 'length = "' + validateLength + '"';
			arrayType[4] = 1;
		}
		if(validateLengthMin > 0){				
			tmp = tmp1 +" validateLengthMin";
			length = 'lengthMin = "' + validateLengthMin + '"';
			arrayType[4] = 1;
		}
		if(validateLengthMax > 0){				
			tmp = tmp1 +" validateLengthMax";
			length = 'lengthMax = "' + validateLengthMax + '"';
			arrayType[4] = 1;
		}
		if(tmp == "") tmp = tmp1;
		
		var exception = $(listCol[i]).attr("exception");
		if(flag != exception){
		//alert(validate);
			if($(this).text() != "") data_default = $(this).text();
			switch($(listCol[i]).attr("data-type")) {
				case "Date":
					$(this).addClass('td-input');
					$(this).html('<input type="text" data-field="Input" class="dd_mm_yyyy typeDate '+tmp+'" placeholder="DD/MM/YYYY"  value="' + data_default + '">');
					arrayType[0] = 1;
					//initDay();
					break;
				case "Number":
					$(this).addClass('td-input');
					$(this).html('<input type="text" data-field="Input" class="typeNumber '+tmp+'" ' + length + ' placeholder="Number"  value="' + data_default + '">');
					arrayType[1] = 1;
					//initNumber();
					break;
				case "NoNumber":
					$(this).addClass('td-input');
					$(this).html('<input type="text" data-field="Input" class="typeNoNumber '+tmp+'" ' + length + ' placeholder="Number"  value="' + data_default + '">');
					arrayType[1] = 1;
					//initNumber();
					break;
				case "Email":
					$(this).addClass('td-input');
					$(this).html('<input type="text" data-field="Input" class="typeEmail '+tmp+'" placeholder="Email" data-formance_algorithm=\'complex\'  value="' + data_default + '">');
					arrayType[2] = 1;
					break;
				case "Select":
					$(this).addClass('td-input');
					var obj = $(listCol[i]).attr("objectId")
					$clone = $("#"+obj).clone();
					$($clone).addClass(tmp);				
					//$clone.prop('id', '');				
					$clone.removeAttr('id');
					// get id
					var inputHidden = $(this).find('input[type="hidden"]');
					var id = "0";
					inputHidden.each(function(){
						if($(this).attr('id')=== "selectid"){
							id = $(this).val();
						}
					});
					if(id != "0" && id != "") $($clone).val(id);
					else $($clone).val($("#"+obj).val());	
					$($clone).attr("data-field","Input");
					$($clone).addClass(tmp);
					$(this).html($clone);				
					break;
				case "String":
					$(this).addClass('td-input');
					arrdata = data_default.split(" *");
					data = "";
					if(arrdata.length > 1 && arrdata[1]== ""){
						data = arrdata[0];
					}
					else{
						data = data_default;
					}
					$(this).html('<input type="text" data-field="Input" class="form-control '+tmp+'" ' + length + ' value="' + data + '">');	
					break;	

					case "Password":
						$(this).addClass('td-input');
						$(this).html('<input type="password" data-field="Input" class="form-control '+tmp+'" value="' + data_default + '">');		
						break;
					case "File":
						$(this).addClass('td-input');
						$(this).html('<input type="file" data-field="Input" class="'+tmp+'" value="' + data_default + '">');		
						break;

					case "maBenhNhan":
						$(this).addClass('td-input');
						arrdata = data_default.split(" *");
						data = "";
						if (arrdata.length > 1 && arrdata[1] == "") {
							data = arrdata[0];
						}
						else {
							data = data_default;
						}
						$(this).html('<input type="text" data-field="Input" maxlength="8" class="form-control ' + tmp + '" ' + length + ' value="' + data + '">');
						var $input = $(this).find('input[type="text"]');
						$input.on('keypress', function (e) {
							if (e.key < '0' || e.key > '9') {
								e.preventDefault();
								Swal.fire({
									position: "center",
									icon: "warning",
									title: "Bạn chỉ được nhập số",
									showConfirmButton: false,
									timer: 1000
								});
							}
						});
						
						$input.on('keyup', function () {
							var patientCode = $(this).val();
							var URL = "http://hsoftapi.bvtn.org.vn/api/upd_hsoft_benhnhan/?ip=192.168.0.75&idbv=79025&mabn=" + patientCode;
							if (patientCode.length > 0) {
								$.ajax({
									url: URL,
									type: 'GET',
									data: { code: patientCode },
									success: function (response) {
										console.log(response)
										if (response.length > 0) {
											var patient = response[0];
										// 	$.ajax({
										// 		url: $("#ULocal").val() + 'benhnhan/getData/',
										// 		success: function (response) {
													// danhSachBN = response.data;
													// var patientExists = false;
													// danhSachBN.forEach(item => {
													// 	if (item.maBN === patient.mabn) {
													// 		patientExists = true;
													// 	}
													// });
													// if (patientExists) {
													// 	Swal.fire({
													// 		position: "center",
													// 		icon: "warning",
													// 		title: "Mã bệnh nhân này đã có trong danh sách",
													// 		showConfirmButton: false,
													// 		timer: 1000
													// 	});
	
													// } else {
														var $row = $input.closest('tr');
														$row.find('input[name="hoten"]').val(patient.hoten);
														// $row.find('select[name="phai"]').val(patient.phai == "1" ? "Nữ" : "Nam");
														// $row.find('input[name="ngaysinh"]').val(patient.ngaysinh);
														$row.find('input[name="namsinh"]').val(patient.namsinh);
														// $row.find('input[name="sodienthoai"]').val(patient.sodienthoai);
													// }
	
												// }
										// 	});
	
										}
									},
								});
							}
						});
						break;

					case "namSinh":
						$(this).addClass('td-input');
						$(this).html('<input type="text" maxLength="4" name="namsinh" data-field="Input" class=" form-control' + tmp + '" ' + length + '  value="' + data_default + '">');
						arrayType[1] = 1;
						var currentYear = new Date().getFullYear();

						$(this).find('input[type="text"]').on('keypress', function (e) {
							if (e.key < '0' || e.key > '9') {
								e.preventDefault();
								Swal.fire({
									position: "center",
									icon: "warning",
									title: "Bạn chỉ được nhập số",
									showConfirmButton: false,
									timer: 1000
								});
							}
						}).on('input', function () {
							var value = $(this).val();
							if (value.length === 4 && parseInt(value) > currentYear) {
								Swal.fire({
									position: "center",
									icon: "warning",
									title: "Năm sinh không được lớn hơn năm hiện tại",
									showConfirmButton: false,
									timer: 1000
								});
								$(this).val(''); // Xóa giá trị không hợp lệ
							}
						}).on('blur', function () {
							var value = $(this).val();
							if (value.length === 4 && parseInt(value) > currentYear) {
								Swal.fire({
									position: "center",
									icon: "warning",
									title: "Năm sinh không được lớn hơn năm hiện tại",
									showConfirmButton: false,
									timer: 1000
								});
								$(this).val(''); // Xóa giá trị không hợp lệ
							}
						});
						break;
					case "tenBenhNhan":
						$(this).addClass('td-input');
						// console.log(arrdata);
						arrdata = data_default.split(" *");
						data = "";
						if (arrdata.length > 1 && arrdata[1] == "") {
							data = arrdata[0];
						}
						else {
							data = data_default;
						}
						$(this).html('<input type="text" maxLength="30" name="hoten" data-field="Input" class="form-control ' + tmp + '" ' + length + ' value="' + data + '">');
						break;

						case "SelectBenhNhan":
                    $(this).addClass('td-input');
                    var obj = $(listCol[i]).attr("objectId");
                    $clone = $("#"+obj).clone();
                    $($clone).addClass(tmp);                
                    $clone.removeAttr('id');

                    var inputHidden = $(this).find('input[type="hidden"]');
                    var id = "0";
                    inputHidden.each(function(){
                        if($(this).attr('id') === "selectid"){
                            id = $(this).val();
                        }
                    });
                    if(id != "0" && id != "") $($clone).val(id);
                    else $($clone).val($("#"+obj).val());    
                    $($clone).attr("data-field","Input");
                    $($clone).addClass(tmp);
                    $(this).html($clone);
				
                    // Add the event listener for the select change
                    $clone.on('change', function() {
                        var selectedValue = $(this).val();
						// console.log(selectedValue)
                        var inputId = $(listCol[i]).attr("objectId");
                        $('#' + inputId).val(selectedValue);
                
						// Gọi Ajax để gửi dữ liệu về server
						$.ajax({
							url: $("#ULocal").val() + 'lichtpt/checkPhongBenhNhan/',  // Đường dẫn đến server xử lý
							type: 'POST',
							data: { selectedValue: selectedValue },
							dataType: 'json', // Ensure the dataType is 'json' to parse JSON response
							success: function(response) {
								// Debugging response
								// console.log(response);
						
								// Assuming response.data contains the required information
								var dta = response.data;
								// console.log(dta);
						
								// Check if there is an input with name 'phonggiuong' and set its value
								var phongGiuongInput = $('input[name="phonggiuong"]');
								var phongGiuongHiddenInput = $('input[name="phonggiuonghidden"]');
								if (phongGiuongInput.length > 0) {
									phongGiuongInput.val(dta[0].giuong);
									phongGiuongHiddenInput.val(dta[0].id_Phong);
								}
							},
							error: function(xhr, status, error) {
								// Handle errors
								console.error(error);
							}
						});
					
					});
                    break;

					case "StringPhong":
					$(this).addClass('td-input');
					arrdata = data_default.split(" *");
					data = "";
					if(arrdata.length > 1 && arrdata[1]== ""){
						data = arrdata[0];
					}
					else{
						data = data_default;
					}
					$(this).html('<input type="text" name="phonggiuong" class="form-control '+tmp+'" ' + length + ' value="' + data + '">'+
						 		'<input type="hidden" data-field="Input" name="phonggiuonghidden" value="">');	
					break;	
			}
		}			
		i++;
	});
	
	$.each(arrayType, function(index, value){
		switch(index) {
			case 0:				
				if(value ==1 ) initDay();
				break;
			case 1:
				if(value ==1 ) initNumber();				
				break;
			case 2:
				if(value ==1 ) initEmail();				
				break;	
			case 3:
				if(value ==1 ) initValidate();
				break;
			case 4:
				if(value ==1 ) initValidateLength();
				break;
		}
	});
}

function addRowInput (table, flage = 0, save = ""){	
	if(flage==1){
		var row = table.find("tr").first();
		var listCol = row.find("th");
		var newRow = '<tr>' ;		
		
		listCol.each(function(){
			newRow += '<td></td>';
		});	
		newRow += '</tr>';	
		table.prepend(newRow);
		
		var lstrow = table.find(".dataTables_empty");			
		lstrow.each(function(){															
			$(this).closest('tr').remove();				
		});
	}
	id = '<input type="hidden" name = "id" id = "id" value="">';						
	// del = '<a class="delete" title="Xóa" data-toggle="tooltip"><i class="fa fa-trash"></i></a>';	
	
	var row1 = table.find("tr:nth-child(1)")
	if(save == ""){
		row1.find("td:last-child").html([id].join(''));
	}
	else{
		row1.find("td:last-child").html([id, save].join(''));	
	}
	addInput(table, table.find("tr:nth-child(1)"), "add");	
}

function checkErrorData(obj){
	var input = $(obj).find('[data-field="Input"]');
	error = false;
	input.each(function(){
		$(this).parent().removeClass('has-success has-error');
		
		if($(this).hasClass("typeValidate") && $(this).val()==""){
			//$(this).addClass("error");
			$(this).parent().addClass('has-error');
			error = true;					
		} 		
		if($(this).hasClass("typeNumber") && !$.isNumeric(stripNonNumeric($(this).val()))&& $(this).val()!=""){
			//$(this).addClass("error");
			$(this).parent().addClass('has-error');
			error = true;			
		}
		if($(this).hasClass("typeEmail") && !(/(.+)@(.+){2,}\.(.+){2,}/.test($(this).val())) && $(this).val()!=""){			
			//$(this).addClass("error");
			$(this).parent().addClass('has-error');
			error = true;		
		}
		var regex = /^(((0[1-9]|[12]\d|3[01])\/(0[13578]|1[02])\/((19|[2-9]\d)\d{2}))|((0[1-9]|[12]\d|30)\/(0[13456789]|1[012])\/((19|[2-9]\d)\d{2}))|((0[1-9]|1\d|2[0-8])\/02\/((19|[2-9]\d)\d{2}))|(29\/02\/((1[6-9]|[2-9]\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00))))$/g;
		if($(this).hasClass("typeDate") && !regex.test($(this).val())&& $(this).val()!=""){
			//$(this).addClass("error");
			$(this).parent().addClass('has-error');
			error = true;			
		}	
	});	
	return error;	
}

function getDataRow(obj){
	var input = $(obj).find('[data-field="Input"]');
	var arrayData = new Array();
	var i = 0;	
	input.each(function(){			
		if($(this).hasClass("typeNumber")){
			arrayData [i] = stripNonNumeric($(this).val());	
		}
		else{
			arrayData [i] = $(this).val();
		}			
		i++;
	});
	return arrayData;
}

function setDataRow(obj){
	var input = $(obj).find('[data-field="Input"]');
	
	input.each(function(){	
		if($(this).attr('type')!='hidden') {
			$(this).parent("td").removeClass('td-input');
			if($(this).prop("tagName")=="SELECT"){
				var valueSelect = $(this).children("option:selected").text();
				$(this).parent("td").html('<input type="hidden" id = "selectid" value="'+$(this).val()+'">'+valueSelect);		
				return true;			
			}
			if($(this).attr('type')=='password') $(this).parent("td").html("");
			else {
				if($(this).hasClass("typeNumber")){
					$(this).parent("td").html(numberFormat(stripNonNumeric($(this).val())));
				}
				else {
					$(this).parent("td").html($(this).val());
				}
			}
		}
	});
}

function getId(obj){	
	var inputHidden = $(obj).closest('tr').find('input[type="hidden"]');
	var id = "";
	inputHidden.each(function(){
		if($(this).attr('id')=== "id"){			
			id = $(this).val();
		}
	});
	return id;
}

function getRowId(obj){
	var inputHidden = $(obj).find('input[type="hidden"]');
	var id = "";
	inputHidden.each(function(){
		if($(this).attr('id')=== "id"){
			id = $(this).val();
		}
	});
	return id;
}

function modalAlert(content){
	$('#modal_alert .modal-body').html("<p>"+content+"</p>");
	$('#modal_alert').modal('show')
}

function saveData(table, url, msg){
	//get list tr
	var error = false;
	var arrayData = new Array();
	var lstrow = table.find("tr:gt(0)");
	var dong = 0;
	lstrow.each(function(){
		var id = getRowId($(this));							
		if(id != "") {
			return false;
		}
		// check error data
		error = error || checkErrorData($(this));
		dong++;
	});
	if(error || dong == 0){
		return false;
	}
	var dong = 0;
	lstrow.each(function(){
		var id = getRowId($(this));							
		if(id != "") {
			return false;
		}	
		dong = 1;
		var	obj = $(this);
		// get data row
		arrayData = getDataRow($(this));
		//alert(JSON.stringify(arrayData));
		
		// Save data
		$.ajax({
			url: url,
			data: {
					'id' : id,
					'data' : JSON.stringify(arrayData)											
				  },
			type: 'POST',
			dataType : "json",
			cache: false,
			//async: false,
			error: function(json){		
				alert("loi"+JSON.stringify(json));
			},
			success : function(json) {
				//alert(json);
				if(json.message.flag == true){
					var save = "",update = "", del = "", id = "";
					id = '<input type="hidden" name = "id" id = "id" value="'+json+'">';
					save = '<a id="btn-update" class="add" title="Lưu" data-toggle="tooltip"><i class="material-icons">save</i></a>';
					update = '<a class="edit" title="Sửa" data-toggle="tooltip"><i class="material-icons">&#xE254;</i></a>';
					del = '<a class="delete" title="Xóa" data-toggle="tooltip"><i class="material-icons">&#xE872;</i></a>';
					obj.find(".delete").parent("td").html([id, save, update, del].join(''));
					
					obj.removeClass('alert-error');									
					setDataRow(obj);
				}
				else{
					error = true;					
					modalAlert("Có lỗi trên dòng " + dong + ". " + json.message.errorMessage);	
					obj.addClass('alert-error');
				}
			}
		});				
	});	
	if(!error) {	
		modalAlert(msg);
	}
	else{
		return false;
	}
	return true;	
}

function updateData(row, url, msg){
	var error = false;
	var id = getId(row); 
	var arrayData = new Array();
						
	// check error data row
	error = checkErrorData(row.closest('tr'));			
	
	row.closest('tr').find(".error").first().focus();
	
	if(!error) 				
	{									
		// get data row
		arrayData = getDataRow(row.closest('tr'));
		var	obj = row.closest('tr');
		//alert(JSON.stringify(arrayData));
		//alert("id:"+id);		
		// update date
		$.ajax({
			url: url,
			data: {
					'id' : id,
					'data' : JSON.stringify(arrayData)
				  },
			type: 'POST',
			dataType : "json",
			cache: false,
			//async: false,
			error: function(json){
				alert("loi"+JSON.stringify(json));
			},
			success : function(json) {
				//alert(JSON.stringify(json));
				if(json.message.flag == true){
					//alert('Danh mục đã được cập nhật thành công');	
					modalAlert(msg);
					row.closest('tr').find(".add, .edit").toggle();
					
					obj.removeClass('alert-error');						
					setDataRow(row.closest('tr'));	
				}
				else{
					modalAlert(json.message.errorMessage);
					obj.addClass('alert-error');
					error = true;
				}
			}
		});
		if(!error) {	
			return true;
		}
		else{
			return false;
		}	
	}
	else{
		return false;	
	}	
}

function deleteData (table, row, url, msg){	
	var id = getId(row); 

	if(id!=""){			
		// ajax delete data			
		$.ajax({
			url: url,
			data: {
					'id' : id
				  },
			type: 'POST',
			dataType : "json",
			cache: false,
			//async: false,
			error: function(json){
				alert(JSON.stringify(json))
			},
			success : function(json) {
				if(json!= null){
					//alert('Danh mục đã được xóa thành công.');
					// delete row
					modalAlert(msg);
					//alert('Danh mục đã được xóa thành công.');
					//row.parents("tr").remove();	
					try{
						table
						.row( row.closest('tr') )
						.remove()
						.draw();	
					}
					catch(err){
						row.closest('tr').remove();
					}
					
				}
			}
		});	
	}
	else{
		row.closest('tr').remove();
	}	
}

function checkSession (){			
	url = $("#ULocal").val()+'refreshSession/';
	$.ajax({
		url: url,		
		type: 'POST',		
		cache: false,
		success : function(json) {
			if(json!=""){
				alert(json);
				window.location.replace($("#ULocal").val());
			}
		}
	});		
}

function getDatetimeNow(){
    if($('#sys_date').val() && $('#sys_date').formance('validate_dd_mm_yyyy')){
		return $('#sys_date').val();
	}
	var currentdate = new Date();
    var dd = currentdate.getDate().toString();
    if(dd.length == 1){
        day = "0" + dd;
    }else{
    	day = dd;
    }

    var month = (currentdate.getMonth() + 1).toString();
    var mm =  month.length;

    if(mm == 1){
        month = "0" + month;
    }else{
    	month = month;
    }

    return day + "/" + month + "/" + currentdate.getFullYear();	
}

function getDatetime(minusDay = 0){
    var currentdate;
	if($('#sys_date').val() && $('#sys_date').formance('validate_dd_mm_yyyy')){
		var dateParts = $('#sys_date').val().split("/");
		currentdate =  new Date(+dateParts[2], dateParts[1] - 1, +dateParts[0]);
	}
	else {	
		currentdate = new Date();
	}
	currentdate.setDate(currentdate.getDate() - minusDay);

    var dd = currentdate.getDate().toString();
    if(dd.length == 1){
        day = "0" + dd;
    }else{
    	day = dd;
    }


    var month = (currentdate.getMonth() + 1).toString();
    var mm =  month.length;

    if(mm == 1){
        month = "0" + month;
    }else{
    	month = month;
    }

    return day + "/" + month + "/" + currentdate.getFullYear();
}

function getDateFirstMonth(){
    var currentdate;
	if($('#sys_date').val() && $('#sys_date').formance('validate_dd_mm_yyyy')){
		var dateParts = $('#sys_date').val().split("/");
		currentdate =  new Date(+dateParts[2], dateParts[1] - 1, +dateParts[0]);
	}
	else {	
		currentdate = new Date();
	}
    var dd = currentdate.getDate().toString();
    
	day = "01";

    var month = (currentdate.getMonth() + 1).toString();
    var mm =  month.length;

    if(mm == 1){
        month = "0" + month;
    }else{
    	month = month;
    }

    return day + "/" + month + "/" + currentdate.getFullYear();
}

function getStringDateNow(){
    var currentdate;
	if($('#sys_date').val() && $('#sys_date').formance('validate_dd_mm_yyyy')){
		var dateParts = $('#sys_date').val().split("/");
		currentdate =  new Date(+dateParts[2], dateParts[1] - 1, +dateParts[0]);
	}
	else {	
		currentdate = new Date();
	}
    var dd = currentdate.getDate().toString();
    if(dd.length == 1){
        day = "0" + dd;
    }else{
    	day = dd;
    }


    var month = (currentdate.getMonth() + 1).toString();
    var mm =  month.length;

    if(mm == 1){
        month = "0" + month;
    }else{
    	month = month;
    }

    return day + month + currentdate.getFullYear();
}

function getStringDateNow1(){
    var currentdate;
	if($('#sys_date').val() && $('#sys_date').formance('validate_dd_mm_yyyy')){
		var dateParts = $('#sys_date').val().split("/");
		currentdate =  new Date(+dateParts[2], dateParts[1] - 1, +dateParts[0]);
	}
	else {	
		currentdate = new Date();
	}
    var dd = currentdate.getDate().toString();
    if(dd.length == 1){
        day = "0" + dd;
    }else{
    	day = dd;
    }

    var month = (currentdate.getMonth() + 1).toString();
    var mm =  month.length;

    if(mm == 1){
        month = "0" + month;
    }else{
    	month = month;
    }

    return "Ngày " + day + " tháng " + month + " năm " + currentdate.getFullYear();
}

function CompareDate(date1, date2) {  
	var arrDate1 = date1.split("/");
	var arrDate2 = date2.split("/");

	//Note: 00 is month i.e. January  
	var dateOne = new Date(arrDate1[2], arrDate1[1], arrDate1[0]); //Year, Month, Date  
	var dateTwo = new Date(arrDate2[2], arrDate2[1], arrDate2[0]); //Year, Month, Date  
	if (dateOne > dateTwo) {  
		return 1;  
	}
	if (dateOne < dateTwo) { 
		return 2;
	}
	return 0;
}

