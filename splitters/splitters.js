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

function getScriptBaseUrl(scriptName) {
    const scripts = document.getElementsByTagName('script');
    for (let i = 0; i < scripts.length; i++) {
        const src = scripts[i].getAttribute('src');
        if (src && src.includes(scriptName)) {
            return src.substring(0, src.lastIndexOf('/') + 1); // inklusive slash
        }
    }
    return '';
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
		//if (browser.isIE)
		  //dragObj.elNode = window.event.srcElement;
		//if (browser.isNS)
		dragObj.elNode = event.target;

		// If this is a text node, use its parent element.

		if (dragObj.elNode.nodeType == 3) {
			dragObj.elNode = dragObj.elNode.parentNode;
		}
	}
	tds2=new Array();
	tds2[0]=dragObj.elNode.parentNode.parentNode;
	// Get cursor position with respect to the page.
	/*
	if (browser.isIE) {
		x = window.event.clientX + document.documentElement.scrollLeft
		  + document.body.scrollLeft;
		y = window.event.clientY + document.documentElement.scrollTop
		  + document.body.scrollTop;
	}
	*/
	//if (browser.isNS) {
	x = event.clientX + window.scrollX;
	y = event.clientY + window.scrollY;
	//}
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
	/*
	if (browser.isIE) {
		document.attachEvent("onmousemove", dragGo);
		document.attachEvent("onmouseup",   dragStop);
		window.event.cancelBubble = true;
		window.event.returnValue = false;
	}
	*/
	//if (browser.isNS) {
	document.addEventListener("mousemove", dragGo,   true);
	document.addEventListener("mouseup",   dragStop, true);
	event.preventDefault();
	//}

}

function dragGo(event) {

	var x, y;

	// Get cursor position with respect to the page.
	/*
	if (browser.isIE) {
		x = window.event.clientX + document.documentElement.scrollLeft
		  + document.body.scrollLeft;
		y = window.event.clientY + document.documentElement.scrollTop
		  + document.body.scrollTop;
	}
	*/
	//if (browser.isNS) {
	x = event.clientX + window.scrollX;
	y = event.clientY + window.scrollY;
	//}

	// Move drag element by the same amount the cursor has moved.
	if (dragObj.elNode.className !== 'horiz_split')
	{
		dragObj.elNode.style.left = (dragObj.elStartLeft + (x - dragObj.cursorStartX)) + "px";
	}
	if (dragObj.elNode.className!== 'vert_split')
	{
		dragObj.elNode.style.top  = (dragObj.elStartTop  + (y - dragObj.cursorStartY)) + "px";
	}
	/*
	if (browser.isIE) {
	window.event.cancelBubble = true;
	window.event.returnValue = false;
	}
	*/
	//if (browser.isNS)
	event.preventDefault();
}

function dragStop(event) {

	// Get cursor position with respect to the page.
	/*
	if (browser.isIE) {
	x = window.event.clientX + document.documentElement.scrollLeft
	  + document.body.scrollLeft;
	y = window.event.clientY + document.documentElement.scrollTop
	  + document.body.scrollTop;
	}
	*/
	//if (browser.isNS) {
	x = event.clientX + window.scrollX;
	y = event.clientY + window.scrollY;
	//}

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
	/*
	if (browser.isIE) {
	document.detachEvent("onmousemove", dragGo);
	document.detachEvent("onmouseup",   dragStop);
	}
	*/
	//if (browser.isNS) {
	document.removeEventListener("mousemove", dragGo,   true);
	document.removeEventListener("mouseup",   dragStop, true);
	//}

	splitbg.style.visibility='hidden';
	/*
	if (browser.isIE && !is_opera) {
	reset_width();
	}
	*/
}

function init_splitters()
{
	elem_array=getElementsByClassName(document,'horiz_split');
    const base_url = getScriptBaseUrl('splitters.js');
    const img_path = base_url + "splitterbg.png";
	for (var i=0;i<elem_array.length;i++)
	{
		elem_array[i].style.top='-4px';
		elem_array[i].onmouseover=new Function("this.style.backgroundImage='url("+img_path+")';");
		elem_array[i].onmouseout=new Function("this.style.background='transparent';");
	}
	elem_array=getElementsByClassName(document,'vert_split');
	for (var i=0;i<elem_array.length;i++)
	{
		elem_array[i].style.left='-4px';
		elem_array[i].onmouseover=new Function("this.style.backgroundImage='url("+img_path+")';");
		elem_array[i].onmouseout=new Function("this.style.background='transparent';");
	}
}