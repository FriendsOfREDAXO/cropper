<?php
/**
 * User: joachimdoerr
 * Date: 2019-02-17
 * Time: 10:48
 * @package Cropper
 */

namespace Cropper;

use rex;
use rex_media;
use rex_media_cache;
use rex_media_manager;
use rex_path;
use rex_sql;
use stefangabos\Zebra_Image\Zebra_Image; // Import the Zebra_Image class

class CroppingException extends \Exception {}

class CropperExecutor
{
    const MSG_SUCCESSFUL_CREATED = 'cropper_successful_created';
    const MSG_SUCCESSFUL_UPDATED = 'cropper_successful_updated';
    const MSG_FAILED_CREATED = 'cropper_failed_created';
    const MSG_FAILED_UPDATED = 'cropper_failed_updated';

    /**
     * @var Zebra_Image
     */
    private Zebra_Image $zebraImage; // Use the imported class

    /**
     * @var string
     */
    private string $originalFilename;

    /**
     * @var string
     */
    private string $tempFilename;

    /**
     * @var string
     */
    private string $filename;

    /**
     * @var string
     */
    private string $category;

    /**
     * @var bool
     */
    private bool $update;

    /**
     * @var array
     */
    private array $parameter;

    /**
     * @param array $parameter
     * @throws \InvalidArgumentException
     */
    public function __construct(array $parameter = [])
    {
        $this->zebraImage = new Zebra_Image(); // Instantiate using the imported class
        $this->update = !isset($parameter['create_new_image']) || !$parameter['create_new_image'];
        $this->parameter = $parameter;

        $this->zebraImage->jpeg_quality = $parameter['jpg_quality'] ?? 90;
        $this->zebraImage->png_compression = $parameter['png_compression'] ?? 9;
        $this->originalFilename = $parameter['media_name'] ?? '';

        if ($this->originalFilename) {
            $this->tempFilename = pathinfo($this->originalFilename, PATHINFO_FILENAME) . '_cropper_' . md5($this->originalFilename . microtime()) . '.' . pathinfo($this->originalFilename, PATHINFO_EXTENSION);
        }

        if (isset($parameter['new_file_name'], $parameter['new_file_extension'])) {
            $this->filename = rex_mediapool_filename($parameter['new_file_name'] . '.' . $parameter['new_file_extension'], true);
        }

        $this->category = $parameter['rex_file_category'] ?? '';
        $this->zebraImage->preserve_time = false;

        if (empty($this->filename)) {
            $this->filename = rex_mediapool_filename($this->originalFilename, true);
        }

        if (empty($this->originalFilename)) {
            throw new \InvalidArgumentException("'media_name' parameter is required.");
        }
    }

    /**
     * @return rex_media|null
     * @throws CroppingException
     * @throws \rex_sql_exception
     */
    private function mediaWriteInitial(): ?rex_media
    {
        if (!file_exists(rex_path::media($this->originalFilename))) {
            throw new CroppingException('File ' . rex_path::media($this->originalFilename) . ' not exist');
        }

        if ($this->update) {
            return rex_media::get($this->originalFilename);
        }

        if (copy(rex_path::media($this->originalFilename), rex_path::media($this->tempFilename))) {
            $FILE = [
                'name' => $this->tempFilename,
                'size' => filesize(rex_path::media($this->originalFilename)),
                'type' => mime_content_type(rex_path::media($this->originalFilename)),
            ];

            $return = rex_mediapool_saveMedia($FILE, $this->category, ['title' => ''], rex::getUser()->getValue('login'), false);

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

                    if(rex_media::get($this->filename) instanceof rex_media) {
                        return rex_media::get($this->filename); //Safe return
                    }
                }
            }
        }

        throw new CroppingException('File ' . rex_path::media($this->originalFilename) . ' copy failed');
    }

    /**
     * @return array
     * @throws CroppingException
     */
    public function crop(): array
    {
        $media = $this->mediaWriteInitial();

        if (!$media instanceof rex_media) {
            throw new CroppingException('Initial media file write failed');
        }

        $this->zebraImage->source_path = rex_path::media($media->getFileName());
        $this->zebraImage->target_path = rex_path::media($media->getFileName());
        $zebraErrors = [];

        // flip image
        if ($this->parameter['scaleX'] == -1 && $this->parameter['scaleY'] == 1) {
            $this->zebraImage->flip_horizontal();
        } else if ($this->parameter['scaleX'] == 1 && $this->parameter['scaleY'] == -1) {
            $this->zebraImage->flip_vertical();
        } else if ($this->parameter['scaleX'] == -1 && $this->parameter['scaleY'] == -1) {
            $this->zebraImage->flip_both();
        }
        $zebraErrors[] = $this->zebraImage->error; // Capture errors *after* all flip operations


        // rotate image
        if ($this->parameter['rotate'] != 0) {
            $this->zebraImage->rotate($this->parameter['rotate']);
            $zebraErrors[] = $this->zebraImage->error; // Capture errors
        }

        // crop image
        if ($this->parameter['width'] > 0 && $this->parameter['height'] > 0) {
            $this->zebraImage->crop(
                $this->parameter['x'],
                $this->parameter['y'],
                ($this->parameter['x'] + $this->parameter['width']),
                ($this->parameter['y'] + $this->parameter['height'])
            );
            $zebraErrors[] = $this->zebraImage->error; // Capture errors
        }
        //These two lines did nothing useful, and were not used anywhere
        //$imgwidth=$this->parameter['width'];
        //$imgheight=$this->parameter['height'];

        rex_media_cache::delete($media->getFileName());
        rex_media_manager::deleteCache(pathinfo($media->getFileName(), PATHINFO_FILENAME));

        $FILEINFOS = [];
        $FILEINFOS['rex_file_category'] = $media->getCategoryId();
        $FILEINFOS['file_id'] = $media->getId();
        $FILEINFOS['title'] = $media->getTitle();
        $FILEINFOS['filename'] = $media->getFileName();
        $FILEINFOS['filetype'] = $media->getType();

        $result = rex_mediapool_updateMedia(['name' => 'none'], $FILEINFOS, rex::getUser()->getValue('login'));
        $msgType = ($this->update) ? self::MSG_SUCCESSFUL_UPDATED : self::MSG_SUCCESSFUL_CREATED;

        if (isset($result['ok']) && $result['ok'] == 1 && isset($result['filename'])) {
            $media = rex_media::get($result['filename']);
            $msg = $msgType;
            $ok = true;

            $size = getimagesize($this->zebraImage->target_path);
            if ($size !== false) {
                $sql = rex_sql::factory();
                $sql->setTable(rex::getTable('media'));
                $sql->setWhere(['filename' => $result['filename']]);
                $sql->setValue('filesize', filesize($this->zebraImage->target_path));
                $sql->setValue('width', $size[0]);
                $sql->setValue('height', $size[1]);
                $sql->update();
            } else {
                // Handle the error - maybe log it
                // rex_logger::logError(E_WARNING, 'Could not get image size for ' . $this->zebraImage->target_path);
                // Optionally set width/height to 0 or some default
            }

        } else {
            $msg = ($this->update) ? self::MSG_FAILED_UPDATED : self::MSG_FAILED_CREATED;
            $ok = false;
        }

        return [
            'media' => $media,
            'error' => array_filter($zebraErrors), // Filter out empty error codes
            'msg' => $msg,
            'ok' => $ok,
            'update' => $this->update
        ];
    }
}