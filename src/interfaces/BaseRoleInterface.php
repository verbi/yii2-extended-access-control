<?php
namespace verbi\yii2ExtendedAccessControl\interfaces;

interface BaseRoleInterface {
    public function init();
    
    public function getBaseRoles();
}