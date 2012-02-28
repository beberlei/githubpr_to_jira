# Github PRs to Jira Issues

Simple Silex Application that listens to Github PR Hook API and transforms the PRs to Jira Tickets.

You have to configure your jira user account, organization/github username, and the projects you want to "observe".

Dependency: Zend Framework XML RPC

## How it works

Look at the example.json and copy it to config/githubuser-githubrepo.json (example config/doctrine-doctrine2.json).
Adjust all the values and add the projects you want to observe.

Add a pull-request hook to the github repository. You might need to use the API for this (no interface yet).

