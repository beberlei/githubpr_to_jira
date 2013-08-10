<?php

namespace TicketBot;

interface Jira
{
    public function search(array $terms);
    public function createIssue(JiraProject $project, NewJiraIssue $newIssue);
    public function addComment(JiraIssue $issue, $comment);
    public function resolveIssue(JiraIssue $issue);
    public function markIssueInvalid(JiraIssue $issue);
}
