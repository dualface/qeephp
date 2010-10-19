<?php

namespace tests\fixture;

class ModelTestApp extends TestApp
{
    function __construct()
    {
        StorageFixture::set_default_mysql_domain_config();
        $config = array(
            'storage.default_domain' => StorageFixture::DEFAULT_NODE,
        );
        parent::__construct($config);
    }
}

