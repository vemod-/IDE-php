<?php
if (file_exists('./.htpwd')) {
	require_once 'http_authenticate.php';
	if (USER_AUTHENTICATED != 1) {
		die('access denied');
	}
}
include('./Page.phpclass');
$page=new Page;
$pwpath='./.htpwd';
$usernames=array();
$passwords=array();
if (file_exists($pwpath)) {
	$pwfile=fopen($pwpath,'r');
	while ($line=fgets($pwfile)) {
		$line=preg_replace('`[\r\n]$`','',$line);
		list($usernames[],$passwords[])=explode(':',$line);
	}
	fclose($pwfile);
}
if (isSet($_POST['action']) && (($_POST['action']=='add_user') || ($_POST['action']=='create_account'))) {
	$usernames[]=$_POST['username'];
	$passwords[]=md5($_POST['password']);
	$pwfile=@fopen($pwpath,'w');
	if (!$pwfile)
	{
        die('<h2>Could not create a password file! Please check your permissions!</h2><br/>This application needs to create writable files and directories on your server!');
    }    
	for ($i=0; $i<count($usernames); $i++) {
		fputs($pwfile,"{$usernames[$i]}:{$passwords[$i]}\n");
	}
	fclose($pwfile);	
	if ($_POST['action']=='create_account') {
		header('Location: ./index.php');
	}
}
if (isSet($_POST['action']) && $_POST['action']=='remove_user') {
	$pwfile=fopen($pwpath,'w');
	for ($i=0; $i<count($usernames); $i++) {
		if ($usernames[$i]!=$_POST['removed_user']) {
			fputs($pwfile,"{$usernames[$i]}:{$passwords[$i]}\n");
		}
	}
	fclose($pwfile);
}
$users = '<p>';
if (count($usernames)>0) {
	$users .=  'Users:';
}
for ($i=0; $i<count($usernames); $i++) {
	if (isSet($_POST['removed_user']) && $usernames[$i]!=$_POST['removed_user']) {
		$users .=  "<p><div class='inside_menu_text' style='font-weight:normal;text-shadow:none;margin-left:20pt;'>". $usernames[$i]."&nbsp;&nbsp;&nbsp;</div><div class='inside_menu'>";
		if (($i>0) && ($usernames[$i]!=$_SERVER['PHP_AUTH_USER'])) {
			//$users .=  "<a href='#' class='btn' onClick='if (confirm(\"Remove user {$usernames[$i]}?\")){main_form.action.value=\"remove_user\";main_form.removed_user.value=\"{$usernames[$i]}\";main_form.submit();}'>Remove</a>";
			$users .=  "<a href='#' class='btn' onClick='main_form.removed_user.value=\"{$usernames[$i]}\";ae_confirm(callback_submit,\"Remove user {$usernames[$i]} ?\",\"remove_user\");'>Remove</a>";
		}
		$users .=  '</div></p><br>';
	}
}
$users.='</p>';
$ret = '<p>';
if (count($usernames)==0) {
	$ret .=  'Create admin account:<br><br>';
} else {
	$ret .=  'Add user:<br><br>';
}
$ret .=  '<input type="hidden" name="action"/>';
$ret .=  '<input type="hidden" name="removed_user"/>';
$ret .=  '<input type="hidden" name="allusernames" value="'.implode('|¤|',$usernames).'"/>';
$ret .=  '<input type="text" name="username" width="80"/> Username<br><br>';
$ret .=  '<input type="password" name="password" width="80"/> Password<br><br>';
$ret .=  '<input type="password" name="passwordrepeat" width="80"/> Repeat password<br>';
$ret.='</p>';
$ret.="<p><div class='inside_menu' style='margin-left:10pt;'>";
if (count($usernames)==0) {
	$ret .=  '<a href="#" class="btn" onClick="if(validatePass()){ae_confirm(callback_submit,\'Create account \'+main_form.username.value+\' ?\',\'create_account\');}">Create account</a>';
} else {
	$ret .=  '<a href="#" class="btn" onClick="if(validatePass()){ae_confirm(callback_submit,\'Add user \'+main_form.username.value+\' ?\',\'add_user\');}">Add user</a>';
}
$ret.='</div></p>';
echo $page->html_top();
echo "<div class='fixed_window' style='overflow:auto;background-color:{$page->Bgcolor};'>";
echo '<form name="main_form" method="POST">';
echo '<br/>';
if (count($usernames)) {
	echo $page->info_box(600,$users).'<br/>';
}
echo $page->info_box(600,$ret);
echo '</form>';
echo '</div>';
echo $page->html_bottom();
?>