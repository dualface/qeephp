<?php

namespace tests\fixture\models\plugins;

use qeephp\storage\plugins\BasePlugin;
use qeephp\storage\Meta;
use qeephp\storage\ModelEvent;
use qeephp\interfaces\IModel;
use qeephp\interfaces\IModelBeforeFindOneEvent;
use qeephp\interfaces\IModelAfterFindOneEvent;
use qeephp\interfaces\IModelBeforeFindMultiEvent;
use qeephp\interfaces\IModelAfterFindMultiEvent;
use qeephp\interfaces\IModelBeforeCreateEvent;
use qeephp\interfaces\IModelAfterCreateEvent;
use qeephp\interfaces\IModelBeforeUpdateEvent;
use qeephp\interfaces\IModelAfterUpdateEvent;
use qeephp\interfaces\IModelBeforeSaveEvent;
use qeephp\interfaces\IModelAfterSaveEvent;
use qeephp\interfaces\IModelBeforeDel;
use qeephp\interfaces\IModelAfterDel;
use qeephp\interfaces\IModelBeforeErase;
use qeephp\interfaces\IModelAfterErase;

class EventTesterPlugin extends BasePlugin implements IModelBeforeFindOneEvent,
                                                      IModelAfterFindOneEvent,
                                                      IModelBeforeFindMultiEvent,
                                                      IModelAfterFindMultiEvent,
                                                      IModelBeforeCreateEvent,
                                                      IModelAfterCreateEvent,
                                                      IModelBeforeUpdateEvent,
                                                      IModelAfterUpdateEvent,
                                                      IModelBeforeSaveEvent,
                                                      IModelAfterSaveEvent,
                                                      IModelBeforeDel,
                                                      IModelAfterDel,
                                                      IModelBeforeErase,
                                                      IModelAfterErase
{
    private $_eventsTriggering = array();

    function bind()
    {
        $this->_meta->addStaticMethod('isEventTriggered', array($this, '__isEventTriggered'));
        $this->_meta->addStaticMethod('setEventTriggered', array($this, '__setEventTriggered'));

        return array(
            Meta::BEFORE_FIND_ONE_EVENT   => array($this, '__beforeFindOne'),
            Meta::AFTER_FIND_ONE_EVENT    => array($this, '__afterFindOne'),
            Meta::BEFORE_FIND_MULTI_EVENT => array($this, '__beforeFindMulti'),
            Meta::AFTER_FIND_MULTI_EVENT  => array($this, '__afterFindMulti'),
            Meta::BEFORE_CREATE_EVENT     => array($this, '__beforeCreate'),
            Meta::AFTER_CREATE_EVENT      => array($this, '__afterCreate'),
            Meta::BEFORE_UPDATE_EVENT     => array($this, '__beforeUpdate'),
            Meta::AFTER_UPDATE_EVENT      => array($this, '__afterUpdate'),
            Meta::BEFORE_SAVE_EVENT       => array($this, '__beforeSave'),
            Meta::AFTER_SAVE_EVENT        => array($this, '__afterSave'),
            Meta::BEFORE_DEL_EVENT        => array($this, '__beforeDel'),
            Meta::AFTER_DEL_EVENT         => array($this, '__afterDel'),
            Meta::BEFORE_ERASE_EVENT      => array($this, '__beforeErase'),
            Meta::AFTER_ERASE_EVENT       => array($this, '__afterErase'),
        );
    }

    function __isEventTriggered($eventName)
    {
        return isset($this->_eventsTriggering[$eventName]);
    }

    function __setEventTriggered($eventName)
    {
        $this->_eventsTriggering[$eventName] = true;
    }

    function __beforeFindOne(ModelEvent $event, $primaryKeyValue)
    {
        $this->_eventsTriggering[Meta::BEFORE_FIND_ONE_EVENT] = true;
    }

    function __afterFindOne(ModelEvent $event, $primaryKeyValue, IModel $model, array $record)
    {
        $this->_eventsTriggering[Meta::AFTER_FIND_ONE_EVENT] = true;
    }

    function __beforeFindMulti(ModelEvent $event, array $primaryKeyValues)
    {
        $this->_eventsTriggering[Meta::BEFORE_FIND_MULTI_EVENT] = true;
    }

    function __afterFindMulti(ModelEvent $event, array $primaryKeyValues, array $models, array $records)
    {
        $this->_eventsTriggering[Meta::AFTER_FIND_MULTI_EVENT] = true;
    }

    function __beforeCreate(ModelEvent $event, IModel $model)
    {
        $this->_eventsTriggering[Meta::BEFORE_CREATE_EVENT] = true;
    }

    function __afterCreate(ModelEvent $event, IModel $model, $primaryKeyValue)
    {
        $this->_eventsTriggering[Meta::AFTER_CREATE_EVENT] = true;
    }

    function __beforeUpdate(ModelEvent $event, IModel $model)
    {
        $this->_eventsTriggering[Meta::BEFORE_UPDATE_EVENT] = true;
    }

    function __afterUpdate(ModelEvent $event, IModel $model, $result)
    {
        $this->_eventsTriggering[Meta::AFTER_UPDATE_EVENT] = true;
    }

    function __beforeSave(ModelEvent $event, IModel $model)
    {
        $this->_eventsTriggering[Meta::BEFORE_SAVE_EVENT] = true;
    }

    function __afterSave(ModelEvent $event, IModel $model, $result)
    {
        $this->_eventsTriggering[Meta::AFTER_SAVE_EVENT] = true;
    }

    function __beforeDel(ModelEvent $event, IModel $model)
    {
        $this->_eventsTriggering[Meta::BEFORE_DEL_EVENT] = true;
    }

    function __afterDel(ModelEvent $event, IModel $model, $result)
    {
        $this->_eventsTriggering[Meta::AFTER_DEL_EVENT] = true;
    }

    function __beforeErase(ModelEvent $event, $primaryKeyValue)
    {
        $this->_eventsTriggering[Meta::BEFORE_ERASE_EVENT] = true;
    }

    function __afterErase(ModelEvent $event, $primaryKeyValue, $result)
    {
        $this->_eventsTriggering[Meta::AFTER_ERASE_EVENT] = true;
    }
}

