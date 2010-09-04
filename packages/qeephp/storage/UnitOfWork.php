<?php

namespace qeephp\storage;

class UnitOfWork
{
    /**
     * 所有已经加入的模型对象实例
     *
     * @var array
     */
    private $_models = array();

    /**
     * 将一个对象添加到工作单元
     *
     * @param IModel $model
     */
    function add(BaseModel $model)
    {
        if (in_array($model, $this->_models)) return;
        $this->_models[] = $model;

        $model->savePropValues();
        $event = $model->__addedToUnitOfWork($this);
        $meta = Meta::instance(get_class($model));
        $meta->_raiseEvent(Meta::ADDED_TO_UNITOFWORK_EVENT, array($this), $event);
    }

    /**
     * 保存工作单元中所有对象的修改
     *
     * - 如果保存成功，返回 true；
     * - 如果任何一个对象保存失败，则还原对象到加入工作单元时的状态，并返回 false；
     * - 如果保存时，出现异常，则还原对象状态后抛出该异常。
     *
     * @return bool
     */
    function save()
    {
        $saved = array();
        $ex = null;

        try
        {
            foreach ($this->_models as $offset => $model)
            {
                /* @var $model IModel */
                $saved[] = $offset;
                $result = $model->save();
                if ($result === false) throw new UnitOfWorkBreakSaveError();
            }
        }
        catch (\Exception $ex)
        {
            foreach ($saved as $offset)
            {
                $model = $this->_models[$offset];
                /* @var $model IModel */
                $model->revertToSavedPropValues();
            }
            if ($ex instanceof UnitOfWorkBreakSaveError) return false;
        }

        if ($ex) throw $ex;
        return true;
    }
}

class UnitOfWorkBreakSaveError extends \Exception
{
    function __construct()
    {
        parent::__construct('UnitOfWorkBreakSaveError', 0);
    }
}

