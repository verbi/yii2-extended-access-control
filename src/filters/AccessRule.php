<?php

namespace verbi\yii2ExtendedAccessControl\filters;

use yii\base\Model;
use yii\base\InvalidConfigException;
use yii\web\NotFoundHttpException;
use verbi\yii2ExtendedActiveRecord\behaviors\AccessRuleModelBehavior;
use verbi\yii2Helpers\events\GeneralFunctionEvent;

/**
 * This class represents an access rule defined by the [[AccessControl]] action filter.
 *
 * @author Philip Verbist <philip.verbist@gmail.com>
 */
class AccessRule extends \yii\filters\AccessRule {

    public static $EVENT_MATCH_MODEL = 'eventMatchModel';
    public $accessTypes;
    public $models;

    public function allows($action, $user, $request) {
        if ($this->matchAccessType($action, $user, $request) && $this->matchModel($action, $user, $request)) {
            $allow = parent::allows($action, $user, $request);
            return $allow;
        }
        return null;
    }

    protected function getModels($action) {
        if ($this->models === null) {
            $this->models = [];
            if ($action->controller->hasMethod('loadModel') && $action->controller->hasMethod('getPkFromRequest')) {
                try {
                    $this->models = [
                        $action->controller->loadModel($action->controller->getPkFromRequest()),
                    ];
                }
                catch(NotFoundHttpException $e) {
                    $this->models = [];
                }
            }
        }
        return $this->models;
    }

    protected function matchModel($action, $user, $request) {
        $models = $this->getModels($action);
        if (!is_array($models)) {
            throw new InvalidConfigException('The property models must be an array.');
        }
        foreach ($models as $model) {
            if ($model instanceof Model && $model->hasMethod('hasBehaviorByClass') && $model->hasBehaviorByClass(AccessRuleModelBehavior::ClassName())
            ) {
                $event = new GeneralFunctionEvent([
                    'params' => [
                        'action' => $action,
                        'user' => $user,
                        'request' => $request,
                    ],
                ]);
                $model->trigger(static::$EVENT_MATCH_MODEL, $event);
                if ($event->isValid) {
                    if ($event->hasReturnValue()) {
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
        return $this->accessTypes == null || !empty(array_filter($this->accessTypes, function($v) use($action) {
                            $types = $action->controller->getAccessTypes($action);
                            return isset($types[$v]) && in_array($action->id, $types[$v]->actions);
                        }));
    }

}
