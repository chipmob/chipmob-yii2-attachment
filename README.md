Yii2 attachment
================

Extension for the file uploading and attaching to the models

https://github.com/chipmob/yii2-attachment

Demo
----
You can see the demo on the [krajee](http://plugins.krajee.com/file-input/demo) website

Installation
------------

1.  Add a module to `app/config/main.php`

```php
'modules' => [
        'attachment' => [
            'class' => 'chipmob\attachment\Module',
            'tempPath' => '@app/web/uploads/temp',
            'storePath' => '@app/web/uploads/store',
            'rules' => [
                'maxFiles' => 10,
                'mimeTypes' => [
                    'image/*', 'application/msword', 'application/vnd.ms-excel',
                    'text/plain', 'application/pdf', 'application/zip', 'application/x-rar-compressed'
                ],
                'maxSize' => 2 * 1024 * 1024
            ],
        ]
    ],
```

2. Apply migrations

```php
	php yii migrate/up
```

3. Attach behavior to your model, where `id` - PK in target table (default "id")

```php
    public function behaviors()
    {
        return [
            'class' => \chipmob\attachment\components\behaviors\FileBehavior::class,
            // Если не указаны, будут глобальные
            'rules' => [
                'maxFiles'  => 1,
                'mimeTypes' => ['image/*'],
                'maxSize'   => 256 * 1024,
            ],
        ];
    }
```

4. Make sure you have added `'enctype' => 'multipart/form-data'` to the ActiveForm options

5. Add upload action to controller if you want to ajax file upload
 
```php
    public function actions()
    {
        return [
            'upload' => [
                'class' => \chipmob\attachment\components\actions\UploadAction::class,
                'model' => \common\models\User::class,
            ],
        ];
    }
```

Usage
-----
1. In the `form.php` of your model add file input (http://plugins.krajee.com/file-input)

```php
    <?= \chipmob\attachment\components\widgets\AttachmentsInputAuto::widget([
        'id' => 'file-input', // Optional
        'model' => $model,
        'attribute' => 'file[]',
        'options' => [ // Options of the Kartik's FileInput widget
            
        ],
        'pluginOptions' => [ // Plugin options of the Kartik's FileInput widget 
            
        ]
    ]) ?>
```

2. Use widget to show all attachments of the model in the `view.php`

```php
	<?= \chipmob\attachment\components\widgets\AttachmentsTable::widget(['model' => $model]) ?>
```

3. (Deprecated) Add onclick action to your submit button that uploads all files before submitting form

```php
    <?= \yii\helpers\Html::submitButton($model->isNewRecord ? 'Create' : 'Update', [
        'class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary',
        'onclick' => "$('#file-input').fileinput('upload');"
    ]) ?>
```

4. You can get all attached files by calling ```$model->files```, for example:

```php
    foreach ($model->files as $file)
    {
        echo $file->path; // or $file->url; or $file->directUrl;
    }
```

5. For auto-upload files

```js
    $input.fileinput({}).on("filebatchselected", function(event, files) {
       // trigger upload method immediately after files are selected
       $input.fileinput("upload");
     });
```
