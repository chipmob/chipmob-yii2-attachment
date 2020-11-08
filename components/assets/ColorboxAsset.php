<?php

namespace chipmob\attachment\components\assets;

use Yii;
use yii\web\AssetBundle;

class ColorboxAsset extends AssetBundle
{
    /** @inheritdoc */
    public $sourcePath = '@bower/jquery-colorbox';

    /** @inheritdoc */
    public $depends = [
        'yii\web\JqueryAsset',
    ];

    /** @inheritdoc */
    public function init()
    {
        parent::init();

        $this->js[] = YII_DEBUG ? 'jquery.colorbox.js' : 'jquery.colorbox-min.js';
        $this->registerLanguageAsset();
    }

    protected function registerLanguageAsset()
    {
        $language = Yii::$app->language;
        if (!file_exists(Yii::getAlias($this->sourcePath . "/i18n/jquery.colorbox-{$language}.js"))) {
            $language = substr($language, 0, 2);
            if (!file_exists(Yii::getAlias($this->sourcePath . "/i18n/jquery.colorbox-{$language}.js"))) {
                return;
            }
        }
        $this->js[] = "i18n/jquery.colorbox-{$language}.js";
    }
}
