<?php

namespace chipmob\attachment\components\behaviors;

use chipmob\attachment\components\traits\FilePreviewTrait;
use chipmob\attachment\components\traits\ModuleTrait;
use chipmob\attachment\models\File;
use Exception;
use ReflectionClass;
use Yii;
use yii\base\Behavior;
use yii\base\DynamicModel;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\base\ModelEvent;
use yii\caching\ArrayCache;
use yii\caching\CacheInterface;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

/**
 * @property array $config
 * @property array $rules Правила валидации
 * @property File[] $files
 * @property bool $hasFiles
 * @property File $firstFile
 */
class FileBehavior extends Behavior
{
    use ModuleTrait;
    use FilePreviewTrait;

    /** @inheritdoc */
    public $owner;

    public $file;

    public array $config = [];

    private array $_uploads = [];
    private array $_rules = [];

    protected CacheInterface $_cache;

    /** @inheritdoc */
    public function init()
    {
        parent::init();

        $this->configureModule($this->config);

        $this->_cache = Yii::createObject(ArrayCache::class);
    }

    public function getRules(): array
    {
        return $this->_rules;
    }

    public function setRules(array $rules)
    {
        $this->_rules = array_merge($this->module->rules, $rules);
    }

    /** @return string|array */
    public function getRule(string $rule)
    {
        if (array_key_exists($rule, $this->_rules)) {
            return $this->_rules[$rule];
        } else {
            throw new InvalidConfigException();
        }
    }

    /** @inheritdoc */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'validateUploads',
            ActiveRecord::EVENT_AFTER_INSERT => 'saveUploads',
            ActiveRecord::EVENT_AFTER_UPDATE => 'saveUploads',
            ActiveRecord::EVENT_AFTER_DELETE => 'deleteUploads',
        ];
    }

    public function validateUploads(ModelEvent $event)
    {
        $this->_uploads = UploadedFile::getInstancesByName((new ReflectionClass($this->owner))->getShortName());

        $count = count($this->_uploads);
        $limit = $this->getRule('maxFiles');
        $exist = count($this->files);

        if ($limit === 1 && $exist === 1 && $count === 1) {
            $this->module->detachFile($this->files[0]->id); // TODO: если 1 файл - его заменить
        } elseif (($limit - ($exist + $count)) < 0) {
            $this->owner->addErrors([
                'file' => Yii::t('attachment',
                    'Превышен лимит загрузки файлов. Всего можно загрузить {limit, plural, one{# файл} few{# файла} many{# файлов} other{# файлов}}. Уже {exist, plural, one{загружен # файл} few{загружено # файла} many{загружено # файлов} other{загружено # файлов}}. Удалите ненужные файлы и повторите попытку.',
                    ['limit' => $limit, 'exist' => $exist])
            ]);
        }

        $files = $limit === 1 ? reset($this->_uploads) : $this->_uploads;

        $model = new DynamicModel(['file' => $files]);
        $model->addRule('file', 'file', $this->rules);

        if (!$model->validate()) {
            $this->owner->addErrors(['file' => $model->getErrors('file')]);
        }
    }

    public function saveUploads(AfterSaveEvent $event)
    {
        $userTempDir = $this->module->getUserDirPath();

        if (!empty($this->_uploads)) {
            foreach ($this->_uploads as $file) {
                if (!$file->saveAs($userTempDir . $file->name)) {
                    throw new Exception(Yii::t('yii', 'File upload failed.'));
                }
                $this->stripExifGD($userTempDir . $file->name);
            }
        }

        foreach (FileHelper::findFiles($userTempDir) as $file) {
            if (!$this->module->attachFile($file, $this->owner)) {
                throw new Exception(Yii::t('yii', 'File upload failed.'));
            }
        }

        FileHelper::removeDirectory($userTempDir);
    }

    public function deleteUploads(Event $event)
    {
        foreach ($this->files as $file) {
            $this->module->detachFile($file->id);
        }
    }

    public function fileQuery(): ActiveQuery
    {
        return File::find()->where([
            'item_id' => $this->owner->getPrimaryKey(),
            'model' => $this->owner->formName(),
        ]);
    }

    /** @return File[] */
    public function getFiles()
    {
        return $this->_cache->getOrSet(['All_Files', $this->owner->formName()], fn() => $this->fileQuery()->all());
    }

    public function getHasFiles(): bool
    {
        return $this->_cache->getOrSet(['Has_Files', $this->owner->formName()], fn() => $this->fileQuery()->exists());
    }

    public function getFirstFile(): ?File
    {
        return $this->_cache->getOrSet(['First_File', $this->owner->formName()], fn() => $this->fileQuery()->one());
    }

    protected function stripExifGD(string $path)
    {
        $mime = FileHelper::getMimeType($path);
        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($path);
                imagejpeg($image, $path, 90);
                imagedestroy($image);
                break;
            case 'image/png':
                $image = imagecreatefrompng($path);
                imagepng($image, $path, 9);
                imagedestroy($image);
                break;
        }
    }
}
