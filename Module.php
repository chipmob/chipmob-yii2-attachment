<?php

namespace chipmob\attachment;

use chipmob\attachment\models\File;
use Yii;
use yii\base\BootstrapInterface;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;

/**
 * @property string $storePath
 * @property string $tempPath
 * @property string $rules
 * @property string $urlPrefix
 * @property string $urlRules
 * @property string $uploadPath
 */
class Module extends \yii\base\Module implements BootstrapInterface
{
    /** @inheritdoc */
    public $controllerNamespace = 'chipmob\attachment\controllers';

    /** @inheritdoc */
    public $defaultRoute = 'file';

    public ?string $storePath = null;
    public ?string $tempPath = null;

    public array $rules = [];

    public string $urlPrefix = 'attachment';
    public array $urlRules = [];

    private string $_uploadPath;

    /** @inheritdoc */
    public function init()
    {
        parent::init();

        if (empty($this->storePath) || empty($this->tempPath)) {
            throw new Exception('Setup {storePath} and {tempPath} in module properties');
        }

        $this->_uploadPath = str_replace(Yii::getAlias('@webroot'), '', Yii::getAlias($this->storePath));
    }

    public function getUploadPath(): string
    {
        return $this->_uploadPath;
    }

    public function getFilesDirPath(string $subDir = ''): string
    {
        $path = Yii::getAlias($this->storePath) . DIRECTORY_SEPARATOR . $subDir;

        FileHelper::createDirectory($path);

        return $path;
    }

    public function getUserDirPath(): string
    {
        Yii::$app->session->useCustomStorage ? Yii::$app->session->openSession('', '') : Yii::$app->session->open();

        $userDirPath = Yii::getAlias($this->tempPath) . DIRECTORY_SEPARATOR . Yii::$app->session->id;

        if (!is_dir($userDirPath)) {
            FileHelper::createDirectory($userDirPath);
        }

        Yii::$app->session->useCustomStorage ? Yii::$app->session->closeSession() : Yii::$app->session->close();

        return $userDirPath . DIRECTORY_SEPARATOR;
    }

    public function attachFile(string $filePath, ActiveRecord $owner): ?File
    {
        if (empty($owner->getPrimaryKey())) {
            throw new Exception('Parent model must have ID when you attaching a file');
        }
        if (!file_exists($filePath)) {
            throw new Exception("File $filePath not exists");
        }

        $class = $owner->formName();

        $fileHash = sha1(microtime(true) . $filePath);
        $fileType = pathinfo($filePath, PATHINFO_EXTENSION);
        $newFileName = "$fileHash.$fileType";
        $fileDirPath = $this->getFilesDirPath(md5($class));
        $newFilePath = $fileDirPath . DIRECTORY_SEPARATOR . $newFileName;

        if (!copy($filePath, $newFilePath)) {
            throw new Exception("Cannot copy file! $filePath to $newFilePath");
        }

        $file = new File();
        $file->name = pathinfo($filePath, PATHINFO_FILENAME);
        $file->model = $class;
        $file->item_id = $owner->getPrimaryKey();
        $file->hash = $fileHash;
        $file->size = filesize($filePath);
        $file->type = $fileType;
        $file->mime = FileHelper::getMimeType($filePath);

        if ($file->save()) {
            unlink($filePath);
            return $file;
        } else {
            return null;
        }
    }

    public function detachFile(int $id): bool
    {
        $file = File::findOne(['id' => $id]);
        if (empty($file)) return false;

        return file_exists($file->getPath()) ? unlink($file->getPath()) && (bool)$file->delete() : (bool)$file->delete();
    }

    /** @inheritdoc */
    public function bootstrap($app)
    {
        Yii::setAlias('@attachment', __DIR__);
        if ($app instanceof \yii\console\Application) {
            $app->controllerMap['migrate']['migrationPath'][] = '@attachment/migrations';
        }
        $app->i18n->translations['attachment*'] = [
            'class' => 'yii\i18n\PhpMessageSource',
            'basePath' => '@attachment/messages',
            'sourceLanguage' => 'en-US',
        ];
    }
}
