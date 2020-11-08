<?php

namespace chipmob\attachment\components\widgets;

use chipmob\attachment\components\traits\ModuleTrait;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\widgets\InputWidget;

class AttachmentsInput extends InputWidget
{
    use ModuleTrait;

    /** @inheritdoc */
    public $id = 'file-input';

    /** @inheritdoc */
    public function init()
    {
        parent::init();

        FileHelper::removeDirectory($this->module->getUserDirPath());

        $this->options = array_replace($this->options, [
            'id' => $this->id,
            'multiple' => $this->model->getRule('maxFiles') > 1,
            'accept' => implode(',', $this->model->getRule('mimeTypes')),
        ]);

        if (!isset($this->field->form->options['enctype'])) {
            $this->field->form->options['enctype'] = 'multipart/form-data';
        }
    }

    /** @inheritdoc */
    public function run()
    {
        return Html::activeFileInput($this->model, $this->attribute, $this->options);
    }
}
