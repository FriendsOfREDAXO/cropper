<?php
        $mediaUrl = $this->mediaUrl;
        $media = $this->media;
        $mtime = $this->mtime;
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

        $originalRatio = $media->getHeight() > 0
            ? (string) ($media->getWidth() / $media->getHeight())
            : 'NaN';
?>

<div
    id="cropper-workspace"
    class="cropper-workspace"
    data-media-width="<?= (int) $media->getWidth() ?>"
    data-media-height="<?= (int) $media->getHeight() ?>"
>
    <div class="cropper-hero">
        <section class="cropper-main-panel">
            <div class="cropper-stage-card">
                <div class="cropper-stage-header">
                    <div>
                        <span class="cropper-stage-kicker"><?= rex_i18n::msg('cropper_workspace_title') ?></span>
                        <p class="cropper-stage-title"><?= $this->escape($media->getFileName()) ?></p>
                    </div>
                </div>

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
                </div>
            </div>

            <div class="docs-buttons">
                <div class="cropper-toolbar-section">
                    <span class="cropper-toolbar-label"><?= rex_i18n::msg('cropper_toolbar_mode') ?></span>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-primary active" data-method="setDragMode" data-option="move" data-toggle="tooltip" data-original-title="<?= rex_i18n::msg('cropper_action_move') ?>" data-animation="false">
                            <span class="fa fa-arrows"></span>
                        </button>
                        <button type="button" class="btn btn-primary" data-method="setDragMode" data-option="crop" data-toggle="tooltip" data-original-title="<?= rex_i18n::msg('cropper_action_select') ?>" data-animation="false">
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
                </div>
            </div>

            <div class="docs-toggles">
                <div class="cropper-toolbar-section">
                    <span class="cropper-toolbar-label"><?= rex_i18n::msg('cropper_toolbar_aspect_ratio') ?></span>
                    <div class="btn-group cropper-ratio-group" data-toggle="buttons">
                        <label class="btn btn-primary" data-toggle="none_tooltip" data-animation="false" data-original-title="aspectRatio: <?= $media->getWidth() . ' / ' . $media->getHeight() ;?>">
                            <input type="radio" class="sr-only" id="aspectRatio-1" name="aspectRatio" value="<?= $originalRatio ?>"><?= rex_i18n::msg('cropper_ratio_original') ?>
                        </label>

                        <?php foreach ($ratios AS $i => $ratio) :?>
                        <label class="btn btn-primary" data-toggle="none_tooltip" data-animation="false" data-original-title="aspectRatio: <?= $ratio['w']?> / <?= $ratio['h']?>">
                            <input type="radio" class="sr-only" id="aspectRatio<?= $i;?>" name="aspectRatio" value="<?= $ratio['r'];?>"><?= $ratio['w']?>:<?= $ratio['h']?>
                        </label>
                        <?php endforeach;?>

                        <label class="btn btn-primary active free" data-toggle="none_tooltip" data-animation="false" data-original-title="aspectRatio: NaN">
                            <input type="radio" class="sr-only" id="aspectRatio-free" name="aspectRatio" value="NaN" checked="checked"><?= rex_i18n::msg('cropper_ratio_free') ?>
                        </label>
                    </div>
                </div>

                <div class="cropper-toolbar-section cropper-toolbar-section--compact">
                    <span class="cropper-toolbar-label"><?= rex_i18n::msg('cropper_toolbar_presets') ?></span>
                    <div class="cropper-preset-control">
                        <select class="form-control" id="cropper_preset_select" name="selectionPreset">
                            <option value=""><?= rex_i18n::msg('cropper_preset_select') ?></option>
                            <option value="0.35"><?= rex_i18n::msg('cropper_preset_focus') ?></option>
                            <option value="0.55"><?= rex_i18n::msg('cropper_preset_balanced') ?></option>
                            <option value="0.78"><?= rex_i18n::msg('cropper_preset_fill') ?></option>
                        </select>
                    </div>

                    <div class="cropper-settings dropdown">
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
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <aside class="cropper-sidebar">
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
                        <dd data-cropper-output="image-size"><?= (int) $media->getWidth() ?> x <?= (int) $media->getHeight() ?> px</dd>
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
