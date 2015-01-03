<?php

namespace TicketBot;

class PullRequestEventTest extends \PHPUnit_Framework_TestCase
{
    public function testIsSynchronize()
    {
        $event = new PullRequestEvent(array('action' => 'synchronize', 'pull_request' => array('html_url' => 'http')));

        $this->assertTrue($event->isSynchronize());
        $this->assertFalse($event->isOpened());
    }

    public function testIsOpened()
    {
        $event = new PullRequestEvent(array('action' => 'opened', 'pull_request' => array('html_url' => 'http')));

        $this->assertFalse($event->isSynchronize());
        $this->assertTrue($event->isOpened());
    }

    public function testIssuePrefix()
    {
        $event = new PullRequestEvent(array('action' => 'synchronize', 'pull_request' => array('html_url' => 'https://github.com/doctrine/doctrine2/pulls/127')));
        $this->assertEquals('[GH-127]', $event->issuePrefix());
    }

    public function testMetadata()
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

        $this->assertEquals('beberlei', $event->openerUsername());
        $this->assertEquals('some title', $event->title());
        $this->assertEquals('some body', $event->body());
    }

    public function testSearchTerms()
    {
        $event = new PullRequestEvent(array(
            'action' => 'synchronize',
            'pull_request' => array(
                'html_url' => 'https://github.com/doctrine/doctrine2/pulls/127',
                'user' => array('login' => 'beberlei'),
                'title' => '[DDC-1234] Doing foo with pride',
                'body' => 'Hello, talking about DDC-4567.',
            )
        ));

        $project = new JiraProject();
        $project->shortname = "DDC";

        $terms = $event->searchTerms($project);

        $this->assertEquals(array(
            'https://github.com/doctrine/doctrine2/pulls/127',
            'DDC-1234',
            'DDC-4567',
        ), $terms);
    }
}
