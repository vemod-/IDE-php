<html>
<head>
<style type="text/css">
.wrapper,.leftwrapper{
	position:absolute;
	width:100%;
	height:100%;
	padding.0px;
	margin:0px;
	-moz-box-sizing: border-box;
	-webkit-box-sizing: border-box;
	-ms-box-sizing: border-box;
	box-sizing: border-box;
	padding-top:25px;
	overflow:auto;
}
.leftwrapper{
	padding-top:0px;
	border-left:34px solid;
}
.header,.leftheader{
	position:absolute;
	height:25px;
	width:100%;
	background-color:#cccccc;
}
.leftheader{
	width:34px;
	height:100%;
}
html,body{
	padding.0px;
	border:none;
	margin:0px;
	width:100%;
	height:100%;
	overflow:auto;
	background-color:#aaaaaa;
}
</style>
   <script type="text/javascript">
   function scrolltext(elem)
   {
   	document.getElementById('code_numbers').style.top=(elem.scrollTop * -1)+'px';
   }
</script>
</head>
<body>
<div class="wrapper">
<div style="position:relative;height:100%;width:100%;">
<div style="position:absolute;height:100%;width:100%;">
<div style="position:relative;height:100%;width:100%;">
<textarea wrap="off" class="leftwrapper" onscroll="scrolltext(this)"></textarea>
<div class="leftheader" style="overflow:hidden;">
<div id="code_numbers" style="position:absolute;height:5000px;width:30px;border-right:1px solid #000000;">
<?php
for ($x=1;$x<500;$x++)
{
	echo $x.'<br/>';
}
?>
</div>
</div>
</div>
</div>
</div>
</div>
<div class="header">header
</div>
</body>
</html>