<?php
$ide=new about;
$ide->IDE();

class about
{
	var $Out;
	var $IDE_homepage_url	= "http://www.ekenberg.se/php/ide/";
	var $GPL_link		= "<A HREF='http://www.gnu.org/copyleft/gpl.html'>GNU General Public License</A>";
	var $PHP_link		= "<A HREF='http://www.php.net'>PHP</A>";
	var $IDE_version		= "2 . 0 . 0";

	function IDE()
	{
		include('./Page.phpclass');
		$this->Out=new Page;
		echo $this->Out->html_top();
		echo $this->about_page();
		echo $this->Out->html_bottom();
	}

	function about_page()
	{
	    ob_start();
	    phpinfo();
	    $phpinfo=ob_get_contents();
	    ob_end_clean();
	    //$phpinfo = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $phpinfo);
        //$phpinfo=str_replace("module_Zend Optimizer","module_Zend_Optimizer", preg_replace( '%^.*<body>(.*)</body>.*$%ms','$1',$phpinfo));
        $phpinfo=str_replace("module_Zend Optimizer","module_Zend_Optimizer", $phpinfo);
		$sections = array("<P><B>I d e . p h p   v e r s i o n   {$this->IDE_version}</B></P>\n",
		"<P>Ide.php is distributed under the {$this->GPL_link}</P>",
		"<P>  - Ide.php was initially written by Johan Ekenberg, <A HREF='mailto:johan@ekenberg.se'>johan@ekenberg.se</A></br>
    For questions regarding the original Ide.php, please use: <A HREF='mailto:ide.php@ekenberg.se'>ide.php@ekenberg.se</A></br></br>
  - This new Ide.php is completely reworked by Thomas Allin, <A HREF='mailto:thomallin@gmail.com'>thomallin@gmail.com</A></br>
    It has an attractive IDE, a real fancy editor and offers complete file and directory control.</P>\n",
		"<P>Visit the original <A HREF='{$this->IDE_homepage_url}'>Ide.php homepage</A></br></br>The new Ide-php is here at <A HREF='https://github.com/vemod-/IDE-php'>Github</A></p>",
		"<P>Feedback and suggestions are always welcome at
           	<A HREF='mailto:ide.php@ekenberg.se'>ide.php@ekenberg.se</A></P>",
		"<p><script type='text/javascript'>
		document.write('Browser CodeName: ' + navigator.appCodeName);
		document.write('<br />');
		document.write('Browser Name: ' + navigator.appName);
		document.write('<br />');
		document.write('Browser Version: ' + navigator.appVersion);
		document.write('<br />');
		document.write('Cookies Enabled: ' + navigator.cookieEnabled);
		document.write('<br />');
		document.write('Platform: ' + navigator.platform);
		document.write('<br />');
		document.write('User-agent header: ' + navigator.userAgent);
		</script></p>" );
		$ret="<div class='fixed_window' style='overflow:auto;background-color:{$this->Out->Bgcolor};'>";
		$ret.="<br/>";
		foreach ($sections as $content) {			
			$ret .= $this->Out->info_box(600, $content);
			$ret .= "<BR>\n";
		}
		$ret .= "<style type=\"text/css\">
table {
max-width:96%;
}
.center {
align:center;
}
";
		$ret .= $phpinfo;
		$ret.='</div>';
		return($ret);
	}
}
?>