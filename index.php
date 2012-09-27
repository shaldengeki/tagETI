<?php
include_once("./global/includes.php");
if ($user->loggedIn()) {
	header("Location: main.php");
}
start_html($database, $user, "TagETI", "Home", $_REQUEST['status'], $_REQUEST['class']);
?>
<div class="hero-unit">
  <h1>Welcome!</h1>
  <p>This is the web interface for TagETI, an administration layer for ETI's topic tags.</p>
  <p>
    <a href="/login.php" class="btn btn-primary btn-large">
      Sign in
    </a>
  </p>
</div>
<?php
display_footer();
?>