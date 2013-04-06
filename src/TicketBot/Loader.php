<?php

namespace TicketBot;

class Loader
{
    /**
     * @var string
     */
    private $configDirectory;

    public function __construct($configDirectory)
    {
        $this->configDirectory = $configDirectory;
    }

    public function loadProject($username, $project)
    {
        if (strpos($username, "..") !== false || strpos($project, "..") !== false) {
            throw new \RuntimeException("Invalid project name given!");
        }

        $config = json_decode(file_get_contents($this->configDirectory . "/". $username."-".$project.".json"), true);
        $requiredValues = array("uri", "username", "password", "ticketType", "shortname", "hash", "assignUsername");;
        $project = new JiraProject();

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
}
