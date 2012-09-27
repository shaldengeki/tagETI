<?php
include_once("global/includes.php");

if (isset($_POST['username'])) {
	// username and password sent from form 
	$username=$_POST['username']; 

	$loginResult = $user->logIn($username);
	redirect_to($loginResult);
}

start_html($database, $user, "TagETI", "Sign In", $_REQUEST['status'], $_REQUEST['class']);
echo "<div class='row-fluid' style='text-align: center;'>
	<div class='span12'>
		<h1>Log In</h1>
";
display_login_form();
echo "	</div>
</div>
";
display_footer();
?>