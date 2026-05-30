<?php

$addon = rex_addon::get('cropper');

if (!$addon->hasConfig()) {
    $addon->setConfig('aspect_ratios', '16:9
4:3
1:1
2:3');
}

if (null === $addon->getConfig('compact_toolbar_in_stage')) {
    $addon->setConfig('compact_toolbar_in_stage', 0);
}



