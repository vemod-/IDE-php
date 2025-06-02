<SCRIPT LANGUAGE="JavaScript">

function validatePass()
{
	var invalid = " "; // Invalid character is a space
	var minLength = 6; // Minimum length
	var pw1 = document.main_form.password.value;
	var pw2 = document.main_form.passwordrepeat.value;
	// check for a value in both fields.
	if (pw1 == '' || pw2 == '') {
		alert('Please enter your password twice.');
		return false;
	}
	// check for minimum length
	if (pw1.length < minLength) {
		alert('Your password must be at least ' + minLength + ' characters long. Try again.');
		return false;
	}
	// check for spaces
	if (pw1.indexOf(invalid) != -1) {
		alert("Sorry, spaces are not allowed.");
		return false;
	}
	if (pw1 != pw2) {
		alert("You did not enter the same new password twice. Please re-enter your password.");
		return false;
	}
	if (confirm('Add user '+document.main_form.username.value+'?')) {
		return true;
	}
	return false;
}
//  End -->
</script>
<form name="main_form" method="POST">
<?php
$pwpath='./.htpwd';
$usernames=array();
$passwords=array();
if (file_exists($pwpath)) {
	$pwfile=fopen($pwpath,'r');
	while ($line=fgets($pwfile)) {
		$line=preg_replace('`[rn]$`','',$line);
		list($usernames[],$passwords[])=explode(':',$line);
	}
	fclose($pwfile);
}
if (isSet($_POST['action'])) {
	if ($_POST['action']=='add_user') {
		$usernames[]=$_POST['username'];
		$passwords[]=md5($_POST['password']);
		$pwfile=fopen($pwpath,'w');
		for ($i=0; $i<count($usernames); $i++) {
			fputs($pwfile,"{$usernames[$i]}:{$passwords[$i]}n");
		}
		fclose($pwfile);
	}
	if ($_POST['action']=='remove_user') {
		$pwfile=fopen($pwpath,'w');
		for ($i=0; $i<count($usernames); $i++) {
			if ($usernames[$i]!=$_POST['removed_user']) {
				fputs($pwfile,"{$usernames[$i]}:{$passwords[$i]}n");
			}
		}
		fclose($pwfile);
	}
}
for ($i=0; $i<count($usernames); $i++) {
	if ($usernames[$i]!=$_POST['removed_user']) {
		echo $usernames[$i];
		if ($i>0) {
			echo '<input type="submit" value="Remove" onClick="if (confirm(\'Remove user '.$usernames[$i].'?\')){main_form.action.value=\'remove_user\';main_form.removed_user.value=\''.$usernames[$i].'\';main_form.submit();}"/>';
			}
			echo '<br>';
		}
	}
	if (count($usernames)==0) {
		echo 'Create admin account<br>';
	} else {
		echo '<br>Add user<br>';
	}
	echo '<input type="hidden" name="action"/>';
	echo '<input type="hidden" name="removed_user"/>';
	echo '<input type="text" name="username" width="80"/>Username<br>';
	echo '<input type="text" name="password" width="80"/>Password<br>';
	echo '<input type="text" name="passwordrepeat" width="80"/>Repeat password<br>';
	if (count($usernames)==0) {
		echo '<input type="submit" value="Create account" onClick="main_form.action.value=\'add_user\';return validatePass();"/>';
	} else {
		echo '<input type="submit" value="Add user" onClick="main_form.action.value=\'add_user\';return validatePass();"/>';
	}
	?>
	</form>