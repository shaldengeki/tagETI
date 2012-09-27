<?php
include_once("global/includes.php");
if (!$user->loggedIn()) {
  header("Location: index.php");
}

if ($_REQUEST['action'] == 'refresh_all' && $user->isAdmin()) {
  // check to see if an update is in progress.
  $checkUpdateStatus = intval($database->queryFirstValue("SELECT `value` FROM `indices` WHERE `name` = 'tag_update' LIMIT 1"));
  if (!$checkUpdateStatus) {
    redirect_to(array('location' => "tag.php", 'status' => "An update is already in progress. Please check back later."));
  }
	$tagRefresh = refreshAllTags($database, $user);
  redirect_to($tagRefresh);
} elseif ($_REQUEST['action'] == 'check_add' && isset($_REQUEST['name'])) {
  // first, check that this is actually a tag and that it's not already managed.
  $tagInfo = $database->queryFirstRow("SELECT `seinma_llusers`.`tags`.`id`, `".MYSQL_DATABASE."`.`tags`.`managed` FROM `seinma_llusers`.`tags` LEFT OUTER JOIN `".MYSQL_DATABASE."`.`tags` ON `".MYSQL_DATABASE."`.`tags`.`id` = `seinma_llusers`.`tags`.`id` WHERE `seinma_llusers`.`tags`.`name` = ".$database->quoteSmart($_REQUEST['name'])." LIMIT 1");
  if (!$tagInfo) {
    js_redirect_to(array('location' => "tag.php?action=new", 'status' => "The specified tag isn't in our database. Please wait a bit and then try again."));
  }
  $tag_id = intval($tagInfo['id']);
  $managed = intval($tagInfo['managed']);
  if (intval($managed)) {
    js_redirect_to(array('location' => "tag.php?action=new", 'status' => "That tag is already being managed by TagETI."));
  }

  // next, reload this tag.
  $tagRefresh = refreshTag($database, $user, $_REQUEST['name']);

  // update the tag to be managed.
  $updateTag = $database->stdQuery("UPDATE `".MYSQL_DATABASE."`.`tags` SET `managed` = 1 WHERE `id` = ".intval($tag_id)." LIMIT 1");
  if (!$updateTag) {
    js_redirect_to(array('location' => "tag.php?action=new", 'status' => "An error occurred while setting this tag as managed. Please try again."));
  }
  /* This is no longer required.
  // now check to see if we're able to hook in.
  $checkPrivs = $managementUser->isTagStaff($tag_id);
  if (!$checkPrivs) {
    js_redirect_to(array('location' => "tag.php?action=new", 'status' => "It looks like you haven't added us to this tag's admins. Please check and try again."));
  }
  */
  js_redirect_to(array('location' => "tag.php?action=show&id=".intval($tag_id), 'status' => "Congratulations! You can now manage this tag through TagETI.", 'class' => 'success'));
}

start_html($database, $user, "TagETI", "Manage Tags", $_REQUEST['status'], $_REQUEST['class']);

switch($_REQUEST['action']) {
  case 'new':
    echo "<h1>Add a tag</h1>\n";
    display_tag_add_form($database, $user);
    break;
  case 'edit':
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id'])) {
      display_error("Error: Invalid tag ID", "Please check your ID and try again.");
      break;
    }
    //ensure that user has sufficient privileges to modify this tag.
    if (!$user->isTagStaff(intval($_REQUEST['id']))) {
      display_error("Error: Insufficient privileges", "You must be staff on this tag to modify it.");
      break;
    }
    $tagName = $database->stdQuery("SELECT `name` FROM `seinma_llusers`.`tags` WHERE `id` = ".intval($_REQUEST['id']));
    if (!$tagName) {
      display_error("Error: Invalid tag", "The given tag doesn't exist.");
      break;
    }
    echo "<h1>".escape_output($tagName)."</h1>\n";
    display_tag_edit_form($database, $user, intval($_REQUEST['id']));
    break;
  case 'show':
    if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id'])) {
      display_error("Error: Invalid tag ID", "Please check your ID and try again.");
      break;
    }
    //ensure that user has sufficient privileges to view this machine.
    $tagObject = $database->queryFirstRow("SELECT * FROM `seinma_llusers`.`tags` LEFT OUTER JOIN `".MYSQL_DATABASE."`.`tags` ON `".MYSQL_DATABASE."`.`tags`.`id` = `seinma_llusers`.`tags`.`id` WHERE `seinma_llusers`.`tags`.`id` = ".intval($_REQUEST['id'])." LIMIT 1");
    if (!$tagObject) {
      display_error("Error: Invalid tag ID", "Please check your ID and try again.");
      break;    
    } elseif (!$user->isAdmin() && !$user->isTagStaff(intval($_REQUEST['id']))) {
      display_error("Error: Insufficient privileges", "You may only view tags you're staff on.");
      break;
    }
    echo "<h1>".escape_output($tagObject['name'])." <small>(<a href='tag.php?action=edit&id=".intval($_REQUEST['id'])."'>edit</a>)</small></h1>\n";
    display_tag_info($database, $user, intval($_REQUEST['id']));
    break;
  default:
  case 'index':
    echo "<h1>Tags</h1>\n";
    display_tags($database, $user);
    echo "<a href='tag.php?action=new'>Add a new tag</a><br />\n";
    break;
}
display_footer();
?>