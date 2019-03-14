<?php

namespace KanbanBoard;

use KanbanBoard\Utilities\Utilities;

class Authentication {

	private $client_id = NULL;
	private $client_secret = NULL;

    const TOKEN = 'gh-token';
    const REDIRECTED = 'redirected';

    public function __construct()
	{
	    try {
            $this->client_id = Utilities::env('GH_CLIENT_ID');
            $this->client_secret = Utilities::env('GH_CLIENT_SECRET');
        } catch (\Exception $e) {
	        throw new \InvalidArgumentException('Invalid Client Id and Client secret');
        }
	}

    public function getTokenOrLogin()
    {
        if (!isset($_SESSION[self::TOKEN])) {
            $this->login();
        }

        return $_SESSION[self::TOKEN];
    }

	public function logout(): void
	{
		unset($_SESSION[self::TOKEN]);
	}

    private function login()
    {
        if ((new Utilities())->hasValue($_GET, 'code') && (new Utilities())->hasValue($_GET, 'state')
            && $_SESSION[self::REDIRECTED]) {
            $_SESSION[self::REDIRECTED] = false;
            $_SESSION[self::TOKEN] = $this->returnFromGithub($_GET['code']);
        } else {
            $_SESSION[self::REDIRECTED] = true;
            $this->redirectToGithub();
        }
    }

	private function redirectToGithub()
	{
	    $url = sprintf('Location: https://github.com/login/oauth/authorize?client_id=%s&scope=repo&state=LKHYgbn776tgubkjhk', $this->client_id);
		header($url);
		exit();
	}

	private function returnFromGithub($code): array
	{
		$url = 'https://github.com/login/oauth/access_token';
		$data = [
			'code' => $code,
			'state' => 'LKHYgbn776tgubkjhk',
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret];
		$options = [
			'http' => [
				'method' => 'POST',
				'header' => "Content-type: application/x-www-form-urlencoded\r\n",
				'content' => http_build_query($data),
			],
		];
        $result = $this->parseResponse($options, $url);

        return array_shift($result);
	}

    private function parseResponse(array $options, string $url): array
    {
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === FALSE) {
            die('Error');
        }
        $result = explode('=', explode('&', $result)[0]);
        array_shift($result);

        return $result;
    }
}
