<?php

$addon = rex_addon::get('cropper');

if (!$addon->hasConfig()) {
    $addon->setConfig('aspect_ratios', '16:9
4:3
1:1
2:3');
}
