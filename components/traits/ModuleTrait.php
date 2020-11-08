<?php

namespace chipmob\attachment\components\traits;

use chipmob\attachment\Module;
use Exception;
use Yii;

/**
 * @property Module $module
 */
trait ModuleTrait
{
    private ?Module $_module = null;

    public function getModule(): ?Module
    {
        if (empty($this->_module)) {
            $this->_module = Yii::$app->getModule('attachment');
        }

        if (empty($this->_module)) {
            throw new Exception("Module yii2-attachment not found, may be you didn't add it to your config?");
        }

        return $this->_module;
    }

    public function configureModule(array $config = [])
    {
        $this->getModule();

        foreach ($config as $key => $value) {
            if ($this->_module->hasProperty($key) && !empty($value)) {
                $this->_module->{$key} = $value;
            }
        }
    }
}
