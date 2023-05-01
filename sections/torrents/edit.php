<?php
//**********************************************************************//
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~ Edit form ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~//
// This page relies on the Util\UploadForm class. All it does is call   //
// the necessary functions.                                             //
//----------------------------------------------------------------------//
// At the bottom, there are grouping functions which are off limits to  //
// most members.                                                        //
//**********************************************************************//

$torrent = (new Gazelle\Manager\Torrent)->findById((int)($_GET['id'] ?? 0));
if (is_null($torrent)) {
    error(404);
}

if ($Viewer->id() != $torrent->uploaderId() || $Viewer->disableWiki()) {
    if (!$Viewer->permitted('torrents_edit') && !$Viewer->permitted('edit_unknowns') && !$Viewer->permitted('users_mod')) {
        error(403);
    }
}

$artist       = $torrent->group()->primaryArtist();
$categoryId   = $torrent->group()->categoryId();
$categoryName = $torrent->group()->categoryName();
$tgroupId     = $torrent->groupId();
$torrentId    = $torrent->id();
$releaseTypes = (new Gazelle\ReleaseType)->list();

View::show_header('Edit torrent', ['js' => 'upload,torrent']);
?>
<div class="thin">
    <div class="header">
        <h2>Edit <a href="torrents.php?id=<?=$torrent->groupId()?>"><?=$torrent->group()->name();?></a> <?=$torrent->version();?></h2>
    </div>
    <?php
        $releasePlatform = (new \Gazelle\ReleasePlatform)->list();
        $releaseIncludes = (new \Gazelle\ReleaseIncludes)->list();
    ?>
    <div class="box pad">
        <form action="torrents.php" method="post">
            <input type="hidden" name="action" value="changetorrent" />
            <input type="hidden" name="auth" value="<?= $Viewer->auth() ?>" />
            <input type="hidden" name="torrentid" value="<?= $torrentId ?>" />
            <table style="border: 0px; background: transparent;">
                <tr>
                    <td class="label">Version:</td>
                    <td><input type="text" id="version" name="version" size="20" value="<?= $torrent->info()['Version'] ?? '' ?>" /></td>
                </tr>
                <tr>
                    <td class="label">Mac Platform:</td>
                    <td>
                        <select id="platform" name="platform">>
    <?php       foreach ($releasePlatform as $Key => $Val) { ?>
                            <option value="<?= $Val ?>"<?= $Val == $torrent->info()['Platform'] ? ' selected="selected"' : '' ?>><?= $Val ?></option>
    <?php       } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="label">Includes:</td>
                    <td>
                        <select id="includes" name="includes">
    <?php       foreach ($releaseIncludes as $Key => $Val) { ?>
                            <option value="<?= $Val ?>"<?= $Val == $torrent->info()['Includes'] ? ' selected="selected"' : '' ?>><?= $Val ?></option>
    <?php       } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="label">OS version (optional):</td>
                    <td><input type="text" id="osversion" name="osversion" size="60" value="<?= $torrent->info()['OSVersion'] ?? '' ?>" /></td>
                </tr>
                <tr>
                    <td class="label">CPU (optional):</td>
                    <td><input type="text" id="processor" name="processor" size="60" value="<?= $torrent->info()['Processor'] ?? '' ?>" /></td>
                </tr>
                <tr>
                    <td class="label">RAM (optional):</td>
                    <td><input type="text" id="ram" name="ram" size="60" value="<?= $torrent->info()['RAM'] ?? '' ?>" /></td>
                </tr>
                <tr>
                    <td class="label">Video RAM (optional):</td>
                    <td><input type="text" id="vram" name="vram" size="60" value="<?= $torrent->info()['VRAM'] ?? '' ?>" /></td>
                </tr>
                <tr>
                    <td class="label">Release description:</td>
                    <td>
                        <?= (new Gazelle\Util\Textarea('desc', display_str($torrent->info()['Description'] ?? ''), 60, 5))->emit() ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="center">
                        <input type="submit" value="Change torrent" />
                    </td>
                </tr>
            </table>
        </form>
    </div>
    <?php
        if ($Viewer->permitted('edit_unknowns')) {
            $uploadForm = new Gazelle\Util\UploadForm(
                $Viewer,
                [
                    'ID'                      => $torrentId,
                    'Media'                   => $torrent->media(),
                    'Format'                  => $torrent->format(),
                    'Bitrate'                 => $torrent->encoding(),
                    'RemasterYear'            => $torrent->remasterYear(),
                    'Remastered'              => $torrent->isRemastered(),
                    'RemasterTitle'           => $torrent->remasterTitle(),
                    'RemasterCatalogueNumber' => $torrent->remasterCatalogueNumber(),
                    'RemasterRecordLabel'     => $torrent->remasterRecordLabel(),
                    'Scene'                   => $torrent->isScene(),
                    'FreeTorrent'             => $torrent->isFreeleech(),
                    'FreeTorrentInt'          => $torrent->isFreeleechInt(),
                    'FreeLeechType'           => $torrent->freeleechType(),
                    'TorrentDescription'      => $torrent->description(),
                    'CategoryID'              => $categoryId,
                    'Title'                   => $torrent->group()->name(),
                    'Year'                    => $torrent->group()->year(),
                    'VanityHouse'             => $torrent->group()->isShowcase(),
                    'GroupID'                 => $tgroupId,
                    'UserID'                  => $torrent->uploaderId(),
                    'HasLog'                  => $torrent->hasLog(),
                    'HasCue'                  => $torrent->hasCue(),
                    'LogScore'                => $torrent->logScore(),
                    'BadTags'                 => $torrent->hasBadTags(),
                    'BadFolders'              => $torrent->hasBadFolders(),
                    'BadFiles'                => $torrent->hasBadFiles(),
                    'MissingLineage'          => $torrent->hasMissingLineage(),
                    'CassetteApproved'        => $torrent->hasCassetteApproved(),
                    'LossymasterApproved'     => $torrent->hasLossymasterApproved(),
                    'LossywebApproved'        => $torrent->hasLossywebApproved(),
                ],
                $Err ?? false,
                false
            );
            // $uploadForm->setCategoryId($categoryId);
            ?>
                <h3>Change freeleech</h3>
                <div class="box pad" style="padding: 0px">
                <?php
                    echo $uploadForm->head();
                    echo $uploadForm->foot(false);
                ?>
                </div>
            <?php
        };
        if ($Viewer->permitted('torrents_edit') && $Viewer->permitted('users_mod')) {
    ?>
    <h3>Change group</h3>
    <div class="box pad">
        <form class="edit_form" name="torrent_group" action="torrents.php" method="post">
            <input type="hidden" name="action" value="editgroupid" />
            <input type="hidden" name="auth" value="<?= $Viewer->auth() ?>" />
            <input type="hidden" name="torrentid" value="<?= $torrentId ?>" />
            <input type="hidden" name="oldgroupid" value="<?= $tgroupId ?>" />
            <div><br>
                Select an existing group to autocomplete group id:<br>
                <input type="text" id="title_search_groupid" size="60" /><br><br>
                <h3>Group ID: </h3>
                <input type="text" id="changegroupid" name="groupid" value="<?= $tgroupId ?>" size="10" />
                <br>
                <br>
                <div style="text-align: center;">
                    <input type="submit" value="Change group ID" />
                </div>
            </div>
        </form>
    </div>
    <script>SetAutocompleteForMigration();</script>
    <h3>Split off into new group</h3>
    <div class="box pad">
        <form class="split_form" name="torrent_group" action="torrents.php" method="post">
            <input type="hidden" name="action" value="newgroup" />
            <input type="hidden" name="auth" value="<?= $Viewer->auth() ?>" />
            <input type="hidden" name="torrentid" value="<?= $torrentId ?>" />
            <input type="hidden" name="oldgroupid" value="<?= $tgroupId ?>" />
            <div>
                <h3>Title: </h3>
                <input type="text" name="title" value="<?= $torrent->group()->name() ?>" size="50" />
                <br>
                <br>
                <div style="text-align: center;">
                    <input type="submit" value="Split off into new group" />
                </div>
            </div>
        </form>
    </div>
    <?php
        }
    ?>
</div>
<?php


View::show_footer();
