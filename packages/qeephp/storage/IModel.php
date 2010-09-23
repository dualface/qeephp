<?php

namespace qeephp\storage;

interface IModel
{
    /* 字段类型 */
    const TYPE_INT      = 'integer';
    const TYPE_SMALLINT = 'smallint';
    const TYPE_FLOAT    = 'double';
    const TYPE_STRING   = 'string';
    const TYPE_TEXT     = 'text';
    const TYPE_BOOL     = 'boolean';
    const TYPE_SERIAL   = 'serial';

    /* 事件 */
    /* class events */
    const BEFORE_FIND_EVENT     = '__before_find';
    const AFTER_FIND_EVENT      = '__after_find';
    /* instance events */
    const AFTER_READ_EVENT      = '__after_read';
    const BEFORE_SAVE_EVENT     = '__before_save';
    const AFTER_SAVE_EVENT      = '__after_save';
    const BEFORE_CREATE_EVENT   = '__before_create';
    const AFTER_CREATE_EVENT    = '__after_create';
    const BEFORE_UPDATE_EVENT   = '__before_update';
    const AFTER_UPDATE_EVENT    = '__after_update';
    const BEFORE_DEL_EVENT      = '__before_del';
    const AFTER_DEL_EVENT       = '__after_del';

    /**
     * 对象更新到存储时的策略
     *
     * -  更新所有属性
     *    -  更新时检查所有属性
     *    -  更新时检查改动过的属性
     *    -  更新时不检查
     * -  更新改动过的属性
     *    -  更新时检查改动过的属性
     *    -  更新时不检查
     *
     * 在模型类中使用 @update 指定更新策略，默认设置为：
     * @update all & check_all
     *
     * 使用 UPDATE_CHECK_ALL 和 UPDATE_CHECK_CHANGED 类似于乐观锁，可以避免并发更新冲突时。
     * 针对特定属性可以指定 UPDATE_PROP_*，进一步减少并发冲突。
     */
    const UPDATE_ALL_PROPS      = 0x0001;
    const UPDATE_CHANGED_PROPS  = 0x0002;
    const UPDATE_CHECK_ALL      = 0x0004;
    const UPDATE_CHECK_CHANGED  = 0x0008;
    const UPDATE_CHECK_NON      = 0x0000;
    const UPDATE_DEFAULT_POLICY = 0x0005;

    /**
     * 对象属性的更新策略
     *
     * -  UPDATE_PROP_OVERWRITE 直接用新值覆盖现有值
     *
     * -  UPDATE_PROP_INCR 增加值
     *    现有值：1
     *    新值：10
     *    更新操作：UPDATE collection SET prop = prop + 9 WHERE pk = id
     *
     * -  UPDATE_PROP_GT_ZERO 增加或减少值，并且保证更新后的值大于 0
     *    现有值：10
     *    新值：1
     *    更新操作：UPDATE collection SET prop = prop - 9 WHERE pk = id AND prop > 9
     *
     * -  UPDATE_PROP_GTE_ZERO 增加或减少值，并且保证更新后的值大于等于 0
     *    现有值：10
     *    新值：1
     *    更新操作：UPDATE collection SET prop = prop - 9 WHERE pk = id AND prop >= 9
     *
     * -  UPDATE_PROP_IGNORE 更新时忽略该属性
     *
     * 在属性中，使用 @update 指定更新策略，默认设置为：
     * @update overwrite
     */
    const UPDATE_PROP_OVERWRITE         = 'overwrite';
    const UPDATE_PROP_INCR              = 'incr';
    const UPDATE_PROP_GT_ZERO           = 'gt_zero';
    const UPDATE_PROP_GTE_ZERO          = 'gte_zero';
    const UPDATE_PROP_IGNORE            = 'ignore';
    const UPDATE_PROP_DEFAULT_POLICY    = 'overwrite';

}

