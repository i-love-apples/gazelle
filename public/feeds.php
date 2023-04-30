<?php

// Prevent people from clearing feeds
if (isset($_GET['clearcache'])) {
    unset($_GET['clearcache']);
}

require_once(__DIR__ . '/../lib/bootstrap.php');

$feed = new Gazelle\Feed;
$user = (new Gazelle\Manager\User)->findById((int)($_GET['user'] ?? 0));
if (!$user?->isEnabled()
    || empty($_GET['feed'])
    || md5($user->id() . RSS_HASH . $_GET['passkey' ?? 'NOTPASS']) !== $_GET['auth'] ?? 'NOTAUTH'
) {
    echo $feed->blocked();
    exit;
}

switch ($_GET['feed']) {
    case 'torrents_all':
    case 'torrents_apps':
    case 'torrents_games':
    case 'torrents_iosapps':
    case 'torrents_iosgames':
    case 'torrents_graphics':
    case 'torrents_audio':
    case 'torrents_tutorials':
    case 'torrents_other':
        echo $feed->byFeedName($user, $_GET['feed']);
        break;
    case 'feed_news':
        echo $feed->news(new Gazelle\Manager\News);
        break;
    case 'feed_blog':
        echo $feed->blog(new Gazelle\Manager\Blog, new Gazelle\Manager\ForumThread);
        break;
    case 'feed_changelog':
        echo $feed->changelog(new Gazelle\Manager\Changelog);
        break;
    default:
        echo match(true) {
            str_starts_with($_GET['feed'], 'torrents_bookmarks_t_') => $feed->bookmark($user, $_GET['feed']),
            str_starts_with($_GET['feed'], 'torrents_notify_') =>      $feed->personal($user, $_GET['feed'], $_GET['name'] ?? null),
            default => $feed->blocked()
        };
}
