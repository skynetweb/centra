<?php

require '../../vendor/autoload.php';
use KanbanBoard\Application;
use KanbanBoard\Exceptions;

putenv("GH_REPOSITORIES=SalesManHackathon2016");
putenv("GH_CLIENT_ID=af426c2eae98b4f64dc7");
putenv("GH_CLIENT_SECRET=c9ff220335d0d36c1e8d0eddb6555cd62a9924f1");
putenv("GH_ACCOUNT=skynetweb");

try {
    if (!session_start()) {
        throw new Exceptions\SessionException('Could not start the session');
    }
    $application = new Application([]);
    $application->board();
} catch (\Exception $e) {
    echo $e->getMessage();
}