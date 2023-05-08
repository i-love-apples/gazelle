<?php

$thread = (new Gazelle\Manager\ForumThread)->findById((int)($_GET['threadid'] ?? 0));
if (is_null($thread)) {
    error(404);
}

if (!$Viewer->readAccess($thread->forum())) {
    error(403);
}

$postId = (int)$_GET['postid'];
if (!$postId) {
    error(404);
}

if ($thread->upvotePost($Viewer->id(), $postId)) {
    echo "success";
} else {
    echo "error";
}
die();