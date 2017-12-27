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
        if (!$this->behavior instanceof Behavior) {
            throw new InvalidParamException('Behavior must be an instance of ' . Behavior::className() . '.');
        }
        if (false === array_search('verbi\yii2ExtendedAccessControl\traits\PermissionCreatorTrait', class_uses($this->behavior))) {
            throw new InvalidParamException('Behavior must be an instance of ' . 'verbi\yii2ExtendedAccessControl\traits\PermissionCreatorTrait' . '.');
        }
//        if(false===array_search('verbi\yii2ExtendedAccessControl\traits\AccessRuleGeneratorBehaviorTrait',class_uses($this->behavior)) ) {
//            throw new InvalidParamException('Behavior must be an instance of ' . 'verbi\yii2ExtendedAccessControl\traits\AccessRuleGeneratorBehaviorTrait' . '.');
//        }
        if (!is_array($this->events)) {
            throw new InvalidParamException('Events must be an array.');
        }
        if ($this->children !== null) {
            if (!is_array($this->children)) {
                throw new InvalidParamException('Children must be an array.');
            }
            array_walk($this->children, function($child, $key) {
                if (!$child instanceof AccessRuleGenerator) {
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
        if ($this->children === null) {
            $this->children = [];
        }
        return $this->children;
    }

    public function events() {
        return $this->events;
    }

    public function getRoleName() {
        if (!$this->generateRoles) {
            return null;
        }
        if (!is_string($this->roleName)) {
//            $explodedClassName = explode('\\',$this->behavior->owner->className());
//            $explodedBehaviorClassName = explode('\\', );
            $this->roleName = $this->behavior->owner->className(true) . '-' . $this->behavior->className(true);
        }
        return str_pad($this->roleName, 64);
    }

    public function getRole() {
        if (!$this->generateRoles) {
            return null;
        }
        $auth = Yii::$app->authManager;
        if ($auth) {
            $roleName = $this->getRoleName();
            if (!$role = $auth->getRole($roleName)) {
                $role = $auth->createRole($roleName);
                $auth->add($role);
            }
            array_walk($this->getChildren(), function($child, $key) use ($auth, $role) {
                if (!$auth->canAddChild($role->name, $child->getRoleName())) {
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

}
