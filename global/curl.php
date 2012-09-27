<?php
function hitPage($page,$cookieString="",$referer=ROOT_URL) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_COOKIE, $cookieString);
	curl_setopt($ch, CURLOPT_USERAGENT, "TagETI");
	curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
	curl_setopt($ch, CURLOPT_URL, $page);
	curl_setopt($ch, CURLOPT_REFERER, $referer);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
  $ret = curl_exec($ch);
	if (curl_error($ch)) {
		curl_close($ch);
		return False;
	} else {
		curl_close($ch);
		return $ret;		
	}
}

function hitPageSSL($page, $cookieString="", $referer=ROOT_URL) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
	curl_setopt($ch, CURLOPT_COOKIE, $cookieString);
	curl_setopt($ch, CURLOPT_USERAGENT, "TagETI");
	curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
	curl_setopt($ch, CURLOPT_URL, $page);
	curl_setopt($ch, CURLOPT_REFERER, $referer);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
  $ret = curl_exec($ch);
	if (curl_error($ch)) {
		//display_curl_error($ch);
		curl_close($ch);
		return False;
	} else {
		curl_close($ch);
		return $ret;		
	}
}

function get_enclosed_string($haystack, $needle1, $needle2="", $offset=0) {
	if ($needle1 == "") {
		$needle1_pos = 0;
	} else {
		$needle1_pos = strpos($haystack, $needle1, $offset) + strlen($needle1);
		if ($needle1_pos === FALSE || ($needle1_pos != 0 && !$needle1_pos) || $needle1_pos > strlen($haystack)) {
			return false;
		}
	}
	if ($needle2 == "") {
		$needle2_pos = strlen($haystack);
	} else {
		$needle2_pos = strpos($haystack, $needle2, $needle1_pos);
		if ($needle2_pos === FALSE || !$needle2_pos) {
			return false;
		}
	}
	if ($needle1_pos > $needle2_pos || $needle1_pos < 0 || $needle2_pos < 0 || $needle1_pos > strlen($haystack) || $needle2_pos > strlen($haystack)) {
		return false;
	}
	
    $enclosed_string = substr($haystack, $needle1_pos, $needle2_pos - $needle1_pos);
    return $enclosed_string;
}

function get_last_enclosed_string($haystack, $needle1, $needle2="") {
	//this is the last, smallest possible enclosed string.
	//position of first needle is as close to the end of the haystack as possible
	//position of second needle is as close to the first needle as possible
	if ($needle2 == "") {
		$needle2_pos = strlen($haystack);
	} else {
		$needle2_pos = strrpos($haystack, $needle2);
		if ($needle2_pos === FALSE) {
			return false;
		}
	}
	if ($needle1 == "") {
		$needle1_pos = 0;
	} else {
		$needle1_pos = strrpos(substr($haystack, 0, $needle2_pos), $needle1) + strlen($needle1);
		if ($needle1_pos === FALSE) {
			return false;
		}
	}
	if ($needle2 != "") {
		$needle2_pos = strpos($haystack, $needle2, $needle1_pos);
		if ($needle2_pos === FALSE) {
			return false;
		}
	}
    $enclosed_string = substr($haystack, $needle1_pos, $needle2_pos - $needle1_pos);
    return $enclosed_string;
}

function get_biggest_enclosed_string($haystack, $needle1, $needle2="") {
	//this is the largest possible enclosed string.
	//position of last needle is as close to the end of the haystack as possible.
	
	if ($needle1 == "") {
		$needle1_pos = 0;
	} else {
		$needle1_pos = strpos($haystack, $needle1) + strlen($needle1);
		if ($needle1_pos === FALSE) {
			return false;
		}
	}
	if ($needle2 == "") {
		$needle2_pos = strlen($haystack);
	} else {
		$needle2_pos = strrpos($haystack, $needle2, $needle1_pos);
		if ($needle2_pos === FALSE) {
			return false;
		}
	}
    $enclosed_string = substr($haystack, $needle1_pos, $needle2_pos - $needle1_pos);
    return $enclosed_string;
}

function getETILoginCookie() {
	// Grabs the cookie header string from a given file.
	$cookieString = file_get_contents(COOKIE_STRING_FILE);
	return $cookieString;
}

function getTagPublicInfo($cookieString, $name, $num=0) {
	$etiTagPage = hitPageSSL("https://boards.endoftheinter.net/async-tag-query.php?e&q=".urlencode($name), $cookieString);
	if (!$etiTagPage) {
		return False;
	}
	$jsonPage = json_decode(substr($etiTagPage, 1, strlen($etiTagPage)-1));
	if (!$jsonPage || count($jsonPage) < 1) {
		return False;
	}

	$tag = parseTagPublicInfo($etiTagPage, $num);
	if (!$tag) {
		return False;
	}
	return $tag;
}

function parseTagPublicInfo($etiTagPage, $num=0) {
	// fetches a tag's info from the public ajax interface on ETI.
	$parsedTags = json_decode(substr($etiTagPage, 1, strlen($etiTagPage)-1));
	if (!$parsedTags || count($parsedTags) < 1) {
		return False;
	}
	$tag = array();
	$tag['name'] = $parsedTags[$num][0];
	$moderators = get_enclosed_string($parsedTags[$num][1][0], "<b>Moderators: </b>", "<br /><b>Administrators:");
	$tag['moderators'] = array();
	if ($moderators) {
		$moderatorTags = explode(", ", $moderators);
		foreach ($moderatorTags as $moderator) {
			$tag['moderators'][] = array('username' => get_enclosed_string($moderator, '">', "</a>"), 
											'id' => intval(get_enclosed_string($moderator, "?user=", '">')));
		}
		$description_end_tag = "<br /><b>Moderators:";
	} else {
		$description_end_tag = "<br /><b>Administrators:";
	}
	$administrators = get_enclosed_string($parsedTags[$num][1][0], "<br /><b>Administrators: </b>");
	$tag['administrators'] = array();
	if ($administrators) {
		$adminTags = explode(", ", $administrators);
		foreach ($adminTags as $admin) {
			$tag['administrators'][] = array('username' => get_enclosed_string($admin, '">', "</a>"), 
											'id' => intval(get_enclosed_string($admin, "?user=", '">')));
		}
	}
	$tag['staff'] = array();
	foreach ($tag['administrators'] as $admin) {
		$tag['staff'][] = array('id' => intval($admin['id']), 'role' => 3);
	}
	foreach ($tag['moderators'] as $moderator) {
		$tag['staff'][] = array('id' => intval($moderator['id']), 'role' => 2);
	}
	$tag['description'] = get_last_enclosed_string($parsedTags[$num][1][0], ":</b> ", "<br /><b>Moderators:");
	$relatedTags = $parsedTags[$num][1][1]->{2};
	$tags['related_tags'] = ($relatedTags) ? array_keys(get_object_vars($relatedTags)) : array();
	$forbiddenTags = $parsedTags[$num][1][1]->{0};
	$tag['forbidden_tags'] = ($forbiddenTags) ? array_keys(get_object_vars($forbiddenTags)) : array();
	$mandatoryTags = $parsedTags[$num][1][1]->{1};
	$tag['dependency_tags'] = ($mandatoryTags) ? array_keys(get_object_vars($mandatoryTags)) : array();

	return $tag;
}

function refreshAllTags($database, $user) {
	// updates all possible tags.
	$cookieString = getETILoginCookie();
	if (!$cookieString) {
		return array('location' => "tag.php", 'status' => "The server could not log into ETI. Please try again later.", 'class' => 'error');
	}
	$etiTagsListing = hitPageSSL("https://endoftheinter.net/main.php", $cookieString);
	if (!$etiTagsListing) {
		return array('location' => "tag.php", 'status' => "The server could not grab the tags listing from ETI. Please try again later.", 'class' => 'error');
	}
	$tagList = explode("&nbsp;&bull; ", get_enclosed_string($etiTagsListing, '<div style="font-size: 14px">', '				</div>'));
	$tagCount = count($tagList);
	$tagUpdateInterval = intval($tagCount/100);
	$tagNum = 0;
	foreach ($tagList as $tagHTML) {
		$tagName = get_enclosed_string($tagHTML, '">', '</a>');

		// grab tag public info.
		$tag = getTagPublicInfo($cookieString, $tagName, 0);
		if (!$tag) {
			continue;
		}
		// grab tag private info if appropriate.

		try {
			$dbTag = new Tag($database, False, $tag['name']);
		} catch (Exception $e) {
			if ($e->getMessage() == "ID Not Found") {
				// no such tag exists. create a new tag.
				$dbTag = new Tag($database, 0, $tag['name']);
			}
		}
		$updateDB = $dbTag->create_or_update($tag);
		$tagNum++;
		if ($tagNum % $tagUpdateInterval == 0) {
			$updateProgress = $database->stdQuery("UPDATE `indices` SET `value` = ".round(1.0*$tagNum/$tagCount, 2)." WHERE `name` = 'tag_update' LIMIT 1");
		}
	}
	$updateProgress = $database->stdQuery("UPDATE `indices` SET `value` = 1 WHERE `name` = 'tag_update' LIMIT 1");
	return array('location' => "tag.php", 'status' => "Successfully refreshed ".$tagNum." tags.", 'class' => 'success');
}

function refreshTag($database, $user, $name) {
	// takes a tag name, finds the first tag matching the name
	// updates the database with this tag and returns a redirect_to array.
	$cookieString = getETILoginCookie();
	if (!$cookieString) {
		return array('location' => "tag.php", 'status' => "The server could not log into ETI. Please try again later.", 'class' => 'error');
	}

	$etiTagPage = hitPageSSL("https://boards.endoftheinter.net/async-tag-query.php?e&q=".urlencode($name), $cookieString);
	if (!$etiTagPage) {
		return array('location' => "tag.php", 'status' => "The server could not grab the tags listing from ETI. Please try again later.", 'class' => 'error');
	}

	$tag = parseTagPublicInfo($etiTagPage);
	if (!$tag) {
		return array('location' => "tag.php", 'status' => "An error occurred parsing the tag information from ETI. Please try again later.", 'class' => 'error');
	}

	try {
		$dbTag = new Tag($database, False, $tag['name']);
	} catch (Exception $e) {
		if ($e->getMessage() == "ID Not Found") {
			// no such tag exists. create a new tag.
			$dbTag = new Tag($database, 0, $tag['name']);
		}
	}
	$updateDB = $dbTag->create_or_update($tag);
	if (!$updateDB) {
		return array('location' => "tag.php", 'status' => "An error occurred while updating ".$dbTag->name.". Please try again.", 'class' => 'error');
	}
	return array('location' => "tag.php", 'status' => $dbTag->name." successfully refreshed.", 'class' => 'success');
}
?>