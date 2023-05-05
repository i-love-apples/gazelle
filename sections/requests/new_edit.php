<?php

/*
 * Yeah, that's right, edit and new are the same place.
 * It makes the page uglier to read but ultimately better as the alternative
 * means maintaining 2 copies of almost identical files.
 *
 * If a variable appears to have been initialized by magic, remember
 * that this file could have been require()'ed from take_new_edit.php
 * which has already initialized things from the submitted form.
 */

$newRequest = $_GET['action'] === 'new';
$isRequestVersion = $_GET['requestversion'];
$disabledTag = "";
$readonlyTag = "";
if ($isRequestVersion) {
    $disabledTag = " disabled=\"disabled\" ";
    $readonlyTag = " readonly ";
}

$releasePlatform = (new \Gazelle\ReleasePlatform)->list();
$categoryName = "";

if ($newRequest) {
    if ($Viewer->uploadedSize() < (REQUEST_MIN+1) * 1024 * 1024 || !$Viewer->permitted('site_submit_requests')) {
        error('You have not enough upload to make a request.');
    }
    $request         = null;
    // $categoryName    = '';
    // $image           = '';
    // $title           = '';
    // $description     = '';
    // $year            = '';
    // $recordLabel     = '';
    // $catalogueNumber = '';
    // $oclc            = '';

    // We may be able to prepare some things based on whence we came
    if (isset($_GET['groupid'])) {
        $tgroup = (new Gazelle\Manager\TGroup)->findById((int)$_GET['groupid']);
        if ($tgroup) {
            $GroupID     = $tgroup->id();
            $categoryId  = $tgroup->categoryId();
            $title       = $tgroup->name();
            $image       = $tgroup->image();
            $tags        = implode(', ', $tgroup->tagNameList());
            $categoryName = $tgroup->categoryName();
        }
    }
} else {
    $request = (new Gazelle\Manager\Request)->findById((int)($_GET['id'] ?? 0));
    if (is_null($request)) {
        error(404);
    }
    $CanEdit = $request->canEdit($Viewer);
    if (!$CanEdit) {
        error(403);
    }
    $requestId = $request->id();

    if (!isset($returnEdit)) {
        // if we are coming back from an edit, these were already initialized in take_new_edit
        $categoryId  = $request->categoryId();
        $title       = $request->title();
        $description = $request->description();
        $image       = $request->image();
        $tags        = implode(', ', $request->tagNameList());
        $GroupID     = $request->tgroupId();
        $ownRequest  = $request->userId() == $Viewer->id();
        $categoryName = $request->categoryName();
    }
}

$isGroupOnStart = false;
if ($categoryName == "Applications" || $categoryName == "Games" || $categoryName == "IOS Applications" || $categoryName == "IOS Games" || $categoryName == "" ) {
    $isGroupOnStart = true;
}

$releaseTypes = (new Gazelle\ReleaseType)->list();
$releaseTags = (new \Gazelle\ReleaseTags)->list();
// $GenreTags    = (new Gazelle\Manager\Tag)->genreList();
if ($isRequestVersion) {
    $pageTitle    = 'Request version';
} else {
    $pageTitle    = $newRequest ? 'Create a request' : 'Edit request &rsaquo; ' . $request->selfLink();
}
View::show_header($pageTitle, ['js' => 'requests,form_validate']);
?>
<div class="thin">
    <div class="header">
        <h2><?= $pageTitle ?></h2>
    </div>
<?php
if (!$newRequest && $CanEdit && !$ownRequest && $Viewer->permitted('site_edit_requests')) {
    $requester = new Gazelle\User($request->userId());
?>
    <div class="box pad">
        <strong class="important_text">Warning! You are editing <?= $requester->link() ?>'s request.
        Be careful when making changes!</strong>
    </div>
<?php } ?>

    <div class="box pad">
        <form action="" method="post" id="request_form" onsubmit="Calculate();">
            <div>
<?php if (!$newRequest) { ?>
                <input type="hidden" name="requestid" value="<?=$requestId?>" />
<?php } ?>
                <input type="hidden" name="auth" value="<?= $Viewer->auth() ?>" />
                <input type="hidden" name="action" value="<?=($newRequest ? 'takenew' : 'takeedit')?>" />
            </div>

            <table class="layout">
                <tr>
                    <td colspan="2" class="center">Please make sure your request follows <a href="rules.php?p=requests">the request rules</a>!
<?php if (isset($Err)) { ?>
            <div class="save_message error"><?= $Err ?></div>
<?php } ?>
                    </td>
                </tr>
<?php if ($newRequest || $CanEdit) { ?>
                <tr>
                    <td class="label">
                        Type
                    </td>
                    <td>
                        <div id="categories_hidden_container">
                            <?php if ($isRequestVersion) { ?>
                                <input id="categories_hidden_container" type="hidden" name="type" value="<?= $categoryName ?>">
                            <?php } ?>
                        </div>
                        <select id="categories" name="type" onchange="Categories();" <?= $disabledTag ?>>
<?php    foreach (CATEGORY as $Cat) { ?>
                            <option value="<?=$Cat?>"<?= $categoryName === $Cat ? ' selected="selected"' : '' ?> ><?=$Cat?></option>
<?php    } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="label">Title</td>
                    <td>
                        <input id="title_search_group" type="text" name="title" size="45" value="<?= $title ?>" <?= $readonlyTag ?> />
                    </td>
                </tr>
<?php } ?>
<?php if ($newRequest || $CanEdit) { ?>
                <tr id="image_tr">
                    <td class="label">Image</td>
                    <td>
                        <input id="image" type="text" name="image" size="45" value="<?= $image ?>" <?= $readonlyTag ?> />
<?php       if (IMAGE_HOST_BANNED) { ?>
                        <br /><b>Images hosted on <strong class="important_text"><?= implode(', ', IMAGE_HOST_BANNED)
                            ?> are not allowed</strong>, please rehost first on one of <?= implode(', ', IMAGE_HOST_RECOMMENDED) ?>.</b>
<?php       } ?>
                    </td>
                </tr>
<?php   } ?>
                <tr>
                    <td class="label">Tags</td>
                    <td>
                        <select id="genre_tags" name="genre_tags" onchange="add_tag();" <?= $disabledTag ?> >
                            <option></option>
    <?php       foreach ($releaseTags as $Key => $Val) { ?>
                            <option value="<?= $Val ?>"><?= $Val ?></option>
    <?php       } ?>
                        </select>
                        <input type="text" id="tags" name="tags" size="40" value="<?= display_str($tags ?? '') ?>" data-gazelle-autocomplete="true" readonly />
                        <input id="clean_tags" type="button" onclick="clear_tag()" value="Clean tags" title="Clean tags" <?php if ($isRequestVersion) { echo("style=\"display: none;\""); } ?>>
                    </td>
                </tr>
                <tr id="version_row" <?php if (!$isGroupOnStart) { ?>style="display: none;"<?php } ?>>
                    <td class="label">Version</td>
                    <td>
                        <input id="version" type="text" name="version" size="20" value="<?= $version ?>" />
                    </td>
                </tr>
                <tr id="platform_row" <?php if (!$isGroupOnStart) { ?>style="display: none;"<?php } ?>>
                    <td class="label">Mac Platform:</td>
                    <td>
                        <select id="platform" name="platform">>
                            <option></option>
    <?php       foreach ($releasePlatform as $Key => $Val) { ?>
                            <option value="<?= $Val ?>"<?= $Val == $platform ? ' selected="selected"' : '' ?>><?= $Val ?></option>
    <?php       } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="label">Description</td>
                    <td>
                        <textarea name="description" cols="70" rows="7"><?= $description ?></textarea> <br />
                    </td>
                </tr>
<?php    if ($Viewer->permitted('site_moderate_requests')) { ?>
                <tr>
                    <td class="label">Torrent group</td>
                    <td>
                        <?=SITE_URL?>/torrents.php?id=<input id="groupid" type="text" name="groupid" value="<?=$GroupID?>" size="15" <?= $readonlyTag ?> /><br />
                        If this request matches a torrent group <span style="font-weight: bold;">already existing</span> on the site, please indicate that here.
                    </td>
                </tr>
<?php    } elseif (isset($GroupID) && ($categoryId == CATEGORY_MUSIC)) { ?>
                <tr>
                    <td class="label">Torrent group</td>
                    <td>
                        <a href="torrents.php?id=<?=$GroupID?>"><?=SITE_URL?>/torrents.php?id=<?=$GroupID?></a><br />
                        This request <?=($newRequest ? 'will be' : 'is')?> associated with the above torrent group.
<?php        if (!$newRequest) {    ?>
                        If this is incorrect, please <a href="reports.php?action=report&amp;type=request&amp;id=<?=$requestId?>">report this request</a> so that staff can fix it.
<?php         }    ?>
                        <input id="groupid" type="hidden" name="groupid" value="<?=$GroupID?>" />
                    </td>
                </tr>
<?php    }
    if ($newRequest) { ?>
                <tr id="voting">
                    <td class="label">Bounty (MiB)</td>
                    <td>
                        <input type="text" id="amount_box" size="8" value="<?= !empty($Bounty) ? $Bounty : REQUEST_MIN ?>" />
                        <select id="unit" name="unit" onchange="Calculate();">
                            <option value="mb"<?=(!empty($_POST['unit']) && $_POST['unit'] === 'mb' ? ' selected="selected"' : '') ?>>MiB</option>
                            <option value="gb"<?=(!empty($_POST['unit']) && $_POST['unit'] === 'gb' ? ' selected="selected"' : '') ?>>GiB</option>
                        </select>
                        <?= REQUEST_TAX > 0 ? "<strong><?= REQUEST_TAX * 100 ?>% of this is deducted as tax by the system.</strong>" : '' ?>
                        <p>Bounty must be greater than or equal to <?= REQUEST_MIN ?> MiB.</p>
                    </td>
                </tr>
                <tr>
                    <td class="label">Bounty information</td>
                    <td>
                        <input type="hidden" id="amount" name="amount" value="<?= !empty($Bounty) ? $Bounty : REQUEST_MIN * 1024 * 1024 ?>" />
                        <input type="hidden" id="current_uploaded" value="<?=$Viewer->uploadedSize()?>" />
                        <input type="hidden" id="current_downloaded" value="<?=$Viewer->downloadedSize()?>" />
                        <input type='hidden' id='request_tax' value="<?=REQUEST_TAX?>" />
                        <?= REQUEST_TAX > 0
                            ? 'Bounty after tax: <strong><span id="bounty_after_tax">' . sprintf("%0.2f", 100 * (1 - REQUEST_TAX)) . ' MiB</span></strong><br />'
                            : '<span id="bounty_after_tax" style="display: none;">' . sprintf("%0.2f", 100 * (1 - REQUEST_TAX)) . ' MiB</span>'
                        ?>
                        If you add the entered <strong><span id="new_bounty"><?= REQUEST_MIN ?>.00 MiB</span></strong> of bounty, your new stats will be: <br />
                        Uploaded: <span id="new_uploaded"><?=Format::get_size($Viewer->uploadedSize())?></span><br />
                        Ratio: <span id="new_ratio"><?=Format::get_ratio_html($Viewer->uploadedSize(), $Viewer->downloadedSize())?></span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="center">
                        <input type="submit" id="button" value="Create request" disabled="disabled" />
                    </td>
                </tr>
<?php    } else { ?>
                <tr>
                    <td colspan="2" class="center">
                        <input type="submit" id="button" value="Edit request" />
                    </td>
                </tr>
<?php    } ?>
            </table>
        </form>
        <script type="text/javascript">ToggleLogCue();<?=$newRequest ? " Calculate();" : '' ?></script>
        <script type="text/javascript">SetAutocomplete();</script>
    </div>
</div>
<?php
View::show_footer();
