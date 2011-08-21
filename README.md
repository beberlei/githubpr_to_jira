# Github PRs to Jira Issues

If you are using Jira for your project and have your code on github then you have two sources of potential change.
This is annoying to manage.

This script can be executed as cron every n-th minute.

You have to put in your jira user account, organization/github username, and the projects you want to "observe".

Dependency: Zend Framework XML RPC

## How it works

Look at the example.json and copy it to yourproject.json. Adjust all the values and add the projects
you want to observe.

1. Grab all open pull requests from Github project
2. Check if the PR url can be found in any ticket of the associated jira project
3. If no ticket is found with the url, create a new ticket linking to the Github PR.

