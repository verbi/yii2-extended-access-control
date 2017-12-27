<?php

namespace verbi\yii2ExtendedAccessControl\interfaces;

interface PermissionCreatorInterface {

    public function getPermissionName($accessType);

    public function getPermission($accessType);

    public function getPermissionParams($accessType);
}
