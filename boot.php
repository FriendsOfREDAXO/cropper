<?php

/** @var rex_addon $this */

require_once 'vendor/autoload.php';

//if (rex::isBackend() && is_object(rex::getUser())) {
//    rex_perm::register('cropper[]');
//}

if (rex::isBackend() && rex::getUser()) {

    rex_view::addCssFile($this->getAssetsUrl('css/cropper.css'));
    if (rex_be_controller::getCurrentPagePart(2) == 'cropper') {
        rex_view::addJsFile($this->getAssetsUrl('js/cropper.min.js'));
        rex_view::addJsFile($this->getAssetsUrl('js/jquery-cropper.min.js'));
        rex_view::addJsFile($this->getAssetsUrl('js/image_cropper.js'));
    }

    rex_extension::register( 'MEDIA_FORM_EDIT', function( rex_extension_point $ep ){
        /** @var rex_sql $media */
        $media = $ep->getParam('media');

        /** @var rex_media $rexMedia */
        $rexMedia = rex_media::get($media->getValue('filename'));

        if ($rexMedia instanceof rex_media && $rexMedia->isImage()) {

            $link = rex_url::backendPage('mediapool/cropper', array(
                'rex_file_category' => rex_request::get('rex_file_category', 'integer', 0),
                'file_id' => $ep->getParam('id'),
                'media_name' => $media->getValue('filename'),
            ), true);

            $fragment = new rex_fragment();
            $fragment->setVar('elements', array(array(
                'label' => '<label>'.rex_i18n::msg('cropper_media_edit_label').'</label>',
                'field' => '<a class="btn btn-primary" href="' . $link . '"><span>' . rex_i18n::msg('cropper_media_edit_link') . '</span> <i class="fa fa-crop"></i></a>',
            )), false);
            return $fragment->parse('core/form/form.php');
        }
    });

}
