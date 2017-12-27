<?php

namespace verbi\yii2ExtendedAccessControl\interfaces;

interface ManagerInterface extends \yii\rbac\ManagerInterface, BaseRoleInterface {

    public function generateBaseRoles($items);
}
