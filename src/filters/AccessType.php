<?php
namespace verbi\yii2ExtendedAccessControl\filters;

use yii\base\Object;
use yii\base\InvalidParamException;
use yii\base\Action;

class AccessType extends Object {
    public $name;
    public $actions;
    protected $_actionsEnsured = false;
    
    public $parentPermissionConfig = [];
    
    public function init() {
        parent::init();
        if( !is_string( $this->name ) ) {
            throw new InvalidParamException('Name must be a string.');
        }
        if( $this->actions !== null && !is_array( $this->actions ) ) {
            throw new InvalidParamException('Actions must be an array.');
        }
//        if( $this->parentPermissionCreators !== null) {
//            if(!is_array( $this->parentPermissionCreators ) ) {
//                throw new InvalidParamException('ParentPermissionCreators must be an array.');
//            }
//            foreach($this->parentPermissionCreators as $parentPermissionCreator) {
//                if($parentPermissionCreator instanceof PermissionCreatorInterface) {
//                    throw new InvalidParamException('ParentPermissionCreators must be an instance of '.PermissionCreatorInterface::className().'.');
//                }
//            }
//        }
    }
    
    protected function ensureActions() {
        if(!$this->_actionsEnsured) {
            $this->_actionsEnsured = true;
        }
        
        if($this->actions === null) {
            
        }
        foreach($this->actions as $action) {
            
        }
        
        return $this->actions;
    }
    
    public function getActions() {
        $this->ensureActions();
        return $this->actions;
    }
    
    public function hasAction($action) {
        if(!is_string($action) && !$action instanceof Action) {
            throw new InvalidParamException('Action must be a string or an instance of '.Action::className().'.');
        }
        if(is_string($action)) {
            
        }
        return false;
    }
}