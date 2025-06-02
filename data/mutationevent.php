<html><head><title>MutationEvent test</title></head>
<BODY onLoad="init()">
<SCRIPT type='text/javascript'>
function init() {
    document.forms[0].inp.addEventListener("DOMCharacterDataModified",
	function(e){document.getElementById('label1').innerHTML=
	    e.prevValue+" => "+e.newValue;}, false);}
</SCRIPT>
<FORM>
<INPUT type='text' name='inp' value='Mom'>
<br>DOMCharacterDataModified:  
<LABEL id=label1 style="background-color:yellow">initial label</LABEL>
</FORM>
</BODY>
</html>