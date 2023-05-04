<?php
//**********************************************************************//
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~ Upload form ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~//
// This page relies on the TORRENT_FORM class. All it does is call      //
// the necessary functions.                                             //
//----------------------------------------------------------------------//
// $Properties, $Err and $categoryId are set in upload_handle.php,      //
// and are only used when the form doesn't validate and this page must  //
// be called again.                                                     //
//**********************************************************************//

ini_set('max_file_uploads', '100');

if (!isset($Properties)) {
    $requestId = (int)($_GET['requestid'] ?? 0);
    if ((int)($_GET['groupid'] ?? 0)) {
        $tgroup = (new Gazelle\Manager\TGroup)->findById((int)$_GET['groupid']);
        if (is_null($tgroup)) {
            unset($_GET['groupid']);
        } else {
            $Properties = [
                'GroupID'          => $tgroup->id(),
                'CategoryID'       => $tgroup->categoryId(),
                'ReleaseType'      => $tgroup->releaseType(),
                'Title'            => $tgroup->name(),
                'Year'             => $tgroup->year(),
                'Image'            => $tgroup->image(),
                'GroupDescription' => $tgroup->description(),
                'RecordLabel'      => $tgroup->recordLabel(),
                'CatalogueNumber'  => $tgroup->catalogueNumber(),
                'VanityHouse'      => $tgroup->isShowcase(),
                'Artists'          => $tgroup->artistRole()?->idList() ?? [],
                'Tags'             => implode(', ', $tgroup->tagNameList()),
            ];
            if ($requestId) {
                $Properties['RequestID'] = $requestId;
            }
        }
    } elseif ($requestId) {
        $request = (new Gazelle\Manager\Request)->findById($requestId);
        if ($request) {
            $Properties = [
                'RequestID'        => $requestId,
                'CategoryID'       => $request->categoryId(),
                'ReleaseType'      => $request->releaseType(),
                'Title'            => $request->title(),
                'Year'             => $request->year(),
                'Image'            => $request->image(),
                'GroupDescription' => $request->description(),
                'RecordLabel'      => $request->recordLabel(),
                'CatalogueNumber'  => $request->catalogueNumber(),
                'Artists'          => $request->artistRole()?->idList() ?? [],
                'Tags'             => implode(', ', $request->tagNameList()),
            ];
        }
    }
}

if (empty($Properties)) {
    $Properties = null;
}
if (empty($Err)) {
    $Err = null;
}

$dnu     = new Gazelle\Manager\DNU;
$dnuNew  = $dnu->hasNewForUser($Viewer);

View::show_header('Upload', ['js' => 'upload,validate_upload,valid_tags,musicbrainz,bbcode']);
?>
<div class="torrents_hide_dnu" style="margin: 0px auto; width: 700px;">
    <h3 id="dnu_header">Do Not Upload List</h3>
    <p><?= $dnuNew ? '<strong class="important_text">' : '' ?>Last updated: <?= time_diff($dnu->latest()) ?><?= $dnuNew ? '</strong>' : '' ?></p>
    <p>The following releases are currently forbidden from being uploaded to the site. Do not upload them unless your torrent meets a condition specified in the comment.

    <span id="showdnu"><a href="#" onclick="$('#dnulist').gtoggle(); this.innerHTML = (this.innerHTML == 'Hide' ? 'Show' : 'Hide'); return false;" class="brackets">Show</a></span>

    </p>
    <table id="dnulist" class="hidden">
        <tr class="colhead">
            <td width="30%"><strong>Name</strong></td>
            <td><strong>Reason</strong></td>
        </tr>
<?php foreach ($dnu->dnuList() as $bad) { ?>
        <tr>
            <td>
                <?= Text::full_format($bad['name']) ?>
<?php   if ($bad['is_new']) { ?>
                <strong class="important_text">(New!)</strong>
<?php   } ?>
            </td>
            <td><?= Text::full_format($bad['comment']) ?></td>
        </tr>
<?php } ?>
    </table>
</div><?= $dnuHide ? '<br />' : '' ?>
<?php
$uploadForm = new Gazelle\Util\UploadForm($Viewer, $Properties, $Err);
// if (isset($categoryId)) {
//     // we have been require'd from upload_handle
//     $uploadForm->setCategoryId($categoryId);
// }
echo $uploadForm->head();
echo match (CATEGORY[($Properties['CategoryID'] ?? 1) - 1]) {
    'Applications', 'Games', 'IOS Applications', 'IOS Games' => $uploadForm->simple_group_form(),
    default                                                  => $uploadForm->simple_form(),
};
echo $uploadForm->foot(true);
