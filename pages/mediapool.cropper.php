<?php

/**
 * Author: Joachim Doerr
 * Date: 2019-02-15
 * Time: 12:04.
 */

use FriendsOfRedaxo\Cropper\Cropper\CropperExecutor;
use FriendsOfRedaxo\Cropper\Cropper\CroppingException;

const POOL_MEDIA = 'mediapool/media';

$csrf = rex_csrf_token::factory('mediapool_structure');
$user = rex::getUser();

$allowedExtensions = ['jpg' => ['jpg', 'jpeg'], 'png' => ['png'], 'gif' => ['gif']];
$mediaName = rex_request::request('media_name', 'string', null);
$isPreviewRequest = rex_request::request('cropper_preview', 'int', 0) === 1;
$urlParameter = ['file_id' => rex_request::request('file_id', 'integer'), 'rex_file_category' => rex_request::request('rex_file_category', 'integer')];

$body = '';
$title = '';
$class = 'edit';

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

$backLink = '<a class="btn btn-default cropper-back-button" href="' . rex_url::backendPage(POOL_MEDIA, $urlParameter, false) . '"><span class="fa fa-arrow-left" aria-hidden="true"></span><span>' . rex_i18n::msg('cropper_back_to_media') . '</span></a>';

$toggleButtons = '
<div class="cropper-page-toggles">
    <button
        type="button"
        id="cropper_sidebar_toggle"
        class="btn btn-default cropper-sidebar-toggle"
        aria-expanded="true"
        aria-controls="cropper-sidebar"
        data-expanded-label="' . rex_i18n::msg('cropper_sidebar_collapse') . '"
        data-collapsed-label="' . rex_i18n::msg('cropper_sidebar_expand') . '"
        data-toggle="tooltip"
        data-animation="false"
        data-original-title="' . rex_i18n::msg('cropper_sidebar_collapse') . '"
    >
        <span class="fa fa-info-circle" aria-hidden="true"></span>
    </button>';

$toggleButtons .= '
</div>';

$back = '<div class="cropper-page-options">' . $backLink . $toggleButtons . '</div>';

if (!$user instanceof rex_user || !$user->hasPerm('cropper[]')) {
    rex_response::sendRedirect(rex_url::backendPage(POOL_MEDIA, $urlParameter, false));
}

if ($isPreviewRequest) {
    if (null === $mediaName) {
        throw new rex_exception('Missing media name for cropper preview.');
    }

    $previewMedia = rex_media::get($mediaName);
    if (!$previewMedia instanceof rex_media) {
        throw new rex_exception('Preview media not found.');
    }

    $previewPath = rex_path::media($previewMedia->getFileName());
    $previewContentType = rex_file::mimeType($previewPath) ?? 'application/octet-stream';

    rex_response::cleanOutputBuffers();
    rex_response::sendFile(
        $previewPath,
        $previewContentType,
        'inline',
        $previewMedia->getFileName(),
    );
    exit;
}

try {
    if (1 === rex_request::request('btn_save', 'integer', 0)) {
        if (!$csrf->isValid()) {
            throw new CroppingException('EXCEPTION csrf not valide'); // TODO text!
        }

        $cropperExecutor = new CropperExecutor($_POST);
        $result = $cropperExecutor->crop();

        if ($result['ok']) {
            $media = $result['media'];
            if (!$media instanceof rex_media) {
                throw new CroppingException('EXCEPTION! NO MEDIA OBJ');
            }

            $urlParameter['file_id'] = $media->getId();
            $urlParameter['cropper_msg'] = $result['msg'];

            if (rex_post('opener_input_field', 'string')) {
                $urlParameter['opener_input_field'] = rex_post('opener_input_field', 'string');
            }

            rex_response::sendRedirect(rex_url::backendPage(POOL_MEDIA, $urlParameter, false));
        } else {
            // don't jump stay and get error msg
            echo rex_view::error(rex_i18n::msg($result['msg']));
        }
    }

    if (1 === rex_request::request('btn_abort', 'integer', 0)) {
        rex_response::sendRedirect(rex_url::backendPage(POOL_MEDIA, $urlParameter, false));
    }

    if (!rex_addon::exists('media_manager')) {
        throw new CroppingException('EXCEPTION error MSG media_manager missing'); // TODO text!
    }
    if (rex_addon::exists('media_manager') && !rex_addon::get('media_manager')->isAvailable()) {
        throw new CroppingException('EXCEPTION error MSG media_manager must be active'); // TODO text!
    }
    if (null === $mediaName) {
        throw new CroppingException('EXCEPTION! NO NAME!'); // TODO text!
    }
    if (!$media = rex_media::get($mediaName)) {
        throw new CroppingException('EXCEPTION! NO MEDIA OBJ'); // TODO text!
    }

    $mediaPoolWidth = (int) $media->getWidth();
    $mediaPoolHeight = (int) $media->getHeight();

    $mediaSql = rex_sql::factory();
    $mediaSql->setQuery(
        'SELECT width, height FROM ' . rex::getTable('media') . ' WHERE filename = ? LIMIT 1',
        [$media->getFileName()],
    );

    if ($mediaSql->getRows() > 0) {
        $dbWidth = (int) $mediaSql->getValue('width');
        $dbHeight = (int) $mediaSql->getValue('height');

        if ($dbWidth > 0 && $dbHeight > 0) {
            $mediaPoolWidth = $dbWidth;
            $mediaPoolHeight = $dbHeight;
        }
    }

    if (rex_media::isImageType(rex_file::extension($mediaName))) {
        $panel = '';
        $options = [];

        $title = sprintf(rex_i18n::msg('cropper_media_crop_title'), pathinfo($media->getFileName(), PATHINFO_FILENAME));

        $defaultJpgQuality = (int) rex_config::get('cropper', 'default_jpg_quality', 100);
        $defaultJpgQuality = max(0, min(100, $defaultJpgQuality));
        $showCompressionSettingsInMediapool = $configEnabled(rex_config::get('cropper', 'show_compression_settings_in_mediapool', 1));

        $defaultPngCompression = (int) rex_config::get('cropper', 'default_png_compression', 9);
        $defaultPngCompression = max(0, min(9, $defaultPngCompression));

        $pngIn = ('png' === $media->getExtension() && $user->isAdmin() && $showCompressionSettingsInMediapool) ? ' in' : '';
        $jpgIn = (('jpg' === $media->getExtension() || 'jpeg' === $media->getExtension()) && $user->isAdmin() && $showCompressionSettingsInMediapool) ? ' in' : '';

        $jpgQuality = rex_request::request('jpg_quality', 'integer', $defaultJpgQuality);
        $jpgQuality = max(0, min(100, $jpgQuality));
        $pngCompression = rex_request::request('png_compression', 'integer', $defaultPngCompression);
        $pngCompression = max(0, min(9, $pngCompression));
        $newFileExtension = rex_request::request('new_file_extension', 'string', null);
        $newFileName = rex_request::request('new_file_name', 'string', rex_escape(pathinfo($media->getFileName(), PATHINFO_FILENAME)));

        $allowed = (null === $newFileExtension) ? false : true;

        // img type options and check is possible to use for my media file
        foreach ($allowedExtensions as $item => $ass) {
            $selected = '';
            if (in_array($media->getExtension(), $ass) && null === $newFileExtension) {
                $selected = 'selected="selected"';
                $allowed = true;
            }
            if (null !== $newFileExtension && in_array($newFileExtension, $ass)) {
                $selected = 'selected="selected"';
                if ('jpg' == $item) {
                    $pngIn = '';
                    $jpgIn = $showCompressionSettingsInMediapool ? ' in' : '';
                } elseif ('png' == $item) {
                    $pngIn = $showCompressionSettingsInMediapool ? ' in' : '';
                    $jpgIn = '';
                } elseif ('gif' == $item) {
                    $pngIn = '';
                    $jpgIn = '';
                }
            }
            $options[] = "<option value=\"$item\" $selected>$item</option>";
        }

        if (!$allowed) {
            throw new CroppingException('EXCEPTION! NOT ALLOWED FILE TYPE NOT SUPPORTED'); // TODO text!
        }

        $fileMtime = @filemtime(rex_path::media($mediaName));
        $mtime = (false !== $fileMtime ? (string) $fileMtime : (string) time()) . uniqid('', true);

        $previewUrl = rex_url::backendPage('mediapool/cropper', [
            'media_name' => $mediaName,
            'cropper_preview' => 1,
        ], false);

        $fragment = new rex_fragment();
        $fragment->setVar('mediaUrl', $previewUrl);
        $fragment->setVar('media', $media);
        $fragment->setVar('mediaPoolWidth', $mediaPoolWidth);
        $fragment->setVar('mediaPoolHeight', $mediaPoolHeight);
        $fragment->setVar('mtime', $mtime);
        $panel = $fragment->parse('cropper_panel.php');

        // JPG QUALITY
        $fragment = new rex_fragment();
        $fragment->setVar('elements', [[
            'class' => 'rex-range-input-group',
            'left' => '<input id="rex-js-rating-source-jpg-quality" type="range" min="0" max="100" step="1" value="' . $jpgQuality . '" />',
            'field' => '<input class="form-control" id="rex-js-rating-text-jpg-quality" type="text" name="jpg_quality" value="' . $jpgQuality . '" />',
        ]], false);
        $jpgQualityElement = $fragment->parse('core/form/input_group.php');

        // PNG COMPRESSION
        $fragment = new rex_fragment();
        $fragment->setVar('elements', [[
            'class' => 'rex-range-input-group',
            'left' => '<input id="rex-js-rating-source-png-compression" type="range" min="0" max="9" step="1" value="' . $pngCompression . '" />',
            'field' => '<input class="form-control" id="rex-js-rating-text-png-compression" type="text" name="png_compression" value="' . $pngCompression . '" />',
        ]], false);
        $pngCompressionElement = $fragment->parse('core/form/input_group.php');

        // FORM ELEMENTS
        // SAVE OPTION
        $checkbox = '
            <label class="checkbox-inline checbox-switch switch-primary">
                <input type="checkbox" name="create_new_image" id="create_new_image" checked />
            <span></span>' . rex_i18n::msg('cropper_img_save_info') . '</label>';
        if (!$user->hasPerm('cropper[overwrite]')) :
            $checkbox = '<div class="nocheckbox"><input type="hidden" name="create_new_image" value="1" />' . rex_i18n::msg('cropper_img_save_info_nochoice') . '</div>';
        endif;
        $fragment = new rex_fragment();
        $fragment->setVar('elements', [
            [
                'label' => '<label for="rex-mediapool-title">' . rex_i18n::msg('cropper_save_options') . '</label>',
                'field' => $checkbox,
            ],
        ], false);
        $panel .= $fragment->parse('core/form/form.php');

        // FILENAME
        $fragment = new rex_fragment();
        $fragment->setVar('elements', [
            [
                'label' => '<label for="rex-mediapool-title">' . rex_i18n::msg('pool_filename') . '</label>',
                //                'field' => '<div class="input-group">
                //                            <input class="form-control" type="text" name="new_file_name" value="' . $newFileName . '" />
                //                            <select name="new_file_extension" class="selectpicker" readonly="readonly">' . implode("\n", $options) . '</select>
                //                        </div>',
                'field' => '<div class="input-group">
                                <input class="form-control" type="text" name="new_file_name" value="' . $newFileName . '" />
                                <input type="hidden" name="new_file_extension" value="' . $media->getExtension() . '" />
                                <span class="input-group-addon">' . $media->getExtension() . '</span>
                            </div>',
            ],
        ], false);

        // Medienpool-Kategorien zur Auswahl
        $rex_file_category = $media->getCategoryId();
        $mediaPerm = $user->getComplexPerm('media');
        $permAll = $mediaPerm->hasCategoryPerm(0);
        if (!$permAll && !$mediaPerm->hasCategoryPerm($rex_file_category)) {
            $rex_file_category = 0;
        }
        $cats_sel = new rex_media_category_select();
        $cats_sel->setStyle('class="form-control selectpicker"');
        $cats_sel->setAttribute('data-live-search', 'true');
        $cats_sel->setSize(1);
        $cats_sel->setName('rex_file_category');
        $cats_sel->setId('rex-mediapool-category');
        $cats_sel->addOption(rex_i18n::msg('pool_kats_no'), '0');
        $cats_sel->setSelected($rex_file_category);

        $mediacat_select = '
        <dl class="rex-form-group form-group">
                        <dt>
                            <label for="rex-mediapool-category">' . rex_i18n::msg('pool_file_category') . '</label>
                        </dt>
                        <dd>
                            ' . $cats_sel->get() . '
                        </dd>
                    </dl>';

        $panel .= '<div id="new_file_name" class="collapse in">' . $fragment->parse('core/form/form.php') . $mediacat_select . '</div>';

        if ($showCompressionSettingsInMediapool) {
            // FORM ELEMENTS
            // JPG QUALITY
            $fragment = new rex_fragment();
            $fragment->setVar('elements', [
                [
                    'label' => '<label for="rex-mediapool-title">' . rex_i18n::msg('cropper_jpg_quality') . '</label>',
                    'field' => $jpgQualityElement,
                ],
            ], false);
            $panel .= "<div class=\"collapse $jpgIn\">" . $fragment->parse('core/form/form.php') . '</div>';
        } else {
            $panel .= '<input type="hidden" name="jpg_quality" value="' . $defaultJpgQuality . '" />';
        }

        if ($showCompressionSettingsInMediapool) {
            // FORM ELEMENTS
            // PNG COMPRESSION
            $fragment = new rex_fragment();
            $fragment->setVar('elements', [
                [
                    'label' => '<label for="rex-mediapool-title">' . rex_i18n::msg('cropper_png_compression') . '</label>',
                    'field' => $pngCompressionElement,
                ],
            ], false);
            $panel .= "<div class=\"collapse $pngIn\">" . $fragment->parse('core/form/form.php') . '</div>';
        } else {
            $panel .= '<input type="hidden" name="png_compression" value="' . $defaultPngCompression . '" />';
        }

        // FORM FOOTER
        // BUTTONS
        $fragment = new rex_fragment();
        $fragment->setVar('elements', [
            ['field' => '<button class="btn btn-apply rex-form-aligned" type="submit" value="1" name="btn_save">' . rex_i18n::msg('form_save') . '</button>'],
            ['field' => '<button class="btn btn-abort" type="submit" value="1" name="btn_abort">' . rex_i18n::msg('form_abort') . '</button>'],
        ], false);
        $buttons = $fragment->parse('core/form/submit.php');

        // METAINFO-Felder (med_*) des Originals als versteckte Felder mitschicken.
        // Das metainfo-AddOn speichert seine Felder aus dem POST; ohne diese Felder
        // würde es sie beim Zuschneiden leeren.
        $metaHiddenFields = '';
        if (rex_addon::get('metainfo')->isAvailable()) {
            $sql = rex_sql::factory();
            $prefix = $sql->escapeLikeWildcards(rex_metainfo_media_handler::PREFIX) . '%';
            $metaFields = $sql->getArray(
                'SELECT name, type_id, attributes FROM ' . rex::getTable('metainfo_field') . ' WHERE name LIKE :prefix',
                ['prefix' => $prefix]
            );

            foreach ($metaFields as $metaField) {
                $name = (string) $metaField['name'];
                $stored = (string) $media->getValue($name);
                $typeId = (int) $metaField['type_id'];
                $attributes = (string) $metaField['attributes'];

                $isMulti = rex_metainfo_table_manager::FIELD_CHECKBOX === $typeId
                    || (rex_metainfo_table_manager::FIELD_SELECT === $typeId && str_contains($attributes, 'multiple'));

                if ($isMulti && '' !== $stored) {
                    // Mehrwertige Felder sind als |a|b| gespeichert -> als name="feld[]"
                    foreach (array_filter(explode('|', $stored), static fn ($v) => '' !== $v) as $part) {
                        $metaHiddenFields .= '<input type="hidden" name="' . rex_escape($name) . '[]" value="' . rex_escape($part) . '" />';
                    }
                } else {
                    // Einwertige Felder (inkl. Datum als Timestamp): Wert unverändert
                    $metaHiddenFields .= '<input type="hidden" name="' . rex_escape($name) . '" value="' . rex_escape($stored) . '" />';
                }
            }
        }

        // FINAL BODY CREATION
        $body = '
        <form action="' . rex_url::currentBackendPage() . '" method="post" enctype="multipart/form-data" data-pjax="false">
            ' . $csrf->getHiddenField() . '
            <input type="hidden" name="file_id" value="' . rex_request::request('file_id', 'integer') . '" />
            <input type="hidden" name="media_name" value="' . $mediaName . '" />
            <input type="hidden" name="rex_file_category" value="' . rex_request::request('rex_file_category', 'integer') . '" />
            <input type="hidden" name="opener_input_field" value="' . rex_request::request('opener_input_field', 'string') . '" />
            ' . $metaHiddenFields . '
            ' . $panel . $buttons . '
        </form>';
    }
} catch (CroppingException $e) {
    rex_logger::logException($e);
    $body = rex_view::error($e->getMessage());
}

$fragment = new rex_fragment();
$fragment->setVar('class', $class, false); // PAGE STYLE UGLY GREEN?
$fragment->setVar('title', $title, false); // FORM TITLE
$fragment->setVar('options', $back, false); // BACK BUTTON
$fragment->setVar('body', $body, false); // FORM
echo $fragment->parse('core/page/section.php');
