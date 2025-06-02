<html>
<head>
    <style type="text/css">
        tr, tr td {
            border: 1px #999999 solid;
            border-bottom: 2px #999999 solid;
            background-color: #FFFFFF;
        }

        tr.underline, tr.underline td {
            border-bottom: 2px #000000 solid;
            background-color: #CCCCCC;
        }
    </style>
    <script type="text/javascript">
    <!--
        var draggingRow = false;
        var sourceRow = null;

        function startedDragging() {
            draggingRow = true;
            sourceRow = event.srcElement.parentNode.parentNode;
        }

        function dragEnter() {
            if (draggingRow) window.event.returnValue = false;
        }

        function dragOver() {
            if (draggingRow) {
                var targetRow = event.srcElement;
                while (targetRow.parentNode != null && targetRow.tagName && targetRow.tagName.toLowerCase() != 'tr') targetRow = targetRow.parentNode;
                targetRow.className = 'underline';
                window.event.returnValue = false;
            }
        }

        function dragLeave() {
            if (draggingRow) {
                var targetRow = event.srcElement;
                while (targetRow.parentNode != null && targetRow.tagName && targetRow.tagName.toLowerCase() != 'tr') targetRow = targetRow.parentNode;
                targetRow.className = '';
            }
        }

        function dropped() {
            if (draggingRow) {
                targetRow = event.srcElement;
                while (targetRow.parentNode != null && targetRow.tagName && targetRow.tagName.toLowerCase() != 'tr') targetRow = targetRow.parentNode;
                targetRow.className = '';
                sourceRow.swapNode(targetRow);
                draggingRow = false;
            }
        }

        var iconForDragging = '#define icon_width 4\n#define icon_height 4\nstatic char icon_bits[] = { 0x05, 0x0A, 0x05, 0x0A };';
    //-->
    </script>
</head>
<body ondrop="dropped();">
    <table id="dragDropTable" border="0" cellpadding="2" cellspacing="0">
        <thead></thead>
        <tbody>
            <tr ondragenter="dragEnter();" ondragover="dragOver();" ondragleave="dragLeave();">
                <td><img src="javascript:iconForDragging;" width="16" height="16" ondragstart="startedDragging();"></td>
                <td>Row 0, Cell 1</td>
                <td>Row 0, Cell 2</td>
                <td>Row 0, Cell 3</td>
            </tr>
            <tr ondragenter="dragEnter();" ondragover="dragOver();" ondragleave="dragLeave();" ondrop="dropped();">
                <td><img src="javascript:iconForDragging;" width="16" height="16" ondragstart="startedDragging();"></td>
                <td>Row 1, Cell 1</td>
                <td>Row 1, Cell 2</td>
                <td>Row 1, Cell 3</td>
            </tr>
            <tr ondragenter="dragEnter();" ondragover="dragOver();" ondragleave="dragLeave();" ondrop="dropped();">
                <td><img src="javascript:iconForDragging;" width="16" height="16" ondragstart="startedDragging();"></td>
                <td>Row 2, Cell 1</td>
                <td>Row 2, Cell 2</td>
                <td>Row 2, Cell 3</td>
            </tr>
        </tbody>
        <tfoot></tfoot>
    </table>
</body>
</html>