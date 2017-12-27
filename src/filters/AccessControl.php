<?php

namespace verbi\yii2ExtendedAccessControl\filters;

use Yii;
use yii\base\Event;
use yii\filters\AccessControl as YiiAccessControl;
use verbi\yii2Helpers\events\GeneralFunctionEvent;
use verbi\yii2Helpers\traits\BehaviorTrait;
use verbi\yii2Helpers\traits\ComponentTrait;

class AccessControl extends YiiAccessControl {

    use BehaviorTrait {
        events as traitEvents;
    }

    const EVENT_BEFORE_GENERATE_RULES = 'before_generate_rules';
    const EVENT_AFTER_GENERATE_RULES = 'after_generate_rules';
    const EVENT_GENERATE_RULES = 'event_generate_rules';
    const EVENT_GENERATE_AUTH_RULES = 'event_generate_auth_rules';
    const EVENT_GENERATE_ACCESS_TYPES = 'event_generate_access_types';

    public $ruleConfig = ['class' => 'verbi\yii2ExtendedAccessControl\filters\AccessRule'];
    protected $_request;
    public $generateRules = true;
    public $generateAuthRules = true;
    protected $_accessTypesEnsured = false;
    public $accessTypeConfig = [
        'class' => 'verbi\yii2ExtendedAccessControl\filters\AccessType',
    ];
    public $accessTypes;
    public $permissionHierarchy;
    public $permissionNames;

    public function events() {
        return array_merge(
                $this->traitEvents(), [
            BehaviorTrait::$EVENT_ON_ATTACH => 'afterAttach',
                ]
        );
    }

    protected function _isEnsured() {
        if (!$this->owner instanceof ComponentTrait) {
            return $this->owner->behaviorsAreEnsured();
        }
        return false;
    }

    public function afterAttach($event) {
        if ($this->generateAuthRules) {
            $this->rules = $this->generateAuthRules();
        }

        if ($this->generateRules !== false && !sizeof($this->rules) && $this->_isEnsured()) {
            $this->generateRules = true;
            $this->rules = $this->generateRules();
        }
    }

    protected function ensureAccessTypes() {
        if (!$this->_accessTypesEnsured) {
            if ($this->accessTypes === null) {
                $this->accessTypes = [];
                $event = new GeneralFunctionEvent();
                $event->sender = $this;
                Event::trigger(static::className(), static::EVENT_GENERATE_ACCESS_TYPES, $event);
            }



            foreach ($this->accessTypes as $key => $accessType) {
                if (is_string($accessType)) {
                    $accessType = Yii::createObject(array_merge($this->accessTypeConfig, [
                                'name' => $accessType,
                    ]));
                }
                if (is_array($accessType)) {
                    $this->accessTypes[$key] = Yii::createObject(array_merge($this->accessTypeConfig, ['name' => $key, 'actions' => $accessType]));
                }
            }
            $this->_accessTypesEnsured = true;
        }
    }

    public function getAccessTypes($action = null) {
        $this->ensureAccessTypes();
        if ($action === null) {
            return $this->accessTypes;
        }
        return array_filter($this->accessTypes, function($accessType) use ($action) {
            return array_search($action->id, $accessType->actions) !== false;
        });
    }

    public function afterEnsureBehaviors($event) {
        if ($this->_isEnsured()) {
            $this->generateRules();
        }
    }

    protected function generateAuthRules() {
        if ($this->owner->hasMethod('loadModel')) {
            $event = new GeneralFunctionEvent;
            $event->setParams([
                'accessControl' => $this,
            ]);
            $this->owner->loadModel()->trigger(self::EVENT_GENERATE_AUTH_RULES, $event);
        }
    }

    protected function generateRules() {
        $rules = $this->rules;
        $event = new GeneralFunctionEvent;
        $event->setParams([
            'rules' => &$rules,
        ]);
        $this->owner->trigger(self::EVENT_BEFORE_GENERATE_RULES, $event);
        if (!$event->isValid) {
            return $event->getReturnValue() === null ? $rules : $event->getReturnValue();
        }

        if ($this->owner->hasMethod('getActions')) {
            $actionIds = array_keys($this->owner->getActions());
            foreach ($actionIds as $id) {
                $rules[$id] = $this->generateRule($id);
            }
        }

        if ($this->owner->hasMethod('loadModel')) {
            $event = new GeneralFunctionEvent;
            $event->setParams([
                'rules' => &$rules,
                'accessControl' => $this,
            ]);
            $this->owner->loadModel($this->owner->getPkFromRequest())->trigger(self::EVENT_GENERATE_RULES, $event);
            if ($event->isValid) {
                if ($event->hasReturnValue()) {
                    $rules = $event->getReturnValue();
                }
            }
        }

        $event = new GeneralFunctionEvent;
        $event->setParams([
            'rules' => &$rules,
        ]);
        $this->owner->trigger(self::EVENT_AFTER_GENERATE_RULES, $event);
        if ($event->isValid && $event->hasReturnValue()) {
            return $event->getReturnValue();
        }
        return $rules;
    }

    protected function generateRule($actionId) {
        return Yii::createObject(array_merge($this->ruleConfig, [
                    'allow' => true,
                    'actions' => [$actionId,],
                    'accessTypes' => ['index'],
                    'roles' => [$this->owner->className() . '-' . $actionId],
                    'roleParams' => function() {
                return ['model' => $this->owner->loadModel($this->owner->getPkFromRequest())];
            }
        ]));
    }

    protected function getRequest() {

        if ($this->_request === null) {
            $this->_request = clone Yii::$app->getRequest();
        }
        return $this->_request;
    }

    public function checkAccess($action, $params, $method = 'get') {
        $user = $this->user;
        $request = clone Yii::$app->getRequest();
        $request->setQueryParams($params);
        /* @var $rule AccessRule */
        foreach ($this->rules as $rule) {
            if ($rule->allows($action, $user, $request)) {
                return true;
            }
        }
        return false;
    }

}
