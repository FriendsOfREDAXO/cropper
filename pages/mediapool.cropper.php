<?php
/**
 * Author: Joachim Doerr
 * Date: 2019-02-15
 * Time: 12:04
 */

use Cropper\CropperExecutor;
use Cropper\CroppingException;

$csrf = rex_csrf_token::factory('mediapool_structure');

$allowedExtensions = array('jpg' => array('jpg', 'jpeg'), 'png' => array('png'), 'gif' => array('gif'));
$mediaName = rex_request::request('media_name', 'string', null);

$body = '';
$title = '';
$class = 'edit';

$backUrl = rex_url::backendPage('mediapool/media', array('file_id' => rex_request::request('file_id', 'integer'), 'rex_file_category' => rex_request::request('rex_file_category', 'integer')), false);
$back = '<a class="cropper_back_to_media" href="' . $backUrl . '">' . rex_i18n::msg('cropper_back_to_media') . '</a>';

try {
    if (rex_request::request('btn_save', 'integer', 0) === 1) {


        if (!$csrf->isValid()) {
            throw new CroppingException('EXCEPTION csrf not valide'); // TODO text!
        }

        // TODO create img with media_manager and save it in category
        // TODO display message and link to new generated img

        $cropperExecutor = new CropperExecutor($_POST);
        $cropperExecutor->crop();

        echo rex_view::info('// TODO create img with media_manager and save it in category');
        echo rex_view::info('// TODO display message and link to new generated img');
    }

    if (rex_request::request('btn_abort', 'integer', 0) === 1) {
        rex_response::sendRedirect($backUrl);
    }

    if (!rex_addon::exists('media_manager')) {
        throw new CroppingException('EXCEPTION error MSG media_manager missing'); // TODO text!
    }
    if (rex_addon::exists('media_manager') && !rex_addon::get('media_manager')->isAvailable()) {
        throw new CroppingException('EXCEPTION error MSG media_manager must be active'); // TODO text!
    }
    if (is_null($mediaName)) {
        throw new CroppingException('EXCEPTION! NO NAME!'); // TODO text!
    }
    if (!$media = rex_media::get($mediaName)) {
        throw new CroppingException('EXCEPTION! NO MEDIA OBJ'); // TODO text!
    }
    if ($media instanceof rex_media && rex_media::isImageType(rex_file::extension($mediaName))) {

        $formElements = array();
        $panel = '';
        $options = array();

        $title = sprintf(rex_i18n::msg('cropper_media_crop_title'), pathinfo($media->getFileName(), PATHINFO_FILENAME));

        $pngIn = ($media->getExtension() == 'png') ? ' in' : '';
        $jpgIn = ($media->getExtension() == 'jpg' or $media->getExtension() == 'jpeg') ? ' in' : '';

        $jpgQuality = rex_request::request('jpg_quality', 'integer', rex_addon::get('media_manager')->getConfig('jpg_quality'));
        $pngCompression = rex_request::request('png_compression', 'integer', rex_addon::get('media_manager')->getConfig('png_compression'));
        $newFileExtension = rex_request::request('new_file_extension', 'string', null);
        $newFileName = rex_request::request('new_file_name', 'string', rex_escape(pathinfo($media->getFileName(), PATHINFO_FILENAME)));

        $allowed = (is_null($newFileExtension)) ? false : true;

        // img type options and check is possible to use for my media file
        foreach ($allowedExtensions as $item => $ass) {
            $selected = '';
            if (in_array($media->getExtension(), $ass) && is_null($newFileExtension)) {
                $selected = 'selected="selected"';
                $allowed = true;
            }
            if (!is_null($newFileExtension) && in_array($newFileExtension, $ass)) {
                $selected = 'selected="selected"';
                if ($item == 'jpg') {
                    $pngIn = '';
                    $jpgIn = ' in';
                } else if ($item == 'png') {
                    $pngIn = ' in';
                    $jpgIn = '';
                } else if ($item == 'gif') {
                    $pngIn = '';
                    $jpgIn = '';
                }
            }
            $options[] = "<option value=\"$item\" $selected>$item</option>";
        }

        if (!$allowed) {
            throw new CroppingException('EXCEPTION! NOT ALLOWED FILE TYPE NOT SUPPORTED'); // TODO text!
        }

        $panel .= '
            <div class="cropper_image_wrapper">
                <img id="cropper_image" src="' . rex_url::media($mediaName) . '">
                <div class="docs-buttons">
                    <div class="btn-group">
                      <button type="button" class="btn btn-primary" data-method="setDragMode" data-option="move" data-toggle="tooltip" data-original-title="Move" data-animation="false">
                        <span class="fa fa-arrows"></span>
                      </button>
                      <button type="button" class="btn btn-primary" data-method="setDragMode" data-option="crop" data-toggle="tooltip" data-original-title="Crop" data-animation="false">
                        <span class="fa fa-crop"></span>
                      </button>
                      <button type="button" class="btn btn-primary" data-method="clear" data-toggle="tooltip" data-original-title="Clear" data-animation="false">
                        <span class="fa fa-remove"></span>
                      </button>
                      <button type="button" class="btn btn-primary" data-method="zoom" data-option="0.1" data-toggle="tooltip" data-original-title="Zoom In" data-animation="false">
                        <span class="fa fa-search-plus"></span>
                      </button>
                      <button type="button" class="btn btn-primary" data-method="zoom" data-option="-0.1" data-toggle="tooltip" data-original-title="Zoom Out" data-animation="false">
                        <span class="fa fa-search-minus"></span>
                      </button>
                      <button type="button" class="btn btn-primary" data-method="rotate" data-option="-45" data-toggle="tooltip" data-original-title="Rotate Left -45" data-animation="false">
                        <span class="fa fa-rotate-left"></span>
                      </button>
                      <button type="button" class="btn btn-primary" data-method="rotate" data-option="45" data-toggle="tooltip" data-original-title="Rotate Right 45" data-animation="false">
                        <span class="fa fa-rotate-right"></span>
                      </button>
                      <button type="button" class="btn btn-primary" data-method="scaleX" data-option="-1" data-toggle="tooltip" data-original-title="Flip Horizontal" data-animation="false">
                        <span class="fa fa-arrows-h"></span>
                      </button>
                      <button type="button" class="btn btn-primary" data-method="scaleY" data-option="-1" data-toggle="tooltip" data-original-title="Flip Vertical" data-animation="false">
                        <span class="fa fa-arrows-v"></span>
                      </button>
                    </div>
                </div>
                <div class="docs-toggles">
                    <div class="btn-group d-flex flex-nowrap" data-toggle="buttons">
                      <label class="btn btn-primary" data-toggle="none_tooltip" data-animation="false" data-original-title="aspectRatio: ' . $media->getWidth() . ' / ' . $media->getHeight() . '">
                        <input type="radio" class="sr-only" id="aspectRatio1" name="aspectRatio" value="' . str_replace(',', '.', ($media->getWidth() / $media->getHeight())) . '">Original
                      </label>
                      <label class="btn btn-primary" data-toggle="none_tooltip" data-animation="false" data-original-title="aspectRatio: 16 / 9">
                        <input type="radio" class="sr-only" id="aspectRatio2" name="aspectRatio" value="1.7777777777777777">16:9
                      </label>
                      <label class="btn btn-primary" data-toggle="none_tooltip" data-animation="false" data-original-title="aspectRatio: 4 / 3">
                        <input type="radio" class="sr-only" id="aspectRatio3" name="aspectRatio" value="1.3333333333333333">4:3
                      </label>
                      <label class="btn btn-primary" data-toggle="none_tooltip" data-animation="false" data-original-title="aspectRatio: 1 / 1">
                        <input type="radio" class="sr-only" id="aspectRatio4" name="aspectRatio" value="1">1:1
                      </label>
                      <label class="btn btn-primary" data-toggle="none_tooltip" data-animation="false" data-original-title="aspectRatio: 2 / 3">
                        <input type="radio" class="sr-only" id="aspectRatio5" name="aspectRatio" value="0.6666666666666666">2:3
                      </label>
                      <label class="btn btn-primary active free" data-toggle="none_tooltip" data-animation="false" data-original-title="aspectRatio: NaN">
                        <input type="radio" class="sr-only" id="aspectRatio6" name="aspectRatio" value="NaN">Free
                      </label>
                    </div>
                </div>
            </div>

            <input type="hidden" id="dataX" name="x">
            <input type="hidden" id="dataY" name="y">
            <input type="hidden" id="dataWidth" name="width">
            <input type="hidden" id="dataHeight" name="height">
            <input type="hidden" id="dataRotate" name="rotate">
            <input type="hidden" id="dataScaleX" name="scaleX">
            <input type="hidden" id="dataScaleY" name="scaleY">';


        // JPG QUALITY
        $fragment = new rex_fragment();
        $fragment->setVar('elements', array(array(
            'class' => 'rex-range-input-group',
            'left' => '<input id="rex-js-rating-source-jpg-quality" type="range" min="0" max="100" step="1" value="' . $jpgQuality . '" />',
            'field' => '<input class="form-control" id="rex-js-rating-text-jpg-quality" type="text" name="jpg_quality" value="' . $jpgQuality . '" />',
        )), false);
        $jpgQualityElement = $fragment->parse('core/form/input_group.php');

        // PNG COMPRESSION
        $fragment = new rex_fragment();
        $fragment->setVar('elements', array(array(
            'class' => 'rex-range-input-group',
            'left' => '<input id="rex-js-rating-source-png-compression" type="range" min="0" max="9" step="1" value="' . $pngCompression . '" />',
            'field' => '<input class="form-control" id="rex-js-rating-text-png-compression" type="text" name="png_compression" value="' . $pngCompression . '" />',
        )), false);
        $pngCompressionElement = $fragment->parse('core/form/input_group.php');

        // FORM ELEMENTS
        // SAVE OPTION
        $fragment = new rex_fragment();
        $fragment->setVar('elements', array(
            array(
                'label' => '<label for="rex-mediapool-title">' . rex_i18n::msg('cropper_save_options') . '</label>',
                'field' => '<label class="checkbox-inline checbox-switch switch-primary">
                                <input type="checkbox" name="create_new_image" id="create_new_image" data-toggle="collapse" data-target="#new_file_name" checked />
                                <span></span>' . rex_i18n::msg('cropper_img_save_info') . '
                            </label>',
            ),
        ), false);
        $panel .= $fragment->parse('core/form/form.php');

        // FILENAME
        $fragment = new rex_fragment();
        $fragment->setVar('elements', array(
            array(
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
            ),
        ), false);
        $panel .= "<div id=\"new_file_name\" class=\"collapse in\">" . $fragment->parse('core/form/form.php') . "</div>";

        // FORM ELEMENTS
        // JPG QUALITY
        $fragment = new rex_fragment();
        $fragment->setVar('elements', array(
            array(
                'label' => '<label for="rex-mediapool-title">' . rex_i18n::msg('cropper_jpg_quality') . '</label>',
                'field' => $jpgQualityElement,
            ),
        ), false);
        $panel .= "<div class=\"collapse $jpgIn\">" . $fragment->parse('core/form/form.php') . '</div>';

        // FORM ELEMENTS
        // PNG COMPRESSION
        $fragment = new rex_fragment();
        $fragment->setVar('elements', array(
            array(
                'label' => '<label for="rex-mediapool-title">' . rex_i18n::msg('cropper_png_compression') . '</label>',
                'field' => $pngCompressionElement,
            ),
        ), false);
        $panel .= "<div class=\"collapse $pngIn\">" . $fragment->parse('core/form/form.php') . '</div>';

        // FORM FOOTER
        // BUTTONS
        $fragment = new rex_fragment();
        $fragment->setVar('elements', array(
            array('field' => '<button class="btn btn-apply rex-form-aligned" type="submit" value="1" name="btn_save">' . rex_i18n::msg('form_save') . '</button>'),
            array('field' => '<button class="btn btn-abort" type="submit" value="1" name="btn_abort">' . rex_i18n::msg('form_abort') . '</button>'),
        ), false);
        $buttons = $fragment->parse('core/form/submit.php');

        // FINAL BODY CREATION
        $body = '
        <form action="' . rex_url::currentBackendPage() . '" method="post" enctype="multipart/form-data" data-pjax="false">
            ' . $csrf->getHiddenField() . '
            <input type="hidden" name="file_id" value="' . rex_request::request('file_id', 'integer') . '" />
            <input type="hidden" name="media_name" value="' . $mediaName . '" />
            <input type="hidden" name="rex_file_category" value="' . rex_request::request('rex_file_category', 'integer') . '" />
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
