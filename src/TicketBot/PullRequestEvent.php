<?php

namespace TicketBot;

class PullRequestEvent
{
    private $event;

    public function __construct(array $event)
    {
        if (!isset($event['action'])) {
            throw new \RuntimeException("Missing action in Pull Request");
        }
        if (!isset($event['pull_request']['html_url'])) {
            throw new \RuntimeException("Missing html url in Pull Request");
        }

        $this->event = $event;
    }

    public function isSendToMaster()
    {
        return $this->event['pull_request']['base']['ref'] === "master";
    }

    public function repository()
    {
        return $this->event['pull_request']['base']['repo']['name'];
    }

    public function owner()
    {
        return $this->event['pull_request']['base']['repo']['owner']['login'];
    }

    public function isSynchronize()
    {
        return $this->event['action'] === "synchronize";
    }

    public function isOpened()
    {
        return $this->event['action'] === "opened";
    }

    public function issueUrl()
    {
        return $this->event['pull_request']['html_url'];
    }

    public function openerUsername()
    {
        return $this->event['pull_request']['user']['login'];
    }

    public function action()
    {
        return $this->event['action'];
    }

    public function getId()
    {
        $issueUrl = $this->issueUrl();
        $parts = explode("/", $issueUrl);
        $pullRequestId = array_pop($parts);

        return $pullRequestId;
    }

    public function issuePrefix()
    {
        return "[GH-".$this->getId()."]";
    }

    public function title()
    {
        return $this->event['pull_request']['title'];
    }

    public function body()
    {
        return $this->event['pull_request']['body'];
    }

    public function searchTerms(JiraProject $project)
    {
        $issueSearchTerms = array($this->issueUrl(), $this->issuePrefix());

        if (preg_match_all('((' . preg_quote($project->shortname) . '\-[0-9]+))', $this->title() . " " . $this->body(), $matches)) {
            $issueSearchTerms = array_merge($issueSearchTerms, array_values(array_unique($matches[1])));
        }

        return $issueSearchTerms;
    }
}
