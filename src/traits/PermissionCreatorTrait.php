<?php

namespace verbi\yii2ExtendedAccessControl\traits;

use Yii;

trait PermissionCreatorTrait {

    protected $_ruleEnsured = false;

    protected function ensureRule() {
        if (!$this->_ruleEnsured && isset($this->rule) && is_array($this->rule)) {
            $auth = Yii::$app->authManager;
            if ($auth) {
                $newRule = Yii::createObject($this->rule);
                if (!$this->rule = $auth->getRule($newRule->name)) {
                    $this->rule = $newRule;
                    // add the rule
                    $auth->add($this->rule);
                }
            }
        }
        $this->_ruleEnsured = true;
    }

    public function getRule() {
        $this->ensureRule();
        return isset($this->rule) ? $this->rule : null;
    }

    public function getPermissionName($accessType) {
        return substr(str_pad('can' . ucfirst($accessType) . ucfirst($this->owner->className(true)) . '-' . $this->className(true), 64), 0, 64);
    }

    public function getPermission($accessType) {
        $auth = Yii::$app->authManager;
        if ($auth) {
            // add the "updateOwnPost" permission and associate the rule with it.
            $permissionName = $this->getPermissionName($accessType);
            if (!$permission = $auth->getPermission($permissionName)) {
                $permission = $auth->createPermission($permissionName);
                $permission->description = 'can' . ucfirst($accessType) . ucfirst($this->owner->className(true));
                if ($rule = $this->getRule()) {
                    $permission->ruleName = $rule->name;
                }
                $auth->add($permission);
            }
            return $permission;
        }
    }

    public function getPermissionParams($accessType) {
        return [
            'model' => $this->owner,
        ];
    }

}
