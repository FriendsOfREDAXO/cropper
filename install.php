<?php

$addon = rex_addon::get('cropper');

rex_logger::factory()->log('notice', 'test', [], __FILE__, __LINE__);
if (!$addon->hasConfig()) {
    $addon->setConfig('aspect_ratios', '16:9
4:3
1:1
2:3');
}
