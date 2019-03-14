<?php

namespace KanbanBoard;

use Github\Api\Issue\Milestones;

class GithubClient
{
    /** @var \Github\Client  */
    private $client;
    /** @var Milestones */
    private $milestoneApi;
    private $account;

    public function __construct(string $token, string $account)
    {
        $this->account = $account;
        $this->client= new \Github\Client(new \Github\HttpClient\CachedHttpClient(array('cache_dir' => '/tmp/github-api-cache')));
        $this->client->authenticate($token, \Github\Client::AUTH_HTTP_TOKEN);
        $this->milestoneApi = $this->client->api('issues')->milestones();
    }

    public function milestones($repository)
    {
        return $this->milestoneApi->all($this->account, $repository);
    }

    public function issues($repository, int $milestoneId)
    {
        return $this->client->api('issue')->all($this->account, $repository, ['milestone' => $milestoneId, 'state' => 'all']);
    }
}