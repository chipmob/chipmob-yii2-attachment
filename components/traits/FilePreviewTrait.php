<?php

namespace chipmob\attachment\components\traits;

use chipmob\attachment\models\File;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @property array $initialPreview Теги для предпросмотра файлов: img для картинок (.file-preview-image), div для остальных (.file-preview-other)
 * @property array $initialPreviewConfig Конфигурация виждета для возможности удаления файлов
 */
trait FilePreviewTrait
{
    public string $filePreviewTemplate = <<<HTML
<div class="file-preview-other"><h2><i class="fas fa-file"></i></h2></div>
HTML;

    public function getInitialPreview(): array
    {
        $preview = [];

        $userTempDir = $this->module->getUserDirPath();

        foreach (FileHelper::findFiles($userTempDir) as $file) {
            if (substr(FileHelper::getMimeType($file), 0, 5) === 'image') {
                $preview[] = Html::img(['/attachment/file/download-temp', 'filename' => basename($file)], ['class' => 'file-preview-image']);
            } else {
                $preview[] = $this->filePreviewTemplate;
            }
        }

        /** @var File $file */
        foreach ($this->files as $file) {
            if (substr($file->mime, 0, 5) === 'image') {
                $preview[] = Html::img($file->directUrl, ['class' => 'file-preview-image']);
            } else {
                $preview[] = $this->filePreviewTemplate;
            }
        }

        return $preview;
    }

    public function getInitialPreviewConfig(): array
    {
        $preview = [];

        $userTempDir = $this->module->getUserDirPath();

        foreach (FileHelper::findFiles($userTempDir) as $file) {
            $filename = basename($file);
            $preview[] = [
                'caption' => $filename,
                'url' => Url::to(['/attachment/file/delete-temp', 'filename' => $filename]),
            ];
        }

        /** @var File $file */
        foreach ($this->files as $index => $file) {
            $preview[] = [
                'caption' => $file->realName,
                'url' => Url::toRoute(['/attachment/file/delete', 'id' => $file->id]),
            ];
        }

        return $preview;
    }
}
