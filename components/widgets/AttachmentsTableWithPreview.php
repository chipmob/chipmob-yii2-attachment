<?php

namespace chipmob\attachment\components\widgets;

use chipmob\attachment\components\behaviors\FileBehavior;
use chipmob\attachment\components\Colorbox;
use chipmob\attachment\components\traits\ModuleTrait;
use chipmob\attachment\models\File;
use Yii;
use yii\bootstrap4\Widget;
use yii\data\ArrayDataProvider;
use yii\db\ActiveRecord;
use yii\grid\GridView;
use yii\helpers\Html;

class AttachmentsTableWithPreview extends Widget
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
    }

    /** @inheritdoc */
    public function run()
    {
        if (!$this->model) {
            return Html::tag('div',
                Html::tag('b',
                    Yii::t('yii', 'Error')) . ': ' . 'The model cannot be empty.',
                [
                    'class' => 'alert alert-danger'
                ]
            );
        }

        $hasFileBehavior = false;
        foreach ($this->model->getBehaviors() as $behavior) {
            if ($behavior instanceof FileBehavior) {
                $hasFileBehavior = true;
                break;
            }
        }
        if (!$hasFileBehavior) {
            return Html::tag('div',
                Html::tag('b',
                    Yii::t('yii', 'Error')) . ': ' . 'The behavior FileBehavior has not been attached to the model.',
                [
                    'class' => 'alert alert-danger'
                ]
            );
        }

        $confirm = Yii::t('yii', 'Are you sure you want to delete this item?');
        $js = <<<JS
        $(".delete-button").click(function(){
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
                        'class' => 'yii\grid\SerialColumn'
                    ],
                    [
                        'label' => Yii::t('common', 'Имя файла'),
                        'format' => 'raw',
                        'value' => fn(File $model) => Html::a($model->realName, $model->downloadUrl, [
                            'class' => ' group' . $model->item_id,
                            'onclick' => 'return false;',
                        ]),
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
            ]) . Colorbox::widget([
                'targets' => [
                    '.group' . $this->model->id => [
                        'rel' => '.group' . $this->model->id,
                        'photo' => true,
                        'scalePhotos' => true,
                        'width' => '100%',
                        'height' => '100%',
                        'maxWidth' => 800,
                        'maxHeight' => 600,
                    ],
                ],
                'coreStyle' => 4,
            ]);
    }
}
