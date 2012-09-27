<?php
include_once("global/includes.php");
if (!$user->loggedIn()) {
  header("Location: index.php");
}

if (isset($_POST['switch_username']) && $user->isAdmin()) {
  $switchUser = $user->switchUser($_POST['switch_username']);
  redirect_to($switchUser);
} elseif ($_REQUEST['action'] == 'switch_back') {
  $switchUser = $user->switchUser($_SESSION['switched_user']['username'], False);
  redirect_to($switchUser);
}

start_html($database, $user, "TagETI", "Users", $_REQUEST['status'], $_REQUEST['class']);

switch($_REQUEST['action']) {
  case 'switch_user':
    if (!$user->isAdmin()) {
      display_error("Error: Insufficient privileges", "Only admins can switch users.");
      break;      
    }
    echo "<h1>Switch users</h1>\n";
    display_user_switch_form($database, $user);
    break;
  case 'edit':
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id'])) {
      display_error("Error: Invalid user ID", "Please check your ID and try again.");
      break;
    }
    //ensure that user has sufficient privileges to modify this user.
    if ($user->id != intval($_REQUEST['id']) && !$user->isAdmin()) {
      display_error("Error: Insufficient privileges", "You can't edit this user.");
      break;
    }
    $username = $database->queryFirstValue("SELECT `username` FROM `users` WHERE `id` = ".intval($_REQUEST['id']));
    if (!$username) {
      display_error("Error: Invalid user", "The given user doesn't exist.");
      break;
    }
    echo "<h1>".escape_output($username)."</h1>\n";
    //display_user_edit_form($database, $user, intval($_REQUEST['id']));
    break;
  case 'show':
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id'])) {
      display_error("Error: Invalid user ID", "Please check your ID and try again.");
      break;
    }
    //ensure that user has sufficient privileges to view this user.
    if ($user->id != intval($_REQUEST['id']) && !$user->isAdmin()) {
      display_error("Error: Insufficient privileges", "You can't view this user.");
      break;
    }    
    $userObject = $database->queryFirstRow("SELECT * FROM `seinma_tageti`.`users` LEFT OUTER JOIN `seinma_llusers`.`ll_users` ON `users`.`id` = `ll_users`.`userid` WHERE `users`.`id` = ".intval($_REQUEST['id'])." LIMIT 1");
    if (!$userObject) {
      display_error("Error: Invalid user ID", "Please check your ID and try again.");
      break;
    }
    echo "<h1>".escape_output($userObject['username'])." <small>(<a href='user.php?action=edit&id=".intval($_REQUEST['id'])."'>edit</a>)</small></h1>\n";
    display_user_profile($database, $user, intval($_REQUEST['id']));
    break;
  default:
  case 'index':
    echo "<h1>Users</h1>\n";
    display_users($database, $user);
    echo "<a href='user.php?action=new'>Add a new user</a><br />\n";
    break;
}
display_footer();
?>