<html><head>
<SCRIPT LANGUAGE='JavaScript'>
function test()
{
	document.getElementById('testdiv').style.zIndex='auto';
}

function test1()
{
	document.getElementById('testdiv').style.zIndex=1000;
}
</script>
</head><body>
<div id='testdiv' style='position:absolute;background-color:#ffffff;width:300px;height:300px;' onClick='test()'>
Hejsan! Klicka på mej!
</div>
<div style='position:absolute;background-color:#dddddd;width:500px;height:500px;' onClick='test1()'>
Ojsan, klicka på mej i stället då!
</div>
</body>
</html>