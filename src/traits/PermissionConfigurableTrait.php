<?php

namespace verbi\yii2ExtendedAccessControl\traits;

use Yii;
use yii\base\InvalidParamException;

trait PermissionConfigurableTrait {

    use BaseRoleTrait {
        init as protected _baseRoleTraitInit;
    }

    public $parents;
    protected $_parentsEnsured = false;

    public function init() {
        $this->_baseRoleTraitInit();
        if ($this->parents !== null && !is_array($this->parents)) {
            throw new InvalidParamException('Parents must be an array.');
        }
    }

    public function ensureBaseRoleChildren($accessTypes = null) {
        $baseRoles = $this->getBaseRoles();
        if (sizeof($baseRoles)) {
            if ($accessTypes === null) {
                $accessTypes = $this->getAccessTypes();
            }
            array_walk($accessTypes, function($accessType) use($baseRoles) {
                array_walk($baseRoles, function($baseRole) use($accessType) {
                    $auth = Yii::$app->authManager;
                    $permissions = $this->getPermissionsForAccessType($accessType, false);
                    foreach ($permissions as $permission) {
                        if (!is_object($permission))
                            die(print_r($this, true));
                        if (!$auth->hasChild($baseRole, $permission)) {
                            $auth->addChild($baseRole, $permission);
                        }
                    }
                });
            });
        }
    }

    protected function ensureParents() {
        if (!$this->_parentsEnsured) {
            if ($this->parents === null) {
                $this->parents = [];
            }
            if (sizeof($this->parents)) {
                array_walk($this->parents, function(&$item, $key) {
                    if (is_array($item)) {

                        $item = Yii::createObject(array_merge($this->_getParentConfiguration(), $item)
                        );
                    }
                    if (!$item instanceof PermissionCreatorInterface) {
                        throw new InvalidParamException('Parents must be an array of ' . PermissionCreatorInterface::className() . '.');
                    }
                    $item->getParents();
                });
            } else {
                $this->ensureBaseRoleChildren();
            }
        }
        $this->_parentsEnsured = true;
    }

    public function getParents() {
        $this->ensureParents();
        return $this->parents;
    }

    protected function _getParentConfiguration() {
        return [];
    }

}
