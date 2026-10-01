// Author : Tran Viet Khoi
// Date : 05/04/2006
// Chuyen focus trong trang khi nhan phim enter

// JavaScript Document
addEvent(window, "load", initTabIndex);

var msg = "Trường này không được rổng";
var selectFlag = 0;

if (document.layers) document.captureEvents(Event.KEYPRESS); // needed if you wish to cancel the key
document.onkeypress = keyHandler;	
//document.onMouseDown = focusHandler;
//document.onfocus = focusHandler;// Mozilla 

function keyHandler(e) {		
	if (document.layers){		
		var obj = e.srcElement ;
		var key = e.which;
	}
	else{					
		if (!e) { // IE
			var obj = window.event.srcElement;
			var key = window.event.keyCode;
		}
		else { //firefox
			var obj = e.target;
			var key = e.which;
		}				
	}
	
	return moveFocus(obj,key);			
}

function focusHandler(e) {			
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
	return focusH(obj,e);
}

function focusH(obj,e){
	if(obj.title == "disabled"){
		moveFocus(obj,13);
	}
	
	// dong select
	if(selectFlag==1 && obj.tagName.toLowerCase() != "select"){
		lostFocusSelect();
		selectFlag = 0;
	}	
	
	if(obj.getAttribute("onFormForcus") != null){
		var f = obj.getAttribute("onFormForcus");
		eval(f);
	}
	// tao select
	if(obj.title == "Select"){
		statusSelect(obj);
		selectFlag = 1;
	}
	
	if(obj.title == "Calendar"){		
		Calendar.setup({
			inputField	: obj.id,
			ifFormat	: "%d/%m/%Y", 
			button		: obj.id,
			align		: "Bl",    
			singleClick	: true,
			onSelect	: changeHandler(e)
		});
		
		obj.onclick();	
	}
	
	if(obj.type == "text" || obj.type == "password" || obj.type == "textarea"){
		obj.select();	
	}		
	return true;		
}

function changeHandler(e){
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
	var name = obj.name
	var number = "";
	for(i=name.length*1;i>0;i--){
		if(!isNaN(name.substr(i-name.length*1-1,1))){
				number = name.substr(i-name.length*1-1,1)+number;
		}
		else{
			break;
		}
	}
	//alert(number);
	//alert(name.substr(-2,1));
	// cap nhat su thay doi 	
	var change = document.getElementById("edit"+number);	
	if(change) change.value = 1;
	//alert(change.value);
	//alert("change"+name.substr(-1));
}

function moveFocus(obj,key){
	var form = obj.form;	
	if(form != undefined){				
		var next = obj.tabIndex;		
		if (key == 13)
		{	//alert(obj.tabIndex);
			// kiem tra du lieu truoc khi chuyen toi doi tuong khac			
			if(!checkValueBeforeFocus(obj)){
				return false;
			}
			
			if(obj.type == "button" || obj.type == "submit" || obj.type == "reset" || obj.type == "image"){					
				if(obj.getAttribute("onFormForcus") != null){
					var f = obj.getAttribute("onFormForcus");
					eval(f);
				}
				return true;			
			}
			//alert("3");
			if ( next >= form.length ){										
				form = getForm(form.name);
				next = 0;
			}
			//alert("4");
			var obj1 = form[next];
			
			if(obj1.name == obj.name) {
				next++;
				if ( next >= form.length ){										
					form = getForm(form.name);
					next = 0;
				}
				obj1 = form[next];
			}
			
			while(obj1.type == "hidden" || (obj1.readOnly && obj1.title != "Calendar")){					
				//next = obj1.tabIndex;
				next++;
				if ( next >= form.length ){										
					form = getForm(form.name);
					next = 0;
				}
				obj1 = form[next];		
			}
			
			// giai quyet truong hop select thay the cho text box
			if(obj1.name == obj.name) {
				next++;
				if ( next >= form.length ){										
					form = getForm(form.name);
					next = 0;
				}
				obj1 = form[next];
				dung = 0;				
				while((obj1.type == "hidden" || (obj1.readOnly && obj1.title != "Calendar")) && dung <20){					
					next++;
					if ( next >= form.length ){										
						form = getForm(form.name);
						next = 0;
					}
					obj1 = form[next];	
					dung++;					
				}
			}		
			
			//alert("5"+ obj1.name);
			try {
				/*obj1.focus();
				if(obj1.type == "text" || obj1.type == "password" || obj1.type == "textarea"){
					obj1.select();
				}*/		
				//lert("6");
				focusH(obj1);
			}
			catch (e){}
			return false;
			
		}
		else {			
			return checkValue(obj,key);
		}	
	}		
	return false;
}

function checkValue(objText,key){	
	if(key == 8 || key == 0) return true;
	if(objText.type == "text"){
		if(objText.title == "Number") return checkNumber(objText,key);
		if(objText.title == "Interger") return checkInterger(objText,key);
		if(objText.title == "Long") return checkLong(objText,key);			
	}
}

function checkValueBeforeFocus(objText){	
	if(objText.type == "text" || objText.type == "password" || objText.type == "textarea"){
		// kiem tra email
		if(objText.title == "Email" && !isEmail(objText.value)){
			createMsg("Email không hợp lệ");
			objText.select();
			return false;
		}
		// kiem tra dieu kien
		var verify = objText.getAttribute("verify");		
		if(verify != null){					
			if(!eval("objText.value" + verify )){
				objText.select();
				return false;
			}
		}
		// kiem tra cac truong co yeu cau kiem tra rong
		if(objText.getAttribute("noEmpty") != null && objText.value == "") {
			// tao message thong bao
			if(objText.getAttribute("msg") != null) msg = objText.getAttribute("msg");			
			createMsg(msg);
			objText.select();
			return false;
		}
	}	
	
	return true;
}

function checkNumber(objText,key){			
	if(key < 48 || key >57){ //kiem tra so 		
		return false;
	}	
	return true;
}

function checkInterger(objText,key){	
	if(key < 48 || key >57 || (key == 48 && objText.value == "")){ //kiem tra so nguyen		
		return false;
	}	
	return true;
}

function checkLong(objText,key){	
	if((key < 48 || key >57) && key != 46){	// kiem tra so thuc
		return false;
	}	
	return true;
}

function isEmail(strValue)
{
  var objRE = /^[\w-\.\']{1,}\@([\da-zA-Z-]{1,}\.){1,}[\da-zA-Z-]{2,}$/;

  return (strValue == '' || objRE.test(strValue));
}

function roundNumber(num, dec) {
	var result = Math.round(num*Math.pow(10,dec))/Math.pow(10,dec);
	return result;
}

function checkForm(objForm)
{
	var length = objForm.length - 1;		
	var i = 0;
	
	while(i < length){				
		var objText = objForm[i];
		if(objText.type == "text"){
			// kiem tra gia tri email
			if(objText.title == "Email" && !isEmail(objText.value)){
				objText.focus();
				objText.select();
				createMsg("Email không hợp lệ");
				return false;
			}
			
			if(objText.getAttribute("noEmpty") != null && objText.value == "") {
				// tao message thong bao
				if(objText.getAttribute("msg") != null) msg = objText.getAttribute("msg");			
				objText.focus();
				objText.select();
				createMsg(msg);				
				return false;
			}
		}
		i++;
	}	
	return true;
}

function createMsg(strMsg){
	alert(strMsg);
}

function getForm(formName){
	var cform = 0;
	var arrForm = document.getElementsByTagName('form');
	while(arrForm[cform].name != formName && cform < arrForm.length) {			
		cform++;
	}
	cform++;
	if(cform >= arrForm.length){
		return arrForm[0];
	}
	return arrForm[cform];
}

function initTabIndex(){
	var cform = 0;
	var arrForm = document.getElementsByTagName('form');
	while(cform < arrForm.length) {
		var objForm = arrForm[cform];
		//objForm.onsubmit = function(){return checkForm(this);}
		addEvent(objForm, "submit", function(){return checkForm(this);});
		
		var cs = objForm.elements;			
		var l = cs.length;		
		var i = l - 1;					
		var tabIndex =  i + 1;				
		
		while(i >= 0){				
			if(cs[i].name != undefined) {
				cs[i].tabIndex = tabIndex;	
				addEvent(cs[i], "click", focusHandler);
				//cs[i].onfocus = focusHandler; 				
				addEvent(cs[i], "change", changeHandler);
				
				tabIndex = i;					
			}
			i--;
		}
		
		cform++;
	}	
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