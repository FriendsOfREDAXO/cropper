<?php

/** @var rex_addon $this */

require_once __DIR__ . '/vendor/autoload.php';

$user = rex::getUser();
$assetVersion = '?v=' . rawurlencode((string) $this->getVersion());

if (rex::isBackend() && $user instanceof rex_user) {
    rex_perm::register('cropper[]');
    rex_perm::register('cropper[overwrite]');
}

if (rex::isBackend() && $user instanceof rex_user && $user->hasPerm('cropper[]')) {

    rex_view::addCssFile($this->getAssetsUrl('vendor/cropper/cropper.css') . $assetVersion);
    rex_view::addCssFile($this->getAssetsUrl('cropper_ui_fix.css') . $assetVersion);
    if (rex_be_controller::getCurrentPagePart(2) == 'cropper' || rex_be_controller::getCurrentPagePart(1) == 'yform') {
        rex_view::addJsFile($this->getAssetsUrl('vendor/cropper/cropper.min.js') . $assetVersion);
        rex_view::addJsFile($this->getAssetsUrl('js/rex_cropper.js') . $assetVersion);
    }

    if (rex_addon::exists('yform') && rex_addon::get('yform')->isAvailable()) {
        rex_yform::addTemplatePath($this->getPath('ytemplates'));
        rex_view::addJsFile($this->getAssetsUrl('js/yform_media_crop.js') . $assetVersion);
    }

    rex_extension::register('MEDIA_FORM_EDIT', function (rex_extension_point $ep) {

        /** @var rex_sql $media */
        $media = $ep->getParam('media');

        $msg = rex_request::request('cropper_msg', 'string', null);
        $cropper_error = rex_request::request('cropper_error', 'boolean', false);

        if (!is_null($msg)) {
            echo ($cropper_error) ? rex_view::error(rex_i18n::msg($msg)) : rex_view::success(rex_i18n::msg($msg));
        }

        $filename = (string) $media->getValue('filename');
        $rexMedia = '' !== $filename ? rex_media::get($filename) : null;

        if ($rexMedia?->isImage()) {
            $linkParams = [
                'rex_file_category' => rex_request::get('rex_file_category', 'integer', 0),
                'file_id' => $ep->getParam('id'),
                'media_name' => $filename,
            ];

            if (rex_get('opener_input_field', 'string')) {
                $linkParams['opener_input_field'] = rex_get('opener_input_field', 'string');
            }

            $link = rex_url::backendPage('mediapool/cropper', $linkParams, true);

            $fragment = new rex_fragment();
            $fragment->setVar('elements', [[
                'label' => '<label>' . rex_i18n::msg('cropper_media_edit_label') . '</label>',
                'field' => '<a class="btn btn-primary" href="' . $link . '"><span>' . rex_i18n::msg('cropper_media_edit_link') . '</span> <i class="fa fa-crop"></i></a>',
            ]], false);
            return $fragment->parse('core/form/form.php');
        }
    });

    rex_extension::register('MEDIA_LIST_FUNCTIONS', function (rex_extension_point $ep) {
        $subject = $ep->getSubject();

        if ($this->getConfig('hide_edit_in_list') === null) {
            return $subject;
        }

        /** @var rex_sql $media */
        $media = $ep->getParam('media');


        $msg = rex_request::request('cropper_msg', 'string', null);
        $cropper_error = rex_request::request('cropper_error', 'boolean', false);

        if (!is_null($msg)) {
            echo ($cropper_error) ? rex_view::error(rex_i18n::msg($msg)) : rex_view::success(rex_i18n::msg($msg));
        }

        $filename = (string) $media->getValue('name');
        $rexMedia = '' !== $filename ? rex_media::get($filename) : null;

        if ($rexMedia?->isImage()) {
            $linkParams = [
                'rex_file_category' => rex_request::get('rex_file_category', 'integer', 0),
                'file_id' => $ep->getParam('id'),
                'media_name' => $filename,
            ];

            if (rex_get('opener_input_field', 'string')) {
                $linkParams['opener_input_field'] = rex_get('opener_input_field', 'string');
            }

            $link = rex_url::backendPage('mediapool/cropper', $linkParams, true);

            return '<a href="' . $link . '" class="cropper-media-edit-link"><span>' . rex_i18n::msg('cropper_media_edit_link') . '</span> <i class="fa fa-crop"></i></a>' .
                $subject;
        }
    });
}
