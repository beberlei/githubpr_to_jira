<?php
/*
 * Github PR to Jira Ticket
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

if (!isset($argv[1]) || !file_exists($argv[1])) {
    echo "You have to pass a file name as first argument.\n";
    exit(1);
}

$config = json_decode(file_get_contents($argv[1]), true);
$requiredValues = array("jiraXmlRpcEndpoint", "jiraUsername", "jiraPassword", "jiraTicketType", "jiraTicketTemplate", "daysBack", "projects");
$defaultValues = array(
    "jiraTicketType" => 4,
    "daysBack" => 14,
    "jiraTicketTemplate" => <<<ISSUETEXT
This issue is created automatically through a Github pull request on behalf of {username}:

  Url: {url}

Message:

{body}

ISSUETEXT
);

foreach ($requiredValues AS $key) {
    if (!isset($config[$key])) {
        if (isset($defaultValues[$key])) {
            $config[$key] = $defaultValues[$key];
        } else {
            echo "Missing configuration value '".$key."' in config.json file.\n";
            exit(1);
        }
    }
}

require_once "Zend/Loader/Autoloader.php";

Zend_Loader_Autoloader::getInstance(); // autoload enabled

$client = new Zend_XmlRpc_Client($config['jiraXmlRpcEndpoint']);

$token = $client->call("jira1.login", array($config['jiraUsername'], $config['jiraPassword']));
$now = new \DateTime();

foreach ($config['projects'] AS $projectName => $jiraProjectShortname) {
    echo "Working on project: " . $projectName . "\n";
    $githubOrganizationName = $config['githubOrganizationName'];
    $pullRequests = json_decode(file_get_contents("https://api.github.com/repos/$githubOrganizationName/$projectName/pulls"));

    foreach ($pullRequests AS $pullRequest) {
        $issueUrl = $pullRequest->html_url;
        $parts = explode("/", $issueUrl);
        $pullRequestId = array_pop($parts);
        $issuePrefix = "Github-PR-".$pullRequestId;

        $created = new \DateTime($pullRequest->created_at);
        if ($created->modify("+".$config['daysBack']." day") < $now) {
            continue;
        }

        echo "Found PR: " . $issueUrl . "\n";

        $data = $client->call("jira1.getIssuesFromTextSearch", array($token, '"' . $issueUrl . '"'));
        
        if (count($data) == 0) {
            echo "..Synchronized.\n";

            $body = str_replace(
                array("{user}", "{url}", "{body}"),
                array($pullRequest->user->login, $issueUrl, $pullRequest->body),
                $config['jiraTicketTemplate']
            );

            $data = $client->call("jira1.createIssue", array($token, array(
                "summary"       => $issuePrefix . " by " . $pullRequest->user->login . ": " . $pullRequest->title,
                "project"       => $jiraProjectShortname,
                "description"   => $body,
                "type"          => $config['jiraTicketType'],
                "assignee"      => $config['jiraUsername'],
            )));
        } else {
            echo "..Already found\n";
        }
    }
}
