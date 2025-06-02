<html>
<head>
<title>prompt test</title>
<script type="text/javascript">

function ae_alert(text,title)
{
	ae_prompt(null,'HIDDEN%'+text+'%','OK%0','',title);
}

function ae_confirm(callback,text,id,title)
{
	if (!callback) {
		callback=hw2;
	}
	ae_prompt(callback,'HIDDEN%'+text+'%','OK%1|Cancel%0',id,title);
}

function ae_confirm_yes_no(callback,text,id,title)
{
	if (!callback) {
		callback=hw2;
	}
	ae_prompt(callback,'HIDDEN%'+text+'%','Yes%1|No%0',id,title);
}

function hw1(id,title)
{
	//ae_prompt(hw2,'text%What is your name ?%Anonymous|text%What is your adress ?%Nowhere|hidden%%dolly|checkbox%select something%checked','OK%1|Cancel%0|don\'t know%2',id,title);
	ae_prompt(hw2,'HIDDEN%This is an alert!!<br>You can\'t do anything here!!%','OK%0',id);
}

function hw2(returncode,id,value)
{
	var hello = document.getElementById('hello');
	hello.innerHTML = '<h1>'+ id + '<br>' + value + '<br>' + returncode + '!</h1>';
}
// ae_prompt function sources
var ae_cb = null;

function ae$(a)
{ return document.getElementById(a); }

function ae_prompt(callback, fields, btns, id, title)
{
	if (!title) {
		title=document.title;
	}
	if (!id) {
		id='current_dialog_id';
	}
	var ovrl=ovrl=ae$("aep_ovrl");
	var win=ae$("aep_win");
	if (!ovrl) {
		ovrl=document.createElement('div');
		ovrl.id="aep_ovrl";
		document.body.appendChild(ovrl);
	}
	ovrl.style.display='none';
	ovrl.innerHTML=' ';
	if (!win) {
		win=document.createElement('div');
		win.id="aep_win";
		document.body.appendChild(win);
	}
	win.style.display='none';
	win.innerHTML='<input type="hidden" id="current_dialog_id" value="'+id+'"/><image class="top_window" src="./nav1.jpg"/><div id="aep_t" class="top_window"></div><div id="aep_w"><span id="aep_prompt"></span><div id="aep_buttons"></div></div>';
	ae$('aep_t').innerHTML='<table width="100%" height="14" cellpadding="0" cellspacing="0"><tr><td align="left"><div style="float:left;padding-left:10px;"> '+title+'</div></td></tr></table>';
	var btns_array=btns.split('|');
	var fields_array=fields.split('|');
	ae_cb = callback;
	//ae$('aep_t').innerHTML = title;
	ae$('aep_prompt').innerHTML='<br/>';
	for (i=0; i<fields_array.length; i++) {
		field_array=fields_array[i].split('%');
		var span=document.createElement('span');
		if ((field_array[0]!='hidden') && (field_array[0]!='checkbox')) {
			span.innerHTML=field_array[1]=field_array[1]+'<br/>';
		}
		if (field_array[0]=='checkbox') {
			var checked=field_array[2]?'checked':'';
			span.innerHTML+='<input type="'+field_array[0]+'" id="id_jspopup_input_'+i+'" value="'+field_array[1]+'" '+checked+'/> '+field_array[1];
		} else {
			span.innerHTML+='<input type="'+field_array[0]+'" id="id_jspopup_input_'+i+'" value="'+field_array[2]+'" class="aep_text"/>';
		}
		if (field_array[0]!='hidden') {
			span.innerHTML+='<br/>';
		}
		ae$('aep_prompt').appendChild(span);
	}
	ae$('aep_prompt').innerHTML+='<br/>';
	ae$('aep_buttons').innerHTML='';
	for (i=0; i<btns_array.length; i++) {
		var btn_array=btns_array[i].split('%');
		ae$('aep_buttons').innerHTML+='<input type="button" id="id_'+btn_array[0]+'" value="'+btn_array[0]+'" onclick="ae_clk(\''+btn_array[1]+'\');" />';
	}
	ovrl.style.display = win.style.display = '';
}

function ae_clk(m)
{
	ae$('aep_ovrl').style.display = ae$('aep_win').style.display = 'none';
	var i=0;
	var id=ae$('current_dialog_id').value;
	var tb=ae$('id_jspopup_input_'+i);
	var val=addvalue(tb,'');
	while (tb) {
		i++;
		tb=ae$('id_jspopup_input_'+i);
		if (!tb) {
			break;
		}
		val+=addvalue(tb,'|');
	}
	ae_cb(m,id,val);
}

function addvalue(elem,separator)
{
	var val='';
	if (elem.type=='checkbox') {
		val+=separator+(elem.checked?'true':'false');
	} else {
		val+=separator+elem.value;
	}
	return val;
}
// ae_prompt function sources
</script>
<!-- CSS styles for ae_prompt function -->
<style type="text/css">
#aep_ovrl {
	position: fixed;
	width: 100%;
	height: 100%;
	z-index: 19000;
	top: 0px;
	left: 0px;
	background-color: #000;
	filter:alpha(opacity=60);
	-moz-opacity: 0.6;
	opacity: 0.6;
}
#aep_win {
	position: fixed;
	top: 20%;
	left: 50%;
	width: 400px;
	margin-left: -200px;
	background: #eee;
	z-index: 19002;
	border: 4px solid #336699;
	text-align: left;
}
#aep_w {
	position:relative;
	width:100%;
	margin-top:23px;
	border: none;
	background-color: #eee;
	text-align: center;
}
.top_window {
	display:block;
	overflow:none;
	position:absolute;
	top:0px;
	left:0px;
	height:23px; !important;
	min-height:23px;
	max-height:23px;
	width:100%;
	font-family: Tahoma, Geneva, Verdana, Arial, Helvetica;
	font-size:9pt;
	white-space: nowrap;
	text-shadow: #dddddd 0px 1px 0px;
	font-weight:600;
	border-bottom:1px solid #666666;
	border-top:1px solid #dddddd;
}
.aep_text {width: 90%;}
#aep_w span {font-family: Arial, sans-serif; font-size: 10pt;}
#aep_w div {text-align: right; margin-top: 5px;}
</style>
</head>
<body>
<div id="hello">
Hi! Set your name.
</div>
<?php
echo "<span id=\"get_name_adress\" style=\"text-decoration: underline; color: blue; cursor: pointer;\" onClick=\"ae_confirm_yes_no(new Function('returncode','if (returncode!=0){alert(1);}'),'Do you reallt want to continue?');\">Click</span>";
?>
</body></html>