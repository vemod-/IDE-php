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

function usesProjects() {
    return typeof findProject === 'function' && currentProject !== undefined;
}

function currentProjectFiles() {
    if (!usesProjects()) return [];

    const project = findProject(currentProject);
    return project ? project.files.map(f => f.path) : [];
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

window.addEventListener('beforeunload', () => {
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
});

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
	localStorage.setItem('console', (frameConsole.style.display != 'none'));
	localStorage.setItem('evalheight', document.getElementById('eval_cell').style.height);
	localStorage.setItem('consoleheight', document.getElementById('console_cell').style.height);
	const searchWindow = document.getElementById('searchWindow');
	localStorage.setItem('searchwindow', (searchWindow.style.display != 'none'));
	localStorage.setItem('codeheight', document.getElementById('code_cell').style.height);
	localStorage.setItem('searchheight', document.getElementById('search_cell').style.height)
	localStorage.setItem('searchPos', searchPos);
	localStorage.setItem('searchTerm', searchTerm);
	localStorage.setItem('replaceTerm', replaceTerm);
	localStorage.setItem('searchMatchCase', document.getElementById('matchcasecb').checked);
	localStorage.setItem('searchWholeWord', document.getElementById('wholewordcb').checked);
	localStorage.setItem('searchSelected', document.getElementById('searchselectedcb').checked);
	localStorage.setItem('searchInProject', document.getElementById('searchinprojectcb').checked);
	localStorage.setItem('searchSelectionStart', searchSelection.selStart);
	localStorage.setItem('searchSelectionEnd', searchSelection.selEnd);

    var uidataField = document.getElementById('UIdata');
    if (uidataField) {
        uidataField.value = php.serialize(UIdata);
	}
}

function unserializeUI() {
	if (localStorage.getItem('console') !== null) {
		if (localStorage.getItem('console') == 'true') {
			console_toggle();
			document.getElementById('eval_cell').style.height = localStorage.getItem('evalheight');
			document.getElementById('console_cell').style.height = localStorage.getItem('consoleheight')
        }
    }
    if (localStorage.getItem('searchPos') !== null) {
		searchPos = Number(localStorage.getItem('searchPos'));
    }
    if (localStorage.getItem('searchTerm') !== null) {
		searchTerm = localStorage.getItem('searchTerm');
    }
    if (localStorage.getItem('replaceTerm') !== null) {
		replaceTerm = localStorage.getItem('replaceTerm');
    }
    if (localStorage.getItem('searchMatchCase') !== null) {
		searchMatchCase = (localStorage.getItem('searchMatchCase')  == 'true');
		document.getElementById('matchcasecb').checked = searchMatchCase;
    }
    if (localStorage.getItem('searchWholeWord') !== null) {
		searchWholeWord = (localStorage.getItem('searchWholeWord')  == 'true');
		document.getElementById('wholewordcb').checked = searchWholeWord;
    }
    if (localStorage.getItem('searchSelected') !== null) {
		searchSelected = (localStorage.getItem('searchSelected')  == 'true');
		document.getElementById('searchselectedcb').checked = searchSelected;
    }
    if (localStorage.getItem('searchInProject') !== null) {
		searchInProject = (localStorage.getItem('searchInProject')  == 'true');
		document.getElementById('searchinprojectcb').checked = searchInProject;
    }
    if (localStorage.getItem('searchSelectionStart') !== null) {
		searchSelection.selStart = Number(localStorage.getItem('searchSelectionStart'));
    }
    if (localStorage.getItem('searchSelectionEnd') !== null) {
		searchSelection.selEnd = Number(localStorage.getItem('searchSelectionEnd'));
    }
    if (localStorage.getItem('searchwindow') !== null) {
		if (localStorage.getItem('searchwindow') == 'true') {
			showSearchWindow();
			if (searchTerm != '' && searchTerm != null)
		    {
				document.getElementById('code_cell').style.height = localStorage.getItem('codeheight');
				document.getElementById('search_cell').style.height = localStorage.getItem('searchheight')
				searchAndDisplay(codeValue(),searchSelection,document.getElementById('hitlist'));
				markSearchItem();
				searchStats();
				searchProjectFiles();
	        }
        }
    }

    var php = new PHP_Serializer();
    var uidataField = document.getElementById('UIdata');
    if (!uidataField || !uidataField.value.length) return;

    var UIdata = php.unserialize(uidataField.value);
	if (UIdata['selStart'] !== null && UIdata['selEnd'] !== null) {
		setSelectionRange(UIdata['selStart'], UIdata['selEnd'], true);
	}
	if (UIdata['scrollLeft'] === undefined && UIdata['scrollTop'] === undefined) {
		if (UIdata['selStart'] !== null && UIdata['selEnd'] !== null) {
			scrollIntoView(UIdata['selStart'], UIdata['selEnd']);
        }
    }
	else if (UIdata['scrollLeft'] !== null && UIdata['scrollTop'] !== null) {
		setScrollPosition(UIdata['scrollLeft'], UIdata['scrollTop']);
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
			const currentFilename = currentFileElement.value;
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
    const iframe = document.getElementById('eval_cell');
    const console = document.getElementById('console_cell')
    if (frameConsole.style.display == 'none') {
	    iframe.style.height = '70%';
	    console.style.height = '30%';
	    frameConsole.style.display = 'block';
    }
    else {
	    iframe.style.height = '100%';
	    console.style.height = '0%';
	    frameConsole.style.display = 'none';
    }
    setConsoleMenuCheckmark();
}

const menu_checkmark = '&nbsp;&#10003;';

function setConsoleMenuCheckmark() {
    const consoleMenu = document.getElementById('menu_item_console');
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

function submit_file(file,selection = "")
{
    document.getElementById('some_file_name').value=""+file+"";
    if (selection != "") {
	    const someSelection = document.getElementById('some_file_selection');
	    var php = new PHP_Serializer();
	    var UIdata = {};
		UIdata['selStart'] = selection.selStart;
		UIdata['selEnd'] = selection.selEnd;
		someSelection.value = php.serialize(UIdata);
	}

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
var searchInProject=false;

RegExp.escape = function(text) {
  const specials = /[.*+?^${}()|[\]\\]/g;
  return text.replace(specials, '\\$&');
}

function hideSearchWindow() {
    let coderow = document.getElementById('code_cell');
    let searchrow = document.getElementById('search_cell');
	let searchWindow = document.getElementById('searchWindow');
    searchWindow.style.display = 'none';
    searchrow.style.height = '0%';
	coderow.style.height = '100%';
	setFocus();
}

function showSearchWindow() {
    let coderow = document.getElementById('code_cell');
    let searchrow = document.getElementById('search_cell');
	let searchWindow = document.getElementById('searchWindow');
	const searchText = document.getElementById('searchText');
	if (searchWindow.style.display == 'none') {
		searchWindow.style.display = 'block';
		coderow.style.height = '70%';
		searchrow.style.height = '30%';
    }
	searchText.value = searchTerm || '';
}

function syncSearchWindow() {
    //let codewrapper = document.getElementById('codewrapper');
	//let searchWindow = document.getElementById('searchWindow');
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
	        search_next();
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
	        search_next();
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
		replace_all();
		setFocus();
	};
}

function search_editor(showDialog) {
	if (!showDialog) {
		search_next();
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

function search_next()
{
    setSearchVars(codeValue());
    if (searchTerm != '' && searchTerm != null)
    {
		const hitlist = document.getElementById('hitlist');
	    var matches = searchAndDisplay(codeValue(),searchSelection,hitlist);
		if (matches.length) {
            const searchMatches = matches.map(m => ({
			    start: m.index,
			    end: m.index + m[0].length
			}));
			markSearchItem();
			searchStats();
			let match = searchMatches[searchPos];
		    scrollIntoView(match.start, match.end);
		    searchPos++;
		}
		searchProjectFiles();
    }
    setFocus();
}

function searchProjectFiles() {
    if (usesProjects() && searchInProject) {
		if (projects.length === 0) {
			loadProjects()
				.then(() => {
					console.log('Projects loaded');
					return renderSearchHits(); // Vänta här
				})
				.then(() => {
					console.log('Sökningen i projektet är klar.');
				})
				.catch(err => {
					console.error('Fel vid laddning eller sökning:', err);
				});
		} else {
			renderSearchHits()
				.then(() => {
					console.log('Sökningen i projektet är klar.');
				})
				.catch(err => {
					console.error('Fel vid sökning i projektet:', err);
				});
		}
	}
}

async function renderSearchHits() {
    const hitlist = document.getElementById('hitlist');
    const currentFile = document.getElementById('Current_filename').value;
    const files = currentProjectFiles();
    for (const file of files) {
        if (file === currentFile) continue;

        const fileLi = document.createElement('li');
        fileLi.textContent = file;

        const fileUl = document.createElement('ul');
        fileLi.appendChild(fileUl);
        hitlist.appendChild(fileLi);

        try {
            const content = await getFileContent(file);
            const matches = searchAndDisplay(content, { selStart: 0, selEnd: content.length }, fileUl);

            if (!matches.length) {
                fileLi.style.display = 'none';
            }
            else {
                const selections = matches.map(m => ({
				    selStart: m.index,
				    selEnd: m.index + m[0].length
				}));

	            for (let i = 0; i < fileUl.childNodes.length; i++) {
		            const li = fileUl.childNodes[i];
		            li.onclick = () => {
			            searchPos = i;
			            submit_file(file,selections[i]);
                    };
                }
            }
        } catch (err) {
            console.error(`Could not read: ${file}`, err);
            fileLi.style.display = 'none';
        }
    }
}

async function getFileContent(file) {
	try {
        const response = await fetch('./read_file.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({file})
        });

        if (response.ok) {
			const text = await response.text();
			return text.replace(/\r\n/g, '\n').trimEnd();
        }
    } catch (err) {
        console.error('Could not read ', file, err);
    }
    return '';
}

function searchAndDisplay(code,selection,hitlist) {
	let matches = searchInText(code,selection);
	hitlist.innerHTML = '';
	createHitlist(hitlist,code,matches,selection.selStart);
    return matches;
}

function markSearchItem() {
	const hitlist = document.getElementById('hitlist');
	var matches = hitlist.childNodes;
	if (matches.length) {
		if (searchPos >= matches.length) {
			searchPos = 0;
	    }
	    if (searchPos < 0) {
			searchPos = matches.length - 1;
	    }
		matches[searchPos].style.backgroundColor = 'lightblue';
	}
}

function searchStats() {
    const stats = document.getElementById('searchstats');
	if (stats) {
		stats.textContent = '';
		const hitlist = document.getElementById('hitlist');
		var matches = hitlist.childNodes;
		if (matches.length) {
			stats.textContent = (searchPos + 1) + ' / ' + matches.length;
	    }
    }
}

function searchInText(code,selection) {
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
	let matches = [...code.substring(selection.selStart,selection.selEnd).matchAll(regex)];

	return matches;
}

function createHitlist(hitlist,code,matches,selStart) {
    let lines = code.split('\n');
	let lineStartIndices = [];
	let index = 0;
	// Skapa karta med var varje rad börjar i hela koden
	for (let line of lines) {
	    lineStartIndices.push(index);
	    index += line.length + 1; // +1 för \n
	}
	// Gå igenom alla matcher
	for (let i = 0; i < matches.length; i++) {
		const m = matches[i];
	    let matchStart = m.index + selStart;
	    let matchEnd = matchStart + m[0].length;
	    // Hitta vilken rad matchen tillhör
	    let lineNumber = lineStartIndices.findIndex((start, i) => {
	        return matchStart >= start && matchStart < (lineStartIndices[i + 1] ?? Infinity);
	    });

        const item = hitlistItem(i,lines,lineNumber);
		hitlist.appendChild(item);
    }
}

function hitlistItem(i,lines,lineNumber) {
	const item = document.createElement('li');
	item.className = 'search-line';
	item.onclick = () => {
		searchPos = i;
		search_next();
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
	item.appendChild(numberDiv);
	item.appendChild(lineDiv);
	return item;
}

function setSearchVars(codevalue) {
    searchTerm = document.getElementById('searchText').value;
    replaceTerm = document.getElementById('replaceText').value;
    searchMatchCase = document.getElementById('matchcasecb').checked;
    searchWholeWord = document.getElementById('wholewordcb').checked;
    searchInProject = document.getElementById('searchinprojectcb').checked;
    if (!document.getElementById('searchselectedcb').checked)
    {
    	searchSelection = { selStart: 0, selEnd: codevalue.length };
    }
}

function replace_all()
{
    var replacements=0;
    var codevalue = codeValue();
    setSearchVars(codevalue);
    if (searchTerm != '' && searchTerm != null && replaceTerm != '' && replaceTerm != null)
    {
		let matches = searchInText(codevalue,searchSelection);
		if (matches.length) {
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
		    setFocus();
		}
    }
}

function formatHtml(html) {
    const voidTags = new Set([
        'area', 'base', 'br', 'col', 'embed', 'hr',
        'img', 'input', 'link', 'meta', 'param',
        'source', 'track', 'wbr'
    ]);

    const tab = '  ';
    let result = '';
    let indentLevel = 0;

    html = html.replace(/>\s*</g, '><').trim();

    const tokens = html
        .replace(/</g, '\n<')
        .replace(/>/g, '>\n')
        .split('\n')
        .map(line => line.trim())
        .filter(line => line.length > 0);

    tokens.forEach(line => {
        const isClosingTag = /^<\/\w/.test(line);
        const tagNameMatch = line.match(/^<(\w+)/);
        const tagName = tagNameMatch ? tagNameMatch[1].toLowerCase() : null;
        const isVoidTag = tagName && voidTags.has(tagName);

        if (isClosingTag) {
            indentLevel--;
        }

        result += tab.repeat(indentLevel) + line + '\n';

        if (
            !isClosingTag &&
            !isVoidTag &&
            /^<\w[^>]*[^/]?>$/.test(line) && // öppningstaggar, ej self-closing
            !line.startsWith('<!')           // ej <!DOCTYPE> eller kommentarer
        ) {
            indentLevel++;
        }
    });

    return result.trim();
}

function showEvalSource() {
    const iframe = document.getElementById('evaluationwindow');
    const doc = iframe.contentDocument || iframe.contentWindow.document;
    const sourceCode = doc.documentElement.outerHTML;

    const pre = document.createElement('pre');
    pre.id = "sourceViewArea";
    pre.style.fontFamily = 'monospace';
    pre.style.fontSize = '13px';
    pre.textContent = formatHtml(sourceCode);

    showElementFrame(pre,'HTML-source');
    //;">${escapeHtml(formatedCode)}</pre>
    //showSourceFrame(sourceCode,'HTML-source');
}

function buildDomTreeFromHtml(html, container) {
  const parser = new DOMParser();
  const doc = parser.parseFromString(html, 'text/html');
  const root = doc.body;

  if (!container) return;

  container.innerHTML = '';
  container.appendChild(buildTree(root));

  function buildTree(node) {
    const ul = document.createElement('ul');
    ul.className = 'tree';

    Array.from(node.childNodes).forEach(child => {
      const li = document.createElement('li');

      if (child.nodeType === 1) { // Element
        const tagName = child.nodeName.toLowerCase();
        let info = `&lt;${tagName}`;
        for (const attr of child.attributes) {
          info += ` ${attr.name}="${attr.value}"`;
        }
        info += '&gt;';

        const tagSpan = document.createElement('span');
        tagSpan.className = 'tag';

        const caret = document.createElement('span');
        caret.className = 'caret';
        caret.innerHTML = ''; // The arrow is styled with ::before

        tagSpan.innerHTML = info;

        const subtree = buildTree(child);
        if (subtree.childNodes.length > 0) {
          li.classList.add('collapsed');
          caret.style.cursor = 'pointer';
          caret.onclick = () => {
            li.classList.toggle('collapsed');
            caret.classList.toggle('caret-down');
          };
          li.appendChild(caret);
        }

        li.appendChild(tagSpan);
        if (subtree.childNodes.length > 0) {
          li.appendChild(subtree);
        }

      } else if (child.nodeType === 3) { // Text
        const text = child.textContent.trim();
        if (text) {
          li.textContent = `"${text}"`;
        }
      }

      if (li.textContent || li.children.length > 0) {
        ul.appendChild(li);
      }
    });

    return ul;
  }
}

function showEvalDomTree() {
    const container = document.createElement('div');
    container.id = 'domTreeContainer';

    const iframe = document.getElementById('evaluationwindow');
    const doc = iframe.contentDocument || iframe.contentWindow.document;
    const sourceCode = doc.documentElement.outerHTML;

	buildDomTreeFromHtml(sourceCode,container);
	const style = document.createElement('style');
	style.textContent = `
	  ul.tree {
		  list-style: none;
		  font-family: monospace;
		  margin: 0;
		  padding-left: 1em;
		}

		.tree li {
		  margin: 2px 0;
		}

		.collapsed > ul {
		  display: none;
		}

		.caret::before {
		  content: ">";
		  color: grey;
		  display: inline-block;
		  margin-right: 6px;
		  transition: transform 0.2s ease;
		}

		.caret.caret-down::before {
		  transform: rotate(90deg);
		}

		.tag {
		  color: darkblue;
		}
	`;
	container.prepend(style);
	showElementFrame(container,'DOM-tree');
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