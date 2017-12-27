<?php

namespace verbi\yii2ExtendedAccessControl\interfaces;

interface PermissionConfigurableInterface extends BaseRoleInterface {

    public function getAccessTypes();

    public function getParents();

    public function ensureBaseRoleChildren($accessTypes = null);

    public function getPermissionsForAccessType($accessType, $alsoEnsure = true);
}
