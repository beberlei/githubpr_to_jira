<?php

namespace TicketBot;

class JiraProject
{
    const TICKETTYPE_BUG = 4;

    public $hash;
    public $uri;
    public $username;
    public $password;
    public $shortname;
    public $ticketType = self::TICKETTYPE_BUG;
    public $assignUsername;
    public $template = <<<ISSUETEXT
This issue is created automatically through a Github pull request on behalf of {user}:

Url: {url}

Message:

{body}

ISSUETEXT;

    public function createTicket(PullRequestEvent $pullRequestEvent)
    {
        $title = sprintf('%s %s', $pullRequestEvent->issuePrefix(), $pullRequestEvent->title());
        $body = str_replace(
            array("{user}", "{url}", "{body}"),
            array($pullRequestEvent->openerUsername(), $pullRequestEvent->issueUrl(), $pullRequestEvent->body()),
            $this->template
        );

        return new NewJiraIssue($title, $body);
    }

    public function createComment(PullRequestEvent $pullRequestEvent)
    {
        return sprintf(
            "A related Github Pull-Request %s was %s:\n%s",
            $pullRequestEvent->issuePrefix(),
            $pullRequestEvent->action(),
            $pullRequestEvent->issueUrl());
    }
}

