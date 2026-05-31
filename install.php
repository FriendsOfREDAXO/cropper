<?php

$addon = rex_addon::get('cropper');

$configEnabled = static function ($value) use (&$configEnabled): bool {
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value) || is_float($value)) {
        return (int) $value === 1;
    }

    if (is_string($value)) {
        $normalized = strtolower(trim($value));
        if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'off', ''], true)) {
            return false;
        }

        $unserialized = @unserialize($value, ['allowed_classes' => false]);
        if (false !== $unserialized || 'b:0;' === $value) {
            return $configEnabled($unserialized);
        }

        return str_contains($normalized, '"1"') || str_contains($normalized, "'1'");
    }

    if (is_array($value)) {
        return in_array(1, $value, true) || in_array('1', $value, true);
    }

    return false;
};

if (!$addon->hasConfig()) {
    $addon->setConfig('aspect_ratios', '16:9
4:3
1:1
2:3');
}

$configuredToolbarMode = $addon->getConfig('toolbar_mode');
if (!is_string($configuredToolbarMode) || '' === trim($configuredToolbarMode)) {
    $addon->setConfig('toolbar_mode', 'legacy');
} elseif (trim($configuredToolbarMode) === 'compact') {
    $addon->setConfig('toolbar_mode', 'legacy');
}

if (null === $addon->getConfig('show_info_sidebar_initially')) {
    $addon->setConfig('show_info_sidebar_initially', 0);
}

if (null === $addon->getConfig('stage_max_height')) {
    $addon->setConfig('stage_max_height', '70vh');
}

if (null === $addon->getConfig('default_jpg_quality')) {
    $addon->setConfig('default_jpg_quality', 100);
}

if (null === $addon->getConfig('default_png_compression')) {
    $addon->setConfig('default_png_compression', 9);
}

if (null === $addon->getConfig('show_compression_settings_in_mediapool')) {
    $legacyShowJpg = $addon->getConfig('show_jpg_quality_in_mediapool', null);
    $legacyShowPng = $addon->getConfig('show_png_compression_in_mediapool', null);

    if (null === $legacyShowJpg && null === $legacyShowPng) {
        $addon->setConfig('show_compression_settings_in_mediapool', 1);
    } else {
        $show = ((int) $legacyShowJpg === 1) || ((int) $legacyShowPng === 1);
        $addon->setConfig('show_compression_settings_in_mediapool', $show ? 1 : 0);
    }
}

$addon->setConfig(
    'show_compression_settings_in_mediapool',
    $configEnabled($addon->getConfig('show_compression_settings_in_mediapool', 1)) ? 1 : 0
);
