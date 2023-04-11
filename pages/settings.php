<?php

$addon = rex_addon::get('cropper');

// Instanzieren des Formulars
$form = rex_config_form::factory('cropper');

// Fieldset 1
//$field = $form->addFieldset($addon->i18n('config_legend1'));

// 1.1 Einfaches Textfeld
$field = $form->addTextAreaField('aspect_ratios', null,['class' => 'form-control']);
$field->setLabel($addon->i18n('cropper_settings_aspect_ratios'));


// Ausgabe des Formulars
$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', $addon->i18n('cropper_settings'), false);
$fragment->setVar('body', $form->get(), false);
echo $fragment->parse('core/page/section.php');