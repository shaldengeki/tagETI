<?php

class Tag {
  public $id;
  public $name;
  public $description;
  public $dependencyTags;
  public $forbiddenTags;
  public $relatedTags;
  public $staff;
  public $dbConn;

  public function __construct($dbConn, $id=False, $name=False) {
    $this->dbConn = $dbConn;
    if ($id == 0) {
      // creating a new tag. initialize blank values.
      $this->id = 0;
      $this->name = $name;
      $this->description = "";
      $this->dependencyTags = array();
      $this->forbiddenTags = array();
      $this->relatedTags = array();
      $this->staff = array();
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
      $this->dependencyTags = $this->getDependencyTags();
      $this->forbiddenTags = $this->getForbiddenTags();
      $this->relatedTags = $this->getRelatedTags();
      $this->staff = $this->getStaff();
    }
  }
  public function create_or_update_tag_dependency($tag_id) {
    /*
      Creates or updates an existing tag dependency for the current tag.
      Takes the dependency tag ID.
      Returns a boolean.
    */
    //check to see if this is an update.
    $updateDependency = False;
    foreach ($this->dependencyTags as $tag) {
      if (intval($tag['id']) == intval($tag_id)) {
        $updateDependency = True;
        break;
      }
    }
    if ($updateDependency) {
      return True;
    } else {
      $dependency = $this->dbConn->queryFirstRow("SELECT `id`, `name` FROM `seinma_llusers`.`tags` WHERE `id` = ".intval($tag_id)." LIMIT 1");
      if (!$dependency) {
        return False;
      }
      $insertDependency = $this->dbConn->stdQuery("INSERT INTO `seinma_llusers`.`tags_dependent` (`parent_tag_id`, `child_tag_id`) VALUES (".intval($tag_id).", ".intval($this->id).")");
      if (!$insertDependency) {
        return False;
      }
      $this->dependencyTags[] = array('id' => intval($dependency['id']), 'name' => $dependency['name']);
      return True;
    }
  }
  public function drop_tag_dependencies($tags=False) {
    /*
      Deletes (this tag is dependent on) relations.
      Takes an array of tag ids as input, defaulting to all dependency tags.
      Returns a boolean.
    */
    if ($tags === False) {
      $tags = array();
      foreach ($this->dependencyTags as $tag) {
        $tags[] = $tag['id'];
      }
    }
    $tagIDs = array();
    foreach ($tags as $tag) {
      if (is_numeric($tag)) {
        $tagIDs[] = intval($tag);
      }
    }
    $drop_tag_dependencies = $this->dbConn->stdQuery("DELETE FROM `seinma_llusers`.`tags_dependent` WHERE `child_tag_id` = ".intval($this->id)." AND `parent_tag_id` IN (".implode(",", $tagIDs).") LIMIT ".count($tagIDs));
    if (!$drop_tag_dependencies) {
      return False;
    }
    return True;
  }
  public function create_or_update_tag_forbidden($tag_id) {
    /*
      Creates or updates an existing tag forbidden for the current tag.
      Takes the forbbiden tag ID.
      Returns a boolean.
    */
    //check to see if this is an update.
    $updateForbidden = False;
    foreach ($this->forbiddenTags as $tag) {
      if (intval($tag['id']) == intval($tag_id)) {
        $updateForbidden = True;
        break;
      }
    }
    if ($updateForbidden) {
      return True;
    } else {
      $forbidden = $this->dbConn->queryFirstRow("SELECT `id`, `name` FROM `seinma_llusers`.`tags` WHERE `id` = ".intval($tag_id)." LIMIT 1");
      if (!$forbidden) {
        return False;
      }
      $insertForbidden = $this->dbConn->stdQuery("INSERT INTO `seinma_llusers`.`tags_forbidden` (`tag_id`, `forbidden_tag_id`) VALUES (".intval($this->id).", ".intval($tag_id).")");
      if (!$insertForbidden) {
        return False;
      }
      $this->forbiddenTags[] = array('id' => intval($forbidden['id']), 'name' => $forbidden['name']);
      return True;
    }
  }
  public function drop_tag_forbiddens($tags=False) {
    /*
      Deletes tag forbiddens for this tag.
      Takes an array of tag ids as input, defaulting to all forbidden tags.
      Returns a boolean.
    */
    if ($tags === False) {
      $tags = array();
      foreach ($this->forbiddenTags as $tag) {
        $tags[] = $tag['id'];
      }
    }
    $tagIDs = array();
    foreach ($tags as $tag) {
      if (is_numeric($tag)) {
        $tagIDs[] = intval($tag);
      }
    }
    $drop_tag_forbiddens = $this->dbConn->stdQuery("DELETE FROM `seinma_llusers`.`tags_forbidden` WHERE `tag_id` = ".intval($this->id)." AND `forbidden_tag_id` IN (".implode(",", $tagIDs).") LIMIT ".count($tagIDs));
    if (!$drop_tag_forbiddens) {
      return False;
    }
    return True;
  }
  public function create_or_update_tag_relation($tag_id) {
    /*
      Creates or updates an existing tag relation for the current tag.
      Takes the related tag ID.
      Returns a boolean.
    */
    //check to see if this is an update.
    $updateRelation = False;
    foreach ($this->relatedTags as $tag) {
      if (intval($tag['id']) == intval($tag_id)) {
        $updateRelation = True;
        break;
      }
    }
    if ($updateRelation) {
      return True;
    } else {
      $relation = $this->dbConn->queryFirstRow("SELECT `id`, `name` FROM `seinma_llusers`.`tags` WHERE `id` = ".intval($tag_id)." LIMIT 1");
      if (!$relation) {
        return False;
      }
      $insertRelation = $this->dbConn->stdQuery("INSERT INTO `seinma_llusers`.`tags_related` (`parent_tag_id`, `child_tag_id`) VALUES (".intval($this->id).", ".intval($tag_id).")");
      if (!$insertRelation) {
        return False;
      }
      $this->relatedTags[] = array('id' => intval($relation['id']), 'name' => $relation['name']);
      return True;
    }
  }
  public function drop_tag_relations($tags=False) {
    /*
      Deletes tag relations for this tag.
      Takes an array of tag ids as input, defaulting to all related tags.
      Returns a boolean.
    */
    if ($tags === False) {
      $tags = array();
      foreach ($this->relatedTags as $tag) {
        $tags[] = $tag['id'];
      }
    }
    $tagIDs = array();
    foreach ($tags as $tag) {
      if (is_numeric($tag)) {
        $tagIDs[] = intval($tag);
      }
    }
    $drop_tag_relations = $this->dbConn->stdQuery("DELETE FROM `seinma_llusers`.`tags_related` WHERE `parent_tag_id` = ".intval($this->id)." AND `child_tag_id` IN (".implode(",", $tagIDs).") LIMIT ".count($tagIDs));
    if (!$drop_tag_relations) {
      return False;
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
    $updateDependency = False;
    foreach ($this->staff as $staff) {
      if (intval($staff['id']) == intval($user_id)) {
        $updateDependency = True;
        break;
      }
    }
    if ($updateDependency) {
      // update tag role.
      $updateRole = $this->dbConn->stdQuery("UPDATE `seinma_llusers`.`tags_users` SET `role` = ".intval($role)." WHERE `tag_id` = ".intval($this->id)." AND `user_id` = ".intval($user_id)." LIMIT 1");
      if (!$updateRole) {
        return False;
      }
      foreach (array_keys($this->staff) as $key) {
        if ($this->staff[$key]['id'] == $user_id) {
          $this->staff[$key]['role'] = $role;
        }
      }
    } else {
      // insert tag role.
      $targetUser = $this->dbConn->queryFirstRow("SELECT `userid`, `username` FROM `seinma_llusers` WHERE `userid` = ".intval($user_id)." LIMIT 1");
      if (!$targetUser) {
        return False;
      }

      $insertRole = $this->dbConn->stdQuery("INSERT INTO `seinma_llusers`.`tags_users` (`tag_id`, `user_id`, `role`) VALUES (".intval($this->id).", ".intval($user_id).", ".intval($role).")");
      if (!$insertRole) {
        return False;
      }
      $this->staff[] = array('id' => intval($targetUser['id']), 'username' => $targetUser['username'], 'role' => intval($role));

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
      $roles = array();
      foreach ($this->staff as $staff) {
        $roles[] = $staff['id'];
      }
    }
    $staffIDs = array();
    foreach ($roles as $staff) {
      if (is_numeric($staff)) {
        $staffIDs[] = intval($staff);
      }
    }
    $drop_tag_roles = $this->dbConn->stdQuery("DELETE FROM `seinma_llusers`.`tags_users` WHERE `tag_id` = ".intval($this->id)." AND `user_id` IN (".implode(",", $staffIDs).") LIMIT ".count($staffIDs));
    if (!$drop_tag_roles) {
      return False;
    }
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
      $update_params = array();
      foreach ($tag as $parameter => $value) {
        if (!is_array($value)) {
          $update_params[] = "`".$this->dbConn->real_escape_string($parameter)."` = ".$this->dbConn->quoteSmart($value);
        }
      }
      $update_tag = $this->dbConn->stdQuery("UPDATE `seinma_llusers`.`tags` SET ".implode(", ", $update_params)."  WHERE `id` = ".intval($this->id)." LIMIT 1");
      if (!$update_tag) {
        return False;
      }
    } else {
      // add this tag.
      $add_tag = $this->dbConn->stdQuery("INSERT INTO `seinma_llusers`.`tags` (`name`, `description`) VALUES (".$this->dbConn->quoteSmart($tag['name']).", ".$this->dbConn->quoteSmart($tag['description']).")");
      if (!$add_tag) {
        return False;
      }
      $this->id = $add_tag->insert_id;
    }

    // now process tag dependencies / forbiddens / relations / roles.
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
        if (!(isset($submittedUserIDs[intval($staff['id'])]) || array_key_exists(intval($staff['id'], $submittedUserIDs))) {
          $rolesToDrop[] = $staff['id'];
        }
      }
      $drop_roles = $this->drop_tag_roles($rolesToDrop);
      foreach ($tag['staff'] as $user_array) {
        $user_id = $this->dbConn->queryFirstValue("SELECT `userid` FROM `seinma_llusers`.`ll_users` WHERE `userid` = ".intval($user_array['id'])." LIMIT 1");
        if ($user_id) {
          $create_tag_role = $this->dbConn->create_or_update_tag_role($user, $this->id, $user_id, intval($user_array['role']));
        }
      }
    }
    return True;
  }
  public function getInfo() {
    /*
      Retrieves the name and description of the current tag.
      Returns an associative array containing both.
    */
    return $this->dbConn->queryFirstRow("SELECT `name`, `description` FROM `seinma_llusers`.`tags` WHERE `id` = ".intval($this->id)." LIMIT 1");
  }
  public function getDependencyTags() {
    /*
      Retrieves all tags that the current tag is dependent on.
      Returns a list of arrays containing id and name.
    */
    $dependencyTags = array();
    $dependencyTagsQuery = $this->dbConn->stdQuery("SELECT `tags`.`id`, `tags`.`name` FROM `seinma_llusers`.`tags_dependent` LEFT OUTER JOIN `seinma_llusers`.`tags` ON `tags`.`id` = `tags_dependent`.`parent_tag_id` WHERE `tags_dependent`.`child_tag_id` = ".intval($this->id));
    while ($tag = $dependencyTagsQuery->fetch_assoc()) {
      $dependencyTags[] = $tag;
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
      $forbiddenTags[] = $tag;
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
      $relatedTags[] = $tag;
    }
    return $relatedTags;
  }
  public function getStaff() {
    /*
      Retrieves all users that the current tag has on staff.
      Returns a list of arrays containing id, username, and role.
    */
    $staff = array();
    $staffQuery = $this->dbConn->stdQuery("SELECT `tags_users`.`user_id` AS `id`, `ll_users`.`username`, `tags_users`.`role` FROM `seinma_llusers`.`tags_users` LEFT OUTER JOIN `seinma_llusers`.`ll_users` ON `ll_users`.`userid` = `tags_users`.`user_id` WHERE `tags_users`.`tag_id` = ".intval($this->id));
    while ($user = $staffQuery->fetch_assoc()) {
      $staff[] = $user;
    }
    return $staff;
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
}

?>