<?php

namespace TicketBot;

class JiraTest extends \PHPUnit_Framework_TestCase
{
    private $client;
    private $token;
    private $jira;

    public function setUp()
    {
        $this->client = \Phake::mock('Zend\XmlRpc\Client');
        $this->token = new \stdClass;
        $this->jira = new Jira($this->client, $this->token);
    }

    public function testSearch()
    {
        \Phake::when($this->client)
            ->call("jira1.getIssuesFromTextSearch", array($this->token, '"foo"'))
            ->thenReturn(array());
        \Phake::when($this->client)
            ->call("jira1.getIssuesFromTextSearch", array($this->token, '"bar"'))
            ->thenReturn(array());

        $issues = $this->jira->search(array("foo", "bar"));

        $this->assertInternalType('array', $issues);
    }

    public function testCreateIssue()
    {
        \Phake::when($this->client)->call(\Phake::anyParameters())->thenReturn(array());

        $project = new JiraProject();
        $project->assignUsername = "beberlei";
        $project->shortname = "DDC";
        $project->ticketType = 1;

        $this->jira->createIssue($project, new NewJiraIssue("some title", "some body"));

        \Phake::verify($this->client)
            ->call('jira1.createIssue', array(
                $this->token, array(
                    "summary" => "some title",
                    "project" => "DDC",
                    "description" => "some body",
                    "type" => 1,
                    "assignee" => "beberlei",
                )
            ));
    }

    public function testaddComment()
    {
        $issue = new JiraIssue();
        $issue->key = "DDC";

        $this->jira->addComment($issue, "some comment");

        \Phake::verify($this->client)
            ->call('jira1.addComment', array($this->token, 'DDC', 'some comment'));
    }
}

