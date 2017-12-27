<?php

namespace verbi\yii2ExtendedAccessControl\configurables\base;

use verbi\yii2ExtendedAccessControl\behaviors\ModelBasedAccessRulesBehavior;
use verbi\yii2ExtendedAccessControl\interfaces\PermissionConfigurableInterface;
use yii\base\Object;
use yii\base\InvalidParamException;

abstract class Permission extends Object implements PermissionConfigurableInterface {

    use \verbi\yii2ExtendedAccessControl\traits\PermissionConfigurableTrait {
        init as protected _permissionConfigurableInit;
    }

    public $accessTypes;
    protected $_accessTypesEnsured = false;
    public $owner;

    public function init() {
        parent::init();
        $this->_permissionConfigurableInit();
        if ($this->accessTypes !== null && !is_array($this->accessTypes)) {
            throw new InvalidParamException('AccessTypes must be an array.');
        }
        if (!$this->owner instanceof ModelBasedAccessRulesBehavior) {
            throw new InvalidParamException('Owner must be an instaceof ' . ModelBasedAccessRulesBehavior::className() . '.');
        }
    }

    protected function _ensureAccesstypes() {
        if (!$this->_accessTypesEnsured) {
            if ($this->accessTypes === null) {
                $this->accessTypes = $this->owner->accessTypes;
            }
        }
        $this->_accessTypesEnsured = true;
    }

    public function getAccessTypes() {
        $this->_ensureAccesstypes();
        return $this->accessTypes;
    }

    public function getPermissionsForAccessType($accessType, $alsoEnsure = true) {
        return [$this->owner->getPermissionsForAccessType($accessType, $alsoEnsure)];
    }

}
