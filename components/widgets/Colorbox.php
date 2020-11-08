<?php

namespace chipmob\attachment\components\widgets;

use chipmob\attachment\components\assets\ColorboxAsset;
use yii\base\Widget;
use yii\helpers\Json;

class Colorbox extends Widget
{
    public array $targets = [];

    public int $coreStyle = 1;

    /** @inheritdoc */
    public function init()
    {
        parent::init();
        $view = $this->getView();

        if (!empty($this->targets)) {
            $script = '';
            foreach ($this->targets as $selector => $options) {
                $options = Json::encode($options);
                $script .= "$('$selector').colorbox($options);" . PHP_EOL;
            }
            $view->registerJs($script);
        }

        $bundle = ColorboxAsset::register($view);
        if ($this->coreStyle !== 0) {
            $view->registerCssFile("{$bundle->baseUrl}/example{$this->coreStyle}/colorbox.css");
        }
    }
}
