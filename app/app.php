<?php
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new Application();
$app->post('/jira/{username}/{project}/accept-pull', function($username, $project, Application $app, Request $request) {
    $project = loadProject($username, $project);
    synchronizePullRequest($request->get('payload'), $project);

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
    public $projectShortname;
    public $ticketType;
    public $assignUsername;
    public $template;
}

function synchronizePullRequest($pullRequest, JiraProject $project)
{
    $client = new \Zend\XmlRpc\Client($project->uri);
    $token = $client->call("jira1.login", array($project->username, $project->password));

    $issueUrl = $pullRequest->html_url;
    $parts = explode("/", $issueUrl);
    $pullRequestId = array_pop($parts);
    $issuePrefix = "[GH-".$pullRequestId."]";

    $created = new \DateTime($pullRequest->created_at);
    if ($created->modify("+14 day") < $now) {
        return false;
    }

    $data = $client->call("jira1.getIssuesFromTextSearch", array($token, '"' . $issueUrl . '"'));

    if (count($data) == 0) {
        $body = str_replace(
            array("{user}", "{url}", "{body}"),
            array($pullRequest->user->login, $issueUrl, $pullRequest->body),
            $project->template
        );

        $data = $client->call("jira1.createIssue", array($token, array(
            "summary"       => $issuePrefix . " by " . $pullRequest->user->login . ": " . $pullRequest->title,
            "project"       => $jiraProjectShortname,
            "description"   => $body,
            "type"          => $project->ticketType,
            "assignee"      => $project->assignUsername
        )));
        return true;
    }
    return false;
}

function loadProject($username, $project)
{
    if (strpos($username, "..") !== false || strpos($project, "..") !== false) {
        throw new \RuntimeException("Invalid project name given!");
    }

    $config = json_decode(file_get_contents(__DIR__. "/../config/". $username."-".$project.".json"));
    $requiredValues = array("uri", "username", "password", "ticketType", "projectShortname", "hash");;
    $project = new JiraProject();
    $project->ticketType = 4;
    $project->template = <<<ISSUETEXT
This issue is created automatically through a Github pull request on behalf of {username}:

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

