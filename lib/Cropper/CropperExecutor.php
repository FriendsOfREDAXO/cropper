<?php
/**
 * User: joachimdoerr
 * Date: 2019-02-17
 * Time: 10:48
 */

namespace Cropper;


use rex;
use rex_media;
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
     * @author Joachim Doerr
     * @param array $parameter
     */
    public function __construct(array $parameter = array())
    {
        $this->zebraImage = new \Zebra_Image();

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

        if (empty($this->filename)) {
            $this->filename = rex_mediapool_filename($this->originalFilename, true);
        }
    }

    /**
     * @return array
     * @throws CroppingException
     * @throws \rex_sql_exception
     * @author Joachim Doerr
     */
    private function firstWrite()
    {
        if(!file_exists(rex_path::media($this->originalFilename))) {
            throw new CroppingException('File ' . rex_path::media($this->originalFilename) . ' not exist');
        }

        if (copy(rex_path::media($this->originalFilename),rex_path::media($this->tempFilename))) {

            $FILE = array(
                'name' => $this->tempFilename,
                'size' => filesize(rex_path::media($this->originalFilename)),
                'type' => mime_content_type(rex_path::media($this->originalFilename)),
            );

            $return = rex_mediapool_saveMedia($FILE, $this->category, ['title' => ''], rex::getUser()->getValue('login'), false);

            dump($return);
            if ($return['ok'] == 1) {
                $media = rex_media::get($this->tempFilename);
                if ($media instanceof rex_media && rename(rex_path::media($this->tempFilename), rex_path::media($this->filename))) {
                    $sql = rex_sql::factory();
                    // $FILESQL->setDebug();
                    $sql->setTable(rex::getTablePrefix() . 'media');
                    $sql->setWhere(['id' => $media->getId()]);
                    $sql->setValue('originalname', $this->originalFilename);
                    $sql->setValue('filename', $this->filename);
                    $sql->addGlobalUpdateFields(rex::getUser()->getValue('login'));
                    $sql->update();
                }

                return $return;
            }
        }

        throw new CroppingException('File ' . rex_path::media($this->originalFilename) . ' copy failed');
    }

    public function crop()
    {
        $this->firstWrite();
    }

}