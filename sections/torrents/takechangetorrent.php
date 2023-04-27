<?php
/***************************************************************
* Temp handler for changing a single torrent.
****************************************************************/

authorize();

$torrent = (new Gazelle\Manager\Torrent)->findById((int)($_POST['torrentid'] ?? 0));
if (is_null($torrent)) {
    error('Torrent does not exist!');
}

if ($Viewer->id() != $torrent->uploaderId() || $Viewer->disableWiki()) {
    if (!$Viewer->permitted('torrents_edit') && !$Viewer->permitted('edit_unknowns') && !$Viewer->permitted('users_mod')) {
        error(403);
    }
}

$Version = trim($_POST['version'] ?? '');
if ($Version === '') {
    error('Version cannot be blank');
}

$Platform = trim($_POST['platform'] ?? '');
if ($Platform === '') {
    error('Platform cannot be blank');
}

$Includes = trim($_POST['includes'] ?? '');
if ($Includes === '') {
    error('Includes cannot be blank');
}

$Description = trim($_POST['desc'] ?? '');
if ($Description === '') {
    error('Includes cannot be blank');
} else {
    if (strlen($Description) < 10) {
        error('The description has a minimum length of 10 characters.');
    }
}

$OSVersion = trim($_POST['osversion'] ?? '');
$Processor = trim($_POST['processor'] ?? '');
$RAM = trim($_POST['ram'] ?? '');
$VRAM = trim($_POST['vram'] ?? '');

$DB->prepared_query('
    UPDATE torrents SET
        Version = ?, Platform = ?, Includes = ?, Description = ?, OSVersion = ?, Processor = ?, RAM = ?, VRAM = ?
    WHERE ID = ?
    ', $Version, $Platform, $Includes, $Description, $OSVersion, $Processor, $RAM, $VRAM, $torrent->id()
);
$DB->commit();

(new Gazelle\Log)->torrent($torrent->groupId(), $_POST['torrentid'], $Viewer->id(), 'Torrent informations')
    ->general("Torrent ".$_POST['torrentid']." ({$torrent->group()->name()}) in group {$torrent->groupId()} was edited by "
        . $Viewer->username() . " (Torrent informations)");

$torrent->flush();
$Cache->delete_value("torrent_download_$TorrentID");

header('Location: ' . $torrent->location());
