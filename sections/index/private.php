<?php
Text::$TOC = true;

$contestMan = new Gazelle\Manager\Contest;
$newsMan    = new Gazelle\Manager\News;
$newsReader = new Gazelle\WitnessTable\UserReadNews;
$tgMan      = new Gazelle\Manager\TGroup;

if ($newsMan->latestId() != -1 && $newsReader->lastRead($Viewer->id()) < $newsMan->latestId()) {
    $newsReader->witness($Viewer->id());
}

$contest     = $contestMan->currentContest();
$contestRank = null;
if (!$contest) {
    $leaderboard = [];
} else {
    $leaderboard = $contest->leaderboard(CONTEST_ENTRIES_PER_PAGE, 0);
    if ($leaderboard) {
        /* Stop showing the contest results after two weeks */
        if ((time() - strtotime($contest->dateEnd())) / 86400 > 15) {
            $leaderboard = [];
        } else {
            $leaderboard = array_slice($leaderboard, 0, 3);
            $userMan = new Gazelle\Manager\User;
            foreach ($leaderboard as &$entry) {
                $entry['username'] = $userMan->findById($entry['user_id'])->username();
            }
            unset($entry);
            $contestRank = $contest->rank($Viewer);
        }
    }
}

$forumToc = (new Gazelle\Manager\Forum())->tableOfContentsRecentThread($Viewer, 5);
$perPage = $Viewer->postsPerPage();
if (count($forumToc) > 0) {
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

echo $Twig->render('index/private-sidebar.twig', [
    'blog'              => new Gazelle\Manager\Blog,
    'collage_count'     => (new Gazelle\Stats\Collage)->collageCount(),
    'contest_rank'      => $contestRank,
    'leaderboard'       => $leaderboard,
    'featured_aotm'     => $tgMan->featuredAlbumAotm(),
    'featured_showcase' => $tgMan->featuredAlbumShowcase(),
    'staff_blog'        => new Gazelle\Manager\StaffBlog,
    'poll'              => (new Gazelle\Manager\ForumPoll)->findByFeaturedPoll(),
    'request_stats'     => new Gazelle\Stats\Request,
    'torrent_stats'     => new Gazelle\Stats\Torrent,
    'user_stats'        => new Gazelle\Stats\Users,
    'viewer'            => $Viewer,
]);

echo $Twig->render('index/private-main.twig', [
    'admin'         => (int)$Viewer->permitted('admin_manage_news'),
    'contest'       => $contestMan->currentContest(),
    'latest'        => (new Gazelle\Manager\Torrent)->latestUploads(5),
    'latest_posts'  => $forumToc,
    'news'          => $newsMan->headlines(),
]);
