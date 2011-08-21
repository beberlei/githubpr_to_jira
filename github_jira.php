<?php

$jiraXmlRpcEndpoint = "http://my-jira/jira/rpc/xmlrpc";
$jiraUsername = "user";
$jiraPassword = "passwd";
$jiraTicketType = 4; // Improvement;

$jiraTicketTemplate = <<<ISSUETEXT
This issue is created automatically through a Github pull request on behalf of {username}:

  Url: {url}

Message:

{body}

ISSUETEXT;

$daysBack = 14;
$githubOrganizationName = "doctrine";
$projects = array(
    // "Repo Name" => "Jira Project Shortcut"
);

require_once "Zend/Loader/Autoloader.php";

Zend_Loader_Autoloader::getInstance(); // autoload enabled

$client = new Zend_XmlRpc_Client($jiraXmlRpcEndpoint);

$token = $client->call("jira1.login", array($jiraUsername, $jiraPassword));
$now = new \DateTime();

foreach ($projects AS $projectName => $jiraProjectId) {
    echo "Working on project: " . $projectName . "\n";
    $pullRequests = json_decode(file_get_contents("https://api.github.com/repos/$githubOrganizationName/$projectName/pulls"));

    foreach ($pullRequests AS $pullRequest) {
        $issueUrl = $pullRequest->html_url;
        $parts = explode("/", $issueUrl);
        $pullRequestId = array_pop($parts);
        $issuePrefix = "Github-PR-".$pullRequestId;

        $created = new \DateTime($pullRequest->created_at);
        if ($created->modify("+".$daysBack." day") < $now) {
            continue;
        }

        echo "Found PR: " . $issueUrl . "\n";

        $data = $client->call("jira1.getIssuesFromTextSearch", array($token, $issuePrefix));
        
        if (count($data) == 0) {
            echo "..Synchronized.\n";

            $body = str_replace(
                array("{user}", "{url}", "{body}"),
                array($pullRequest->user->login, $issueUrl, $pullRequest->body),
                $jiraTicketTemplate
            );

            $data = $client->call("jira1.createIssue", array($token, array(
                "summary"       => $issuePrefix . " by " . $pullRequest->user->login . ": " . $pullRequest->title,
                "project"       => $jiraProjectId,
                "description"   => $body,
                "type"          => $jiraTicketType,
                "assignee"      => $jiraUsername,
            )));
        } else {
            echo "..Already found\n";
        }
    }
}


