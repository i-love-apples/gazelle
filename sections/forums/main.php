<?php

$userMan = new Gazelle\Stats\Users;
$users = $userMan->findOnline();

echo $Twig->render('forum/main.twig', [
    'toc'    => (new Gazelle\Manager\Forum())->tableOfContents($Viewer),
    'users' => $users,
    'viewer' => $Viewer,
]);
