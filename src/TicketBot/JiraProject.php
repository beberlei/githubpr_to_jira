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

    public function __construct(array $data = array())
    {
        foreach ($data as $k => $v) {
            if ( ! property_exists($this, $k)) {
                throw new \RuntimeException(sprintf("There is no property called '%s' on class '%s'.", $k, get_class($this)));
            }

            $this->$k = $v;
        }
    }

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

    public function createNotifyComment(JiraIssue $issue, $sentToMaster = true)
    {
        $link = $this->uri . "/browse/" . $issue->key;

        if ( ! $sentToMaster) {
            return <<<TEXT
Hello,

thank you for creating this pull request. However did not open it on the "master"
branch. Our Git workflow requires all pull requests to go through "master" branch
and the release masters then merge them back into stable branches, if they are
bug fixes.

Please open the pull request again for the "master" branch and close
this one.

Nevertheless I have opened a Jira ticket for this Pull Request to track this
issue:

$link

We use Jira to track the state of pull requests and the versions they got
included in.

TEXT;
        }

        return <<<TEXT
Hello,

thank you for creating this pull request. I have automatically opened an issue
on our Jira Bug Tracker for you. See the issue link:

$link

We use Jira to track the state of pull requests and the versions they got
included in.
TEXT;
    }
}

