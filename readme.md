Chaperone4php 
---------------

Chaperone4php is a simple framework for writing unattended processes in PHP 
(scripts run by cron or some other scheduler).  Think of it as the equivalent of 
Symfony or Laravel but focused strictly on unattended processes.

#Motivation#
Unattended processes in PHP are simple to write but hard to maintain.  
Specifically, unattended processes never get the full complement of checks, 
preconditions, logging, and alerting that they deserve.  This is because people 
don't actually see them run, they just set them up to run and forget about them 
until something goes wrong. Also, there is a cost-benefit analysis that us 
developers do internally; we usually won't bother coding around scenarios that 
are highly unlikely (low disk space, no network access).

#Status#
Chaperone4php is in active development, focusing on its first release. See 
doc/99-roadmap.md for details and progress.

#Why do I need it?#
Chaperone4php provides you with the following:

1. a robust set of "pre-conditions"; checks done on script dependencies.  For 
example, checks to make sure a directory is writable, whether the script can 
connect to a database, and so forth.

2. an API that's laser-focused on backend processes that run "batch" operations; 
for example getting huge lists of database records from sizable tables in a 
memory-efficient way; bulk loading thousands of records into a database table 
in a fast manner.

3. A sane logging methodology that gives you visibility into your processes, yet
won't spam you when things are going ok.

#What Chaperone4php isn't#
Chaperone4php is NOT

* a replacement for CRON
* a message queue, or message broker
* a replacement for Symfony or Laravel or any other web framework

#License#
This project is licensed under the Apache License Version 2.0.  The software
can be used freely, but is provided on an "as-is" basis and comes with no 
warranties.

#Requirements#
This project will work on PHP version 5.3+. It will work primarily on Linux
platforms; it may also work on MS Windows.

#Installation#
Chaperone4php can be easily installed via Composer; just add a new require
and run composer install.

``
{
    "require": {
        "robertop/chaperone4php": "*"
    }
}
``

#FAQ#
1. I already use the Symfony Console. Why should I use Chaperone4php?

   Symfony is tailored for web applications, while Chaperone4php is tailored 
   for background processes. Chaperone4php provides many features helpful for
   writing background processes that Symfony does not.

2. How difficult is it to integrate Chaperone4php into my existing scripts?

   Chaperone4php is not an application container of any sort; it has no 
   bootstrapping other than requiring an autoloader which is already provided
   by Composer. Just start using the provided objects as desired! You can
   integrate as much or as little of Chaperone4php into your apps as you
   desire.
