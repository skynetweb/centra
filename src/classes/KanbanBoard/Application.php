<?php
namespace KanbanBoard;

use KanbanBoard\Utilities\Utilities;
use \Michelf\Markdown;

class Application {

    private $github;
    private $repositories;
    private $pausedLabels = [];

    const COMPLETED = 'completed';
    const ASSIGNEE  = 'assignee';

    public function __construct($pausedLabels = [])
	{
        try {
            $authentication = new Authentication();
            $token = $authentication->getTokenOrLogin();
            $this->github = new GithubClient($token, Utilities::env('GH_ACCOUNT'));
            $this->repositories = explode('|', Utilities::env('GH_REPOSITORIES'));
            $this->pausedLabels = $pausedLabels;
        } catch (\Exception $e) {
            throw new \InvalidArgumentException();
        }
	}

	public function board()
	{
        $milestones = [];
	    $milestones = $this->fetchMilestones($this->getSortedMilestones(), $milestones);
        $this->renderView($milestones);
    }

	private function issues($repository, $milestone_id)
	{
        $return = [];
        $issues = $this->github->issues($repository, $milestone_id);
        foreach ($issues as $issue)
        {
            if (isset($issue['pull_request'])) {
                continue;
            }

            $return[$this->checkState($issue)][] = [
                'id' 				=> $issue['id'],
                'number'            => $issue['number'],
                'title'            	=> $issue['title'],
                'body'             	=> Markdown::defaultTransform($issue['body']),
                'url' 				=> $issue['html_url'],
                'assignee'          => (\array_key_exists(self::ASSIGNEE, $issue) && \is_array($issue[self::ASSIGNEE])) ? $issue[self::ASSIGNEE]['avatar_url'] : NULL,
                'paused'			=> $this->labelsMatch($issue, $this->pausedLabels),
                'closed'			=> $issue['closed_at']
            ];
        }

        $this->sortActiveIssues($return);

        return $return;
	}

	private static function checkState($issue): string
	{
	   if($issue['state'] === 'closed') {
           return self::COMPLETED;
       }
       else if(Utilities::hasValue($issue, self::ASSIGNEE) && \count($issue[self::ASSIGNEE]) > 0)
       {
            $state = 'active';
       } else {
            $state = 'queued';
       }

        return $state;
	}

	private static function labelsMatch($issue, $needles)
	{
	    $return = [];
        if(Utilities::hasValue($issue, 'labels')) {
            foreach ($issue['labels'] as $label) {
                if (\in_array($label['name'], $needles)) {
                    $return = [$label['name']];
                    break;
                }
            }
        }

        return $return;
	}

	private function getProgress($complete, $remaining)
	{
        $total = $complete + $remaining;
        if($total > 0) {
            return [
                'total' 	=> $total,
                'complete' 	=> $complete,
                'remaining' => $remaining,
                'percent' 	=> \round($complete / $total * 100)
            ];
        }

        return [];
	}

    private function getSortedMilestones(): array
    {
        $ms = array();
        foreach ($this->repositories as $repository) {
            foreach ($this->github->milestones($repository) as $data) {
                $ms[$data['title']] = $data;
                $ms[$data['title']]['repository'] = $repository;
            }
        }
        ksort($ms);

        return $ms;
    }

    private function fetchMilestones($ms, $milestones): array
    {
        foreach ($ms as $name => $data) {
            $issues = $this->issues($data['repository'], $data['number']);
            $percent = $this->getProgress($data['closed_issues'], $data['open_issues']);

            if (!empty($percent)) {
                $milestones[] = [
                    'milestone' => $name,
                    'url' => $data['html_url'],
                    'progress' => $percent,
                    'queued' => isset($issues['queued']) ? $issues['queued']: [],
                    'active' => isset($issues['active']) ? $issues['active'] : [],
                    self::COMPLETED => isset($issues[self::COMPLETED]) ?: ''
                ];
            }
        }
        return $milestones;
    }

    private function renderView($milestones): void
    {
        $mustache = new \Mustache_Engine(array(
            'loader' => new \Mustache_Loader_FilesystemLoader('../views'),
        ));

        echo $mustache->render('index', ['milestones' => $milestones]);
    }

    private function sortActiveIssues($return): void
    {
        if (!isset($return['active'])) {
            return;
        }

        usort($return['active'], function ($a, $b) {
            return count($a['paused']) - count($b['paused']) === 0 ? strcmp($a['title'], $b['title']) : count($a['paused']) - count($b['paused']);
        });
    }
}
