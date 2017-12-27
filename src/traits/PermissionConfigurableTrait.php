<?php

namespace verbi\yii2ExtendedAccessControl\traits;

use verbi\yii2ExtendedAccessControl\interfaces\ManagerInterface;
use Yii;
use yii\rbac\Item;
use yii\base\InvalidParamException;

trait PermissionConfigurableTrait {
   use BaseRoleTrait {
        init as protected _baseRoleTraitInit;
    }
    
    public $parents;
    
    protected $_parentsEnsured = false;

    
    public function init() {
        $this->_baseRoleTraitInit();
        if( $this->parents !== null && !is_array($this->parents)) {
            throw new InvalidParamException('Parents must be an array.');
        }
    }
    
    public function ensureBaseRoleChildren($accessTypes = null) {
        $baseRoles = $this->getBaseRoles();
                if(sizeof($baseRoles))  {
//                    array_walk($this->owner->accessTypes, function($accessType) use($baseRoles) {
                    if($accessTypes===null) {
                        $accessTypes = $this->getAccessTypes();
                    }
                    array_walk($accessTypes, function($accessType) use($baseRoles) {
//                        if($accessType != 'update') {
//                            die($accessType);
//                        }
                        array_walk($baseRoles, function($baseRole) use($accessType) {
                            $auth = Yii::$app->authManager;
    //                        $permission = $this->owner->getPermission($accessType,false);
                            $permissions = $this->getPermissionsForAccessType($accessType,false);
    //                        $permission = $this->getBehavior()->getPermission($accessType);
                            foreach($permissions as $permission) {
//                                if(!is_object($baseRole))
//                                    die(print_r($this,true));
                                if(!is_object($permission))
                                    die(print_r($this,true));
                                if(!$auth->hasChild($baseRole,$permission)) {
                                    $auth->addChild($baseRole,$permission);
                                }
                            }
                        });
                    });
                }
                
//                $baseRoles = $this->owner->getBaseRoles();
//                if(sizeof($baseRoles))  {
//                    $permission = $this->owner->getPermission($accessType,false);
//                    
//                    $auth = Yii::$app->authManager;
//                    array_walk($baseRoles, function(&$baseRole) use ($auth,$permission) {
//                        if(!$auth->hasChild($baseRole,$permission)) {
//    //                        $auth->addChild($baseRole,$permission);
//                        }
//                    });
//                }
    }
    
    protected function ensureParents() {
        if(!$this->_parentsEnsured) {
            if($this->parents === null) {
                $this->parents = [];
            }
            if(sizeof($this->parents)) {
                array_walk($this->parents, function(&$item, $key){
                    if(is_array($item)) {

                        $item = Yii::createObject(array_merge($this->_getParentConfiguration(),
                            $item)
                        );
                    }
                    if(!$item instanceof PermissionCreatorInterface) {
                        throw new InvalidParamException('Parents must be an array of '.PermissionCreatorInterface::className().'.');
                    }
                    $item->getParents();
                });
            
            }
            else {
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
        return [
                            'owner' => $this->owner,
                            'accessTypes' => $this->getAccessTypes(),
                            ];
    }
    
    
}