<?php

$addon = rex_addon::get('cropper');

// Instanzieren des Formulars
$form = rex_config_form::factory('cropper');

// Fieldset 1
//$field = $form->addFieldset($addon->i18n('config_legend1'));

// 1.1 Einfaches Textfeld
$field = $form->addTextAreaField('aspect_ratios', null,['class' => 'form-control']);
$field->setLabel($addon->i18n('cropper_settings_aspect_ratios'));

$field = $form->addSelectField('show_original_ratio');
$field->setLabel($addon->i18n('cropper_settings_original_ratio'));
$select = $field->getSelect();
$select->addOption($addon->i18n('cropper_settings_original_ratio_inactive'), '0');
$select->addOption($addon->i18n('cropper_settings_original_ratio_active'), '1');

$field = $form->addSelectField('show_free_ratio');
$field->setLabel($addon->i18n('cropper_settings_free_ratio'));
$select = $field->getSelect();
$select->addOption($addon->i18n('cropper_settings_free_ratio_inactive'), '0');
$select->addOption($addon->i18n('cropper_settings_free_ratio_active'), '1');

$field = $form->addCheckboxField('hide_edit_in_list');
$field->setLabel($addon->i18n('cropper_settings_show'));
$field->addOption($addon->i18n('cropper_settings_show_edit_in_list'), 1);

$field = $form->addSelectField('toolbar_mode');
$field->setLabel($addon->i18n('cropper_settings_toolbar_mode'));
$select = $field->getSelect();
$select->addOption($addon->i18n('cropper_settings_toolbar_mode_legacy'), 'legacy');
$select->addOption($addon->i18n('cropper_settings_toolbar_mode_default'), 'default');

$field = $form->addCheckboxField('show_info_sidebar_initially');
$field->setLabel($addon->i18n('cropper_settings_sidebar_mode'));
$field->addOption($addon->i18n('cropper_settings_sidebar_initial_open'), 1);

$field = $form->addTextField('stage_max_height');
$field->setLabel($addon->i18n('cropper_settings_stage_max_height'));
$field->setNotice($addon->i18n('cropper_settings_stage_max_height_notice'));

$field = $form->addTextField('default_jpg_quality');
$field->setLabel($addon->i18n('cropper_settings_default_jpg_quality'));
$field->setNotice($addon->i18n('cropper_settings_default_jpg_quality_notice'));

$field = $form->addTextField('default_png_compression');
$field->setLabel($addon->i18n('cropper_settings_default_png_compression'));
$field->setNotice($addon->i18n('cropper_settings_default_png_compression_notice'));

$field = $form->addSelectField('show_compression_settings_in_mediapool');
$field->setLabel($addon->i18n('cropper_settings_compression_mediapool'));
$select = $field->getSelect();
$select->addOption($addon->i18n('cropper_settings_compression_mediapool_inactive'), '0');
$select->addOption($addon->i18n('cropper_settings_compression_mediapool_active'), '1');

// Ausgabe des Formulars
$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', $addon->i18n('cropper_settings'), false);
$fragment->setVar('body', $form->get(), false);
echo $fragment->parse('core/page/section.php');