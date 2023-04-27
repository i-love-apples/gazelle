<?php
authorize();

if (!$Viewer->permitted('site_edit_wiki')) {
    error(403);
}
if (!$Viewer->permitted('torrents_edit_vanityhouse') && isset($_POST['vanity_house'])) {
    error(403);
}
$tgroup = (new Gazelle\Manager\TGroup)->findById((int)$_REQUEST['groupid']);
if (is_null($tgroup)) {
    error(404);
}
$GroupID = $tgroup->id();

$logInfo = [];
if (($_GET['action'] ?? '') == 'revert') {
    // we're reverting to a previous revision
    $RevisionID = (int)$_GET['revisionid'];
    if (!$RevisionID) {
        error(0);
    }
    if (empty($_GET['confirm'])) {
        echo $Twig->render('tgroup/revert-confirm.twig', [
            'auth'        => $Viewer->auth(),
            'group_id'    => $GroupID,
            'revision_id' => $RevisionID,
        ]);
        exit;
    }
    $revert = $tgroup->revertRevision($Viewer->id(), $RevisionID);
    if (is_null($revert)) {
        error(404);
    }
    [$Body, $Image] = $revert;
} else {
    if (empty($_POST['image'])) {
        $Image = '';
    } else {
        $Image = $_POST['image'];
        if (!preg_match(IMAGE_REGEXP, $Image)) {
            error(display_str($Image) . " does not look like a valid image url");
        }
        $banned = (new Gazelle\Util\ImageProxy($Viewer))->badHost($Image);
        if ($banned) {
            error("Please rehost images from $banned elsewhere.");
        }
    }

    $categoryID = $_POST['type'];

    if (empty($_POST['tags'])) {
        error('A torrent group needs at least one tag to be submitted.');
    }
    $TagList = array_unique(array_map('trim', explode(',', $_POST['tags'])));

    $Body = $_POST['body'];
    if (strlen($Body) < 10) {
        error('The description has a minimum length of 10 characters.');
    }
    if ($_POST['summary']) {
        $logInfo[] = "summary: " . trim($_POST['summary']);
    }
    $RevisionID = $tgroup->createRevision($Body, $Image, $_POST['summary'], $Viewer);
}

$imageFlush = ($Image != $tgroup->showFallbackImage(false)->image());

$tagMan = new Gazelle\Manager\Tag;
if ($_POST['tags'] != $tgroup->tags()) {
    $tagMan->removeGroupTorrentTags($GroupID);
    foreach ($TagList as $tag) {
        $tag = $tagMan->resolve($tagMan->sanitize($tag));
        if (!empty($tag)) {
            $TagID = $tagMan->create($tag, $Viewer->id());
            $tagMan->createTorrentTag($TagID, $GroupID, $Viewer->id(), 10);
        }
    }
}

$tgroup->setUpdate('CategoryID', $categoryID+1)
    ->setUpdate('WikiImage', $Image)
    ->setUpdate('WikiBody', $Body)
    ->setUpdate('TagList', $tagMan->normalize(str_replace(',', ' ', $_POST['tags'])))
    ->modify();

if ($imageFlush) {
    $tgroup->imageFlush();
}

$noCoverArt = isset($_POST['no_cover_art']);
if ($noCoverArt != $tgroup->hasNoCoverArt()) {
    $tgroup->toggleNoCoverArt($noCoverArt);
    $logInfo[] = "No cover art exception " . ($noCoverArt ? 'added' : 'removed');
}
if ($logInfo) {
    (new Gazelle\Log)->group($tgroup->id(), $Viewer->id(), implode(', ', $logInfo));
}

header('Location: ' . $tgroup->location());
