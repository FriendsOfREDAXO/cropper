<?php

class rex_yform_value_media_crop extends rex_yform_value_abstract
{
    public function enterObject(): void
    {
        $warnings = [];
        $old_value = $this->getValue();
        $required = $this->getElement('required');

        // Ensure string value
        if (!is_string($old_value)) {
            $old_value = '';
        }

        // Get media category
        $media_category_id = 0;
        if ($cat_id = $this->getElement('category')) {
            $media_category_id = (int) $cat_id;
            if (!rex_media_category::get($media_category_id)) {
                $media_category_id = 0;
            }
        }

        if ($this->params['send']) {
            $delete_field = md5($this->getFieldName('delete'));

            // Handle delete request - only possible if not required
            if (!$required && rex_post($delete_field, 'int') == 1) {
                $this->setValue('');

                // Try to delete the media file if it exists
                if ($old_value !== '') {
                    try {
                        rex_media_service::deleteMedia($old_value);
                    } catch (rex_exception $e) {
                        // Only show warning if file exists but can't be deleted
                        if (strpos($e->getMessage(), 'pool_file_not_found') === false) {
                            $warnings[] = $e->getMessage();
                        }
                    }
                }
            }

            // Handle file upload
            $file_field = 'file_' . $this->getFieldId();
            if (isset($_FILES[$file_field]) && $_FILES[$file_field]['size'] > 0) {
                $file = $_FILES[$file_field];
                $fileName = (string) ($file['name'] ?? '');
                $fileTmpName = (string) ($file['tmp_name'] ?? '');
                $fileError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

                if (UPLOAD_ERR_OK === $fileError) {
                    try {
                        $media_data = [
                            'title' => $fileName,
                            'category_id' => $media_category_id,
                            'file' => [
                                'name' => $fileName,
                                'tmp_name' => $fileTmpName,
                                'error' => $fileError,
                            ],
                        ];

                        $result = rex_media_service::addMedia($media_data, true);

                        if (isset($result['ok'], $result['filename']) && 1 === (int) $result['ok'] && is_string($result['filename'])) {
                            $filename = $result['filename'];

                            if ($media_category_id > 0) {
                                $update_data = [
                                    'category_id' => $media_category_id,
                                    'title' => $fileName,
                                ];

                                rex_media_service::updateMedia($filename, $update_data);
                            }

                            $this->setValue($filename);

                            if ($old_value !== '' && $old_value !== $filename) {
                                try {
                                    rex_media_service::deleteMedia($old_value);
                                } catch (rex_exception $e) {
                                    if (strpos($e->getMessage(), 'pool_file_not_found') === false) {
                                        $warnings[] = $e->getMessage();
                                    }
                                }
                            }
                        } else {
                            $warnings[] = isset($result['messages']) && is_array($result['messages'])
                                ? implode(', ', array_map('strval', $result['messages']))
                                : rex_i18n::msg('pool_file_upload_error');
                        }
                    } catch (rex_exception $e) {
                        $warnings[] = $e->getMessage();
                    }
                } else {
                    $warnings[] = rex_i18n::msg('yform_media_crop_error_on_upload') . ' ' . $fileError;
                }
            } elseif ($required && '' === $old_value) {
                $warnings[] = rex_i18n::msg('yform_values_required_msg');
            }
        } else {
            $this->setValue($old_value);
        }

        if ($this->params['send'] && count($warnings) > 0) {
            $this->params['warning'][$this->getId()] = $this->params['error_class'];
            $this->params['warning_messages'][$this->getId()] = implode(', ', $warnings);
        }

        if ($this->params['send']) {
            $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
            if ($this->saveInDb()) {
                $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
            }
        }

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse(['value.media_crop.tpl.php']);
        }
    }

    /** @return array<string, mixed> */
    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'media_crop',
            'values' => [
                'name' => [
                    'type' => 'name',
                    'label' => rex_i18n::msg('yform_values_defaults_name'),
                ],
                'label' => [
                    'type' => 'text',
                    'label' => rex_i18n::msg('yform_values_defaults_label'),
                ],
                'required' => [
                    'type' => 'boolean',
                    'label' => rex_i18n::msg('yform_media_crop_required'),
                ],
                'category' => [
                    'type' => 'text',
                    'label' => rex_i18n::msg('yform_media_crop_category'),
                    'notice' => rex_i18n::msg('yform_media_crop_category_notice'),
                ],
                'crop_width' => [
                    'type' => 'integer',
                    'label' => rex_i18n::msg('yform_media_crop_width'),
                    'notice' => rex_i18n::msg('yform_media_crop_width_notice'),
                    'min' => 1,
                    'default' => 800,
                ],
                'crop_height' => [
                    'type' => 'integer',
                    'label' => rex_i18n::msg('yform_media_crop_height'),
                    'notice' => rex_i18n::msg('yform_media_crop_height_notice'),
                    'min' => 1,
                    'default' => 450,
                ],
                'preview_width' => [
                    'type' => 'text',
                    'label' => rex_i18n::msg('yform_media_crop_preview_width'),
                    'notice' => rex_i18n::msg('yform_media_crop_preview_width_notice'),
                ],
                'preview_height' => [
                    'type' => 'integer',
                    'label' => rex_i18n::msg('yform_media_crop_preview_height'),
                    'notice' => rex_i18n::msg('yform_media_crop_preview_height_notice'),
                    'min' => 300,
                    'default' => 450,
                ],
                'preview_style' => [
                    'type' => 'text',
                    'label' => rex_i18n::msg('yform_media_crop_preview_style'),
                    'notice' => rex_i18n::msg('yform_media_crop_preview_style_notice'),
                ],
                'notice' => [
                    'type' => 'text',
                    'label' => rex_i18n::msg('yform_values_defaults_notice'),
                ],
            ],
            'description' => rex_i18n::msg('yform_media_crop_description'),
            'db_type' => ['varchar(191)'],
            'famous' => false,
            'search' => false,
        ];
    }
}
