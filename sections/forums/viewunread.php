<?php
/**********|| Page to show individual forums || ********************************\

Things to expect in $_GET:
    forumId: ID of the forum curently being browsed
    page:    The page the user's on.
    page = 1 is the same as no page

********************************************************************************/

if (isset($_GET["page"])) {
    $forumToc = (new Gazelle\Manager\Forum())->tableOfContentsUnreadThread($Viewer, $_GET["page"]);
} else {
    $forumToc = (new Gazelle\Manager\Forum())->tableOfContentsUnreadThread($Viewer);
}

$paginator = new Gazelle\Util\Paginator(TOPICS_PER_PAGE, (int)($_GET['page'] ?? 1));

if (count($forumToc) > 0) {
    $totalRows = (new Gazelle\Manager\Forum())->tableOfContentsUnreadThreadTotalPages($Viewer);
    $paginator->setTotal($totalRows);
    
    $perPage      = $Viewer->postsPerPage();
    
    foreach ($forumToc as &$thread) {
        $forum = (new Gazelle\Manager\Forum)->findById((int)$thread['ForumID']);
        if (!is_null($forum)) {
            $userLastRead = $forum->userLastRead($Viewer->id(), $perPage);
        }
        if (isset($userLastRead[$thread['ID']])) {
            $thread['last_read_page'] = (int)$userLastRead[$thread['ID']]['Page'];
            $thread['last_read_post'] = $userLastRead[$thread['ID']]['PostID'];
            $catchup = $userLastRead[$thread['ID']]['PostID'] >= $thread['LastPostID']
                || $Viewer->forumCatchupEpoch() >= strtotime($thread['LastPostTime']);
            $thread['is_read'] = true;
        } else {
            $thread['last_read_page'] = null;
            $thread['last_read_post'] = null;
            $catchup = $Viewer->forumCatchupEpoch() >= strtotime($thread['LastPostTime']);
            $thread['is_read'] = false;
        }
    
        $thread['icon_class'] = (($thread['IsLocked'] && !$thread['IsSticky']) || $catchup ? 'read' : 'unread')
            . ($thread['IsLocked'] ? '_locked' : '')
            . ($thread['IsSticky'] ? '_sticky' : '');
    
        $links = [];
        $threadPages = ceil($thread['NumPosts'] / $perPage);
        if ($threadPages > 1) {
            $ellipsis = false;
            for ($i = 1; $i <= $threadPages; $i++) {
                if ($threadPages > 4 && ($i > 2 && $i <= $threadPages - 2)) {
                    if (!$ellipsis) {
                        $links[] = '-';
                        $ellipsis = true;
                    }
                    continue;
                }
                $links[] = sprintf('<a href="forums.php?action=viewthread&amp;threadid=%d&amp;page=%d">%d</a>',
                    $thread['ID'], $i, $i);
            }
        }
        $thread = array_merge($thread, [
            'cut_title'  => shortenString($thread['Title'] ?? "", 75 - (2 * count($links))),
            'page_links' => $links ? (' (' . implode(' ', $links) . ')') : '',
        ]);
        unset($thread); // because looping by reference
    }
}


echo $Twig->render('forum/viewunread.twig', [
    'donor_forum' => $forumId == DONOR_FORUM,
    'toc'         => $forumToc,
    'paginator'   => $paginator,
    'viewer'      => $Viewer,
]);
