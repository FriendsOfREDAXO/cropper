<?php
/**
 * @var rex_yform_value_media_crop $this
 * @psalm-scope-this rex_yform_value_media_crop
 */

$cropWidth = (int) ($this->getElement('crop_width') ?: 1200);
$cropHeight = (int) ($this->getElement('crop_height') ?: 630);
$previewHeight = (int) ($this->getElement('preview_height') ?: 500);
$previewWidth = $this->getElement('preview_width') ?: '100%';
$previewStyle = $this->getElement('preview_style') ?: '';
$required = (bool) $this->getElement('required');
$notice = (string) $this->getElement('notice');

if (is_numeric($previewWidth)) {
    $previewWidth .= 'px';
}

$fieldId = $this->getFieldId();
$fieldName = $this->getFieldName();
$value = (string) $this->getValue();
$stageStyle = sprintf(
    'width: %s; min-height: %dpx; height: %dpx; overflow: hidden; position: relative; %s',
    $previewWidth,
    $previewHeight,
    $previewHeight,
    $previewStyle,
);
?>

<div
    class="<?= trim('form-group cropper-media-crop ' . $this->getHTMLClass() . ' ' . $this->getWarningClass()) ?>"
    id="<?= $this->getHTMLId() ?>"
    data-crop-width="<?= $cropWidth ?>"
    data-crop-height="<?= $cropHeight ?>"
    data-preview-height="<?= $previewHeight ?>"
    data-preview-width="<?= htmlspecialchars($previewWidth) ?>"
>
    <label class="control-label" for="<?= $fieldId ?>"><?= $this->getLabel() ?></label>

    <input
        type="file"
        class="form-control"
        id="<?= $fieldId ?>"
        name="file_<?= $fieldId ?>"
        accept="image/*"
        <?= $required ? 'required' : '' ?>
    >

    <?php if ('' !== $notice): ?>
        <span class="help-block"><?= htmlspecialchars($notice) ?></span>
    <?php endif; ?>

    <input type="hidden" name="<?= $fieldName ?>" value="<?= htmlspecialchars($value) ?>">

    <?php if ('' !== $value): ?>
        <?php $previewUrl = rex_media_manager::getUrl('rex_media_medium', $value); ?>
        <div class="cropper-current-image" style="margin-top: 15px;">
            <img src="<?= $previewUrl ?>" alt="" style="max-width: 240px; height: auto; display: block;">
            <?php if (!$required): ?>
                <label class="checkbox" style="margin-top: 10px; display: inline-flex; gap: 8px; align-items: center;">
                    <input type="checkbox" name="<?= md5($this->getFieldName('delete')) ?>" value="1">
                    <span><?= rex_i18n::msg('yform_media_crop_delete_image') ?></span>
                </label>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="upload-preview" hidden style="margin-top: 15px; width: 100%;">
        <div class="cropper-stage" style="<?= $stageStyle ?>">
            <img id="upload-image-<?= $fieldId ?>" src="" alt="" style="display: block; max-width: 100%; height: auto;">
        </div>

        <div class="cropper-controls" style="margin-top: 12px; display: flex; flex-wrap: wrap; gap: 8px; justify-content: center;">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-default" data-action="zoom-in" title="Vergrößern">
                    <span class="fa fa-search-plus"></span>
                </button>
                <button type="button" class="btn btn-default" data-action="zoom-out" title="Verkleinern">
                    <span class="fa fa-search-minus"></span>
                </button>
            </div>

            <div class="btn-group" role="group">
                <button type="button" class="btn btn-default" data-action="rotate-left" title="Nach links drehen">
                    <span class="fa fa-rotate-left"></span>
                </button>
                <button type="button" class="btn btn-default" data-action="rotate-right" title="Nach rechts drehen">
                    <span class="fa fa-rotate-right"></span>
                </button>
            </div>

            <div class="btn-group" role="group">
                <button type="button" class="btn btn-default" data-action="toggle-drag" title="Zwischen Verschieben und Zuschneiden wechseln">
                    <span class="fa fa-arrows"></span>
                </button>
                <button type="button" class="btn btn-default" data-action="reset" title="Zurücksetzen">
                    <span class="fa fa-refresh"></span>
                </button>
            </div>
        </div>
    </div>
</div>
