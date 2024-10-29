<?php
/**
 * @var rex_yform_value_media_crop $this
 * @psalm-scope-this rex_yform_value_media_crop
 */

$crop_width = $this->getElement('crop_width') ?: 1200;
$crop_height = $this->getElement('crop_height') ?: 630;

// Get style options with defaults
$preview_width = $this->getElement('preview_width') ?: '100%';
$preview_height = $this->getElement('preview_height') ?: '500';
$preview_style = $this->getElement('preview_style') ?: '';

// Convert numeric width to pixels
if (is_numeric($preview_width)) {
    $preview_width = $preview_width . 'px';
}

// Build style attributes
$container_style = sprintf(
    'width: %s; height: %spx; %s',
    $preview_width,
    $preview_height,
    $preview_style
);

$field_id = $this->getFieldId();
$field_name = $this->getFieldName();
$value = $this->getValue();
?>

<div class="<?= trim('form-group ' . $this->getHTMLClass() . ' ' . $this->getWarningClass()) ?>" 
     id="<?= $this->getHTMLId() ?>"
     data-crop-width="<?= $crop_width ?>"
     data-crop-height="<?= $crop_height ?>"
     data-preview-height="<?= $preview_height ?>">
    
    <label class="control-label" for="<?= $field_id ?>"><?= $this->getLabel() ?></label>

    <!-- File Upload Field -->
    <input type="file" 
           class="form-control" 
           id="<?= $field_id ?>" 
           name="file_<?= $field_id ?>"
           accept="image/*">

    <!-- Hidden field for current value -->
    <input type="hidden" name="<?= $field_name ?>" value="<?= htmlspecialchars($value) ?>">

    <!-- Preview Section -->
    <div id="preview-container-<?= $field_id ?>" style="margin-top: 15px;">
        <?php if ($value): ?>
            <!-- Current Image -->
            <div class="current-image">
                <?php
                    $mediaUrl = rex_url::media($value);
                    $previewUrl = rex_media_manager::getUrl('rex_media_medium', $value);
                ?>
                <img src="<?= $previewUrl ?>" alt="Current Image" style="max-width: 200px;">
                <div class="checkbox" style="margin-top: 10px;">
                    <label>
                        <input type="checkbox" name="<?= md5($this->getFieldName('delete')) ?>" value="1">
                        Bild löschen
                    </label>
                </div>
            </div>
        <?php endif; ?>

        <!-- Preview for new upload with correct dimensions -->
        <div class="upload-preview" style="display: none;">
            <div style="<?= $container_style ?>">
                <img id="upload-image-<?= $field_id ?>" src="" style="max-width: 100%; max-height: 100%; object-fit: contain;">
            </div>
            
            <div class="cropper-controls" style="margin-top: 10px; text-align: center;">
    <div class="btn-group" style="margin-right: 10px;">
        <button type="button" class="btn btn-default" data-action="zoom-in" title="Vergrößern">
            <span class="fa fa-search-plus"></span>
        </button>
        <button type="button" class="btn btn-default" data-action="zoom-out" title="Verkleinern">
            <span class="fa fa-search-minus"></span>
        </button>
    </div>
    
    <div class="btn-group" style="margin-right: 10px;">
        <button type="button" class="btn btn-default" data-action="rotate-left" title="Nach links drehen">
            <span class="fa fa-rotate-left"></span>
        </button>
        <button type="button" class="btn btn-default" data-action="rotate-right" title="Nach rechts drehen">
            <span class="fa fa-rotate-right"></span>
        </button>
    </div>

    <div class="btn-group" style="margin-right: 10px;">
        <button type="button" class="btn btn-default" data-action="toggle-drag" title="Zwischen Verschieben und Zuschneiden wechseln">
            <span class="fa fa-arrows"></span>
        </button>
    </div>

    <div class="btn-group">
        <button type="button" class="btn btn-default" data-action="reset" title="Zurücksetzen">
            <span class="fa fa-refresh"></span>
        </button>
    </div>
</div>

        </div>
    </div>
</div>
