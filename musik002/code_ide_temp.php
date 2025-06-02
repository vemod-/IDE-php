<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<style type="text/css">
body{
	background-color:#cccccc;
    font-family: "Lucida Grande","Lucida Sans Unicode",Arial,Verdana,sans-serif;
    font-style: normal;
    font-variant: normal;
    font-weight: normal;
    font-size: 12px;
    line-height: 13px;
    font-size-adjust: none;
    font-stretch: normal;
    -x-system-font: none;
    color:#2d2d2d;
}
.inside_menu,.inside_menu_text{
    border:0px;
    padding:0px;
    margin:0px;
    height:100%;
    display:inline;
    float:left;
    clear:right;
    overflow:visible;
    font-size:12px;
    font-weight:bold;
	-moz-box-sizing: border-box;
	-webkit-box-sizing: border-box;
	-ms-box-sizing: border-box;
	box-sizing: border-box;
}
.inside_menu_text{
    padding-top:5px;
}
.btn, .btnpressed, .btndisabled,.navcontainer #current,.imgbutton,hiddenbutton{
    display:block;
	-moz-box-sizing: border-box;
	-webkit-box-sizing: border-box;
	-ms-box-sizing: border-box;
	box-sizing: border-box;
    height:25px;
    margin:0px;
    padding:0px;
    vertical-align:middle;
    padding-top:5px;
    padding-left:10px;
    padding-right:10px;
    color:#2d2d2d;
    font-weight:bold;
    text-shadow: #dddddd 0px 1px 0px;
    filter: DropShadow(Color=dddddd, OffX=0, OffY=1, Positive=1);
    /*background:url(../images/nav1.jpg) repeat-x left top;    */
}
.imgbutton
{
    padding-top:2px;
}
.hiddenbutton{
    display:none;
}
display:none;
.btn:visited, .btndisabled:visited,.btndisabled:active, .btn:link, .btndisabled:link{
    color:#2d2d2d;
}

.btndisabled {
  color:#666666;
}

.btn:active,.imgbutton:active,.btn:focus,.imgbutton:focus{
    background:url(../images/nav4.jpg) repeat-x left top;
    text-shadow: #2d2d2d 0px 1px 0px;
    filter: DropShadow(Color=2d2d2d, OffX=0, OffY=1, Positive=1);
    color:#f5f5f5;
}

.btnpressed,.btnpressed:visited,.btnpressed:active,.btnpressed:link {
    background:url(../images/nav3.jpg) repeat-x left top;
    text-shadow: #2d2d2d 0px 1px 0px;
    filter: DropShadow(Color=2d2d2d, OffX=0, OffY=1, Positive=1);
    color:#f5f5f5;
}

.btn:hover,.navcontainer:hover #current,.imgbutton:hover {
    background:url(../images/nav2.jpg) repeat-x left top;
  text-shadow: #2d2d2d 0px -1px 0px;
    filter: DropShadow(Color=2d2d2d, OffX=0, OffY=-1, Positive=1);
  color:#f5f5f5;
}

.navcontainer,.navcontainer ul {
    position:relative;
    display:block;
    height:100%;
    border:0px;
    padding:0px;
    margin:0px;
}

.navsubbox {
    position:absolute;
    min-width:88px !important;     /* <--- För firefox */
    min-width:90px;                /* <--- Så bred man vill ha menyn / antalet root menyer */
    visibility:hidden;
    padding-left:0px;
    padding-right:0px;
    padding-bottom:3px;
    padding-top:2px;
    text-indent: 2px;
    /*filter:alpha(opacity=85);*/
    -moz-opacity: 0.95;
    opacity: 0.95;
    white-space: nowrap;
}

.navlist li {
    display: inline;
}

.navlist li.disabled {
    display:block;
    padding:3px;
    color:#888888;
    padding-left:20px;
    cursor:default;
}

.navlist a {
    text-decoration:none;
    color: #101010;
    display: block;
    background: transparent;
    padding:3px;
    text-shadow:none;
    padding-left:20px;
    cursor:default;
}

.navlist a:hover {
    background:url(../images/menubg.png) repeat-x left top;
    color:#ffffff;
}

hr {
    margin-top:1px;
    margin-bottom:1px;
    padding:0px;
    border: 0;
    border-top:1px solid #cccccc;
    border-bottom:1px solid #ffffff;
    width: 100%;
    height: 0px;
}
</style>
<script language="javascript">
var show;
var sub_id;
function showHideLayer() {
   if (show==true)
   {
      document.getElementById(sub_id).style.visibility = "visible";
      document.getElementById(sub_id).style.zIndex=1000;
   }
   else
   {
      document.getElementById(sub_id).style.visibility = "hidden";
   }
}
</script>
</head>
<body>
<?php
	$menu_id=1;
        echo "<div class='inside_menu'>\n";
        echo "<div class='navcontainer'>\n";
        echo "<ul class='navlist' onMouseOver='showHideLayer(show=true, sub_id=\"sub_$menu_id\")' onMouseOut='showHideLayer(show=false)'>\n";
        echo "<li><a href='#' id='current'>this is a menu</a></li>\n";
$x=new borderimg('span',"<div style='margin:-5px -9px'>
<li><a href='#'><span style='position:absolute;left:-6px;'>✓</span>Hejsan Hejsan och hejsan... </a></li>
<hr>
<li><a href='#'>Hejsan </a></li>
<li class='disabled'>Disabled</li>
</div>","sub_$menu_id",'navsubbox','margin-top:8px;margin-left:9px;');
$x->borderwidth(8,39,44);
echo $x->borderimage('../images/mnshadow.png',10,39,44);
echo "</span></ul></div>";
echo '</div>';
class borderimg{
	var $url;
	var $width;
	var $height;
	var $borderwidth=array();
	var $imgwidth=array();
	var $elem;
	var $style;
	var $content;
	var $class;
	var $id;
	function borderimg($elem='span',$content='',$id='',$class='',$style='')
	{
		$this->content=$content;
		$this->elem=$elem;
		$this->style=$style;
		if ($id)
		{
			$this->id="id='{$id}'";
		}
		if ($class)
		{
			$this->class="class='{$class}'";
		}
	}
	function borderwidth($top,$right=-1,$bottom=-1,$left=-1)
	{
		$this->borderwidth['top']=$top;
		$this->borderwidth['bottom']=$bottom;
		$this->borderwidth['left']=$left;
		$this->borderwidth['right']=$right;
		if ($bottom==-1)
		{
			$this->borderwidth['bottom']=$this->borderwidth['top'];
		}
		if ($right==-1)
		{
			$this->borderwidth['right']=$this->borderwidth['top'];
		}
		if ($left==-1)
		{
			$this->borderwidth['left']=$this->borderwidth['right'];
		}
	}
	function borderimage($url,$top,$right=-1,$bottom=-1,$left=-1)
	{
		$this->url=$url;
		$info=getimagesize($url);
		$this->width=$info[0];
		$this->height=$info[1];

		$this->imgwidth['top']=$top;
		$this->imgwidth['bottom']=$bottom;
		$this->imgwidth['left']=$left;
		$this->imgwidth['right']=$right;
		if ($bottom==-1)
		{
			$this->imgwidth['bottom']=$this->imgwidth['top'];
		}
		if ($right==-1)
		{
			$this->imgwidth['right']=$this->imgwidth['top'];
		}
		if ($left==-1)
		{
			$this->imgwidth['left']=$this->imgwidth['right'];
		}
		$srcx=$this->width-$this->imgwidth['right'];
		$middlew=$this->width-($this->imgwidth['left']+$this->imgwidth['right']);
		$middleh=$this->height-($this->imgwidth['top']+$this->imgwidth['bottom']);
		$srcy=$this->height-$this->imgwidth['bottom'];

		$ret .= "<{$this->elem} {$this->id} {$this->class}
		style='border-style:solid;
		border-color:#ffffff;
		border-top:{$this->borderwidth['top']}px;
		border-right:{$this->borderwidth['right']}px;
		border-bottom:{$this->borderwidth['bottom']}px;
		border-left:{$this->borderwidth['left']}px;
		background:url(\"imgslicer.php?image={$this->url}&srcw={$middlew}&srch={$middleh}&destw={$middlew}&desth={$middleh}&srcx={$this->imgwidth['left']}&srcy={$this->imgwidth['top']}\");
		{$this->style}'
		>\n";

		//top section
		$ret .= "<img src='imgslicer.php?image={$this->url}&srcw={$this->imgwidth['left']}&srch={$this->imgwidth['top']}&destw={$this->borderwidth['left']}&desth={$this->borderwidth['top']}'
		style='position:absolute;
		left:-{$this->borderwidth['left']}px;
		top:-{$this->borderwidth['top']}px;
		z-index:-1;'
		/>";

		$ret .= "<img src='imgslicer.php?image={$this->url}&srcw={$middlew}&srch={$this->imgwidth['top']}&destw={$middlew}&desth={$this->borderwidth['top']}&srcx={$this->imgwidth['left']}'
		style='position:absolute;
		height:{$this->borderwidth['top']}px;
		width:100%;
		left:0px;
		top:-{$this->borderwidth['top']}px;
		z-index:-1;'
		/>\n";

		$ret .= "<img src='imgslicer.php?image={$this->url}&srcw={$this->imgwidth['right']}&srch={$this->imgwidth['top']}&destw={$this->borderwidth['right']}&desth={$this->borderwidth['top']}&srcx={$srcx}'
		style='position:absolute;
		right:-{$this->borderwidth['right']}px;
		top:-{$this->borderwidth['top']}px;
		z-index:-1;'
		/>\n";

		//middle section
		$ret .= "<img src='imgslicer.php?image={$this->url}&srcw={$this->imgwidth['left']}&srch={$middleh}&destw={$this->borderwidth['left']}&desth={$middleh}&srcy={$this->imgwidth['top']}'
		style='position:absolute;
		left:-{$this->borderwidth['left']}px;
		top:0px;
		height:100%;
		width:{$this->borderwidth['left']}px;
		z-index:-1;'
		/>\n";

		//$ret .= "<img src='imgslicer.php?image={$this->url}&srcw={$middlew}&srch={$middleh}&destw={$middlew}&desth={$middleh}&srcx={$this->imgwidth['left']}&srcy={$this->imgwidth['top']}' style='position:absolute;height:100%;width:100%;left:0px;top:0px;'/>";

		$ret .= "<img src='imgslicer.php?image={$this->url}&srcw={$this->imgwidth['right']}&srch={$middleh}&destw={$this->borderwidth['right']}&desth={$middleh}&srcx={$srcx}&srcy={$this->imgwidth['top']}'
		style='position:absolute;
		right:-{$this->borderwidth['right']}px;
		top:0px;
		height:100%;
		width:{$this->borderwidth['right']}px;
		z-index:-1;'
		/>\n";

		//bottom section;
		$ret .= "<img src='imgslicer.php?image={$this->url}&srcw={$this->imgwidth['left']}&srch={$this->imgwidth['bottom']}&destw={$this->borderwidth['left']}&desth={$this->borderwidth['bottom']}&srcx=0&srcy={$srcy}'
		style='position:absolute;
		left:-{$this->borderwidth['left']}px;
		bottom:-{$this->borderwidth['bottom']}px;
		z-index:-1;'
		/>\n";

		$ret .= "<img src='imgslicer.php?image={$this->url}&srcw={$middlew}&srch={$this->imgwidth['bottom']}&destw={$middlew}&desth={$this->borderwidth['bottom']}&srcx={$this->imgwidth['left']}&srcy={$srcy}'
		style='position:absolute;
		height:{$this->borderwidth['bottom']}px;
		width:100%;
		left:0px;
		bottom:-{$this->borderwidth['bottom']}px;
		z-index:-1;'
		/>\n";

		$ret .= "<img src='imgslicer.php?image={$this->url}&srcw={$this->imgwidth['right']}&srch={$this->imgwidth['bottom']}&destw={$this->borderwidth['right']}&desth={$this->borderwidth['bottom']}&srcx={$srcx}&srcy={$srcy}'
		style='position:absolute;
		right:-{$this->borderwidth['right']}px;
		bottom:-{$this->borderwidth['bottom']}px;
		z-index:-1;'
		/>\n";

		//include ('imgslicer.php');
		$ret .= "{$this->content}</{$this->elem}>";
		return $ret;
	}

}
?>
		<p>
			In the dark ages of table layouts, Web pioneers used to build their
			whole pages using heavy, unmaintainable, inaccessible
			tables.
		</p>
		<p>
			Then came CSS with the simple promise of
			<em>Allowing a clear separation between the content and the presentation</em>.
			But it failed to save them from slicing images with their precious hands
			and countless, meaningless divs, just to create visually pleasing designs.
		</p>

		<p>
			Introducing CSS3 and the border-image property, your personal design ninja.
			It is now possible to decorate any container or element of a page
			independently of its position and dimensions in a flash.
			Just design and describe how you would've sliced;
			sit back and let a ninja do the dirty work.
		</p>
</body>
</html>