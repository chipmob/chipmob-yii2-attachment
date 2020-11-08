<?php

namespace chipmob\attachment\components\widgets;

use chipmob\attachment\components\behaviors\FileBehavior;
use chipmob\attachment\components\traits\ModuleTrait;
use chipmob\attachment\models\File;
use Yii;
use yii\base\InvalidConfigException;
use yii\bootstrap4\Widget;
use yii\data\ArrayDataProvider;
use yii\db\ActiveRecord;
use yii\grid\GridView;
use yii\helpers\Html;

class AttachmentsTable extends Widget
{
    use ModuleTrait;

    /** @var ActiveRecord */
    public $model;

    public array $tableOptions = ['class' => 'table table-striped table-bordered table-condensed'];

    public bool $showDeleteButton = true;

    /** @inheritdoc */
    public function init()
    {
        parent::init();

        if (empty($this->model)) {
            throw new InvalidConfigException("Property {model} cannot be blank");
        }

        $hasFileBehavior = false;
        foreach ($this->model->getBehaviors() as $behavior) {
            if (is_a($behavior, FileBehavior::class)) {
                $hasFileBehavior = true;
            }
        }
        if (!$hasFileBehavior) {
            throw new InvalidConfigException("The behavior {FileBehavior} has not been attached to the model.");
        }
    }

    /** @inheritdoc */
    public function run()
    {
        $confirm = Yii::t('yii', 'Are you sure you want to delete this item?');
        $js = <<<JS
        $(".delete-button").click(function(e){
            e.preventDefault();
            var tr = this.closest('tr');
            var url = $(this).data('url');
            if (confirm("$confirm")) {
                $.ajax({
                    method: "POST",
                    url: url,
                    success: function(data) {
                        if (data) {
                            tr.remove();
                        }
                    }
                });
            }
        });
JS;
        Yii::$app->view->registerJs($js);

        return GridView::widget([
            'dataProvider' => new ArrayDataProvider(['allModels' => $this->model->files]),
            'layout' => '{items}',
            'tableOptions' => $this->tableOptions,
            'columns' => [
                [
                    'class' => 'yii\grid\SerialColumn',
                ],
                [
                    'label' => Yii::t('common', 'Имя файла'),
                    'value' => fn(File $model) => Html::a($model->realName, $model->downloadUrl),
                    'format' => 'raw',
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{delete}',
                    'visibleButtons' => ['delete' => $this->showDeleteButton],
                    'buttons' => [
                        'delete' => fn($url, File $model, $key) => Html::a(Html::tag('i', null, ['class' => 'fas fa-trash-alt']),
                            '#',
                            [
                                'class' => 'delete-button',
                                'title' => Yii::t('yii', 'Delete'),
                                'data-url' => $model->deleteUrl,
                            ]
                        ),
                    ],
                ],
            ],
        ]);
    }
}
