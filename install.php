<?php

$addon = rex_addon::get('cropper');

$configEnabled = static function ($value): bool {
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value) || is_float($value)) {
        return (int) $value === 1;
    }

    if (is_string($value)) {
        $trimmedValue = trim($value);
        if ('' === $trimmedValue) {
            return false;
        }

        if (preg_match('/(^|\|)1(\||$)/', $trimmedValue) === 1) {
            return true;
        }

        return in_array(strtolower($trimmedValue), ['1', 'true', 'yes', 'on'], true);
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

if (null === $addon->getConfig('toolbar_mode')) {
    $compactEnabled = $configEnabled($addon->getConfig('compact_toolbar_in_stage'));
    $legacyLayoutEnabled = $configEnabled($addon->getConfig('compact_toolbar_legacy_layout'));
    $toolbarMode = 'legacy';

    if ($compactEnabled) {
        $toolbarMode = $legacyLayoutEnabled ? 'legacy' : 'compact';
    }

    $addon->setConfig('toolbar_mode', $toolbarMode);
}

if (null === $addon->getConfig('show_info_sidebar_initially')) {
    $addon->setConfig('show_info_sidebar_initially', 0);
}
