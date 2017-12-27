<?php
namespace verbi\yii2ExtendedAccessControl\traits;

use Yii;

trait PermissionCreatorTrait {
    protected $_ruleEnsured = false;
    
//    public $rule = [
//        'class' => 'verbi\yii2ExtendedActiveRecord\rbac\ModelBasedRule',
//    ];
    
    protected function ensureRule() {
        if(!$this->_ruleEnsured && isset($this->rule) && is_array($this->rule)) {
            $auth = Yii::$app->authManager;
            if ($auth) {
                $newRule = Yii::createObject($this->rule);
//                $newRule = new \verbi\yii2ExtendedActiveRecord\rbac\ModelBasedRule;
                if (!$this->rule = $auth->getRule($newRule->name)) {
                    $this->rule = $newRule;
//                    $rule = new \verbi\yii2ExtendedActiveRecord\rbac\ModelBasedRule;
                    // add the rule
                    $auth->add($this->rule);
                }
//                return $rule;
            }
        }
        $this->_ruleEnsured = true;
    }
    
    public function getRule() {
        $this->ensureRule();
        return isset($this->rule)?$this->rule:null;
    }
    
    public function getPermissionName($accessType) {
        return substr(str_pad('can'.ucfirst($accessType).ucfirst($this->owner->className(true)).'-'.$this->className(true),64),0,64);
    }
    
    public function getPermission($accessType) {
        $auth = Yii::$app->authManager;
        if($auth) {
            // add the "updateOwnPost" permission and associate the rule with it.
            $permissionName = $this->getPermissionName($accessType);
            if(!$permission = $auth->getPermission($permissionName)) {
                $permission = $auth->createPermission($permissionName);
                $permission->description = 'can'.ucfirst($accessType).ucfirst($this->owner->className(true));
                if($rule = $this->getRule()) {
                    $permission->ruleName = $rule->name;
                }
                $auth->add($permission);
            }
            
//            $baseRoles = $this->getBaseRoles();
//            if(sizeof($baseRoles))  {
//                $auth = Yii::$app->authManager;
//                array_walk($baseRoles, function(&$baseRole) use ($auth,$permission) {
//                    if(!$auth->hasChild($baseRole,$permission)) {
////                        $auth->addChild($baseRole,$permission);
//                    }
//                });
//            }
            
            
            
            
            
            
            
            
//            $permissionName = $this->getPermissionName($accessType);
//            if(!$permission = $auth->getRole('role'.$permissionName)) {
//                $permission = $auth->createRole('role'.$permissionName);
//                $permission->description = 'can'.ucfirst($accessType).ucfirst($this->owner->className(true));
//                if($rule = $this->getRule()) {
////                    $permission->ruleName = $rule->name;
//                }
//                $auth->add($permission);
//                
//                
//                
////                if($this->generateRoles) {
////                    $role = $this->getRole();
////                    $auth->addChild($role, $permission);
////                }
//            }
            
            
            
            
            
            
            
            
            
            
//            $children = $this->getChildren();
//            array_walk($children, function($child, $key) use ($auth, $permission, $name) {
//                $childName = $child->getPermission($name,$accessControl)->name;
//                if($auth->cannAddChild($permission->name, $childName)) {
//                    $auth->addChild($permission->name, $childName);
//                }
//            });
            return $permission;
        }
    }
    
    public function getPermissionParams($accessType) {
        return [
            'model' => $this->owner,
        ];
    }
}