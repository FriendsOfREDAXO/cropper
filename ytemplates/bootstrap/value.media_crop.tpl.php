<?php
/**
 * @var rex_yform_value_media_crop $this
 * @psalm-scope-this rex_yform_value_media_crop
 */

$crop_width = $this->getElement('crop_width') ?: 1200;
$crop_height = $this->getElement('crop_height') ?: 630;
$aspectRatio = $crop_width / $crop_height;

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
                        <input type="checkbox" name="<?= $field_name ?>_delete" value="1">
                        Bild löschen
                    </label>
                </div>
            </div>
        <?php endif; ?>

        <!-- Preview for new upload -->
        <div class="upload-preview" style="display: none;">
            <img id="upload-image-<?= $field_id ?>" src="" style="max-width: 100%;">
        </div>
    </div>
</div>

<script nonce="<?= rex_response::getNonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
    const fieldId = '<?= $field_id ?>';
    const cropWidth = <?= $crop_width ?>;
    const cropHeight = <?= $crop_height ?>;
    const aspectRatio = <?= $aspectRatio ?>;
    
    let cropper = null;
    let originalFile = null;

    const fileInput = document.getElementById(fieldId);
    const previewImage = document.getElementById('upload-image-' + fieldId);
    const previewContainer = previewImage.parentElement;

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
                responsive: true
            });
        };
        reader.readAsDataURL(file);
    });

    // Handle form submit
    const form = fileInput.closest('form');
    form.addEventListener('submit', function(e) {
        if (!cropper || !originalFile) return;

        e.preventDefault();

        // Get cropped canvas
        const canvas = cropper.getCroppedCanvas({
            width: cropWidth,
            height: cropHeight
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
        }, 'image/jpeg');
    });
});
</script>