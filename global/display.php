﻿<?php

function humanize($str) {
  $str = trim(strtolower($str));
  $str = preg_replace('/_/', ' ', $str);
  $str = preg_replace('/[^a-z0-9\s+]/', '', $str);
  $str = preg_replace('/\s+/', ' ', $str);
  $str = explode(' ', $str);

  $str = array_map('ucwords', $str);

  return implode(' ', $str);
}

function format_mysql_timestamp($date) {
  return date('n/j/Y', strtotime($date));
}

function display_post_time($unixtime) {
  return date('Y/m/d H:i', $unixtime);
}

function escape_output($input) {
  if ($input == '' || $input == 'NULL') {
    return '';
  }
  return htmlspecialchars(html_entity_decode($input, ENT_QUOTES, "UTF-8"), ENT_QUOTES, "UTF-8");
}

function redirect_to($redirect_array) {
  $location = (isset($redirect_array['location'])) ? $redirect_array['location'] : 'index.php';
  $status = (isset($redirect_array['status'])) ? $redirect_array['status'] : '';
  $class = (isset($redirect_array['class'])) ? $redirect_array['class'] : '';
  
  $redirect = "Location: ".$location;
  if ($status != "") {
    if (strpos($location, "?") === FALSE) {
      $redirect .= "?status=".$status."&class=".$class;
    } else {
      $redirect .= "&status=".$status."&class=".$class;
    }
  }
  header($redirect);
}

function js_redirect_to($redirect_array) {
  $location = (isset($redirect_array['location'])) ? $redirect_array['location'] : 'index.php';
  $status = (isset($redirect_array['status'])) ? $redirect_array['status'] : '';
  $class = (isset($redirect_array['class'])) ? $redirect_array['class'] : '';
  
  $redirect = ROOT_URL."/".$location;
  if ($status != "") {
    if (strpos($location, "?") === FALSE) {
      $redirect .= "?status=".urlencode($status)."&class=".urlencode($class);
    } else {
      $redirect .= "&status=".urlencode($status)."&class=".urlencode($class);
    }
  }
  echo "window.location.replace(\"".$redirect."\");";
  exit;
}

function display_http_error($code=500, $contents="") {
  switch (intval($code)) {
    case 301:
      $subtitle = "Moved Permanently";
      $bodyText = $contents;
      break;
    case 403:
      $subtitle = "Forbidden";
      $bodyText = "I'm sorry, Dave. I'm afraid I can't do that.";
      break;
    case 404:
      $subtitle = "Not Found";
      $bodyText = "Oh geez. We couldn't find the page you were looking for; please check your URL and try again.";
      break;
    default:
    case 500:
      $subtitle = "Internal Server Error";
      $bodyText = "Whoops! We had problems processing your request. Please go back and try again!";
      break;
  }

  header('HTTP/1.0 '.intval($code).' '.$subtitle);
  echo $bodyText;
  exit;
}

function display_error($title="Error", $text="An unknown error occurred. Please try again.") {
  echo "<h1>".escape_output($title)."</h1>
  <p>".escape_output($text)."</p>";
}

function start_html($database, $user, $title="TagETI", $subtitle="", $status="", $statusClass="") {
  echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n".'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">'."\n".'<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>'.escape_output($title).($subtitle != "" ? " - ".escape_output($subtitle) : "").'</title>
	<link rel="shortcut icon" href="/favicon.ico" />
	<link rel="stylesheet" href="css/bootstrap.min.css" type="text/css" />
	<link rel="stylesheet" href="css/bootstrap-responsive.min.css" type="text/css" />
	<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" type="text/css" />
	<link rel="stylesheet" href="css/jquery.dataTables.css" type="text/css" />
	<link rel="stylesheet" href="css/tageti.css" type="text/css" />
  <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
  <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js"></script>
  <script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>
	<script type="text/javascript" language="javascript" src="js/jquery.dropdownPlain.js"></script>
	<script type="text/javascript" language="javascript" src="js/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="https://www.google.com/jsapi"></script>
  <script type="text/javascript" src="js/d3.v2.min.js"></script>
  <script type="text/javascript" src="js/d3-helpers.js"></script>
	<script type="text/javascript" language="javascript" src="js/bootstrap.min.js"></script>
	<script type="text/javascript" language="javascript" src="js/bootstrap-dropdown.js"></script>
	<script type="text/javascript" language="javascript" src="js/tageti.js"></script>'."\n".'</head>'."\n".'<body>
  <div class="navbar navbar-inverse navbar-fixed-top">
    <div class="navbar-inner">
      <div class="container-fluid">
        <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </a>
        <a href="./index.php" class="brand">TagETI</a>
        <div class="nav-collapse">
          <ul class="nav">'."\n";
  if ($user->loggedIn()) {
    foreach ($user->managedTags as $tag) {
      /*
      echo '              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                  '.escape_output($tag->name).'
                  <b class="caret"></b>
                </a>
                <ul class="dropdown-menu">
                  <li><a href="tag.php?action=show&id='.intval($tag->id).'">View</a></li>
                  <li><a href="tag.php?action=edit&id='.intval($tag->id).'">Edit</a></li>
                </ul>
              </li>
              <li class="divider-vertical"></li>'."\n";
      */
      echo '              <li><a href="tag.php?action=show&id='.intval($tag->id).'">'.escape_output($tag->name).'</a></li>
              <li class="divider-vertical"></li>'."\n";
    }
    echo '              <li><a href="/tag.php?action=new">Add a tag</a></li>'."\n";
  }
  echo '              <li class="divider-vertical"></li>
          </ul>
          <ul class="nav pull-right">
            <li class="divider-vertical"></li>
            <li class="dropdown">'."\n";
  if ($user->loggedIn()) {
    echo '              <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="icon-user icon-white"></i>'.escape_output($user->username).'<b class="caret"></b></a>
              <ul class="dropdown-menu">
                <a href="/user.php?action=show&id='.intval($user->id).'">Profile</a>'."\n";
    if ($user->isAdmin() && !isset($user->switched_user)) {
      echo '              <a href="/user.php?action=switch_user">Switch Users</a>'."\n";
    }
    if (isset($user->switched_user) && is_numeric($user->switched_user)) {
      echo '              <a href="/user.php?action=switch_back">Switch Back</a>'."\n";
    }
    echo '                <a href="/logout.php">Sign out</a>
              </ul>'."\n";
  } else {
    echo '              <a href="#" class="dropdown-toggle" data-toggle="dropdown">Sign in<b class="caret"></b></a>
              <ul class="dropdown-menu">'."\n";
    display_login_form();
    echo '              </ul>'."\n";
  }
  echo '            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  <div class="container-fluid">'."\n";
  if ($status != "") {
    echo '<div class="alert alert-'.escape_output($statusClass).'">
  <button class="close" data-dismiss="alert" href="#">×</button>
  '.escape_output($status)."\n".'</div>'."\n";
  }
}

function display_login_form() {
  echo '<form id="login_form" accept-charset="UTF-8" action="/login.php" method="post">
  <label for="username">Username</label>
  <input id="username" name="username" size="30" />
  <input class="btn btn-primary" name="commit" type="submit" value="Sign in" />'."\n".'</form>'."\n";
}

function display_month_year_dropdown($select_id="", $select_name_prefix="form_entry", $selected=False) {
  if ($selected === false) {
    $selected = array( 0 => intval(date('n')), 1 => intval(date('Y')));
  }
  echo "<select id='".escape_output($select_id)."' name='".escape_output($select_name_prefix)."[qa_month]'>\n";
  for ($month_i = 1; $month_i <= 12; $month_i++) {
    echo "  <option value='".$month_i."'".(($selected[0] === $month_i) ? "selected='selected'" : "").">".htmlentities(date('M', mktime(0, 0, 0, $month_i, 1, 2000)), ENT_QUOTES, "UTF-8")."</option>\n";
  }
  echo "</select>\n<select id='".escape_output($select_id)."' name='".escape_output($select_name_prefix)."[qa_year]'>\n";
  for ($year = 2007; $year <= intval(date('Y', time())); $year++) {
    echo "  <option value='".$year."'".(($selected[1] === $year) ? "selected='selected'" : "").">".$year."</option>\n";
  }
  echo "</select>\n";
}

function display_ok_notok_dropdown($select_id="ok_notok", $selected=0) {
  echo "<select id='".escape_output($select_id)."' name='".escape_output($select_id)."'>
                    <option value=1".((intval($selected) == 1) ? " selected='selected'" : "").">OK</option>
                    <option value=0".((intval($selected) == 0) ? " selected='selected'" : "").">NOT OK</option>\n</select>\n";
}

function display_register_form($database, $action=".") {
  echo '    <form class="form-horizontal" name="register" method="post" action="'.$action.'">
      <fieldset>
        <legend>Sign up</legend>
        <div class="control-group">
          <label class="control-label">Name</label>
          <div class="controls">
            <input type="text" class="" name="name" id="name" />
          </div>
        </div>
        <div class="control-group">
          <label class="control-label">Email</label>
          <div class="controls">
            <input type="text" class="" name="email" id="email" />
          </div>
        </div>
        <div class="control-group">
          <label class="control-label">Password</label>
          <div class="controls">
            <input type="password" class="" name="password" id="password" />
          </div>
        </div>
        <div class="control-group">
          <label class="control-label">Confirm password</label>
          <div class="controls">
            <input type="password" class="" name="password_confirmation" id="password_confirmation" />
          </div>
        </div>
        <div class="control-group">
          <label class="control-label">Facility</label>
          <div class="controls">\n';
  echo display_facility_dropdown($database);
  echo '          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Sign up</button>
        </div>
      </fieldset>
    </form>\n';
}

function display_users($database, $user) {
  //lists all users.
  return;
  echo "<table class='table table-striped table-bordered dataTable'>
  <thead>
    <tr>
      <th>Username</th>
      <th>Email</th>
      <th>Role</th>
      <th>Facility</th>
      <th></th>
      <th></th>
    </tr>
  </thead>
  <tbody>\n";
  if ($user->isAdmin()) {
    $users = $database->stdQuery("SELECT `users`.`id`, `users`.`name`, `users`.`email`, `users`.`userlevel`, `facilities`.`name` AS `facility_name` FROM `users` LEFT OUTER JOIN `facilities` ON `users`.`facility_id` = `facilities`.`id` ORDER BY `users`.`name` ASC");
  } else {
    $users = $database->stdQuery("SELECT `users`.`id`, `users`.`name`, `users`.`email`, `users`.`userlevel`, `facilities`.`name` AS `facility_name` FROM `users` LEFT OUTER JOIN `facilities` ON `users`.`facility_id` = `facilities`.`id` WHERE `users`.`facility_id` = ".intval($user->facility_id)." ORDER BY `users`.`name` ASC");
  }
  while ($thisUser = mysqli_fetch_assoc($users)) {
    echo "    <tr>
      <td><a href='user.php?action=show&id=".intval($thisUser['id'])."'>".escape_output($thisUser['name'])."</a></td>
      <td>".escape_output($thisUser['email'])."</td>
      <td>".escape_output(convert_userlevel_to_text($thisUser['userlevel']))."</td>
      <td>".escape_output($thisUser['facility_name'])."</td>
      <td>"; if ($user->isAdmin()) { echo "<a href='user.php?action=edit&id=".intval($thisUser['id'])."'>Edit</a>"; } echo "</td>
      <td>"; if ($user->isAdmin()) { echo "<a href='user.php?action=delete&id=".intval($thisUser['id'])."'>Delete</a>"; } echo "</td>
    </tr>\n";
  }
  echo "  </tbody>\n</table>\n";
}

function display_userlevel_dropdown($database, $select_id="userlevel", $selected=0) {
  echo "<select id='".escape_output($select_id)."' name='".escape_output($select_id)."'>\n";
  for ($userlevel = 0; $userlevel <= 3; $userlevel++) {
    echo "  <option value='".intval($userlevel)."'".(($selected == intval($userlevel)) ? "selected='selected'" : "").">".escape_output(convert_userlevel_to_text($userlevel))."</option>\n";
  }
  echo "</select>\n";
}

function display_user_edit_form($database, $user, $id=false) {
  // displays a form to edit user parameters.
  if (!($id === false)) {
    $userObject = $database->queryFirstRow("SELECT * FROM `users` WHERE `id` = ".intval($id)." LIMIT 1");
    if (!$userObject) {
      $id = false;
    } elseif (intval($userObject['facility_id']) != $user->facility_id) {
      echo "You may only modify users under your own facility.";
      return;
    }
  }    
  echo "<form action='user.php".(($id === false) ? "" : "?id=".intval($id))."' method='POST' class='form-horizontal'>\n".(($id === false) ? "" : "<input type='hidden' name='user[id]' value='".intval($id)."' />")."
  <fieldset>
    <div class='control-group'>
      <label class='control-label' for='user[name]'>Name</label>
      <div class='controls'>
        <input name='user[name]' type='text' class='input-xlarge' id='user[name]'".(($id === false) ? "" : " value='".escape_output($userObject['name'])."'").">
      </div>
    </div>
    <div class='control-group'>
      <label class='control-label' for='user[password]'>Password</label>
      <div class='controls'>
        <input name='user[password]' type='password' class='input-xlarge' id='user[password]' />
      </div>
    </div>
    <div class='control-group'>
      <label class='control-label' for='user[password_confirmation]'>Confirm Password</label>
      <div class='controls'>
        <input name='user[password_confirmation]' type='password' class='input-xlarge' id='user[password_confirmation]' />
      </div>
    </div>
    <div class='control-group'>
      <label class='control-label' for='user[name]'>Email</label>
      <div class='controls'>
        <input name='user[email]' type='email' class='input-xlarge' id='user[email]'".(($id === false) ? "" : " value='".escape_output($userObject['email'])."'").">
      </div>
    </div>\n";
  if ($user->isAdmin()) {
    echo "    <div class='control-group'>
      <label class='control-label' for='user[facility_id]'>Facility</label>
      <div class='controls'>\n";
  display_facility_dropdown($database, "user[facility_id]", ($id === false) ? 0 : $userObject['facility_id']);
  echo "      </div>
    </div>
    <div class='control-group'>
      <label class='control-label' for='user[userlevel]'>Role</label>
      <div class='controls'>\n";
  display_userlevel_dropdown($database, "user[userlevel]", ($id === false) ? 0 : intval($userObject['userlevel']));
  echo "      </div>
    </div>\n";
    }
  echo "    <div class='form-actions'>
      <button type='submit' class='btn btn-primary'>".(($id === false) ? "Add User" : "Save changes")."</button>
      <a href='#' onClick='window.location.replace(document.referrer);' class='btn'>".(($id === false) ? "Go back" : "Discard changes")."</a>
    </div>
  </fieldset>\n</form>\n";
}

function display_user_switch_form($database, $user) {
  echo "<form action='user.php' method='POST' class='form-horizontal'>
  <fieldset>
    <div class='control-group'>
      <label class='control-label' for='switch_username'>Username</label>
      <div class='controls'>
        <input name='switch_username' type='text' class='input-xlarge' id='switch_username' />
      </div>
    </div>
    <div class='form-actions'>
      <button type='submit' class='btn btn-primary'>Switch</button>
      <a href='#' onClick='window.location.replace(document.referrer);' class='btn'>Back</a>
    </div>
  </fieldset>\n</form>\n";
}

function display_user_profile($database, $user, $user_id) {
  return;
  $userObject = new User($database, $user_id);
  $facility = $database->queryFirstValue("SELECT `name` FROM `facilities` WHERE `id` = ".intval($userObject->facility_id)." LIMIT 1");
  $form_entries = $database->stdQuery("SELECT `form_entries`.*, `forms`.`name` AS `form_name`, `machines`.`name` AS `machine_name` FROM `form_entries` 
                                        LEFT OUTER JOIN `forms` ON `forms`.`id` = `form_entries`.`form_id`
                                        LEFT OUTER JOIN `machines` ON `machines`.`id` = `form_entries`.`machine_id`
                                        WHERE `user_id` = ".intval($user_id)." 
                                        ORDER BY `updated_at` DESC");
  echo "<dl class='dl-horizontal'>
    <dt>Email</dt>
    <dd>".escape_output($userObject->email)."</dd>
    <dt>Facility</dt>
    <dd><a href='facility.php?action=show&id=".intval($userObject->facility_id)."'>".escape_output($facility)."</a></dd>
    <dt>User Role</dt>
    <dd>".escape_output(convert_userlevel_to_text($userObject->userlevel))."</dd>
  </dl>\n";
  if (convert_userlevel_to_text($userObject->userlevel) == 'Physicist') {
    $form_approvals = $database->stdQuery("SELECT `form_entries`.`id`, `qa_month`, `qa_year`, `machine_id`, `machines`.`name` AS `machine_name`, `user_id`, `users`.`name` AS `user_name`, `approved_on` FROM `form_entries` LEFT OUTER JOIN `machines` ON `machines`.`id` = `form_entries`.`machine_id` LEFT OUTER JOIN `users` ON `users`.`id` = `form_entries`.`user_id` WHERE `approved_user_id` = ".intval($userObject->id)." ORDER BY `approved_on` DESC");
    echo "  <h3>Approvals</h3>
  <table class='table table-striped table-bordered dataTable'>
    <thead>
      <tr>
        <th>QA Date</th>
        <th>Machine</th>
        <th>Submitter</th>
        <th>Approval Date</th>
      </tr>
    </thead>
    <tbody>\n";
    while ($approval = mysqli_fetch_assoc($form_approvals)) {
      echo "      <tr>
        <td><a href='form_entry.php?action=edit&id=".intval($approval['id'])."'>".escape_output($approval['qa_year']."/".$approval['qa_month'])."</a></td>
        <td><a href='form.php?action=show&id=".intval($approval['machine_id'])."'>".escape_output($approval['machine_name'])."</a></td>
        <td><a href='user.php?action=show&id=".intval($approval['user_id'])."'>".escape_output($approval['user_name'])."</a></td>
        <td>".escape_output(format_mysql_timestamp($approval['approved_on']))."</td>
      </tr>\n";
    }
    echo "    </tbody>
  </table>\n";
  }
  echo "  <h3>Form Entries</h3>
  <table class='table table-striped table-bordered dataTable'>
    <thead>
      <tr>
        <th>Form</th>
        <th>Machine</th>
        <th>Comments</th>
        <th>QA Date</th>
        <th>Submitted on</th>
        <th></th>
      </tr>
    </thead>
    <tbody>\n";
  while ($form_entry = mysqli_fetch_assoc($form_entries)) {
    echo "    <tr>
      <td><a href='form.php?action=show&id=".intval($form_entry['form_id'])."'>".escape_output($form_entry['form_name'])."</a></td>
      <td><a href='form.php?action=show&id=".intval($form_entry['machine_id'])."'>".escape_output($form_entry['machine_name'])."</a></td>
      <td>".escape_output($form_entry['comments'])."</td>
      <td>".escape_output($form_entry['qa_year']."/".$form_entry['qa_month'])."</td>
      <td>".escape_output(format_mysql_timestamp($form_entry['created_at']))."</td>
      <td><a href='form_entry.php?action=edit&id=".intval($form_entry['id'])."'>View</a></td>
    </tr>\n";
  }
  echo "    </tbody>
  </table>\n";
}

function display_tag_info($database, $user, $tag_id) {
  try {
    $tag = new Tag($database, $tag_id);
  } catch (Exception $e) {
    display_error("Error: Invalid Tag ID", "Please check the ID provided and try again.");
    return;
  }
  echo "<blockquote>
  <p>".$tag->description."</p>\n</blockquote>\n";
    // fetch the historical tag activity data.
  $timelines = $user->getTagActivity(array($tag), False, False, time(), 10);
  $postCountTimeline = $timelines['postCount'];
  if (count($postCountTimeline) > 0) {
    echo "<div class='row-fluid'>
  <div class='span12'>\n";
    displayTagActivityGraph("Number of Posts", $postCountTimeline, array($tag), "postCountTimeline");
    echo "  </div>\n</div>\n";
  }
  echo "<div class='row-fluid'>
  <div class='span4'>
    <h4 class='center-horizontal'>Related Tags</h4>\n<ul>\n";
  foreach ($tag->relatedTags as $relatedTag) {
    echo "      <li><a href='tag.php?action=show&id=".intval($relatedTag['id'])."'>".escape_output($relatedTag['name'])."</a><button type='button' class='close remove-tag-link remove-related-tag-link' data-dismiss='alert'>×</button></li>\n";
  }
  if ($user->isTagAdmin($tag_id)) {
    echo "      <li><a href='#' class='add-tag-link add-related-tag-link'>Add a tag</a></li>\n";
  }
  echo "    </ul>\n  </div>
  <div class='span4'>
    <h4 class='center-horizontal'>Dependent Tags</h4>
    <ul>\n";
  foreach ($tag->dependencyTags as $dependencyTag) {
    echo "      <li><a href='tag.php?action=show&id=".intval($dependencyTag['id'])."'>".escape_output($dependencyTag['name'])."</a><button type='button' class='close remove-tag-link remove-dependency-tag-link' data-dismiss='alert'>×</button></li>\n";
  }
  if ($user->isTagAdmin($tag_id)) {
    echo "      <li><a href='#' class='add-tag-link add-dependency-tag-link'>Add a tag</a></li>\n";
  }
  echo "    </ul>\n  </div>
  <div class='span4'>
    <h4 class='center-horizontal'>Forbidden Tags</h4>
    <ul>\n";
  foreach ($tag->forbiddenTags as $forbiddenTag) {
    echo "      <li><a href='tag.php?action=show&id=".intval($forbiddenTag['id'])."'>".escape_output($forbiddenTag['name'])."</a><button type='button' class='close remove-tag-link remove-forbidden-tag-link' data-dismiss='alert'>×</button></li>\n";
  }
  if ($user->isTagAdmin($tag_id)) {
    echo "      <li><a href='#' class='add-tag-link add-forbidden-tag-link'>Add a tag</a></li>\n";
  }
  echo "    </ul>\n  </div>\n</div>\n";
  echo "<h3>Latest Topics</h3>\n";
  $latestTopics = $tag->getLatestTopics();
  echo "<div class='row-fluid'>
  <div class='span12'>
    <table class='table dataTable table-bordered table-striped'>
      <thead>
        <tr>
          <th>Title</th>
          <th>Creator</th>
          <th># Posts</th>
          <th>Last Posted</th>
        </tr>
      </thead>
      <tbody>\n";
  foreach ($latestTopics as $topic) {
    echo "        <tr>
          <td><a href='https://boards.endoftheinter.net/showmessages.php?topic=".intval($topic['topic_id'])."' target='_blank'>".escape_output($topic['title'])."</a></td>
          <td><a href='https://endoftheinter.net/profile.php?user=".intval($topic['user_id'])."' target='_blank'>".escape_output($topic['username'])."</a></td>
          <td>".intval($topic['postCount'])."</td>
          <td>".display_post_time($topic['lastPostTime'])."</td>
        </tr>\n";
  }
  echo "      </tbody>
      </table>
    </div>
  </div>\n";
}

function display_tag_add_form($database, $user) {
  // displays a form to add a tag to tagETI to start managing.
  echo "<div class='row-fluid'>
  <div class='span6'>
    <h4>Glad you're here! To start managing a tag through TagETI, you have to:</h4>
    <ol>
      <li>Be a mod or admin for your tag</li>
      <li>Install the TagETI Greasemonkey script (coming soon!)</li>
    </ol>
  </div>
  <div class='span6'>
    <h5>To get the most out of TagETI, you can (but don't have to!):</h5>
    <ol>
      <li>Go to the tag's management page on ETI (https://boards.endoftheinter.net/tag.php?tag=YOUR_TAG_NAME_HERE)</li>
      <li>Add 'Sakagami Tomoyo' to the list of administrators</li>
      <li>Remove all admins/mods besides you and Sakagami Tomoyo</li>
      <li>Have your mods/admins install the TagETI Greasemonkey script (coming soon!)</li>
    </ol>
    <p>This will allow you to track all tag moderation through TagETI, giving you a more complete history.</p>
  </div>\n</div>\n<div class='row-fluid'>&nbsp;</div>\n<div class='row-fluid'>\n
  <div class='span12' style='text-align: center;'>
    <p>When you're ready, select the tag you want to add below and hit Add Tag!</p>
    <form class='form-inline' action='tag.php?action=new' method='post'>
      <select id='tag_name' name='tag_name'>\n";
  foreach ($user->unManagedTags as $tag) {
    echo "        <option value='".escape_output($tag->name)."'>".escape_output($tag->name)."</option>\n";
  }
  echo "      </select>
    <a class='btn btn-xlarge btn-primary' href='#' id='add-tag-to-manage'>Add Tag</a>
    </form>
  </div>\n</div>\n";
}

function display_history_json($database, $user, $fields = array(), $machines=array()) {
  header('Content-type: application/json');
  $return_array = array();
  
  if (!$user->loggedIn()) {
    $return_array['error'] = "You must be logged in to view history data.";
  } elseif (!is_array($fields) || !is_array($machines)) {
    $return_array['error'] = "Please provide a valid list of fields and machines.";  
  } else {
    foreach ($fields as $field) {
      foreach ($machines as $machine) {
        $line_array = array();
        $values = $database->stdQuery("SELECT `form_field_id`, `form_entries`.`machine_id`, `form_entries`.`qa_month`, `form_entries`.`qa_year`, `value` FROM `form_values`
                                    LEFT OUTER JOIN `form_entries` ON `form_entry_id` = `form_entries`.`id`
                                    WHERE `form_field_id` = ".intval($field)." && `machine_id` = ".intval($machine)."
                                    ORDER BY `qa_year` ASC, `qa_month` ASC");
        while ($value = mysqli_fetch_assoc($values)) {
          $line_array[] = array('x' => intval($value['qa_month'])."/".intval($value['qa_year']),
                                  'y' => doubleval($value['value']),
                                  'machine' => intval($value['machine_id']),
                                  'field' => intval($value['form_field_id']));
        }
        if (count($line_array) > 1) {
          $return_array[] = $line_array;
        }
      }
    }
  }
  echo json_encode($return_array);
}

function display_history_plot($database, $user, $form_id) {
  //displays plot for a particular form.
  $formObject = $database->queryFirstRow("SELECT * FROM `forms` WHERE `id` = ".intval($form_id)." LIMIT 1");
  if (!$formObject) {
    echo "The form ID you provided was invalid. Please try again.\n";
  } else {
    $formFields = $database->stdQuery("SELECT `id`, `name` FROM `form_fields`
                                        WHERE `form_id` = ".intval($form_id)."
                                        ORDER BY `name` ASC");
    $machines = $database->stdQuery("SELECT `id`, `name` FROM `machines`
                                        WHERE `machine_type_id` = ".intval($formObject['machine_type_id'])."
                                        ORDER BY `name` ASC");
    echo "<div id='vis'></div>
  <form action='#'>
    <input type='hidden' id='form_id' name='form_id' value='".intval($form_id)."' />
    <div class='row-fluid'>
      <div class='span4'>
        <div class='row-fluid'><h3 class='span12' style='text-align:center;'>Machines</h3></div>
        <div class='row-fluid'>
          <select multiple='multiple' id='machines' class='span12' size='10' name='machines[]'>\n";
    while ($machine = mysqli_fetch_assoc($machines)) {
      echo "           <option value='".intval($machine['id'])."'>".escape_output($machine['name'])."</option>\n";
    }
    echo "         </select>
        </div>
      </div>
      <div class='span4'>
        <div class='row-fluid'><h3 class='span12' style='text-align:center;'>Fields</h3></div>
        <div class='row-fluid'>
          <select multiple='multiple' id='form_fields' class='span12' size='10' name='form_fields[]'>\n";
    while ($field = mysqli_fetch_assoc($formFields)) {
      echo "            <option value='".intval($field['id'])."'>".escape_output($field['name'])."</option>\n";
    }
    echo "          </select>
        </div>
      </div>
      <div class='span4'>
        <div class='row-fluid'><h3 class='span12' style='text-align:center;'>Time Range</h3></div>
        <div class='row-fluid'>
          <div class='span12' style='text-align:center;'>(Coming soon)</div>
        </div>
      </div>
    </div>
    <div class='row-fluid'>
      <div class='span12' style='text-align:center;'>As a reminder, you can highlight multiple fields by either clicking and dragging, or holding down Control and clicking on the fields you want.</div>
    </div>
    <div class='form-actions'>
      <a class='btn btn-xlarge btn-primary' href='#' onClick='drawLargeD3Plot();'>Redraw Plot</a>
    </div>
  </form>\n";
  }
}

function display_footer() {
  echo '    <hr />
    <p>Created and maintained by <a href="http://llanim.us">shaldengeki</a>.</p>
  </div>'."\n".'</body>'."\n".'</html>';
}

?>