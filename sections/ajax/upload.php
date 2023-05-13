<?php
// echo out the slice of the form needed for the selected upload type ($_GET['section']).

$uploadForm = new Gazelle\Util\UploadForm($Viewer);
// $uploadForm->setCategoryId((int)$_GET['categoryid'] + 1);
$emitJS = isset($_GET['js']);

switch (CATEGORY[$_GET['categoryid']-1]) {
    case 'Applications':
    case 'Games':
    case 'IOS Applications':
    case 'IOS Games':
        if ($emitJS) {
            echo $uploadForm->albumReleaseJS();
        } else {
            echo $uploadForm->simple_group_form();
        }
        break;
    default:
        if ($emitJS) {
            echo $uploadForm->descriptionJS();
        } else {
            echo $uploadForm->simple_form();
        }
        break;
}
