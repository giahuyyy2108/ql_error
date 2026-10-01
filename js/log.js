$(document).ready(function() {	
	$(document).on("click", ".delete", function(){
		if(confirm("Bạn có chắc muốn xóa dữ liệu này không !")){			
			deleteData($('table'),$(this),$("#ULocal").val()+'log/deleteLog/','Ngươi dùng đã được xóa thành công.')
		}			
	});
	hiddenButton();
});
function deleteLog(id,msg){
	if(confirm(msg)){
		document.getElementById("id").value=id;
		document.getElementById("cmd").value="201";
		return true;
	}
	return false;
}
function hiddenButton(){	
	if($("#role-log-deleteLog").val() == "false"){
		$(".delete").hide();				
	}
}	
