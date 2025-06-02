<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<!--<meta http-equiv="X-UA-Compatible" content="IE=7" />-->
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
.navcontainer,.navcontainer ul {
    position:relative;
    display:block;
    height:100%;
    border:0px;
    padding:0px;
    margin:0px;
}
.navsubbox{
    font-weight:bold;
	position:absolute;
	display:block;
	left:50px;
	top:30px;
    filter:alpha(opacity=85);
    -moz-opacity: 0.85;
    opacity: 0.85;
	border:solid transparent;
	border-width:9px 31px 46px 31px;
	border-image:url("../images/mnshadow.png") 10% 49% 50%;
	-moz-border-image:url("../images/mnshadow.png") 10% 49% 50%;
	-webkit-border-image:url("../images/mnshadow.png") 10% 49% 50%;
}
.insidesubbox{
	display:block;
	margin: -4px -7px;
	background-color:#f5f5f5;
}
.navlist li {
    display: inline;
}

.navlist li.disabled {
    display:block;
    padding:3px;
    color:#888888;
    padding-left:20px;
    padding-right:10px;
}
.navlist a {
    text-decoration:none;
    color: #101010;
    display: block;
    background: transparent;
    padding:3px;
    text-shadow:none;
    padding-left:20px;
    padding-right:10px;
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
    height: 0px;
}
</style>
</head><body>
<div class='navcontainer>'>
<ul class='navlist' onMouseOver='showHideLayer(show=true, sub_id=\"sub_$menu_id\")' onMouseOut='showHideLayer(show=false)'>
<li><a href='#' id='current'>this is a menu</a></li>
<ul class='navlist'>
<span class='navsubbox'>
<span class='insidesubbox'>
<li><a href='#'><span style='position:absolute;left:0px;'> ✓</span>Menu</a></li>
<hr/>
<li><a href='#'><span style='position:absolute;left:0px;'> </span>Hejsan, hejsa och hallöj</a></li>
<li class='disabled'><span style='position:absolute;left:0px;'> </span>Disabled</li>
</span>
</span>
</ul>
</div>
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