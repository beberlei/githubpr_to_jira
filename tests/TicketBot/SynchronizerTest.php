<?php

namespace TicketBot;

class SynchronizerTest extends \PHPUnit_Framework_TestCase
{
    public function testOpenedPullRequest()
    {
        $event = $this->createPullRequestEvent('opened');

        $jira = \Phake::mock('TicketBot\Jira');
        $github = \Phake::mock('TicketBot\Github');

        $project = new JiraProject();

        \Phake::when($jira)->createIssue(\Phake::anyParameters())->thenReturn(new JiraIssue);

        $synchronizer = new Synchronizer($jira, $github);
        $synchronizer->accept($event, $project);

        \Phake::verify($github)->addComment("bar", "foo", 127, <<<TEXT
Hello,

thank you for creating this pull request. I have automatically opened an issue
on our Jira Bug Tracker for you. See the issue link:

/browse/

We use Jira to track the state of pull requests and the versions they got
included in.
TEXT
        );
    }

    public function testUpdatedPullRequest()
    {
        $event = $this->createPullRequestEvent('closed');

        $jira = \Phake::mock('TicketBot\Jira');
        $github = \Phake::mock('TicketBot\Github');

        $project = new JiraProject();

        \Phake::when($jira)->search(\Phake::anyParameters())->thenReturn(array(
            $issue = JiraIssue::createFromArray(array("key" => "DDC-1234"))
        ));

        $synchronizer = new Synchronizer($jira, $github);
        $synchronizer->accept($event, $project);

        \Phake::verify($jira)->addComment($issue, "A related Github Pull-Request [GH-127] was closed:\nhttps://github.com/doctrine/doctrine2/pulls/127");
    }

    private function createPullRequestEvent($action)
    {
        $event = new PullRequestEvent(array(
            'action' => $action,
            'pull_request' => array(
                'html_url' => 'https://github.com/doctrine/doctrine2/pulls/127',
                'user' => array('login' => 'beberlei'),
                'title' => 'some title',
                'body' => 'some body',
                'base' => array(
                    'ref' => 'master',
                    'repo' => array('name' => 'foo', 'owner' => array('login' => 'bar')),
                ),
                'merged' => true,
            )
        ));

        return $event;
    }
}
