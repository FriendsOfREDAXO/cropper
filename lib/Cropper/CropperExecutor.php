<?php
/**
 * User: joachimdoerr
 * Date: 2019-02-17
 * Time: 10:48
 */

namespace Cropper;

use rex;
use rex_media;
use rex_media_cache;
use rex_media_manager;
use rex_path;
use rex_sql;

class CropperExecutor
{
    /**
     * @var \Zebra_Image
     */
    private $zebraImage;

    /**
     * @var string
     */
    private $originalFilename;

    /**
     * @var string
     */
    private $tempFilename;

    /**
     * @var string
     */
    private $filename;

    /**
     * @var string
     */
    private $category;

    /**
     * @var boolean
     */
    private $update;

    /**
     * @var array
     */
    private $parameter;

    /**
     * @author Joachim Doerr
     * @param array $parameter
     */
    public function __construct(array $parameter = array())
    {
        $this->zebraImage = new \Zebra_Image();
        $this->update = !isset($parameter['create_new_image']);
        $this->parameter = $parameter;

        foreach ($parameter as $key => $value) {
            switch ($key) {
                case 'jpg_quality':
                    $this->zebraImage->jpeg_quality = $value;
                    break;
                case 'png_compression':
                    $this->zebraImage->png_compression = $value;
                    break;
                case 'media_name':
                    $this->originalFilename = $value;
                    $this->tempFilename = pathinfo($value, PATHINFO_FILENAME) . '_cropper_' . md5($value . microtime()) . '.' . pathinfo($value, PATHINFO_EXTENSION);
                    break;
                case 'new_file_name':
                    if (isset($parameter['new_file_extension'])) {
                        $this->filename = rex_mediapool_filename($value . '.' . $parameter['new_file_extension'], true);
                    }
                    break;
                case 'rex_file_category':
                    $this->category = $value;
                    break;
            }
        }

        $this->zebraImage->preserve_time = false;

        if (empty($this->filename)) {
            $this->filename = rex_mediapool_filename($this->originalFilename, true);
        }
    }

    /**
     * @return rex_media|null
     * @throws CroppingException
     * @throws \rex_sql_exception
     */
    private function mediaWriteInitial()
    {
        if (!file_exists(rex_path::media($this->originalFilename))) {
            throw new CroppingException('File ' . rex_path::media($this->originalFilename) . ' does not exist');
        }

        if ($this->update) {
            return rex_media::get($this->originalFilename);
        } else {
            if (copy(rex_path::media($this->originalFilename), rex_path::media($this->tempFilename))) {
                $originalMedia = rex_media::get($this->originalFilename);

                $FILE = array(
                    'name' => $this->tempFilename,
                    'size' => filesize(rex_path::media($this->originalFilename)),
                    'type' => mime_content_type(rex_path::media($this->originalFilename)),
                );

                // Dynamically fetch all metadata from the original media
                $metadata = [];
                $sql = rex_sql::factory();
                $sql->setQuery('SELECT * FROM ' . rex::getTablePrefix() . 'media WHERE filename = ?', [$this->originalFilename]);

                if ($sql->getRows() == 1) {
                    foreach ($sql->getFieldNames() as $fieldName) {
                        if (!in_array($fieldName, ['id', 'filename', 'originalname', 'filetype', 'filesize', 'width', 'height', 'category_id'])) {
                            $metadata[$fieldName] = $sql->getValue($fieldName);
                        }
                    }
                }

                $return = rex_mediapool_saveMedia($FILE, $this->category, $metadata, rex::getUser()->getValue('login'), false);

                if ($return['ok'] == 1) {
                    $media = rex_media::get($this->tempFilename);
                    if ($media instanceof rex_media && rename(rex_path::media($this->tempFilename), rex_path::media($this->filename))) {
                        $sql = rex_sql::factory();
                        $sql->setTable(rex::getTablePrefix() . 'media');
                        $sql->setWhere(['id' => $media->getId()]);
                        $sql->setValue('originalname', $this->originalFilename);
                        $sql->setValue('filename', $this->filename);
                        $sql->addGlobalUpdateFields(rex::getUser()->getValue('login'));
                        $sql->update();

                        // Update metadata for the new media
                        foreach ($metadata as $fieldName => $value) {
                            $sql->setValue($fieldName, $value);
                        }
                        $sql->update();

                        return rex_media::get($this->filename);
                    }
                }
            }
        }

        throw new CroppingException('File ' . rex_path::media($this->originalFilename) . ' copy failed');
    }

    public function crop()
    {
        $media = $this->mediaWriteInitial();

        if (!$media instanceof rex_media) {
            throw new CroppingException('Initial media file write failed');
        }

        $this->zebraImage->source_path = rex_path::media($media->getFileName());
        $this->zebraImage->target_path = rex_path::media($media->getFileName());
        $zebraErrors = array();

        // x
        // y
        // width
        // height
        // rotate
        // scaleX -> 1 == normal | -1 == reflect
        // scaleY -> 1 == normal | -1 == reflect

        // flip image
        if ($this->parameter['scaleX'] == -1 && $this->parameter['scaleY'] == 1) {
            $this->zebraImage->flip_horizontal();
            $zebraErrors[] = $this->zebraImage->error;
        } else if ($this->parameter['scaleX'] == 1 && $this->parameter['scaleY'] == -1) {
            $this->zebraImage->flip_vertical();
            $zebraErrors[] = $this->zebraImage->error;
        } else if ($this->parameter['scaleX'] == -1 && $this->parameter['scaleY'] == -1) {
            $this->zebraImage->flip_both();
            $zebraErrors[] = $this->zebraImage->error;
        }

        // rotate image
        if ($this->parameter['rotate'] != 0) {
            $this->zebraImage->rotate($this->parameter['rotate']);
            $zebraErrors[] = $this->zebraImage->error;
        }

        // crop image
        if ($this->parameter['width'] > 0 && $this->parameter['height'] > 0) {
            $this->zebraImage->crop($this->parameter['x'], $this->parameter['y'], ($this->parameter['x'] + $this->parameter['width']), ($this->parameter['y'] + $this->parameter['height']));
            $zebraErrors[] = $this->zebraImage->error;
        }

        rex_media_cache::delete($media->getFileName());
        rex_media_manager::deleteCache(pathinfo($media->getFileName(), PATHINFO_FILENAME));

        $FILEINFOS = [];
        $FILEINFOS['rex_file_category'] = $media->getCategoryId();
        $FILEINFOS['file_id'] = $media->getId();
        $FILEINFOS['title'] = $media->getTitle();
        $FILEINFOS['filename'] = $media->getFileName();
        $FILEINFOS['filetype'] = $media->getType();

        $result = rex_mediapool_updateMedia(array('name' => 'none'), $FILEINFOS, rex::getUser()->getValue('login'));
        $msgType = ($this->update) ? 'updated' : 'created';

        if (isset($result['ok']) && $result['ok'] == 1 && isset($result['filename'])) {
            $media = rex_media::get($result['filename']);
            $msg = 'cropper_successful_' . $msgType;
            $ok = true;

            $size = @getimagesize($this->zebraImage->target_path);

            $sql = rex_sql::factory();
            $sql->setTable(rex::getTable('media'));
            $sql->setWhere(['filename' => $result['filename']]);
            $sql->setValue('filesize', filesize($this->zebraImage->target_path));

            if ($size) {
                $sql->setValue('width', $size[0]);
                $sql->setValue('height', $size[1]);
            }

            $sql->update();

        } else {
            $msg = 'cropper_failed_' . $msgType;
            $ok = false;
        }

        return array('media' => $media, 'error' => $zebraErrors, 'msg' => $msg, 'ok' => $ok, 'update' => $this->update);
    }
}
