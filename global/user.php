<?php

class User {
  public $id;
  public $username;
  public $tags;
  public $managedTags;
  public $unManagedTags;
  public $lastLoginCheckTime;
  public $role;
  public $switched_user;
  public $dbConn;
  public function __construct($dbConn, $id=False, $username=False) {
    $this->dbConn = $dbConn;
    if ($id === 0) {
      // creating a new user (or specifying a guest user). initialize blank values.
      $this->id = 0;
      $this->username = $username;
      $this->tags = array();
      $this->managedTags = array();
      $this->unManagedTags = array();
      $this->role = 0;
    } else {
      if (!$id || !is_numeric($id)) {
        if (!($username === False)) {
          $this->id = intval($this->dbConn->queryFirstValue("SELECT `userid` FROM `seinma_llusers`.`ll_users` WHERE `username` = ".$this->dbConn->quoteSmart($username)." LIMIT 1"));
        } else {
          throw new Exception('Invalid Arguments');
        }
      } else {
        $userInfo = $this->dbConn->queryFirstRow("SELECT `userid`, `username` FROM `seinma_llusers`.`ll_users` WHERE `userid` = ".intval($id)." LIMIT 1");
        $this->id = intval($userInfo['userid']);
        $username = $userInfo['username'];
      }
      if (!$this->id) {
        throw new Exception('ID Not Found');
      }
      $this->username = $username;
      $this->managedTags = $this->getTags(True);
      $this->unManagedTags = $this->getTags(False);
      $this->tags = $this->managedTags + $this->unManagedTags;
      $this->role = $this->getRole();

      if (isset($_SESSION['switched_user'])) {
        $this->switched_user = intval($_SESSION['switched_user']);
      }
    }
  }
  public function loggedIn() {
    /*
      Checks if the current session is logged in as the current user object.
      If the last IP that the user logged into is not the current IP, return False.
      Returns a boolean.
    */
    //if userID is not proper then return False.
    if (intval($this->id) <= 0) {
      return False;
    }
    // if we last checked for login under a second ago then return true.
    if (($this->id == $_SESSION['id']) && $_SESSION['lastLoginCheckTime'] > microtime(true) - 1) {
      return True;
    } elseif (isset($_SESSION['switched_user'])) {
      $checkID = $_SESSION['switched_user'];
    } else {
      $checkID = $this->id;
    }
    $thisUserInfo = $this->dbConn->queryFirstRow("SELECT `ip` FROM `usermap` WHERE `user_id` = ".intval($checkID)." ORDER BY `last_date` DESC LIMIT 1");
    if (!$thisUserInfo || $thisUserInfo['ip'] != $_SERVER['REMOTE_ADDR']) {
      return False;
    }
    $_SESSION['lastLoginCheckTime'] = microtime(true);
    return True;
  }
  public function logFailedLogin($username) {
    /* 
      Enters a failed login entry for a given username and the current IP into the database.
      Returns a boolean.
    */
    $insert_log = $this->dbConn->stdQuery("INSERT IGNORE INTO `failed_logins` (`ip`, `date`, `username`) VALUES ('".$_SERVER['REMOTE_ADDR']."', NOW(), ".$this->dbConn->quoteSmart($username).")");
    if (!$this->dbConn->insert_id) {
      return False;
    } else {
      return True;
    }
  }
  public function insertUsermap() {
    /*
      Inserts a usermap entry for the current IP into the usermap table.
      If the given userID,IP combination already exists in the table, updates the extant row with the current date.
      Returns a boolean.
    */
    $userMapEntry = $this->dbConn->queryFirstRow("SELECT `mapid` FROM `usermap` WHERE `user_id` = ".intval($this->id)." AND `ip` = ".$this->dbConn->quoteSmart($_SERVER['REMOTE_ADDR'])." LIMIT 1");
    if (!$userMapEntry) {
      $insertMapEntry = $this->dbConn->stdQuery("INSERT INTO `usermap` SET `user_id` = ".intval($this->id).", `ip` = ".$this->dbConn->quoteSmart($_SERVER['REMOTE_ADDR']).", `last_date` = ".$this->dbConn->quoteSmart(date('Y-m-d H:i:s')));
      if (!$insertMapEntry) {
        return False;
      }
    } else {
      $updateMapEntry = $this->dbConn->stdQuery("UPDATE `usermap` SET `last_date` = ".$this->dbConn->quoteSmart(date('Y-m-d H:i:s'))." WHERE `user_id` = ".intval($this->id)." AND `ip` = ".$this->dbConn->quoteSmart($_SERVER['REMOTE_ADDR'])." LIMIT 1");
      if (!$updateMapEntry) {
        return False;
      }
    }
    return True;
  }
  public function logIn($username) {
    /*
      Logs the current user into the account specified by $username.
      If an account for $username does not yet exist, creates one and signs them into that account.
      Returns a redirect_to array.
    */
    // rate-limit requests.
    $numFailedRequests = $this->dbConn->queryCount("SELECT COUNT(*) FROM `failed_logins` WHERE `ip` = ".$this->dbConn->quoteSmart($_SERVER['REMOTE_ADDR'])." AND `date` > NOW() - INTERVAL 1 HOUR");
    if ($numFailedRequests > 5) {
      return array("location" => "login.php", "status" => "You have had too many unsuccessful login attempts. Please wait awhile and try again.", 'class' => 'error');
    }
    if (trim($username) == "") {
      return array("location" => "login.php", "status" => "Please enter a username to sign in.");
    }

    $check_ll_username = hitPageSSL('https://boards.endoftheinter.net/scripts/login.php?username='.urlencode($username).'&ip='.$_SERVER['REMOTE_ADDR']);
    if ($check_ll_username != "1:".$username) {
      $this->logFailedLogin($username);
      return array("location" => "login.php", "status" => "Please sign into ETI first.", 'class' => 'error');
    }
    // find this user to set some basic information about them.
    $findUsername = $this->dbConn->queryFirstRow("SELECT `userid`, `username` FROM `seinma_llusers`.`ll_users` WHERE `username` = ".$this->dbConn->quoteSmart($username)." LIMIT 1");
    if (!$findUsername) {
      return array("location" => "login.php", "status" => "You're not in our user database! Please contact shaldengeki so he can add you.", 'class' => 'error');
    }
    // insert user with username if necessary.
    $findLocalUsername = $this->dbConn->queryCount("SELECT COUNT(*) FROM `users` WHERE `id` = ".intval($findUsername['userid'])." LIMIT 1");
    if (!$findLocalUsername) {
      $create_user = $this->create_or_update(array('username' => $username, 'role' => 0));
      if (!$create_user) {
        return array("location" => "login.php", "status" => "An error occurred while signing you up. Please try again.", 'class' => 'error');
      }
    }

    // set the current userID. This is needed to insert a usermap entry.
    $_SESSION['id'] = $this->id = intval($findUsername['userid']);

    // insert usermap entry if necessary.
    $insertUsermap = $this->insertUsermap();

    return array("location" => "main.php", "status" => "Successfully logged in.", 'class' => 'success');
  }
  public function create_or_update($user) {
    /*
      Creates or updates the current user object in the database.
      Takes an array $user of attributes
      Determines whether to create/update based on value of $this->id
      Updates role (only user-modifiable field)
      Returns a boolean.
    */
    //go ahead and register or update this user.
    if ($this->id != 0) {
      //update this user.
      $update_params = array();
      foreach ($user as $parameter => $value) {
        if (!is_array($value)) {
          $update_params[] = "`".$this->dbConn->real_escape_string($parameter)."` = ".$this->dbConn->quoteSmart($value);
        }
      }
      $updateUser = $this->dbConn->stdQuery("UPDATE `users` SET ".implode(", ", $update_params)."  WHERE `id` = ".intval($this->id)." LIMIT 1");
      if (!$updateUser) {
        return False;
      }
    } else {
      //insert this user.
      // find this user's userID if necessary.
      if (!isset($user['id'])) {
        $user['id'] = intval($this->dbConn->queryFirstValue("SELECT `userid` FROM `seinma_llusers`.`ll_users` WHERE `username` = ".$this->dbConn->quoteSmart($user['username'])." LIMIT 1"));
        if (!$user['id']) {
          return False;
        }
      }
      $insertUser = $this->dbConn->stdQuery("INSERT INTO `users` (`id`, `username`, `role`) VALUES (".intval($user['id']).", ".$this->dbConn->quoteSmart($user['username']).", ".intval($user['role']).")");
      if (!$insertUser) {
        return False;
      }
    }
    return True;
  }
  public function switchUser($username, $switch_back=True) {
    /*
      Switches the current user's session out for another user (provided by $username) in the etiStats db.
      If $switch_back is true, packs the current session into $_SESSION['switched_user'] before switching.
      If not, then retrieves the packed session and overrides current session with that info.
      Returns a redirect_to array.
    */
    if ($switch_back) {
      // get user entry in database.
      $findUserID = intval($this->dbConn->queryFirstValue("SELECT `userid` FROM `seinma_llusers`.`ll_users` WHERE `username` = ".$this->dbConn->quoteSmart($username)." LIMIT 1"));
      if (!$findUserID) {
        return array("location" => "main.php", "status" => "The given user to switch to doesn't exist in the database.", 'class' => 'error');
      }
      $newUser = new User($this->dbConn, $findUserID);
      $newUser->switched_user = $_SESSION['id'];
      $_SESSION['lastLoginCheckTime'] = $newUser->lastLoginCheckTime = microtime(true);
      $_SESSION['id'] = $newUser->id;
      $_SESSION['switched_user'] = $newUser->switched_user;
    } else {
      $newUser = new User($this->dbConn, $_SESSION['switched_user']);
      $_SESSION['id'] = $newUser->id;
      $_SESSION['lastLoginCheckTime'] = microtime(true);
      unset($_SESSION['switched_user']);
    }
    return array("location" => "main.php", "status" => "Successfully switched to ".escape_output($newUser->username).".".(($switch_back) ? " LOG OUT OR SWITCH BACK WHEN YOU ARE DONE." : ""), 'class' => 'success');
  }
  public function getTags($managed=NULL) {
    /*
      Retrieves the current user object's tags.
      If $managed is True, retrieves only tags that are managed by tagETI.
      If false, retrieves only tags that are not managed by tagETI.
      If null, retrieves both.
      Returns an array of Tag objects.
    */
    if (is_null($managed)) {
      $managedCriteria = "";
    } else {
      $managedCriteria = "`".MYSQL_DATABASE."`.`tags`.`managed` = ".intval($managed)." && ";
    }

    $tags = $this->dbConn->stdQuery("SELECT `seinma_llusers`.`tags_users`.`tag_id` AS `id` FROM `seinma_llusers`.`tags_users` LEFT OUTER JOIN `".MYSQL_DATABASE."`.`tags` ON `tags`.`id` = `tags_users`.`tag_id` WHERE (".$managedCriteria."`tags_users`.`user_id` = ".intval($this->id)." && `tags_users`.`role` > 0) ORDER BY `seinma_llusers`.`tags_users`.`tag_id` ASC");
    $user_tags = array();
    while ($tag = mysqli_fetch_assoc($tags)) {
        $user_tags[intval($tag['id'])] = new Tag($this->dbConn, intval($tag['id']));
    }
    return $user_tags;
  }
  public function getAllTagTopics($limit=20) {
    if ($limit) {
      $limitString = " LIMIT ".intval($limit);
    } else {
      $limitString = "";
    }
    $topicsQuery = $this->dbConn->stdQuery("SELECT `tags`.`name` AS `tag_name`, `role`, `topic_id`, `userid` AS `user_id`, `title`, `postCount`, `lastPostTime` FROM `seinma_llusers`.`tags_users` LEFT OUTER JOIN `seinma_llusers`.`tags` ON `tags`.`id` = `tags_users`.`tag_id` LEFT OUTER JOIN `seinma_llusers`.`tags_topics` ON `tags_topics`.`tag_id` = `tags_users`.`tag_id` LEFT OUTER JOIN `seinma_llusers`.`topics` ON `topics`.`ll_topicid` = `tags_topics`.`topic_id` WHERE `tags_users`.`user_id` = ".intval($this->id)." && `tags_users`.`role` > 0 GROUP BY `topic_id` ORDER BY `lastPostTime` DESC".$limitString);
    $topics = array();
    while ($topic = $topicsQuery->fetch_assoc()) {
      $topics[] = $topic;
    }
    return $topics;
  }
  public function getRole() {
    $checkUserlevel = $this->dbConn->queryFirstValue("SELECT `role` FROM `users` WHERE `id` = ".intval($this->id)." LIMIT 1");
    if (!$checkUserlevel) {
      return False;
    }
    return intval($checkUserlevel);
  }
  public function getTagRole($tag_id) {
    if (!isset($this->tags[intval($tag_id)])) {
      return False;
    }
    return intval($this->tags[intval($tag_id)]->staff[$this->id]['role']);
  }
  public function isTagStaff($tag_id) {
    $tag_role = $this->getTagRole($tag_id);
    if (!$tag_role || $tag_role < 1) {
      return False;
    }
    return True;
  }
  public function isTagModerator($tag_id) {
    $tag_role = $this->getTagRole($tag_id);
    if (!$tag_role || $tag_role < 2) {
      return false;
    }
    return true;
  }
  public function isTagAdmin($tag_id) {
    $tag_role = $this->getTagRole($tag_id);
    if (!$tag_role || $tag_role < 3) {
      return false;
    }
    return true;
  }
  public function isAdmin() {
    if (!isset($this->role) || !$this->role) {
      return False;
    }
    return intval($this->role) > 0;
  }
  public function getTagActivity($tags=False, $metrics=False, $start=False, $end=False, $numPartitions=False) {
    /* 
      Given a set of tag objects, retrieve the tag activity timelines for the given interval.
      Defaults to all metrics for all tags belonging to the user for the entirey of ETI's existence.
      Returns an array with keys for each activity metric provided by getActivityTimeline, each assigned to a list of [date,tag1,tag2,...] lists.
    */
    if (count($this->tags) > 0) {
      $start = ($start === False) ? 1084263862 : $start;
      $end = ($end === False) ? time() : $end;
      $numPartitions = ($numPartitions === False) ? 10 : $numPartitions;
      if ($tags === False) {
        $tags = $this->tags;
      }
      $tagTimelines = array();
      $timelineBins = array();

      $partitionSize = floor(($end - $start) / $numPartitions);
      // change date string based on partition size.
      if ($partitionSize >= 31536000) {
        $dateFormat = 'Y';
      } elseif ($partitionSize >= 2592000) {
        $dateFormat = 'M Y';
      } elseif ($partitionSize >= 86400) {
        $dateFormat = 'n/j';
      } elseif ($partitionSize >= 3600) {
        $dateFormat = 'n/j G:00';
      } else {
        $dateFormat = 'n/j G:i';
      }

      for ($partition_i = 1; $partition_i <= $numPartitions; $partition_i++) {
        $timelineBins[$partition_i] = 1;
      }

      foreach ($tags as $tag) {
        $tagTimelines[$tag->name] = array();
        foreach ($tag->getActivityTimeline($start, $end, $numPartitions) as $timelineEntry) {
          // set the metrics if they haven't already been set.
          if ($metrics === False) {
            $metrics = array_slice(array_keys($timelineEntry), 1, NULL, True);
          }
          // put this in the bin to which it belongs.
          $bin = intval(($timelineEntry['dateStamp'] - $start) / $partitionSize);
          if ($bin >= 0) {
            if (!isset($timelineBins[$bin])) {
              $timelineBins[$bin] = 1;
            }
            if (!isset($tagTimelines[$tag->name][$bin])) {
              $tagTimelines[$tag->name][$bin] = $timelineEntry;
            } else {
              foreach ($metrics as $metric) {
                $tagTimelines[$tag->name][$bin][$metric] += $timelineEntry[$metric];
              }
            }
          }
        }
      }

      ksort($timelineBins);
      $activityTimelines = array();
      foreach ($metrics as $metric) {
        $activityTimelines[$metric] = array();
      }
      $outputTimeZone = new DateTimeZone(OUTPUT_TIMEZONE);
      foreach ($timelineBins as $bin => $foo) {
        $date = $start + $bin * $partitionSize;
        $dateObject = new DateTime('@'.$date);
        $dateObject->setTimeZone($outputTimeZone);
        $dateStamp = $dateObject->format($dateFormat);
        $activityTimelineRow = array();
        foreach ($metrics as $metric) {
          $activityTimelineRow[$metric] = array($dateStamp);
        }
        foreach ($tags as $tag) {
          if (!isset($tagTimelines[$tag->name][$bin])) {
            foreach ($metrics as $metric) {
              $activityTimelineRow[$metric][] = 0;
            }
          } else {
            foreach ($metrics as $metric) {
              $activityTimelineRow[$metric][] = $tagTimelines[$tag->name][$bin][$metric];
            }
          }
        }
        foreach ($metrics as $metric) {
          $activityTimelines[$metric][$date] = $activityTimelineRow[$metric];
        }
      }
    } else {
      foreach ($metrics as $metric) {
        $activityTimelines[$metric] = array();
      }
    }
    return $activityTimelines;
  }
}

?>