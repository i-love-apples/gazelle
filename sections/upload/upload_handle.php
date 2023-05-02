<?php

use Gazelle\Util\Irc;
use OrpheusNET\Logchecker\Logchecker;

ini_set('max_file_uploads', 100);
ini_set('upload_max_filesize', 1_000_000);

define('MAX_FILENAME_LENGTH', 255);
if (!defined('AJAX')) {
    authorize();
}

//******************************************************************************//
//--------------- Set $Properties array ----------------------------------------//
// This is used if the form doesn't validate, and when the time comes to enter  //
// it into the database.                                                        //

$Err = null;
$Properties = [];
$categoryId = (int)$_POST['type'];
$categoryName = CATEGORY[$categoryId-1];
$Properties['CategoryID'] = $categoryId;
$Properties['CategoryName'] = $categoryName;
$Properties['Title'] = isset($_POST['title']) ? trim($_POST['title']) : null;
// Remastered is an Enum in the DB
if (isset($_POST['tags'])) {
    $Properties['Tags'] = $_POST['tags'];
    $Properties['TagList'] = array_unique(array_map('trim', explode(',', $_POST['tags']))); // Musicbranes loves to send duplicates
}
$Properties['Image'] = trim($_POST['image'] ?? '');
$Properties['GroupDescription'] = trim($_POST['album_desc'] ?? '');
$Properties['TorrentDescription'] = trim($_POST['desc'] ?? '');
$Properties['GroupID'] = $_POST['groupid'] ?? null;
$Properties['Version'] = $_POST['version'] ?? "";
$Properties['Platform'] = $_POST['platform'] ?? "";
$Properties['Includes'] = $_POST['includes'] ?? "";
$Properties['OSVersion'] = $_POST['osversion'] ?? "";
$Properties['Processor'] = $_POST['processor'] ?? "";
$Properties['RAM'] = $_POST['ram'] ?? "";
$Properties['VRAM'] = $_POST['vram'] ?? "";

if (!empty($_POST['requestid'])) {
    $RequestID = $_POST['requestid'];
    $Properties['RequestID'] = $RequestID;
}
//******************************************************************************//
//--------------- Validate data in upload form ---------------------------------//


$isApplicationsUpload = ($categoryName === 'Applications' || $categoryName === 'Games' || $categoryName === 'IOS Applications' || $categoryName === 'IOS Games');

// common to all types
$Validate = new Gazelle\Util\Validator;
if (is_null($categoryName)) {
    $Err = 'Please select a valid category.';
}
$Validate->setFields([
    ['release_desc', '0','string','The release description you entered is too long.', ['maxlength'=>1_000_000]],
    ['rules', '1','require','Your torrent must abide by the rules.'],
]);

if (!$Properties['GroupID']) {
    $Validate->setFields([
        ['image', '0','link','The image URL you entered was invalid.', ['range' => [255, 12]]],
        ['tags', '1','string','You must enter at least one tag. Maximum length is 200 characters.', ['range' => [2, 200]]],
        ['title', '1','string','Title must be less than 200 characters.', ['maxlength' => 200]],
    ]);
}

$Validate->setField('album_desc', '1','string','The description has a minimum length of 10 characters.', ['range' => [10, 1_000_000]]);

if ($isApplicationsUpload) {
    $Validate->setField('version', '1','string','The application must have a version. Maximum length is 45 characters.', ['range' => [1, 45]]);
    $Validate->setField('platform', '1','string','The application must have a mac platform.', ['range' => [2, 100]]);
    $Validate->setField('includes', '1','string','The application must have an include of.', ['range' => [1, 100]]);
    $Validate->setField('desc', '1','string','The description has a minimum length of 10 characters.', ['range' => [10, 1_000_000]]);
    $Validate->setField('osversion', '1','string','Maximum OS Version length is 100 characters.', ['range' => [0, 100]]);
    $Validate->setField('processor', '1','string','Maximum CPU length is 100 characters.', ['range' => [0, 100]]);
    $Validate->setField('ram', '1','string','Maximum RAM length is 100 characters.', ['range' => [0, 100]]);
    $Validate->setField('vram', '1','string','Maximum VRAM length is 100 characters.', ['range' => [0, 100]]);
}

$feedType = ['torrents_all'];
switch ($categoryName) {
    case 'Applications':
        $feedType[] = 'torrents_apps';
        break;
    case 'Games':
        $feedType[] = 'torrents_games';
        break;
    case 'IOS Applications':
        $feedType[] = 'torrents_iosapps';
        break;
    case 'IOS Games':
        $feedType[] = 'torrents_iosgames';
        break;
    case 'Graphics':
        $feedType[] = 'torrents_graphics';
        break;
    case 'Audio':
        $feedType[] = 'torrents_audio';
        break;
    case 'Tutorials':
        $feedType[] = 'torrents_tutorials';
        break;
    case 'Other':
        $feedType[] = 'torrents_other';
        break;
}

$Err = $Validate->validate($_POST) ? false : $Validate->errorMessage();

$File = $_FILES['file_input']; // This is our torrent file
$TorrentName = $File['tmp_name'];
$LogName = '';

if (!is_uploaded_file($TorrentName) || !filesize($TorrentName)) {
    $Err = 'No torrent file uploaded, or file is empty.';
} elseif (substr(strtolower($File['name']), strlen($File['name']) - strlen('.torrent')) !== '.torrent') {
    $Err = "You seem to have put something other than a torrent file into the upload field. (".$File['name'].").";
}

if ($Properties['Image']) {
    // Strip out Amazon's padding
    if (preg_match('/(http:\/\/ecx.images-amazon.com\/images\/.+)(\._.*_\.jpg)/i', $Properties['Image'], $match)) {
        $Properties['Image'] = $match[1].'.jpg';
    }
    if (!preg_match(IMAGE_REGEXP, $Properties['Image'])) {
        $Err = display_str($Properties['Image']) . " does not look like a valid image url";
    }
    $banned = (new Gazelle\Util\ImageProxy($Viewer))->badHost($Properties['Image']);
    if ($banned) {
        $Err = "Please rehost images from $banned elsewhere.";
    }
}

if ($Err) { // Show the upload form, with the data the user entered
    if (defined('AJAX')) {
        json_error($Err);
    } else {
        require(__DIR__ . '/upload.php');
        die();
    }
}

//******************************************************************************//
//--------------- Generate torrent file ----------------------------------------//

$torMan = new Gazelle\Manager\Torrent;
$tgMan = new Gazelle\Manager\TGroup;
$torrentFiler = new Gazelle\File\Torrent;
$bencoder = new OrpheusNET\BencodeTorrent\BencodeTorrent;
$bencoder->decodeFile($TorrentName);
$PublicTorrent = $bencoder->makePrivate(); // The torrent is now private.
$UnsourcedTorrent = $torMan->setSourceFlag($bencoder);
$InfoHash = $bencoder->getHexInfoHash();
$TorData = $bencoder->getData();

$torrent = $torMan->findByInfohash(bin2hex($InfoHash));
if ($torrent) {
    $torrentId = $torrent->id();
    if ($torrentFiler->exists($torrentId)) {
        $Err = defined('AJAX')
           ? "The exact same torrent file already exists on the site! (torrentid=$torrentId)"
           : "<a href=\"torrents.php?torrentid=$torrentId\">The exact same torrent file already exists on the site!</a>";
    } else {
        // A lost torrent
        $torrentFiler->put($bencoder->getEncode(), $torrentId);
        $Err = defined('AJAX')
            ? "Thank you for fixing this torrent (torrentid=$torrentId)"
            : "<a href=\"torrents.php?torrentid=$torrentId\">Thank you for fixing this torrent</a>";
    }
}

if (isset($TorData['encrypted_files'])) {
    $Err = 'This torrent contains an encrypted file list which is not supported here.';
}

if (isset($TorData['info']['meta version'])) {
    $Err = 'This torrent is not a V1 torrent. V2 and Hybrid torrents are not supported here.';
}

$checker = new Gazelle\Util\FileChecker;

// File list and size
['total_size' => $TotalSize, 'files' => $FileList] = $bencoder->getFileList();
$HasLog = '0';
$HasCue = '0';
$TmpFileList = [];
$TooLongPaths = [];
$DirName = (isset($TorData['info']['files']) ? make_utf8($bencoder->getName()) : '');
$folderCheck = [$DirName];
$IgnoredLogFileNames = ['audiochecker.log', 'sox.log'];

if (!$Err) {
    $Err = $checker->checkName($DirName); // check the folder name against the blacklist
}
foreach ($FileList as $FileInfo) {
    ['path' => $Name, 'size' => $Size] = $FileInfo;
    // Check file name and extension against blacklist/whitelist
    if (!$Err) {
        $Err = $checker->checkFile($categoryName, $Name);
    }
    // Make sure the filename is not too long
    if (mb_strlen($Name, 'UTF-8') + mb_strlen($DirName, 'UTF-8') + 1 > MAX_FILENAME_LENGTH) {
        $TooLongPaths[] = "<li>$DirName/$Name</li>";
    }
    // Add file info to array
    $TmpFileList[] = $torMan->metaFilename($Name, $Size);
}
if (count($TooLongPaths) > 0) {
    $Err = defined('AJAX')
        ? ['The torrent contained one or more files with too long a name', ['list' => $TooLongPaths]]
        : ('The torrent contained one or more files with too long a name: <ul>' . implode('', $TooLongPaths) . '</ul><br />');
}
$Debug->set_flag('upload: torrent decoded');

if ($Err) {
    if (defined('AJAX')) {
        json_error($Err);
    } else {
        // TODO: Repopulate the form correctly
        require(__DIR__ . '/upload.php');
        die();
    }
}

//******************************************************************************//
//--------------- Start database stuff -----------------------------------------//

$logfileSummary = new Gazelle\LogfileSummary;
if ($HasLog == '1' && isset($_FILES['logfiles'])) {
    foreach (array_keys($_FILES['logfiles']['error']) as $n) {
        if ($_FILES['logfiles']['error'][$n] == UPLOAD_ERR_OK) {
            $logfileSummary->add(
                new Gazelle\Logfile(
                    $_FILES['logfiles']['tmp_name'][$n],
                    $_FILES['logfiles']['name'][$n]
                )
            );
        }
    }
}
$LogInDB = count($logfileSummary->all()) ? '1' : '0';

$tgroup = null;
$NoRevision = false;
if ($isApplicationsUpload) {
    // Does it belong in a group?
    if ($Properties['GroupID']) {
        $tgroup = $tgMan->findById($Properties['GroupID']);
    }
}

//For notifications--take note now whether it's a new group
$IsNewGroup = is_null($tgroup);

//Needs to be here as it isn't set for add format until now
$LogName .= $Properties['Title'];

//----- Start inserts

$Debug->set_flag('upload: database begin transaction');
$DB->begin_transaction();

if (!$IsNewGroup) {
    $DB->prepared_query('
        UPDATE torrents_group SET
            Time = now()
        WHERE ID = ?
        ', $Properties['GroupID']
    );
    $tgroup = $tgMan->findById($Properties['GroupID']);
} else {
    $tgroup = $tgMan->create(
        categoryId:      $categoryId,
        name:            $Properties['Title'],
        description:     $Properties['GroupDescription'],
        image:           $Properties['Image'],
        showcase:        (bool)($Viewer->permitted('torrents_edit_vanityhouse') && isset($_POST['vanity_house'])),
    );
    $Viewer->stats()->increment('unique_group_total');
}
$GroupID = $tgroup->id();

// Description
if ($NoRevision) {
    $tgroup->createRevision($Properties['GroupDescription'], $Properties['Image'], 'Uploaded new torrent', $Viewer);
}

// Tags
$tagMan = new Gazelle\Manager\Tag;
$tagList = [];
if (!$Properties['GroupID']) {
    foreach ($Properties['TagList'] as $tag) {
        $tag = $tagMan->resolve($tagMan->sanitize($tag));
        if (!empty($tag)) {
            $TagID = $tagMan->create($tag, $Viewer->id());
            $tagMan->createTorrentTag($TagID, $GroupID, $Viewer->id(), 10);
        }
        $tagList[] = $tag;
    }
}

// Torrent
$isFreeTorrent = 0;
$freeTorrent = '0';
$freeLeechType = '0';
$converted_mb_size = $TotalSize/1024/1024;
$freeleech_min = (new \Gazelle\Manager\SiteOption)->findOptionalValueByName('freeleech-min');
if (is_null($freeleech_min)) {
    $freeleech_min = -1;
} else {
    $freeleech_min = (int)$freeleech_min;
}
if ($freeleech_min >= 0 && $freeleech_min <= $converted_mb_size) {
    $isFreeTorrent = 1;
    $freeTorrent = '1';
    $freeLeechType = '1';
}

$DB->prepared_query("
    INSERT INTO torrents
        (GroupID, UserID, 
        HasLog, HasLogDB, LogScore,
        LogChecksum, info_hash, FileCount, FileList, FilePath,
        Size, Description, Time, FreeTorrent, FreeLeechType,
        Version, Platform, Includes, OSVersion, Processor, RAM, VRAM)
    VALUES
        (?, ?, 
         ?, ?, ?, 
         ?, ?, ?, ?, ?,
         ?, ?, now(), ?, ?,
         ?, ?, ?, ?, ?, ?, ?)
    ", $GroupID, $Viewer->id(), 
       $HasLog, $LogInDB, $logfileSummary->overallScore(),
       $logfileSummary->checksumStatus(), $InfoHash, count($FileList), implode("\n", $TmpFileList), $DirName,
       $TotalSize, $Properties['TorrentDescription'], $freeTorrent, $freeLeechType,
       $Properties['Version'], $Properties['Platform'], $Properties['Includes'], $Properties['OSVersion'], $Properties['Processor'], $Properties['RAM'], $Properties['VRAM'],
);
$TorrentID = $DB->inserted_id();
$DB->prepared_query('
    INSERT INTO torrents_leech_stats (TorrentID)
    VALUES (?)
    ', $TorrentID
);
$torrent = $torMan->findById($TorrentID);

$bonus = new Gazelle\User\Bonus($Viewer);
$bonusTotal = $bonus->torrentValue($torrent);

// Prevent deletion of this torrent until the rest of the upload process is done
$Cache->cache_value("torrent_{$TorrentID}_lock", true, 120);

//******************************************************************************//
//--------------- Write Files To Disk ------------------------------------------//

$ripFiler = new Gazelle\File\RipLog;
$htmlFiler = new Gazelle\File\RipLogHTML;
foreach($logfileSummary->all() as $logfile) {
    $DB->prepared_query('
        INSERT INTO torrents_logs
               (TorrentID, Score, `Checksum`, FileName, Ripper, RipperVersion, `Language`, ChecksumState, LogcheckerVersion, Details)
        VALUES (?,         ?,      ?,         ?,        ?,      ?,             ?,          ?,             ?,                 ?)
        ', $TorrentID, $logfile->score(), $logfile->checksumStatus(), $logfile->filename(),
            $logfile->ripper(), $logfile->ripperVersion(), $logfile->language(), $logfile->checksumState(),
            Logchecker::getLogcheckerVersion(), $logfile->detailsAsString()
    );
    $LogID = $DB->inserted_id();
    $ripFiler->put($logfile->filepath(), [$TorrentID, $LogID]);
    $htmlFiler->put($logfile->text(), [$TorrentID, $LogID]);
}

$log = new Gazelle\Log;
$torrentFiler->put($bencoder->getEncode(), $TorrentID);
$log->torrent($GroupID, $TorrentID, $Viewer->id(), 'uploaded ('.number_format($TotalSize / (1024 * 1024), 2).' MiB)')
    ->general("Torrent $TorrentID ($LogName) (".number_format($TotalSize / (1024 * 1024), 2).' MiB) was uploaded by ' . $Viewer->username());

foreach ($extraFile as $id => $info) {
    $torrentFiler->put($info['payload'], $id);
    $log->torrent($GroupID, $id, $Viewer->id(), "uploaded ({$info['size']} MiB)")
        ->general("Torrent $ExtraTorrentID ($LogName) ({$info['size']}  MiB) was uploaded by " . $Viewer->username());
}

$DB->commit(); // We have a usable upload, any subsequent failures can be repaired ex post facto
$Debug->set_flag('upload: database committed');

//******************************************************************************//
//--------------- Finalize -----------------------------------------------------//

$tracker = new \Gazelle\Tracker;
$trackerUpdate[$TorrentID] = rawurlencode($InfoHash);
foreach ($trackerUpdate as $id => $hash) {
    $tracker->update_tracker('add_torrent', ['id' => $id, 'info_hash' => $hash, 'freetorrent' => $isFreeTorrent]);
}
$Debug->set_flag('upload: ocelot updated');

if (!$Viewer->disableBonusPoints()) {
    $bonus->addPoints($bonusTotal);
}

$tgroup->refresh();
$torMan->flushFoldernameCache($DirName);

$totalNew = 1;
$Viewer->stats()->increment('upload_total', $totalNew);

// Update the various cache keys affected by this
$Cache->increment_value('stats_torrent_count', $totalNew);
if ($Properties['Image'] != '') {
    $Cache->delete_value('user_recent_up_' . $Viewer->id());
}
$Cache->delete_multi(["torrents_details_$GroupID", "torrent_{$TorrentID}_lock"]);
if (!$IsNewGroup) {
    $Cache->delete_multi([
        "torrent_group_$GroupID",
        "detail_files_$GroupID",
        sprintf(\Gazelle\TGroup::CACHE_KEY, $GroupID),
        sprintf(\Gazelle\TGroup::CACHE_TLIST_KEY, $GroupID),
    ]);
}

//******************************************************************************//
//---------------IRC announce and feeds ---------------------------------------//

$Announce = $Properties['Title'];
if ($isApplicationsUpload) {
    $Announce .= ' ' . $Properties['Version'] . ' [' . $Properties['Platform'] . "/" . $Properties['Includes'] . ']';
}
$Announce .= ' (' . $categoryName . ') ';
$Title = $Announce;
$AnnounceFreeleech = '';
if ($isFreeTorrent == 1) {
    $AnnounceFreeleech = 'freeleech,';
}

$AnnounceSSL = "\002TORRENT:\002 \00303{$Announce}\003"
    . " - \00312" . $AnnounceFreeleech . implode(',', $tagList) . "\003"
    . " - \00304".SITE_URL."/torrents.php?id=$GroupID\003 / \00304".SITE_URL."/torrents.php?action=download&id=$TorrentID\003";

// ENT_QUOTES is needed to decode single quotes/apostrophes
Irc::sendMessage('#ANNOUNCE', html_entity_decode($AnnounceSSL, ENT_QUOTES));
$Debug->set_flag('upload: announced on irc');

//******************************************************************************//
//--------------- Post-processing ----------------------------------------------//
/* Because tracker updates and notifications can be slow, we're
 * redirecting the user to the destination page and flushing the buffers
 * to make it seem like the PHP process is working in the background.
 */

if ($Properties['Image'] != '') {
    $Viewer->flushRecentUpload();
}

if (defined('AJAX')) {
    $Response = [
        'groupId' => $GroupID,
        'torrentId' => $TorrentID,
        'private' => !$PublicTorrent,
        'source' => !$UnsourcedTorrent,
    ];

    if (isset($RequestID)) {
        define('NO_AJAX_ERROR', true);
        $FillResponse = require_once(__DIR__ . '/../requests/take_fill.php');
        if (!isset($FillResponse['requestId'])) {
            $FillResponse = [
                'status' => 400,
                'error' => $FillResponse,
            ];
        }
        $Response['fillRequest'] = $FillResponse;
    }

    // TODO: this is copy-pasted
    $Feed = new Gazelle\Feed;
    $Item = $Feed->item(
        $Title,
        Text::strip_bbcode($Properties['GroupDescription']),
        "torrents.php?action=download&id={$TorrentID}&torrent_pass=[[PASSKEY]]",
        date('r'),
        $Viewer->username(),
        'torrents.php?id=' . $GroupID,
        implode(',', $tagList)
    );

    $notification = (new Gazelle\Notification\Upload)
        ->addTags($tagList)
        ->addCategory($categoryName)
        ->addUser($Viewer)
        ->setDebug(DEBUG_UPLOAD_NOTIFICATION);

    $notification->trigger($GroupID, $TorrentID, $Feed, $Item);

    // RSS for bookmarks
    $DB->prepared_query('
        SELECT u.torrent_pass
        FROM users_main AS u
        INNER JOIN bookmarks_torrents AS b ON (b.UserID = u.ID)
        WHERE b.GroupID = ?
        ', $GroupID
    );
    while ([$Passkey] = $DB->next_record()) {
        $feedType[] = "torrents_bookmarks_t_$Passkey";
    }
    foreach ($feedType as $subFeed) {
        $Feed->populate($subFeed, $Item);
    }

    $Debug->set_flag('upload: notifications handled');

    $notification->trigger($GroupID, $TorrentID, $Feed, $Item);

    json_print('success', $Response);
} else {
    $folderClash = 0;
    if ($PublicTorrent || $UnsourcedTorrent || $folderClash) {
        View::show_header('Warning');
        echo $Twig->render('upload/result_warnings.twig', [
            'clash'     => $folderClash,
            'group_id'  => $GroupID,
            'public'    => $PublicTorrent,
            'unsourced' => $UnsourcedTorrent,
            'wiki_id'   => SOURCE_FLAG_WIKI_PAGE_ID,
        ]);
        View::show_footer();
    } elseif (isset($RequestID)) {
        header("Location: requests.php?action=takefill&requestid=$RequestID&torrentid=$TorrentID&auth=" . $Viewer->auth());
    } else {
        header("Location: torrents.php?id=$GroupID");
    }
}

if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    ignore_user_abort(true);
    ob_flush();
    flush();
    ob_start(); // So we don't keep sending data to the client
}

if ($Viewer->option('AutoSubscribe')) {
    (new Gazelle\User\Subscription($Viewer))->subscribeComments('torrents', $GroupID);
}

// Manage notifications
if (!in_array('notifications', $Viewer->paranoia())) {
    // For RSS
    $Feed = new Gazelle\Feed;
    $Item = $Feed->item(
        $Title,
        Text::strip_bbcode($Properties['GroupDescription']),
        "torrents.php?action=download&id={$TorrentID}&torrent_pass=[[PASSKEY]]",
        date('r'),
        $Viewer->username(),
        'torrents.php?id=' . $GroupID,
        implode(',', $tagList)
    );

    $notification = (new Gazelle\Notification\Upload)
        ->addTags($tagList)
        ->addCategory($categoryName)
        ->addUser($Viewer)
        ->setDebug(DEBUG_UPLOAD_NOTIFICATION);
    $notification->trigger($GroupID, $TorrentID, $Feed, $Item);

    // RSS for bookmarks
    $DB->prepared_query('
        SELECT u.torrent_pass
        FROM users_main AS u
        INNER JOIN bookmarks_torrents AS b ON (b.UserID = u.ID)
        WHERE b.GroupID = ?
        ', $GroupID
    );
    while ([$Passkey] = $DB->next_record()) {
        $feedType[] = "torrents_bookmarks_t_$Passkey";
    }
    foreach ($feedType as $subFeed) {
        $Feed->populate($subFeed, $Item);
    }

    $Debug->set_flag('upload: notifications handled');
}
