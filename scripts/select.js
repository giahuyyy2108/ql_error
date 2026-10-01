// JavaScript Document
addEvent(window, "load", initSelectBox);

var arrSelectBox = new Array();
var arrSelect = new Array();
var currentFocus = "";
var currentFocusID = "";
var arrFunctionName = new Array();

//document.onclick = focusSelectHandler;

function focusSelectHandler(e) {
	if (document.layers){
		var obj = e.srcElement ;
	}
	else{
		if (!e) { // IE
			var obj = window.event.srcElement;
		}
		else { //firefox
			var obj = e.target;
		}
	}
	if (obj.tagName.toLowerCase() != "select") {
		statusSelect(obj);
	}
	/*if(obj.type == "text" || obj.type == "password" || obj.type == "textarea"){
		obj.select();
	}*/
}

function statusSelect(obj){		
	var objParen = getParent(obj,"td");	
	var tmp = currentFocus;//document.getElementById("currentFocus").value;
	
	if(tmp != ""){
		lostFocusSelect();
	}	
	if(objParen != null && objParen.getAttribute("fieldName") != ""){					
		handleSelect(objParen);
	}		
}

function lostFocusSelect(){
	//var objCurrentFocus = document.getElementById("currentFocus");
	//var objCurrentFocusID = document.getElementById("currentFocusID");
	var name = currentFocus;//objCurrentFocus.value;	
	var id = currentFocusID;//objCurrentFocusID.value;
	
	if(name != ""){
		// lay du lieu
		var objSelectName = document.getElementById(name);		
		var objSelectId = null;
		if(id != "" && id != 0){
			objSelectId = document.getElementById(id);
		}
		
		var objParenNode = getParent(objSelectName,"td");		
		var objDelete = document.getElementById("div" + name);		
		var objSelect = getChild(objDelete, "select");
		
		if(objSelect == null) return true;
		
		var i = objSelect.selectedIndex;		
		if(i != -1){						
			objSelectName.value = objSelect.options[i].text;			
			if(objSelectId != null) objSelectId.value = objSelect.value;
		}	
		// chay ham kem theo
		var objParen = getParent(objParenNode,"td");
		if(objParen != null && objParen.getAttribute("fieldName") != ""){					
			if(arrFunctionName[objParen.getAttribute("fieldName")]!= undefined){
				if(objParen.getAttribute("fieldParameter") != null){
					eval(arrFunctionName[objParen.getAttribute("fieldName")]+"("+objParen.getAttribute("fieldParameter")+")");
				}
				else{
					eval(arrFunctionName[objParen.getAttribute("fieldName")]+"()");
				}
			}				
		}	
		
		// xoa select		
		objParenNode.removeChild(objDelete);
		// thiet lap lai trang thai ban dau cua HT		
		objSelectName.style.display = "block";		
		//objCurrentFocus.value = "";	
		//objCurrentFocusID.value = ""; 	
		currentFocus = "";
		currentFocusID = "";		
	}
}

function handleSelect(objParent){			
	if(objParent.tagName.toLowerCase() != "td") return true;
	if(objParent.getAttribute("fieldName") != ""){
		// tim doi tuong textarea hoac text trong cell co ten trong fieldName
		var objTextBox = getTextBox(objParent , objParent.getAttribute("fieldName"));		
		var objTextBoxId = getTextBox(objParent , objParent.getAttribute("fieldId"));
		
		if(objTextBox == null) return true;
		var txtName = objTextBox.value;
		var txtId = "";
		
		var tmpcurrentFocus = (objTextBox.name == "" ) ? objTextBox.id : objTextBox.name;
		
		var tmpcurrentFocusId = "";
		if(objTextBoxId != null){
			tmpcurrentFocusId = (objTextBoxId.name == "" ) ? objTextBoxId.id : objTextBoxId.name;
			txtId = objTextBoxId.value;
		}
		
		objTextBox.style.display = "none";
		var div = "<div id='div" + tmpcurrentFocus + "'>" + getSelectBox(objParent.getAttribute("fieldName")) + "</div>";
		//alert(objTextBox.onfocus);
		objParent.innerHTML = objParent.innerHTML + div;		
		objTextBox1 = document.getElementById(objTextBox.id);	
		//objTextBox1.onclick = objTextBox.onclick;
		addEvent(objTextBox1, "click", focusHandler);
		//alert(objTextBox.onclick);
		currentFocus = tmpcurrentFocus;		
		currentFocusID = tmpcurrentFocusId;	
		
		if(tmpcurrentFocusId != ""){
			document.getElementById("select_" + objParent.getAttribute("fieldName")).value = txtId;
		}
		else{			
			document.getElementById("select_" + objParent.getAttribute("fieldName")).value = txtName;						
		}
		document.getElementById("select_" + objParent.getAttribute("fieldName")).focus();
		document.getElementById("select_" + objParent.getAttribute("fieldName")).tabIndex = objTextBox.tabIndex;
		document.getElementById("select_" + objParent.getAttribute("fieldName")).onchange = changeHandler;
		document.getElementById("select_" + objParent.getAttribute("fieldName")).name = "select_" + objTextBox.name;
		//alert(objTextBox.tabIndex);
		//document.getElementById("select_" + objParent.getAttribute("fieldName")).onfocus = objTextBox.onfocus;
	}
}

function createSelect(array,fieldName,functionName){
	var tmp;
	tmp = '<select name="select_' + fieldName + '" id="select_' + fieldName + '" style="width:100%;height:18;font-size:12px">';	
	for( keyVar in array ){
		tmp += '<option value="' + keyVar + '">' + array[keyVar] + '</option>';		
	}
	tmp += '</select>';
	arrSelectBox[fieldName] = tmp;
	arrSelect[fieldName] = array;
	arrFunctionName[fieldName] = functionName ;
}

function getSelectBox(fieldName){		
	return arrSelectBox[fieldName];
}

function getTextBox(obj , fieldName){
	if(obj == null || fieldName == null || fieldName == "") return null;
	
	var cs = obj.childNodes;	
	
	var name = fieldName.toLowerCase();
	var l = cs.length;
	var objTextBox = null;
	for (var i = 0; i < l; i++) {		
		if(cs[i].name!= undefined){
			var tmp = cs[i].name.toLowerCase();
			var tmp1 = cs[i].id.toLowerCase();		
			if(cs[i].nodeType == 1 && (tmp.indexOf(name)!=-1 || tmp1.indexOf(name)!=-1)){
				var objTextBox = cs[i];
				i = l;
			}
		}
	}
	return objTextBox;
}

function getChild(objParenNode,tagName){
	if(objParenNode == null) return null;
	
	var cs = objParenNode.childNodes;	
	var l = cs.length;
	var objSelect = null;
	for (var i = 0; i < l; i++) {		
		if(cs[i].nodeType == 1 && (cs[i].tagName.toLowerCase() == tagName.toLowerCase())){
			var objSelect = cs[i];
			i = l;			
		}
	}
	return objSelect;
}

function getParent(el, pTagName) {
	if (el == null) return null;	
	var i=0 ;
	while(el.nodeType == 1 && (el.tagName.toLowerCase() != pTagName.toLowerCase()) && i < 20){
		el = el.parentNode;
		i++;
	}
	if (i==20) return null;
	if(el.nodeType != 1) return null;	
	return el;
}

function initSelectBox(){
	var cform = 0;
	var arrForm = document.getElementsByTagName('form');
	var objForm = null;
	while(cform < arrForm.length) {
		objForm = arrForm[cform];
		
		var cs = objForm.elements;			
		var l = cs.length;		
		var i = 0;
		
		while(i < l){			
			//cs[i].onfocus = focusSelectHandler;
			if(cs[i].type == "text" || cs[i].type == "textarea" || cs[i].type == "hidden"){
				var objParen = getParent(cs[i],"td");
				if(objParen != null) {
					var fieldName = objParen.getAttribute("fieldName");
					var fieldId = objParen.getAttribute("fieldId");
					var name = cs[i].name;
					if(fieldName != "" && fieldId != "" && name.indexOf(fieldId) != -1){						
						var c = objParen.childNodes;	
						var d = c.length;						
						for (var j = 0; j < d; j++) {
							if(c[j].nodeType == 1 && (c[j].name.indexOf(fieldName) !=-1)){
								if(arrSelect[fieldName][cs[i].value] != undefined) {
									c[j].value = arrSelect[fieldName][cs[i].value];
								}
								j = d;
							}
						}						
					}					
				}
			}
			i++;
		}		
		cform++;
	}
	//document.write("<input type='hidden' name='currentFocus' id='currentFocus' value=''>");
	//document.write("<input type='hidden' name='currentFocusID' id='currentFocusID' value='0'>");
}

function addEvent(elm, evType, fn, useCapture){
  	if (elm.addEventListener){
    	elm.addEventListener(evType, fn, useCapture);
   		 return true;
  	} else if (elm.attachEvent){
    	var r = elm.attachEvent("on"+evType, fn);
   	 	return r;
  	} else {
    	alert("Handler could not be removed");
  	}  
} 