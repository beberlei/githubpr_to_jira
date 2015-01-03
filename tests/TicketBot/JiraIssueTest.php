<?php

namespace TicketBot;

class JiraIssueTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_belongs_to_pr_with_same_issue_prefix()
    {
        $issue = JiraIssue::createFromArray(array('summary' => '[GH-123] Foo'));
        $event123 = new PullRequestEvent(array('action' => 'opened', 'pull_request' => array('html_url' => 'foo/123')));
        $event124 = new PullRequestEvent(array('action' => 'opened', 'pull_request' => array('html_url' => 'foo/124')));

        $this->assertTrue($issue->belongsTo($event123));
        $this->assertFalse($issue->belongsTo($event124));
    }
}
