<?php

header('Content-Type: application/json; charset=utf-8');
if (empty($_GET['query'])) {
    echo json_encode([]);
    exit;
}

$maxRows = 10;
$maxKeySize = 255;
$fullName = trim(urldecode($_GET['query']));
$keySize = min($maxKeySize, max(1, strlen($fullName)));
$letters = mb_strtolower(mb_substr($fullName, 0, $keySize));

$key = 'autocomplete_torrents_upload_' . $keySize . '_' . str_replace(' ', '%20', $letters);
$autoSuggest = $Cache->get($key);
if ($autoSuggest === false) {
    $DB->prepared_query("
        SELECT a.ID,
            a.Name,
            a.TagList,
            a.WikiImage,
            a.Wikibody,
            a.CategoryID
        FROM torrents_group AS a
        LEFT JOIN
            torrents AS b
        ON
            a.ID = b.GroupID
        WHERE a.Name LIKE ?
        AND b.ID IS NOT NULL
        GROUP BY
            a.Name, a.TagList, a.WikiImage, a.Wikibody, a.CategoryID
        ORDER BY a.Name ASC
        LIMIT ?",
        str_replace('\\','\\\\',$letters) . '%', $maxRows
    );
    $autoSuggest = $DB->to_array(false, MYSQLI_NUM, false);
    $Cache->cache_value($key, $autoSuggest, 1800 + 7200 * ($maxKeySize - $keySize)); // Can't cache things for too long in case names are edited
}

$matched = 0;
$response = [
    'query' => $fullName,
    'suggestions' => []
];
foreach ($autoSuggest as $suggestion) {
    [$id, $name, $taglist, $wikiimage, $wikibody, $categoryid] = $suggestion;
    if (stripos($name, $fullName) === 0) {
        $response['suggestions'][] = ['id' => $id, 'value' => $name, 'taglist' => str_replace(' ', ', ', $taglist), 'wikiimage' => $wikiimage, 'wikibody' => $wikibody, 'categoryid' => $categoryid];
        if (++$matched > $maxRows) {
            break;
        }
    }
}
echo json_encode($response);
