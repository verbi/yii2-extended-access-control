<?php

namespace verbi\yii2ExtendedAccessControl\rbac;

use verbi\yii2ExtendedAccessControl\interfaces\ManagerInterface;

class DbManager extends \yii\rbac\DbManager implements ManagerInterface {

    use \verbi\yii2ExtendedAccessControl\traits\ManagerTrait;
}
