<?php

namespace TicketBot;

class JiraProjectTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateTicket()
    {
        $event = new PullRequestEvent(array(
            'action' => 'synchronize',
            'pull_request' => array(
                'html_url' => 'https://github.com/doctrine/doctrine2/pulls/127',
                'user' => array('login' => 'beberlei'),
                'title' => 'some title',
                'body' => 'some body',
            )
        ));

        $project = new JiraProject();
        $issue = $project->createTicket($event);

        $this->assertInstanceOf('TicketBot\NewJiraIssue', $issue);
        $this->assertEquals('[GH-127] some title', $issue->title);
        $this->assertEquals(<<<ASSERT
This issue is created automatically through a Github pull request on behalf of beberlei:

Url: https://github.com/doctrine/doctrine2/pulls/127

Message:

some body

ASSERT
            , $issue->body);
    }

    public function testCreateComment()
    {
        $event = new PullRequestEvent(array(
            'action' => 'synchronize',
            'pull_request' => array(
                'html_url' => 'https://github.com/doctrine/doctrine2/pulls/127',
                'user' => array('login' => 'beberlei'),
                'title' => 'some title',
                'body' => 'some body',
            )
        ));

        $project = new JiraProject();
        $comment = $project->createComment($event);

        $this->assertEquals("A related Github Pull-Request [GH-127] was synchronize:\nhttps://github.com/doctrine/doctrine2/pulls/127", $comment);
    }
}
