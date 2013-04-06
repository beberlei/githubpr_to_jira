<?php

namespace TicketBot;

/**
 * Synchronize between Github and Jira Service
 */
class Synchronizer
{
    private $jira;
    private $github;

    public function __construct(Jira $jira, Github $github)
    {
        $this->jira = $jira;
        $this->github = $github;
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
            $issue = $this->jira->createIssue($project, $newIssue);



            $this->github->addComment(
                $pullRequestEvent->owner(),
                $pullRequestEvent->repository(),
                $pullRequestEvent->getId(),
                $project->createNotifyComment($issue, $pullRequestEvent->isSendToMaster())
            );

            return true;
        }

        foreach ($issues as $issue) {
            $this->jira->addComment($issue, $project->createComment($pullRequestEvent));
        }

        return true;
    }
}
