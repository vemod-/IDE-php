function clearAuthenticationCache(page) {
    ae_confirm_yes_no(clearAuthenticationCache_callback,"Log out?",page);
}

function clearAuthenticationCache_callback(returncode,page,value)
{
	if (returncode == 1)
	{
		if (page.length === 0) page = '.force_logout';
		try{
			var agt=navigator.userAgent.toLowerCase();
			if (agt.indexOf("msie") !== -1) {
				// IE clear HTTP Authentication
				document.execCommand("ClearAuthenticationCache");
			}
			else {
				// Let's create an xmlhttp object
				var xmlhttp = createXMLObject();
				// Let's prepare invalid credentials
				xmlhttp.open("GET", page, true, "logout", "logout");
				// Let's send the request to the server
				xmlhttp.send("");
				// Let's abort the request
				xmlhttp.abort();
			}
			main_submit("logout");
			return;
		} catch(e) {
			// There was an error
			ae_alert('Log out error, browser may not be supported');
		}
	}
}

function createXMLObject() {
	var xmlhttp=false;
	if (!xmlhttp && typeof XMLHttpRequest !== 'undefined') {
		try {
			xmlhttp = new XMLHttpRequest();
		} catch (e) {
			xmlhttp=false;
		}
	}
	if (!xmlhttp && window.createRequest) {
		try {
			xmlhttp = window.createRequest();
		} catch (e) {
			xmlhttp=false;
		}
	}
    return xmlhttp;
}

function validatePass()
{
	var invalid = " "; // Invalid character is a space
	var minLength = 6; // Minimum length
	var pw1 = document.main_form.password.value;
	var pw2 = document.main_form.passwordrepeat.value;
	var usernames=document.main_form.allusernames.value.split('|¤|');
	var specials = [
      '/', '.', '*', '+', '?', '|',
      '(', ')', '[', ']', '{', '}','$','^','\\'
    ];
	var matches=document.main_form.username.value.match(/[\?\[\]\/\\=\+<>:;'",\.\*]+/);
    if (matches)
    {
    	ae_alert(matches[0]+' is not allowed in the username.');
	   	return false;
    }

	for (var i=0;i<usernames.length;i++)
	{
        if (document.main_form.username.value === usernames[i])
        {
    		ae_alert('User already exists.');
	   	    return false;
        }
    }
	// check for a value in both fields.
	if (pw1 == '' || pw2 == '') {
		ae_alert('Please enter your password twice.');
		return false;
	}
	// check for minimum length
	if (pw1.length < minLength) {
		ae_alert('Your password must be at least ' + minLength + ' characters long. Try again.');
		return false;
	}
	// check for spaces
	if (pw1.indexOf(invalid) !== -1) {
		ae_alert("Sorry, spaces are not allowed.");
		return false;
	}
	if (pw1 != pw2) {
		ae_alert("You did not enter the same new password twice. Please re-enter your password.");
		return false;
	}
	return true;
}