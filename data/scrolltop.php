<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<script type='text/javascript'>

function scrollfunc()
{
	//alert(document.getElementById('txt1').scrollTop);
	document.getElementById('memo').innerHTML=document.getElementById('txt1').scrollTop;
}

</script>
</head>
<body>
<p>A new row is added when the number of
characters is greater than rows * cols.</p>
<form>
<div style='position:absolute;width:300;height:300;overflow:hidden'>
<textarea id='txt1' rows='10' cols='10' WRAP='OFF' style='min-width:100%;min-height:100%;overflow:auto;border:1px solid;padding:0px;margin:0px;' onscroll='scrollfunc()'></textarea>
<div id="memo"></div>
<input type="button" value="set" onClick="document.getElementById('txt1').scrollTop=77;"/>
<div>
</form>
</body>
</html>