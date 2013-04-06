<?php

namespace TicketBot;

class JiraIssue
{
    public $environment;
    public $status;
    public $reporter;
    public $fixVersions = array();
    public $resolution;
    public $key;
    public $type;
    public $updated;
    public $priority;
    public $components;
    public $affectedVersions = array();
    public $assignee;
    public $summary;
    public $customFieldValues = array();
    public $votes;
    public $id;
    public $description;
    public $project;
    public $created;

    static public function createFromArray(array $data)
    {
        $issue = new self();
        foreach ($data as $k => $v) {
            if (property_exists($issue, $k)) {
                $issue->$k = $v;
            }
        }
        return $issue;
    }
}
