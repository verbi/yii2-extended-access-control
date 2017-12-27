<?php

namespace verbi\yii2ExtendedAccessControl\behaviors;

use Yii;
use verbi\yii2Helpers\behaviors\base\Behavior;
use verbi\yii2ExtendedAccessControl\filters\AccessControl;
use verbi\yii2Helpers\events\GeneralFunctionEvent;
use verbi\yii2ExtendedAccessControl\generators\AccessRuleGenerator;
use verbi\yii2ExtendedAccessControl\interfaces\PermissionCreatorInterface;
use yii\base\InvalidParamException;
use yii\base\Event;

/**
 * @author Philip Verbist <philip.verbist@gmail.com>
 * @link https://github.com/verbi/yii2-extended-activerecord/
 * @license https://opensource.org/licenses/GPL-3.0
 */
class ModelBasedAccessRulesBehavior extends Behavior implements PermissionCreatorInterface {
    use \verbi\yii2ExtendedAccessControl\traits\PermissionConfigurableTrait {
        init as protected _permissionConfigurableInit;
    }
    use \verbi\yii2ExtendedAccessControl\traits\PermissionCreatorTrait {
        getPermission as protected _permissionCreatorTraitGetPermission;
        getPermissionName as protected _permissionCreatorTraitGetPermissionName;
    }
    
    public $accessTypes = [
        'view',
        'index',
        'create',
        'update',
        'delete',
    ];
    
    public $parentPermissionConfig = [
        'class' => 'verbi\yii2ExtendedAccessControl\configurables\BehaviorPermission',
    ];
    
    protected $_permissionsEnsured = [];
    
    public $parentPermissions;
    
    protected $_parentPermissionsEnsured = false;
    
    public $accessRuleGenerator;
    
    protected $_rulesGenerated = false;
    
    public $generateRules = true;
    
    public $ruleConfig = [
        'class' => 'verbi\yii2ExtendedAccessControl\filters\AccessRule',
        'allow' => true,
    ];
    
    public $rules;
    
    protected $_rulesEnsured = false;
    
    public $events = [
        AccessControl::EVENT_GENERATE_RULES => 'eventGenerateRules',
    ];
    
    public function init() {
        parent::init();
        $this->_permissionConfigurableInit();
        if($this->rules !== null && !is_array($this->rules)) {
            throw new InvalidParamException('Rules must be an array.');
        }
        if(!is_array($this->ruleConfig)) {
            throw new InvalidParamException('RuleConfig must be an array.');
        }
        if($this->parentPermissions !== null && !is_array($this->parentPermissions)) {
            throw new InvalidParamException('ParentPermissions must be an array.');
        }
        if(!is_array($this->parentPermissionConfig)) {
            throw new InvalidParamException('ParentPermissionConfig must be an array.');
        }
        if(!isset($this->parentPermissionConfig['owner'])) {
            $this->parentPermissionConfig['owner'] = $this;
        }
        $className = static::className();
        if(!$this->parentPermissionConfig['owner'] instanceof $className) {
            throw new InvalidParamException('ParentPermissionConfig must be an instance of '.$className.'.');
        }
        if($this->baseRoles!==null && !is_array($this->baseRoles)) {
            throw new InvalidParamException('BaseRoles must be an array.');
        }
        
        Event::on(AccessControl::className(), AccessControl::EVENT_GENERATE_ACCESS_TYPES, [$this, 'eventGenerateAccesstypes',]);
    }
    
    protected function ensurePermissionsForAccessType($accessType, $permission) {
        if(!isset($this->_permissionsEnsured[$accessType]) || !$this->_permissionsEnsured[$accessType]) {
            $parentPermissions = $this->getParentPermissions();
            if(is_array($parentPermissions) && sizeof($parentPermissions)) {
                foreach($parentPermissions as $parentPermission) {
                    $pps=$parentPermission->getParents();
                }
            }
        }
        
        $this->_permissionsEnsured[$accessType] = true;
    }
    
    public function getPermissionName($accessType) {
        return substr(str_pad('can'.ucfirst($accessType).ucfirst($this->owner->className(true)),64),0,64);
    }
    
    public function getPermissionsForAccessType($accessType,$alsoEnsure=true) {
        $permission = $this->_permissionCreatorTraitGetPermission($accessType);
        if($alsoEnsure) {
            $this->ensurePermissionsForAccessType($accessType, $permission);
        }
        return [$permission];
    }
    
    public function ensureParentPermissions() {
        if(!$this->_parentPermissionsEnsured) {
            if(is_array($this->parentPermissions)) {
                $usedAccessTypes = [];
                if(sizeof($this->parentPermissions)) {
                    foreach($this->parentPermissions as $key => $parentPermission) {
                        if(is_string($parentPermission)) {
                            $parentPermission = [
                                'behavior' => $parentPermission,
                            ];
                        }
                        if (is_array($parentPermission)) {
                            $this->parentPermissions[$key] = Yii::createObject(array_merge(
                                        $this->parentPermissionConfig,
                                        ['accessTypes' => $this->accessTypes,],
                                        $parentPermission
                                    ));
                        }
                        if(is_array($this->parentPermissions[$key]->getAccessTypes())) {
                            $usedAccessTypes = array_merge($usedAccessTypes,$this->parentPermissions[$key]->getAccessTypes());
                        }
                    }
                }
                $accessTypes = array_diff($this->accessTypes, $usedAccessTypes);
                if(!empty($accessTypes)) {
                    $this->ensureBaseRoleChildren($accessTypes);
                }
            }
        }
        $this->_parentPermissionsEnsured = true;
    }
    
    public function getParentPermissions() {
        $this->ensureParentPermissions();
        return $this->parentPermissions;
    }
    
    public function eventGenerateRules(GeneralFunctionEvent $event) {
        $params = $event->getParams();
        if(isset($params['accessControl'])) {
            $event->setReturnValue(
                    array_merge(
                            is_array($event->getReturnValue())
                                ?$event->getReturnValue()
                                :[],
                            $this->getAccessRules($params['accessControl'])
                    )
                );
        }
    }
    
    protected function ensureRules() {
        if(!$this->_rulesEnsured && $this->rules !== null) {
            foreach($this->rules as $key => $rule) {
                if (is_array($rule)) {
                    $this->rules[$key] = Yii::createObject(array_merge($this->ruleConfig,$rule));
                }
            }
            $this->_rulesEnsured = true;
        }
    }
    
    public function generateRules() {
        if(!$this->_rulesGenerated) {
            if(!is_array($this->rules)) {
                $this->rules = [];
            }
            foreach($this->getAccessTypes() as $accessType) {
                $this->rules[] = Yii::createObject(array_merge($this->ruleConfig, [
                    'accessTypes' => [$accessType,],
                    'roles' => 
                        array_column($this->getPermissionsForAccessType($accessType),'name'),
                        
                    'roleParams' => $this->getPermissionParams($accessType),
                    ]
                ));
            }
        }
    }
    
    public function getRules() {
        $this->ensureRules();
        if($this->generateRules) {
            $this->generateRules();
        }
        return $this->rules;
    }
    
    public function attach($owner) {
        parent::attach($owner);
        $this->__linkAccessRuleGenerator();
    }
    
    protected function __linkAccessRuleGenerator() {
        if(!$this->accessRuleGenerator instanceof AccessRuleGenerator) {
            $config = [
                'class' => AccessRuleGenerator::className(),
                'behavior' => $this,
            ];
            if(is_array($this->accessRuleGenerator)) {
                $config = array_merge($config, $this->accessRuleGenerator);
            }
            if(is_string($this->accessRuleGenerator)) {
                $config['class'] = $this->accessRuleGenerator;
            }
            $this->accessRuleGenerator = \Yii::createObject($config);
        }
    }
    
    public function getPermissionConfig() {
        return $this->__permissionsConfig;
    }
    
    public function setPermissionConfigs($config) {
        $this->__permissionsConfig = $config;
    }
    
    public function eventGenerateAccesstypes($event) {
            if($event->sender instanceof AccessControl) {
                $sender = $event->sender;
                foreach(array_keys($sender->owner->getActions()) as $actionId) {
                    $accessTypes = $this->getAccessTypes();
                    $found = array_search($actionId, $accessTypes);
                        if($found !== false) {
                            $sender->accessTypes[$accessTypes[$found]] = [$actionId];
                        }
                }
            }
    }
    
    public function getAccessTypes() {
        return $this->accessTypes;
    }
    
    public function getAccessRules($accessControl) {
        $rules = [];
        return $this->getRules();
    }
}