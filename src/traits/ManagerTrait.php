<?php

namespace verbi\yii2ExtendedAccessControl\traits;

use Yii;

trait ManagerTrait {

    use BaseRoleTrait {
        init as protected _baseRoleTraitInit;
    }

    public $defaultUserRoles = [];

    public function init() {
        $this->_baseRoleTraitInit();
        if ($this->baseRoles !== null && !is_array($this->baseRoles)) {
            throw new InvalidParamException('BaseRoles must be an array.');
        }
    }

    public function generateBaseRoles($items) {
        array_walk($items, function(&$item) {
            if ($item instanceof yii\rbac\Role) {
                $newItem = $item;
            } else {
                $newItem = $this->createRole($item);
            }
            $auth = Yii::$app->authManager;
            if (!$item = $auth->getRole($newItem->name)) {
                $item = $newItem;
                // add the rule
                $auth->add($item);
            }
        });
        return $items;
    }

    /**
     * Returns defaultRoles as array of Role objects.
     * @since 2.0.12
     * @return Role[] default roles. The array is indexed by the role names
     */
    public function getDefaultRoleInstances() {
        $result = [];
        foreach ($this->defaultRoles as $roleName) {
            $result[$roleName] = $this->createRole($roleName);
        }
        if (!Yii::$app->user->getIsGuest()) {
            foreach ($this->defaultUserRoles as $roleName) {
                $result[$roleName] = $this->createRole($roleName);
            }
        }
        return $result;
    }

    /**
     * @inherit
     */
    protected function hasNoAssignments(array $assignments) {
        return parent::hasNoAssignments($assignments) && empty($this->defaultUserRoles);
    }

    /**
     * @inherit
     */
    protected function checkAccessFromCache($user, $itemName, $params, $assignments) {
        $result = $this->_checkAccessForTrait($user, $itemName, $params);
        return $result === null ? parent::checkAccessFromCache($user, $itemName, $params, $assignments) : $result;
    }

    /**
     * @inherit
     */
    protected function checkAccessRecursive($user, $itemName, $params, $assignments) {
        $result = $this->_checkAccessForTrait($user, $itemName, $params);
        return $result === null ? parent::checkAccessRecursive($user, $itemName, $params, $assignments) : $result;
    }

    protected function _checkAccessForTrait($user, $itemName, $params) {
        if ($user) {
            if (($item = $this->getItem($itemName)) === null) {
                return false;
            }
            Yii::trace($item instanceof Role ? "Checking role: $itemName" : "Checking permission: $itemName", __METHOD__);
            if (!$this->executeRule($user, $item, $params)) {
                return false;
            }
            if (in_array($itemName, $this->defaultUserRoles)) {
                return true;
            }
        }
        return null;
    }

}
