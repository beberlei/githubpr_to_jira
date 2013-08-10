<?php

namespace TicketBot\Jira;

use TicketBot\Jira;
use TicketBot\JiraProject;
use TicketBot\NewJiraIssue;
use TicketBot\JiraIssue;

use Zend\XmlRpc\Client;

class JiraXmlRpc implements Jira
{
    private $client;
    private $token;

    static public function create(JiraProject $project)
    {
        $client = new Client($project->uri . "/rpc/xmlrpc");
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
        $data =  $this->client->call("jira1.createIssue", $payload);

        return JiraIssue::createFromArray($data);
    }

    public function addComment(JiraIssue $issue, $comment)
    {
        return $this->client->call("jira1.addComment", array($this->token, $issue->key, $comment));
    }

    public function resolveIssue(JiraIssue $issue)
    {
        $this->client->call("jira1.updateIssue", array(
            $this->token,
            $issue->key,
            array("resolution" => array('id' => 1), "status" => array('id' => 5))
        ));
    }

    public function markIssueInvalid(JiraIssue $issue)
    {
        $this->client->call("jira1.updateIssue", array(
            $this->token,
            $issue->key,
            array("resolution" => array('id' => 6), "status" => array('id' => 5))
        ));
    }
}
