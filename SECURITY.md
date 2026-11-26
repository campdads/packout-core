# Security

## Reporting a vulnerability

Please report it privately. Do not open a public issue or pull request for a security problem.

Use GitHub’s private reporting: on this repository, open the **Security** tab, choose **Advisories**, then **Report a vulnerability**. Only the maintainers see the report.

Say what you found, how to reproduce it, and which version you used. If you have a fix in mind, say so, and leave it out of public view until a release is out.

## What to expect

- An acknowledgement within five working days.
- A fix or a plan, and a date, once the report is confirmed.
- Credit in the release notes, unless you would rather not be named.

## What is in scope

This library, as carried by the Pack-Out Spot plugins. That includes the release keys and signature check in `src/class-release.php` and the update client in `src/class-updates.php`.

A problem in one of the plugins is reported on that plugin’s repository. A problem with the packout.spot site itself can be reported here too.

## Supported versions

The latest released version. A fix is released as a new version, and each plugin then takes it.
