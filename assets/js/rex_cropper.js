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
    container.find('[data-toggle="tooltip"]').each(function () {
        const element = $(this);

        // Ensure we don't keep an earlier tooltip instance with default options.
        element.tooltip('destroy');
        element.tooltip({
            container: 'body',
        });
    });
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

function readOriginalAspectRatio(root) {
    if (!(root instanceof HTMLElement)) {
        return undefined;
    }

    const configuredRatio = Number.parseFloat(root.dataset.originalRatio || '0');
    if (configuredRatio > 0) {
        return configuredRatio;
    }

    const mediaWidth = Number.parseFloat(root.dataset.mediaWidth || '0');
    const mediaHeight = Number.parseFloat(root.dataset.mediaHeight || '0');

    if (mediaWidth > 0 && mediaHeight > 0) {
        return mediaWidth / mediaHeight;
    }

    return undefined;
}

function readNaturalAspectRatio(imageElement) {
    if (!(imageElement instanceof HTMLImageElement)) {
        return undefined;
    }

    const naturalWidth = Number.parseFloat(String(imageElement.naturalWidth || 0));
    const naturalHeight = Number.parseFloat(String(imageElement.naturalHeight || 0));

    if (naturalWidth > 0 && naturalHeight > 0) {
        return naturalWidth / naturalHeight;
    }

    return undefined;
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
        if (this.root instanceof HTMLElement) {
            this.root.style.setProperty('--cropper-stage-max-height', this.root.dataset.stageMaxHeight || '70vh');
        }
        this.previewRenderFrame = null;
        this.state = {
            rotation: 0,
            scaleX: 1,
            scaleY: 1,
            wheelZoomEnabled: false,
            pinchZoomEnabled: true,
            dragMode: 'move',
        };

        this.previewImage = this.root.querySelector('#cropper_live_preview');
        this.previewEmpty = this.root.querySelector('#cropper_preview_empty');
        this.stage = this.root.querySelector('.cropper-stage');
        this.selectionOverlay = this.root.querySelector('#cropper-selection-overlay');
        this.selectionGrabHandle = this.root.querySelector('#cropper-selection-grab');
        this.sidebar = this.root.querySelector('#cropper-sidebar');
        this.sidebarToggle = this.root.querySelector('#cropper_sidebar_toggle') || document.querySelector('#cropper_sidebar_toggle');
        this.toolbarToggle = this.root.querySelector('#cropper_toolbar_toggle') || document.querySelector('#cropper_toolbar_toggle');
        this.toolbarClose = this.root.querySelector('#cropper_toolbar_close');
        this.toolbarButtons = this.root.querySelector('#cropper-toolbar-buttons');
        this.toolbarToggles = this.root.querySelector('#cropper-toolbar-toggles');
        this.modeBadge = this.root.querySelector('#cropper_mode_badge');
        this.modeHint = this.root.querySelector('#cropper_mode_hint');
        this.sidebarStorageKey = 'cropper.sidebarCollapsed';
        this.toolbarStorageKey = 'cropper.toolbarCollapsed';
        this.sidebarCollapsed = false;
        this.toolbarCollapsed = false;
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
            imageBoxX: this.container.querySelector('#dataImageBoxX'),
            imageBoxY: this.container.querySelector('#dataImageBoxY'),
            imageBoxWidth: this.container.querySelector('#dataImageBoxWidth'),
            imageBoxHeight: this.container.querySelector('#dataImageBoxHeight'),
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
        if (this.cropperSelection) {
            this.cropperSelection.precise = true;
        }

        if (!this.cropperImage || !this.cropperCanvas || !this.cropperSelection) {
            return;
        }

        this.handleCanvasAction = this.handleCanvasActionEvent.bind(this);
        this.handleStageWheel = this.handleStageWheelAction.bind(this);
        this.handleWindowResize = this.handleWindowResizeAction.bind(this);
        this.handleSelectionGripMove = this.moveSelectionWithGrip.bind(this);
        this.handleSelectionGripEnd = this.stopSelectionGripDrag.bind(this);
        this.handleImageDragMove = this.moveImageWithStageDrag.bind(this);
        this.handleImageDragEnd = this.stopImageStageDrag.bind(this);
        this.handleTouchStart = this.startTouchPinchZoom.bind(this);
        this.handleTouchMove = this.moveTouchPinchZoom.bind(this);
        this.handleTouchEnd = this.endTouchPinchZoom.bind(this);
        this.handleDocumentKeydown = this.handleDocumentKeydownAction.bind(this);
        this.selectionGripDrag = null;
        this.imageStageDrag = null;
        this.moveModeSelectionSnapshot = null;
        this.touchPinch = null;
        this.resizeSnapshot = null;
        this.resizeSyncTimer = null;
        this.resizeRafId = null;
        this.stageResizeObserver = null;
        this.modeToastTimer = null;
        this.modeToastInitialized = false;
        this.isCanvasActionActive = false;

        this.cropperImage.$ready(() => {
            this.updateStageHeight();
            this.bindControls();
            this.configureSelection();
            this.scheduleInitialFit();
            this.syncHiddenFields();
        });
    }

    handleCanvasActionEvent(event) {
        if (event.type === 'action') {
            // Mark active so geometry-mutating helpers (clamp, resize observer,
            // window-resize restore) stay out of the way during the drag.
            this.isCanvasActionActive = true;
            return;
        }

        // actionend: safe to write hidden fields, refresh preview, re-enable helpers.
        this.isCanvasActionActive = false;
        this.syncHiddenFields(false);
    }

    bindControls() {
        this.initSidebarToggle();
        this.initToolbarToggle();
        this.installCustomAspectResize();
        window.addEventListener('resize', this.handleWindowResize);
        this.cropperCanvas.addEventListener('action', this.handleCanvasAction);
        this.cropperCanvas.addEventListener('actionend', this.handleCanvasAction);
        // Cropper.js dispatches 'change' on the selection for every geometry
        // mutation — including keyboard arrow-key nudges, which never trigger
        // action/actionend. Keep hidden fields, inspector and preview in sync.
        this.cropperSelection.addEventListener('change', () => {
            this.syncHiddenFields(false);
        });
        this.cropperCanvas.addEventListener('wheel', this.handleStageWheel, { capture: true, passive: false });
        this.stage?.addEventListener('wheel', this.handleStageWheel, { capture: true, passive: false });
        this.stage?.addEventListener('touchstart', this.handleTouchStart, { passive: false });
        this.stage?.addEventListener('touchmove', this.handleTouchMove, { passive: false });
        this.stage?.addEventListener('touchend', this.handleTouchEnd, { passive: false });
        this.stage?.addEventListener('touchcancel', this.handleTouchEnd, { passive: false });
        document.addEventListener('keydown', this.handleDocumentKeydown);

        if (typeof window.ResizeObserver === 'function' && this.stage instanceof HTMLElement) {
            this.stageResizeObserver = new window.ResizeObserver(() => {
                this.handleWindowResizeAction();
            });
            this.stageResizeObserver.observe(this.stage);
        }

        // Keep toolbar interactions independent from cropper pointer handlers.
        [this.root.querySelector('.docs-buttons'), this.root.querySelector('.docs-toggles')].forEach((area) => {
            area?.addEventListener('pointerdown', (event) => {
                event.stopPropagation();
            });
        });

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

            if (!(input instanceof HTMLInputElement)) {
                return;
            }

            if (input.type === 'radio' && input.name === 'aspectRatio') {
                this.setDragMode('crop');
                this.applyAspectRatio(this.getAspectRatioFromInput(input));
                return;
            }

            if (input.type === 'checkbox' && input.name === 'zoomOnWheel') {
                this.applyWheelZoomState(input.checked);
                return;
            }

            if (input.type === 'checkbox' && input.name === 'pinchOnTouch') {
                this.applyPinchZoomState(input.checked);
            }
        });

        // The wheel-zoom toggle can be rendered in different toolbar blocks.
        this.root.addEventListener('change', (event) => {
            const input = event.target;

            if (!(input instanceof HTMLInputElement)) {
                return;
            }

            if (input.type === 'checkbox' && input.name === 'zoomOnWheel') {
                this.applyWheelZoomState(input.checked);
                return;
            }

            if (input.type === 'checkbox' && input.name === 'pinchOnTouch') {
                this.applyPinchZoomState(input.checked);
                return;
            }
        });

        const wheelZoomCheckbox = this.root.querySelector('input[name="zoomOnWheel"]');
        if (wheelZoomCheckbox instanceof HTMLInputElement) {
            this.applyWheelZoomState(wheelZoomCheckbox.checked);
        } else {
            this.applyWheelZoomState(false);
        }

        const pinchZoomCheckbox = this.root.querySelector('input[name="pinchOnTouch"]');
        if (pinchZoomCheckbox instanceof HTMLInputElement) {
            this.applyPinchZoomState(pinchZoomCheckbox.checked);
        } else {
            this.applyPinchZoomState(true);
        }

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
            if (this.state.dragMode === 'move') {
                const target = event.target;
                const onSelectionGrab = target instanceof Element
                    && target.closest('#cropper-selection-grab');

                if (!onSelectionGrab) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                }
            }

            this.startImageStageDrag(event);
        }, true);
    }

    installCustomAspectResize() {
        // Drift fix: when aspectRatio is fixed, the vendor's pointer handler
        // recomputes width/height on every pointermove from the rounded current
        // selection. With aspect cover + integer rounding this accumulates a drift
        // on the opposite edge/corner. We take over entirely while a *-resize
        // handle is dragged with a fixed ratio: anchor the opposite edge/corner
        // of the START selection and recompute geometry from that anchor every
        // move. No accumulation, no drift.
        const handleSpecs = {
            'n-resize':  { axis: 'v', ax: 0.5, ay: 1 },
            's-resize':  { axis: 'v', ax: 0.5, ay: 0 },
            'w-resize':  { axis: 'h', ax: 1,   ay: 0.5 },
            'e-resize':  { axis: 'h', ax: 0,   ay: 0.5 },
            'nw-resize': { axis: 'c', ax: 1,   ay: 1 },
            'ne-resize': { axis: 'c', ax: 0,   ay: 1 },
            'sw-resize': { axis: 'c', ax: 1,   ay: 0 },
            'se-resize': { axis: 'c', ax: 0,   ay: 0 },
        };

        let drag = null;
        const MIN_SIZE = 8;

        const computeGeometry = (mx, my) => {
            const ratio = drag.ratio;
            const spec = drag.spec;
            let newW;
            let newH;

            if (Number.isFinite(ratio) && ratio > 0) {
                if (spec.axis === 'h') {
                    const dx = spec.ax === 1 ? (drag.anchorX - mx) : (mx - drag.anchorX);
                    newW = Math.max(MIN_SIZE, dx);
                    newH = newW / ratio;
                } else if (spec.axis === 'v') {
                    const dy = spec.ay === 1 ? (drag.anchorY - my) : (my - drag.anchorY);
                    newH = Math.max(MIN_SIZE, dy);
                    newW = newH * ratio;
                } else {
                    const dx = Math.abs(mx - drag.anchorX);
                    const dy = Math.abs(my - drag.anchorY);
                    if (dy === 0 || dx / Math.max(dy, 1) > ratio) {
                        newW = Math.max(MIN_SIZE, dx);
                        newH = newW / ratio;
                    } else {
                        newH = Math.max(MIN_SIZE, dy);
                        newW = newH * ratio;
                    }
                }
            } else {
                if (spec.axis === 'h') {
                    const dx = spec.ax === 1 ? (drag.anchorX - mx) : (mx - drag.anchorX);
                    newW = Math.max(MIN_SIZE, dx);
                    newH = drag.startH;
                } else if (spec.axis === 'v') {
                    const dy = spec.ay === 1 ? (drag.anchorY - my) : (my - drag.anchorY);
                    newH = Math.max(MIN_SIZE, dy);
                    newW = drag.startW;
                } else {
                    const dx = spec.ax === 1 ? (drag.anchorX - mx) : (mx - drag.anchorX);
                    const dy = spec.ay === 1 ? (drag.anchorY - my) : (my - drag.anchorY);
                    newW = Math.max(MIN_SIZE, dx);
                    newH = Math.max(MIN_SIZE, dy);
                }
            }

            let newX = drag.anchorX - spec.ax * newW;
            let newY = drag.anchorY - spec.ay * newH;

            // Clamp to canvas: shrink while keeping ratio + anchor if ratio exists.
            if (Number.isFinite(ratio) && ratio > 0) {
                if (newX < 0) {
                    newW += newX;
                    newH = newW / ratio;
                    newX = 0;
                    newY = drag.anchorY - spec.ay * newH;
                }
                if (newY < 0) {
                    newH += newY;
                    newW = newH * ratio;
                    newY = 0;
                    newX = drag.anchorX - spec.ax * newW;
                }
                if (newX + newW > drag.canvasW) {
                    newW = drag.canvasW - newX;
                    newH = newW / ratio;
                    newY = drag.anchorY - spec.ay * newH;
                }
                if (newY + newH > drag.canvasH) {
                    newH = drag.canvasH - newY;
                    newW = newH * ratio;
                    newX = drag.anchorX - spec.ax * newW;
                }
            } else {
                if (newX < 0) {
                    newW += newX;
                    newX = 0;
                }
                if (newY < 0) {
                    newH += newY;
                    newY = 0;
                }
                if (newX + newW > drag.canvasW) {
                    newW = drag.canvasW - newX;
                }
                if (newY + newH > drag.canvasH) {
                    newH = drag.canvasH - newY;
                }
            }

            if (newW < MIN_SIZE || newH < MIN_SIZE) {
                return null;
            }
            return { x: newX, y: newY, w: newW, h: newH };
        };

        const onMove = (event) => {
            if (!drag || event.pointerId !== drag.pointerId) {
                return;
            }
            event.stopImmediatePropagation();
            event.preventDefault();
            const mx = event.clientX - drag.canvasRect.left;
            const my = event.clientY - drag.canvasRect.top;
            const g = computeGeometry(mx, my);
            if (!g) {
                return;
            }
            this.cropperSelection.$change(g.x, g.y, g.w, g.h, drag.ratio ?? NaN, true);
        };

        const onUp = (event) => {
            if (!drag || event.pointerId !== drag.pointerId) {
                return;
            }
            event.stopImmediatePropagation();
            drag = null;
            this.isCanvasActionActive = false;
            window.removeEventListener('pointermove', onMove, true);
            window.removeEventListener('pointerup', onUp, true);
            window.removeEventListener('pointercancel', onUp, true);
            this.syncHiddenFields(false);
        };

        const onDown = (event) => {
            if (!event.isPrimary || event.button !== 0) {
                return;
            }
            const target = event.target;
            if (!(target instanceof Element) || typeof target.getAttribute !== 'function') {
                return;
            }
            const action = target.getAttribute('action');
            const spec = action && handleSpecs[action];
            if (!spec) {
                return;
            }
            const ratio = this.cropperSelection.aspectRatio;

            event.stopImmediatePropagation();
            event.preventDefault();

            const canvasRect = this.cropperCanvas.getBoundingClientRect();
            drag = {
                pointerId: event.pointerId,
                spec,
                action,
                ratio,
                startX: this.cropperSelection.x,
                startY: this.cropperSelection.y,
                startW: this.cropperSelection.width,
                startH: this.cropperSelection.height,
                canvasRect,
                canvasW: this.cropperCanvas.offsetWidth,
                canvasH: this.cropperCanvas.offsetHeight,
            };
            drag.anchorX = drag.startX + spec.ax * drag.startW;
            drag.anchorY = drag.startY + spec.ay * drag.startH;

            this.isCanvasActionActive = true;

            window.addEventListener('pointermove', onMove, true);
            window.addEventListener('pointerup', onUp, true);
            window.addEventListener('pointercancel', onUp, true);
        };

        this.cropperCanvas.addEventListener('pointerdown', onDown, true);
    }

    handleDocumentKeydownAction(event) {
        if (event.key !== 'Escape') {
            return;
        }

        if (this.state.dragMode !== 'move') {
            return;
        }

        const target = event.target;
        if (
            target instanceof HTMLInputElement
            || target instanceof HTMLSelectElement
            || target instanceof HTMLTextAreaElement
            || (target instanceof HTMLElement && target.isContentEditable)
        ) {
            return;
        }

        event.preventDefault();
        this.setDragMode('crop');
        this.syncHiddenFields();
    }

    initToolbarToggle() {
        if (!(this.toolbarToggle instanceof HTMLButtonElement)) {
            return;
        }

        if (!(this.toolbarButtons instanceof HTMLElement) || !(this.toolbarToggles instanceof HTMLElement)) {
            return;
        }

        let collapsed = false;
        const isSmallViewport = window.matchMedia('(max-width: 1079px)').matches;

        try {
            const storedValue = window.localStorage.getItem(this.toolbarStorageKey);
            collapsed = storedValue === null ? isSmallViewport : storedValue === '1';
        } catch (error) {
            collapsed = isSmallViewport;
        }

        this.setToolbarCollapsed(collapsed, false);

        this.toolbarToggle.addEventListener('click', (event) => {
            event.preventDefault();
            this.setToolbarCollapsed(!this.toolbarCollapsed, true);
        });

        if (this.toolbarClose instanceof HTMLButtonElement) {
            this.toolbarClose.addEventListener('click', (event) => {
                event.preventDefault();
                this.setToolbarCollapsed(true, true);
            });
        }
    }

    setToolbarCollapsed(collapsed, persist) {
        if (!(this.toolbarToggle instanceof HTMLButtonElement)) {
            return;
        }

        this.toolbarCollapsed = collapsed;
        this.root.classList.toggle('is-toolbar-collapsed', collapsed);
        this.toolbarToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

        const label = collapsed
            ? (this.toolbarToggle.dataset.collapsedLabel || '')
            : (this.toolbarToggle.dataset.expandedLabel || '');

        this.toolbarToggle.setAttribute('title', label);
        this.toolbarToggle.setAttribute('data-original-title', label);

        if (typeof window !== 'undefined' && window.jQuery) {
            window.jQuery(this.toolbarToggle).tooltip('fixTitle');
        }

        if (persist) {
            try {
                window.localStorage.setItem(this.toolbarStorageKey, collapsed ? '1' : '0');
            } catch (error) {
                // Ignore storage errors; the toggle still works for current session.
            }
        }

        this.relayoutAfterPanelToggle();
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

        const initialOpen = this.root.dataset.sidebarInitialOpen === '1';
        let collapsed = !initialOpen;

        try {
            const storedValue = window.localStorage.getItem(this.sidebarStorageKey);
            collapsed = storedValue === null ? !initialOpen : storedValue === '1';
        } catch (error) {
            collapsed = !initialOpen;
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

        this.relayoutAfterPanelToggle();
    }

    captureSelectionSnapshot() {
        if (!this.cropperSelection || !this.cropperCanvas) {
            return null;
        }

        const canvasWidth = this.cropperCanvas.offsetWidth;
        const canvasHeight = this.cropperCanvas.offsetHeight;

        if (canvasWidth <= 0 || canvasHeight <= 0) {
            return null;
        }

        const widthRatio = this.cropperSelection.width / canvasWidth;
        const heightRatio = this.cropperSelection.height / canvasHeight;
        const centerXRatio = (this.cropperSelection.x + (this.cropperSelection.width / 2)) / canvasWidth;
        const centerYRatio = (this.cropperSelection.y + (this.cropperSelection.height / 2)) / canvasHeight;

        const imageBox = this.getVisibleImageBoxInCanvas();
        const hasImageBox = imageBox && imageBox.width > 0 && imageBox.height > 0;

        let imageWidthRatio = widthRatio;
        let imageHeightRatio = heightRatio;
        let imageCenterXRatio = centerXRatio;
        let imageCenterYRatio = centerYRatio;

        if (hasImageBox) {
            imageWidthRatio = this.cropperSelection.width / imageBox.width;
            imageHeightRatio = this.cropperSelection.height / imageBox.height;
            imageCenterXRatio = ((this.cropperSelection.x + (this.cropperSelection.width / 2)) - imageBox.x) / imageBox.width;
            imageCenterYRatio = ((this.cropperSelection.y + (this.cropperSelection.height / 2)) - imageBox.y) / imageBox.height;
        }

        return {
            widthRatio,
            heightRatio,
            centerXRatio,
            centerYRatio,
            imageWidthRatio,
            imageHeightRatio,
            imageCenterXRatio,
            imageCenterYRatio,
            hasImageBox,
            aspectRatio: this.cropperSelection.aspectRatio,
        };
    }

    getVisibleImageBoxInCanvas() {
        if (!this.cropperImage || !this.cropperCanvas) {
            return null;
        }

        const canvasRect = this.cropperCanvas.getBoundingClientRect();
        const imageRect = this.cropperImage.getBoundingClientRect();

        if (canvasRect.width <= 0 || canvasRect.height <= 0 || imageRect.width <= 0 || imageRect.height <= 0) {
            return null;
        }

        const x = imageRect.left - canvasRect.left;
        const y = imageRect.top - canvasRect.top;
        const width = imageRect.width;
        const height = imageRect.height;

        return {
            x,
            y,
            width,
            height,
        };
    }

    restoreSelectionSnapshot(snapshot) {
        if (!snapshot || !this.cropperSelection || !this.cropperCanvas) {
            return;
        }

        const canvasWidth = this.cropperCanvas.offsetWidth;
        const canvasHeight = this.cropperCanvas.offsetHeight;

        if (canvasWidth <= 0 || canvasHeight <= 0) {
            return;
        }

        const imageBox = this.getVisibleImageBoxInCanvas();
        const useImageBox = snapshot.hasImageBox === true && imageBox && imageBox.width > 0 && imageBox.height > 0;

        let width;
        let height;
        let centerX;
        let centerY;

        if (useImageBox) {
            width = Math.max(24, Math.min(canvasWidth, imageBox.width * snapshot.imageWidthRatio));
            height = Math.max(24, Math.min(canvasHeight, imageBox.height * snapshot.imageHeightRatio));
            centerX = imageBox.x + (snapshot.imageCenterXRatio * imageBox.width);
            centerY = imageBox.y + (snapshot.imageCenterYRatio * imageBox.height);
        } else {
            width = Math.max(24, Math.min(canvasWidth, canvasWidth * snapshot.widthRatio));
            height = Math.max(24, Math.min(canvasHeight, canvasHeight * snapshot.heightRatio));
            centerX = snapshot.centerXRatio * canvasWidth;
            centerY = snapshot.centerYRatio * canvasHeight;
        }

        if (Number.isFinite(snapshot.aspectRatio) && snapshot.aspectRatio > 0) {
            if (width / height > snapshot.aspectRatio) {
                width = height * snapshot.aspectRatio;
            } else {
                height = width / snapshot.aspectRatio;
            }
        }

        centerX = clamp(centerX, width / 2, canvasWidth - (width / 2));
        centerY = clamp(centerY, height / 2, canvasHeight - (height / 2));
        const x = centerX - (width / 2);
        const y = centerY - (height / 2);

        this.cropperSelection.$change(x, y, width, height, snapshot.aspectRatio, true);
        this.clampSelectionToCanvas();
    }

    relayoutAfterPanelToggle() {
        const snapshot = this.captureSelectionSnapshot();

        this.updateStageHeight();

        window.requestAnimationFrame(() => {
            this.updateStageHeight();
            this.fitImageToCanvas();
            this.ensureSelection();
            this.restoreSelectionSnapshot(snapshot);
            this.syncHiddenFields();
        });

        window.setTimeout(() => {
            this.updateStageHeight();
            this.fitImageToCanvas();
            this.ensureSelection();
            this.restoreSelectionSnapshot(snapshot);
            this.syncHiddenFields();
        }, 180);
    }

    handleWindowResizeAction() {
        // Never reshape the selection while the user is interacting with it.
        if (this.isCanvasActionActive || this.selectionGripDrag || this.imageStageDrag) {
            return;
        }

        if (!this.resizeSnapshot) {
            this.resizeSnapshot = this.captureSelectionSnapshot();
        }

        this.updateStageHeight();

        if (this.resizeRafId !== null) {
            window.cancelAnimationFrame(this.resizeRafId);
            this.resizeRafId = null;
        }

        if (this.resizeSyncTimer !== null) {
            window.clearTimeout(this.resizeSyncTimer);
        }

        this.resizeRafId = window.requestAnimationFrame(() => {
            this.resizeRafId = null;
            this.updateStageHeight();
            this.fitImageToCanvas();

            if (this.resizeSnapshot) {
                this.ensureSelection();
                this.restoreSelectionSnapshot(this.resizeSnapshot);
            }

            this.syncHiddenFields();
        });

        this.resizeSyncTimer = window.setTimeout(() => {
            const snapshot = this.resizeSnapshot;
            this.resizeSnapshot = null;
            this.resizeSyncTimer = null;

            this.updateStageHeight();
            this.fitImageToCanvas();
            this.ensureSelection();
            this.restoreSelectionSnapshot(snapshot);
            this.syncHiddenFields();
        }, 220);
    }

    activateAspectRatioInput(input) {
        this.root.querySelectorAll('input[name="aspectRatio"]').forEach((field) => {
            if (field instanceof HTMLInputElement) {
                field.checked = field === input;
                field.closest('label.btn')?.classList.toggle('active', field === input);
            }
        });

        this.applyAspectRatio(this.getAspectRatioFromInput(input));
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
        const stageMaxHeight = this.resolveStageMaxHeightPx(viewportHeight, viewportWidth);

        // For landscape and square images, keep the stage in the original image ratio.
        if (mediaRatio >= 1 && stageWidth > 0) {
            const exactStageHeight = Math.round(stageWidth / mediaRatio);
            const minStageHeight = viewportWidth < 768 ? 220 : 260;
            const maxStageHeight = Math.max(minStageHeight, stageMaxHeight);
            const stageHeight = clamp(exactStageHeight, minStageHeight, maxStageHeight);

            this.root.style.setProperty('--cropper-stage-height', `${stageHeight}px`);
            this.updateCompactToolbarRailBounds();
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
            Math.max(viewportWidth < 768 ? 280 : 380, stageMaxHeight),
        );

        this.root.style.setProperty('--cropper-stage-height', `${stageHeight}px`);
        this.updateCompactToolbarRailBounds();
        this.updateSelectionOverlay();
    }

    resolveStageMaxHeightPx(viewportHeight, viewportWidth) {
        if (!(this.root instanceof HTMLElement)) {
            return Math.round(viewportHeight * 0.7);
        }

        const configuredValue = (this.root.dataset.stageMaxHeight || '70vh').trim().toLowerCase();
        const match = configuredValue.match(/^(\d+(?:\.\d+)?)(px|vh|vw|rem|em|%)$/);

        if (!match) {
            return Math.round(viewportHeight * 0.7);
        }

        const value = Number.parseFloat(match[1]);
        const unit = match[2];

        if (!Number.isFinite(value) || value <= 0) {
            return Math.round(viewportHeight * 0.7);
        }

        if (unit === 'px') {
            return Math.round(value);
        }

        if (unit === 'vh' || unit === '%') {
            return Math.round((viewportHeight * value) / 100);
        }

        if (unit === 'vw') {
            return Math.round((viewportWidth * value) / 100);
        }

        const rootFontSize = Number.parseFloat(window.getComputedStyle(document.documentElement).fontSize) || 16;

        if (unit === 'rem' || unit === 'em') {
            return Math.round(value * rootFontSize);
        }

        return Math.round(viewportHeight * 0.7);
    }

    updateCompactToolbarRailBounds() {
        if (!(this.root instanceof HTMLElement)) {
            return;
        }

        if (!this.root.classList.contains('is-compact-toolbar')) {
            return;
        }

        const rail = this.root.querySelector('#cropper-toolbar-rail');
        if (!(rail instanceof HTMLElement) || !(this.stage instanceof HTMLElement)) {
            return;
        }

        const railHost = rail.offsetParent instanceof HTMLElement
            ? rail.offsetParent
            : this.root.querySelector('.cropper-stage-card');

        if (!(railHost instanceof HTMLElement)) {
            return;
        }

        const modeBar = railHost.querySelector('.cropper-mode-bar');
        const modeBarHeight = modeBar instanceof HTMLElement ? modeBar.offsetHeight : 0;
        const sidebarOpen = !this.root.classList.contains('is-sidebar-collapsed');

        const panelRect = railHost.getBoundingClientRect();
        const stageRect = this.stage.getBoundingClientRect();

        if (panelRect.height <= 0 || stageRect.height <= 0) {
            return;
        }

        const top = Math.max(8, Math.round(stageRect.top - panelRect.top + 10));
        const geometricBottom = Math.max(8, Math.round(panelRect.bottom - stageRect.bottom + 10));
        const preferredBottom = sidebarOpen
            ? 14
            : Math.max(8, Math.round((modeBarHeight * 0.65) + 8));
        const bottom = Math.max(8, Math.min(geometricBottom, preferredBottom));

        this.root.style.setProperty('--cropper-compact-rail-top', `${top}px`);
        this.root.style.setProperty('--cropper-compact-rail-bottom', `${bottom}px`);
    }

    getSelectedAspectRatio() {
        const activeRatio = this.root.querySelector('input[name="aspectRatio"]:checked');
        return activeRatio instanceof HTMLInputElement ? this.getAspectRatioFromInput(activeRatio) : undefined;
    }

    getAspectRatioFromInput(input) {
        if (!(input instanceof HTMLInputElement)) {
            return undefined;
        }

        if (input.dataset.aspectRatio === 'original') {
            const ratioFromMediaMeta = readOriginalAspectRatio(this.root);
            if (Number.isFinite(ratioFromMediaMeta) && ratioFromMediaMeta > 0) {
                return ratioFromMediaMeta;
            }

            return readNaturalAspectRatio(this.imageElement);
        }

        return readAspectRatio(input.value);
    }

    configureSelection() {
        this.cropperSelection.movable = true;
        this.cropperSelection.resizable = true;
        this.cropperSelection.keyboard = true;
        this.cropperSelection.zoomable = this.state.wheelZoomEnabled;
        this.cropperSelection.aspectRatio = this.getSelectedAspectRatio();
        this.cropperSelection.initialAspectRatio = this.getSelectedAspectRatio();
        this.cropperSelection.hidden = false;
        this.cropperSelection.$reset();
        this.setDragMode('crop');
    }

    applyWheelZoomState(enabled) {
        const normalized = enabled === true;
        this.state.wheelZoomEnabled = normalized;

        if (this.cropperSelection) {
            this.cropperSelection.zoomable = normalized;
        }

        if (this.cropperCanvas && 'scaleStep' in this.cropperCanvas) {
            this.cropperCanvas.scaleStep = normalized ? 0.1 : 0;
        }
    }

    applyPinchZoomState(enabled) {
        this.state.pinchZoomEnabled = enabled === true;

        if (!this.state.pinchZoomEnabled) {
            this.touchPinch = null;
        }
    }

    getTouchDistance(touches) {
        if (!touches || touches.length < 2) {
            return 0;
        }

        const touchA = touches[0];
        const touchB = touches[1];
        return Math.hypot(touchB.clientX - touchA.clientX, touchB.clientY - touchA.clientY);
    }

    startTouchPinchZoom(event) {
        if (!this.state.pinchZoomEnabled) {
            return;
        }

        if (!event.touches || event.touches.length < 2) {
            this.touchPinch = null;
            return;
        }

        this.stopImageStageDrag();
        this.stopSelectionGripDrag();

        const initialDistance = this.getTouchDistance(event.touches);
        if (!Number.isFinite(initialDistance) || initialDistance <= 0) {
            this.touchPinch = null;
            return;
        }

        this.touchPinch = { distance: initialDistance };
        event.preventDefault();
    }

    moveTouchPinchZoom(event) {
        if (!this.state.pinchZoomEnabled) {
            return;
        }

        if (!event.touches || event.touches.length < 2) {
            this.touchPinch = null;
            return;
        }

        const currentDistance = this.getTouchDistance(event.touches);
        if (!Number.isFinite(currentDistance) || currentDistance <= 0) {
            return;
        }

        if (!this.touchPinch) {
            this.touchPinch = { distance: currentDistance };
            event.preventDefault();
            return;
        }

        const distanceRatio = currentDistance / this.touchPinch.distance;
        const zoomDelta = clamp((distanceRatio - 1) * 0.7, -0.2, 0.2);

        if (Math.abs(zoomDelta) > 0.002) {
            this.cropperImage.$zoom(zoomDelta);
            this.syncHiddenFields();
        }

        this.touchPinch.distance = currentDistance;
        event.preventDefault();
    }

    endTouchPinchZoom(event) {
        if (!event.touches || event.touches.length < 2) {
            this.touchPinch = null;
        }
    }

    handleStageWheelAction(event) {
        const target = event.target;
        const inStage = target instanceof Node
            && (
                (this.cropperCanvas instanceof HTMLElement && this.cropperCanvas.contains(target))
                || (this.stage instanceof HTMLElement && this.stage.contains(target))
            );

        if (!inStage) {
            return;
        }

        if (this.state.wheelZoomEnabled) {
            event.preventDefault();
            event.stopImmediatePropagation();

            const rawDelta = event.deltaY;
            const normalizedDelta = Math.abs(rawDelta) < 0.0001 ? 0 : rawDelta;
            if (normalizedDelta === 0) {
                return;
            }

            const direction = normalizedDelta < 0 ? 1 : -1;
            const intensity = clamp(Math.abs(normalizedDelta) / 280, 0.02, 0.18);
            const zoomStep = direction * intensity;

            this.cropperImage.$zoom(zoomStep);
            this.syncHiddenFields();
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        let deltaX = event.deltaX;
        let deltaY = event.deltaY;

        if (event.deltaMode === 1) {
            deltaX *= 16;
            deltaY *= 16;
        } else if (event.deltaMode === 2) {
            deltaX *= window.innerWidth;
            deltaY *= window.innerHeight;
        }

        window.scrollBy({
            left: deltaX,
            top: deltaY,
            behavior: 'auto',
        });
    }

    forwardWheelToPageWhenZoomDisabled(event) {
        // BC shim: delegate to unified wheel handler.
        this.handleStageWheelAction(event);
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

        // Do not write geometry back while an active drag/resize is running.
        if (this.selectionGripDrag || this.imageStageDrag || this.isCanvasActionActive) {
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

        // Some Cropper interactions toggle selection visibility while moving the image.
        // Force it back on in move mode when a valid selection exists.
        if (this.state.dragMode === 'move' && this.cropperSelection.width > 0 && this.cropperSelection.height > 0) {
            this.cropperSelection.hidden = false;
            this.ensureSelection();
        }

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

        if (this.modeToastInitialized) {
            this.flashModeToast();
        } else {
            this.modeToastInitialized = true;
        }

        this.updateSelectionOverlay();
    }

    flashModeToast() {
        if (!(this.modeBadge instanceof HTMLElement)) {
            return;
        }

        const modeBar = this.modeBadge.closest('.cropper-mode-bar');
        if (!(modeBar instanceof HTMLElement)) {
            return;
        }

        modeBar.classList.remove('is-visible');

        if (this.modeToastTimer !== null) {
            window.clearTimeout(this.modeToastTimer);
            this.modeToastTimer = null;
        }

        window.requestAnimationFrame(() => {
            modeBar.classList.add('is-visible');
            this.modeToastTimer = window.setTimeout(() => {
                modeBar.classList.remove('is-visible');
                this.modeToastTimer = null;
            }, 900);
        });
    }

    startImageStageDrag(event) {
        if (this.state.dragMode !== 'move' || !this.cropperImage) {
            return;
        }

        if (event.pointerType === 'touch') {
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
        this.ensureSelection();
        this.moveModeSelectionSnapshot = this.captureSelectionSnapshot();
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

        if (this.cropperSelection) {
            const collapsedSelection = this.cropperSelection.hidden
                || this.cropperSelection.width < 8
                || this.cropperSelection.height < 8;

            if (collapsedSelection) {
                this.restoreSelectionSnapshot(this.moveModeSelectionSnapshot);
                this.ensureSelection();
            } else {
                this.cropperSelection.hidden = false;
            }
        }

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
        this.moveModeSelectionSnapshot = null;
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

        const hasSelection = this.cropperSelection.width > 0
            && this.cropperSelection.height > 0
            && (this.state.dragMode === 'move' || !this.cropperSelection.hidden);

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

    applyAspectRatio(ratio) {
        if (!this.ensureSelection()) {
            return;
        }

        const canvasWidth = this.cropperCanvas.offsetWidth;
        const canvasHeight = this.cropperCanvas.offsetHeight;
        const centerX = this.cropperSelection.x + (this.cropperSelection.width / 2);
        const centerY = this.cropperSelection.y + (this.cropperSelection.height / 2);

        let nextWidth = this.cropperSelection.width;
        let nextHeight = this.cropperSelection.height;

        if (Number.isFinite(ratio) && ratio > 0) {
            const currentArea = Math.max(1, this.cropperSelection.width * this.cropperSelection.height);
            nextWidth = Math.sqrt(currentArea * ratio);
            nextHeight = nextWidth / ratio;

            if (canvasWidth > 0 && nextWidth > canvasWidth) {
                nextWidth = canvasWidth;
                nextHeight = nextWidth / ratio;
            }

            if (canvasHeight > 0 && nextHeight > canvasHeight) {
                nextHeight = canvasHeight;
                nextWidth = nextHeight * ratio;
            }
        }

        const nextX = canvasWidth > 0
            ? clamp(centerX - (nextWidth / 2), 0, Math.max(0, canvasWidth - nextWidth))
            : this.cropperSelection.x;
        const nextY = canvasHeight > 0
            ? clamp(centerY - (nextHeight / 2), 0, Math.max(0, canvasHeight - nextHeight))
            : this.cropperSelection.y;

        this.cropperSelection.aspectRatio = ratio;
        this.cropperSelection.initialAspectRatio = ratio;
        this.cropperSelection.$change(
            nextX,
            nextY,
            nextWidth,
            nextHeight,
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
        this.stopSelectionGripDrag();
        this.stopImageStageDrag();
        this.state.rotation = 0;
        this.state.scaleX = 1;
        this.state.scaleY = 1;
        this.cropperImage.$resetTransform();
        this.updateStageHeight();
        this.fitImageToCanvas();
        this.cropperSelection.hidden = false;
        this.cropperSelection.$reset();
        this.applyAspectRatio(this.getSelectedAspectRatio());
        this.ensureSelection();
        this.centerSelection();
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
            case 'fitImage':
                this.fitImageToCanvas();
                this.ensureSelection();
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
            const previewFrame = this.previewImage.closest('.cropper-preview-frame');
            const frameWidth = previewFrame instanceof HTMLElement
                ? Math.max(0, previewFrame.clientWidth - 16)
                : 0;
            const deviceScale = Math.max(1, window.devicePixelRatio || 1);
            const targetWidth = Math.max(roundValue(this.cropperSelection.width), roundValue(frameWidth * deviceScale));
            const previewWidth = clamp(targetWidth, 180, 1400);
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
            let outputWidth = selectionWidth;
            let outputHeight = selectionHeight;

            if (hasSelection) {
                const imageBox = this.getVisibleImageBoxInCanvas();
                if (
                    imageBox
                    && imageBox.width > 0
                    && imageBox.height > 0
                    && imageWidth > 0
                    && imageHeight > 0
                ) {
                    const scaleX = imageWidth / imageBox.width;
                    const scaleY = imageHeight / imageBox.height;
                    outputWidth = roundValue(this.cropperSelection.width * scaleX);
                    outputHeight = roundValue(this.cropperSelection.height * scaleY);
                }
            }

            this.outputFields.selectionSize.textContent = `${selectionWidth} x ${selectionHeight} px (Ansicht) | ${outputWidth} x ${outputHeight} px (Output)`;
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

    syncHiddenFields(clampSelection = true) {
        if (!this.cropperSelection) {
            return;
        }

        if (clampSelection) {
            this.clampSelectionToCanvas();
        }

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

        const imageBox = this.getVisibleImageBoxInCanvas();
        if (
            imageBox
            && imageBox.width > 0
            && imageBox.height > 0
        ) {
            if (this.hiddenFields.imageBoxX) {
                this.hiddenFields.imageBoxX.value = imageBox.x.toFixed(4);
            }
            if (this.hiddenFields.imageBoxY) {
                this.hiddenFields.imageBoxY.value = imageBox.y.toFixed(4);
            }
            if (this.hiddenFields.imageBoxWidth) {
                this.hiddenFields.imageBoxWidth.value = imageBox.width.toFixed(4);
            }
            if (this.hiddenFields.imageBoxHeight) {
                this.hiddenFields.imageBoxHeight.value = imageBox.height.toFixed(4);
            }
        } else {
            if (this.hiddenFields.imageBoxX) {
                this.hiddenFields.imageBoxX.value = '0';
            }
            if (this.hiddenFields.imageBoxY) {
                this.hiddenFields.imageBoxY.value = '0';
            }
            if (this.hiddenFields.imageBoxWidth) {
                this.hiddenFields.imageBoxWidth.value = '0';
            }
            if (this.hiddenFields.imageBoxHeight) {
                this.hiddenFields.imageBoxHeight.value = '0';
            }
        }

        this.hiddenFields.rotate.value = this.state.rotation;
        this.hiddenFields.scaleX.value = this.state.scaleX;
        this.hiddenFields.scaleY.value = this.state.scaleY;

        this.updateInspector(hasSelection);
        this.updateSelectionOverlay();
        this.schedulePreviewUpdate();
    }
}