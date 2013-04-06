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

    public function accept(PullRequestEvent $pullRequestEvent, JiraProject $project)
    {
        if ($pullRequestEvent->isSynchronize()) {
            // this is triggered way to often by Github
            return false;
        }

        if ($pullRequestEvent->isOpened()) {
            $newIssue = $project->createTicket($pullRequestEvent);
            $jiraIssue = $this->jira->createIssue($project, $newIssue);

            $this->github->addComment(
                $pullRequestEvent->owner(),
                $pullRequestEvent->repository(),
                $pullRequestEvent->getId(),
                $project->createNotifyComment($jiraIssue, $pullRequestEvent->isSendToMaster())
            );

            return true;
        }

        $issues = $this->jira->search($pullRequestEvent->searchTerms($project));

        foreach ($issues as $issue) {
            $this->jira->addComment($issue, $project->createComment($pullRequestEvent));

            if ($pullRequestEvent->isClosed() && $pullRequestEvent->isMerged()) {
                $this->jira->resolveIssue($issue);
            }

            if ($pullRequestEvent->isClosed() && ! $pullRequestEvent->isMerged()) {
                $this->jira->markIssueInvalid($issue);
            }

            if ($pullRequestEvent->isReopened()) {
                $this->jira->reopenIssue($issue);
            }
        }


        return true;
    }
}
