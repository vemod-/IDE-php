var is_opera=(navigator.userAgent.toLowerCase().indexOf("opera")!=-1);
var is_safari=(navigator.userAgent.toLowerCase().indexOf("safari")!=-1);
var is_IE6=((navigator.userAgent.toLowerCase().indexOf('msie 6') != -1) && (navigator.userAgent.toLowerCase().indexOf('msie 7') == -1) && (navigator.userAgent.toLowerCase().indexOf('msie 8') == -1));
var do_scroll_loop=is_opera;

var browser = new Browser();
var savedCode='';

if (!String.prototype.substring_count) String.prototype.substring_count=function(substring){
     var count = 0;
     var idx = 0;

     while ((idx = this.indexOf(substring, idx)) !== -1)
     {
        idx++;
        count++;
     }

     return count;
}

if (!String.prototype.explode) String.prototype.explode=function(separator){
     if (this.indexOf(separator) === -1)
     {
        var retval=new Array();
        retval[0]=this;
        return retval;
     }
     return this.split(separator);
}

String.prototype.toHtmlEntities = function() {
	return this.replace(/[^a-z0-9\.\-\_\s\t]/ig, function(c) {
    		return '&#'+c.charCodeAt(0)+';';
       	});
};

if (do_scroll_loop)
{
    window.onload=function()
    {
        opera_scroll();
    }
}

if (browser.isIE && !is_opera) {
    window.onresize=function()
    {
        reset_width();
    }
}

window.onbeforeunload=function()
{
    if (document.main_form)
    {
        if ((document.main_form.action.value.length === 0) || (document.main_form.action.value === 'options') || (document.main_form.action.value === 'about'))
        {
            if (checkDirty())
            {
                return 'You are about to leave a page with a file that has not have been saved. Your changes will probably be lost and the results may not be predictable!!!!';
            }
        }
    }
}

var show;
var sub_id;

function main_submit(action) {
    var php = new PHP_Serializer();
    var UIdata = {};

    var uidataField = document.getElementById('UIdata');
    if (uidataField && uidataField.value.length) {
        UIdata = php.unserialize(uidataField.value);
    }

    // === CodeMirror-variant ===
    if (editor) {
        // Scrollposition
        var scrollInfo = editor.getScrollInfo();
        UIdata['scrollTop'] = scrollInfo.top;
        UIdata['scrollLeft'] = scrollInfo.left;

        // Markering / cursor
        var sel = editor.listSelections();
        if (sel.length > 0) {
            var anchor = sel[0].anchor;
            var head = sel[0].head;

            UIdata['selAnchorLine'] = anchor.line;
            UIdata['selAnchorCh'] = anchor.ch;
            UIdata['selHeadLine'] = head.line;
            UIdata['selHeadCh'] = head.ch;

            var startPos = editor.indexFromPos(anchor);
            var endPos = editor.indexFromPos(head);
            UIdata['selStart'] = Math.min(startPos, endPos);
            UIdata['selEnd'] = Math.max(startPos, endPos);
        }
        var elem = document.getElementById('code');
		elem.disabled = true;
		var newElem = document.createElement('textarea');
		newElem.name = 'code';
		newElem.style.display = 'none'; // Dölj det
		newElem.value = editor.getValue();;
		document.main_form.appendChild(newElem);
    }

    // === Fallback till vanlig <textarea> ===
    else {
        var elem = document.getElementById('code');
        if (elem) {
            UIdata['scrollTop'] = parseInt(elem.scrollTop, 10) || 0;
            UIdata['scrollLeft'] = parseInt(elem.scrollLeft, 10) || 0;

            if (elem.tagName.toLowerCase() === 'textarea') {
                UIdata['selStart'] = selStart(elem, true);
                UIdata['selEnd'] = selEnd(elem, true);
            }
        }
    }

    // Skriv tillbaka till dolt fält
    if (uidataField) {
        uidataField.value = php.serialize(UIdata);
    }

    // Skicka formuläret
    document.main_form.action.value = action;
    document.main_form.submit();
}

function startdownload()
{
	if (do_scroll_loop)
	{
		opera_scroll();
	}
	document.getElementById('save_as_filename').value='./systemzip/idephp.zip';
	document.getElementById('action').value='set_download';
	document.getElementById('main_form').submit();
}

function UItimeout() {
    var php = new PHP_Serializer();
    var uidataField = document.getElementById('UIdata');
    if (!uidataField || !uidataField.value.length) return;

    var UIdata = php.unserialize(uidataField.value);

    // === CodeMirror-variant ===
    if (editor && typeof editor.scrollTo === 'function') {
        // Återställ scrollposition
        editor.scrollTo(UIdata['scrollLeft'] || 0, UIdata['scrollTop'] || 0);

        // Återställ markering
        if (UIdata['selStart'] !== null && UIdata['selEnd'] !== null) {
            //var startPos = editor.posFromIndex(UIdata['selStart']);
            //var endPos = editor.posFromIndex(UIdata['selEnd']);
            //editor.setSelection(startPos, endPos);
            setSelectionRange(elem, UIdata['selStart'], UIdata['selEnd'], true);
        }

    // === Fallback till vanlig <textarea> ===
    } else {
        var elem = document.getElementById('code');
        if (!elem || elem.tagName.toLowerCase() !== 'textarea') return;

        // Återställ markering
        if (UIdata['selStart'] !== null && UIdata['selEnd'] !== null) {
            setSelectionRange(elem, UIdata['selStart'], UIdata['selEnd'], true);
        }

        // Återställ scrollposition
        elem.scrollTop = UIdata['scrollTop'] || 0;
        elem.scrollLeft = UIdata['scrollLeft'] || 0;
    }
}

var sel_line_num=-1;

function syncTextarea (UIstr) {
    var elem=document.getElementById('code');
    var UIfield=document.createElement('input');
    UIfield.id='UIdata';
    UIfield.name='UIdata';
    UIfield.type='hidden';
    UIfield.value=UIstr;
    document.main_form.appendChild(UIfield);
    if (UIstr.length)
    {
        setTimeout('UItimeout()',0);
    }
    if (browser.isIE)
    {
        if (!is_opera)
        {
            if (elem.tagName.toLowerCase() === 'textarea')
            {
                elem.style.width=((elem.parentNode.offsetWidth)-34)+'px';
                document.getElementById('code_numbers').style.paddingTop='1px';
            }
        }
    }
    init_splitters();
    var elem1 = document.getElementById('code_numbers');
    elem.onscroll = function (evt) {
        //var st=parseInt(this.scrollTop,10);
        elem1.style.top = (parseInt(this.scrollTop,10) * -1) + 'px';
        //document.main_form.scrollposy.value=st;
        //document.main_form.scrollposx.value=parseInt(elem.scrollLeft,10);
    };
    if (elem.tagName.toLowerCase() !== 'textarea')
    {
        return;
    }
    if (Number(document.getElementById('change_counter').value) === 0)
    {
    	if (editor && typeof editor.getValue === 'function') {
    		savedCode = editor.getValue();
		}
		else {
        	savedCode = elem.value;
        }
    }
    elem.onkeydown = function (evt) {
        var retval=catchTab(this,evt);
        //setTimeout("checkDirty()",0);
        return retval;
    };
    elem.onkeyup = function (evt) {
        setTimeout("checkDirty()",0);
   	};
    elem.onmouseup = function (evt) {
        setTimeout("checkDirty()",0);
   	};
    elem.onchange = function (evt) {
    	setTimeout("checkDirty()",0);
   	};
    elem.onfocus = function (evt) {
    	setTimeout("checkDirty()",0);
   	};
   	if (elem1) {
		elem1.onmousedown = function (evt)
		{
			sel_line_num=line_number(evt,this);
			var splitbg=document.getElementById('splitter_bg');

			if (!splitbg)
			{
				splitbg=document.createElement('div');
				splitbg.id='splitter_bg';
				splitbg.style.top='0px';
				splitbg.style.left='0px';
				splitbg.style.width='100%';
				splitbg.style.height='100%';
				splitbg.style.position='fixed';
				//splitbg.style.backgroundColor='#ffffff';
				splitbg.style.display='block';
				splitbg.innerHTML=' ';
				//splitbg.onmousemove=new Function("dragGo(event);");
				//splitbg.onmouseup=new Function("dragStop(event);");
				document.body.appendChild(splitbg);
			}
			splitbg.style.zIndex=8000;
			this.parentNode.style.zIndex=8001;
			splitbg.style.cursor='default';
			splitbg.style.visibility='visible';

			if (browser.isIE)
			{
				document.attachEvent("onmousemove", lines_dragGo);
				document.attachEvent("onmouseup",   lines_dragStop);
				window.event.cancelBubble = true;
				window.event.returnValue = false;
			}
			if (browser.isNS)
			{
				document.addEventListener("mousemove", lines_dragGo,   true);
				document.addEventListener("mouseup",   lines_dragStop, true);
				event.preventDefault();
			}
	   };
	}
   	elem.focus();
}

function lines_dragGo(event)
{
    var elem=document.getElementById('code');
    var elem1=document.getElementById('code_numbers');
    select_lines(elem,sel_line_num,line_number(event,elem1));
    if (browser.isIE)
    {
        window.event.cancelBubble = true;
        window.event.returnValue = false;
    }
    if (browser.isNS)
    {
        event.preventDefault();
    }
}

function lines_dragStop(event)
{
    var elem=document.getElementById('code');
    var elem1=document.getElementById('code_numbers');
    select_lines(elem,sel_line_num,line_number(event,elem1));
    sel_line_num=-1;
    if (browser.isIE) {
        document.detachEvent("onmousemove", lines_dragGo);
        document.detachEvent("onmouseup",   lines_dragStop);
    }
    if (browser.isNS) {
        document.removeEventListener("mousemove", lines_dragGo,   true);
        document.removeEventListener("mouseup",   lines_dragStop, true);
    }
    elem1.parentNode.style.zIndex='auto';
    var splitbg=document.getElementById('splitter_bg');
    splitbg.style.visibility='hidden';
    checkDirty();
}

function line_number(event,elem)
{
  if (browser.isIE) {
	    y = window.event.clientY + document.documentElement.scrollTop + document.body.scrollTop;
  }
  if (browser.isNS) {
    y = event.clientY + window.scrollY;
  }
	var top  = parseInt(elem.style.top,10);
    if (isNaN(top))
    {
    	top=0;
    }
	var y_rel=(y-top)-50;
	return parseInt(y_rel/parseInt(elem.style.lineHeight,10),10);
}

function select_lines(elem,startLine,endLine)
{
	if (endLine<startLine)
	{
		var temp=endLine;
		endLine=startLine;
		startLine=temp;
	}
    var selcode=elem.value;
    if (browser.isIE && !is_opera)
    {
        selcode=selcode.replace(/\r/g,'');
    }
    var lines=selcode.explode('\n');
    if (startLine>=lines.length-1)
    {
        startLine=lines.length-2;
    }
    if (startLine<0)
    {
        startLine=0;
    }
    if (endLine>=lines.length)
    {
        endLine=lines.length-1;
    }
    if (endLine<0)
    {
        endLine=0;
    }
    var selS=0;
    for (var i=0;i<startLine;i++)
    {
    	selS+=lines[i].length+1;
    }
    var selE=selS;
    for (var i= startLine;i<=endLine;i++)
    {
    	selE+=lines[i].length+1;
    }
    setSelectionRange(elem,selS,selE-1,true);
}

function showHideLayer() {
    const menu = document.getElementById(sub_id);
    if (show)
    {
		menu.style.visibility = "visible";
		menu.style.display = "block";
		menu.style.zIndex = 99999;
    }
    else
    {
   		menu.style.display = "none";
    }
}

function decimalToHex(d, padding) {
    var hex = Number(d).toString(16);
    padding = typeof padding === "undefined" || padding == null ? padding = 2 : padding;

    while (hex.length < padding) {
        hex = "0" + hex;
    }

    return hex.toUpperCase();
}

function checkDirty()
{
	var isdirty=false;
	if (editor) {
		return checkDirtyCodeMirror();
	}
	var code=document.getElementById('code');
	if (code.tagName.toLowerCase() === 'textarea')
	{
		if (document.getElementById('infobar'))
		{
			var selS=selStart(code,true);
			var selL=selLen(code,true);
			var info='';
			var selcode=code.value;
			if (browser.isIE && !is_opera)
			{
				selcode=selcode.replace(/\r/g,'');
			}
			if (selL != 0)
			{
				var selection=selcode.substring(selS,selS+selL);
				var rows=selection.substring_count('\n')+1;
				var startarray=selcode.substring(0,selS).split('\n');
				var startcol=startarray[startarray.length-1].length;
				var endarray=selcode.substring(0,selS+selL).split('\n');
				var endcol=endarray[endarray.length-1].length;
				info+= rows+' x '+Math.abs(endcol-startcol)+'   ['+selection.length+'] ';
			}
			else
			{
				var startarray=selcode.substring(0,selS).split('\n');
				var startcol=startarray[startarray.length-1].length+1;
				var startrow=startarray.length;
				var endarray=selcode.split('\n');
				info+= startrow+' : '+startcol+' / '+endarray.length+' ';
			}
			document.getElementById('infobar').innerHTML=info+'   '+selcode.charCodeAt(selS)+' (0x'+decimalToHex(selcode.charCodeAt(selS),2)+')';
		}
	}
	if ((savedCode.length) && (code.tagName.toLowerCase() === 'textarea'))
	{
		isdirty=(code.value !== savedCode);
	}
	else
	{
		isdirty=(Number(document.getElementById('change_counter').value) > 0);
	}
	if (isdirty)
	{
		document.getElementById('change_counter').value++;
	}
	document.getElementById('dirty_p').innerHTML=save_image(isdirty,true);
	if (code.tagName.toLowerCase() === 'textarea')
	{
		if (document.getElementById('infobar'))
		{
			var charcode=selcode.charCodeAt(selS);
			var ccodestr='';
			if (charcode)
			{
				ccodestr=charcode+' (0x'+decimalToHex(charcode,2)+')';
			}
			document.getElementById('infobar').innerHTML=save_image(isdirty,false)+'   '+info+'   '+ccodestr;
		}
	}
	return isdirty;
}

function checkDirtyCodeMirror() {
    let isDirty = false;
    let currentCode = "";
    let cursor = { line: 0, ch: 0 };
    let selLen = 0;
    let info = "";
    let ccodestr = "";
    let lineCount = 0;

    const elem = document.getElementById('code');

    if (editor && typeof editor.getValue === 'function') {
        currentCode = editor.getValue();
        if (savedCode.length > 0) {
            isDirty = currentCode !== savedCode;
        } else {
            isDirty = (Number(document.getElementById('change_counter').value) > 0);
        }
		if (isDirty)
		{
			document.getElementById('change_counter').value++;
		}

        const selText = editor.getSelection();
        selLen = selText.length;
        cursor = editor.getCursor();
        lineCount = editor.lineCount();

        if (selLen > 0) {
            const selectedLines = selText.split("\n").length;
            const start = editor.getCursor("from");
            const end = editor.getCursor("to");

            const startCol = start.ch + 1;
            const endCol = end.ch + 1;
            const colDiff = Math.abs(endCol - startCol);

            info = `${selectedLines} x ${colDiff}   [${selLen}]`;
        } else {
            info = `${cursor.line + 1} : ${cursor.ch + 1} / ${lineCount}`;
        }

        let charcode = currentCode.charCodeAt(editor.indexFromPos(cursor));
        if (!isNaN(charcode)) {
            ccodestr = `${charcode} (0x${decimalToHex(charcode, 2)})`;
        }
    }
    // Uppdatera UI
    document.getElementById('dirty_p').innerHTML = save_image(isDirty, true);
    if (document.getElementById('infobar')) {
        document.getElementById('infobar').innerHTML =
            save_image(isDirty, false) + '   ' + info + '   ' + ccodestr;
    }
    return isDirty;
}

function save_image(dirty,large)
{
    if (dirty)
    {
        if (large)
        {
            return '<a href="#" class="imgbutton" onClick="main_submit(\'save\');" title="Save"> <img src="images/savel.png"/> </a>';
        }
        else
        {
            return '<a href="#" onClick="main_submit(\'save\');" title="Save"><img src="images/save.png"/></a>';
        }
    }
    return '';
}

function checkEnter(e)
{
    var evt = e ? e : window.event;
	var c = evt.which ? evt.which : evt.keyCode;
    if (c == 13)
    {
        document.main_form.action.value='eval_change';
        var evalbutton=document.getElementById('submit_eval');
        evalbutton.click();
        return true;
    }
}

function change_inc(c)
{
    if ((c>=16) && (c<=20))
    {
        return;
    }
    if ((c>=33) && (c<=40))
    {
        return;
    }
    if ((c>=173) && (c<=178))
    {
        return;
    }
    if ((c>=112) && (c<=123))
    {
        return;
    }
    if (c == 27){return;}
    if (c == 45){return;}
    if (c == 91){return;}
    if (c == 93){return;}
    if (c == 144){return;}
    inc_changecount();
}

function inc_changecount(reset)
{
    if (typeof reset ==='undefined')
    {
        reset=false;
    }
    if (!reset)
    {
        document.getElementById('change_counter').value++;
        document.getElementById('dirty_p').innerHTML='*';
    }
    else
    {
        document.getElementById('change_counter').value = 0;
        document.getElementById('dirty_p').innerHTML='';
    }
}

function reset_width()
{
    var elem=document.getElementById('code');
    if (elem.tagName.toLowerCase() === 'textarea')
    {
        elem.style.width=((elem.parentNode.offsetWidth)-34)+'px';
        var st=parseInt(elem.scrollTop,10);
        document.getElementById('code_numbers').style.top = (st * -1) + 'px';
    }
}

function opera_scroll()
{
    var elem=document.getElementById('code');
    var st=parseInt(elem.scrollTop,10);
    document.getElementById('code_numbers').style.top= (st * -1) +'px';
    setTimeout('opera_scroll()', 50);
}

function replaceSelection(input, replaceString) {
    if (replaceString.length === 0)
    {
		if (input.setSelectionRange) {
			var selectionStart = input.selectionStart;
			var selectionEnd = input.selectionEnd;
			input.value = input.value.substring(0, selectionStart)+ replaceString + input.value.substring(selectionEnd);

			if (selectionStart != selectionEnd){
				setSelectionRange(input, selectionStart, selectionStart + replaceString.length);
			}else{
				setSelectionRange(input, selectionStart + replaceString.length, selectionStart + replaceString.length);
			}
		}else if (document.selection) {
			var range = document.selection.createRange();
			if (range.parentElement() == input) {
				var isCollapsed = range.text === '';
				range.text = replaceString;

				 if (!isCollapsed)  {
					range.moveStart('character', -replaceString.length);
					range.select();
				}
			}
		}
		checkDirty();
		return;
    }
    setTimeout("create_text_event('"+replaceString+"','"+input.id+"');",0);
}

function selStart(input,trueCharCount) {
	if (input.setSelectionRange) {
		return input.selectionStart;
	} else if (document.selection) {
        if (typeof trueCharCount ==='undefined')
        {
            trueCharCount=false;
        }
        if (!trueCharCount)
        {
            input.focus();

            var r = document.selection.createRange();
            if (!r) {
                return 0;
            }

            var re = input.createTextRange(),
            rc = re.duplicate();
            re.moveToBookmark(r.getBookmark());
            rc.setEndPoint('EndToStart', re);

            return rc.text.length;// + j;
        }
        input.focus();
        var Sel = document.selection.createRange ();
        var Sel2 = Sel.duplicate();
        Sel2.moveToElementText(input);
        var CaretPos = -1;
        while(Sel2.inRange(Sel))
        {
            Sel2.moveStart('character');
            CaretPos++;
        }
        return CaretPos;
	}
}

function selEnd(input,trueCharCount) {
	if (input.setSelectionRange) {
		return input.selectionEnd;
	} else if (document.selection) {
        if (typeof trueCharCount === 'undefined')
        {
            trueCharCount=false;
        }
        if (!trueCharCount)
        {

            input.focus();

            var r = document.selection.createRange();
            if (!r) {
                return 0;
            }

            var re = input.createTextRange(),
            rc = re.duplicate();
            re.moveToBookmark(r.getBookmark());
            rc.setEndPoint('EndToStart', re);

            //var j=input.value.substr(0,rc.text.length+r.text.length).split('\n').length-1;
            return rc.text.length+r.text.length;// + j;
        }
        return selLen(input,trueCharCount)+selStart(input,trueCharCount);
	}
}

function selLen(input,trueCharCount) {
	if (input.setSelectionRange) {
		return input.selectionEnd-input.selectionStart;
	} else if (document.selection) {
        if (typeof trueCharCount === 'undefined')
        {
            trueCharCount=false;
        }
        if (!trueCharCount)
        {
            input.focus();

            var r = document.selection.createRange();
            return r.text.length;// + j;
        }
        input.focus();
        var Sel = document.selection.createRange ();
        var Sel1 = Sel.duplicate();
        var Sel2 = Sel.duplicate();
        //Sel2.moveToElementText(input);
        Sel2.setEndPoint('EndToStart', document.selection.createRange());
        var CaretPos = -1;
        while(Sel1.inRange(Sel2))
        {
            Sel2.moveStart('character');
            CaretPos++;
        }
        return CaretPos;
	}
}

// We are going to catch the TAB key so that we can use it, Hooray!
function catchTab(item,e){
    var evt = e ? e : window.event;
	c = evt.which ? evt.which : evt.keyCode;
	if (c == 114) //f3
	{
        search_textarea(false);
        return false;
    }
	if(c == 9){  //tab
        replaceSelection(item,String.fromCharCode(9));
		return false;
	}
    if (c == 13)   // enter
    {
        var code=document.getElementById(item.id);
        var SE=selEnd(item);
        var line_array=code.value.substring(0,SE).split('\n');
        var occur = line_array[line_array.length-1].match(/^([ \t]+)/g);
        if (occur)
        {
            replaceSelection(item,occur[0]);
        }
    }
}

function create_text_event(str,id)
{
    var input=document.getElementById(id);
	if (is_opera) {
		var selectionStart = input.selectionStart;
		var selectionEnd = input.selectionEnd;
		input.value = input.value.substring(0, selectionStart)+ str + input.value.substring(selectionEnd);

		if (selectionStart != selectionEnd){
			setSelectionRange(input, selectionStart, selectionStart + str.length);
		}else{
			setSelectionRange(input, selectionStart + str.length, selectionStart + str.length);
		}
	}else if (document.selection) { //IE
		var range = document.selection.createRange();
		if (range.parentElement() == input) {
			var isCollapsed = range.text === '';
			range.text = str;

			 if (!isCollapsed)  {
				range.moveStart('character', -str.length);
				range.select();
			}
		}
	}
    else if (is_safari)
    {
        var eventObject = document.createEvent('TextEvent');
        eventObject.initTextEvent('textInput', true, true, null, str);
        input.dispatchEvent(eventObject);
    }
    else
    {
        for (var i=0;i<str.length;i++)
        {
            var ev = document.createEvent ('KeyEvents');
            ev.initKeyEvent('keypress', true, true, window,false, false, false, false, 0,str.charCodeAt(i));
            input.dispatchEvent(ev); // causes the scrolling
        }
    }
    setTimeout("checkDirty()",0);
}

function submit_sort(order)
{
    document.getElementById('sortorder').value=order;
    main_submit('set_sortorder');
}

function submit_dir(dir)
{
    document.getElementById('current_directory').value=dir;
    main_submit('set_directory');
}

function submit_file(file)
{
		var elem=document.getElementById('code');
	   	if (editor) {
	   		setSelectionRange(elem,0,0);
	   	}
		else if (elem.tagName.toLowerCase() === 'textarea')
		{
			setSelectionRange(elem,0,0);
			elem.scrollTop=0;
			elem.scrollLeft=0;
		}
		document.getElementById('some_file_name').value=""+file+"";
		//document.main_form.action.value='load_browse_file';
		if (checkDirty())
		{
			var currfile=document.getElementById('Current_filename').value;
			ae_confirm_yes_no(submit_file_callback,"Save changes to "+currfile);
			return;
		}
		main_submit('load_browse_file');
}

function submit_file_callback(returncode,id,value)
{
    if (returncode != 1)
    {
        main_submit("load_browse_discard");
        return;
    }
    main_submit('load_browse_save');
}

function chmod_file(file,value)
{
    ae_prompt(chmod_callback,'text%¤%Change permissions for '+file+'%¤%'+value,'OK%¤%1|¤|Cancel%¤%0',file);
}

function chmod_callback(returncode,id,value)
{
    if (returncode == 1)
    {
        if (value != '' && value != null)
        {
            document.getElementById('some_file_name').value=id;
            document.getElementById('chmod_value').value=""+value+"";

            main_submit('chmod_file');
        }
    }
}

function save_as(file)
{
    ae_prompt(prompt_callback,'text%¤%Save as%¤%'+file,'OK%¤%1|¤|Cancel%¤%0','save_as');
}

function prompt_callback(returncode,id,value)
{
    //var answer = prompt ('Save as ',""+file+"");
    if (returncode == 1)
    {
        if (value != '' && value != null)
        {
            document.getElementById('save_as_filename').value=""+value+"";
            main_submit(id);
        }
    }
}

function new_file(path)
{
    ae_prompt(prompt_callback,'text%¤%New file:%¤%'+path,'OK%¤%1|¤|Cancel%¤%0','set_new');
}

function upload_file()
{
    ae_prompt(prompt_callback,'file%¤%Upload file <b>Will overwrite existing files!!!</b>:%¤%','OK%¤%1|¤|Cancel%¤%0','set_upload');
}

function get_url_file()
{
    ae_prompt(prompt_callback,'text%¤%File URL:%¤%','OK%¤%1|¤|Cancel%¤%0','set_file_from_url');
}

function ftp_file(ftp_path)
{
    ae_prompt(prompt_callback,'text%¤%Ftp path & login ( ftp://username:password@sld.domain.tld/path1/path2/ ):%¤%'+ftp_path,'OK%¤%1|¤|Cancel%¤%0','set_ftp_file');
}

function ftp_system(ftp_path)
{
    ae_prompt(prompt_callback,'text%¤%Ftp path & login ( ftp://username:password@sld.domain.tld/path1/path2/ ):%¤%'+ftp_path,'OK%¤%1|¤|Cancel%¤%0','set_ftp_system');
}

function new_directory(path)
{
    ae_prompt(prompt_callback,'text%¤%New directory:%¤%'+path,'OK%¤%1|¤|Cancel%¤%0','set_new_directory');
}

function copy_file(path)
{
    //document.main_form.action.value='set_copy';
    if (checkDirty())
    {
        var currfile=document.getElementById('Current_filename').value;
        ae_confirm_yes_no(copy_file_callback,"Save changes to "+currfile);
        return;
    }
    main_submit('set_copy');
}

function copy_file_callback(returncode,id,value)
{
    if (returncode != 1)
    {
        main_submit("set_copy_discard");
    }
    main_submit('set_copy_save');
}

function callback_submit(returncode,act)
{
    if (returncode != 0)
    {
        main_submit(act);
    }
}

function rename_file(name)
{
    ae_prompt(prompt_callback,'text%¤%New file name:%¤%'+name,'OK%¤%1|¤|Cancel%¤%0','set_rename');
}

function runeval()
{
   var evalwin=document.getElementById('evaluationwindow');
   if (evalwin)
   {
     evalwin.contentWindow.location.reload();
   }
}

function eval_history(step)
{
   var evalwin=document.getElementById('evaluationwindow');
   if (evalwin)
   {
        evalwin.contentWindow.history.go(step);
   }
}

var searchPos=0;
var searchTerm='';
var searchMatchCase=false;
var searchWholeWord=false;
var replaceTerm='';
var searchSelStart=0;
var searchSelEnd=0;
var searchSelected=false;

RegExp.escape = function(text) {
  if (!arguments.callee.sRE) {
    var specials = [
      '/', '.', '*', '+', '?', '|',
      '(', ')', '[', ']', '{', '}','$','^','\\'
    ];
    arguments.callee.sRE = new RegExp(
      '(\\' + specials.join('|\\') + ')', 'g'
    );
  }
  return text.replace(arguments.callee.sRE, '\\$1');
}

function search_textarea(showDialog)
{
    if (showDialog)
    {
        var code=document.getElementById('code');
        searchSelStart=selStart(code);
        searchSelEnd=selEnd(code);
        if (searchSelStart != searchSelEnd)
        {
            searchTerm=code.value.substring(searchSelStart,searchSelEnd).explode('\n')[0];
        }
        var caseChecked=(searchMatchCase ? 'checked':'');
        var wordChecked=(searchWholeWord ? 'checked':'');
        var selectedChecked=(searchSelected ? 'checked':'');
        ae_prompt(search_callback,'textarea%¤%Search for%¤%'+searchTerm+'|¤|checkbox%¤%Match case%¤%'+caseChecked+'|¤|checkbox%¤%Whole word%¤%'+wordChecked+'|¤|checkbox%¤%Only inside selection%¤%'+selectedChecked,'OK%¤%1|¤|Cancel%¤%0');
    }
    else
    {
        search_callback(2,'','')
    }
}

function search_callback(returncode,id,value)
{
    var code=document.getElementById('code');
    if (returncode == 1)
    {
        value_array=value.split('|¤|');
        searchTerm=value_array[0];
        searchMatchCase=(value_array[1] == 'true') ? true:false;
        searchWholeWord=(value_array[2] == 'true') ? true:false;
        searchSelected=(value_array[3] == 'true') ? true:false;
        if (!searchSelected)
        {
            searchSelStart=0;
            searchSelEnd=code.value.length;
        }
        searchPos=searchSelStart;
    }
    if (returncode == 0)
    {
        return;
    }
    if (searchTerm != '' && searchTerm != null)
    {
        var RegExpStr=RegExp.escape(searchTerm);
        var RegExpModifier='';
        if (searchWholeWord)
        {
            RegExpStr='[^a-zA-Z0-9_]'+RegExpStr+'[^a-zA-Z0-9_]';
        }
        if (!searchMatchCase)
        {
            RegExpModifier='i';
        }
        var regex = new RegExp(RegExpStr, RegExpModifier);
        var start=-1;
        while (1)
        {
            start=code.value.substring(searchPos,searchSelEnd).search(regex);
            if (start != -1)
            {
                if (searchWholeWord)
                {
                    start++;
                }
                start+=searchPos;
                searchPos=start+1;
                scrollIntoView(code,start,start+searchTerm.length);
                break;
            }
            else
            {
                if (searchPos>0)
                {
                    searchPos=searchSelStart;
                }
                else
                {
                    break;
                }
            }
        }
        if (start == -1)
        {
            ae_alert(searchTerm + ' not found');
        }
        else
        {
	        setTimeout("document.getElementById('code').focus();",0);
        }
    }
}

function replace_textarea()
{
	var code=document.getElementById('code');
	searchSelStart=selStart(code);
	searchSelEnd=selEnd(code);
	if (searchSelStart != searchSelEnd)
	{
		searchTerm=code.value.substring(searchSelStart,searchSelEnd).explode('\n')[0];
	}
    var caseChecked=(searchMatchCase ? 'checked':'');
    var wordChecked=(searchWholeWord ? 'checked':'');
        var selectedChecked=(searchSelected ? 'checked':'');
    ae_prompt(replace_callback,'textarea%¤%Search for%¤%'+searchTerm+'|¤|textarea%¤%Replace with%¤%'+replaceTerm+'|¤|checkbox%¤%Match case%¤%'+caseChecked+'|¤|checkbox%¤%Whole word%¤%'+wordChecked+'|¤|checkbox%¤%Only inside selection%¤%'+selectedChecked,'OK%¤%1|¤|Cancel%¤%0');
}

function replace_callback(returncode,id,value)
{
    var replacements=0;
    var code=document.getElementById('code');
    if (returncode == 1)
    {
        value_array=value.split('|¤|');
        searchTerm=value_array[0];
        replaceTerm=value_array[1];
        searchMatchCase=(value_array[2] == 'true') ? true:false;
        searchWholeWord=(value_array[3] == 'true') ? true:false;
        searchSelected=(value_array[4] == 'true') ? true:false;
    }
    if (returncode == 0)
    {
        return;
    }
    if (!searchSelected)
    {
        searchSelStart=0;
        searchSelEnd=code.value.length;
    }
    searchPos=searchSelStart;
    if (searchTerm != '' && searchTerm != null && replaceTerm != '' && replaceTerm != null)
    {
        var RegExpStr=RegExp.escape(searchTerm);
        var RegExpModifier='';
        if (searchWholeWord)
        {
            RegExpStr='[^a-zA-Z0-9_]'+RegExpStr+'[^a-zA-Z0-9_]';
        }
        if (!searchMatchCase)
        {
            RegExpModifier='i';
        }
        var regex = new RegExp(RegExpStr, RegExpModifier);
        var start=-1;
        while (1)
        {
            start=code.value.substring(searchPos,searchSelEnd).search(regex);
            if (start != -1)
            {
                if (searchWholeWord)
                {
                    start++;
                }
                start+=searchPos;
                searchPos=start+1;
                code.value=code.value.substring(0,start)+replaceTerm+code.value.substring(start+searchTerm.length);
                searchSelEnd+=replaceTerm.length-searchTerm.length;
                replacements++;
            }
            else
            {
                break;
            }
        }
        if (replacements == 0)
        {
            ae_alert(searchTerm + ' not found');
        }
        else
        {
            //inc_changecount();
            checkDirty();
            ae_alert(searchTerm.toHtmlEntities() + ' was replaced '+replacements+' times');
	        setTimeout("document.getElementById('code').focus();",0);
        }
    }
}

function setSelectionRange(input, selectionStart, selectionEnd, trueCharCount)
{
     if (editor && typeof editor.setSelection === 'function') {
		var startPos = editor.posFromIndex(selectionStart);
		var endPos = editor.posFromIndex(selectionEnd);
		editor.setSelection(startPos, endPos);
    // === Fallback till vanlig <textarea> ===
    }
    else {
         input.focus();
		 if (input.setSelectionRange)
		 {
			/*
			if (is_opera)
			{
				input.setSelectionRange(selectionStart+(input.value.substring(0,selectionStart).substring_count('\n')), selectionEnd+(input.value.substring(0,selectionEnd).substring_count('\n')));
				return;
			}
			*/
			input.setSelectionRange(selectionStart, selectionEnd);
		 }
		 else if (input.createTextRange) {
			var i = input.createTextRange();
			i.collapse(true);
			if (typeof trueCharCount === 'undefined')
			{
				trueCharCount=false;
			}
			var j1=0;
			var j2=0;
			if (!trueCharCount)
			{
				//j1=input.value.substring(0,selectionEnd).split('\n').length;
				//j2=input.value.substring(0,selectionStart).split('\n').length;
				j1=input.value.substring(0,selectionEnd).substring_count('\n');
				j2=input.value.substring(0,selectionStart).substring_count('\n');
			}
			i.moveEnd('character', selectionEnd-j1);
			i.moveStart('character', selectionStart-j2);
			i.select();
			input.focus();
		 }
     }
}

function scrollIntoView(input, selectionStart, selectionEnd)
{
     input.focus();
     if (input.setSelectionRange)
     {
        input.setSelectionRange(selectionEnd-1, selectionEnd);
        //element.setSelectionRange((i=index+needle.length)-1,i);

        if (is_safari)
        {
            input.setSelectionRange(selectionEnd-1, selectionEnd);
            var eventObject = document.createEvent('TextEvent');
            eventObject.initTextEvent('textInput', true, true, null, input.value.substring(selectionEnd-1,selectionEnd));
            input.dispatchEvent(eventObject);
        }
        else if (is_opera)
        {
            //opera can't do it!
            //alert(input.value.substring(0,selectionEnd).split('\n').length);
            input.scrollTop=((input.value.substring(0,selectionEnd).substring_count('\n'))*parseInt(input.style.lineHeight,10))-20;   //???
        }
        else
        {
            input.setSelectionRange(selectionEnd-1, selectionEnd);
            var ev = document.createEvent ('KeyEvents');
            ev.initKeyEvent('keypress', true, true, window,false, false, false, false, 0,input.value.charCodeAt(selectionEnd-1));
            input.dispatchEvent(ev); // causes the scrolling
        }
        input.setSelectionRange(selectionStart, selectionEnd);
     }
     else if(input.createTextRange) {
        var i = input.createTextRange();
        i.collapse(true);
        var j=input.value.substring(0,selectionEnd).substring_count('\n');
        i.moveEnd('character', selectionEnd-j);
        j=input.value.substring(0,selectionStart).substring_count('\n');
        i.moveStart('character', selectionStart-j);
        i.select();
        i.scrollIntoView();
        input.focus();
     }
}

function setCaretToPos(input, pos) {
	setSelectionRange(input, pos, pos);
}

function clearAuthenticationCache(page) {
    ae_confirm_yes_no(clearAuthenticationCache_callback,"Log out?",page);
}

function clearAuthenticationCache_callback(returncode,page,value)
{
	if (returncode == 1)
	{
		if (page.length === 0) page = '.force_logout';
		try{
			var agt=navigator.userAgent.toLowerCase();
			if (agt.indexOf("msie") !== -1) {
				// IE clear HTTP Authentication
				document.execCommand("ClearAuthenticationCache");
			}
			else {
				// Let's create an xmlhttp object
				var xmlhttp = createXMLObject();
				// Let's prepare invalid credentials
				xmlhttp.open("GET", page, true, "logout", "logout");
				// Let's send the request to the server
				xmlhttp.send("");
				// Let's abort the request
				xmlhttp.abort();
			}
			main_submit("logout");
			return;
		} catch(e) {
			// There was an error
			ae_alert('Log out error, browser may not be supported');
		}
	}
}

function createXMLObject() {
	var xmlhttp=false;
	if (!xmlhttp && typeof XMLHttpRequest !== 'undefined') {
		try {
			xmlhttp = new XMLHttpRequest();
		} catch (e) {
			xmlhttp=false;
		}
	}
	if (!xmlhttp && window.createRequest) {
		try {
			xmlhttp = window.createRequest();
		} catch (e) {
			xmlhttp=false;
		}
	}
    return xmlhttp;
}

function validatePass()
{
	var invalid = " "; // Invalid character is a space
	var minLength = 6; // Minimum length
	var pw1 = document.main_form.password.value;
	var pw2 = document.main_form.passwordrepeat.value;
	var usernames=document.main_form.allusernames.value.explode('|¤|');
	var specials = [
      '/', '.', '*', '+', '?', '|',
      '(', ')', '[', ']', '{', '}','$','^','\\'
    ];
	var matches=document.main_form.username.value.match(/[\?\[\]\/\\=\+<>:;'",\.\*]+/);
    if (matches)
    {
    	ae_alert(matches[0]+' is not allowed in the username.');
	   	return false;
    }

	for (var i=0;i<usernames.length;i++)
	{
        if (document.main_form.username.value === usernames[i])
        {
    		ae_alert('User already exists.');
	   	    return false;
        }
    }
	// check for a value in both fields.
	if (pw1 == '' || pw2 == '') {
		ae_alert('Please enter your password twice.');
		return false;
	}
	// check for minimum length
	if (pw1.length < minLength) {
		ae_alert('Your password must be at least ' + minLength + ' characters long. Try again.');
		return false;
	}
	// check for spaces
	if (pw1.indexOf(invalid) !== -1) {
		ae_alert("Sorry, spaces are not allowed.");
		return false;
	}
	if (pw1 != pw2) {
		ae_alert("You did not enter the same new password twice. Please re-enter your password.");
		return false;
	}
	return true;
}

function getElementsByClassName(node,classname) {
	if (node.getElementsByClassName)
		return node.getElementsByClassName(classname);
	else {
	var testClass = new RegExp("(^|\\s)" + classname + "(\\s|$)");
	var tag = "*";
	var node = node || document;
	var elements = (tag == "*" && node.all)? node.all : node.getElementsByTagName(tag);
	var returnElements = [];
	var current;
	var length = elements.length;
	for(var i=0; i<length; i++){
		current = elements[i];
		if(testClass.test(current.className)){
			returnElements.push(current);
		}
	}
	return returnElements;
	}
}

// Determine browser and version.

function Browser() {

  var ua, s, i;

  this.isIE    = false;
  this.isNS    = false;
  //this.version = null;

  ua = navigator.userAgent;

  s = "MSIE";
  if ((i = ua.indexOf(s)) >= 0) {
    this.isIE = true;
    //this.version = parseFloat(ua.substr(i + s.length));
    return;
  }

  s = "Opera";
  if ((i = ua.indexOf(s)) >= 0) {
    this.isIE = true;
    //this.version = parseFloat(ua.substr(i + s.length));
    return;
  }

  s = "Netscape6/";
  if ((i = ua.indexOf(s)) >= 0) {
    this.isNS = true;
    //this.version = parseFloat(ua.substr(i + s.length));
    return;
  }

  // Treat any other "Gecko" browser as NS 6.1.

  s = "Gecko";
  if ((i = ua.indexOf(s)) >= 0) {
    this.isNS = true;
    //this.version = 6.1;
    return;
  }
}

// Global object to hold drag information.

var dragObj = new Object();
dragObj.zIndex = 0;
var winheight;
var winwidth;
//global to hold the current table placement
var cell_padding=0;
var padding_left=0;
var padding_right=0;
var padding_bottom=0;
var padding_top=25;
var tds1;
var tds2;

function window_size()
{
  if( typeof window.innerWidth === 'number' ) {
    //Non-IE
    winwidth = window.innerWidth;
    winheight = window.innerHeight;
  } else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
    //IE 6+ in 'standards compliant mode'
    winwidth = document.documentElement.clientWidth;
    winheight = document.documentElement.clientHeight;
  } else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
    //IE 4 compatible
    winwidth = document.body.clientWidth;
    winheight = document.body.clientHeight;
  }
}

function dragStart(event,id, td1) {

  var el;
  var x, y;
  var id;

    dragObj=new Object();
    dragObj.zIndex = 0;
  tds1=new Array();
  td_array=td1.split('%');
  for (var i=0;i<td_array.length;i++)
  {
    tds1[i]=document.getElementById(td_array[i]);
  }

  window_size();

  // If an element id was given, find it. Otherwise use the element being
  // clicked on.

  if (id)
  {
    dragObj.elNode = document.getElementById(id);
    //alert(id);
  }
  else {
    if (browser.isIE)
      dragObj.elNode = window.event.srcElement;
    if (browser.isNS)
      dragObj.elNode = event.target;

    // If this is a text node, use its parent element.

    if (dragObj.elNode.nodeType == 3)
      dragObj.elNode = dragObj.elNode.parentNode;
  }

  tds2=new Array();
    tds2[0]=dragObj.elNode.parentNode.parentNode;

  // Get cursor position with respect to the page.

  if (browser.isIE) {
    x = window.event.clientX + document.documentElement.scrollLeft
      + document.body.scrollLeft;
    y = window.event.clientY + document.documentElement.scrollTop
      + document.body.scrollTop;
  }
  if (browser.isNS) {
    x = event.clientX + window.scrollX;
    y = event.clientY + window.scrollY;
  }

  // Save starting positions of cursor and element.

  dragObj.cursorStartX = x;
  dragObj.cursorStartY = y;
  dragObj.elStartLeft  = parseInt(dragObj.elNode.style.left, 10);
  dragObj.elStartTop   = parseInt(dragObj.elNode.style.top,  10);

  if (isNaN(dragObj.elStartLeft)) dragObj.elStartLeft = 0;
  if (isNaN(dragObj.elStartTop))  dragObj.elStartTop  = 0;

  dragObj.elStartLeftPercent=((x-padding_left)*100)/(winwidth-(padding_left+padding_right));
  dragObj.elStartTopPercent=((y-padding_top)*100)/(winheight-(padding_top+padding_bottom));

  var splitbg=document.getElementById('splitter_bg');

  if (!splitbg)
  {
    splitbg=document.createElement('div');
    splitbg.id='splitter_bg';
    splitbg.style.top='0px';
    splitbg.style.left='0px';
    splitbg.style.width='100%';
    splitbg.style.height='100%';
    splitbg.style.position='fixed';
    splitbg.style.display='block';
    splitbg.innerHTML=' ';
    document.body.appendChild(splitbg);
  }
  splitbg.style.zIndex=8000;
  splitbg.style.cursor='pointer';
  splitbg.style.visibility='visible';

  dragObj.elNode.style.zIndex =8001;// ++dragObj.zIndex;

  // Capture mousemove and mouseup events on the page.

  if (browser.isIE) {
    document.attachEvent("onmousemove", dragGo);
    document.attachEvent("onmouseup",   dragStop);
    window.event.cancelBubble = true;
    window.event.returnValue = false;
  }
  if (browser.isNS) {
    document.addEventListener("mousemove", dragGo,   true);
    document.addEventListener("mouseup",   dragStop, true);
    event.preventDefault();
  }

}

function dragGo(event) {

  var x, y;

  // Get cursor position with respect to the page.

  if (browser.isIE) {
    x = window.event.clientX + document.documentElement.scrollLeft
      + document.body.scrollLeft;
    y = window.event.clientY + document.documentElement.scrollTop
      + document.body.scrollTop;
  }
  if (browser.isNS) {
    x = event.clientX + window.scrollX;
    y = event.clientY + window.scrollY;
  }

  // Move drag element by the same amount the cursor has moved.
  if (dragObj.elNode.className !== 'horiz_split')
  {
    dragObj.elNode.style.left = (dragObj.elStartLeft + (x - dragObj.cursorStartX)) + "px";
  }
  if (dragObj.elNode.className!== 'vert_split')
  {
    dragObj.elNode.style.top  = (dragObj.elStartTop  + (y - dragObj.cursorStartY)) + "px";
  }

  if (browser.isIE) {
    window.event.cancelBubble = true;
    window.event.returnValue = false;
  }
  if (browser.isNS)
    event.preventDefault();
}

function dragStop(event) {

  // Get cursor position with respect to the page.

  if (browser.isIE) {
    x = window.event.clientX + document.documentElement.scrollLeft
      + document.body.scrollLeft;
    y = window.event.clientY + document.documentElement.scrollTop
      + document.body.scrollTop;
  }
  if (browser.isNS) {
    x = event.clientX + window.scrollX;
    y = event.clientY + window.scrollY;
  }

  if (dragObj.elNode.className === 'horiz_split')
  {
    var y_percent=((y-(padding_top+cell_padding))*100)/(winheight-(padding_top+padding_bottom+(cell_padding*2)));
    var y_diff=Math.round(y_percent-dragObj.elStartTopPercent);
    var minHeight1=100;
    var minHeight2=100;
    for (var i=0;i<tds1.length;i++)
    {
      var newHeight=parseInt(tds1[i].style.height, 10)+y_diff;
      if (newHeight<minHeight1)
      {
        minHeight1=newHeight;
      }
    }
    for (var i=0;i<tds2.length;i++)
    {
      var newHeight=parseInt(tds2[i].style.height, 10)-y_diff;
      if (newHeight<minHeight2)
      {
        minHeight2=newHeight;
      }
    }
    if (minHeight1<2)
    {
    	minHeight2+=(minHeight1-2);
    	minHeight1=2;
    }
    if (minHeight2<2)
    {
    	minHeight1+=(minHeight2-2);
    	minHeight2=2;
    }
    for (var i=0;i<tds1.length;i++)
    {
      tds1[i].style.height=minHeight1+'%';
      var styleelem=document.getElementById(tds1[i].id+'_style');
      if (styleelem)
      {
        styleelem.value='width:'+tds1[i].style.width+';height:'+tds1[i].style.height+';';
      }
    }
    for (var i=0;i<tds2.length;i++)
    {
      tds2[i].style.height=minHeight2+'%';
      var styleelem=document.getElementById(tds2[i].id+'_style');
      if (styleelem)
      {
        styleelem.value='width:'+tds2[i].style.width+';height:'+tds2[i].style.height+';';
      }
    }
    dragObj.elNode.style.top=(dragObj.elStartTop-cell_padding)+'px';
  }

  if (dragObj.elNode.className === 'vert_split')
  {
    var x_percent=((x-(padding_left+cell_padding))*100)/(winwidth-(padding_left+padding_right+(cell_padding*2)));
    var x_diff=Math.round(x_percent-dragObj.elStartLeftPercent);
    var minWidth1=100;
    var minWidth2=100;
    for (var i=0;i<tds1.length;i++)
    {
      var newWidth=parseInt(tds1[i].style.width, 10)+x_diff;
      if (newWidth<minWidth1)
      {
        minWidth1=newWidth;
      }
    }
    for (var i=0;i<tds2.length;i++)
    {
      var newWidth=parseInt(tds2[i].style.width, 10)-x_diff;
      if (newWidth<minWidth2)
      {
        minWidth2=newWidth;
      }
    }
    if (minWidth1<2)
    {
    	minWidth2+=(minWidth1-2);
    	minWidth1=2;
    }
    if (minWidth2<2)
    {
    	minWidth1+=(minWidth2-2);
    	minWidth2=2;
    }
    for (var i=0;i<tds1.length;i++)
    {
      tds1[i].style.width=minWidth1+'%';
      var styleelem=document.getElementById(tds1[i].id+'_style');
      if (styleelem)
      {
        styleelem.value='width:'+tds1[i].style.width+';height:'+tds1[i].style.height+';';
      }
    }
    for (var i=0;i<tds2.length;i++)
    {
      tds2[i].style.width=minWidth2+'%';
      var styleelem=document.getElementById(tds2[i].id+'_style');
      if (styleelem)
      {
        styleelem.value='width:'+tds2[i].style.width+';height:'+tds2[i].style.height+';';
      }
    }
    dragObj.elNode.style.left=(dragObj.elStartLeft-cell_padding)+'px';
  }
  dragObj.elNode.style.zIndex=1001;
  // Clear the drag element global.
  dragObj.elNode = null;

  // Stop capturing mousemove and mouseup events.
  var splitbg=document.getElementById('splitter_bg');

  if (browser.isIE) {
    document.detachEvent("onmousemove", dragGo);
    document.detachEvent("onmouseup",   dragStop);
  }
  if (browser.isNS) {
    document.removeEventListener("mousemove", dragGo,   true);
    document.removeEventListener("mouseup",   dragStop, true);
  }

  splitbg.style.visibility='hidden';
  if (browser.isIE && !is_opera) {
    reset_width();
  }
}

function init_splitters()
{
  elem_array=getElementsByClassName(document,'horiz_split');
  for (var i=0;i<elem_array.length;i++)
  {
  	elem_array[i].style.top='-4px';
    elem_array[i].onmouseover=new Function("this.style.backgroundImage='url(images/splitterbg.png)';");
    elem_array[i].onmouseout=new Function("this.style.background='transparent';");
  }
  elem_array=getElementsByClassName(document,'vert_split');
  for (var i=0;i<elem_array.length;i++)
  {
  	elem_array[i].style.left='-4px';
    elem_array[i].onmouseover=new Function("this.style.backgroundImage='url(images/splitterbg.png)';");
    elem_array[i].onmouseout=new Function("this.style.background='transparent';");
  }
}