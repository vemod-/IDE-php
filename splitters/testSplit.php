<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
            "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title></title>
<style type="text/css">
    div#header{position:absolute;top:0px;left:0px;right:0px;height:50px;margin:0px;padding:0px;overflow:hidden;}
    div#wrapper{position:absolute;top:50px;left:0px;right:0px;bottom:0px;margin:0px;padding:0px;overflow:hidden;}

table.insidediv{
    position:relative;
    height:100%;
    width:100%;
    border-collapse: collapse;
}
tr{
    padding:0px;
    margin:0px;
    border:none;
}
div.relative{
	padding:0px;
	margin:0px;
	border:none;
	position:relative;
	top:0px;
	left:0px;
	width:100%;
	height:100%;
}
div.absolute{
	padding:0px;
	margin:0px;
	border:1px;
	border-style: solid;
	position:absolute;
	width:100%;
	height:100%;
	background-color:#eeeeee;
}

</style>
</head>
<body>
<div id="header">Header</div>
<div id="header">Header 2</div>
<?php
    require('splitters.php');
    $f = new SplitterFactory;
    echo $f->buildAssets();
?>
<div id="wrapper">
<table class="insidediv"><tr>
<?php
    echo $f->buildNeutralCell("td_upper_left","width:18%;height:50%;",
        "<div class=\"relative\"><div class=\"absolute\">
upper left
	 	</div></div>"
    );
    echo $f->buildVertCell("td_upper_middle","width:41%;height:50%;","splitter_2","td_upper_left",
        "<div class=\"relative\"><div class=\"absolute\">
upper middle
	 	</div></div>"
     );
    echo $f->buildVertCell("td_upper_right","width:41%;height:50%;","splitter_3","td_upper_middle",
        "<div class=\"relative\"><div class=\"absolute\">
upper right
	 	</div></div>"
    );
?>

</td>
</tr>
<?php
    echo $f->buildHorizCell("td_upper_right","width:100%;height:50%;","splitter_4","td_upper_left%td_upper_middle%td_upper_right",
        "<div class=\"relative\"><div class=\"absolute\">
bottom
	 	</div></div>"
    );
?>

</td></tr>

</table>
</div>
</body>