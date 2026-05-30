$(document).on('rex:ready', function (event, container) {
    const $container = container || $(document);
    const imageElement = $container.find('#cropper_image').get(0);
    const CropperConstructor = resolveCropperConstructor();

    if (!imageElement || !CropperConstructor) {
        return;
    }

    initLinkedInputs($container);
    initSaveToggle($container);
    initSaveGuard($container);
    initTooltips($container);
    new BackendCropper(imageElement, $container.get(0), CropperConstructor);
});

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

function initTooltips(container) {
    container.find('[data-toggle="tooltip"]').tooltip();
}

function initLinkedInputs(container) {
    [
        ['#rex-js-rating-text-jpg-quality', '#rex-js-rating-source-jpg-quality'],
        ['#rex-js-rating-text-png-compression', '#rex-js-rating-source-png-compression'],
        ['#rex-js-rating-text-webp-quality', '#rex-js-rating-source-webp-quality'],
    ].forEach(function (selectors) {
        const textInput = container.find(selectors[0]);
        const rangeInput = container.find(selectors[1]);

        if (!textInput.length || !rangeInput.length) {
            return;
        }

        textInput.on('input change', function () {
            rangeInput.val(this.value);
        });

        rangeInput.on('input change', function () {
            textInput.val(this.value);
            textInput.trigger('change');
        });
    });
}

function initSaveToggle(container) {
    const createNewImage = container.find('#create_new_image');
    const newFileName = container.find('#new_file_name');

    if (!createNewImage.length || !newFileName.length) {
        return;
    }

    createNewImage
        .on('change', function () {
            if ($(this).data('disable') !== 1) {
                newFileName.collapse('toggle');
            }
        })
        .on('click', function () {
            return $(this).data('disable') !== 1;
        });
}

function initSaveGuard(container) {
    container.find('form').each(function () {
        const form = this;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (!form.querySelector('button[name="btn_save"]')) {
            return;
        }

        if (form.dataset.cropperSaveGuard === '1') {
            return;
        }

        form.dataset.cropperSaveGuard = '1';

        form.addEventListener('submit', (event) => {
            const submitter = event.submitter instanceof HTMLElement ? event.submitter : null;
            const submitterName = submitter ? submitter.getAttribute('name') : '';

            if (submitterName === 'btn_abort') {
                return;
            }

            if (form.dataset.cropperSaving === '1') {
                event.preventDefault();
                return;
            }

            form.dataset.cropperSaving = '1';
            form.classList.add('cropper-is-saving');

            if (!form.querySelector('.cropper-save-overlay')) {
                const overlay = document.createElement('div');
                overlay.className = 'cropper-save-overlay';
                overlay.setAttribute('role', 'status');
                overlay.setAttribute('aria-live', 'polite');
                overlay.innerHTML = `<span class="fa fa-spinner fa-spin" aria-hidden="true"></span><span>${getSavingMessage()}</span>`;
                form.appendChild(overlay);
            }
        });
    });
}

function getSavingMessage() {
    const i18n = (typeof window !== 'undefined' && window.rex && window.rex.cropperI18n)
        ? window.rex.cropperI18n
        : (typeof window !== 'undefined' ? window.cropperI18n : null);

    if (
        i18n
        && typeof i18n.savingMessage === 'string'
        && i18n.savingMessage !== ''
    ) {
        return i18n.savingMessage;
    }

    return 'Saving image...';
}

function readAspectRatio(value) {
    const ratio = Number.parseFloat(value);
    return Number.isFinite(ratio) ? ratio : undefined;
}

function roundValue(value) {
    return Number.isFinite(value) ? Math.round(value) : 0;
}

function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
}

class BackendCropper {
    constructor(imageElement, containerElement, CropperConstructor) {
        this.container = containerElement;
        this.root = imageElement.closest('#cropper-workspace') || containerElement;
        this.imageElement = imageElement;
        this.CropperConstructor = CropperConstructor;
        this.previewRenderFrame = null;
        this.state = {
            rotation: 0,
            scaleX: 1,
            scaleY: 1,
            wheelZoomEnabled: false,
            dragMode: 'move',
        };

        this.previewImage = this.root.querySelector('#cropper_live_preview');
        this.previewEmpty = this.root.querySelector('#cropper_preview_empty');
        this.stage = this.root.querySelector('.cropper-stage');
        this.selectionOverlay = this.root.querySelector('#cropper-selection-overlay');
        this.selectionGrabHandle = this.root.querySelector('#cropper-selection-grab');
        this.sidebar = this.root.querySelector('#cropper-sidebar');
        this.sidebarToggle = this.root.querySelector('#cropper_sidebar_toggle');
        this.modeBadge = this.root.querySelector('#cropper_mode_badge');
        this.modeHint = this.root.querySelector('#cropper_mode_hint');
        this.sidebarStorageKey = 'cropper.sidebarCollapsed';
        this.sidebarCollapsed = false;
        this.outputFields = {
            imageSize: this.root.querySelector('[data-cropper-output="image-size"]'),
            selectionSize: this.root.querySelector('[data-cropper-output="selection-size"]'),
            selectionPosition: this.root.querySelector('[data-cropper-output="selection-position"]'),
            selectionRatio: this.root.querySelector('[data-cropper-output="selection-ratio"]'),
            transformState: this.root.querySelector('[data-cropper-output="transform-state"]'),
        };

        this.hiddenFields = {
            x: this.container.querySelector('#dataX'),
            y: this.container.querySelector('#dataY'),
            width: this.container.querySelector('#dataWidth'),
            height: this.container.querySelector('#dataHeight'),
            canvasWidth: this.container.querySelector('#dataCanvasWidth'),
            canvasHeight: this.container.querySelector('#dataCanvasHeight'),
            rotate: this.container.querySelector('#dataRotate'),
            scaleX: this.container.querySelector('#dataScaleX'),
            scaleY: this.container.querySelector('#dataScaleY'),
        };

        this.cropper = new this.CropperConstructor(this.imageElement, {
            container: this.imageElement.parentElement,
        });

        this.cropperImage = this.cropper.getCropperImage();
        this.cropperCanvas = this.cropper.getCropperCanvas();
        this.cropperSelection = this.cropper.getCropperSelection();

        if (!this.cropperImage || !this.cropperCanvas || !this.cropperSelection) {
            return;
        }

        this.handleCanvasAction = this.syncHiddenFields.bind(this);
        this.handleWheel = this.preventWheelZoom.bind(this);
        this.handleWindowResize = this.updateStageHeight.bind(this);
        this.handleSelectionGripMove = this.moveSelectionWithGrip.bind(this);
        this.handleSelectionGripEnd = this.stopSelectionGripDrag.bind(this);
        this.handleImageDragMove = this.moveImageWithStageDrag.bind(this);
        this.handleImageDragEnd = this.stopImageStageDrag.bind(this);
        this.selectionGripDrag = null;
        this.imageStageDrag = null;

        this.cropperImage.$ready(() => {
            this.updateStageHeight();
            this.bindControls();
            this.configureSelection();
            this.scheduleInitialFit();
            this.syncHiddenFields();
        });
    }

    bindControls() {
        this.initSidebarToggle();
        window.addEventListener('resize', this.handleWindowResize);
        this.cropperCanvas.addEventListener('action', this.handleCanvasAction);
        this.cropperCanvas.addEventListener('actionend', this.handleCanvasAction);
        this.cropperCanvas.addEventListener('wheel', this.handleWheel, { capture: true });

        this.root.querySelector('.docs-buttons')?.addEventListener('click', (event) => {
            const button = event.target.closest('[data-method]');

            if (!button || button.disabled || button.classList.contains('disabled')) {
                return;
            }

            event.preventDefault();
            this.handleMethod(button.dataset.method, button.dataset.option);
        });

        this.root.querySelector('.docs-toggles')?.addEventListener('change', (event) => {
            const input = event.target;

            if (input instanceof HTMLSelectElement && input.name === 'selectionPreset') {
                const presetValue = Number.parseFloat(input.value);
                if (Number.isFinite(presetValue) && presetValue > 0) {
                    this.setDragMode('crop');
                    this.applySelectionCoverage(presetValue);
                }
                return;
            }

            if (!(input instanceof HTMLInputElement)) {
                return;
            }

            if (input.type === 'radio' && input.name === 'aspectRatio') {
                this.setDragMode('crop');
                this.applyAspectRatio(readAspectRatio(input.value));
                return;
            }

            if (input.type === 'checkbox' && input.name === 'zoomOnWheel') {
                this.state.wheelZoomEnabled = input.checked;
            }
        });

        this.root.querySelector('.cropper-ratio-group')?.addEventListener('click', (event) => {
            const label = event.target.closest('label.btn');

            if (!(label instanceof HTMLElement)) {
                return;
            }

            const input = label.querySelector('input[name="aspectRatio"]');
            if (!(input instanceof HTMLInputElement)) {
                return;
            }

            event.preventDefault();
            this.activateAspectRatioInput(input);
        });

        this.selectionGrabHandle?.addEventListener('pointerdown', (event) => {
            this.startSelectionGripDrag(event);
        });

        this.stage?.addEventListener('pointerdown', (event) => {
            this.startImageStageDrag(event);
        });
    }

    scheduleInitialFit() {
        this.fitImageToCanvas();

        window.requestAnimationFrame(() => {
            this.fitImageToCanvas();
            this.syncHiddenFields();
        });

        window.setTimeout(() => {
            this.fitImageToCanvas();
            this.syncHiddenFields();
        }, 120);
    }

    fitImageToCanvas() {
        if (!this.cropperImage) {
            return;
        }

        if (typeof this.cropperImage.$center === 'function') {
            this.cropperImage.$center('contain');
            return;
        }

        if (typeof this.cropperImage.$resetTransform === 'function') {
            this.cropperImage.$resetTransform();
        }
    }

    initSidebarToggle() {
        if (!(this.sidebar instanceof HTMLElement) || !(this.sidebarToggle instanceof HTMLButtonElement)) {
            return;
        }

        let collapsed = false;

        try {
            collapsed = window.localStorage.getItem(this.sidebarStorageKey) === '1';
        } catch (error) {
            collapsed = false;
        }

        this.setSidebarCollapsed(collapsed, false);

        this.sidebarToggle.addEventListener('click', (event) => {
            event.preventDefault();
            this.setSidebarCollapsed(!this.sidebarCollapsed, true);
        });
    }

    setSidebarCollapsed(collapsed, persist) {
        if (!(this.sidebarToggle instanceof HTMLButtonElement)) {
            return;
        }

        this.sidebarCollapsed = collapsed;
        this.root.classList.toggle('is-sidebar-collapsed', collapsed);
        this.sidebarToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

        const label = collapsed
            ? (this.sidebarToggle.dataset.collapsedLabel || '')
            : (this.sidebarToggle.dataset.expandedLabel || '');

        this.sidebarToggle.setAttribute('title', label);
        this.sidebarToggle.setAttribute('data-original-title', label);

        if (typeof window !== 'undefined' && window.jQuery) {
            window.jQuery(this.sidebarToggle).tooltip('fixTitle');
        }

        if (persist) {
            try {
                window.localStorage.setItem(this.sidebarStorageKey, collapsed ? '1' : '0');
            } catch (error) {
                // Ignore storage errors; the toggle still works for current session.
            }
        }

        this.updateStageHeight();
    }

    activateAspectRatioInput(input) {
        this.root.querySelectorAll('input[name="aspectRatio"]').forEach((field) => {
            if (field instanceof HTMLInputElement) {
                field.checked = field === input;
                field.closest('label.btn')?.classList.toggle('active', field === input);
            }
        });

        this.applyAspectRatio(readAspectRatio(input.value));
    }

    updateStageHeight() {
        if (!(this.root instanceof HTMLElement)) {
            return;
        }

        const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 900;
        const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 1440;
        const mediaWidth = Number.parseFloat(this.root.dataset.mediaWidth || '0');
        const mediaHeight = Number.parseFloat(this.root.dataset.mediaHeight || '0');
        const mediaRatio = mediaWidth > 0 && mediaHeight > 0 ? mediaWidth / mediaHeight : 1;
        const stageWidth = this.stage instanceof HTMLElement ? this.stage.clientWidth : 0;

        // For landscape and square images, keep the stage in the original image ratio.
        if (mediaRatio >= 1 && stageWidth > 0) {
            const exactStageHeight = Math.round(stageWidth / mediaRatio);
            const minStageHeight = viewportWidth < 768 ? 220 : 260;
            const stageHeight = Math.max(minStageHeight, exactStageHeight);

            this.root.style.setProperty('--cropper-stage-height', `${stageHeight}px`);
            this.updateSelectionOverlay();
            return;
        }

        // Portrait images stay viewport-adaptive to avoid a too narrow crop stage.
        let factor = mediaRatio > 1.6 ? 0.5 : 0.62;

        if (viewportWidth < 1080) {
            factor = mediaRatio > 1.6 ? 0.46 : 0.56;
        }

        if (viewportWidth < 768) {
            factor = mediaRatio > 1.6 ? 0.4 : 0.48;
        }

        const stageHeight = clamp(
            Math.round(viewportHeight * factor),
            viewportWidth < 768 ? 280 : 380,
            760,
        );

        this.root.style.setProperty('--cropper-stage-height', `${stageHeight}px`);
        this.updateSelectionOverlay();
    }

    getSelectedAspectRatio() {
        const activeRatio = this.root.querySelector('input[name="aspectRatio"]:checked');
        return activeRatio instanceof HTMLInputElement ? readAspectRatio(activeRatio.value) : undefined;
    }

    configureSelection() {
        this.cropperSelection.movable = true;
        this.cropperSelection.resizable = true;
        this.cropperSelection.keyboard = true;
        this.cropperSelection.zoomable = true;
        this.cropperSelection.aspectRatio = this.getSelectedAspectRatio();
        this.cropperSelection.initialAspectRatio = this.getSelectedAspectRatio();
        this.cropperSelection.hidden = false;
        this.cropperSelection.$reset();
        this.setDragMode('crop');
    }

    ensureSelection() {
        if (!this.cropperSelection) {
            return false;
        }

        this.cropperSelection.hidden = false;

        if (this.cropperSelection.width <= 0 || this.cropperSelection.height <= 0) {
            this.cropperSelection.$reset();
        }

        if (this.cropperSelection.width < 8 || this.cropperSelection.height < 8) {
            const canvasWidth = this.cropperCanvas.offsetWidth;
            const canvasHeight = this.cropperCanvas.offsetHeight;
            const ratio = this.getSelectedAspectRatio();
            let width = Math.max(64, canvasWidth * 0.55);
            let height = Math.max(64, canvasHeight * 0.55);

            if (Number.isFinite(ratio) && ratio > 0) {
                if (width / height > ratio) {
                    width = height * ratio;
                } else {
                    height = width / ratio;
                }
            }

            width = Math.min(width, canvasWidth);
            height = Math.min(height, canvasHeight);

            this.cropperSelection.$change(
                (canvasWidth - width) / 2,
                (canvasHeight - height) / 2,
                width,
                height,
                ratio,
                true,
            );
        }

        this.clampSelectionToCanvas();

        this.cropperSelection.$render();
        return true;
    }

    clampSelectionToCanvas() {
        if (!this.cropperSelection || !this.cropperCanvas) {
            return;
        }

        const canvasWidth = this.cropperCanvas.offsetWidth;
        const canvasHeight = this.cropperCanvas.offsetHeight;

        if (canvasWidth <= 0 || canvasHeight <= 0) {
            return;
        }

        const selectionWidth = Math.min(this.cropperSelection.width, canvasWidth);
        const selectionHeight = Math.min(this.cropperSelection.height, canvasHeight);
        const selectionX = clamp(this.cropperSelection.x, 0, Math.max(0, canvasWidth - selectionWidth));
        const selectionY = clamp(this.cropperSelection.y, 0, Math.max(0, canvasHeight - selectionHeight));

        if (
            selectionX !== this.cropperSelection.x
            || selectionY !== this.cropperSelection.y
            || selectionWidth !== this.cropperSelection.width
            || selectionHeight !== this.cropperSelection.height
        ) {
            this.cropperSelection.$change(
                selectionX,
                selectionY,
                selectionWidth,
                selectionHeight,
                this.cropperSelection.aspectRatio,
                true,
            );
        }
    }

    setDragMode(mode) {
        this.state.dragMode = mode === 'crop' ? 'crop' : 'move';
        this.stopSelectionGripDrag();
        this.stopImageStageDrag();
        this.cropperSelection.movable = this.state.dragMode === 'crop';
        this.cropperSelection.resizable = this.state.dragMode === 'crop';
        this.cropperCanvas.$setAction(this.state.dragMode === 'crop' ? 'select' : 'none');
        this.root.classList.toggle('is-move-mode', this.state.dragMode === 'move');
        this.root.classList.toggle('is-crop-mode', this.state.dragMode === 'crop');

        if (this.modeBadge) {
            this.modeBadge.textContent = this.state.dragMode === 'move'
                ? this.modeBadge.dataset.moveLabel || 'Bildmodus'
                : this.modeBadge.dataset.cropLabel || 'Auswahlmodus';
        }

        if (this.modeHint) {
            this.modeHint.textContent = this.state.dragMode === 'move'
                ? this.modeHint.dataset.moveHint || ''
                : this.modeHint.dataset.cropHint || '';
        }

        this.root.querySelectorAll('[data-method="setDragMode"]').forEach((button) => {
            button.classList.toggle('active', button.dataset.option === this.state.dragMode);
        });

        this.updateSelectionOverlay();
    }

    startImageStageDrag(event) {
        if (this.state.dragMode !== 'move' || !this.cropperImage) {
            return;
        }

        if (!event.isPrimary || event.button !== 0) {
            return;
        }

        const target = event.target;
        if (target instanceof Element && target.closest('button, input, select, textarea, a, label, #cropper-selection-grab')) {
            return;
        }

        event.preventDefault();
        this.imageStageDrag = {
            pointerId: event.pointerId,
            clientX: event.clientX,
            clientY: event.clientY,
        };

        document.addEventListener('pointermove', this.handleImageDragMove);
        document.addEventListener('pointerup', this.handleImageDragEnd);
        document.addEventListener('pointercancel', this.handleImageDragEnd);
    }

    moveImageWithStageDrag(event) {
        if (!this.imageStageDrag || !this.cropperImage) {
            return;
        }

        if (event.pointerId !== this.imageStageDrag.pointerId) {
            return;
        }

        const deltaX = event.clientX - this.imageStageDrag.clientX;
        const deltaY = event.clientY - this.imageStageDrag.clientY;

        if (deltaX === 0 && deltaY === 0) {
            return;
        }

        this.cropperImage.$move(deltaX, deltaY);
        this.imageStageDrag.clientX = event.clientX;
        this.imageStageDrag.clientY = event.clientY;
        this.syncHiddenFields();
    }

    stopImageStageDrag(event) {
        if (!this.imageStageDrag) {
            return;
        }

        if (event && event.pointerId !== this.imageStageDrag.pointerId) {
            return;
        }

        this.imageStageDrag = null;
        document.removeEventListener('pointermove', this.handleImageDragMove);
        document.removeEventListener('pointerup', this.handleImageDragEnd);
        document.removeEventListener('pointercancel', this.handleImageDragEnd);
    }

    startSelectionGripDrag(event) {
        if (this.state.dragMode !== 'move' || !this.ensureSelection()) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        this.selectionGripDrag = {
            startClientX: event.clientX,
            startClientY: event.clientY,
            startX: this.cropperSelection.x,
            startY: this.cropperSelection.y,
        };

        document.addEventListener('pointermove', this.handleSelectionGripMove);
        document.addEventListener('pointerup', this.handleSelectionGripEnd);
        document.addEventListener('pointercancel', this.handleSelectionGripEnd);
    }

    moveSelectionWithGrip(event) {
        if (!this.selectionGripDrag) {
            return;
        }

        const canvasWidth = this.cropperCanvas.offsetWidth;
        const canvasHeight = this.cropperCanvas.offsetHeight;
        const deltaX = event.clientX - this.selectionGripDrag.startClientX;
        const deltaY = event.clientY - this.selectionGripDrag.startClientY;
        const nextX = clamp(this.selectionGripDrag.startX + deltaX, 0, Math.max(0, canvasWidth - this.cropperSelection.width));
        const nextY = clamp(this.selectionGripDrag.startY + deltaY, 0, Math.max(0, canvasHeight - this.cropperSelection.height));

        this.cropperSelection.$change(
            nextX,
            nextY,
            this.cropperSelection.width,
            this.cropperSelection.height,
            this.cropperSelection.aspectRatio,
            true,
        );
        this.syncHiddenFields();
    }

    stopSelectionGripDrag() {
        if (!this.selectionGripDrag) {
            return;
        }

        this.selectionGripDrag = null;
        document.removeEventListener('pointermove', this.handleSelectionGripMove);
        document.removeEventListener('pointerup', this.handleSelectionGripEnd);
        document.removeEventListener('pointercancel', this.handleSelectionGripEnd);
    }

    updateSelectionOverlay() {
        if (!this.selectionOverlay || !this.stage || !this.cropperCanvas || !this.cropperSelection) {
            return;
        }

        const hasSelection = !this.cropperSelection.hidden
            && this.cropperSelection.width > 0
            && this.cropperSelection.height > 0;

        if (!hasSelection || this.state.dragMode !== 'move') {
            this.selectionOverlay.hidden = true;
            return;
        }

        const stageRect = this.stage.getBoundingClientRect();
        const canvasRect = this.cropperCanvas.getBoundingClientRect();
        const canvasOffsetLeft = canvasRect.left - stageRect.left;
        const canvasOffsetTop = canvasRect.top - stageRect.top;

        this.selectionOverlay.hidden = false;
        this.selectionOverlay.style.left = `${canvasOffsetLeft + this.cropperSelection.x}px`;
        this.selectionOverlay.style.top = `${canvasOffsetTop + this.cropperSelection.y}px`;
        this.selectionOverlay.style.width = `${this.cropperSelection.width}px`;
        this.selectionOverlay.style.height = `${this.cropperSelection.height}px`;
    }

    preventWheelZoom(event) {
        if (this.state.wheelZoomEnabled) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
    }

    applyAspectRatio(ratio) {
        if (!this.ensureSelection()) {
            return;
        }

        this.cropperSelection.aspectRatio = ratio;
        this.cropperSelection.initialAspectRatio = ratio;
        this.cropperSelection.$change(
            this.cropperSelection.x,
            this.cropperSelection.y,
            this.cropperSelection.width,
            this.cropperSelection.height,
            ratio,
            true,
        );
        this.syncHiddenFields();
    }

    centerSelection() {
        if (!this.ensureSelection()) {
            return;
        }

        const canvasWidth = this.cropperCanvas.offsetWidth;
        const canvasHeight = this.cropperCanvas.offsetHeight;

        if (canvasWidth <= 0 || canvasHeight <= 0) {
            return;
        }

        const x = (canvasWidth - this.cropperSelection.width) / 2;
        const y = (canvasHeight - this.cropperSelection.height) / 2;
        this.cropperSelection.$change(x, y, this.cropperSelection.width, this.cropperSelection.height, this.cropperSelection.aspectRatio, true);
        this.syncHiddenFields();
    }

    applySelectionCoverage(coverage) {
        if (!this.ensureSelection()) {
            return;
        }

        const canvasWidth = this.cropperCanvas.offsetWidth;
        const canvasHeight = this.cropperCanvas.offsetHeight;

        if (canvasWidth <= 0 || canvasHeight <= 0) {
            return;
        }

        const ratio = this.getSelectedAspectRatio();
        let width = canvasWidth * coverage;
        let height = canvasHeight * coverage;

        if (Number.isFinite(ratio) && ratio > 0) {
            if (width / height > ratio) {
                width = height * ratio;
            } else {
                height = width / ratio;
            }
        }

        width = Math.min(width, canvasWidth);
        height = Math.min(height, canvasHeight);

        const x = (canvasWidth - width) / 2;
        const y = (canvasHeight - height) / 2;

        this.cropperSelection.$change(x, y, width, height, ratio, true);
        this.syncHiddenFields();
    }

    resetView() {
        this.state.rotation = 0;
        this.state.scaleX = 1;
        this.state.scaleY = 1;
        this.cropperImage.$resetTransform();
        this.cropperSelection.hidden = false;
        this.cropperSelection.$reset();
        this.applyAspectRatio(this.getSelectedAspectRatio());
        this.setDragMode('crop');
        this.syncHiddenFields();
    }

    handleMethod(method, option) {
        if (!this.cropperImage || !this.cropperCanvas || !this.cropperSelection) {
            return;
        }

        const numericOption = Number.parseFloat(option ?? '0');

        switch (method) {
            case 'setDragMode':
                this.ensureSelection();
                this.setDragMode(option);
                break;
            case 'clear':
                this.stopSelectionGripDrag();
                this.cropperSelection.$clear();
                this.cropperSelection.hidden = true;
                break;
            case 'resetView':
                this.resetView();
                return;
            case 'zoom':
                this.cropperImage.$zoom(numericOption);
                break;
            case 'rotate':
                this.ensureSelection();
                this.state.rotation += numericOption;
                this.cropperImage.$rotate(`${numericOption}deg`);
                break;
            case 'scaleX':
                this.ensureSelection();
                this.state.scaleX *= -1;
                this.cropperImage.$scale(-1, 1);
                break;
            case 'scaleY':
                this.ensureSelection();
                this.state.scaleY *= -1;
                this.cropperImage.$scale(1, -1);
                break;
            case 'centerSelection':
                this.centerSelection();
                return;
            case 'selectionPreset':
                if (Number.isFinite(numericOption) && numericOption > 0) {
                    this.applySelectionCoverage(numericOption);
                }
                return;
            default:
                return;
        }

        this.syncHiddenFields();
    }

    schedulePreviewUpdate() {
        if (!this.previewImage) {
            return;
        }

        if (this.previewRenderFrame !== null) {
            return;
        }

        this.previewRenderFrame = window.requestAnimationFrame(async () => {
            this.previewRenderFrame = null;
            await this.renderPreview();
        });
    }

    async renderPreview() {
        if (!this.previewImage || !this.cropperSelection) {
            return;
        }

        const hasSelection = !this.cropperSelection.hidden
            && this.cropperSelection.width > 0
            && this.cropperSelection.height > 0;

        if (!hasSelection) {
            this.previewImage.hidden = true;
            this.previewImage.removeAttribute('src');
            if (this.previewEmpty) {
                this.previewEmpty.hidden = false;
            }
            return;
        }

        try {
            const previewWidth = clamp(roundValue(this.cropperSelection.width), 160, 320);
            const previewCanvas = await this.cropperSelection.$toCanvas({ width: previewWidth });

            if (!(previewCanvas instanceof HTMLCanvasElement)) {
                return;
            }

            this.previewImage.src = previewCanvas.toDataURL('image/png');
            this.previewImage.hidden = false;
            if (this.previewEmpty) {
                this.previewEmpty.hidden = true;
            }
        } catch (error) {
            this.previewImage.hidden = true;
            this.previewImage.removeAttribute('src');
            if (this.previewEmpty) {
                this.previewEmpty.hidden = false;
            }
        }
    }

    updateInspector(hasSelection) {
        const imageWidth = Number.parseFloat(this.root.dataset.mediaWidth || '0');
        const imageHeight = Number.parseFloat(this.root.dataset.mediaHeight || '0');
        const selectionWidth = hasSelection ? roundValue(this.cropperSelection.width) : 0;
        const selectionHeight = hasSelection ? roundValue(this.cropperSelection.height) : 0;
        const selectionX = hasSelection ? roundValue(this.cropperSelection.x) : 0;
        const selectionY = hasSelection ? roundValue(this.cropperSelection.y) : 0;
        const selectionRatio = hasSelection && selectionHeight > 0
            ? (selectionWidth / selectionHeight).toFixed(2)
            : '-';

        if (this.outputFields.imageSize) {
            this.outputFields.imageSize.textContent = `${roundValue(imageWidth)} x ${roundValue(imageHeight)} px`;
        }

        if (this.outputFields.selectionSize) {
            this.outputFields.selectionSize.textContent = `${selectionWidth} x ${selectionHeight} px`;
        }

        if (this.outputFields.selectionPosition) {
            this.outputFields.selectionPosition.textContent = `x: ${selectionX}, y: ${selectionY}`;
        }

        if (this.outputFields.selectionRatio) {
            this.outputFields.selectionRatio.textContent = selectionRatio;
        }

        if (this.outputFields.transformState) {
            this.outputFields.transformState.textContent = `R ${this.state.rotation} / X ${this.state.scaleX} / Y ${this.state.scaleY}`;
        }
    }

    syncHiddenFields() {
        if (!this.cropperSelection) {
            return;
        }

        this.clampSelectionToCanvas();

        const hasSelection = !this.cropperSelection.hidden
            && this.cropperSelection.width > 0
            && this.cropperSelection.height > 0;

        this.hiddenFields.x.value = hasSelection ? roundValue(this.cropperSelection.x) : 0;
        this.hiddenFields.y.value = hasSelection ? roundValue(this.cropperSelection.y) : 0;
        this.hiddenFields.width.value = hasSelection ? roundValue(this.cropperSelection.width) : 0;
        this.hiddenFields.height.value = hasSelection ? roundValue(this.cropperSelection.height) : 0;
        if (this.hiddenFields.canvasWidth) {
            this.hiddenFields.canvasWidth.value = roundValue(this.cropperCanvas.offsetWidth);
        }
        if (this.hiddenFields.canvasHeight) {
            this.hiddenFields.canvasHeight.value = roundValue(this.cropperCanvas.offsetHeight);
        }
        this.hiddenFields.rotate.value = this.state.rotation;
        this.hiddenFields.scaleX.value = this.state.scaleX;
        this.hiddenFields.scaleY.value = this.state.scaleY;

        this.updateInspector(hasSelection);
        this.updateSelectionOverlay();
        this.schedulePreviewUpdate();
    }
}