<?php

namespace chipmob\attachment\controllers;

use chipmob\attachment\components\traits\ModuleTrait;
use chipmob\attachment\models\File;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\FileHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class FileController extends Controller
{
    use ModuleTrait;

    /** @inheritdoc */
    public function beforeAction($action)
    {
        $this->configureModule(Yii::$app->request->post('config', []) ?: Yii::$app->request->get('config', []));

        return parent::beforeAction($action);
    }

    /** @inheritdoc */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'delete-temp' => ['post'],
                    'download' => ['get'],
                    'download-temp' => ['get'],
                ],
            ],
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionDownload(string $id): Response
    {
        if (empty($file = File::findOne($id))) {
            throw new NotFoundHttpException(Yii::t('attachment', 'File not found'));
        }

        return Yii::$app->response->sendFile($file->getPath(), $file->realName);
    }

    public function actionDelete(string $id): bool
    {
        if (empty($file = File::findOne($id))) {
            throw new NotFoundHttpException(Yii::t('attachment', 'File not found'));
        }

        return $this->module->detachFile($id);
    }

    public function actionDownloadTemp(string $filename): Response
    {
        $filePath = $this->module->getUserDirPath() . DIRECTORY_SEPARATOR . $filename;

        return Yii::$app->response->sendFile($filePath, $filename);
    }

    public function actionDeleteTemp(string $filename): array
    {
        $userTempDir = $this->module->getUserDirPath();

        $filePath = $userTempDir . DIRECTORY_SEPARATOR . $filename;
        unlink($filePath);

        if (!sizeof(FileHelper::findFiles($userTempDir))) {
            rmdir($userTempDir);
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        return [];
    }
}
