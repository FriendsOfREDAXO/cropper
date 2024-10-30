<?php

class rex_yform_value_media_crop extends rex_yform_value_abstract
{
    public function enterObject()
    {
        $warnings = [];
        $old_value = $this->getValue();

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

            // Handle delete request
            if (isset($_POST[$delete_field]) && $_POST[$delete_field] == 1) {
                // First clear the value
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
            else {
                $file_field = 'file_' . $this->getFieldId();
                
                if (isset($_FILES[$file_field]) && $_FILES[$file_field]['size'] > 0) {
                    $file = $_FILES[$file_field];

                    if ($file['error'] == 0) {
                        try {
                            // Upload the new file
                            $media_data = [
                                'title' => $file['name'],
                                'category_id' => $media_category_id,
                                'file' => [
                                    'name' => $file['name'],
                                    'tmp_name' => $file['tmp_name'],
                                    'type' => $file['type'],
                                    'size' => $file['size'],
                                    'error' => $file['error']
                                ]
                            ];
                            
                            // Add to media pool
                            $result = rex_media_service::addMedia($media_data, true);

                            if ($result['ok']) {
                                $filename = $result['filename'];

                                // Update Media - Set category
                                if ($media_category_id > 0) {
                                    $update_data = [
                                        'category_id' => $media_category_id,
                                        'title' => $file['name']
                                    ];

                                    rex_media_service::updateMedia($filename, $update_data);
                                }

                                // Set new value
                                $this->setValue($filename);

                                // Try to delete the old file
                                if ($old_value !== '' && $old_value !== $filename) {
                                    try {
                                        rex_media_service::deleteMedia($old_value);
                                    } catch (rex_exception $e) {
                                        // Ignore file not found errors
                                        if (strpos($e->getMessage(), 'pool_file_not_found') === false) {
                                            $warnings[] = $e->getMessage();
                                        }
                                    }
                                }
                            } else {
                                $warnings[] = implode(', ', $result['messages']);
                            }

                        } catch (rex_exception $e) {
                            $warnings[] = $e->getMessage();
                        }
                    } else {
                        $warnings[] = rex_i18n::msg('yform_media_crop_error_on_upload') . $file['error'];
                    }
                }
            }
        } else {
            $this->setValue($old_value);
        }

        // Handle warnings
        if ($this->params['send'] && count($warnings) > 0) {
            $this->params['warning'][$this->getId()] = $this->params['error_class'];
            $this->params['warning_messages'][$this->getId()] = implode(', ', $warnings);
        }

        // Save to value pool
        if ($this->params['send']) {
            $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
            if ($this->saveInDb()) {
                $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
            }
        }

        // Output
        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse(['value.media_crop.tpl.php']);
        }
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'media_crop',
            'values' => [
                'name' => [
                    'type' => 'name',
                    'label' => rex_i18n::msg('yform_values_defaults_name')
                ],
                'label' => [
                    'type' => 'text',
                    'label' => rex_i18n::msg('yform_values_defaults_label')
                ],
                'required' => [
                    'type' => 'boolean',
                    'label' => rex_i18n::msg('yform_media_crop_required')
                ],
                'category' => [
                    'type' => 'text',
                    'label' => rex_i18n::msg('yform_media_crop_category'),
                    'notice' => rex_i18n::msg('yform_media_crop_category_notice')
                ],
                'crop_width' => [
                    'type' => 'text',
                    'label' => rex_i18n::msg('yform_media_crop_width'),
                    'notice' => rex_i18n::msg('yform_media_crop_width_notice')
                ],
                'crop_height' => [
                    'type' => 'text',
                    'label' => rex_i18n::msg('yform_media_crop_height'),
                    'notice' => rex_i18n::msg('yform_media_crop_height_notice')
                ],
                'preview_width' => [
                    'type' => 'text',
                    'label' => rex_i18n::msg('yform_media_crop_preview_width'),
                    'notice' => rex_i18n::msg('yform_media_crop_preview_width_notice')
                ],
                'preview_height' => [
                    'type' => 'text',
                    'label' => rex_i18n::msg('yform_media_crop_preview_height'),
                    'notice' => rex_i18n::msg('yform_media_crop_preview_height_notice')
                ],
                'preview_style' => [
                    'type' => 'text',
                    'label' => rex_i18n::msg('yform_media_crop_preview_style'),
                    'notice' => rex_i18n::msg('yform_media_crop_preview_style_notice')
                ],
                'notice' => [
                    'type' => 'text',
                    'label' => rex_i18n::msg('yform_values_defaults_notice')
                ],
            ],
            'description' => rex_i18n::msg('yform_media_crop_description'),
            'db_type' => ['varchar(191)'],
            'famous' => false,
            'search' => false,
        ];
    }
}
