<?php

namespace chipmob\attachment\components\actions;

use chipmob\attachment\components\behaviors\FileBehavior;
use chipmob\attachment\components\traits\ModuleTrait;
use Yii;
use yii\base\Action;
use yii\base\DynamicModel;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

class UploadAction extends Action
{
    use ModuleTrait;

    public ?string $model = null;

    public bool $dropZone = false;

    private array $_files = [];
    private array $_rules = [];

    /** @inheritdoc */
    public function beforeRun()
    {
        if (empty($this->model)) {
            throw new InvalidConfigException("Property {model} cannot be blank");
        }

        if (!Yii::$app->request->isAjax) {
            throw new MethodNotAllowedHttpException();
        }

        /** @var ActiveRecord $model */
        $model = Yii::createObject($this->model);

        $this->_files = UploadedFile::getInstancesByName($model->formName());

        foreach ($model->getBehaviors() as $name => $item) {
            if (is_a($item, FileBehavior::class)) {
                /** @var FileBehavior $behavior */
                $behavior = $item;
                break;
            }
        }

        if (empty($behavior)) {
            throw new InvalidConfigException("The behavior {FileBehavior} has not been attached to the model");
        }

        $this->_rules = $behavior->rules;

        return parent::beforeRun();
    }

    public function run(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $result = ['uploadedFiles' => []];

        if (empty($this->_files)) {
            return $this->dropZone ? $result : ['error' => 'Error'];
        }

        $files = (!$this->dropZone && count($this->_files) === 1) ? reset($this->_files) : $this->_files;

        $model = new DynamicModel(['file' => $files]);
        $model->addRule('file', 'file', $this->_rules);

        if (!$model->validate()) {
            return ['error' => $model->getErrors('file')];
        }

        $userTempDir = $this->module->getUserDirPath();
        foreach ($files as $file) {
            $path = $userTempDir . DIRECTORY_SEPARATOR . $file->name;
            if ($file->saveAs($path)) {
                $result['uploadedFiles'][] = $file->name;
            }
        }

        return $result;
    }
}
