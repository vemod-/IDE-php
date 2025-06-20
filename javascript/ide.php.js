var savedCode='';
let editor = null;

// Kontroll om CodeMirror används
function isUsingCodeMirror() {
	return typeof editor !== 'undefined' && editor !== null;
}

function isTextAreaEditor() {
	const el = document.getElementById('code');
	return el && el.tagName && el.tagName.toLowerCase() === 'textarea';
}
// Läs innehåll
function codeValue() {
	if (isUsingCodeMirror()) {
		return editor.getValue();
	}
	const code = document.getElementById('code');
	return code ? code.value : '';
}
// Sätt innehåll
function setCodeValue(val) {
	if (isUsingCodeMirror()) {
		editor.setValue(val);
	} else {
		const code = document.getElementById('code');
		if (code) code.value = val;
	}
}
// Scrollposition
function getScrollPosition() {
	if (isUsingCodeMirror()) {
		let info = editor.getScrollInfo();
		return { scrollTop: info.top, scrollLeft: info.left };
	} else if (isTextAreaEditor()) {
		const code = document.getElementById('code');
		return {
			scrollTop: code?.scrollTop || 0,
			scrollLeft: code?.scrollLeft || 0,
		};
	}
}
// Cursorposition och markering
function getSelectionRange() {
	if (isUsingCodeMirror()) {
		const sel = editor.listSelections()?.[0] || {};
		const from = editor.indexFromPos(sel.anchor || { line: 0, ch: 0 });
		const to = editor.indexFromPos(sel.head || { line: 0, ch: 0 });
		return { selStart: Math.min(from, to), selEnd: Math.max(from, to) };
	} else if (isTextAreaEditor()) {
		const code = document.getElementById('code');
		if (code && typeof code.selectionStart === 'number') {
			return {
				selStart: code.selectionStart,
				selEnd: code.selectionEnd,
			};
		}
		return { selStart: 0, selEnd: 0 };
	}
}

function setSelectionRange(selectionStart, selectionEnd) {
	if (isUsingCodeMirror()) {
		const startPos = editor.posFromIndex(selectionStart);
		const endPos = editor.posFromIndex(selectionEnd);
		const scrollInfo = editor.getScrollInfo();
		editor.setSelection(startPos, endPos);
		editor.scrollTo(scrollInfo.left, scrollInfo.top);
		editor.focus();
	}
    else if (isTextAreaEditor()) {
		const input = document.getElementById('code');
		input.focus();
		input.setSelectionRange(selectionStart, selectionEnd);
	}
}

function setScrollPosition(scrollLeft,scrollTop) {
    if (isUsingCodeMirror()) {
        editor.scrollTo(scrollLeft, scrollTop);
	} else if (isTextAreaEditor()) {
        const elem = document.getElementById('code');
        elem.scrollTop = scrollTop;
        elem.scrollLeft = scrollLeft;
    }
}

function getSelectedCode() {
	var selectionRange = getSelectionRange();
	return codeValue().substring(selectionRange.selStart,selectionRange.selEnd);
}

function scrollIntoView(selectionStart, selectionEnd) {
	if (isUsingCodeMirror()) {
		editor.focus();
		const fromPos = editor.posFromIndex(selectionStart);
		const toPos = editor.posFromIndex(selectionEnd);
		editor.setSelection(fromPos, toPos);
		editor.scrollIntoView({ from: fromPos, to: toPos });
	} else if (isTextAreaEditor()) {
		const input = document.getElementById('code');
		input.focus();
		if (input.setSelectionRange) {
			input.setSelectionRange(selectionStart, selectionEnd);
			// Scrolla raden manuellt om det behövs
			const lineHeight = parseInt(getComputedStyle(input).lineHeight || '16', 10);
			const linesAbove = codeValue().substring(0, selectionStart).split('\n').length;
			input.scrollTop = Math.max(0, (linesAbove - 1) * lineHeight - 20);
		}
	}
}

function setFocus() {
	if (isUsingCodeMirror()) {
		editor.focus();
	} else if (isTextAreaEditor()) {
		const input = document.getElementById('code');
		input.focus();
	}
}

function toHtmlEntities(str) {
    return str.replace(/[^a-z0-9.\-_\s\t]/ig, c => `&#${c.charCodeAt(0)};`);
}

window.onbeforeunload=function()
{
    if (document.main_form)
    {
    	alert("beforeUnload");
        if ((document.main_form.action.value.length === 0) || (document.main_form.action.value === 'options') || (document.main_form.action.value === 'about'))
        {
            if (checkDirty())
            {
                return 'You are about to leave a page with a file that has not have been saved. Your changes will probably be lost and the results may not be predictable!!!!';
            }
        }
    }
    //window.onbeforeunload = null;
}

function main_submit(action) {
	serializeUI();
    if (isUsingCodeMirror()) {
        var elem = document.getElementById('code');
		elem.disabled = true;
		var newElem = document.createElement('textarea');
		newElem.name = 'code';
		newElem.style.display = 'none'; // Dölj det
		newElem.value = editor.getValue();;
		document.main_form.appendChild(newElem);
    }
    // Skicka formuläret
    document.main_form.action.value = action;
    document.main_form.submit();
}

function startdownload()
{
	document.getElementById('save_as_filename').value='./systemzip/idephp.zip';
	document.getElementById('action').value='set_download';
	document.getElementById('main_form').submit();
}

function serializeUI() {
    var php = new PHP_Serializer();
    var UIdata = {};
    if (uidataField && uidataField.value.length) {
        UIdata = php.unserialize(uidataField.value);
    }
	var scrollInfo = getScrollPosition();
	UIdata['scrollTop'] = scrollInfo.scrollTop;
	UIdata['scrollLeft'] = scrollInfo.scrollLeft;
	var selectionRange = getSelectionRange();
	UIdata['selStart'] = selectionRange.selStart;
	UIdata['selEnd'] = selectionRange.selEnd;
	const frameConsole = document.getElementById('frame-console');
	UIdata['console'] = (frameConsole.style.display != 'none');
	const searchWindow = document.getElementById('searchWindow');
	UIdata['searchwindow'] = (searchWindow.style.display != 'none');
	UIdata['searchPos'] = searchPos;
	UIdata['searchTerm'] = searchTerm;
	UIdata['replaceTerm'] = replaceTerm;
	UIdata['searchMatchCase'] = document.getElementById('matchcasecb').checked;
	UIdata['searchWholeWord'] = document.getElementById('wholewordcb').checked;
	UIdata['searchSelected'] = document.getElementById('searchselectedcb').checked;
	UIdata['searchSelectionStart'] = searchSelection.selStart;
	UIdata['searchSelectionEnd'] = searchSelection.selEnd;

    var uidataField = document.getElementById('UIdata');
    if (uidataField) {
        uidataField.value = php.serialize(UIdata);
	}
} 

function unserializeUI() {
    var php = new PHP_Serializer();
    var uidataField = document.getElementById('UIdata');
    if (!uidataField || !uidataField.value.length) return;

    var UIdata = php.unserialize(uidataField.value);
	if (UIdata['selStart'] !== null && UIdata['selEnd'] !== null) {
		setSelectionRange(UIdata['selStart'], UIdata['selEnd'], true);
	}
	if (UIdata['scrollLeft'] !== null && UIdata['scrollTop'] !== null) {
		setScrollPosition(UIdata['scrollLeft'], UIdata['scrollTop']);
	}
	if (UIdata['console'] !== null) {
		if (UIdata['console'] > 0) {
			console_toggle();
        }
    }
    if (UIdata['searchPos'] !== null) {
		searchPos = UIdata['searchPos'];
    }
    if (UIdata['searchTerm'] !== null) {
		searchTerm = UIdata['searchTerm'];
    }
    if (UIdata['replaceTerm'] !== null) {
		replaceTerm = UIdata['replaceTerm'];
    }
    if (UIdata['searchMatchCase'] !== null) {
		searchMatchCase = UIdata['searchMatchCase'];
		document.getElementById('matchcasecb').checked = searchMatchCase;
    }
    if (UIdata['searchWholeWord'] !== null) {
		searchWholeWord = UIdata['searchWholeWord'];
		document.getElementById('wholewordcb').checked = searchWholeWord;
    }
    if (UIdata['searchSelected'] !== null) {
		searchSelected = UIdata['searchSelected'];
		document.getElementById('searchselectedcb').checked = searchSelected;
    }
    if (UIdata['searchSelection'] !== null) {
		searchSelection.selStart = UIdata['searchSelectionStart'];
    }
    if (UIdata['searchSelection'] !== null) {
		searchSelection.selEnd = UIdata['searchSelectionEnd'];
    }
    if (UIdata['searchwindow'] !== null) {
		if (UIdata['searchwindow'] > 0) {
			showSearchWindow();
			searcAndDisplay();
        }
    }
}

var sel_line_num=-1;

function syncEditor(UIstr) {
	const UIfield = document.createElement('input');
	UIfield.id = 'UIdata';
	UIfield.name = 'UIdata';
	UIfield.type = 'hidden';
	UIfield.value = UIstr;
	document.main_form.appendChild(UIfield);
	if (Number(document.getElementById('change_counter').value) === 0) {
        savedCode = codeValue();
    }
    if (isUsingCodeMirror()) {
		if (!editor.__eventsSynced) {
			editor.on("keydown", (cm, evt) => catchTab(evt));
			editor.on("change", () => checkDirty());
			editor.on("cursorActivity", () => checkDirty());
			editor.on("focus", () => checkDirty());
			editor.on("refresh", () => checkDirty());
			editor.__eventsSynced = true; // markera som färdig
		}
	    let gutterStartLine = null;
		editor.getWrapperElement().addEventListener('mousedown', function (e) {
			if (!e.target.classList.contains('CodeMirror-linenumber')) return;
			e.preventDefault(); // Förhindra textmarkering
			const line = editor.lineAtHeight(e.clientY, 'client');
			editor.setSelection(
					{ line: line, ch: 0 },
					{ line: line , ch: Infinity }
				);
			gutterStartLine = line;
			const onMouseMove = function (e2) {
				const currentLine = editor.lineAtHeight(e2.clientY, 'client');
				const from = Math.min(gutterStartLine, currentLine);
				const to = Math.max(gutterStartLine, currentLine) + 1;
				editor.setSelection(
					{ line: from, ch: 0 },
					{ line: to, ch: 0 }
				);
			};
			const onMouseUp = function () {
				document.removeEventListener('mousemove', onMouseMove);
				document.removeEventListener('mouseup', onMouseUp);
				setFocus();
			};
			document.addEventListener('mousemove', onMouseMove);
			document.addEventListener('mouseup', onMouseUp);
		});
    }
    else if (isTextAreaEditor()) {
		const elem = document.getElementById('code');
		const elem1 = document.getElementById('code_numbers');
		elem.addEventListener('keydown', evt => catchTab(evt));
		elem.addEventListener('keyup', checkDirty);
		elem.addEventListener('mouseup', checkDirty);
		elem.addEventListener('change', checkDirty);
		elem.addEventListener('focus', checkDirty);
		if (elem1) {
			elem.addEventListener('scroll', () => {
				elem1.style.top = (-elem.scrollTop) + 'px';
			});
			elem1.onmousedown = function (evt) {
				sel_line_num = line_number(evt, this);
				let splitbg = document.getElementById('splitter_bg');
				if (!splitbg) {
					splitbg = document.createElement('div');
					splitbg.id = 'splitter_bg';
					splitbg.innerHTML = ' ';
					document.body.appendChild(splitbg);
				}

				splitbg.style.zIndex = 8000;
				this.parentNode.style.zIndex = 8001;
				document.addEventListener("mousemove", lines_dragGo, true);
				document.addEventListener("mouseup", lines_dragStop, true);
				evt.preventDefault();
				elem.focus();
			};
		}
		elem.focus();
	}
	unserializeUI();
	syncSearchWindow();
	syncConsoleWindow();
	checkDirty();
}

function syncConsoleWindow() {
	const frameConsole = document.getElementById('frame-console');
	const iframe = document.getElementById('evaluationwindow');
	const closebutton = document.getElementById('consoleclosebutton');
	closebutton.style.marginRight = '8px';
	closebutton.onclick = () => {
		console_toggle();
	};
	iframe.onload = function () {
	    try {
	        const iframeWindow = iframe.contentWindow;
	        const realLog = iframeWindow.console.log;
	        const realError = iframeWindow.console.error;
	
	        iframeWindow.console.log = (...args) => {
			    appendLog('log', args.join(' '));
			    realLog.apply(iframeWindow.console, args);
			};
			
			iframeWindow.console.warn = (...args) => {
			    appendLog('warning', args.join(' '));
			    realLog.apply(iframeWindow.console, args);
			};

            iframeWindow.console.debug = (...args) => {
			    appendLog('debug', args.join(' '));
			    realLog.apply(iframeWindow.console, args);
			};
			
			iframeWindow.console.info = (...args) => {
			    appendLog('info', args.join(' '));
			    realLog.apply(iframeWindow.console, args);
			};

			iframeWindow.console.error = (...args) => {
			    appendLog('error', args.join(' '));
			    realError.apply(iframeWindow.console, args);
			};
			
			iframeWindow.onerror = (message, source, lineno, colno, error) => {
			    appendLog('error', message, lineno, colno, source);
			};
				
	    } catch (e) {
		    appendLog('error', 'Can\'t log from (cross-origin): ' + e.message);
	    }
	    setConsoleMenuCheckmark();
	};    
}
/*
function appendLog(type, message, lineno, colno, source = null) {
    const frameConsole = document.getElementById('frame-console');
    const line = document.createElement('div');
    line.className = `log-line ${type}`;

    const sourceText = source ? ` <span class="log-source">${source}:</span>` : '';

    if (typeof lineno === 'number') {
        line.innerHTML = `
            ${getIcon(type)}
            <span class="log-msg">${escapeHtml(message)}</span>
            ${sourceText}<span class="log-jump">${lineno}&nbsp;</span>
        `;

        line.querySelector('.log-jump').onclick = () => {
            //if (isCurrentSource(source)) {
                setSelectionRangeFromLine(lineno);
            //} else {
            //    alert(`Fel kommer från annan fil: ${source}`);
            //}
        };
    } else {
        line.innerHTML = `
            ${getIcon(type)} 
            <span class="log-msg">${escapeHtml(message)}</span>
            ${sourceText}
        `;
    }
    var prev = frameConsole.lastChild;
    if (prev) {
		if (line.innerHTML == prev.innerHTML) {
			return;
	    }
	}
    frameConsole.appendChild(line);
}
*/
const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '/');

function appendLog(type, message, lineno, colno, source = '') {
    const frameConsole = document.getElementById('frame-console');
    const logKey = `${type}:${message}:${lineno}:${colno}:${source}`;

    const prev = frameConsole.lastElementChild;

    if (prev && prev.dataset.key === logKey) {
        // Redan samma fel – öka räknare
        let countSpan = prev.querySelector('.log-count');
        let count = parseInt(countSpan.textContent, 10);
        count++;
        countSpan.textContent = count;
        countSpan.style.display = 'block';
        return;
    }

    // Ny rad
    const line = document.createElement('div');
    line.className = `log-line ${type}`;
    line.dataset.key = logKey;
    
    if (source.startsWith(baseUrl)) {
	    source = './' + source.substring(baseUrl.length);
	}
	
    const icon = getIcon(type);
    const escapedMsg = escapeHtml(message);
    const sourceInfo = source ? `<span class="log-jump"  style="color:blue;cursor:pointer;">${escapeHtml(source)}:</span>` : '';
    const jump = (typeof lineno === 'number')
        ? `<span class="log-jump" style="color:blue;cursor:pointer;">${lineno}</span>`
        : '';

    line.innerHTML = `
        <span class="log-count" style="display:none;">1</span>
        ${icon} <span class="log-msg">${escapedMsg}</span><br>
        ${sourceInfo}${jump}
    `;

    if (lineno) {
        line.querySelector('.log-jump').onclick = () => {
            setSelectionRangeFromLine(lineno);
        };
    }
	if (source) {
	    const currentFileElement = document.getElementById('Current_filename');
		if (currentFileElement) {
			const currentFilename = document.getElementById('Current_filename').value;
			if (currentFilename.length) {
				if (currentFilename != source) {
			        line.querySelector('.log-jump').onclick = () => {
			            submit_file(source);
			        };
	            }
	        }
		}
	}

    frameConsole.appendChild(line);
}


// Hjälpmetoder
function getIcon(type) {
    return type === 'error' ? '🔴' : type === 'warn' ? '🟡' : '🟢';
}

function escapeHtml(text) {
    return text.replace(/[&<>"']/g, c =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]
    );
}

function console_toggle() {
    const frameConsole = document.getElementById('frame-console');
    const iframe = document.getElementById('evaluationwindow');
    if (frameConsole.style.display == 'none') {
	    iframe.style.height = '70%';
	    frameConsole.style.display = 'block';
    }
    else {
	    iframe.style.height = '100%';
	    frameConsole.style.display = 'none';	    
    }
    setConsoleMenuCheckmark();
}

const menu_checkmark = '&nbsp;&#10003;';

function setConsoleMenuCheckmark() {
    const consoleMenu = document.getElementById('menu_item_Console');
    const frameConsole = document.getElementById('frame-console');
    if (frameConsole.style.display == 'none') {
	    consoleMenu.innerHTML = '';
    }
    else {
    	consoleMenu.innerHTML = menu_checkmark;
    }
}

function setSelectionRangeFromLine(line) {
    if (typeof setSelectionRange !== 'function') return;
    const lineNum = parseInt(line, 10) - 1;
    var selStart = lineNumStartPos(lineNum);
    var selEnd = lineNumStartPos(lineNum + 1);
    setSelectionRange(selStart,selEnd);
    scrollIntoView(selStart,selEnd);
}

function lineNumStartPos(lineNumber) {
	const code = codeValue(); // hel sträng från textarea eller editor
	if (!code || lineNumber < 0) return 0;

	let pos = 0;
	let currentLine = 0;

	while (currentLine < lineNumber && pos !== -1) {
		pos = code.indexOf('\n', pos);
		if (pos === -1) return code.length; // om rad inte finns, gå till slutet
		pos++; // hoppa till nästa tecken efter \n
		currentLine++;
	}

	return pos;
}

function catchTab(e) {
    const evt = e || window.event;
    const code = document.getElementById('code');
    const key = evt.which || evt.keyCode;
    // Cmd/Ctrl + S (spara)
    if ((evt.ctrlKey || evt.metaKey) && key === 83) { // S
        evt.preventDefault();
        if (checkDirty()) {
        	main_submit('save');
        }
        return false;
    }

    // Cmd/Ctrl + F (sök)
    if ((evt.ctrlKey || evt.metaKey) && key === 70) { // F
        evt.preventDefault();
        search_editor(true);
        return false;
    }

    // Cmd/Ctrl + G (sök nästa)
    if ((evt.ctrlKey || evt.metaKey) && key === 71) { // G
        evt.preventDefault();
        search_editor(false);
        return false;
    }

    // Cmd/Ctrl + H (replace)
    if ((evt.ctrlKey || evt.metaKey) && key === 72) { // H
        evt.preventDefault();
        //replace_editor();
        search_editor(true);
        return false;
    }

	// Cmd/Ctrl + R (run)
	if ((evt.ctrlKey || evt.metaKey) && key === 82) { // R
        evt.preventDefault();
		main_form.phpnet.value=0;
		main_submit("eval");
		return false;
	}

    // Tab
    if (key === 9) {
        evt.preventDefault(); //  blockera fokusbyte
        const selectionRange = getSelectionRange();
        const lastNewline = codeValue().lastIndexOf('\n', selectionRange.selStart - 1);
		const startcol = selectionRange.selStart - (lastNewline + 1);
		setSelectionRange(selectionRange.selStart - startcol, selectionRange.selEnd);
		const selectedCode = getSelectedCode();
        if (selectionRange.selEnd - selectionRange.selStart === 0) {
			const currentline = selectedCode;
			let modified = tabLine(currentline,evt);
			replaceTextareaSelection(modified);
			const replaceLen = selectedCode.length - modified.length;
			setSelectionRange(selectionRange.selStart - replaceLen, selectionRange.selStart - replaceLen);
        }
        else {
			const lines = selectedCode.split('\n');
			let modified = lines.map(line => tabLine(line,evt)).join('\n');
			replaceTextareaSelection(modified);
			setSelectionRange(selectionRange.selStart - startcol, selectionRange.selStart - startcol + modified.length);
        }
        return false;
    }

    // Enter
    if (key === 13) {
        const selectionRange = getSelectionRange();
        const line_array = codeValue().substring(0, selectionRange.selEnd).split('\n');
        const match = line_array[line_array.length - 1].match(/^([ \t]+)/);
        if (match) {
            evt.preventDefault();
            replaceTextareaSelection("\n" + match[1]);
            return false;
        }
    }
    
    //Escape
    if (key === 27) {
	    hideSearchWindow()
    }

    return true;
}

function tabLine(line, evt) {
	const TAB = '\t';
	if (evt.shiftKey) {
		if (line.startsWith(TAB)) return line.slice(TAB.length);
		if (line.startsWith('    ')) return line.slice(4);
		return line;
	} else {
		return TAB + line;
	}
}
// drop-dowm menu
function showLayer(sub_id) {
    const menu = document.getElementById(sub_id);
    if (!menu) return;
    menu.style.visibility = "visible";
    menu.style.display = "block";
    menu.style.zIndex = 99999;
}

function hideLayer(sub_id) {
    const menu = document.getElementById(sub_id);
    if (!menu) return;
    menu.style.display = "none";
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
	const selectionRange = getSelectionRange();
	const currentCode = codeValue();
	const charcode = currentCode.charCodeAt(selectionRange.selStart);
	var info='';
	const infobar = document.getElementById('infobar');
	if (infobar)
	{
		const selection = getSelectedCode();
		const startarray = currentCode.substring(0,selectionRange.selStart).split('\n');
		const startcol = startarray[startarray.length-1].length;
		if (selection.length != 0)
		{
			//var rows = selection.substring_count('\n')+1;
			const rows = selection.split('\n').length;
			const endarray = currentCode.substring(0,selectionRange.selEnd).split('\n');
			const endcol = endarray[endarray.length-1].length;
			info+= rows+' x '+Math.abs(endcol-startcol)+'   ['+selection.length+'] ';
		}
		else
		{
			const startrow = startarray.length;
			const endarray = currentCode.split('\n');
			info+= startrow+' : '+startcol+' / '+endarray.length+' ';
		}
		infobar.innerHTML = info + '   '+charcode+' (0x'+decimalToHex(charcode,2)+')';
	}
	if (savedCode.length)
	{
		isdirty = (currentCode !== savedCode);
	}
	else
	{
		isdirty = (Number(document.getElementById('change_counter').value) > 0);
	}
	if (isdirty)
	{
		document.getElementById('change_counter').value++;
	}
	document.getElementById('dirty_p').innerHTML = save_image(isdirty,true);
	if (infobar)
	{
		var ccodestr='';
		if (charcode)
		{
			ccodestr = charcode+' (0x'+decimalToHex(charcode,2)+')';
		}
		infobar.innerHTML = save_image(isdirty,false)+'   '+info+'   '+ccodestr;
	}
	return isdirty;
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
    return false;
}

function replaceTextareaSelection(str) {
	var selectionRange = getSelectionRange();
	setCodeValue(codeValue().substring(0, selectionRange.selStart) + str + codeValue().substring(selectionRange.selEnd));
	const newPos = selectionRange.selStart + str.length;
	setSelectionRange(newPos, newPos);
	checkDirty();
}
//menu functions filetable
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
    document.getElementById('some_file_name').value=""+file+"";
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
//file menu functions
function save_as(file)
{
    ae_prompt(prompt_callback,'text%¤%Save as%¤%'+file,'OK%¤%1|¤|Cancel%¤%0','save_as');
}

function prompt_callback(returncode,id,value)
{
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
//eval menu functions
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
//search and replace
var searchPos=0;
var searchTerm='';
var searchMatchCase=false;
var searchWholeWord=false;
var replaceTerm='';
var searchSelection = { selStart: 0, selEnd: 0 };
var searchSelected=false;

RegExp.escape = function(text) {
  const specials = /[.*+?^${}()|[\]\\]/g;
  return text.replace(specials, '\\$&');
}

function hideSearchWindow() {
    let codewrapper = document.getElementById('codewrapper');
	let searchWindow = document.getElementById('searchWindow');
    searchWindow.style.display = 'none';
	codewrapper.style.height = '100%';
	setFocus();
}

function showSearchWindow() {
	let codewrapper = document.getElementById('codewrapper');
	let searchWindow = document.getElementById('searchWindow');
	const searchText = document.getElementById('searchText');
	searchWindow.style.display = 'block';
	codewrapper.style.height = '70%';
	searchText.value = searchTerm || '';
}

function syncSearchWindow() {
    let codewrapper = document.getElementById('codewrapper');
	let searchWindow = document.getElementById('searchWindow');
	const closeButton = document.getElementById('closesearchbutton');
	closeButton.style.marginRight = '8px';
	closeButton.onclick = () => {
		hideSearchWindow()
	};
	const inputTextarea = document.getElementById('searchText');
	const replaceTextarea = document.getElementById('replaceText');
	inputTextarea.addEventListener('keydown', evt => {
		if (evt.key === 'Enter') {
			evt.preventDefault();
	        search_callback(3,'','');
		}
		if (evt.key === 'Escape') {
			evt.preventDefault();
			hideSearchWindow()
        }
        if (evt.key === 'Tab') {
			evt.preventDefault();
			replaceTextarea.focus();
		}
	});
	document.getElementById('searchnextbutton').onclick = () => {
		search_editor(false);
		setFocus();
	};
	replaceTextarea.addEventListener('keydown', evt => {
		if (evt.key === 'Enter') {
			evt.preventDefault();
	        search_callback(3,'','');
		}
		if (evt.key === 'Escape') {
			evt.preventDefault();
			hideSearchWindow()
        }
        if (evt.key === 'Tab') {
			evt.preventDefault();
			inputTextarea.focus();
		}
	});
	document.getElementById('replacefindbutton').onclick = () => {
		if (replaceTextarea.value.length) {
			replaceTextareaSelection(replaceTextarea.value);
			search_editor(false);
			setFocus();
        }
	};
	document.getElementById('replaceallbutton').onclick = () => {
		replace_callback(3,'','');
		setFocus();
	};
}

function search_editor(showDialog) {
	if (!showDialog) {
		search_callback(3, '', '');
		return;
	}
	searchSelection = getSelectionRange();
	if (searchSelection.selStart !== searchSelection.selEnd) {
		searchTerm = getSelectedCode().split('\n')[0];
	}
	searchPos = 0;
	showSearchWindow();
	const searchText = document.getElementById('searchText');
	searchText.focus();
}

function highlightMatchPreserveCase(line, searchTerm) {
    const escapedLine = escapeHtml(line);
    // Escape HTML i söktermen också, för korrekt regex
    const escapedTerm = escapeHtml(searchTerm);
    const regex = new RegExp(escapedTerm, 'gi');
    return escapedLine.replace(regex, match => `<mark>${match}</mark>`);
}

function search_callback(returncode,id,value)
{
    const codevalue = codeValue();
    if (returncode == 1)
    {
        var value_array = value.split('|¤|');
        searchTerm=value_array[0];
        searchMatchCase=(value_array[1] == 'true') ? true:false;
        searchWholeWord=(value_array[2] == 'true') ? true:false;
        searchSelected=(value_array[3] == 'true') ? true:false;
        if (!searchSelected)
        {
        	searchSelection = { selStart: 0, selEnd: codevalue.length };
        }
        searchPos = 0;//searchSelection.selStart;
    }
    if (returncode == 0)
    {
        return;
    }
    if (returncode == 3) {
	    searchTerm = document.getElementById('searchText').value;
        searchMatchCase = document.getElementById('matchcasecb').checked;
	    searchWholeWord = document.getElementById('wholewordcb').checked;
	    if (!document.getElementById('searchselectedcb').checked)
        {
        	searchSelection = { selStart: 0, selEnd: codevalue.length };
        }
    }
    if (searchTerm != '' && searchTerm != null)
    {
    /*
        var RegExpStr=RegExp.escape(searchTerm);
        var RegExpModifier='';
		if (searchWholeWord) {
		    RegExpStr = '\\b' + RegExpStr + '\\b';
		}
		if (!searchMatchCase)
        {
            RegExpModifier='i';
        }
        let regex = new RegExp(RegExpStr, RegExpModifier + 'g'); // global match
		let matches = [...codevalue.substring(searchSelection.selStart,searchSelection.selEnd).matchAll(regex)];
		if (searchPos >= matches.length) {
			searchPos = 0;
        }
        if (searchPos < 0) {
			searchPos = matches.length - 1;
        }
        const stats = document.getElementById('searchstats');		    
        if (stats) {
			stats.textContent = (searchPos + 1) + ' / ' + matches.length;
        }
        const showAllDiv = document.getElementById('hitlist');
        if (showAllDiv) {
        	showAllDiv.innerHTML = '';
	        let lines = codevalue.split('\n');
			let lineStartIndices = [];
			let index = 0;
			// Skapa karta med var varje rad börjar i hela koden
			for (let line of lines) {
			    lineStartIndices.push(index);
			    index += line.length + 1; // +1 för \n
			}
			// Gå igenom alla matcher
			let detailedMatches = matches.map((m,i) => {
			    let matchStart = m.index + searchSelection.selStart;
			    let matchEnd = matchStart + m[0].length;
			
			    // Hitta vilken rad matchen tillhör
			    let lineNumber = lineStartIndices.findIndex((start, i) => {
			        return matchStart >= start && matchStart < (lineStartIndices[i + 1] ?? Infinity);
			    });
			    const showAllList = document.getElementById('hitlist');
			    const escapedTerm = RegExp.escape(searchTerm);
				const flags = searchMatchCase ? 'g' : 'gi';
				const highlightRegex = new RegExp(escapedTerm, flags);
				
				const item = document.createElement('li');
				item.className = 'search-line';
				item.onclick = () => {
					console.log("single");
					searchPos = i;
					search_callback(3, '', '');
				};
				const numberDiv = document.createElement('div');
				numberDiv.innerHTML = lineNumber + 1;
				numberDiv.style.minWidth = '32px';
				numberDiv.style.textAlign = 'right';
				numberDiv.style.backgroundColor = '#E0E4EA';
				numberDiv.style.marginRight = '8px';
				
				const lineDiv = document.createElement('div');
				const highlightedLine = highlightMatchPreserveCase(lines[lineNumber], searchTerm);
				lineDiv.innerHTML = highlightedLine;
				if (searchPos == i) {
					item.style.backgroundColor = 'lightblue';
                }

				item.appendChild(numberDiv);
				item.appendChild(lineDiv);
				showAllList.appendChild(item);
			    let lineText = lines[lineNumber];

			    return {
			        start: matchStart,
			        end: matchEnd,
			        lineNumber: lineNumber + 1, // 1-baserat
			        lineText
			    };
			});
	    }
	    */
	    var matches = searcAndDisplay();
		if (matches.length === 0) {
		    ae_alert(searchTerm + ' not found');
		} else {
            const searchMatches = matches.map(m => ({
			    start: m.index,
			    end: m.index + m[0].length
			}));
			let match = searchMatches[searchPos];
		    scrollIntoView(match.start, match.end);
		    searchPos++;
		}
    }
    setFocus();
}

function searcAndDisplay() {
    const codevalue = codeValue();
    var RegExpStr=RegExp.escape(searchTerm);
    var RegExpModifier='';
	if (searchWholeWord) {
	    RegExpStr = '\\b' + RegExpStr + '\\b';
	}
	if (!searchMatchCase)
    {
        RegExpModifier='i';
    }
    let regex = new RegExp(RegExpStr, RegExpModifier + 'g'); // global match
	let matches = [...codevalue.substring(searchSelection.selStart,searchSelection.selEnd).matchAll(regex)];
	if (searchPos >= matches.length) {
		searchPos = 0;
    }
    if (searchPos < 0) {
		searchPos = matches.length - 1;
    }
    const stats = document.getElementById('searchstats');		    
    if (stats) {
		stats.textContent = (searchPos + 1) + ' / ' + matches.length;
    }
    const showAllDiv = document.getElementById('hitlist');
    if (showAllDiv) {
    	showAllDiv.innerHTML = '';
        let lines = codevalue.split('\n');
		let lineStartIndices = [];
		let index = 0;
		// Skapa karta med var varje rad börjar i hela koden
		for (let line of lines) {
		    lineStartIndices.push(index);
		    index += line.length + 1; // +1 för \n
		}
		// Gå igenom alla matcher
		let detailedMatches = matches.map((m,i) => {
		    let matchStart = m.index + searchSelection.selStart;
		    let matchEnd = matchStart + m[0].length;
		
		    // Hitta vilken rad matchen tillhör
		    let lineNumber = lineStartIndices.findIndex((start, i) => {
		        return matchStart >= start && matchStart < (lineStartIndices[i + 1] ?? Infinity);
		    });
		    const showAllList = document.getElementById('hitlist');
		    const escapedTerm = RegExp.escape(searchTerm);
			const flags = searchMatchCase ? 'g' : 'gi';
			const highlightRegex = new RegExp(escapedTerm, flags);
			
			const item = document.createElement('li');
			item.className = 'search-line';
			item.onclick = () => {
				console.log("single");
				searchPos = i;
				search_callback(3, '', '');
			};
			const numberDiv = document.createElement('div');
			numberDiv.innerHTML = lineNumber + 1;
			numberDiv.style.minWidth = '32px';
			numberDiv.style.textAlign = 'right';
			numberDiv.style.backgroundColor = '#E0E4EA';
			numberDiv.style.marginRight = '8px';
			
			const lineDiv = document.createElement('div');
			const highlightedLine = highlightMatchPreserveCase(lines[lineNumber], searchTerm);
			lineDiv.innerHTML = highlightedLine;
			if (searchPos == i) {
				item.style.backgroundColor = 'lightblue';
            }

			item.appendChild(numberDiv);
			item.appendChild(lineDiv);
			showAllList.appendChild(item);
		    let lineText = lines[lineNumber];

		    return {
		        start: matchStart,
		        end: matchEnd,
		        lineNumber: lineNumber + 1, // 1-baserat
		        lineText
		    };
		});
    }
    return matches;
}

function replace_editor()
{
	searchSelection = getSelectionRange();
	if (searchSelection.selStart != searchSelection.selEnd)
	{
		searchTerm = getSelectedCode().split('\n')[0];
	}
    var caseChecked=(searchMatchCase ? 'checked':'');
    var wordChecked=(searchWholeWord ? 'checked':'');
    var selectedChecked=(searchSelected ? 'checked':'');
    ae_prompt(replace_callback,'textarea%¤%Search for%¤%'+searchTerm+'|¤|textarea%¤%Replace with%¤%'+replaceTerm+'|¤|checkbox%¤%Match case%¤%'+caseChecked+'|¤|checkbox%¤%Whole word%¤%'+wordChecked+'|¤|checkbox%¤%Only inside selection%¤%'+selectedChecked,'OK%¤%1|¤|Cancel%¤%0');
}

function replace_callback(returncode,id,value)
{
    var replacements=0;
    var codevalue = codeValue();
    if (returncode == 1)
    {
        var value_array=value.split('|¤|');
        searchTerm=value_array[0];
        replaceTerm=value_array[1];
        searchMatchCase=(value_array[2] == 'true') ? true:false;
        searchWholeWord=(value_array[3] == 'true') ? true:false;
        searchSelected=(value_array[4] == 'true') ? true:false;
        if (!searchSelected)
	    {
			searchSelection = { selStart: 0, selEnd: codevalue.length };
	    }
    }
    if (returncode == 0)
    {
        return;
    }
    if (returncode == 3) {
	    searchTerm = document.getElementById('searchText').value;
	    replaceTerm = document.getElementById('replaceText').value;
        searchMatchCase = document.getElementById('matchcasecb').checked;
	    searchWholeWord = document.getElementById('wholewordcb').checked;
	    if (!document.getElementById('searchselectedcb').checked)
        {
        	searchSelection = { selStart: 0, selEnd: codevalue.length };
        }
    }
    //searchPos = searchSelection.selStart;
    if (searchTerm != '' && searchTerm != null && replaceTerm != '' && replaceTerm != null)
    {
        var RegExpStr=RegExp.escape(searchTerm);
        var RegExpModifier='';
        if (searchWholeWord)
		if (searchWholeWord) {
		    RegExpStr = '\\b' + RegExpStr + '\\b';
		}
        if (!searchMatchCase)
        {
            RegExpModifier='i';
        }
        let regex = new RegExp(RegExpStr, RegExpModifier + 'g'); // global match
		let matches = [...codevalue.substring(searchSelection.selStart,searchSelection.selEnd).matchAll(regex)];
		
		if (matches.length === 0) {
		    ae_alert(searchTerm + ' not found');
		} else {
		    for (let i = matches.length - 1; i >= 0; i--) {
		        let match = matches[i];
		        let start = match.index;
		        let end = start + match[0].length;
		
		        codevalue = codevalue.substring(0, start + searchSelection.selStart) + replaceTerm + codevalue.substring(end + searchSelection.selStart);
		        replacements++;
		    }
		
		    setCodeValue(codevalue);
		    checkDirty();
		    ae_alert(toHtmlEntities(searchTerm) + ' was replaced ' + replacements + ' times');
		    //setTimeout(() => document.getElementById('code').focus(), 0);
		    setFocus();
		}
		
    }
}

//For textarea editor line numbers
function lines_dragGo(event)
{
    var elem=document.getElementById('code');
    var elem1=document.getElementById('code_numbers');
    select_lines(elem,sel_line_num,line_number(event,elem1));
    event.preventDefault();
}

function lines_dragStop(event)
{
    var elem=document.getElementById('code');
    var elem1=document.getElementById('code_numbers');
    select_lines(elem,sel_line_num,line_number(event,elem1));
    sel_line_num=-1;
	document.removeEventListener("mousemove", lines_dragGo,   true);
	document.removeEventListener("mouseup",   lines_dragStop, true);
    elem1.parentNode.style.zIndex='auto';
    var splitbg=document.getElementById('splitter_bg');
    splitbg.style.visibility='hidden';
    checkDirty();
}

function line_number(event,elem)
{
	var y = event.clientY + window.scrollY;
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
    var lines=selcode.split('\n');
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
    setSelectionRange(selS,selE-1,true);
}