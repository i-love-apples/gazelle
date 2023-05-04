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

$title = trim($_POST['title'] ?? '');
if ($title == '') {
    $Err = 'You forgot to enter the title!';
}

$tags = trim($_POST['tags'] ?? '');
if ($tags == '') {
    $Err = 'You forgot to enter any tags!';
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

if (empty($_POST['artists'])) {
    $Err = 'You did not enter any artists.';
} else {
    $Artists = $_POST['artists'];
    $role = $_POST['importance'];
}

$year = (int)$_POST['year'];
if (!$year) {
    // $Err = 'The given year is not a number.';
    $year = date("Y");
}

// optional
$EditionInfo     = trim($_POST['editioninfo'] ?? '');
$catalogueNumber = trim($_POST['cataloguenumber'] ?? '');
$recordLabel     = trim($_POST['recordlabel'] ?? '');
$OCLC            = trim($_POST['oclc'] ?? '');

$AllEncodings = false;
$AllFormats   = false;
$AllMedia     = false;
$NeedLog      = false;
$NeedCue      = false;
$NeedChecksum = false;
$MinLogScore  = 0;

if (!$onlyMetadata) {
    $releaseType = (int)$_POST['releasetype'];
    if (!(new Gazelle\ReleaseType)->findNameById($releaseType)) {
        // $Err = 'Please pick a release type';
        $releaseType = 0;
    }

    $EncodingArray = $_POST['bitrates'] ?? [];
    if (isset($_POST['all_bitrates']) || count($EncodingArray) === count(ENCODING)) {
        $AllEncodings = true;
    } else {
        if (empty($EncodingArray)) {
            $Err = 'You must require at least one bitrate';
        }
    }

    $MediaArray = $_POST['media'] ?? [];
    if (isset($_POST['all_media']) || count($MediaArray) === count(MEDIA)) {
        $AllMedia = true;
    } else {
        if (empty($MediaArray)) {
            $Err = 'You must require at least one medium.';
        }
    }

    $FormatArray = $_POST['formats'] ?? [];
    if (isset($_POST['all_formats']) || count($FormatArray) === count(FORMAT)) {
        $AllFormats = true;
    } else {
        if (empty($FormatArray)) {
            $Err = 'You must require at least one format';
        } elseif (in_array(array_search('FLAC', FORMAT), $FormatArray)) {
            $NeedChecksum = isset($_POST['needcksum']);
            $NeedCue      = isset($_POST['needcue']);
            $NeedLog      = isset($_POST['needlog']);
            if ($NeedLog) {
                if ($_POST['minlogscore']) {
                    $MinLogScore = intval(trim($_POST['minlogscore']));
                } else {
                    $MinLogScore = 0;
                }
                if ($MinLogScore < 0 || $MinLogScore > 100) {
                    $Err = 'You have entered a minimum log score that is not between 0 and 100 inclusive.';
                }
            }
            //FLAC was picked, require either Lossless or 24 bit Lossless
            if (!$AllEncodings && empty(array_intersect($EncodingArray, [array_search('Lossless', ENCODING), array_search('24bit Lossless', ENCODING)]))) {
                $Err = 'You selected FLAC as a format but no possible bitrate to fill it (Lossless or 24bit Lossless)';
            }

            if ($NeedCue || $NeedLog || $NeedChecksum) {
                if (empty($_POST['all_media']) && !(in_array('0', $MediaArray))) {
                    $Err = 'Only CD is allowed as media for FLAC + log/cue requests.';
                }
            }
        }
    }
}

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

//For refilling on error
if ($categoryName === 'Music') {
    $MainArtistCount = 0;
    $ArtistNames = [];
    $ArtistForm = [
        ARTIST_MAIN      => [],
        ARTIST_GUEST     => [],
        ARTIST_REMIXER   => [],
        ARTIST_COMPOSER  => [],
        ARTIST_CONDUCTOR => [],
        ARTIST_DJ        => [],
        ARTIST_PRODUCER  => [],
        ARTIST_ARRANGER  => [],
    ];
    for ($i = 0, $il = count($Artists); $i < $il; $i++) {
        if (trim($Artists[$i]) !== '') {
            if (!in_array($Artists[$i], $ArtistNames)) {
                $ArtistForm[$role[$i]][] = ['name' => trim($Artists[$i])];
                if (in_array($role[$i], [ARTIST_ARRANGER, ARTIST_COMPOSER, ARTIST_CONDUCTOR, ARTIST_DJ, ARTIST_MAIN])) {
                    $MainArtistCount++;
                }
                $ArtistNames[] = trim($Artists[$i]);
            }
        }
    }
    if ($MainArtistCount < 1) {
        $Err = 'Please enter at least one main artist, conductor, arranger, composer, or DJ.';
    }
    if (!isset($ArtistNames[0])) {
        unset($ArtistForm);
    }
}

if (!empty($Err)) {
    $Div = $_POST['unit'] === 'mb' ? 1024 * 1024 : 1024 * 1024 * 1024;
    $Bounty /= $Div;
    $returnEdit = true;
    require_once('new_edit.php');
    exit;
}

if (!$onlyMetadata) {
    if ($AllEncodings) {
        $EncodingList = 'Any';
    } else {
        foreach ($EncodingArray as $Index => $MasterIndex) {
            if (array_key_exists($Index, ENCODING)) {
                $EncodingArray[$Index] = ENCODING[$MasterIndex];
            } else {
                error(0);
            }
        }
        $EncodingList = implode('|', $EncodingArray);
    }

    if ($AllFormats) {
        $FormatList = 'Any';
    } else {
        foreach ($FormatArray as $Index => $MasterIndex) {
            if (array_key_exists($Index, FORMAT)) {
                $FormatArray[$Index] = FORMAT[$MasterIndex];
            } else {
                error(0);
            }
        }
        $FormatList = implode('|', $FormatArray);
    }

    if ($AllMedia) {
        $MediaList = 'Any';
    } else {
        foreach ($MediaArray as $Index => $MasterIndex) {
            if (array_key_exists($Index, MEDIA)) {
                $MediaArray[$Index] = MEDIA[$MasterIndex];
            } else {
                error(0);
            }
        }
        $MediaList = implode('|', $MediaArray);
    }
}

if (!$NeedLog) {
    $LogCue = '';
} else {
    $LogCue = 'Log';
    if ($MinLogScore > 0) {
        if ($MinLogScore >= 100) {
            $LogCue .= ' (100%)';
        } else {
            $LogCue .= ' (>= '.$MinLogScore.'%)';
        }
    }
}
if ($NeedCue) {
    if ($LogCue !== '') {
        $LogCue .= ' + Cue';
    } else {
        $LogCue = 'Cue';
    }
}

if ($newRequest) {
    $DB->prepared_query('
        INSERT INTO requests (
            TimeAdded, LastVote, Visible, UserID, CategoryID, Title, Year, Image, Description, RecordLabel,
            CatalogueNumber, ReleaseType, BitrateList, FormatList, MediaList, LogCue, Checksum, GroupID, OCLC)
        VALUES (
            now(), now(), 1, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        $Viewer->id(), $categoryId, $title, $year, $image, $description, $recordLabel,
        $catalogueNumber, $releaseType, $EncodingList, $FormatList, $MediaList, $LogCue, $NeedChecksum ? 1 : 0, $GroupID ?? null, $OCLC
    );
    $request = new Gazelle\Request($DB->inserted_id());
    $RequestID = $request->id();
} else {
    if ($onlyMetadata) {
        $DB->prepared_query("
            UPDATE requests SET
                CategoryID = ?, Title = ?, Year = ?, Image = ?, Description = ?, CatalogueNumber = ?, RecordLabel = ?, GroupID = ?, OCLC = ?
            WHERE ID = ?
            ", $categoryId, $title, $year, $image, $description, $catalogueNumber, $recordLabel, $GroupID ?? null, $OCLC,
            $RequestID
        );
    } else {
        $DB->prepared_query('
            UPDATE requests SET
                CategoryID = ?, Title = ?, Year = ?, Image = ?, Description = ?, CatalogueNumber = ?, RecordLabel = ?,
                ReleaseType = ?, BitrateList = ?, FormatList = ?, MediaList = ?, LogCue = ?, Checksum = ?, GroupID = ?, OCLC = ?
            WHERE ID = ?',
            $categoryId, $title, $year, $image, $description, $catalogueNumber, $recordLabel,
            $releaseType, $EncodingList, $FormatList, $MediaList, $LogCue, $NeedChecksum ? 1 : 0, $GroupID ?? null, $OCLC,
            $RequestID
        );
    }

    // We need to be able to delete artists / tags
    $DB->prepared_query("
        SELECT concat('artists_requests_', ArtistID) FROM requests_artists WHERE RequestID = ?
        ", $RequestID
    );
    $Cache->delete_multi([
        "request_artists_$RequestID",
        ...$DB->collect(0, false)
    ]);
    $DB->prepared_query("
        DELETE FROM requests_artists WHERE RequestID = ?
        ", $RequestID
    );
}

if (isset($GroupID)) {
    $Cache->delete_value("requests_group_$GroupID");
}

/*
 * Multiple Artists!
 * For the multiple artists system, we have 3 steps:
 * 1. See if each artist given already exists and if it does, grab the ID.
 * 2. For each artist that didn't exist, create an artist.
 * 3. Create a row in the requests_artists table for each artist, based on the ID.
 */

$artistMan = new Gazelle\Manager\Artist;
foreach ($ArtistForm as $role => $Artists) {
    foreach ($Artists as $Num => $Artist) {
        //1. See if each artist given already exists and if it does, grab the ID.
        $DB->prepared_query('
            SELECT
                ArtistID,
                AliasID,
                Name,
                Redirect
            FROM artists_alias
            WHERE Name = ?', $Artist['name']);

        while ([$ArtistID, $AliasID, $AliasName, $Redirect] = $DB->next_record(MYSQLI_NUM, false)) {
            if (!strcasecmp($Artist['name'], $AliasName)) {
                if ($Redirect) {
                    $AliasID = $Redirect;
                }
                $ArtistForm[$role][$Num] = ['id' => $ArtistID, 'aliasid' => $AliasID, 'name' => $AliasName];
                break;
            }
        }
        if (!$ArtistID) {
            //2. For each artist that didn't exist, create an artist.
            [$ArtistID, $AliasID] = $artistMan->create($Artist['name']);
            $ArtistForm[$role][$Num] = ['id' => $ArtistID, 'aliasid' => $AliasID, 'name' => $Artist['name']];
        }
    }
}

//3. Create a row in the requests_artists table for each artist, based on the ID.
$artistMan->setGroupID($RequestID);
foreach ($ArtistForm as $role => $Artists) {
    foreach ($Artists as $Artist) {
        $artistMan->addToRequest($Artist['id'], $Artist['aliasid'], $role);
        $Cache->increment('stats_album_count');
        $Cache->delete_value('artists_requests_'.$Artist['id']);
    }
}

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
