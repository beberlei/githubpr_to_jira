<?php
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new Application();
$app->post('/jira/{username}/{project}/accept-pull', function($username, $project, Application $app, Request $request) {
    $project = loadProject($username, $project);
    if ($project->hash != $request->get('hash')) {
        throw new \RuntimeException("Invalid access token!");
    }
    synchronizePullRequest(json_decode($request->get('payload')), $project);

    return new Response('{"ok":true}', 201, array('Content-Type' => 'application/json'));
});

$app->error(function (\Exception $e, $code) {
    syslog(LOG_INFO, "JIRA Error [" . $code . "]: " . $e->getMessage());

    return new Response(json_encode(array('error' => true, 'code' => $code)), $code, array('Content-Type' => 'application/json'));
});

class JiraProject
{
    public $hash;
    public $uri;
    public $username;
    public $password;
    public $shortname;
    public $ticketType;
    public $assignUsername;
    public $template;
}

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

function searchJiraIssues($client, $token, array $terms)
{
    $issues = array();
    foreach ($terms as $term) {
        $data = $client->call("jira1.getIssuesFromTextSearch", array($token, '"' . $term . '"'));
        foreach ($data as $row) {
            $issue = JiraIssue::createFromArray($row);
            $issues[$issue->key] = $issue;
        }
    }
    return $issues;
}

function synchronizePullRequest($pullRequestEvent, JiraProject $project)
{
    if ( ! isset($pullRequestEvent->action)) {
        throw new \RuntimeException("Missing action in Pull Request");
    }
    $pullRequest = $pullRequestEvent->pull_request;
    if (!isset($pullRequest->html_url)) {
        throw new \RuntimeException("Missing html url in Pull Request");
    }

    $client = new \Zend\XmlRpc\Client($project->uri);
    $token = $client->call("jira1.login", array($project->username, $project->password));

    $issueUrl = $pullRequest->html_url;
    $parts = explode("/", $issueUrl);
    $pullRequestId = array_pop($parts);
    $issuePrefix = "[GH-".$pullRequestId."]";

    $now = new \DateTime("now");
    $created = new \DateTime($pullRequest->created_at);
    if ($created->modify("+14 day") < $now) {
        return false;
    }

    $issueSearchTerms = array($issueUrl, $issuePrefix);
    if (preg_match_all('((' . preg_quote($project->shortname) . '\-[0-9]+))', $pullRequest->title . " " . $pullRequest->body, $matches)) {
        $issueSearchTerms = array_values(array_unique($matches[1]));
    }
    $issues = searchJiraIssues($client, $token, $issueSearchTerms);

    if (count($issues) == 0 && in_array($pullRequestEvent->action, array('opened', 'synchronized'))) {
        $body = str_replace(
            array("{user}", "{url}", "{body}"),
            array($pullRequest->user->login, $issueUrl, $pullRequest->body),
            $project->template
        );

        $data = $client->call("jira1.createIssue", array($token, array(
            "summary"       => $issuePrefix . " " . $pullRequest->title,
            "project"       => $project->shortname,
            "description"   => $body,
            "type"          => $project->ticketType,
            "assignee"      => $project->assignUsername
        )));
    } else {
        $comment = "A related Github Pull-Request " . $issuePrefix . " was " . $pullRequestEvent->action . "\n";
        $comment .= $issueUrl;

        foreach ($issues as $issue) {
            $client->call("jira1.addComment", array($token, $issue->key, $comment));
        }
    }
    return true;
}

function loadProject($username, $project)
{
    if (strpos($username, "..") !== false || strpos($project, "..") !== false) {
        throw new \RuntimeException("Invalid project name given!");
    }

    $config = json_decode(file_get_contents(__DIR__. "/../config/". $username."-".$project.".json"), true);
    $requiredValues = array("uri", "username", "password", "ticketType", "shortname", "hash", "assignUsername");;
    $project = new JiraProject();
    $project->ticketType = 4;
    $project->template = <<<ISSUETEXT
This issue is created automatically through a Github pull request on behalf of {user}:

  Url: {url}

Message:

{body}

ISSUETEXT;

    foreach ($requiredValues AS $key) {
        if (!isset($config[$key])) {
            if (isset($defaultValues[$key])) {
                $project->$key = $defaultValues[$key];
            } else {
                throw new \RuntimeException("Missing configuration value for $key");
            }
        } else {
            $project->$key = $config[$key];
        }
    }

    return $project;
}

