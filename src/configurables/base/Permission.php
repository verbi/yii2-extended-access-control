<?php
namespace verbi\yii2ExtendedAccessControl\configurables\base;

use verbi\yii2ExtendedAccessControl\interfaces\PermissionCreatorInterface;
use verbi\yii2ExtendedAccessControl\behaviors\ModelBasedAccessRulesBehavior;
use verbi\yii2ExtendedAccessControl\interfaces\PermissionConfigurableInterface;
use Yii;
use yii\base\Object;
use yii\base\InvalidParamException;

abstract class Permission extends Object implements PermissionConfigurableInterface {
    use \verbi\yii2ExtendedAccessControl\traits\PermissionConfigurableTrait {
        init as protected _permissionConfigurableInit;
    }
    
    public $accessTypes;
    
    protected $_accessTypesEnsured = false;
    
//    public $baseRoles;
//    
//    protected $_baseRolesEnsured = false;
    
    public $owner;
    
//    protected $_parentPermissions = [];
//    
//    protected $_parentPermissionsEnsured = false;
    
    public function init() {
        parent::init();
        $this->_permissionConfigurableInit();
        if($this->accessTypes !== null && !is_array($this->accessTypes)) {
            throw new InvalidParamException('AccessTypes must be an array.');
        }
        if(!$this->owner instanceof ModelBasedAccessRulesBehavior) {
            throw new InvalidParamException('Owner must be an instaceof '.ModelBasedAccessRulesBehavior::className().'.');
        }
    }
    
//    protected function ensureParentPermissions() {
//        if(!$this->_parentPermissionsEnsured) {
//            
//            $parents = $this->getParents();
//            if(sizeof($parents)) {
//                array_walk($parents, function($parent) {
//                    $parent->getParentPermissions();
//                });
//            }
//            else {
//                $this->ensureBaseRoleChildren();
//            }
////            foreach( $this->_parentPermissions as $parentPermission ) {
////                
////            }
//        }
//        $this->_parentPermissionsEnsured = true;
//    }
//    
//    public function getParentPermissions() {
//        $this->ensureParentPermissions();
//        return $this->_parentPermissions;
//    }
    
    protected function _ensureAccesstypes() {
       if(!$this->_accessTypesEnsured) {
           if($this->accessTypes === null) {
//               die(print_r($this->getParents(),true));
               $this->accessTypes = $this->owner->accessTypes;
           }
       }
       $this->_accessTypesEnsured = true;
    }
    
    public function getAccessTypes() {
        $this->_ensureAccesstypes();
        return $this->accessTypes;
    }
    
//    protected function _ensureBaseRoles() {
//        if(!$this->_baseRolesEnsured) {
//           if($this->baseRoles === null) {
////               die(print_r($this->getParents(),true));
//               $this->baseRoles = $this->owner->getBaseRoles();
//           }
//       }
//       $this->_baseRolesEnsured = true;
//    }
//    
//    public function getBaseRoles() {
//        $this->_ensureBaseRoles();
//        return $this->baseRoles;
//    }
    
    public function getPermissionsForAccessType($accessType,$alsoEnsure=true) {
        return [$this->owner->getPermissionsForAccessType($accessType,$alsoEnsure)];
    }
}