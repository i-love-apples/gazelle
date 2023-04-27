<?php

authorize();

if (empty($_POST['type'])) {
    error(0);
}
if (!in_array($_POST['action'], ['takenew', 'takeedit'])) {
    error(0);
}
$newRequest = ($_POST['action'] === 'takenew');

$categoryName = $_POST['type'];
$categoryId = array_search($categoryName, CATEGORY);
if ($categoryId === false) {
    error(0);
}
$categoryId += 1;

if ($newRequest) {
    if (!$Viewer->permitted('site_submit_requests') || $Viewer->uploadedSize() < 250 * 1024 * 1024) {
        error(403);
    }

    if (empty($_POST['amount'])) {
        $Err = 'You forgot to enter any bounty!';
    } else {
        $Bounty = (int)$_POST['amount'];
        if ($Bounty < REQUEST_MIN * 1024 * 1024) {
            $Err = 'Minimum bounty is ' . REQUEST_MIN . ' MiB.';
        }
        $Bytes = $Bounty; //From MiB to B
    }
    $onlyMetadata = false;
} else {
    $request = (new Gazelle\Manager\Request)->findById((int)($_POST['requestid'] ?? 0));
    if (is_null($request)) {
        error(404);
    }
    $onlyMetadata = $Viewer->id() != $request->userId() && $Viewer->permitted('site_edit_requests');
    $RequestID = $request->id();
}

$description = trim($_POST['description'] ?? '');
if ($description == '') {
    $Err = 'You forgot to enter a description.';
}

$tags = trim($_POST['tags'] ?? '');
if ($tags == '') {
    $Err = 'You forgot to enter any tags!';
}

$title = trim($_POST['title'] ?? '');
if ($title == '') {
    $Err = 'You forgot to enter the title!';
}

if (empty($_POST['image'])) {
    $image = null;
} else {
    $image = $_POST['image'];
    if (!preg_match(IMAGE_REGEXP, $image)) {
        $Err = display_str($image) . " does not look like a valid image url";
    }
    $banned = (new Gazelle\Util\ImageProxy($Viewer))->badHost($image);
    if ($banned) {
        $Err = "Please rehost images from $banned elsewhere.";
    }
}

// optional
$MinLogScore  = 0;

// GroupID
if (!empty($_POST['groupid'])) {
    $GroupID = preg_match(TGROUP_REGEXP, trim($_POST['groupid']), $match)
        ? (int)$match['id']
        : (int)$_POST['groupid'];
    if ($GroupID > 0) {
        $tgroup = (new Gazelle\Manager\TGroup)->findById($GroupID);
        if (is_null($tgroup)) {
            $Err = 'The torrent group, if entered, must correspond to a music torrent group on the site.';
        } else {
            $GroupID = $tgroup->id();
        }
    }
}

if (!empty($Err)) {
    $Div = $_POST['unit'] === 'mb' ? 1024 * 1024 : 1024 * 1024 * 1024;
    $Bounty /= $Div;
    require_once('new_edit.php');
    exit;
}

if ($newRequest) {
    $DB->prepared_query('
        INSERT INTO requests (
            TimeAdded, LastVote, Visible, UserID, CategoryID, Title, Image, Description, GroupID, CatalogueNumber)
        VALUES (
            now(), now(), 1, ?, ?, ?, ?, ?, ?, ?)',
        $Viewer->id(), $categoryId, $title, $image, $description,  $GroupID ?? null, ''
    );
    $request = new Gazelle\Request($DB->inserted_id());
    $RequestID = $request->id();
} else {
    if ($onlyMetadata) {
        $DB->prepared_query("
            UPDATE requests SET
                CategoryID = ?, Title = ?, Image = ?, Description = ?, GroupID = ?
            WHERE ID = ?
            ", $categoryId, $title, $image, $description, $GroupID ?? null,
            $RequestID
        );
    } else {
        $DB->prepared_query('
            UPDATE requests SET
                CategoryID = ?, Title = ?, Image = ?, Description = ?, GroupID = ?
            WHERE ID = ?',
            $categoryId, $title, $image, $description, $GroupID ?? null,
            $RequestID
        );
    }
}

if (isset($GroupID)) {
    $Cache->delete_value("requests_group_$GroupID");
}

//3. Create a row in the requests_artists table for each artist, based on the ID.
if (!$newRequest) {
    $DB->prepared_query("
        DELETE FROM requests_tags WHERE RequestID = ?
        ", $RequestID
    );
    $Cache->delete_value("request_$RequestID");
    $Cache->delete_value("request_artists_$RequestID");
}

//Tags
$tagMan = new Gazelle\Manager\Tag;
$tags = array_unique(explode(',', $tags));
foreach ($tags as $Index => $Tag) {
    $TagID = $tagMan->create($Tag, $Viewer->id());
    $tagMan->createRequestTag($TagID, $RequestID);
    $tags[$Index] = $tagMan->name($TagID); // For announce, may have been aliased
}

if ($newRequest) {
    //Remove the bounty and create the vote
    $DB->prepared_query("
        INSERT INTO requests_votes
               (RequestID, UserID, Bounty)
        VALUES (?,         ?,      ?)
        ", $RequestID, $Viewer->id(), $Bytes * (1 - REQUEST_TAX)
    );

    $DB->prepared_query('
        UPDATE users_leech_stats
        SET Uploaded = (Uploaded - ?)
        WHERE UserID = ?',
        $Bytes, $Viewer->id());
    $Cache->delete_value('user_stats_'.$Viewer->id());

    if ($Viewer->option('AutoSubscribe')) {
        (new Gazelle\User\Subscription($Viewer))->subscribeComments('requests', $RequestID);
    }

    Gazelle\Util\Irc::sendMessage(
        '#requests',
        $request->text() . " - " . SITE_URL . "/" . $request->location() . " - " . implode(' ', $tags)
    );
}

$request->updateSphinx();

header("Location: " . $request->location());
