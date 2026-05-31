<?php
        $mediaUrl = $this->mediaUrl;
        $media = $this->media;
        $mtime = $this->mtime;
        $mediaPoolWidth = (int) $this->getVar('mediaPoolWidth', (int) $media->getWidth());
        $mediaPoolHeight = (int) $this->getVar('mediaPoolHeight', (int) $media->getHeight());
        $aspectRatioConfig = rex_config::get('cropper', 'aspect_ratios');
        $aspectRatioConfig = is_string($aspectRatioConfig) ? str_replace(',', '.', $aspectRatioConfig) : '';
        $aspectRatios = preg_split("/\R/", $aspectRatioConfig) ?: [];

        $ratios = [];
        foreach ($aspectRatios as $ratioLine) {
            $parts = explode(':', $ratioLine);
            if (count($parts) !== 2 || !is_numeric($parts[0]) || !is_numeric($parts[1]) || (float) $parts[1] === 0.0) {
                continue;
            }

            $width = (string) $parts[0];
            $height = (string) $parts[1];
            $ratios[] = [
                'w' => $width,
                'h' => $height,
                'r' => (float) $width / (float) $height,
            ];
        }

        $originalRatio = $mediaPoolHeight > 0
            ? (string) ($mediaPoolWidth / $mediaPoolHeight)
            : 'NaN';

        $configEnabled = static function ($value): bool {
            if (is_bool($value)) {
                return $value;
            }

            if (is_int($value) || is_float($value)) {
                return (int) $value === 1;
            }

            if (is_string($value)) {
                $trimmedValue = trim($value);
                if ('' === $trimmedValue) {
                    return false;
                }

                if (preg_match('/(^|\|)1(\||$)/', $trimmedValue) === 1) {
                    return true;
                }

                return in_array(strtolower($trimmedValue), ['1', 'true', 'yes', 'on'], true);
            }

            if (is_array($value)) {
                return in_array(1, $value, true) || in_array('1', $value, true);
            }

            return false;
        };

        $toolbarModeConfig = rex_config::get('cropper', 'toolbar_mode', 'legacy');
        $toolbarMode = is_string($toolbarModeConfig) ? trim($toolbarModeConfig) : 'legacy';
        if (!in_array($toolbarMode, ['legacy', 'default'], true)) {
            $toolbarMode = 'legacy';
        }

        $legacyOverlayToolbar = $toolbarMode === 'legacy' || $toolbarMode === 'compact';

        $showSidebarInitiallyConfig = rex_config::get('cropper', 'show_info_sidebar_initially', 0);
        $showSidebarInitially = false;
        if (is_bool($showSidebarInitiallyConfig)) {
            $showSidebarInitially = $showSidebarInitiallyConfig;
        } elseif (is_int($showSidebarInitiallyConfig) || is_float($showSidebarInitiallyConfig)) {
            $showSidebarInitially = (int) $showSidebarInitiallyConfig === 1;
        } elseif (is_string($showSidebarInitiallyConfig)) {
            $trimmedConfig = trim($showSidebarInitiallyConfig);
            if ('' !== $trimmedConfig) {
                $showSidebarInitially = preg_match('/(^|\|)1(\||$)/', $trimmedConfig) === 1;
            }
        } elseif (is_array($showSidebarInitiallyConfig)) {
            $showSidebarInitially = in_array(1, $showSidebarInitiallyConfig, true) || in_array('1', $showSidebarInitiallyConfig, true);
        }

        $showOriginalRatio = $configEnabled(rex_config::get('cropper', 'show_original_ratio', 1));
        $showFreeRatio = $configEnabled(rex_config::get('cropper', 'show_free_ratio', 1));
        $hasConfiguredRatios = count($ratios) > 0;

        if (!$showOriginalRatio && !$hasConfiguredRatios) {
            $showOriginalRatio = true;
        }

        $stageMaxHeightConfig = rex_config::get('cropper', 'stage_max_height', '70vh');
        $stageMaxHeight = '70vh';
        if (is_string($stageMaxHeightConfig)) {
            $trimmedStageMaxHeight = trim($stageMaxHeightConfig);
            if (preg_match('/^\d+(?:\.\d+)?(?:px|vh|vw|rem|em|%)$/i', $trimmedStageMaxHeight) === 1) {
                $stageMaxHeight = $trimmedStageMaxHeight;
            }
        }
?>

<div
    id="cropper-workspace"
    class="cropper-workspace<?= $legacyOverlayToolbar ? ' is-compact-toolbar is-legacy-compact-toolbar' : '' ?>"
    data-media-width="<?= $mediaPoolWidth ?>"
    data-media-height="<?= $mediaPoolHeight ?>"
    data-original-ratio="<?= rex_escape($originalRatio) ?>"
    data-sidebar-initial-open="<?= $showSidebarInitially ? '1' : '0' ?>"
    data-stage-max-height="<?= rex_escape($stageMaxHeight) ?>"
>
    <div class="cropper-hero">
        <section class="cropper-main-panel">
            <div class="cropper-stage-card">
                <div class="cropper_image_wrapper">
                    <div class="cropper-stage">
                        <img id="cropper_image" src="<?= $mediaUrl;?>?buster=<?= $mtime;?>" alt="">
                        <div id="cropper-selection-overlay" class="cropper-selection-overlay" hidden>
                            <button
                                type="button"
                                id="cropper-selection-grab"
                                class="cropper-selection-grab"
                                title="<?= rex_i18n::msg('cropper_action_move_selection') ?>"
                            >
                                <span class="fa fa-arrows"></span>
                                <span class="cropper-selection-grab-label"><?= rex_i18n::msg('cropper_selection_grab') ?></span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="cropper-toolbar-rail" id="cropper-toolbar-rail">
                <div id="cropper-toolbar-buttons" class="docs-buttons">
                    <div class="cropper-toolbar-section">
                        <span class="cropper-toolbar-label"><?= rex_i18n::msg('cropper_toolbar_mode') ?></span>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-primary" data-method="setDragMode" data-option="move" data-toggle="tooltip" data-original-title="<?= rex_i18n::msg('cropper_action_move') ?>" data-animation="false">
                                <span class="fa fa-arrows"></span>
                            </button>
                            <button type="button" class="btn btn-primary active" data-method="setDragMode" data-option="crop" data-toggle="tooltip" data-original-title="<?= rex_i18n::msg('cropper_action_select') ?>" data-animation="false">
                                <span class="fa fa-crop"></span>
                            </button>
                            <button type="button" class="btn btn-primary" data-method="centerSelection" data-toggle="tooltip" data-original-title="<?= rex_i18n::msg('cropper_action_center') ?>" data-animation="false">
                                <span class="fa fa-crosshairs"></span>
                            </button>
                            <button type="button" class="btn btn-primary" data-method="clear" data-toggle="tooltip" data-original-title="<?= rex_i18n::msg('cropper_action_clear') ?>" data-animation="false">
                                <span class="fa fa-times"></span>
                            </button>
                            <button type="button" class="btn btn-primary" data-method="resetView" data-toggle="tooltip" data-original-title="<?= rex_i18n::msg('cropper_action_reset') ?>" data-animation="false">
                                <span class="fa fa-refresh"></span>
                            </button>
                        </div>
                    </div>

                    <div class="cropper-toolbar-section">
                        <span class="cropper-toolbar-label"><?= rex_i18n::msg('cropper_preview_title') ?></span>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-primary" data-method="zoom" data-option="0.1" data-toggle="tooltip" data-original-title="<?= rex_i18n::msg('cropper_action_zoom_in') ?>" data-animation="false">
                                <span class="fa fa-search-plus"></span>
                            </button>
                            <button type="button" class="btn btn-primary" data-method="zoom" data-option="-0.1" data-toggle="tooltip" data-original-title="<?= rex_i18n::msg('cropper_action_zoom_out') ?>" data-animation="false">
                                <span class="fa fa-search-minus"></span>
                            </button>
                            <button type="button" class="btn btn-primary" data-method="fitImage" data-toggle="tooltip" data-original-title="<?= rex_i18n::msg('cropper_action_fit') ?>" data-animation="false">
                                <span class="fa fa-compress"></span>
                            </button>
                            <button type="button" class="btn btn-primary" data-method="rotate" data-option="-90" data-toggle="tooltip" data-original-title="<?= rex_i18n::msg('cropper_action_rotate_left') ?>" data-animation="false">
                                <span class="fa fa-rotate-left"></span>
                            </button>
                            <button type="button" class="btn btn-primary" data-method="rotate" data-option="90" data-toggle="tooltip" data-original-title="<?= rex_i18n::msg('cropper_action_rotate_right') ?>" data-animation="false">
                                <span class="fa fa-rotate-right"></span>
                            </button>
                            <button type="button" class="btn btn-primary" data-method="scaleX" data-option="-1" data-toggle="tooltip" data-original-title="<?= rex_i18n::msg('cropper_action_flip_horizontal') ?>" data-animation="false">
                                <span class="fa fa-arrows-h"></span>
                            </button>
                            <button type="button" class="btn btn-primary" data-method="scaleY" data-option="-1" data-toggle="tooltip" data-original-title="<?= rex_i18n::msg('cropper_action_flip_vertical') ?>" data-animation="false">
                                <span class="fa fa-arrows-v"></span>
                            </button>
                        </div>

                        <div class="cropper-settings dropdown dropup">
                            <button class="btn dropdown-toggle" type="button" id="cropper-settings" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-cogs" aria-hidden="true"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="cropper-settings">
                                <li>
                                    <label for="zoomOnWheel">
                                        <input class="form-check-input" id="zoomOnWheel" type="checkbox" name="zoomOnWheel" data-form-type="other">
                                        <?= rex_i18n::msg('cropper_wheel_zoom') ?>
                                    </label>
                                </li>
                                <li>
                                    <label for="pinchOnTouch">
                                        <input class="form-check-input" id="pinchOnTouch" type="checkbox" name="pinchOnTouch" data-form-type="other" checked="checked">
                                        <?= rex_i18n::msg('cropper_pinch_zoom') ?>
                                    </label>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div id="cropper-toolbar-toggles" class="docs-toggles">
                <div class="cropper-toolbar-section">
                    <span class="cropper-toolbar-label"><?= rex_i18n::msg('cropper_toolbar_aspect_ratio') ?></span>
                    <div class="btn-group cropper-ratio-group" data-toggle="buttons">
                        <?php $originalSelected = $showOriginalRatio || !$hasConfiguredRatios; ?>
                        <?php if ($showOriginalRatio || !$hasConfiguredRatios) : ?>
                        <label class="btn btn-primary<?= $originalSelected ? ' active' : '' ?>" data-toggle="none_tooltip" data-animation="false" data-original-title="aspectRatio: <?= $mediaPoolWidth . ' / ' . $mediaPoolHeight ;?>">
                            <input type="radio" class="sr-only" id="aspectRatio-1" name="aspectRatio" value="<?= $originalRatio ?>" data-aspect-ratio="original"<?= $originalSelected ? ' checked="checked"' : '' ?>><?= rex_i18n::msg('cropper_ratio_original') ?>
                        </label>
                        <?php endif; ?>

                        <?php foreach ($ratios AS $i => $ratio) :?>
                        <?php $ratioSelected = !$originalSelected && 0 === $i; ?>
                        <label class="btn btn-primary<?= $ratioSelected ? ' active' : '' ?>" data-toggle="none_tooltip" data-animation="false" data-original-title="aspectRatio: <?= $ratio['w']?> / <?= $ratio['h']?>">
                            <input type="radio" class="sr-only" id="aspectRatio<?= $i;?>" name="aspectRatio" value="<?= $ratio['r'];?>"<?= $ratioSelected ? ' checked="checked"' : '' ?>><?= $ratio['w']?>:<?= $ratio['h']?>
                        </label>
                        <?php endforeach;?>

                        <?php if ($showFreeRatio) : ?>
                        <?php $freeSelected = !$originalSelected && !$hasConfiguredRatios; ?>
                        <label class="btn btn-primary<?= $freeSelected ? ' active free' : ' free' ?>" data-toggle="none_tooltip" data-animation="false" data-original-title="aspectRatio: NaN">
                            <input type="radio" class="sr-only" id="aspectRatio-free" name="aspectRatio" value="NaN"<?= $freeSelected ? ' checked="checked"' : '' ?>><?= rex_i18n::msg('cropper_ratio_free') ?>
                        </label>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
            <div class="cropper-mode-bar">
                <span
                    id="cropper_mode_badge"
                    class="cropper-mode-badge"
                    data-move-label="<?= rex_i18n::msg('cropper_mode_state_move') ?>"
                    data-crop-label="<?= rex_i18n::msg('cropper_mode_state_crop') ?>"
                ><?= rex_i18n::msg('cropper_mode_state_crop') ?></span>
                <p
                    id="cropper_mode_hint"
                    class="cropper-mode-hint"
                    data-move-hint="<?= rex_i18n::msg('cropper_mode_hint_move') ?>"
                    data-crop-hint="<?= rex_i18n::msg('cropper_mode_hint_crop') ?>"
                ><?= rex_i18n::msg('cropper_mode_hint_crop') ?></p>
                <?php if (!$legacyOverlayToolbar) : ?>
                <button
                    type="button"
                    id="cropper_toolbar_toggle"
                    class="btn btn-default cropper-toolbar-toggle"
                    aria-expanded="true"
                    aria-controls="cropper-toolbar-buttons cropper-toolbar-toggles"
                    data-expanded-label="<?= rex_i18n::msg('cropper_toolbar_collapse') ?>"
                    data-collapsed-label="<?= rex_i18n::msg('cropper_toolbar_expand') ?>"
                    data-toggle="tooltip"
                    data-animation="false"
                    data-original-title="<?= rex_i18n::msg('cropper_toolbar_collapse') ?>"
                >
                    <span class="fa fa-ellipsis-v" aria-hidden="true"></span>
                </button>
                <?php endif; ?>
            </div>
            </div>
            </div>
        </section>

        <aside id="cropper-sidebar" class="cropper-sidebar">
            <section class="cropper-sidebar-panel cropper-preview-panel">
                <div class="cropper-sidebar-header">
                    <h4><?= rex_i18n::msg('cropper_preview_title') ?></h4>
                </div>
                <div class="cropper-preview-frame">
                    <img id="cropper_live_preview" class="cropper-preview-image" alt="" hidden>
                    <p id="cropper_preview_empty" class="cropper-preview-empty"><?= rex_i18n::msg('cropper_preview_empty') ?></p>
                </div>
            </section>

            <section class="cropper-sidebar-panel cropper-meta-panel">
                <div class="cropper-sidebar-header">
                    <h4><?= rex_i18n::msg('cropper_info_title') ?></h4>
                </div>
                <dl class="cropper-meta-list">
                    <div>
                        <dt><?= rex_i18n::msg('cropper_info_image') ?></dt>
                        <dd data-cropper-output="image-size"><?= $mediaPoolWidth ?> x <?= $mediaPoolHeight ?> px</dd>
                    </div>
                    <div>
                        <dt><?= rex_i18n::msg('cropper_info_selection') ?></dt>
                        <dd data-cropper-output="selection-size">0 x 0 px</dd>
                    </div>
                    <div>
                        <dt><?= rex_i18n::msg('cropper_info_position') ?></dt>
                        <dd data-cropper-output="selection-position">x: 0, y: 0</dd>
                    </div>
                    <div>
                        <dt><?= rex_i18n::msg('cropper_info_ratio') ?></dt>
                        <dd data-cropper-output="selection-ratio">-</dd>
                    </div>
                    <div>
                        <dt><?= rex_i18n::msg('cropper_info_transform') ?></dt>
                        <dd data-cropper-output="transform-state">R 0 / X 1 / Y 1</dd>
                    </div>
                </dl>
            </section>
        </aside>
    </div>
</div>
<input type="hidden" id="dataX" name="x">
<input type="hidden" id="dataY" name="y">
<input type="hidden" id="dataWidth" name="width">
<input type="hidden" id="dataHeight" name="height">
<input type="hidden" id="dataCanvasWidth" name="canvas_width">
<input type="hidden" id="dataCanvasHeight" name="canvas_height">
<input type="hidden" id="dataRotate" name="rotate">
<input type="hidden" id="dataScaleX" name="scaleX">
<input type="hidden" id="dataScaleY" name="scaleY">
