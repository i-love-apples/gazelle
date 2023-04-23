<?php

$tgroup = (new Gazelle\Manager\TGroup)->findById((int)($_POST['id'] ?? 0));
if (!$tgroup) {
    error(404);
}
authorize();

$forum = (new Gazelle\Manager\Forum)->findById(EDITING_FORUM_ID);
if (is_null($forum)) {
    error(404);
}
$thread = (new Gazelle\Manager\ForumThread)->create(
    forum: $forum,
    userId:  SYSTEM_USER_ID,
    title:   "Editing request \xE2\x80\x93 Torrent Group: " . $tgroup->name(),
    body:    $Twig->render('forum/edit-request-body.twig', [
        'link'    => '[torrent]' . $tgroup->id() . '[/torrent]',
        'details' => trim($_POST['edit_details']),
        'viewer'  => $Viewer,
    ]),
);

header("Location: {$thread->location()}");
