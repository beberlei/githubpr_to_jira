<?php

namespace TicketBot\Jira;

use TicketBot\Jira;
use TicketBot\JiraProject;
use TicketBot\NewJiraIssue;
use TicketBot\JiraIssue;
use Jira_Api;

class JiraRest implements Jira
{
    private $api;

    public function __construct(Jira_Api $api)
    {
        $this->api = $api;
    }

    public function search(JiraProject $project, array $terms)
    {
        $issues = array();

        foreach ($terms as $term) {
            $result = $this->api->search($term);

            foreach ($result->getIssues() as $issue) {
                $issues[$issue->getKey()] = JiraIssue::createFromArray($issue->getFields());
            }
        }

        return $issues;
    }

    public function createIssue(JiraProject $project, NewJiraIssue $newIssue)
    {
        $result = $this->api->createIssue(
            $project->shortname,
            $newIssue->title,
            $project->ticketType,
            array(
                'assigne' => $project->assignUsername,
                'description' => $newIssue->body
            )
        );

        $key = $result->getResult()["key"];

        $issueResult = $this->api->getIssue($key);

        return JiraIssue::createFromArray($issueResult->getResult());
    }

    public function addComment(JiraIssue $issue, $comment)
    {
        $this->api->addComment($issue->getId(), array('body' => $comment));
    }

    public function resolveIssue(JiraIssue $issue)
    {
        $this->api->transition($issue->getId(), array(
            'update' => array('comment' => array('add' => array('comment' => 'Resolved')))
        ));
    }

    public function markIssueInvalid(JiraIssue $issue)
    {
    }
}
