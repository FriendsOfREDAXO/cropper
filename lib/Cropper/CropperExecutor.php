<?php

/**
 * User: joachimdoerr
 * Date: 2019-02-17
 * Time: 10:48.
 * @package Cropper
 */

namespace FriendsOfRedaxo\Cropper\Cropper;

use Exception;
use InvalidArgumentException;
use rex;
use rex_file;
use rex_media;
use rex_media_cache;
use rex_media_manager;
use rex_path;
use rex_sql;
use rex_sql_exception;
use rex_user;

use const PATHINFO_EXTENSION;
use const PATHINFO_FILENAME;

class CropperExecutor
{
    public const MSG_SUCCESSFUL_CREATED = 'cropper_successful_created';
    public const MSG_SUCCESSFUL_UPDATED = 'cropper_successful_updated';
    public const MSG_FAILED_CREATED = 'cropper_failed_created';
    public const MSG_FAILED_UPDATED = 'cropper_failed_updated';

    private ZebraImageAdapter $zebraImage;

    private string $originalFilename;

    private string $tempFilename;

    private string $filename;

    private int $category;

    private bool $update;

    /** @var array<string, scalar|null> */
    private array $parameter;

    /**
     * @param array<string, scalar|null> $parameter
     * @throws InvalidArgumentException
     */
    public function __construct(array $parameter = [])
    {
        $this->zebraImage = new ZebraImageAdapter();
        $this->update = !isset($parameter['create_new_image']) || !(bool) $parameter['create_new_image'];
        $this->parameter = $parameter;

        $this->zebraImage->setJpegQuality((int) ($parameter['jpg_quality'] ?? 90));
        $this->zebraImage->setPngCompression((int) ($parameter['png_compression'] ?? 9));
        $this->originalFilename = (string) ($parameter['media_name'] ?? '');

        if ('' !== $this->originalFilename) {
            $this->tempFilename = pathinfo($this->originalFilename, PATHINFO_FILENAME) . '_cropper_' . md5($this->originalFilename . microtime()) . '.' . pathinfo($this->originalFilename, PATHINFO_EXTENSION);
        } else {
            $this->tempFilename = '';
        }

        if (isset($parameter['new_file_name'], $parameter['new_file_extension'])) {
            $this->filename = rex_mediapool_filename((string) $parameter['new_file_name'] . '.' . (string) $parameter['new_file_extension'], true);
        } else {
            $this->filename = '';
        }

        $this->category = (int) ($parameter['rex_file_category'] ?? 0);
        $this->zebraImage->setPreserveTime(false);

        if ('' === $this->filename) {
            $this->filename = rex_mediapool_filename($this->originalFilename, true);
        }

        if ('' === $this->originalFilename) {
            throw new InvalidArgumentException("'media_name' parameter is required.");
        }
    }

    private function getCurrentUser(): rex_user
    {
        $user = rex::getUser();
        if (!$user instanceof rex_user) {
            throw new CroppingException('No backend user available.');
        }

        return $user;
    }

    private function getIntParameter(string $key, int $default = 0): int
    {
        return (int) ($this->parameter[$key] ?? $default);
    }

    /**
     * @throws CroppingException
     * @throws rex_sql_exception
     */
    private function mediaWriteInitial(): rex_media
    {
        $sourcePath = rex_path::media($this->originalFilename);

        if (!is_file($sourcePath)) {
            throw new CroppingException('File ' . $sourcePath . ' not exist');
        }

        if ($this->update) {
            $existingMedia = rex_media::get($this->originalFilename);
            if ($existingMedia instanceof rex_media) {
                return $existingMedia;
            }

            throw new CroppingException('Initial media file write failed');
        }

        $targetPath = rex_path::media($this->filename);
        if (!rex_file::copy($sourcePath, $targetPath)) {
            throw new CroppingException('File copy failed: ' . $sourcePath . ' -> ' . $targetPath);
        }

        $original = rex_media::get($this->originalFilename);
        $title = $original instanceof rex_media ? $original->getTitle() : '';
        $user = $this->getCurrentUser();

        $return = rex_mediapool_syncFile(
            $this->filename,
            $this->category,
            $title,
            filesize($targetPath),
            rex_file::mimeType($targetPath),
            $user->getValue('login'),
        );

        if (!(isset($return['ok']) && 1 === (int) $return['ok'])) {
            rex_file::delete($targetPath);
            $message = isset($return['msg']) && is_string($return['msg']) ? $return['msg'] : 'unknown error';
            throw new CroppingException('Create media entry failed: ' . $message);
        }

        $createdMedia = rex_media::get($this->filename);
        if (!$createdMedia instanceof rex_media) {
            rex_file::delete($targetPath);
            throw new CroppingException('Created media not found: ' . $this->filename);
        }

        $sql = rex_sql::factory();
        $sql->setTable(rex::getTablePrefix() . 'media');
        $sql->setWhere(['id' => $createdMedia->getId()]);
        $sql->setValue('originalname', $this->originalFilename);
        $sql->addGlobalUpdateFields($user->getValue('login'));
        $sql->update();

        return $createdMedia;
    }

    /**
     * @return array{media: rex_media|null, error: list<int>, msg: string, ok: bool, update: bool}
     * @throws CroppingException
     */
    public function crop(): array
    {
        $media = $this->mediaWriteInitial();

        $this->zebraImage->setSourcePath(rex_path::media($media->getFileName()));
        $this->zebraImage->setTargetPath(rex_path::media($media->getFileName()));
        $zebraErrors = [];
        $scaleX = $this->getIntParameter('scaleX', 1);
        $scaleY = $this->getIntParameter('scaleY', 1);
        $rotate = $this->getIntParameter('rotate');
        $cropX = $this->getIntParameter('x');
        $cropY = $this->getIntParameter('y');
        $cropWidth = $this->getIntParameter('width');
        $cropHeight = $this->getIntParameter('height');
        $canvasWidth = $this->getIntParameter('canvas_width');
        $canvasHeight = $this->getIntParameter('canvas_height');

        // flip image
        if (-1 === $scaleX && 1 === $scaleY) {
            $this->zebraImage->flipHorizontal();
        } elseif (1 === $scaleX && -1 === $scaleY) {
            $this->zebraImage->flipVertical();
        } elseif (-1 === $scaleX && -1 === $scaleY) {
            $this->zebraImage->flipBoth();
        }
        $zebraErrors[] = $this->zebraImage->getError();

        // rotate image
        if (0 !== $rotate) {
            $this->zebraImage->rotate($rotate);
            $zebraErrors[] = $this->zebraImage->getError();
        }

        // crop image
        if ($cropWidth > 0 && $cropHeight > 0) {
            $imageSize = @getimagesize($this->zebraImage->getTargetPath());
            if (is_array($imageSize)) {
                $imageWidth = (int) $imageSize[0];
                $imageHeight = (int) $imageSize[1];

                if ($canvasWidth > 0 && $canvasHeight > 0) {
                    $cropX = (int) round($cropX * ($imageWidth / $canvasWidth));
                    $cropY = (int) round($cropY * ($imageHeight / $canvasHeight));
                    $cropWidth = (int) round($cropWidth * ($imageWidth / $canvasWidth));
                    $cropHeight = (int) round($cropHeight * ($imageHeight / $canvasHeight));
                }

                $cropX = max(0, min($cropX, $imageWidth));
                $cropY = max(0, min($cropY, $imageHeight));
                $cropWidth = max(0, min($cropWidth, $imageWidth - $cropX));
                $cropHeight = max(0, min($cropHeight, $imageHeight - $cropY));
            }

            $this->zebraImage->crop(
                $cropX,
                $cropY,
                $cropX + $cropWidth,
                $cropY + $cropHeight,
            );
            $zebraErrors[] = $this->zebraImage->getError();
        }

        rex_media_cache::delete($media->getFileName());
        rex_media_manager::deleteCache(pathinfo($media->getFileName(), PATHINFO_FILENAME));

        $FILEINFOS = [];
        $FILEINFOS['rex_file_category'] = $media->getCategoryId();
        $FILEINFOS['file_id'] = $media->getId();
        $FILEINFOS['title'] = $media->getTitle();
        $FILEINFOS['filename'] = $media->getFileName();
        $FILEINFOS['filetype'] = $media->getType();

        $result = rex_mediapool_updateMedia(['name' => 'none'], $FILEINFOS, $this->getCurrentUser()->getValue('login'));
        $msgType = ($this->update) ? self::MSG_SUCCESSFUL_UPDATED : self::MSG_SUCCESSFUL_CREATED;

        if (isset($result['ok']) && 1 == $result['ok'] && isset($result['filename'])) {
            $media = is_string($result['filename']) ? rex_media::get($result['filename']) : null;
            $msg = $msgType;
            $ok = true;

            $targetPath = $this->zebraImage->getTargetPath();
            $size = getimagesize($targetPath);
            if (false !== $size) {
                $sql = rex_sql::factory();
                $sql->setTable(rex::getTable('media'));
                $sql->setWhere(['filename' => (string) $result['filename']]);
                $sql->setValue('filesize', filesize($targetPath));
                $sql->setValue('width', $size[0]);
                $sql->setValue('height', $size[1]);
                $sql->update();
            }
        } else {
            $media = null;
            $msg = ($this->update) ? self::MSG_FAILED_UPDATED : self::MSG_FAILED_CREATED;
            $ok = false;
        }

        $filteredErrors = [];
        foreach ($zebraErrors as $error) {
            if (0 !== $error) {
                $filteredErrors[] = $error;
            }
        }

        return [
            'media' => $media,
            'error' => $filteredErrors,
            'msg' => $msg,
            'ok' => $ok,
            'update' => $this->update,
        ];
    }
}
