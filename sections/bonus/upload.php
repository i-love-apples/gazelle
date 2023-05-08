<?php

authorize();

if (!preg_match('/^upload-[1-4]$/', $Label, $match)) {
    error(403);
}

if (!$viewerBonus->purchaseUpload($Label)) {
    error("You aren't able to buy this upload amount. Do you have enough bonus points?");
}

header('Location: bonus.php?complete=' . urlencode($Label));
