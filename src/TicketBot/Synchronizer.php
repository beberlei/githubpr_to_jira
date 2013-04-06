<?php

namespace TicketBot;

/**
 * Synchronize between Github and Jira Service
 */
class Synchronizer
{
    private $jira;

    public function __construct(Jira $jira)
    {
        $this->jira = $jira;
    }

    public function synchronizePullRequest(PullRequestEvent $pullRequestEvent, JiraProject $project)
    {
        if ($pullRequestEvent->isSynchronize()) {
            // this is triggered way to often by Github
            return false;
        }

        $issues = $this->jira->search($pullRequestEvent->searchTerms($project));

        if ($pullRequestEvent->isOpened()) {
            $newIssue = $project->createTicket($pullRequestEvent);
            $this->jira->createIssue($project, $newIssue);

            return true;
        }

        foreach ($issues as $issue) {
            $this->jira->addComment($issue, $project->createComment($pullRequestEvent));
        }

        return true;
    }
}
