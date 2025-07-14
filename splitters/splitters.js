const dragObj = { zIndex: 0 };
let winWidth, winHeight;
let tds1 = [], tds2 = [];
const cellPadding = 0;
const padding = {
    left: 0,
    right: 0,
    bottom: 0,
    top: 25,
};

function getWindowSize(tds) {
    const tbl = tds.closest('table');
    if (tbl) {
        winWidth = tbl.clientWidth;
        winHeight = tbl.clientHeight;
    } else {
        winWidth = window.innerWidth;
        winHeight = window.innerHeight;
    }
}

function dragStart(event, splitterId, td1Ids) {
    dragObj.zIndex = 0;

    tds1 = td1Ids.split('%').map(id => document.getElementById(id));
    getWindowSize(tds1[0]);

    dragObj.elNode = splitterId ? document.getElementById(splitterId) : event.target;
    if (dragObj.elNode.nodeType === 3) dragObj.elNode = dragObj.elNode.parentNode;

    //tds2 = [dragObj.elNode.closest('tr').nextElementSibling?.querySelector('td') || dragObj.elNode.parentNode.parentNode];
    tds2 = new Array();
	tds2[0] = dragObj.elNode.parentNode.parentNode; 
    
    const x = event.clientX + window.scrollX;
    const y = event.clientY + window.scrollY;

    dragObj.cursorStartX = x;
    dragObj.cursorStartY = y;
    dragObj.elStartLeft = parseInt(dragObj.elNode.style.left) || 0;
    dragObj.elStartTop = parseInt(dragObj.elNode.style.top) || 0;

    dragObj.elStartLeftPercent = ((x - padding.left) * 100) / (winWidth - padding.left - padding.right);
    dragObj.elStartTopPercent = ((y - padding.top) * 100) / (winHeight - padding.top - padding.bottom);

    let splitBg = document.getElementById('splitter_bg');
    if (!splitBg) {
        splitBg = document.createElement('div');
        splitBg.id = 'splitter_bg';
        Object.assign(splitBg.style, {
            top: '0px',
            left: '0px',
            width: '100%',
            height: '100%',
            position: 'fixed',
            zIndex: '8000',
            cursor: 'pointer',
            display: 'block',
        });
        splitBg.innerHTML = '&nbsp;';
        document.body.appendChild(splitBg);
    } else {
        splitBg.style.display = 'block';
    }

    dragObj.elNode.style.zIndex = 8001;

    document.addEventListener('mousemove', dragGo, true);
    document.addEventListener('mouseup', dragStop, true);
    event.preventDefault();
}

function dragGo(event) {
    const x = event.clientX + window.scrollX;
    const y = event.clientY + window.scrollY;

    if (dragObj.elNode.className !== 'horiz_split') {
        dragObj.elNode.style.left = (dragObj.elStartLeft + (x - dragObj.cursorStartX)) + 'px';
    }
    if (dragObj.elNode.className !== 'vert_split') {
        dragObj.elNode.style.top = (dragObj.elStartTop + (y - dragObj.cursorStartY)) + 'px';
    }

    event.preventDefault();
}

function dragStop(event) {
    const x = event.clientX + window.scrollX;
    const y = event.clientY + window.scrollY;

    if (dragObj.elNode.className === 'horiz_split') {
        const yPercent = ((y - (padding.top + cellPadding)) * 100) / (winHeight - padding.top - padding.bottom - cellPadding * 2);
        const yDiff = Math.round(yPercent - dragObj.elStartTopPercent);
        let min1 = 100, min2 = 100;

        tds1.forEach(td => {
            const newHeight = parseInt(td.style.height) + yDiff;
            if (newHeight < min1) min1 = newHeight;
        });
        tds2.forEach(td => {
            const newHeight = parseInt(td.style.height) - yDiff;
            if (newHeight < min2) min2 = newHeight;
        });

        if (min1 < 2) { min2 += min1 - 2; min1 = 2; }
        if (min2 < 2) { min1 += min2 - 2; min2 = 2; }
 
        tds1.forEach(td => {
            td.style.height = min1 + '%';
            const styleElem = document.getElementById(td.id + '_style');
            if (styleElem) styleElem.value = `width:${td.style.width};height:${td.style.height};`;
        });
        tds2.forEach(td => {
            td.style.height = min2 + '%';
            const styleElem = document.getElementById(td.id + '_style');
            if (styleElem) styleElem.value = `width:${td.style.width};height:${td.style.height};`;
        });

        dragObj.elNode.style.top = (dragObj.elStartTop - cellPadding) + 'px';
    }

    if (dragObj.elNode.className === 'vert_split') {
        const xPercent = ((x - (padding.left + cellPadding)) * 100) / (winWidth - padding.left - padding.right - cellPadding * 2);
        const xDiff = Math.round(xPercent - dragObj.elStartLeftPercent);
        let min1 = 100, min2 = 100;

        tds1.forEach(td => {
            const newWidth = parseInt(td.style.width) + xDiff;
            if (newWidth < min1) min1 = newWidth;
        });
        tds2.forEach(td => {
            const newWidth = parseInt(td.style.width) - xDiff;
            if (newWidth < min2) min2 = newWidth;
        });

        if (min1 < 2) { min2 += min1 - 2; min1 = 2; }
        if (min2 < 2) { min1 += min2 - 2; min2 = 2; }

        tds1.forEach(td => {
            td.style.width = min1 + '%';
            const styleElem = document.getElementById(td.id + '_style');
            if (styleElem) styleElem.value = `width:${td.style.width};height:${td.style.height};`;
        });
        tds2.forEach(td => {
            td.style.width = min2 + '%';
            const styleElem = document.getElementById(td.id + '_style');
            if (styleElem) styleElem.value = `width:${td.style.width};height:${td.style.height};`;
        });

        dragObj.elNode.style.left = (dragObj.elStartLeft - cellPadding) + 'px';
    }

    dragObj.elNode.style.zIndex = 1001;
    dragObj.elNode = null;

    document.removeEventListener('mousemove', dragGo, true);
    document.removeEventListener('mouseup', dragStop, true);

    const splitBg = document.getElementById('splitter_bg');
    if (splitBg) splitBg.style.display = 'none';
}
