<?php
/**
 * Author: Joachim Doerr
 * Date: 2019-02-15
 * Time: 12:04
 */

use Cropper\CropperExecutor;
use Cropper\CroppingException;

const POOL_MEDIA = 'mediapool/media';

$csrf = rex_csrf_token::factory('mediapool_structure');

$allowedExtensions = array('jpg' => array('jpg', 'jpeg'), 'png' => array('png'), 'gif' => array('gif'));
$mediaName = rex_request::request('media_name', 'string', null);
$urlParameter = array('file_id' => rex_request::request('file_id', 'integer'), 'rex_file_category' => rex_request::request('rex_file_category', 'integer'));

$body = '';
$title = '';
$class = 'edit';

$back = '<a class="cropper_back_to_media" href="' . rex_url::backendPage(POOL_MEDIA, $urlParameter, false) . '">' . rex_i18n::msg('cropper_back_to_media') . '</a>';

if (!rex::getUser()->hasPerm('cropper[]')) {
    rex_response::sendRedirect(rex_url::backendPage(POOL_MEDIA, $urlParameter, false));
}

try {
    if (rex_request::request('btn_save', 'integer', 0) === 1) {

        if (!$csrf->isValid()) {
            throw new CroppingException('EXCEPTION csrf not valide'); // TODO text!
        }

        $cropperExecutor = new CropperExecutor($_POST);
        $result = $cropperExecutor->crop();

        if ($result['ok']) {
            /** @var rex_media $media */
            $media = ($result['media'] instanceof rex_media) ? $result['media'] : null;
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

    if (rex_request::request('btn_abort', 'integer', 0) === 1) {
        rex_response::sendRedirect(rex_url::backendPage(POOL_MEDIA, $urlParameter, false));
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

        $pngIn = ($media->getExtension() == 'png' && rex::getUser()->isAdmin()) ? ' in' : '';
        $jpgIn = (($media->getExtension() == 'jpg' or $media->getExtension() == 'jpeg') && rex::getUser()->isAdmin()) ? ' in' : '';

        $jpgQuality = rex_request::request('jpg_quality', 'integer', 100);
        $pngCompression = rex_request::request('png_compression', 'integer', 9);
        $newFileExtension = rex_request::request('new_file_extension', 'string', null);
        $newFileName = rex_request::request('new_file_name', 'string', rex_escape(pathinfo($media->getFileName(), PATHINFO_FILENAME)));
        $newMediaPoolCategory = rex_request::request('rex_file_category', 'integer', null);

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

        $mtime = @filemtime(rex_url::media($mediaName)) . uniqid();


        $fragment = new rex_fragment();
        $fragment->setVar('mediaUrl', rex_url::media($mediaName));
        $fragment->setVar('media', $media);
        $fragment->setVar('mtime', $mtime);
        $panel = $fragment->parse('cropper_panel.php');


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
        $checkbox = '
            <label class="checkbox-inline checbox-switch switch-primary">
                <input type="checkbox" name="create_new_image" id="create_new_image" checked />
            <span></span>' . rex_i18n::msg('cropper_img_save_info') . '</label>';
        if (!rex::getUser()->hasPerm('cropper[overwrite]')) :
            $checkbox =  '<div class="nocheckbox"><input type="hidden" name="create_new_image" value="1" />' . rex_i18n::msg('cropper_img_save_info_nochoice') .'</div>';
        endif;
        $fragment = new rex_fragment();
        $fragment->setVar('elements', array(
            array(
                'label' => '<label for="rex-mediapool-title">' . rex_i18n::msg('cropper_save_options') . '</label>',
                'field' => $checkbox,
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


        // Medienpool-Kategorien zur Auswahl
        $rex_file_category = $media->getCategoryId();
        $PERMALL = rex::getUser()->getComplexPerm('media')->hasCategoryPerm(0);
        if (!$PERMALL && !rex::getUser()->getComplexPerm('media')->hasCategoryPerm($rex_file_category))
        {
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
                            <label for="rex-mediapool-category">'. rex_i18n::msg('pool_file_category') .'</label>
                        </dt>
                        <dd>
                            ' . $cats_sel->get() .'
                        </dd>
                    </dl>';

        $panel .= "<div id=\"new_file_name\" class=\"collapse in\">" . $fragment->parse('core/form/form.php') . $mediacat_select ."</div>";

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
            <input type="hidden" name="opener_input_field" value="' . rex_request::request('opener_input_field', 'string') . '" />
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
