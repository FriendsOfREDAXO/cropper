<?php
/**
 * @var rex_yform_value_media_crop $this
 * @psalm-scope-this rex_yform_value_media_crop
 */

$crop_width = $this->getElement('crop_width') ?: 1200;
$crop_height = $this->getElement('crop_height') ?: 630;
$aspectRatio = $crop_width / $crop_height;

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

<div class="<?= trim('form-group ' . $this->getHTMLClass() . ' ' . $this->getWarningClass()) ?>" id="<?= $this->getHTMLId() ?>">
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
            
            <!-- Control buttons -->
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

                <div class="btn-group">
                    <button type="button" class="btn btn-default" data-action="reset" title="Zurücksetzen">
                        <span class="fa fa-refresh"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= rex_response::getNonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
    const fieldId = '<?= $field_id ?>';
    const aspectRatio = <?= $aspectRatio ?>;
    const previewHeight = <?= (int)$preview_height ?>;
    let cropper = null;
    let originalFile = null;

    const fileInput = document.getElementById(fieldId);
    const previewImage = document.getElementById('upload-image-' + fieldId);
    const previewContainer = previewImage.parentElement.parentElement;
    
    // Initialize cropper controls
    function initCropperControls() {
        const controls = previewContainer.querySelectorAll('[data-action]');
        controls.forEach(control => {
            control.addEventListener('click', function(e) {
                e.preventDefault();
                if (!cropper) return;

                const action = this.dataset.action;
                switch (action) {
                    case 'zoom-in':
                        cropper.zoom(0.1);
                        break;
                    case 'zoom-out':
                        cropper.zoom(-0.1);
                        break;
                    case 'rotate-left':
                        cropper.rotate(-90);
                        break;
                    case 'rotate-right':
                        cropper.rotate(90);
                        break;
                    case 'reset':
                        cropper.reset();
                        break;
                }
            });
        });
    }

    // Event: File Selected
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Store original file
        originalFile = file;

        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            previewContainer.style.display = 'block';

            // Initialize or reinitialize cropper
            if (cropper) {
                cropper.destroy();
            }

            cropper = new Cropper(previewImage, {
                aspectRatio: aspectRatio,
                viewMode: 2,
                autoCropArea: 1,
                responsive: true,
                minContainerHeight: previewHeight,
                maxContainerHeight: previewHeight,
                // Enable additional features
                zoomable: true,
                rotatable: true,
                scalable: true
            });
        };
        reader.readAsDataURL(file);
    });

    // Initialize controls
    initCropperControls();

    // Handle form submit
    const form = fileInput.closest('form');
    form.addEventListener('submit', function(e) {
        if (!cropper || !originalFile) return;

        e.preventDefault();

        // Get cropped canvas
        const canvas = cropper.getCroppedCanvas({
            width: <?= $crop_width ?>,
            height: <?= $crop_height ?>,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        });

        // Convert canvas to blob
        canvas.toBlob(function(blob) {
            // Create new file with original name
            const croppedFile = new File([blob], originalFile.name, {
                type: 'image/jpeg',
                lastModified: new Date().getTime()
            });

            // Replace file in input
            const container = new DataTransfer();
            container.items.add(croppedFile);
            fileInput.files = container.files;

            // Continue form submission
            form.submit();
        }, 'image/jpeg', 0.95);
    });
});
</script>
