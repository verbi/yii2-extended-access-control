<?php

namespace verbi\yii2ExtendedAccessControl\configurables;

use verbi\yii2ExtendedAccessControl\configurables\base\Permission;
use verbi\yii2ExtendedAccessControl\interfaces\PermissionCreatorInterface;
use verbi\yii2Helpers\behaviors\base\Behavior;
use Yii;
use yii\base\InvalidParamException;

class BehaviorPermission extends Permission {

    public $behaviors;
    protected $_behaviorEnsured = false;

    public function init() {
        parent::init();
        if ($this->behaviors !== null) {
            $behaviors = $this->getBehaviors();
            if (!is_array($behaviors)) {
                throw new InvalidParamException('Behaviors must be an instance of ' . Behavior::className() . '.');
            }
            foreach ($behaviors as $behavior) {
                if (!$behavior instanceof Behavior) {
                    throw new InvalidParamException('Each behavior must be an instance of ' . Behavior::className() . '.');
                }
                if (!$behavior instanceof Behavior) {
                    throw new InvalidParamException('Each behavior must be an instance of ' . PermissionCreatorInterface::className() . '.');
                }
            }
        }
    }

    protected function ensureBehaviors() {
        if (!$this->_behaviorEnsured && $this->behaviors !== null) {
            foreach ($this->behaviors as &$behavior) {
                if (is_string($behavior)) {
                    $b = $this->owner->owner->getBehavior($behavior);
                    if ($b) {
                        $behavior = $b;
                    }
                }
                if (is_array($behavior)) {
                    $behavior = Yii::createObject($behavior);
                }
            }
        }
        $this->_behaviorEnsured = true;
    }

    public function getBehaviors() {
        $this->ensureBehaviors();
        return $this->behaviors;
    }

    protected function ensureParents() {
        if (!$this->_parentsEnsured) {
            $accessTypes = $this->owner->getAccessTypes();
            array_walk($accessTypes, function($accessType) {
                $auth = Yii::$app->authManager;
                foreach ($this->owner->getPermissionsForAccessType($accessType, false) as $child) {
                    if ($this->getBehaviors() === null) {
                        foreach ($this->getBaseRoles() as $baseRole) {
                            if (!$auth->hasChild($baseRole, $child)) {
                                $auth->addChild($baseRole, $child);
                            }
                        }
                    } else {
                        foreach ($this->getBehaviors() as $behavior) {
                            if ($parent = $behavior->getPermission($accessType)) {
                                if (!$auth->hasChild($parent, $child)) {
                                    $auth->addChild($parent, $child);
                                }
                            }
                        }
                    }
                }
            });
        }
        parent::ensureParents();
        $this->_parentsEnsured = true;
    }

    public function getPermissionsForAccessType($accessType, $alsoEnsure = true) {
        $permissions = [];
        if (is_array($this->getBehaviors())) {
            foreach ($this->getBehaviors() as $behavior) {
                $permissions[] = $behavior->getPermission($accessType, $alsoEnsure);
            }
        }
        return $permissions;
    }

}
