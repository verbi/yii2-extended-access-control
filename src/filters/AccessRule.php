<?php

namespace verbi\yii2ExtendedAccessControl\filters;

use yii\base\Model;
use yii\base\InvalidConfigException;
use verbi\yii2Helpers\traits\ComponentTrait;
use verbi\yii2ExtendedActiveRecord\behaviors\AccessRuleModelBehavior;
use verbi\yii2Helpers\events\GeneralFunctionEvent;

/**
 * This class represents an access rule defined by the [[AccessControl]] action filter.
 *
 * @author Philip Verbist <philip.verbist@gmail.com>
 */
class AccessRule extends \yii\filters\AccessRule
{
    public static $EVENT_MATCH_MODEL = 'eventMatchModel';
 
    public $accessTypes;
    
    public $models;
    
    public function allows($action, $user, $request)
    {
        if($this->matchAccessType($action, $user, $request)
                && $this->matchModel($action, $user, $request)) {
//            die(print_r($this->roles,true));
//            die($this->matchRole($user));
            
            
            
//            $authManager = \Yii::$app->getAuthManager();
//            
//            if (isset($authManager->_checkAccessAssignments[(string) $user->getId()])) {
//                $assignments = $authManager->_checkAccessAssignments[(string) $user->getId()];
//            } else {
//                $assignments = $authManager->getAssignments($user->getId());
////                $authManager->_checkAccessAssignments[(string) $user->getId()] = $assignments;
//            }
//            die(print_r($assignments,true));
//            if ($authManager->hasNoAssignments($assignments)) {
//                die('nope');
//            }
//            die('yep');
            
            
            
            return parent::allows($action, $user, $request);
        }
        return null;
    }
    
    protected function getModels($action) {
        if($this->models === null) {
            $this->models = [];
            if($action->controller->hasMethod('loadModel')
                    && $action->controller->hasMethod('getPkFromRequest')) {
                $this->models = [
                    $action->controller->loadModel($action->controller->getPkFromRequest()),
//                    $action->controller->loadModel(null),
                ];
            }
        }
        return $this->models;
    }
    
    protected function matchModel($action, $user, $request) {
        $models = $this->getModels($action);
        if(!is_array($models)) {
            throw new InvalidConfigException('The property models must be an array.');
        }
        foreach($models as $model) {
            if($model instanceof Model
                    && $model->hasMethod('hasBehaviorByClass')
                    && $model->hasBehaviorByClass(AccessRuleModelBehavior::ClassName())
                    ) {
                    $event = new GeneralFunctionEvent([
                        'params' => [
                            'action' => $action,
                            'user' => $user,
                            'request' => $request,
                        ],
                    ]);
                    $model->trigger(static::$EVENT_MATCH_MODEL,$event);
                    if($event->isValid) {
                        if($event->hasReturnValue()) {
                            return $event->getReturnValue();
                        }
                        return true;
                    }
                    return false;
            }
        }
        return true;
    }
    
    protected function matchAccessType($action, $user, $request) {
        return $this->accessTypes==null || !empty(array_filter($this->accessTypes, function($v) use($action) {
            $types = $action->controller->getAccessTypes($action);
            return isset($types[$v]) && in_array($action->id, $types[$v]->actions);
        }));
    }
}