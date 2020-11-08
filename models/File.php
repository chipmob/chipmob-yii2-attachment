<?php

namespace chipmob\attachment\models;

use chipmob\attachment\components\traits\ModuleTrait;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\Url;

/**
 * This is the model class for table "attachment".
 *
 * @property string $hashName
 * @property string $realName
 * @property string $subDir
 * @property string $downloadUrl
 * @property string $deleteUrl
 * @property string $directUrl
 * @property string $secureUrl
 *
 * @property integer $id
 * @property string $name
 * @property string $model
 * @property integer $item_id
 * @property string $hash
 * @property integer $size
 * @property string $type
 * @property string $mime
 * @property string $created_by
 * @property string $created_at
 */
class File extends ActiveRecord
{
    use ModuleTrait;

    const IMAGE_PLACEHOLDER = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNsqAcAAYUBAdpOiIkAAAAASUVORK5CYII=';

    /** @inheritdoc */
    public static function tableName()
    {
        return '{{%attachment}}';
    }

    /** @inheritdoc */
    public function fields()
    {
        return [
            'downloadUrl',
            'deleteUrl',
            'directUrl',
            'realName',
            'size',
        ];
    }

    /** @inheritdoc */
    public function rules()
    {
        return [
            [['name', 'model', 'item_id', 'hash', 'size', 'mime'], 'required'],
            [['item_id', 'size'], 'integer'],
            [['name', 'model', 'hash', 'type', 'mime'], 'string', 'max' => 255],
        ];
    }

    /** @inheritdoc */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'created_at',
                ],
            ],
            [
                'class' => BlameableBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'created_by',
                ],
            ],
        ]);
    }

    public function getRealName(): string
    {
        return "$this->name.$this->type";
    }

    public function getHashName(): string
    {
        return "$this->hash.$this->type";
    }

    public function getSubDir(): string
    {
        return md5($this->model);
    }

    public function getDownloadUrl(): string
    {
        return Url::to(['/attachment/file/download', 'id' => $this->id]);
    }

    public function getDeleteUrl(): string
    {
        return Url::to(['/attachment/file/delete', 'id' => $this->id]);
    }

    public function getDirectUrl(): string
    {
        return Url::to($this->module->uploadPath . DIRECTORY_SEPARATOR . $this->subDir . DIRECTORY_SEPARATOR . $this->hashName);
    }

    public function getPath(array $config = []): string
    {
        $this->configureModule($config);

        return $this->module->getFilesDirPath($this->subDir) . DIRECTORY_SEPARATOR . $this->hashName;
    }
}
