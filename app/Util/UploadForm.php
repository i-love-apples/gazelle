<?php

/********************************************************************************
 ************ Torrent form class *************** upload.php and torrents.php ****
 ********************************************************************************
 ** This class is used to create both the upload form, and the 'edit torrent'  **
 ** form. It is broken down into several functions - head(), foot(),           **
 ** music_form() [music], audiobook_form() [Audiobooks and comedy], and        **
 ** simple_form() [everything else].                                           **
 **                                                                            **
 ** When it is called from the edit page, the forms are shortened quite a bit. **
 **                                                                            **
 ********************************************************************************/

namespace Gazelle\Util;

use \OrpheusNET\Logchecker\Logchecker;

class UploadForm extends \Gazelle\Base {
    var $user;

    protected int $categoryId = 0;

    var $NewTorrent = false;
    var $Torrent = [];
    var $Error = false;
    var $Disabled = '';
    var $Readonly = '';
    var $DisabledFlag = false;

    const TORRENT_INPUT_ACCEPT = ['application/x-bittorrent', '.torrent'];
    const JSON_INPUT_ACCEPT = ['application/json', '.json'];

    public function __construct(\Gazelle\User $user, $Torrent = false, $Error = false, $NewTorrent = true) {
        $this->user = $user;
        $this->NewTorrent = $NewTorrent;
        $this->Torrent = $Torrent;
        $this->Error = $Error;

        if (isset($this->Torrent['GroupID'])) {
            if ($this->Torrent['GroupID'] > 0) {
                $this->Disabled = ' disabled="disabled"';
                $this->Readonly = ' readonly';
                $this->DisabledFlag = true;
            }
        }
    }

    public function setCategoryId(int $categoryId): UploadForm {
        // FIXME: the upload form counts categories from zero
        $this->categoryId = $categoryId - 1;
        return $this;
    }

    /**
     * This is an awful hack until something better can be figured out.
     * We want to get rid eval()'ing Javascript code, and this produces
     * something that can be added to the DOM and the engine will run it.
     */
    function albumReleaseJS(): string {
        $groupDesc = new Textarea('album_desc', '');
        $relDesc   = new Textarea('release_desc', '');
        return Textarea::factory();
    }

    function descriptionJS(): string {
        $groupDesc = new Textarea('desc', '');
        return Textarea::factory();
    }

    function head(): string {
        return self::$twig->render('upload/header.twig', [
            'announce'    => $this->user->announceUrl(),
            'auth'        => $this->user->auth(),
            'category_id' => $this->categoryId,
            'error'       => $this->Error,
            'is_disabled' => $this->DisabledFlag,
            'is_new'      => (int)$this->NewTorrent,
            'info'        => $this->Torrent,
        ]);
    }

    function foot(bool $showFooter): string {
        return self::$twig->render('upload/footer.twig', [
            'is_new'      => (int)$this->NewTorrent,
            'info'        => $this->Torrent,
            'show_footer' => $showFooter,
            'viewer'      => $this->user,
        ]);
    }

    function simple_group_form(): string {
        $Torrent = $this->Torrent;
        $releaseTags = (new \Gazelle\ReleaseTags)->list();
        $releasePlatform = (new \Gazelle\ReleasePlatform)->list();
        $releaseIncludes = (new \Gazelle\ReleaseIncludes)->list();
        ob_start();
?>
        <table cellpadding="3" cellspacing="1" border="0" class="layout border slice" width="100%">
            <tr>
                <td class="label"><h3>Group information</h3></td><td></td>
        </tr>
            <tr id="name">

<?php
        if ($this->NewTorrent) {
?>
                <td class="label">Title:</td>
                <td><input type="text" id="title_search_group" name="title" size="60" value="<?=display_str($Torrent['Title'] ?? '') ?>"<?= $this->Readonly ?> /></td>
            </tr>
            <tr>
                <td class="label">Tags:</td>
                <td>
                    <select id="genre_tags" name="genre_tags" onchange="add_tag();"<?=$this->Disabled?>>
                        <option></option>
<?php       foreach ($releaseTags as $Key => $Val) { ?>
                        <option value="<?= $Val ?>"<?= !$this->NewTorrent && $Key == $Torrent['ReleaseTags'] ? ' selected="selected"' : '' ?>><?= $Val ?></option>
<?php       } ?>
                    </select>
                    <input type="text" id="tags" name="tags" size="40" value="<?= display_str($Torrent['Tags'] ?? '') ?>"<?=
                        $this->user->hasAutocomplete('other') ? ' data-gazelle-autocomplete="true"' : '' ?> readonly />
                    <input type="button" onclick="clear_tag()" value="Clean tags" title="Clean tags">
                </td>
            </tr>
            <tr>
                <td class="label">Image (optional):</td>
                <td><input type="text" id="image" name="image" size="60" value="<?=display_str($Torrent['Image'] ?? '') ?>"<?=$this->Readonly?> />
                <br />Artwork helps improve the quality of the catalog. Please try to find a decent sized image (500x500).
<?php       if (IMAGE_HOST_BANNED) { ?>
                <br />Images hosted on <?= implode(', ', IMAGE_HOST_BANNED) ?> are not allowed, please rehost first on one of <?= implode(', ', IMAGE_HOST_RECOMMENDED) ?>
<?php       } ?>
                </td>
            </tr>

            <tr>
                <td class="label">Description:</td>
                <td>
                    <?= (new Textarea('album_desc', display_str($Torrent['GroupDescription'] ?? ''), 60, 5))->emit() ?>
                </td>
            </tr>
<?php   } ?>
        </table>

        <table cellpadding="3" cellspacing="1" border="0" class="layout border slice" width="100%">
            <tr>
                <td class="label"><h3>Torrent information</h3></td><td></td>
            </tr>
            <tr>
                <td class="label">Version:</td>
                <td><input type="text" id="version" name="version" size="20" value="<?= display_str($Torrent['Version'] ?? '') ?>" /></td>
            </tr>
            <tr>
                <td class="label">Mac Platform:</td>
                <td>
                    <select id="platform" name="platform">>
                        <option></option>
<?php       foreach ($releasePlatform as $Key => $Val) { ?>
                        <option value="<?= $Val ?>"<?= $Val == $Torrent['Platform'] ? ' selected="selected"' : '' ?>><?= $Val ?></option>
<?php       } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="label">Includes:</td>
                <td>
                    <select id="includes" name="includes">
                        <option></option>
<?php       foreach ($releaseIncludes as $Key => $Val) { ?>
                        <option value="<?= $Val ?>"<?= $Val == $Torrent['Includes'] ? ' selected="selected"' : '' ?>><?= $Val ?></option>
<?php       } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="label">OS version (optional):</td>
                <td><input type="text" id="osversion" name="osversion" size="60" value="<?=display_str($Torrent['OSVersion'] ?? '') ?>" /></td>
            </tr>
            <tr>
                <td class="label">CPU (optional):</td>
                <td><input type="text" id="processor" name="processor" size="60" value="<?=display_str($Torrent['Processor'] ?? '') ?>" /></td>
            </tr>
            <tr>
                <td class="label">RAM (optional):</td>
                <td><input type="text" id="ram" name="ram" size="60" value="<?=display_str($Torrent['RAM'] ?? '') ?>" /></td>
            </tr>
            <tr>
                <td class="label">Video RAM (optional):</td>
                <td><input type="text" id="vram" name="vram" size="60" value="<?=display_str($Torrent['VRAM'] ?? '') ?>" /></td>
            </tr>
            <tr>
                <td class="label">Release description:</td>
                <td>
                    <?= (new Textarea('desc', display_str($Torrent['TorrentDescription'] ?? ''), 60, 5))->emit() ?>
                </td>
            </tr>
        </table>
        <script>SetAutocomplete();</script>
<?php
        return ob_get_clean();
    }

    function simple_form(): string {
        $Torrent = $this->Torrent;
        $releaseTags = (new \Gazelle\ReleaseTags)->list();
        ob_start();
?>
        <table cellpadding="3" cellspacing="1" border="0" class="layout border slice" width="100%">
            <tr>
                <td class="label"><h3>Group information</h3></td><td></td>
            </tr>
                <tr id="name">

<?php
        if ($this->NewTorrent) {
?>
                <td class="label">Title:</td>
                <td><input type="text" id="title" name="title" size="60" value="<?=display_str($Torrent['Title'] ?? '') ?>" /></td>
            </tr>
            <tr>
                <td class="label">Tags:</td>
                <td>
                    <select id="genre_tags" name="genre_tags" onchange="add_tag();"<?=$this->Disabled?>>
                        <option></option>
<?php       foreach ($releaseTags as $Key => $Val) { ?>
                        <option value="<?= $Val ?>"<?= !$this->NewTorrent && $Key == $Torrent['ReleaseTags'] ? ' selected="selected"' : '' ?>><?= $Val ?></option>
<?php       } ?>
                    </select>
                    <input type="text" id="tags" name="tags" size="40" value="<?= display_str($Torrent['Tags'] ?? '') ?>"<?=
                        $this->user->hasAutocomplete('other') ? ' data-gazelle-autocomplete="true"' : '' ?> readonly />
                    <input type="button" onclick="clear_tag()" value="Clean tags" title="Clean tags">
                </td>
            </tr>
            <tr>
                <td class="label">Image (optional):</td>
                <td><input type="text" id="image" name="image" size="60" value="<?=display_str($Torrent['Image'] ?? '') ?>"<?=$this->Readonly?> />
                <br />Artwork helps improve the quality of the catalog. Please try to find a decent sized image (500x500).
<?php       if (IMAGE_HOST_BANNED) { ?>
                <br />Images hosted on <?= implode(', ', IMAGE_HOST_BANNED) ?> are not allowed, please rehost first on one of <?= implode(', ', IMAGE_HOST_RECOMMENDED) ?>
<?php       } ?>
                </td>
            </tr>
            <tr>
                <td class="label">Description:</td>
                <td>
                    <?= (new Textarea('album_desc', display_str($Torrent['GroupDescription'] ?? ''), 60, 5))->emit() ?>
                </td>
            </tr>
<?php   } ?>
        </table>
<?php
        return ob_get_clean();
    }
}
?>