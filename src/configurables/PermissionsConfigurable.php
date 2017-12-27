<?php
namespace verbi\yii2ExtendedAccessControl\configurables;

use Yii;
use yii\base\Object;
use yii\base\InvalidParamException;

class PermissionsConfigurable extends Object {
    
    // the authitem configurations
    public $items;
    
    protected $__items;
    
    public $defaultItemClass = 'verbi\yii2ExtendedAccessControl\configurables\PermissionItemConfigurable';
    
    public function init() {
        parent::init();
        if( $this->items !== null && !is_array( $this->items ) ) {
            throw new InvalidParamException('Items must be an array.');
        }
    }
    
    protected function ensureItems() {
        if(!is_array($this->__items) && is_array($this->items)) {
            $this->__items = array_map(function($item) {
                return Yii::createObject($item);
            }, $this->items);
        }
    }
}