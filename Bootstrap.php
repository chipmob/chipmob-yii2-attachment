<?php

namespace chipmob\attachment;

use Yii;
use yii\base\BootstrapInterface;

class Bootstrap implements BootstrapInterface
{
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
