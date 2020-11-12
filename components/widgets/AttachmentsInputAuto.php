<?php

namespace chipmob\attachment\components\widgets;

use kartik\file\FileInput;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;

class AttachmentsInputAuto extends AttachmentsInput
{
    public array $pluginOptions = [];

    /** @inheritdoc */
    public function init()
    {
        parent::init();

        $this->pluginOptions = array_replace($this->pluginOptions, [
            'uploadUrl' => Url::toRoute('upload'),
            'uploadAsync' => false,
            'initialPreview' => $this->model->isNewRecord ? [] : $this->model->initialPreview,
            'initialPreviewConfig' => $this->model->isNewRecord ? [] : $this->model->initialPreviewConfig,
        ]);

        $js = <<<JS
        var fileInput = $('#file-input');
        var form = fileInput.closest('form');
        var filesUploaded = false;
        var filesToUpload = 0;
        var uploadButtonClicked = false;

        form.on('beforeSubmit', function(event) {
            if (!filesUploaded && filesToUpload) {
                fileInput.fileinput('upload').fileinput('lock');
        
                return false;
            }
        });

        // "Загрузить" (все файлы)
        fileInput.on('filebatchpreupload', function(event, data, previewId, index) {
            uploadButtonClicked = true;
        });

        // Успешно удален отдельный файл
        fileInput.on('filesuccessremove', function(event, id) {
            var name = $("#" + id).find('img').prop('title'); // найдем реальное имя файла

            $.ajax({
                method: "POST",
                url: "/attachment/file/delete-temp?filename=" + name,
                success: function(data) {
                    if (data) {
                        filesToUpload = 0; // у нас один файл
                    }
                }
            });
        });

        // Успешно загружены отдельные файлы
        fileInput.on('fileuploaded', function(event, data, previewId, index) {
            filesToUpload = 0;
        });

        // Успешно загружены все файлы
        fileInput.on('filebatchuploadsuccess', function(event, data, previewId, index) {
            filesUploaded = true;
            filesToUpload = 0;
            fileInput.fileinput('unlock');
            if (uploadButtonClicked) {
                form.submit();
            }
        });

        // Файлы добавлены в окно предпросмотра
        fileInput.on('filebatchselected', function(event, files) {
            if ($.isArray(files)) {
               filesToUpload = files.length;
           } else {
               filesToUpload = $(files).length;
           }
        });

        // "Удалить" (все файлы)
        fileInput.on('filecleared', function(event) {
            filesToUpload = 0;
        });
JS;
        Yii::$app->view->registerJs($js);
    }

    /** @inheritdoc */
    public function run()
    {
        $fileInput = FileInput::widget([
            'model' => $this->model,
            'attribute' => $this->attribute,
            'options' => $this->options,
            'pluginOptions' => $this->pluginOptions,
            'sortThumbs' => false,
            'showMessage' => false,
        ]);

        return Html::tag('div', $fileInput, ['class' => 'form-group']);
    }
}
