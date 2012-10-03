<?php

class Tag {
  public $id;
  public $name;
  public $description;
  public $access;
  public $accessUsers;
  public $participation;
  public $permanent;
  public $inceptive;
  public $dependencyTags;
  public $forbiddenTags;
  public $relatedTags;
  public $staff;
  public $dbConn;

  public function __construct($dbConn, $id=False, $name=False) {
    $this->dbConn = $dbConn;
    if ($id === 0) {
      // creating a new tag. initialize blank values.
      $this->id = $this->inceptive = $this->permanent = 0;
      $this->access = $this->participation = 1;
      $this->name = $name;
      $this->description = "";
      $this->accessUsers = $this->dependencyTags = $this->forbiddenTags = $this->relatedTags = $this->staff = array();
    } else {
      if (!$id || !is_numeric($id)) {
        if (!($name === False)) {
          $this->id = intval($this->dbConn->queryFirstValue("SELECT `id` FROM `seinma_llusers`.`tags` WHERE UPPER(`name`) = ".$this->dbConn->quoteSmart(strtoupper($name))." LIMIT 1"));
        } else {
          throw new Exception('Invalid Arguments');
        }
      } else {
        $this->id = intval($this->dbConn->queryFirstValue("SELECT `id` FROM `seinma_llusers`.`tags` WHERE `id` = ".intval($id)." LIMIT 1"));
      }
      if (!$this->id) {
        throw new Exception('ID Not Found');
      }
      $info = $this->getInfo();
      $this->name = $info['name'];
      $this->description = $info['description'];
      $this->access = intval($info['access']);
      $this->participation = intval($info['participation']);
      $this->inceptive = intval($info['inceptive']);
      $this->permanent = intval($info['permanent']);
      $this->accessUsers = $this->getAccessRules();
      $this->dependencyTags = $this->getDependencyTags();
      $this->forbiddenTags = $this->getForbiddenTags();
      $this->relatedTags = $this->getRelatedTags();
      $this->staff = $this->getStaff();
    }
  }
  public function create_or_update_tag_access($user_id) {
    /*
      Creates or updates an existing tag access rule for the current tag.
      Takes the user ID to create a rule for.
      Returns a boolean.
    */
    // check to see if this is an update.
    if (isset($this->accessUsers[intval($user_id)])) {
      return True;
    }
    $user = $this->dbConn->queryFirstRow("SELECT `userid`, `username` FROM `seinma_llusers`.`ll_users` WHERE `userid` = ".intval($user_id)." LIMIT 1");
    if (!$user) {
      return False;
    }
    $insertAccess = $this->dbConn->stdQuery("INSERT INTO `seinma_llusers`.`tags_access` (`user_id`, `tag_id`) VALUES (".intval($user_id).", ".intval($this->id).")");
    if (!$insertAccess) {
      return False;
    }
    $this->accessUsers[intval($user['userid'])] = array('id' => intval($user['userid']), 'username' => $user['username']);
    return True;
  }
  public function drop_tag_access($users=False) {
    /*
      Deletes access rules for a tag.
      Takes an array of user ids as input, defaulting to all users.
      Returns a boolean.
    */
    if ($users === False) {
      $users = array_keys($this->accessUsers);
    }
    $userIDs = array();
    foreach ($users as $user) {
      if (is_numeric($user)) {
        $userIDs[] = intval($user);
      }
    }
    if (count($userIDs) > 0) {
      $drop_access_rules = $this->dbConn->stdQuery("DELETE FROM `seinma_llusers`.`tags_access` WHERE `tag_id` = ".intval($this->id)." AND `user_id` IN (".implode(",", $userIDs).") LIMIT ".count($userIDs));
      if (!$drop_access_rules) {
        return False;
      }
    }
    foreach ($userIDs as $userID) {
      unset($this->accessUsers[intval($userID)]);
    }
    return True;
  }
  public function create_or_update_tag_dependency($tag_id) {
    /*
      Creates or updates an existing tag dependency for the current tag.
      Takes the dependency tag ID.
      Returns a boolean.
    */
    // cannot make a tag dependent on itself.
    if ($this->id == $tag_id) {
      return False;
    }
    // check to see if this is an update.
    if (isset($this->dependencyTags[intval($tag_id)])) {
      return True;
    }
    $dependency = $this->dbConn->queryFirstRow("SELECT `id`, `name` FROM `seinma_llusers`.`tags` WHERE `id` = ".intval($tag_id)." LIMIT 1");
    if (!$dependency) {
      return False;
    }
    $insertDependency = $this->dbConn->stdQuery("INSERT INTO `seinma_llusers`.`tags_dependent` (`parent_tag_id`, `child_tag_id`) VALUES (".intval($tag_id).", ".intval($this->id).")");
    if (!$insertDependency) {
      return False;
    }
    $this->dependencyTags[intval($dependency['id'])] = $dependency;
    return True;
  }
  public function drop_tag_dependencies($tags=False) {
    /*
      Deletes (this tag is dependent on) relations.
      Takes an array of tag ids as input, defaulting to all dependency tags.
      Returns a boolean.
    */
    if ($tags === False) {
      $tags = array_keys($this->dependencyTags);
    }
    $tagIDs = array();
    foreach ($tags as $tag) {
      if (is_numeric($tag)) {
        $tagIDs[] = intval($tag);
      }
    }
    if (count($tagIDs) > 0) {
      $drop_tag_dependencies = $this->dbConn->stdQuery("DELETE FROM `seinma_llusers`.`tags_dependent` WHERE `child_tag_id` = ".intval($this->id)." AND `parent_tag_id` IN (".implode(",", $tagIDs).") LIMIT ".count($tagIDs));
      if (!$drop_tag_dependencies) {
        return False;
      }
    }
    foreach ($tagIDs as $tagID) {
      unset($this->dependencyTags[intval($tagID)]);
    }
    return True;
  }
  public function create_or_update_tag_forbidden($tag_id) {
    /*
      Creates or updates an existing tag forbidden for the current tag.
      Takes the forbbiden tag ID.
      Returns a boolean.
    */
    // cannot make a tag forbidden from itself.
    if ($this->id == $tag_id) {
      return False;
    }
    // check to see if this is an update.
    if (isset($this->forbiddenTags[intval($tag_id)])) {
      return True;
    }
    $forbidden = $this->dbConn->queryFirstRow("SELECT `id`, `name` FROM `seinma_llusers`.`tags` WHERE `id` = ".intval($tag_id)." LIMIT 1");
    if (!$forbidden) {
      return False;
    }
    $insertForbidden = $this->dbConn->stdQuery("INSERT INTO `seinma_llusers`.`tags_forbidden` (`tag_id`, `forbidden_tag_id`) VALUES (".intval($this->id).", ".intval($tag_id).")");
    if (!$insertForbidden) {
      return False;
    }
    $this->forbiddenTags[intval($forbidden['id'])] = $forbidden;
    return True;
  }
  public function drop_tag_forbiddens($tags=False) {
    /*
      Deletes tag forbiddens for this tag.
      Takes an array of tag ids as input, defaulting to all forbidden tags.
      Returns a boolean.
    */
    if ($tags === False) {
      $tags = array_keys($this->forbiddenTags);
    }
    $tagIDs = array();
    foreach ($tags as $tag) {
      if (is_numeric($tag)) {
        $tagIDs[] = intval($tag);
      }
    }
    if (count($tagIDs) > 0) {
      $drop_tag_forbiddens = $this->dbConn->stdQuery("DELETE FROM `seinma_llusers`.`tags_forbidden` WHERE `tag_id` = ".intval($this->id)." AND `forbidden_tag_id` IN (".implode(",", $tagIDs).") LIMIT ".count($tagIDs));
      if (!$drop_tag_forbiddens) {
        return False;
      }
    }
    foreach ($tagIDs as $tagID) {
      unset($this->forbiddenTags[intval($tagID)]);
    }
    return True;
  }
  public function create_or_update_tag_relation($tag_id) {
    /*
      Creates or updates an existing tag relation for the current tag.
      Takes the related tag ID.
      Returns a boolean.
    */
    // cannot make a tag related to itself.
    if ($this->id == $tag_id) {
      return False;
    }
    // check to see if this is an update.
    if (isset($this->relatedTags[intval($tag_id)])) {
      return True;
    }

    $relation = $this->dbConn->queryFirstRow("SELECT `id`, `name` FROM `seinma_llusers`.`tags` WHERE `id` = ".intval($tag_id)." LIMIT 1");
    if (!$relation) {
      return False;
    }
    $insertRelation = $this->dbConn->stdQuery("INSERT INTO `seinma_llusers`.`tags_related` (`parent_tag_id`, `child_tag_id`) VALUES (".intval($this->id).", ".intval($tag_id).")");
    if (!$insertRelation) {
      return False;
    }
    $this->relatedTags[intval($relation['id'])] = $relation;
    return True;
  }
  public function drop_tag_relations($tags=False) {
    /*
      Deletes tag relations for this tag.
      Takes an array of tag ids as input, defaulting to all related tags.
      Returns a boolean.
    */
    if ($tags === False) {
      $tags = array_keys($this->relatedTags);
    }
    $tagIDs = array();
    foreach ($tags as $tag) {
      if (is_numeric($tag)) {
        $tagIDs[] = intval($tag);
      }
    }
    if (count($tagIDs) > 0) {
      $drop_tag_relations = $this->dbConn->stdQuery("DELETE FROM `seinma_llusers`.`tags_related` WHERE `parent_tag_id` = ".intval($this->id)." AND `child_tag_id` IN (".implode(",", $tagIDs).") LIMIT ".count($tagIDs));
      if (!$drop_tag_relations) {
        return False;
      }
    }
    foreach ($tagIDs as $tagID) {
      unset($this->relatedTags[intval($tagID)]);
    }
    return True;
  }
  public function create_or_update_tag_role($user_id, $role) {
    /*
      Creates or updates an existing tag role for the current tag.
      Takes the user ID and the desired role.
      Returns a boolean.
    */
    //check to see if this is an update.
    if (isset($this->staff[intval($user_id)])) {
      // update tag role.
      $updateRole = $this->dbConn->stdQuery("UPDATE `seinma_llusers`.`tags_users` SET `role` = ".intval($role)." WHERE `tag_id` = ".intval($this->id)." AND `user_id` = ".intval($user_id)." LIMIT 1");
      if (!$updateRole) {
        return False;
      }
      foreach (array_keys($this->staff) as $userID) {
        if (intval($userID) == intval($user_id)) {
          $this->staff[$userID]['role'] = $role;
        }
      }
    } else {
      // insert tag role.
      $targetUser = $this->dbConn->queryFirstRow("SELECT `userid` AS `id`, `username` FROM `seinma_llusers`.`ll_users` WHERE `userid` = ".intval($user_id)." LIMIT 1");
      if (!$targetUser) {
        return False;
      }

      $insertRole = $this->dbConn->stdQuery("INSERT INTO `seinma_llusers`.`tags_users` (`tag_id`, `user_id`, `role`) VALUES (".intval($this->id).", ".intval($user_id).", ".intval($role).")");
      if (!$insertRole) {
        return False;
      }
      $this->staff[intval($targetUser['id'])] = array('id' => intval($targetUser['id']), 'username' => $targetUser['username'], 'role' => intval($role));

      // if necessary, update managed tags.
      if ($user_id == TAGETI_MANAGEMENT_USERID && $role > 0) {
        $updateTag = $this->dbConn->stdQuery("UPDATE `tags` SET `managed` = 1 WHERE `id` = ".intval($this->id)." LIMIT 1");
      }
    }
    return True;
  }
  public function drop_tag_roles($roles=False) {
    /*
      Deletes tag roles for this tag.
      Takes an array of user ids as input, defaulting to all tag roles.
      Returns a boolean.
    */
    if ($roles === False) {
      $roles = array_keys($this->staff);
    }
    $staffIDs = array();
    foreach ($roles as $staff) {
      if (is_numeric($staff)) {
        $staffIDs[] = intval($staff);
      }
    }
    if (count($staffIDs) > 0) {
      $drop_tag_roles = $this->dbConn->stdQuery("DELETE FROM `seinma_llusers`.`tags_users` WHERE `tag_id` = ".intval($this->id)." AND `user_id` IN (".implode(",", $staffIDs).") LIMIT ".count($staffIDs));
      if (!$drop_tag_roles) {
        return False;
      }
    }
    foreach ($staffIDs as $staffID) {
      unset($this->staff[intval($staffID)]);
    }
    return True;
  }
  public function update_eti($tag=False) {
    /*
      Updates a tag on ETI with the information in $tag.
      Returns a boolean.
    */
    if ($tag === False) {
      $tag = array();
    }

    // first, grab the latest information from the db and set defaults to that.
    $etiTag = new Tag($this->dbConn, $this->id);

    foreach ($tag as $key=>$value) {
      switch($key) {
        case 'description':
          $etiTag->description = htmlspecialchars($value, ENT_QUOTES, "UTF-8");
          break;
        case 'access':
          $etiTag->access = intval($value);
          break;
        case 'accessUsers':
          $etiTag->accessUsers = $value;
          break;
        case 'participation':
          $etiTag->participation = intval($value);
          break;
        case 'permanent':
          $etiTag->permanent = intval($value);
          break;
        case 'inceptive':
          $etiTag->inceptive = intval($value);
          break;
        case 'dependencyTags':
          $etiTag->dependencyTags = $value;
          break;
        case 'forbiddenTags':
          $etiTag->forbiddenTags = $value;
          break;
        case 'relatedTags':
          $etiTag->relatedTags = $value;
          break;
        case 'staff':
          $etiTag->staff = $value;
          break;
        default:
          break;
      }
    }

    // now go through each of the fields for this tag, creating a post query string.
    $postFields = "description=".urlencode($etiTag->description);
    $postFields .= "&access=".$etiTag->access;
    $accessUsers = array();
    foreach ($etiTag->accessUsers as $id => $foo) {
      $accessUsers[] = $id;
    }
    $postFields .= "&access_users=".implode(",", $accessUsers);
    $postFields .= "&participation=".$etiTag->participation;
    $postFields .= ($etiTag->permanent ? "&permanent=on" : "");
    $postFields .= ($etiTag->inceptive ? "&inceptive=on" : "");
    $dependencyTags = array();
    foreach ($etiTag->dependencyTags as $dependencyTag) {
      $dependencyTags[] = $dependencyTag['name'];
    }
    $postFields .= "&dependent=".implode(",", $dependencyTags);
    $forbiddenTags = array();
    foreach ($etiTag->forbiddenTags as $forbiddenTag) {
      $forbiddenTags[] = $forbiddenTag['name'];
    }
    $postFields .= "&exclusive=".implode(",", $forbiddenTags);
    $relatedTags = array();
    foreach ($etiTag->relatedTags as $relatedTag) {
      $relatedTags[] = $relatedTag['name'];
    }
    $postFields .= "&related=".implode(",", $relatedTags);
    $moderators = [];
    $admins = [];
    foreach ($etiTag->staff as $staff) {
      if (intval($staff['role']) == 2) {
        $moderators[] = intval($staff['id']);
      } else {
        $admins[] = intval($staff['id']);
      }
    }
    $postFields .= "&moderators=".implode(",", $moderators)."&admins=".implode(",", $admins)."&submit=Save";

    // now post this to ETI.
    $cookieString = getETILoginCookie();
    $updateETI = hitFormSSL("https://endoftheinter.net/tag.php?tag=".urlencode($this->name), $postFields, $cookieString);
    return True;
  }
  public function create_or_update($tag) {
    /*
      Creates or updates an extant tag.
      Takes an array of tag parameters like name, description, related_tags
      Returns a boolean.
    */

    // check to see if this tag exists in the db or not.
    if ($this->id != 0) {
      // update this tag.

      // first, submit this to ETI and see if it works.
      $updateETI = $this->update_eti($tag);
      if (!$updateETI) {
        return False;
      }

      // now go ahead with the update.
      // set any loose parameters on the tag.
      $update_params = array();
      foreach ($tag as $parameter => $value) {
        if (!is_array($value)) {
          $update_params[] = "`".$this->dbConn->real_escape_string($parameter)."` = ".$this->dbConn->quoteSmart($value);
        }
      }
      if (count($update_params) > 0) {
        $update_tag = $this->dbConn->stdQuery("UPDATE `seinma_llusers`.`tags` SET ".implode(", ", $update_params)."  WHERE `id` = ".intval($this->id)." LIMIT 1");
        if (!$update_tag) {
          return False;
        }
      }
    } else {
      // add this tag.
      $insert_params = array();
      $insert_values = array();
      foreach ($tag as $parameter => $value) {
        if (!is_array($value)) {
          $insert_params[] = "`".$this->dbConn->real_escape_string($parameter)."`";
          $insert_values[] = $this->dbConn->quoteSmart($value);
        }
      }
      $add_tag = $this->dbConn->stdQuery("INSERT INTO `seinma_llusers`.`tags` (".implode(",", $insert_params).") VALUES (".implode(",", $insert_values).")");
      if (!$add_tag) {
        return False;
      }
      $this->id = $add_tag->insert_id;
    }

    // now process tag access rules / dependencies / forbiddens / relations / roles.
    if (isset($tag['access_users'])) {
      // drop any unneeded access rules.
      $usersToDrop = array();
      foreach ($this->accessUsers as $accessUser) {
        if (!in_array($accessUser['username'], $tag['access_users'])) {
          $usersToDrop[] = $accessUser['id'];
        }
      }
      $drop_users = $this->drop_tag_access($usersToDrop);
      foreach ($tag['access_users'] as $user) {
        // find this userID.
        $userID = intval($this->dbConn->queryFirstValue("SELECT `userid` FROM `seinma_llusers`.`ll_users` WHERE `userid` = ".intval($user)." LIMIT 1"));
        if ($userID) {
          $create_access_rule = $this->create_or_update_tag_access($userID);
        }
      }
    }
    if (isset($tag['dependency_tags'])) {
      // drop any unneeded subtag dependencies.
      $tagsToDrop = array();
      foreach ($this->dependencyTags as $dependencyTag) {
        if (!in_array($dependencyTag['name'], $tag['dependency_tags'])) {
          $tagsToDrop[] = $dependencyTag['id'];
        }
      }
      $drop_dependencies = $this->drop_tag_dependencies($tagsToDrop);
      foreach ($tag['dependency_tags'] as $subtag) {
        // create or update this subtag.
        try {
          $newTag = new Tag($this->dbConn, False, $subtag);
        } catch (Exception $e) {
          if ($e->getMessage() == "ID Not Found") {
            // no such tag exists. create a new tag.
            $newTag = new Tag($this->dbConn, 0, $subtag);
          }
        }
        $create_subtag = $newTag->create_or_update(array('name' => $subtag));
        if ($create_subtag) {
          $create_subtag_dependency = $this->create_or_update_tag_dependency($newTag->id);
        }
      }
    }
    if (isset($tag['forbidden_tags'])) {
      // drop any unneeded forbiddens.
      $tagsToDrop = array();
      foreach ($this->forbiddenTags as $forbiddenTag) {
        if (!in_array($forbiddenTag['name'], $tag['forbidden_tags'])) {
          $tagsToDrop[] = $forbiddenTag['id'];
        }
      }
      $drop_dependencies = $this->drop_tag_forbiddens($tagsToDrop);
      foreach ($tag['forbidden_tags'] as $subtag) {
        // create or update this subtag.
        try {
          $newTag = new Tag($this->dbConn, False, $subtag);
        } catch (Exception $e) {
          if ($e->getMessage() == "ID Not Found") {
            // no such tag exists. create a new tag.
            $newTag = new Tag($this->dbConn, 0, $subtag);
          }
        }
        $create_subtag = $newTag->create_or_update(array('name' => $subtag));
        if ($create_subtag) {
          $create_subtag_forbidden = $this->create_or_update_tag_forbidden($newTag->id);
        }
      }
    }
    if (isset($tag['related_tags'])) {
      // drop any unneeded relations.
      $tagsToDrop = array();
      foreach ($this->relatedTags as $relatedTag) {
        if (!in_array($relatedTag['name'], $tag['related_tags'])) {
          $tagsToDrop[] = $relatedTag['id'];
        }
      }
      $drop_dependencies = $this->drop_tag_relations($tagsToDrop);
      foreach ($tag['related_tags'] as $subtag) {
        // create or update this subtag.
        try {
          $newTag = new Tag($this->dbConn, False, $subtag);
        } catch (Exception $e) {
          if ($e->getMessage() == "ID Not Found") {
            // no such tag exists. create a new tag.
            $newTag = new Tag($this->dbConn, 0, $subtag);
          }
        }
        $create_subtag = $newTag->create_or_update(array('name' => $subtag));
        if ($create_subtag) {
          $create_subtag_related = $this->create_or_update_tag_relation($newTag->id);
        }
      }
    }
    if (isset($tag['staff'])) {
      // drop any unneeded roles.
      $rolesToDrop = array();
      $submittedUserIDs = array();
      foreach ($tag['staff'] as $staff) {
        $submittedUserIDs[intval($staff['id'])] = 1;
      }
      foreach ($this->staff as $staff) {
        if (!(isset($submittedUserIDs[intval($staff['id'])]) || array_key_exists(intval($staff['id'], $submittedUserIDs)))) {
          $rolesToDrop[] = $staff['id'];
        }
      }
      $drop_roles = $this->drop_tag_roles($rolesToDrop);
      foreach ($tag['staff'] as $user_array) {
        $user_id = $this->dbConn->queryFirstValue("SELECT `userid` FROM `seinma_llusers`.`ll_users` WHERE `userid` = ".intval($user_array['id'])." LIMIT 1");
        if ($user_id) {
          $create_tag_role = $this->create_or_update_tag_role($user_id, intval($user_array['role']));
        }
      }
    }
    return True;
  }
  public function getInfo() {
    /*
      Retrieves the inline parameters of the current tag.
      Returns an associative array.
    */
    return $this->dbConn->queryFirstRow("SELECT `name`, `description`, `access`, `participation`, `permanent`, `inceptive` FROM `seinma_llusers`.`tags` WHERE `id` = ".intval($this->id)." LIMIT 1");
  }
  public function getDependencyTags() {
    /*
      Retrieves all tags that the current tag is dependent on.
      Returns a list of arrays containing id and name.
    */
    $dependencyTags = array();
    $dependencyTagsQuery = $this->dbConn->stdQuery("SELECT `tags`.`id`, `tags`.`name` FROM `seinma_llusers`.`tags_dependent` LEFT OUTER JOIN `seinma_llusers`.`tags` ON `tags`.`id` = `tags_dependent`.`parent_tag_id` WHERE `tags_dependent`.`child_tag_id` = ".intval($this->id));
    while ($tag = $dependencyTagsQuery->fetch_assoc()) {
      $dependencyTags[intval($tag['id'])] = $tag;
    }
    return $dependencyTags;
  }
  public function getForbiddenTags() {
    /*
      Retrieves all tags that the current tag forbids.
      Returns a list of arrays containing id and name.
    */
    $forbiddenTags = array();
    $forbiddenTagsQuery = $this->dbConn->stdQuery("SELECT `tags`.`id`, `tags`.`name` FROM `seinma_llusers`.`tags_forbidden` LEFT OUTER JOIN `seinma_llusers`.`tags` ON `tags`.`id` = `tags_forbidden`.`forbidden_tag_id` WHERE `tags_forbidden`.`tag_id` = ".intval($this->id));
    while ($tag = $forbiddenTagsQuery->fetch_assoc()) {
      $forbiddenTags[intval($tag['id'])] = $tag;
    }
    return $forbiddenTags;
  }
  public function getRelatedTags() {
    /*
      Retrieves all tags that the current tag is related to.
      Returns a list of arrays containing id and name.
    */
    $relatedTags = array();
    $relatedTagsQuery = $this->dbConn->stdQuery("SELECT `tags`.`id`, `tags`.`name` FROM `seinma_llusers`.`tags_related` LEFT OUTER JOIN `seinma_llusers`.`tags` ON `tags`.`id` = `tags_related`.`child_tag_id` WHERE `tags_related`.`parent_tag_id` = ".intval($this->id));
    while ($tag = $relatedTagsQuery->fetch_assoc()) {
      $relatedTags[intval($tag['id'])] = $tag;
    }
    return $relatedTags;
  }
  public function getStaff() {
    /*
      Retrieves all users that the current tag has on staff.
      Returns a list of arrays containing id, username, and role.
    */
    $staff = array();
    $staffQuery = $this->dbConn->stdQuery("SELECT `seinma_llusers`.`tags_users`.`user_id` AS `id`, `seinma_llusers`.`ll_users`.`username`, `seinma_llusers`.`tags_users`.`role` FROM `seinma_llusers`.`tags_users` LEFT OUTER JOIN `seinma_llusers`.`ll_users` ON `ll_users`.`userid` = `tags_users`.`user_id` WHERE `tags_users`.`tag_id` = ".intval($this->id));
    while ($user = $staffQuery->fetch_assoc()) {
      $staff[intval($user['id'])] = $user;
    }
    return $staff;
  }
  public function getAccessRules() {
    /*
      Retrieves all access rules for the current tag.
      Returns a list of arrays containing id and username.
    */
    $rules = array();
    $rulesQuery = $this->dbConn->stdQuery("SELECT `user_id` AS `id`, `ll_users`.`username` FROM `seinma_llusers`.`tags_access` LEFT OUTER JOIN `seinma_llusers`.`ll_users` ON `ll_users`.`userid` = `tags_access`.`user_id` WHERE `tag_id` = ".intval($this->id)." ORDER BY `user_id` ASC");
    while ($rule = $rulesQuery->fetch_assoc()) {
      $rules[intval($rule['id'])] = array('id' => intval($rule['id']), 'username' => $rule['username']);
    }
    return $rules;
  }
  public function getLatestTopics($limit=20) {
    /*
      Retrieves the latest $limit topics tagged with the current tag.
      Returns a list of arrays containing topic_id, user_id, username, title, postCount, and lastPostTime.
    */
    if ($limit && is_numeric($limit)) {
      $limitString = " LIMIT ".intval($limit);
    } else {
      $limitString = "";
    }
    $topicsQuery = $this->dbConn->stdQuery("SELECT `ll_topicid` AS `topic_id`, `ll_users`.`userid` AS `user_id`, `username`, `title`, `postCount`, `lastPostTime` FROM `seinma_llusers`.`tags_topics` LEFT OUTER JOIN `seinma_llusers`.`topics` ON `topics`.`ll_topicid` = `tags_topics`.`topic_id` LEFT OUTER JOIN `seinma_llusers`.`ll_users` ON `ll_users`.`userid` = `topics`.`userid` WHERE `tags_topics`.`tag_id` = ".intval($this->id)." ORDER BY `lastPostTime` DESC".$limitString);
    $topics = array();
    while ($topic = $topicsQuery->fetch_assoc()) {
      $topics[] = $topic;
    }
    return $topics;
  }
  public function getActivityTimeline($start=False, $end=False, $numPartitions=False) {
    /*
      Retrieves the number of topics for the given tag from $start to $end in unix timestamps, divided into $numPartitions.
      Returns an array of date,topicCount,postCount,userCount values.
    */
    // Set default values.
    if ($start === False || !is_numeric($start)) {
      $start = 0;
    }
    if ($end === False || !is_numeric($end)) {
      $end = time();
    }
    if ($numPartitions === False || !is_numeric($numPartitions)) {
      $numPartitions = 10;
    }

    // First calculate the length of each partition.
    $realStart = $this->dbConn->queryFirstValue("SELECT MIN(`posts`.`date`) FROM `seinma_llusers`.`tags_topics` LEFT OUTER JOIN `seinma_llusers`.`posts` ON `posts`.`ll_topicid` = `tags_topics`.`topic_id` WHERE `tag_id` = ".intval($this->id)." && `posts`.`date` >= ".intval($start));
    $realEnd = $this->dbConn->queryFirstValue("SELECT MAX(`posts`.`date`) FROM `seinma_llusers`.`tags_topics` LEFT OUTER JOIN `seinma_llusers`.`posts` ON `posts`.`ll_topicid` = `tags_topics`.`topic_id` WHERE `tag_id` = ".intval($this->id)." && `posts`.`date` < ".intval($end));

    $partitionLength = round(($realEnd - $realStart) / $numPartitions);

    // Now fetch the postcounts for this interval.
    $postCountQuery = $this->dbConn->queryAssoc("SELECT FLOOR(`posts`.`date`/".intval($partitionLength).")*".intval($partitionLength)." AS `dateStamp`, COUNT(*) AS `postCount`, COUNT(DISTINCT `ll_topicid`) AS `topicCount`, COUNT(DISTINCT `userid`) AS `userCount` FROM `seinma_llusers`.`tags_topics` LEFT OUTER JOIN `seinma_llusers`.`posts` ON `posts`.`ll_topicid` = `tags_topics`.`topic_id` WHERE `tag_id` = ".intval($this->id)." && `posts`.`date` != 0 && (`posts`.`date` >= ".intval($realStart)." && `posts`.`date` < ".intval($realEnd).") GROUP BY `dateStamp` ORDER BY `dateStamp` ASC");
    return $postCountQuery;
  }
}

?>