<?php

declare(strict_types=1);
require '../../vendor/autoload.php';


final class ApplicationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \KanbanBoard\Exceptions\EnvironmentException
     */
    public function testEnvDoesntExists(): void
    {
        $util = new KanbanBoard\Utilities\Utilities();
        $util->env('TEST');
    }

    public function testEnvExists(): void
    {
        putenv("GH_REPOSITORIES=SalesManHackathon2016");
        $util = new KanbanBoard\Utilities\Utilities();
        $this->assertEquals($util->env('GH_REPOSITORIES'), 'SalesManHackathon2016');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testApplicationInvalidArguments(): void
    {
        new KanbanBoard\Application();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAuthInvalidArguments(): void
    {
        new KanbanBoard\Authentication();
    }


    public function testAuthWithSession(): void
    {
        putenv("GH_CLIENT_ID=af426c2eae98b4f64dc7");
        putenv("GH_CLIENT_SECRET=c9ff220335d0d36c1e8d0eddb6555cd62a9924f1");
        $auth = new KanbanBoard\Authentication();

        $_SESSION['gh-token'] = 123;
        $this->assertEquals(123, $auth->getTokenOrLogin());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAppProgressBar()
    {
        putenv("GH_REPOSITORIES=SalesManHackathon2016");
        putenv("GH_ACCOUNT=skynetweb");
        $app = new \KanbanBoard\Application();
    }
}