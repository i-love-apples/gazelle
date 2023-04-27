<?php

$tgroup = (new Gazelle\Manager\TGroup)->findById((int)$_GET['id']);
if (is_null($tgroup)) {
    error(404);
}

// if (!$Viewer->permitted('site_edit_wiki')) {
//     error(403);
// }

if ($Viewer->disableWiki()) {
    if (!$Viewer->permitted('torrents_edit') && !$Viewer->permitted('edit_unknowns') && !$Viewer->permitted('users_mod')) {
        error(403);
    }
}

echo $Twig->render('tgroup/edit.twig', [
    'body'         => new Gazelle\Util\Textarea('body', $tgroup->description(), 80, 20),
    'release_type' => (new Gazelle\ReleaseType)->list(),
    'release_tags' => (new \Gazelle\ReleaseTags)->list(),
    'tgroup'       => $tgroup->showFallbackImage(false),
    'viewer'       => $Viewer,
]);
