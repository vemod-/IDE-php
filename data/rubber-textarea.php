<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<script type='text/javascript'>

window.onload = function()
{
  txtOnTimeout();
}

function txtOnTimeout()
{
  var t1 = document.getElementById('txt1');

  var txt_array=t1.value.split('\n');
  if (t1.rows != txt_array.length)
  {
  	t1.rows=txt_array.length;
  }

  var longest=txt_array[0].length;
  for(var i=1;i<txt_array.length;i++)
  {
    if (txt_array[i].length>longest)
    {
      longest=txt_array[i].length;
    }
  }
  if (t1.cols != longest)
  {
  	t1.cols=(longest>1) ? longest : 1;
  }


  //if (t1.value.length > t1.rows * t1.cols) {
  //  ++t1.rows;
  //}
  setTimeout('txtOnTimeout()', 50);
}
</script>
</head>
<body>
<p>A new row is added when the number of
characters is greater than rows * cols.</p>
<form>
<div style='position:absolute;width:300;height:300;overflow:auto'>
<textarea id='txt1' rows='1' cols='1' style='min-width:100%;min-height:100%;overflow:hidden;border:1px solid;padding:0px;margin:0px;'></textarea>
<div>
</form>
</body>
</html>