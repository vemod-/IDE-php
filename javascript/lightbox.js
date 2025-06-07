var win;

var olderBrowser;


if (String.prototype.right==null) String.prototype.right=function(num){ 
      return this.substring(this.length-num);  // pull out right num
}
/*
function closeFrame()
{
    if (document.getElementById('is_submit').value != 0)
    {
        var doc=window.frames['previewframe'].contentDocument;
        if (!doc)
        {
            //doc=window.frames['previewframe'].document;
            doc=document.getElementById('previewframe').contentDocument;
        }
        if (!doc)
        {
            doc=window.frames['previewframe'].document;
        }
        if (!doc)
        {
            doc=document.getElementById('previewframe').document;
        } 
        alert(doc);
        alert(doc.forms[0]);
        doc.forms[0].submit();
    }
    //document.getElementById('borderdiv').style.visibility="hidden";       
    //fadeoutdiv('borderdiv',60);
    //document.getElementById('framediv').style.visibility="hidden";
    //fadeoutdiv('framediv',90);
    hidedivs('framediv',100);
}
*/
function closeFrame() {
    const isSubmit = document.getElementById('is_submit');
    if (isSubmit && isSubmit.value != "0") {
        const previewFrame = document.getElementById('previewframe');

        try {
            const doc = previewFrame.contentDocument || previewFrame.contentWindow.document;
            if (doc && doc.forms.length > 0) {
                doc.forms[0].submit();

                // Vänta tills formuläret laddat klart, sen stäng och ladda om
                previewFrame.onload = function () {
                    hideFrame();
                    window.location.reload();
                };
                return;
            }
        } catch (e) {
            console.error("closeFrame(): Formulär kunde inte skickas.", e);
        }
    }

    // Om inget formulär eller is_submit == 0, stäng bara
    hideFrame();
}

function hideFrame() {
    const borderdiv = document.getElementById('borderdiv');
    const framediv = document.getElementById('framediv');
    if (borderdiv) borderdiv.style.display = "none";
    if (framediv) framediv.style.display = "none";
}

function showwindow(url,inf,title)
{
    var path='';
    if (url.length)
    {
        var path_array=window.location.pathname.split('/');
        for (i=0;i<path_array.length-1;i++)
        {
            path+=path_array[i]+'/';
        }
        path=window.location.protocol + "//" + window.location.host + path+url;
    }
	if (win)
	{
		if (!win.closed)
		{
		    if (url.length>0)
		    {
		    win.document.location.href=path;
            }
            else
            {		
    		win.document.close();
			win.document.write("" + inf + "");
			win.document.close();
			}
		}
		else
		{
		    if (url.length>0)
		    {
			 win = window.open(path, 'popup', 'width = 800, height = 600, resizable=1,scrollbars=1,location=0,status=0,menubar=0,directories=0,toolbar=0,titlebar=0');		    
            }
            else
            {
			 win = window.open("", 'popup', 'width = 800, height = 600, resizable=1,scrollbars=1,location=0,status=0,menubar=0,directories=0,toolbar=0,titlebar=0');
			 win.document.write("" + inf + "");
			 win.document.close();
			} 
		}
	}
	else
	{
     if (url.length>0)
     {        
		win = window.open(path, 'popup', 'width = 800, height = 600, resizable=1,scrollbars=1,location=0,status=0,menubar=0,directories=0,toolbar=0,titlebar=0');     
	   }
	   else
	   {
		win = window.open("", 'popup', 'width = 800, height = 600, resizable=1,scrollbars=1,location=0,status=0,menubar=0,directories=0,toolbar=0,titlebar=0');
		win.document.write("" + inf + "");
		win.document.close();
		}
	}
	if((navigator.userAgent.toLowerCase().indexOf("opera")==-1)&&(navigator.userAgent.toLowerCase().indexOf("safari")==-1))
	{
	   win.document.location.reload();
	}
	win.focus();
}   
   
    function showFrame(url,inf,title,close,is_submit)
    {
        if (olderBrowser)
        {
            showwindow(url,inf,title);
            return;
        }
       
        var borderdiv=document.getElementById('borderdiv');       
        if (!borderdiv)
        {
            borderdiv=document.createElement('div');
            borderdiv.id='borderdiv';
            document.body.appendChild(borderdiv);            
        }              
        borderdiv.onclick=new Function("closeFrame()");        
        var framediv=document.getElementById('framediv');
        if (!framediv)
        {
            framediv=document.createElement('div');
            framediv.id='framediv';
            document.body.appendChild(framediv);
        }
       
        var frameurl=url;
        if (frameurl.length==0)
        {
            frameurl='about:blank';
        }               
        framediv.innerHTML='<div name="closediv" id="closediv" class="globalheader"></div><IFRAME NAME="previewframe" ID="previewframe" frameborder="0" SRC="'+frameurl+'"><div id="alternativediv"></div></IFRAME><input type="hidden" name="is_submit" id="is_submit"/>';
        
        var previewframe=window.frames['previewframe'];        
                
        var alternativediv=document.getElementById('alternativediv');
        if (alternativediv)
        {
            alternativediv.innerHTML=inf;
        }
        document.getElementById('closediv').innerHTML='<div class="inside_menu_text" style="text-indent:8px;"> '+title+'</div><div class="inside_menu" style="float:right;"><a href="#" class="btn" onClick="closeFrame();"/>'+close+'</a><div>';            
        //document.getElementById('borderdiv').style.visibility="visible";
        //fadeindiv('borderdiv',60);
        //fadeindiv('framediv',90);
            showdivs('framediv',100);
        //document.getElementById('framediv').style.visibility="visible";
        document.getElementById('borderdiv').style.position="absolute";
        document.getElementById('borderdiv').style.position="fixed"; 
        document.getElementById('is_submit').value=is_submit ? 1 : 0;              
        if (previewframe)
        {
            if (url.length==0)
            {
                previewframe.document.open();
                previewframe.document.write(""+inf+"");
                previewframe.document.close();
                if((navigator.userAgent.toLowerCase().indexOf("opera")==-1)&&(navigator.userAgent.toLowerCase().indexOf("safari")==-1))
                {
                    previewframe.document.location.reload();
                }                    
            }
        }
    }

    olderBrowser=true;
        
function ae_alert(text,title)
{
	ae_prompt(null,'hidden%¤%'+text+'%¤%','OK%¤%1','',title);
}

function ae_confirm(callback,text,id,title)
{
	if (!callback) {
		callback=hw2;
	}
	ae_prompt(callback,'hidden%¤%'+text+'%¤%','Yes%¤%1|¤|No%¤%0',id,title);
}

function ae_confirm_yes_no(callback,text,id,title)
{
	if (!callback) {
		callback=hw2;
	}
	ae_prompt(callback,'hidden%¤%'+text+'%¤%','Yes%¤%1|¤|No%¤%0',id,title);
}

function hw1(id,title)
{
	//ae_prompt(hw2,'text%What is your name ?%Anonymous|text%What is your adress ?%Nowhere|hidden%%dolly|checkbox%select something%checked','OK%1|Cancel%0|don\'t know%2',id,title);
	ae_prompt(hw2,'hidden%¤%This is an alert!!<br>You can\'t do anything here!!%¤%','OK%¤%0',id);
}

function hw2(returncode,id,value)
{
	var hello = document.getElementById('hello');
	hello.innerHTML = '<h1>'+ id + '<br>' + value + '<br>' + returncode + '!</h1>';
}
// ae_prompt function sources 
var ae_cb = null;

function ae$(a)
{
    return document.getElementById(a); 
}

function button_keycheck(e)
{
    var evt = e ? e : window.event;    
	var c =evt.which ? evt.which : evt.keyCode;
	//alert(c);
    if (c==13)
    {       
        var i=0;    
        var button1=null;
        while (ae$('button_id_'+i))
        {
            if (ae$('button_id_'+i).style.borderStyle.toLowerCase().indexOf("dotted") != -1)
            {
                button1=ae$('button_id_'+i);
            }
            i++;
        }
        if (button1)
        {
            //button1.click();
            ae_clk(button1.name.right(1));
        }
        else if (document.getElementsByName('button_name_1')[0])
        {
            //document.getElementsByName('button_name_1')[0].click();
            ae_clk(1);
        }        
        else
        {
            //ae$('button_id_0').click();
            ae_clk(ae$('button_id_0').name.right(1));            
        }
        return false;
    }
    if (c==27)    
    {   
        if (document.getElementsByName('button_name_0')[0])
        {
            //document.getElementsByName('button_name_0')[0].click();
            ae_clk(0);            
        }
        else
        {
            //ae$('button_id_0').click();
            ae_clk(ae$('button_id_0').name.right(1));        
        }
        return false;            
    }   
    if ((c==37) || (c==39))
    {
        var btns_array=new Array();
        var i=0;
        var button1=-1;
        while (ae$('button_id_'+i))
        {
            btns_array[i]=ae$('button_id_'+i);
            if (ae$('button_id_'+i).style.borderStyle.toLowerCase().indexOf("dotted") != -1)
            {
                button1=i;
            }
            i++;
        }
        if (btns_array.length<2)
        {
            return true;
        }
        //btns_array[button1].name="button_name_none";
        btns_array[button1].style.borderStyle="solid";        
        btns_array[button1].style.borderColor="transparent";
        if (c==37)
        {
            button1--;
            if (button1==-1)
            {
                button1=btns_array.length-1;
            }
        }
        if (c==39)
        {
            button1++;
            if (button1==btns_array.length)
            {
                button1=0;
            }
        }        
        //btns_array[button1].name="button_name_1";
        btns_array[button1].style.borderStyle="dotted";        
        btns_array[button1].style.borderColor="#000";        
    }
}

function ae_prompt(callback, fields, btns, id, title)
{
    if (document.getElementById('borderdiv'))
    {
        if (document.getElementById('borderdiv').style.visibility=='visible')
        {
            setTimeout('ae_prompt('+callback+',"'+fields+'","'+btns+'","'+id+'","'+title+'")',100);  
            return;      
        }
    }
	if ((!title) || (typeof(title)=='undefined') || (title=='undefined')) {
		title=document.title;
	}
	if ((!id) || (typeof(id)=='undefined') || (id=='undefined')) {
		id='current_dialog_id';
	}
	var ovrl=ae$("borderdiv");
	var ae_win=ae$("aep_win");
	if (!ovrl) {
		ovrl=document.createElement('div');
		ovrl.id="borderdiv";
		document.body.appendChild(ovrl);
	}
	ovrl.onclick=new Function("return");	
	//ovrl.style.display='none';
	//ovrl.innerHTML='&nbsp;';
	if (!ae_win) {
		ae_win=document.createElement('div');
		ae_win.id="aep_win";
		ae_win.style.paddingBottom='6px';
		if (document.forms[0])
		{
		  document.forms[0].appendChild(ae_win);
		}
		else
		{
		  document.body.appendChild(ae_win);        
        }
	}
	ae_win.innerHTML='<input type="hidden" id="current_dialog_id" value="'+id+'"/><div class="globalheader" id="aep_t"></div><div id="aep_w"><span id="aep_prompt"></span><div id="aep_buttons" style="padding-right:8px;"></div></div>';
	ae$('aep_t').innerHTML='<div class="inside_menu_text" style="text-indent:8px;">'+title+'</div>';
	var btns_array=btns.split('|¤|');
	var fields_array=fields.split('|¤|');
	ae_cb = callback;
	//ae$('aep_t').innerHTML = title;
	ae$('aep_prompt').innerHTML='<br/>';
	for (i=0; i<fields_array.length; i++) {
		field_array=fields_array[i].split('%¤%');
		var span=document.createElement('span');
		var thistype=field_array[0].toLowerCase();
		if (thistype!='checkbox') {
			span.innerHTML=field_array[1]+'<br/>';
		}
		if (thistype=='checkbox') {
			var checked=field_array[2]?'checked':'';
			span.innerHTML+='<input type="checkbox" id="id_jspopup_input_'+i+'" value="'+field_array[1]+'" '+checked+' onkeydown="return button_keycheck(event);"/>&nbsp;'+field_array[1];
		} 
        else if (thistype=='hidden')
        {
            span.innerHTML+='<input type="text" id="id_jspopup_input_'+i+'" value="'+field_array[2]+'" class="aep_hidden_text" onkeydown="return button_keycheck(event);"/>';
        }
        else if (thistype=='textarea') 
        {
			span.innerHTML+='<textarea rows="1" wrap="off" id="id_jspopup_input_'+i+'" class="aep_text" style="overflow:hidden;" onkeydown="return button_keycheck(event);"/>'+field_array[2]+'</textarea>';        
        }
        else if (thistype=='file')
        {
			span.innerHTML+='<input type="'+thistype+'" id="id_jspopup_input_'+i+'" name="uploadedfile" value="'+field_array[2]+'" class="aep_text" onkeydown="return button_keycheck(event);" style="height:auto;"/>';		
		}        
        else
        {
			span.innerHTML+='<input type="'+thistype+'" id="id_jspopup_input_'+i+'" value="'+field_array[2]+'" class="aep_text" onkeydown="return button_keycheck(event);"/>';		
		}
		if (thistype!='hidden') {
			span.innerHTML+='<br/>';
		}
		ae$('aep_prompt').appendChild(span);
	}
	//ae$('aep_prompt').innerHTML+='<br/>';
	ae$('aep_buttons').innerHTML='';
	for (i=btns_array.length-1; i>=0; i--) {
		var btn_array=btns_array[i].split('%¤%');
		ae$('aep_buttons').innerHTML+='<div class="inside_menu" style="float:right;clear:left;"><a href="#" style="border:1px solid transparent;" name="button_name_'+btn_array[1]+'" id="button_id_'+i+'" class="btn" onclick="ae_clk(\''+btn_array[1]+'\');">&nbsp;'+btn_array[0]+'&nbsp;</a></div>';
	}
	var default_button=null;
    if (document.getElementsByName('button_name_1')[0])
    {
        default_button=document.getElementsByName('button_name_1')[0];
    }
    else
    {
        default_button=ae$('button_id_0');        
    }	
    default_button.style.borderStyle="dotted";        
    default_button.style.borderColor="#000";  	
	if (!ae$('id_jspopup_input_0')) 
    {
        ae$('aep_prompt').innerHTML+='<input type="text" class="aep_hidden_text" id="id_jspopup_input_0" onkeydown="return button_keycheck(event);"/>';
    }
	//ovrl.style.visibility =
    //fadeindiv('borderdiv',60); 
    //fadeindiv('aep_win',90);
    showdivs('aep_win',100);
    //ae_win.style.visibility = 'visible';    
    //alert(ae$('aep_win').innerHTML);
}

function ae_clk(m)
{
	//ae$('borderdiv').style.visibility =
    //fadeoutdiv('borderdiv',60); 
    //fadeoutdiv('aep_win',90);
    hidedivs('aep_win',100);
    if (!ae_cb)
    {
        return;
    }
    //ae$('aep_win').style.visibility = 'hidden';
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
		val+=addvalue(tb,'|¤|');
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

var fadeintime=20; // higher is slower
var fadeouttime=20; // higher is slower

function showdivs(id,opac)
{
    //fadeindiv('borderdiv',60);
    //fadeindiv(id,opac);
    document.getElementById('borderdiv').style.visibility='visible';
    document.getElementById(id).style.visibility='visible';
        if (id == 'aep_win')
        {   
            ae$('id_jspopup_input_0').focus();
            ae$('id_jspopup_input_0').select();
        }     
}

function hidedivs(id,opac)
{
    //fadeoutdiv('borderdiv',60);
    //fadeoutdiv(id,opac);
    document.getElementById('borderdiv').style.visibility='hidden';
    document.getElementById(id).style.visibility='hidden';
	    if (id=='framediv')
	    {
	        if (document.getElementById('is_submit').value != 0)
	        {
                //document.forms[0].action.value='do_nothing';
                 //document.forms[0].submit();
                 setTimeout("main_submit('do_nothing');",0);
             }
        }        
}

function fadeindiv(id,targetop) 
{ 
    //document.getElementById(id).style.visibility='hidden';
    fadeinto=setTimeout("divIn(0,'" + id + "',"+targetop+")",0);
} 

function fadeoutdiv(id,targetop) 
{ 
    fadeoutto=setTimeout("divOut("+targetop+",'" + id + "',"+targetop+")",0);
} 

function divIn(opacity, id, targetop) { 
    opacity+=targetop/4;

    var os = document.getElementById(id).style; 
    
    
    //clearTimeout(fadeinto);
    if(opacity<targetop)
    {
	    os.opacity = (opacity / 100); 
	    os.mozOpacity = (opacity / 100); 
    	os.KhtmlOpacity = (opacity / 100); 
	    os.filter = "alpha(opacity=" + opacity + ")";
    	os.visibility="visible";    	
		fadeinto=setTimeout("divIn(" + opacity + ",'" + id + "',"+targetop+")",fadeintime);
    }
    else
    {
    	os.opacity = (targetop / 100); 
	    os.mozOpacity = (targetop / 100); 
	    os.KhtmlOpacity = (targetop / 100); 
    	os.filter = "alpha(opacity=" + targetop + ")";
    	os.visibility="visible";
        if (id == 'aep_win')
        {   
            ae$('id_jspopup_input_0').focus();
            ae$('id_jspopup_input_0').select();
        } 
    }
}

function divOut(opacity, id, targetop) { 
    opacity-=targetop/4;

    var os = document.getElementById(id).style; 
    
    //clearTimeout(fadeoutto);
    if(opacity>0)
    {
	    os.opacity = (opacity / 100); 
	    os.mozOpacity = (opacity / 100); 
    	os.KhtmlOpacity = (opacity / 100); 
	    os.filter = "alpha(opacity=" + opacity + ")";    	
		fadeoutto=setTimeout("divOut(" + opacity + ",'" + id + "',"+targetop+")",fadeouttime);
    }
    else
    {
	    os.opacity = 0; 
	    os.mozOpacity = 0; 
    	os.KhtmlOpacity = 0; 
	    os.filter = "alpha(opacity=0)";    	    	
	    os.visibility='hidden';
	    if (id=='framediv')
	    {
	        if (document.getElementById('is_submit').value != 0)
	        {
                //document.forms[0].action.value='do_nothing';
                 //document.forms[0].submit();
                 main_submit('do_nothing');
             }
        }
    }
} 
       	