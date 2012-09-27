<?php
include_once("global/includes.php");

if (!$user->loggedIn()) {
  header("Location: index.php");
}
start_html($database, $user, "TagETI", "Dashboard", $_REQUEST['status'], $_REQUEST['class']);

?>
<div class="row-fluid">
  <div class="span4">
    <h1>Welcome!</h1>
    <p>You are now logged in. Here's what I've got on my to-do list:</p>
    <ol>
      <li><s>Import tag admins/moderators</s></li>
      <li><s>Let users indicate tags they want to manage through TagETI</s></li>
      <li>Basic tag management interface for admins and mods</li>
      <li><s>Rework adding tags to manage so that adding Tomoyo isn't strictly required</s></li>
      <li>Tag management logs</li>
      <li>Greasemonkey script (ongoing)</li>
      <li>User (post) flagging/reporting for mods</li>
      <li>Automated infraction criteria</li>
    </ol>
  </div>
  <div class="span4">
    <div class="row-fluid">
      <h2>Notifications</h2>
<?php
  // TODO: entries here for tags that have suspensions/bans that are out-of-sync
  echo "Coming soon!";
?>
    </div>
    <div class="row-fluid">
      <h2>Tag Activity</h2>
<?php
  // TODO: tag update feed containing bans, suspensions, 
  echo "Coming soon!"
?>
    </div>
  </div>
  <div class="span4">
    <div class="row-fluid">
      <h2>Latest topics</h2>
<?php
  $topics = $user->getAllTagTopics();
  if (count($topics) > 0) {
    echo "<ul>\n";
    foreach ($topics as $topic) {
      echo "<li><a href='https://boards.endoftheinter.net/showmessages.php?topic=".intval($topic['topic_id'])."' target='_blank'>".escape_output($topic['title'])."</a></li>\n";
    }
    echo "</ul>\n";
  } else {
    echo "<em>No topics to show.</em>";
  }
?>
    </div>
  </div>
</div>
<?php
display_footer();
?>