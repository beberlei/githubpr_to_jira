<?php

namespace TicketBot;

use TicketBot\Jira\JiraXmlRpc;

class JiraXmlRpcTest extends \PHPUnit_Framework_TestCase
{
    private $client;
    private $token;
    private $jira;

    public function setUp()
    {
        $this->client = \Phake::mock('Zend\XmlRpc\Client');
        $this->token = new \stdClass;
        $this->jira = new JiraXmlRpc($this->client, $this->token);
    }

    public function testSearch()
    {
        $project = new JiraProject(array('shortname' => 'DDC'));
        \Phake::when($this->client)
            ->call("jira1.getIssuesFromTextSearchWithProject", array($this->token, array('DDC'), '"foo"', 10))
            ->thenReturn(array());
        \Phake::when($this->client)
            ->call("jira1.getIssuesFromTextSearchWithProject", array($this->token, array('DDC'), '"bar"', 10))
            ->thenReturn(array());

        $issues = $this->jira->search($project, array("foo", "bar"));

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

    public function testSearchIntegration()
    {
        if (!isset($_SERVER['JIRA_USERNAME']) || !isset($_SERVER['JIRA_URI']) || !isset($_SERVER['JIRA_PASSWORD'])) {
            $this->markTestSkipped("Run test with JIRA_URI=http JIRA_USERNAME=foo JIRA_PASSWORD=bar to test integration for search.");
        }

        $project = new JiraProject(array(
            'shortname' => 'DDC',
            'uri' => $_SERVER['JIRA_URI'],
            'username' => $_SERVER['JIRA_USERNAME'],
            'password' => $_SERVER['JIRA_PASSWORD'],
        ));
        $jira = JiraXmlRpc::create($project);

        $issues = $jira->search($project, array('Embedded'));

        $this->assertTrue(count($issues) > 0);
        $this->assertContainsOnly('TicketBot\JiraIssue', $issues);
    }
}

