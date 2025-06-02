<?php
include './Page.phpclass';
include './Conf.phpclass';
$Conf= new Conf();

function get_value($name,$default)
{
	global $_POST,$_REQUEST;
	$retval=$default;
	if (isset($_REQUEST[$name])) {
		$retval=$_REQUEST[$name];
	}
	if (isset($_POST[$name])) {
		$retval=$_POST[$name];
	}
	return $retval;
}

function make_options($list,$selected)
{
	$ret = "";
	$list=explode(',',$list);
	foreach ($list as $option) {
		$ret .= '<option';
		if (trim($option)==$selected) {
			$ret .= ' selected';
		}
		$ret .= '>'.trim($option).'</option>';
	}
	return $ret;
}
$in_encodings='UTF-8, UTF-7, ASCII, EUC-JP,SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP, ISO-8859-1, WINDOWS-1252';
$filename=get_value('filename',$Conf->Current_file);
$f=fopen($filename,"r");
$data=fread($f,filesize($filename));
fclose($f);
$detected=mb_detect_encoding($data,$in_encodings);
$show_as=get_value('show_as',$Conf->Encoding);
$convert_to=get_value('convert_to','');
if (isSet($_POST['action']) && $_POST['action']=='convert') {
	//echo 'converting from '.$show_as.' to '.$convert_to.'<br>';
	$filename=get_value('filename','new.php');
	$f=fopen($filename,"r");
	$data=fread($f,filesize($filename));
	fclose($f);
	$data=mb_convert_encoding($data,$convert_to,$show_as);
	$show_as=$convert_to;
	$f=fopen($filename,"w");
	fputs($f,$data);
	fclose($f);
	$detected=mb_detect_encoding($data,$in_encodings);
}
$Conf->Encoding=$show_as;
$Conf->save_to_file(array('Encoding'));
$Out=new Page();
echo $Out->html_top($show_as);
?>
<script language="javascript">

function submit_convert()
{
	var show_as=document.main_form.show_as.value;
	var convert_to=document.main_form.convert_to.value;
	if (show_as==convert_to) {
		ae_alert('Input and Output format both '+show_as);
		return;
	}
	//var answer=ae_confirm('Convert from '+show_as+' to '+convert_to+' ?nBe careful since data can be lost!nMake sure everything looks right in the textarea!');
	//if (answer)
	//{
	//    document.main_form.action.value='convert';
	//    document.main_form.submit();
	//}
	ae_confirm(callback_submit,'Convert from '+show_as+' to '+convert_to+' ?<br>Be careful since data can be lost !<br>Make sure everything looks right in the textarea !',"convert");
}
</script>
<?php
echo '<div class="fixed_window" style="overflow:auto;background-color:'.$Out->Bgcolor.';">';
echo '<form name="main_form" METHOD="POST" ACTION="'.$_SERVER['PHP_SELF'].'">';
echo '<input type="hidden" name="filename" value="'.$filename.'">';
echo '<input type="hidden" name="detected" value="'.$detected.'">';
echo '<input type="hidden" name="action">';
$f=fopen($filename,"r");
$data=fread($f,filesize($filename));
fclose($f);
$ret = '<P CLASS="indentall">Guessed encoding: '.$detected.'</P>';
echo '<br>'.$Out->info_box(600, $ret);
$ret = '<P CLASS="indentall">Show as: <select name="show_as" onchange="main_form.submit();">';
$ret .= make_options('UTF-8, UTF-7, ASCII, EUC-JP,SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP, ISO-8859-1, WINDOWS-1252, UTF-16, UTF-32',$show_as);
$ret .= '</select>
</P>';
$ret .= '<center><textarea rows="20" cols="60" disabled>' .
        preg_replace("/<\/(textarea)>/i", "</ide\\1>", $data) .
        '</textarea></center>';echo '<br>'.$Out->info_box(600, $ret);
$ret = '<P CLASS="indentall">Save as: <select name="convert_to">';
$ret .= make_options($in_encodings,$convert_to);
$ret .= '</select>&nbsp;<input type="button" onclick="submit_convert();" value="Save">
</P>';
echo '<br>'.$Out->info_box(600, $ret).'<br>';
echo '</form>';
echo '</div>';
echo $Out->html_bottom();
?>