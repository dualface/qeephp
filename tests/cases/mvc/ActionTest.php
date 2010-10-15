<?php

namespace tests\cases\mvc;

use qeephp\Config;
use qeephp\mvc\App;

use tests\includes\TestCase;
use tests\fixture\TestApp;

require __DIR__ . '/__init.php';

class ActionTest extends TestCase
{
	/**
	 * @var TestApp
	 */
	private $_app;

    function test_run()
    {
        ob_start();
        $this->_app->run();
        $result = ob_get_clean();
        $this->assertEquals('indexAction', $result);

        ob_start();
        $this->_app->run('help');
        $result = ob_get_clean();
        $this->assertEquals('helpAction', $result);

        ob_start();
        $this->_app->run('tests.empty');
        $result = ob_get_clean();
        $this->assertEquals('tests.emptyAction', $result);

        Config::set('app.default_action_name', 'help');
        ob_start();
        $this->_app->run();
        $result = ob_get_clean();
        $this->assertEquals('helpAction', $result);
    }

    function test_url()
    {
        $this->markTestIncomplete();
    }

    function test_view()
    {
        $this->markTestIncomplete();
    }

    function test_tool()
    {
        $this->markTestIncomplete();
    }
    
    
    protected function setup()
    {
        Config::set('app.default_action_name', App::DEFAULT_ACTION);
    	$this->_app = new TestApp();
    }

    protected function teardown()
    {
        unset($this->_app);
        App::unset_instance();
    }
}
 