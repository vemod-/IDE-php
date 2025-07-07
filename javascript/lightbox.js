var win;

//var olderBrowser;

if (String.prototype.right==null) String.prototype.right=function(num){
      return this.substring(this.length-num);  // pull out right num
}

function closeFrame() {
    const isSubmit = document.getElementById('is_submit')?.value === '1';

    if (isSubmit) {
        const iframe = document.getElementById('previewframe');
        const doc = iframe?.contentDocument || iframe?.contentWindow?.document;

        try {
            doc?.forms[0]?.submit();
        } catch (e) {
            console.warn('Kunde inte skicka formuläret från iframen:', e);
        }
    }

    hideDiv('framediv');
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

function showFrame(url = '', inf = '', title = '', closeText = 'Stäng', isSubmit = false) {
    // Skapa eller hämta overlay-diven
    let borderDiv = document.getElementById('borderdiv');
    if (!borderDiv) {
        borderDiv = document.createElement('div');
        borderDiv.id = 'borderdiv';
        document.body.appendChild(borderDiv);
    }
    borderDiv.onclick = () => closeFrame();

    // Skapa eller hämta container-diven för ramen
    let frameDiv = document.getElementById('framediv');
    if (!frameDiv) {
        frameDiv = document.createElement('div');
        frameDiv.id = 'framediv';
        document.body.appendChild(frameDiv);
    }

    const frameUrl = url || 'about:blank';

    // Sätt HTML-innehållet
    frameDiv.innerHTML = `
        <div id="closediv" class="globalheader"></div>
        <iframe name="previewframe" id="previewframe" frameborder="0" src="${frameUrl}"></iframe>
        <div id="alternativediv" style="display: none;"></div>
        <input type="hidden" name="is_submit" id="is_submit" value="${isSubmit ? 1 : 0}" />
    `;

    // Sätt alternativ HTML-innehåll (om iframe inte används)
    const alternativeDiv = document.getElementById('alternativediv');
    if (alternativeDiv) {
        alternativeDiv.innerHTML = inf;
    }

    // Rubrik och stäng-knapp
    const closeDiv = document.getElementById('closediv');
    closeDiv.innerHTML = `
        <div class="inside_menu_text" style="text-indent:8px;">${title}</div>
        <div class="inside_menu" style="float:right;">
            <a href="#" class="btn" onclick="closeFrame(); return false;">${closeText}</a>
        </div>
    `;

    // Visa frame-div och sätt overlay till fixed
    showDiv('framediv');
    borderDiv.style.position = 'fixed';

    // Skriv direkt till iframen om ingen URL angavs
    if (!url && window.frames['previewframe']) {
        try {
            const previewFrame = window.frames['previewframe'];
            previewFrame.document.open();
            previewFrame.document.write(inf);
            previewFrame.document.close();
        } catch (e) {
            console.warn('Kunde inte skriva till iframen:', e);
        }
    }
}

function showElementFrame(element, title = '', close = 'Close', is_submit = false) {
    Object.assign(element.style, {
	    margin: '40px 20px 20px 20px', // top right bottom left
	    padding: '5px',
	    overflow: 'auto',
	    border: '1px solid black',
	    backgroundColor: 'white',
	    height: 'calc(100% - 60px)', // tar hänsyn till headern
	    width: 'calc(100% - 40px)',
	    boxSizing: 'border-box',
	});

    let borderdiv = document.getElementById('borderdiv');
    if (!borderdiv) {
	    borderdiv = document.createElement('div');
	    borderdiv.id = 'borderdiv';
	    document.body.appendChild(borderdiv);
    }
    borderdiv.onclick = () => closeFrame();

    let framediv = document.getElementById('framediv');
    if (!framediv) {
	    framediv = document.createElement('div');
	    framediv.id = 'framediv';
	    document.body.appendChild(framediv);
    }

    // Rensa innehåll
    framediv.innerHTML = '';

    // Header med stäng-knapp
    const closediv = document.createElement('div');
    closediv.id = 'closediv';
    closediv.className = 'globalheader';
    closediv.innerHTML = `
	    <div class="inside_menu_text" style="text-indent:8px;"> ${title}</div>
	    <div class="inside_menu" style="float:right;">
        <a href="#" class="btn" onclick="closeFrame(); return false;">${close}</a>
	    </div>`;
    framediv.appendChild(closediv);

    // Lägg till elementet (ex. domTreeContainer)
    framediv.appendChild(element);
    framediv.style.display = 'flex';	
    // Dold input för submit-flagg
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.id = 'is_submit';
    hiddenInput.name = 'is_submit';
    hiddenInput.value = is_submit ? '1' : '0';
    framediv.appendChild(hiddenInput);

    // Visa fönstret
    showDiv('framediv');
    borderdiv.style.position = 'fixed';
}

function ae_alert(text, title = '') {
  ae_prompt(null, `hidden%¤%${text}%¤%`, 'OK%¤%1', '', title);
}

async function ae_confirm_async(text, title = '') {
    //return ae_prompt_async(`hidden%¤%${text}%¤%`, 'Yes%¤%1|¤|No%¤%0', title)
	//    .then(answer => answer === '1');
	const { returncode, value } = await ae_promptAsync(`hidden%¤%${text}%¤%`, 'Yes%¤%1|¤|No%¤%0', title);
	return (returncode == '1');
}

function ae_confirm(callback = hw2, text, id = '', title = '') {
  ae_prompt(callback, `hidden%¤%${text}%¤%`, 'Yes%¤%1|¤|No%¤%0', id, title);
}

function ae_confirm_yes_no(callback = hw2, text, id = '', title = '') {
  ae_confirm(callback, text, id, title); // Samma
}

function ae$(id) {
  return document.getElementById(id);
}
/*
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
*/
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
/*
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
            ae_clk(0);
        }
        else
        {
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
	//ovrl.innerHTML=' ';
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
			span.innerHTML+='<input type="checkbox" id="id_jspopup_input_'+i+'" value="'+field_array[1]+'" '+checked+' onkeydown="return button_keycheck(event);"/> '+field_array[1];
		}
        else if (thistype=='hidden')
        {
            span.innerHTML+='<input type="text" id="id_jspopup_input_'+i+'" value="'+field_array[2]+'" class="aep_hidden_text" onkeydown="return button_keycheck(event);"/>';
        }
        else if (thistype=='textarea')
        {
			span.innerHTML+='<textarea rows="1" wrap="off" id="id_jspopup_input_'+i+'" class="aep_text" style="overflow:hidden;" onkeydown="return button_keycheck(event);"/>'+field_array[2]+'</idetextarea>';
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

	ae$('aep_buttons').innerHTML='';
	for (i=btns_array.length-1; i>=0; i--) {
		var btn_array=btns_array[i].split('%¤%');
		ae$('aep_buttons').innerHTML+='<div class="inside_menu" style="float:right;clear:left;"><a href="#" style="border:1px solid transparent;" name="button_name_'+btn_array[1]+'" id="button_id_'+i+'" class="btn" onclick="ae_clk(\''+btn_array[1]+'\');"> '+btn_array[0]+' </a></div>';
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
    showDiv('aep_win');//showdivs('aep_win',100);
}

function ae_clk(m)
{
    hideDiv('aep_win');//hidedivs('aep_win',100);
    if (!ae_cb)
    {
        return;
    }
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
*/
function button_keycheck(e) {
  const evt = e || window.event;
  const key = evt.which || evt.keyCode;

  const getAllButtons = () => {
    const btns = [];
    let i = 0;
    while (ae$(`button_id_${i}`)) {
      btns.push(ae$(`button_id_${i}`));
      i++;
    }
    return btns;
  };

  const activeButton = getAllButtons().find(btn =>
    btn.style.borderStyle.toLowerCase().includes('dotted')
  );

  switch (key) {
    case 13: // Enter
      if (activeButton) {
        ae_clk(activeButton.name.slice(-1));
      } else if (ae$('button_id_0')) {
        ae_clk(ae$('button_id_0').name.slice(-1));
      }
      return false;

    case 27: // Escape
      const cancelBtn = document.getElementsByName('button_name_0')[0] || ae$('button_id_0');
      if (cancelBtn) {
        ae_clk(cancelBtn.name.slice(-1));
      }
      return false;

    case 37: // Left arrow
    case 39: // Right arrow
      const buttons = getAllButtons();
      if (buttons.length < 2) return true;

      let idx = buttons.findIndex(btn =>
        btn.style.borderStyle.toLowerCase().includes('dotted')
      );

      buttons[idx].style.borderStyle = 'solid';
      buttons[idx].style.borderColor = 'transparent';

      if (key === 37) idx = (idx - 1 + buttons.length) % buttons.length;
      if (key === 39) idx = (idx + 1) % buttons.length;

      buttons[idx].style.borderStyle = 'dotted';
      buttons[idx].style.borderColor = '#000';
      return false;

    default:
      return true;
  }
}

async function ae_prompt_async(fields, buttons, title = document.title, id = 'current_dialog_id') {
    const { returncode, value } = await ae_promptAsync(fields,buttons,title,id);

	if (returncode !== '1') return '';
	return value;
}

function ae_promptAsync(fields, buttons, title = document.title, id = 'current_dialog_id') {
  return new Promise(resolve => {
    ae_prompt((returncode, id, value) => {
      resolve({ returncode, id, value });
    }, fields, buttons, id, title);
  });
}

function ae_prompt(callback, fields, buttons, id = 'current_dialog_id', title = document.title) {
  ae_cb = callback;

  let ovrl = ae$('borderdiv');
  if (!ovrl) {
    ovrl = document.createElement('div');
    ovrl.id = 'borderdiv';
    document.body.appendChild(ovrl);
  }

  let ae_win = ae$('aep_win');
  if (!ae_win) {
    ae_win = document.createElement('div');
    ae_win.id = 'aep_win';
    ae_win.style.paddingBottom = '6px';
    (document.forms[0] || document.body).appendChild(ae_win);
  }

  ae_win.innerHTML = `
    <input type="hidden" id="current_dialog_id" value="${id}" />
    <div class="globalheader" id="aep_t">
      <div class="inside_menu_text" style="text-indent:8px;">${title}</div>
    </div>
    <div id="aep_w">
      <span id="aep_prompt"><br/></span>
      <div id="aep_buttons" style="padding-right:8px;"></div>
    </div>
  `;

	// Add inputs
	const promptContainer = ae$('aep_prompt');
	fields.split('|¤|').forEach((raw, i) => {
	  const [type, label, value] = raw.split('%¤%');
	  const span = document.createElement('span');
	
	  if (type !== 'checkbox' && type !== 'select') {
	    span.innerHTML = `${label}<br/>`;
	  }
	
	  let input;
	
	  if (type === 'checkbox') {
	    input = document.createElement('input');
	    input.type = 'checkbox';
	    input.id = `id_jspopup_input_${i}`;
	    input.checked = value === 'true' || value === 'checked';
	    input.value = label;
	    span.append(input);
	    span.innerHTML += ` ${label}`;
	  }
	
	  else if (type === 'textarea') {
	    input = document.createElement('textarea');
	    input.id = `id_jspopup_input_${i}`;
	    input.className = 'aep_text';
	    input.value = value || '';
	    input.style.overflow = 'hidden';
	    input.rows = 1;
	    span.appendChild(input);
	  }
	
	  else if (type === 'select') {
		  input = document.createElement('select');
		  input.id = `id_jspopup_input_${i}`;
		  input.className = 'aep_text';

		  (value || '').split(',').forEach(optionTextRaw => {
		    const option = document.createElement('option');
		    const isSelected = optionTextRaw.endsWith('*');
		    const optionText = isSelected ? optionTextRaw.slice(0, -1) : optionTextRaw;
		
		    option.value = optionText;
		    option.textContent = optionText;
		    if (isSelected) option.selected = true;
		
		    input.appendChild(option);
		  });
		  span.innerHTML = `${label}<br/>`;
		  span.appendChild(input);
		}
			
	  else {
	    input = document.createElement('input');
	    input.type = type === 'hidden' ? 'text' : type;
	    input.id = `id_jspopup_input_${i}`;
	    input.value = value || '';
	    input.defaultValue = value || '';
	    input.className = type === 'hidden' ? 'aep_hidden_text' : 'aep_text';
	    span.appendChild(input);
	  }
	
	  input.addEventListener('keydown', button_keycheck);
	  if (type !== 'hidden' && type !== 'select') span.innerHTML += '<br/>';
	  promptContainer.appendChild(span);
	});

  // Add buttons
  const btnContainer = ae$('aep_buttons');
  buttons.split('|¤|').reverse().forEach((btn, i) => {
    const [label, retVal] = btn.split('%¤%');
    const btnDiv = document.createElement('div');
    btnDiv.className = 'inside_menu';
    btnDiv.style.float = 'right';
    btnDiv.style.clear = 'left';

    const a = document.createElement('a');
    a.href = '#';
    a.className = 'btn';
    a.name = `button_name_${retVal}`;
    a.id = `button_id_${i}`;
    a.textContent = ` ${label} `;
    a.style.border = '1px solid transparent';
    a.addEventListener('click', e => {
      e.preventDefault();
      ae_clk(retVal);
    });

    btnDiv.appendChild(a);
    btnContainer.appendChild(btnDiv);
  });

  // Set initial focus and highlight
  const defaultBtn = ae$('button_id_0');
  if (defaultBtn) {
    defaultBtn.style.borderStyle = 'dotted';
    defaultBtn.style.borderColor = '#000';
  }

  const defaultInput = ae$('id_jspopup_input_0');
  if (!defaultInput) {
    const fallback = document.createElement('input');
    fallback.type = 'text';
    fallback.className = 'aep_hidden_text';
    fallback.id = 'id_jspopup_input_0';
    fallback.addEventListener('keydown', button_keycheck);
    ae$('aep_prompt').appendChild(fallback);
  }

  showDiv('aep_win');
}
/*
function ae_clk(returnCode) {
  hideDiv('aep_win');

  if (!ae_cb) return;

  const id = ae$('current_dialog_id')?.value || 'no_id';
  const values = [];
  let i = 0;
  let input;

  while ((input = ae$(`id_jspopup_input_${i}`))) {
    values.push(addvalue(input));
    i++;
  }

  ae_cb(returnCode, id, values.join('|¤|'));
}
*/
function ae_clk(returncode) {
  let val = '';
  let sep = '';
  let i = 0;
  let input;

  while ((input = ae$('id_jspopup_input_' + i))) {
    if (input.type === 'checkbox') {
      val += sep + (input.checked ? 'true' : 'false');
    } else if (input.tagName === 'SELECT') {
      val += sep + input.options[input.selectedIndex].value;
    } else {
      val += sep + input.value;
    }
    sep = '|¤|';
    i++;
  }

  hideDiv('aep_win');
  ae_cb(returncode, ae$('current_dialog_id').value, val);
}

function addvalue(elem, separator = '') {
  if (!elem) return '';
  const value = elem.type === 'checkbox' ? (elem.checked ? 'true' : 'false') : elem.value;
  return separator + value;
}
/*
function showDiv(id) {
    const border = document.getElementById('borderdiv');
    const el = document.getElementById(id);
  
    if (border) border.style.visibility = 'visible';
    if (el) {
        el.style.visibility = 'visible';
        el.style.opacity = 1;

        if (id === 'aep_win') {
            const input = document.getElementById('id_jspopup_input_0');
            input?.focus();
            input?.select();
        }
    }
}
*/
function showDiv(id) {
  const border = document.getElementById('borderdiv');
  const el = document.getElementById(id);

  if (border) border.style.visibility = 'visible';
  if (el) {
    el.style.visibility = 'visible';
    el.style.opacity = 1;

    if (id === 'aep_win') {
      const input = document.getElementById('id_jspopup_input_0');
      if (input) {
        input.focus();
        if (input.tagName === 'INPUT' || input.tagName === 'TEXTAREA') {
          input.select();
        }
      }
    }
  }
}

function hideDiv(id) {
    const border = document.getElementById('borderdiv');
    const el = document.getElementById(id);
    if (border) border.style.visibility = 'hidden';
    if (el) {
        el.style.visibility = 'hidden';
        el.style.opacity = 0;
    }

    if (id === 'framediv' && document.getElementById('is_submit')?.value === '1') {
        setTimeout(() => main_submit('do_nothing'), 0);
    }
}

function fadeIn(id, targetOpacity = 1, duration = 300) {
    const el = document.getElementById(id);
    if (!el) return;

    el.style.visibility = 'visible';
    let opacity = 0;
    const step = 16 / duration;

    function animate() {
        opacity += step;
        if (opacity < targetOpacity) {
            el.style.opacity = opacity;
            requestAnimationFrame(animate);
        } else {
            el.style.opacity = targetOpacity;
            if (id === 'aep_win') {
                const input = document.getElementById('id_jspopup_input_0');
                input?.focus();
                input?.select();
            }
        }
    }

    animate();
}

function fadeOut(id, duration = 300) {
    const el = document.getElementById(id);
    if (!el) return;

    let opacity = parseFloat(getComputedStyle(el).opacity) || 1;
    const step = 16 / duration;

    function animate() {
        opacity -= step;
        if (opacity > 0) {
            el.style.opacity = opacity;
            requestAnimationFrame(animate);
        } else {
            el.style.opacity = 0;
            el.style.visibility = 'hidden';

            if (id === 'framediv' && document.getElementById('is_submit')?.value === '1') {
                main_submit('do_nothing');
            }
        }
    }

    animate();
}
