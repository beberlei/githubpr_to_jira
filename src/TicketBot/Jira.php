<?php

namespace TicketBot;

use Zend\XmlRpc\Client;

class Jira
{
    private $client;
    private $token;

    static public function create(JiraProject $project)
    {
        $client = new Client($project->uri);
        $token = $client->call("jira1.login", array($project->username, $project->password));

        return new self($client, $token);
    }

    public function __construct($client, $token)
    {
        $this->client = $client;
        $this->token = $token;
    }

    public function search(array $terms)
    {
        $issues = array();

        foreach ($terms as $term) {
            $data = $this->client->call("jira1.getIssuesFromTextSearch", array($this->token, '"' . $term . '"'));

            foreach ($data as $row) {
                $issue = JiraIssue::createFromArray($row);
                $issues[$issue->key] = $issue;
            }
        }

        return $issues;
    }

    public function createIssue(JiraProject $project, NewJiraIssue $newIssue)
    {
        $payload = array($this->token, array(
            "summary"       => $newIssue->title,
            "project"       => $project->shortname,
            "description"   => $newIssue->body,
            "type"          => $project->ticketType,
            "assignee"      => $project->assignUsername
        ));
        return $this->client->call("jira1.createIssue", $payload);
    }

    public function addComment(JiraIssue $issue, $comment)
    {
        return $this->client->call("jira1.addComment", array($this->token, $issue->key, $comment));
    }
}
