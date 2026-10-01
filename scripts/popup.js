// JavaScript Document
var ns4=document.layers;
var ie4=document.all;
var ns6=document.getElementById&&!document.all;
var titlebox="";
//drag drop function for NS 4////
/////////////////////////////////

var dragswitch=0;
var nsx;
var nsy;
var nstemp;

var offsetx;
var offsety;

function drag_dropns(name){
	if (!ns4)
		return	temp=eval(name);
	temp.captureEvents(Event.MOUSEDOWN | Event.MOUSEUP);
	temp.onmousedown=gons;
	temp.onmousemove=dragns;
	temp.onmouseup=stopns;
}

function gons(e){
	temp.captureEvents(Event.MOUSEMOVE);
	nsx=e.x;
	nsy=e.y;
}
function dragns(e){
	if (dragswitch==1){
		temp.moveBy(e.x-nsx,e.y-nsy);
		return false;
	}
}

function stopns(){
	temp.releaseEvents(Event.MOUSEMOVE);
}

//drag drop function for ie4+ and NS6////
/////////////////////////////////


function drag_drop(e){
	if (ie4&&dragapproved){
		crossobj.style.left=tempx+event.clientX-offsetx;
		crossobj.style.top=tempy+event.clientY-offsety;
		return false;
	}
	else if (ns6&&dragapproved){
		crossobj.style.left = tempx + e.clientX - offsetx+"px";
		crossobj.style.top = tempy + e.clientY - offsety+"px";
		return false;
	}
}

function initializedrag(e){
	crossobj=ns6? document.getElementById("showimage") : document.all.showimage;
	var firedobj=ns6? e.target : event.srcElement;
	var topelement=ns6? "html" : document.compatMode!="BackCompat"? "documentElement" : "body";
	
	while (firedobj.tagName!=topelement.toUpperCase() && firedobj.id!="dragbar"){
		firedobj=ns6? firedobj.parentNode : firedobj.parentElement;
	}
	
	offsetx=ie4? event.clientX : e.clientX;
	offsety=ie4? event.clientY : e.clientY;	
	
	tempx=parseInt(crossobj.style.left);
	tempy=parseInt(crossobj.style.top);
	
	if (firedobj.id=="dragbar"){		
		dragapproved=true;
		document.onmousemove=drag_drop;
	}
}

document.onmouseup=new Function("dragapproved=false");

////drag drop functions end here//////

function hidebox(){		
	
	if (ie4||ns6)
		crossobj.style.visibility="hidden";
	else if (ns4)
		document.showimage.visibility="hide";

}

function showbox(){		
	var obj=ns6? document.getElementById("test") : document.all.test;	
	
	if (ie4){
		crossobj.style.left = offsetx;
		crossobj.style.top =  offsety;		
	}
	else if (ns6){
		crossobj.style.left =  offsetx + "px";
		crossobj.style.top = offsety + "px";		
	}	
	
	if (ie4||ns6)
		crossobj.style.visibility="";
	else if (ns4)
		document.showimage.visibility="";
}

function openForm(url,name) {
	var left = parseInt((screen.availWidth/2) - (770/2));
    var top = parseInt((screen.availHeight/2) - (300/2));
	var options = "width=770,height=300,toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,copyhistory=no,left=" + left + ",top=" + top + "screenX=" + left + ",screenY=" + top;		
	new_window = window.open(url, name, options);		
	window.self.name = "main";
	new_window.opener = self
	new_window.focus();		
}

function openForm1(url,name,width,height) {
	var left = parseInt((screen.availWidth/2) - (width/2));
    var top = parseInt((screen.availHeight/2) - (height/2));
	var options = "width="+width+",height="+height+",toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,copyhistory=no,left=" + left + ",top=" + top + "screenX=" + left + ",screenY=" + top;		
	new_window = window.open(url, name, options);		
	window.self.name = "main";
	new_window.opener = self
	new_window.focus();		
}