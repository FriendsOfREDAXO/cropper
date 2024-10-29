(function() {
    'use strict';

    class YFormMediaCrop {
        constructor(element, options = {}) {
            this.fieldId = element.id;
            this.element = element;
            this.options = {
                cropWidth: options.cropWidth || 1200,
                cropHeight: options.cropHeight || 630,
                previewHeight: options.previewHeight || 500,
                ...options
            };
            
            this.aspectRatio = this.options.cropWidth / this.options.cropHeight;
            this.cropper = null;
            this.originalFile = null;
            this.dragMode = 'move'; // Default drag mode
            
            this.previewImage = document.getElementById(`upload-image-${this.fieldId}`);
            if (!this.previewImage) return;
            
            this.previewContainer = this.previewImage.closest('.upload-preview');
            if (!this.previewContainer) return;
            
            this.init();
        }
        
        init() {
            this.initFileInput();
            this.initCropperControls();
            this.initFormSubmit();
        }
        
        initFileInput() {
            this.element.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (!file) return;
                
                this.originalFile = file;
                this.showPreview(file);
            });
        }
        
        showPreview(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                this.previewImage.src = e.target.result;
                this.previewContainer.style.display = 'block';
                
                if (this.cropper) {
                    this.cropper.destroy();
                }
                
                if (typeof Cropper === 'undefined') return;
                
                this.cropper = new Cropper(this.previewImage, {
                    aspectRatio: this.aspectRatio,
                    viewMode: 1,  // Changed to 1 to allow moving outside
                    autoCropArea: 1,
                    responsive: true,
                    minContainerHeight: this.options.previewHeight,
                    maxContainerHeight: this.options.previewHeight,
                    zoomable: true,
                    rotatable: true,
                    scalable: true,
                    movable: true,   // Enable image movement
                    dragMode: this.dragMode,  // Use current drag mode
                    guides: true,    // Show grid lines
                    center: true,    // Show center indicator
                    highlight: true, // Highlight crop box
                    background: true, // Show grid background
                    toggleDragModeOnDblclick: true, // Toggle between 'crop' and 'move' on double click
                });

                // Add keyboard support for moving
                document.addEventListener('keydown', (e) => {
                    if (!this.cropper) return;
                    
                    switch(e.key) {
                        case 'ArrowLeft':
                            e.preventDefault();
                            this.cropper.move(-1, 0);
                            break;
                        case 'ArrowRight':
                            e.preventDefault();
                            this.cropper.move(1, 0);
                            break;
                        case 'ArrowUp':
                            e.preventDefault();
                            this.cropper.move(0, -1);
                            break;
                        case 'ArrowDown':
                            e.preventDefault();
                            this.cropper.move(0, 1);
                            break;
                    }
                });
            };
            reader.readAsDataURL(file);
        }
        
        initCropperControls() {
            this.previewContainer.querySelectorAll('[data-action]').forEach(control => {
                control.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (!this.cropper) return;
                    
                    const action = control.dataset.action;
                    switch (action) {
                        case 'zoom-in':
                            this.cropper.zoom(0.1);
                            break;
                        case 'zoom-out':
                            this.cropper.zoom(-0.1);
                            break;
                        case 'rotate-left':
                            this.cropper.rotate(-90);
                            break;
                        case 'rotate-right':
                            this.cropper.rotate(90);
                            break;
                        case 'reset':
                            this.cropper.reset();
                            break;
                        case 'toggle-drag':
                            this.dragMode = this.dragMode === 'crop' ? 'move' : 'crop';
                            this.cropper.setDragMode(this.dragMode);
                            break;
                    }
                });
            });
        }
        
        initFormSubmit() {
            const form = this.element.closest('form');
            
            form.addEventListener('submit', (e) => {
                if (!this.cropper || !this.originalFile) return;
                
                e.preventDefault();
                
                const canvas = this.cropper.getCroppedCanvas({
                    width: this.options.cropWidth,
                    height: this.options.cropHeight,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high'
                });
                
                canvas.toBlob((blob) => {
                    const croppedFile = new File([blob], this.originalFile.name, {
                        type: 'image/jpeg',
                        lastModified: new Date().getTime()
                    });
                    
                    const container = new DataTransfer();
                    container.items.add(croppedFile);
                    this.element.files = container.files;
                    
                    form.submit();
                }, 'image/jpeg', 0.95);
            });
        }
        
        destroy() {
            if (this.cropper) {
                this.cropper.destroy();
            }
            document.removeEventListener('keydown', this.handleKeydown);
        }
    }

    function initMediaCrops() {
        document.querySelectorAll('.form-group input[type="file"]').forEach(input => {
            const container = input.closest('.form-group');
            
            const options = {
                cropWidth: container.dataset.cropWidth,
                cropHeight: container.dataset.cropHeight,
                previewHeight: container.dataset.previewHeight
            };
            
            new YFormMediaCrop(input, options);
        });
    }

    // Detect environment and initialize accordingly
    if (typeof rex !== 'undefined') {
        // Backend (REDAXO)
        if (typeof jQuery !== 'undefined') {
            jQuery(document).on('rex:ready', initMediaCrops);
        }
    } else {
        // Frontend
        document.addEventListener('DOMContentLoaded', initMediaCrops);
    }

})();
