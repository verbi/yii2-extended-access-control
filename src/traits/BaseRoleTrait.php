<?php
namespace verbi\yii2ExtendedAccessControl\traits;

use verbi\yii2ExtendedAccessControl\interfaces\ManagerInterface;
use Yii;
use yii\base\InvalidParamException;

trait BaseRoleTrait {
    protected $baseRolesEnsured = false;

    /**
     * The roles
     * 
     * @var array|null
     */
    public $baseRoles;
    
    protected $_baseRolesEnsured = false;
    
    public function init() {
        parent::init();
        if( $this->baseRoles !== null && !is_array($this->baseRoles)) {
            throw new InvalidParamException('BaseRoles must be an array.');
        }
    }
    
    protected function _ensureBaseRoles() {
        if(!$this->_baseRolesEnsured) {
            if($this->baseRoles === null) {
                $auth = Yii::$app->authManager;
                $this->baseRoles = $auth->getBaseRoles();
            }
            elseif(is_array($this->baseRoles)) {
                $auth = Yii::$app->authManager;
                if(!$auth instanceof ManagerInterface) {
                    throw new InvalidParamException('Auth must be an instance of must be an instance of ' . ManagerInterface::className() . '.');
                }
                $this->baseRoles = $auth->generateBaseRoles($this->baseRoles);
            }
            else {
                throw new InvalidParamException('BaseRoles must be an array.');
            }
        }
        $this->_baseRolesEnsured = true;
    }
    
    public function getBaseRoles() {
        $this->_ensureBaseRoles();
        return $this->baseRoles;
    }
}