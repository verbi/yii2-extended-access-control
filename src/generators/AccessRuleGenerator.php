<?php

namespace verbi\yii2ExtendedAccessControl\generators;

use Yii;
use \yii\base\Behavior;
use yii\base\Configurable;
use yii\base\Controller;
use yii\base\Object;
use yii\base\InvalidParamException;
use verbi\yii2ExtendedAccessControl\filters\AccessControl;
use verbi\yii2ExtendedAccessControl\traits\AccessRuleGeneratorBehaviorTrait;
use verbi\yii2Helpers\events\GeneralFunctionEvent;


class AccessRuleGenerator extends Object implements Configurable {
    
    public $behavior;
    
    public $children = [];
    
    public $generateRoles = false;
    
    public $roleName;
    
    public $events = [
//        Accesscontrol::EVENT_GENERATE_AUTH_RULES => 'eventAddAuthRules',
//        AccessControl::EVENT_GENERATE_RULES => 'eventGenerateRules',
    ];
    
    public function init() {
        parent::init();
        if(!$this->behavior instanceof Behavior) {
            throw new InvalidParamException('Behavior must be an instance of ' . Behavior::className() . '.');
        }
        if(false===array_search('verbi\yii2ExtendedAccessControl\traits\PermissionCreatorTrait',class_uses($this->behavior)) ) {
            throw new InvalidParamException('Behavior must be an instance of ' . 'verbi\yii2ExtendedAccessControl\traits\PermissionCreatorTrait' . '.');
        }
//        if(false===array_search('verbi\yii2ExtendedAccessControl\traits\AccessRuleGeneratorBehaviorTrait',class_uses($this->behavior)) ) {
//            throw new InvalidParamException('Behavior must be an instance of ' . 'verbi\yii2ExtendedAccessControl\traits\AccessRuleGeneratorBehaviorTrait' . '.');
//        }
        if(!is_array($this->events)) {
            throw new InvalidParamException('Events must be an array.');
        }
        if($this->children!==null) {
            if(!is_array($this->children)) {
                throw new InvalidParamException('Children must be an array.');
            }
            array_walk($this->children, function($child, $key) {
                if(!$child instanceof AccessRuleGenerator) {
                    throw new InvalidParamException('All items in children must be an instance of ' . Behavior::className() . '.');
                }
            });
        }
        $owner = $this->behavior->owner;
        foreach ($this->events() as $event => $handler) {
            $owner->on($event, is_string($handler) ? [$this, $handler] : $handler);
        }
    }
    
    public function getChildren() {
        if($this->children===null) {
            $this->children=[];
        }
        return $this->children;
    }
    
    public function events() {
        return $this->events;
    }
    
    public function getRoleName() {
        if(!$this->generateRoles) {
            return null;
        }
        if(!is_string($this->roleName)) {
//            $explodedClassName = explode('\\',$this->behavior->owner->className());
//            $explodedBehaviorClassName = explode('\\', );
            $this->roleName = $this->behavior->owner->className(true) . '-' . $this->behavior->className(true);
        }
        return str_pad($this->roleName,64);
    }
    
    public function getRole() {
        if(!$this->generateRoles) {
            return null;
        }
        $auth = Yii::$app->authManager;
        if($auth) {
            $roleName = $this->getRoleName();
            if(!$role = $auth->getRole($roleName)) {
                $role = $auth->createRole($roleName);
//                $auth->add($rule);
//                $owner->ruleName = $rule->name;
                $auth->add($role);
            }
//            $children = array_map(function($child){
//                return $child->name;
//            }, $auth->getChildren($roleName));
            array_walk($this->getChildren(), function($child, $key) use ($auth, $role) {
                if(!$auth->canAddChild($role->name, $child->getRoleName())) {
                    $auth->addChild($role->name, $child->getRoleName());
                }
            });
            return $role;
        }
    }
    
    public function getRule() {
        $auth = Yii::$app->authManager;
        if ($auth) {
            $newRule = new \verbi\yii2ExtendedActiveRecord\rbac\ModelBasedRule;
            if (!$rule = $auth->getRule($newRule->name)) {
                $rule = new \verbi\yii2ExtendedActiveRecord\rbac\ModelBasedRule;
                // add the rule
                $auth->add($rule);
            }
            return $rule;
        }
    }

//    public function getPermissions($accessControl) {
//        $permissions = [];
//        foreach(array_keys($accessControl->owner->getActions()) as $actionId) {
////            $permissions[] = $this->getPermission($actionId, $accessControl);
//        }
//        return $permissions;
//    }
//    
//    public function getPermission($name, $accessControl) {
//        $auth = Yii::$app->authManager;
//        if($auth) {
//            // add the "updateOwnPost" permission and associate the rule with it.
//            $permissionName = substr(str_pad($this->behavior->owner->className(true) . '-' . $accessControl->owner->className(true) . '-' . $name . '-' . $this->behavior->className(true),64),0,64);
//            if(!$permission = $auth->getPermission($permissionName)) {
//                $permission = $auth->createPermission($permissionName);
//                $permission->description = $accessControl->owner->className(true) . ' ' . $name . ' ' . $this->behavior->className(true) . ' ' . $this->behavior->owner->className(true);
//                if($rule = $this->getRule()) {
//                    $permission->ruleName = $rule->name;
//                }
//                $auth->add($permission);
//                if($this->generateRoles) {
//                    $role = $this->getRole();
//                    $auth->addChild($role, $permission);
//                }
//            }
////            $children = array_map(function($child){
////                return $child->name;
////            }, $auth->getChildren($permissionName));
//            $children = $this->getChildren();
//            array_walk($children, function($child, $key) use ($auth, $permission, $name) {
//                $childName = $child->getPermission($name,$accessControl)->name;
//                if($auth->cannAddChild($permission->name, $childName)) {
//                    $auth->addChild($permission->name, $childName);
//                }
//            });
//            return $permission;
////          $auth->addChild($permission, $updatePost);
//        }
//    }
    
//    public $events = [
//        AccessControl::EVENT_AFTER_GENERATE_RULES => 'eventAfterGenerateRules',
//    ];
//    
    
//    public function eventAddAuthRules(GeneralFunctionEvent $event) {
//        $params = $event->getParams();
//        if(isset($params['accessControl'])) {
//            $this->addAuthRules($params['accessControl']);
//        }
//    }
    
    
    
//    public function addAuthRules($accessControl) {
//        $this->getPermissions($accessControl);
////        $auth = Yii::$app->authManager;
////        if($auth) {
////            // add the "updateOwnPost" permission and associate the rule with it.
////            foreach(array_keys($controller->getActions()) as $actionId) {
////                $this->getPermission($actionId);
//////              $auth->addChild($permission, $updatePost);
////            }
////        }
//    }
    
    
}