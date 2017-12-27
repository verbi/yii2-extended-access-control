<?php
namespace verbi\yii2ExtendedAccessControl\rbac;

use verbi\yii2ExtendedAccessControl\interfaces\ManagerInterface;

class PhpManager extends \yii\rbac\PhpManager implements ManagerInterface {
    use \verbi\yii2ExtendedAccessControl\traits\ManagerTrait;
}