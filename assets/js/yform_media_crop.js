(function () {
    'use strict';

    function resolveCropperConstructor() {
        if (typeof window === 'undefined' || typeof window.Cropper === 'undefined') {
            return null;
        }

        if (typeof window.Cropper === 'function') {
            return window.Cropper;
        }

        if (window.Cropper && typeof window.Cropper.default === 'function') {
            return window.Cropper.default;
        }

        if (window.Cropper && typeof window.Cropper.Cropper === 'function') {
            return window.Cropper.Cropper;
        }

        return null;
    }

    function readNumeric(value, fallback) {
        const parsed = Number.parseInt(String(value), 10);
        return Number.isFinite(parsed) ? parsed : fallback;
    }

    function roundValue(value) {
        return Number.isFinite(value) ? Math.round(value) : 0;
    }

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    class YFormMediaCrop {
        constructor(element, options = {}) {
            this.fieldId = element.id;
            this.element = element;
            this.options = {
                cropWidth: readNumeric(options.cropWidth, 1200),
                cropHeight: readNumeric(options.cropHeight, 630),
                previewHeight: readNumeric(options.previewHeight, 500),
            };

            this.aspectRatio = this.options.cropWidth / this.options.cropHeight;
            this.CropperConstructor = resolveCropperConstructor();
            this.cropper = null;
            this.cropperImage = null;
            this.cropperCanvas = null;
            this.cropperSelection = null;
            this.originalFile = null;
            this.dragMode = 'crop';
            this.skipNextSubmit = false;
            this.isSaving = false;

            this.previewImage = document.getElementById(`upload-image-${this.fieldId}`);
            this.previewContainer = this.previewImage ? this.previewImage.closest('.upload-preview') : null;
            this.stage = this.previewImage ? this.previewImage.closest('.cropper-stage') : null;
            this.form = this.element.closest('form');

            if (!this.previewImage || !this.previewContainer || !this.stage) {
                return;
            }

            this.stage.style.minHeight = `${this.options.previewHeight}px`;
            this.stage.style.height = `${this.options.previewHeight}px`;

            this.init();
        }

        init() {
            this.initFileInput();
            this.initCropperControls();
            this.initFormSubmit();
        }

        initFileInput() {
            this.element.addEventListener('change', (event) => {
                const input = event.currentTarget;
                const file = input instanceof HTMLInputElement ? input.files?.[0] : null;

                if (!file) {
                    return;
                }

                this.originalFile = file;
                this.showPreview(file);
            });
        }

        showPreview(file) {
            if (!(file instanceof File)) {
                return;
            }

            const reader = new FileReader();
            reader.onload = (event) => {
                const src = event.target && typeof event.target.result === 'string' ? event.target.result : '';

                if ('' === src) {
                    return;
                }

                this.destroyCropper();
                this.previewContainer.hidden = false;
                this.previewImage.onload = () => {
                    this.initCropperInstance();
                };
                this.previewImage.src = src;

                if (this.previewImage.complete && this.previewImage.naturalWidth > 0) {
                    this.initCropperInstance();
                }
            };

            reader.readAsDataURL(file);
        }

        initCropperInstance() {
            if (!this.CropperConstructor || !this.previewImage.complete || this.previewImage.naturalWidth <= 0) {
                return;
            }

            this.destroyCropper();

            this.cropper = new this.CropperConstructor(this.previewImage, {
                container: this.stage,
            });

            this.cropperImage = this.cropper.getCropperImage();
            this.cropperCanvas = this.cropper.getCropperCanvas();
            this.cropperSelection = this.cropper.getCropperSelection();

            if (!this.cropperImage || !this.cropperCanvas || !this.cropperSelection) {
                return;
            }

            this.cropperCanvas.style.display = 'block';
            this.cropperCanvas.style.width = '100%';
            this.cropperCanvas.style.height = `${this.options.previewHeight}px`;
            this.cropperCanvas.style.minHeight = `${this.options.previewHeight}px`;

            this.cropperImage.$ready(() => {
                this.cropperImage.style.width = '100%';
                this.cropperImage.style.height = '100%';
                this.cropperSelection.aspectRatio = this.aspectRatio;
                this.cropperSelection.initialAspectRatio = this.aspectRatio;
                this.cropperSelection.movable = true;
                this.cropperSelection.resizable = true;
                this.cropperSelection.zoomable = true;
                this.cropperSelection.keyboard = true;
                this.cropperSelection.hidden = false;
                this.cropperSelection.$reset();
                this.cropperCanvas.$setAction('select');
                this.syncPreviewState();
            });
        }

        initCropperControls() {
            this.previewContainer.querySelectorAll('[data-action]').forEach((control) => {
                control.addEventListener('click', (event) => {
                    event.preventDefault();

                    if (!this.cropperImage || !this.cropperCanvas || !this.cropperSelection) {
                        return;
                    }

                    const action = String(control.dataset.action || '');

                    switch (action) {
                        case 'zoom-in':
                            this.cropperImage.$zoom(0.1);
                            break;
                        case 'zoom-out':
                            this.cropperImage.$zoom(-0.1);
                            break;
                        case 'rotate-left':
                            this.cropperImage.$rotate('-90deg');
                            break;
                        case 'rotate-right':
                            this.cropperImage.$rotate('90deg');
                            break;
                        case 'toggle-drag':
                            this.dragMode = this.dragMode === 'crop' ? 'move' : 'crop';
                            this.cropperSelection.hidden = false;
                            this.cropperSelection.movable = this.dragMode === 'crop';
                            this.cropperSelection.resizable = this.dragMode === 'crop';
                            this.cropperCanvas.$setAction(this.dragMode === 'crop' ? 'select' : 'move');
                            break;
                        case 'reset':
                            this.cropperImage.$resetTransform();
                            this.cropperSelection.aspectRatio = this.aspectRatio;
                            this.cropperSelection.initialAspectRatio = this.aspectRatio;
                            this.cropperSelection.hidden = false;
                            this.cropperSelection.$reset();
                            this.cropperCanvas.$setAction('select');
                            this.dragMode = 'crop';
                            break;
                    }

                    this.syncPreviewState();
                });
            });
        }

        initFormSubmit() {
            if (!this.form) {
                return;
            }

            this.form.addEventListener('submit', async (event) => {
                if (this.isSaving) {
                    event.preventDefault();
                    return;
                }

                if (this.skipNextSubmit || !this.cropperSelection || !this.originalFile) {
                    this.skipNextSubmit = false;
                    return;
                }

                event.preventDefault();
                this.setSavingState(true);

                const submitter = event.submitter instanceof HTMLElement ? event.submitter : null;
                const outputMimeType = this.originalFile.type === 'image/png' ? 'image/png' : 'image/jpeg';
                const outputFilename = outputMimeType === 'image/png'
                    ? this.originalFile.name
                    : this.originalFile.name.replace(/\.[^.]*$/, '') + '.jpg';

                try {
                    const canvas = await this.cropperSelection.$toCanvas({
                        width: this.options.cropWidth,
                        height: this.options.cropHeight,
                    });

                    canvas.toBlob((blob) => {
                        if (!blob) {
                            this.skipNextSubmit = true;
                            this.resubmitForm(submitter);
                            return;
                        }

                        const croppedFile = new File([blob], outputFilename, {
                            type: outputMimeType,
                            lastModified: Date.now(),
                        });

                        const transfer = new DataTransfer();
                        transfer.items.add(croppedFile);
                        this.element.files = transfer.files;

                        this.skipNextSubmit = true;
                        this.resubmitForm(submitter);
                    }, outputMimeType, 0.95);
                } catch (error) {
                    this.skipNextSubmit = true;
                    this.resubmitForm(submitter);
                }
            });
        }

        setSavingState(state) {
            this.isSaving = state;

            if (!this.form) {
                return;
            }

            this.form.classList.toggle('cropper-is-saving', state);

            let overlay = this.form.querySelector('.cropper-save-overlay');
            if (!overlay && state) {
                overlay = document.createElement('div');
                overlay.className = 'cropper-save-overlay';
                overlay.innerHTML = '<span class="fa fa-spinner fa-spin" aria-hidden="true"></span><span>Bild wird gespeichert...</span>';
                this.form.appendChild(overlay);
            }

            if (overlay instanceof HTMLElement) {
                overlay.hidden = !state;
            }

            this.form.querySelectorAll('button, input, select, textarea').forEach((field) => {
                if (field instanceof HTMLInputElement && field.type === 'hidden') {
                    return;
                }

                if (state) {
                    field.setAttribute('disabled', 'disabled');
                }
            });
        }

        resubmitForm(submitter) {
            if (!this.form) {
                return;
            }

            if (typeof this.form.requestSubmit === 'function') {
                if (submitter instanceof HTMLElement && !submitter.hasAttribute('disabled')) {
                    this.form.requestSubmit(submitter);
                    return;
                }

                this.form.requestSubmit();
                return;
            }

            HTMLFormElement.prototype.submit.call(this.form);
        }

        syncPreviewState() {
            if (!this.cropperSelection) {
                return;
            }

            const hasSelection = !this.cropperSelection.hidden
                && this.cropperSelection.width > 0
                && this.cropperSelection.height > 0;

            if (hasSelection) {
                this.cropperSelection.$change(
                    clamp(this.cropperSelection.x, 0, Math.max(0, this.cropperCanvas.offsetWidth - this.cropperSelection.width)),
                    clamp(this.cropperSelection.y, 0, Math.max(0, this.cropperCanvas.offsetHeight - this.cropperSelection.height)),
                    this.cropperSelection.width,
                    this.cropperSelection.height,
                    this.cropperSelection.aspectRatio,
                    true,
                );
            }
        }

        destroyCropper() {
            if (this.cropper) {
                this.cropper.destroy();
            }

            this.cropper = null;
            this.cropperImage = null;
            this.cropperCanvas = null;
            this.cropperSelection = null;
        }

        destroy() {
            this.destroyCropper();
        }
    }

    function initMediaCrops() {
        document.querySelectorAll('.form-group[data-crop-width] input[type="file"]').forEach((input) => {
            const container = input.closest('.form-group');

            if (!container) {
                return;
            }

            const options = {
                cropWidth: container.dataset.cropWidth || '1200',
                cropHeight: container.dataset.cropHeight || '630',
                previewHeight: container.dataset.previewHeight || '500',
            };

            new YFormMediaCrop(input, options);
        });
    }

    if (typeof rex !== 'undefined') {
        if (typeof jQuery !== 'undefined') {
            jQuery(document).on('rex:ready', initMediaCrops);
        }
    } else {
        document.addEventListener('DOMContentLoaded', initMediaCrops);
    }
}());
