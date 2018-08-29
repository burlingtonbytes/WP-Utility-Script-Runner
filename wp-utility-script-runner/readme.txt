=== WP Utility Script Runner ===
Contributors: burlingtonbytes, gschoppe
Tags: developers, utilities, run-once, cron, task, custom, toolkit, framework
Requires at least: 4.6
Tested up to: 4.9
Stable tag: trunk
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create custom scripts and manage them directly from the WordPress Dashboard. Schedule scripts, handle user input, download reports, and more.

== Description ==

Sometimes, you run into a situation where you just need to manually run a script. The actual logic might be as simple as generating a csv from a query, or changing a user's settings, or even just running a single line of SQL. WordPress makes this difficult.

There are many hacky solutions to the problem, but most of them lack security or are too difficult for anyone but a developer to run or just take too long to build. That's where we come in.

WP Utility Script Runner lets you start with a simple 17 line template, add in your custom code, save to the server, and in minutes you have a secure, fully featured utility, that you can safely run from the WordPress dashboard.

But that's not all! With a few extra lines of code, your utility can:

*   Accept user input
*   Accept file input
*   Run at a future date and time
*   Run on a recurring schedule
*   Run large tasks by breaking them up into several smaller tasks, and saving state.
*   Create reports and other output files

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Settings->Utility Scripts screen to manage and run utility scripts


== Frequently Asked Questions ==

= Why would I ever need to run a script on-demand, rather than on page load? =

There are many times running on page load is not the best option:

*   You may want to ensure that a task only happens once, when many people may be visiting the page
*   You may need to capture some form of output from the script, without showing it to all users
*   You may have multiple team members working on the site, and not want to harm their workflow with your debugging output
*   You may need to be able to have non-developers run the script periodically, for tasks like report generation

There are too many other use cases to count. If you don't see a use case, you probably don't need this tool, and that's ok.

= Why not just write some custom PHP and tuck it in a subfolder? =

There are many caveats to taking this approach, but mostly it just leaves you writing a lot of (probably insecure) code.

To build a custom script properly, you need to:

*   Handle security on your own.
*   Either duplicate or side-load any WordPress functions you need
*   Build an interface and form handler
*   Handle downloads
*   Handle cron
*   Get around PHP_MAX_EXECUTION_TIME

That can be a massive amount of code, for a script that could be as simple as a single SQL query.

= Why not WP-CLI? =

We love WP-CLI, but sometimes the command line is just not the right tool for the job. Often you can't get WP-CLI on shared hosts, and users who may need to use the utilities may not be comfortable with the command line. But stay tuned, WP-CLI integration is on our roadmap.

= Why not (Insert Solution Here)? =

The most common answer is feature completion and ease of access. A utility script can contain as little as 17 lines of overhead, before you are free and clear to write whatever you need. We dare you to find a lighter-weight, more feature-complete option. We wrote WP Utility Script Runner because there wasn't one.

= Is it possible to get down to under 17 lines of boilerplate? =

Technically, yes, but you'll be sacrificing a lot of features. You can write your utility in "legacy mode", where it can be written as simply as:

```
<?php
// Utility Name: My Legacy Utility
// Description: An example of a legacy utility
echo "Hello World"; // your custom code goes here
```

You will not get native handling for inputs, files, state, downloads, or error output, but it is pretty darn short.

= Why aren't there any useful utilities bundled in? =

Most of the time, we find that Utility Scripts are very custom to the specific site and hosting environment. We have plans to build a library of useful scripts, but for now, we just included a couple examples to help you get started writing your own.

= Your plugin is bad, and You should feel bad! =

While that's not exactly a question, we do take bug reports and reviews very seriously. In addition, you can always contact us directly with your thoughts, at support@burlingtonbytes.com.

== Screenshots ==

1. The simplest version of a utility only has 17 lines of required code.
2. Utilities can be enabled and disabled from the manage tab
3. Scripts are tucked away behind accordions, to prevent accidentally running them.
4. Output is presented in real-time, without refreshing the page

== Changelog ==

= 08/29/2018 - 1.1.0 =
* Bugfix for form fields with hyphens in name improperly serializing

= 11/16/2017 - 1.0.1 =
* Bugfix for sample Comment2Post utility

= 08/19/2017 - 1.0.0 =
* Initial Release!
