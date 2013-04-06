<?php

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use TicketBot\Synchronizer;
use TicketBot\Loader;
use TicketBot\Jira;
use TicketBot\Github;
use TicketBot\PullRequestEvent;

use Github\Client;

$app = new Application();
$app->post('/synchronizer/accept', function(Application $app, Request $request) {

    $event = new PullRequestEvent(json_decode($request->get('payload'), true));

    $loader = new Loader(__DIR__ . "/../config");

    $project = $loader->loadProject($event->owner(), $event->repository());

    if ($project->hash !== $request->get('hash')) {
        throw new \RuntimeException("Invalid access token!");
    }

    $client = new Client();
    $client->authenticate($request->server->get('GITHUB_OAUTH_TOKEN'));

    $github = new Github($client);
    $synchronizer = new Synchronizer(Jira::create($project), $github);

    $synchronizer->accept($event, $project);

    return new Response('{"ok":true}', 201, array('Content-Type' => 'application/json'));
});

$app->error(function (\Exception $e, $code) {
    syslog(LOG_INFO, "JIRA Error [" . $code . "]: " . $e->getMessage() . $e->getTraceAsString());

    return new Response(json_encode(array('error' => true, 'code' => $code)), $code, array('Content-Type' => 'application/json'));
});


