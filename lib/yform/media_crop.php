<?php

class rex_yform_value_media_crop extends rex_yform_value_abstract
{
    public function enterObject()
    {
        if (!is_string($this->getValue())) {
            $this->setValue('');
        }

        // Get media category
        $media_category_id = 0;
        if ($cat_id = $this->getElement('category')) {
            $media_category_id = (int) $cat_id;
            if (!rex_media_category::get($media_category_id)) {
                $media_category_id = 0;
            }
        }

        $warnings = [];

        if ($this->params['send']) {
            // Handle delete request
            if (isset($_POST[$this->getFieldName('delete')]) && $_POST[$this->getFieldName('delete')] == 1) {
                $this->setValue('');
            }
            // Handle file upload
            else {
                $file_field = 'file_' . $this->getFieldId();
                
                if (isset($_FILES[$file_field]) && $_FILES[$file_field]['size'] > 0) {
                    $file = $_FILES[$file_field];

                    if ($file['error'] == 0) {
                        try {
                            // Add Media - Upload new file
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

                                $this->setValue($filename);
                            } else {
                                $warnings[] = implode(', ', $result['messages']);
                            }

                        } catch (rex_exception $e) {
                            $warnings[] = $e->getMessage();
                        }
                    } else {
                        $warnings[] = 'Fehler beim Datei-Upload: ' . $file['error'];
                    }
                }
            }
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
                'name'      => ['type' => 'name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label'     => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_label')],
                'required'  => ['type' => 'boolean', 'label' => 'Pflichtfeld'],
                'category' => ['type' => 'text', 'label' => 'Medienkategorie ID'],
                'crop_width' => ['type' => 'text', 'label' => 'Zielbreite für Crop'],
                'crop_height' => ['type' => 'text', 'label' => 'Zielhöhe für Crop'],
                'preview_width' => ['type' => 'text', 'label' => 'Breite der Vorschau (z.B: 800 oder 100%)'],
                'preview_height' => ['type' => 'text', 'label' => 'Maximale Höhe der Vorschau in px'],
                'preview_style' => ['type' => 'text', 'label' => 'Zusätzliche CSS Styles'],
                'notice'    => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => 'Ein Medienfeld mit Crop-Funktion',
            'db_type' => ['varchar(191)']
        ];
    }
}
