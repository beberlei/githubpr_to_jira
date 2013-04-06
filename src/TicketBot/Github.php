<?php

namespace TicketBot;

use Github\Client;

class Github
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function addComment($username, $repository, $issue, $message)
    {
        $this->client
             ->api('issue')
             ->comments()
             ->create($username, $repository, $issue, array('body' => $message));
    }
}
