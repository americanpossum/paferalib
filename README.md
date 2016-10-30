# paferalib

PHP Framework for Rapid Application Development

After a couple of years of hacking this in my spare time, I feel that my API is stable enough for other people to play around with this code as well. It's released under the GPL, so feel free to use it, fork it, or do anything else that you want as long as you send me any improvements.

## Basics

Paferalib is a set of components for developing web applications in PHP. It has a URL resolver, object cacher, database modeler, and all of the other convenience tools that you need in 2016. It's designed for use on any host that supports PHP 5, mod_rewrite, and MySQL or SQLite (although complex applications will really benefit from MySQL). It's lightweight enough to be useful on free or cheap hosting packages where bandwidth and/or disk space is limited, and is decently fast even on a Raspberry Pi.

### Installation

You can install paferalib simply by downloading the zip file and unzipping it into your web server's www directory. Afterwards, you can point your browser to your website and setup your database, scripts, and so forth using the administration interface.

### Directory Structure

The main structure is as follow:

root
+ .htaccess   # Rewrites short URLs
+ index.php   # Loads the library files and resolves the URL
+ apps        # All application code and resources live here
+ cache       # Caches compiled code, pages, and database queries
+ data        # User-created images, videos, etc...
+ libs        # External libraries
+ private     # Unreadable directory from the web; useful for security logs
+ paferalib   # Main library directory

### Application Structure

Each application can contain the following directories

+ admin        # Administration pages for the app
+ api          # APIs accessible via JSON
+ css          # Stylesheets
+ js           # JavaScript files
+ models       # Database models
+ pages        # Webpages
+ plugins      # Plugins to be run after the page content has been generated
+ translations # Translations in JSON format

Due to the rewrite rules in .htaccess, direct file access is limited. This is both for security and for convenience in using short URLs. 

### Short URLs

To shorten URLs and to save typing, resources for each application can be addressed using the following scheme:

+ apps/appname/pages/pagename   -> /appname/pagename
+ apps/appname/css/filename     -> /c/appname/filename
+ data/appname/filename         -> /d/appname/filename
+ apps/appname/images/filename  -> /i/appname/filename
+ apps/appname/js/filename      -> /j/appname/filename
+ apps/appname/sounds/filename  -> /s/appname/filename
+ apps/appname/videos/filename  -> /v/appname/filename

Let's say you created an app called test, your main page would be at apps/test/pages/index.php and the URL would be /test/index or /test for short. I think most people would agree that the short form is much easier to type.

## Editing pages

### Hello, world!

The default application for all root-level URLs is called h, which is short for home. To make our traditional "Hello, world!" page, simply edit apps/h/pages/index.php to read

	<p>Hello, world!</p>

and your website's main page will read "Hello, world!"

### Linking pages

All pages in Paferalib are PHP scripts. Let's say that you wanted an about page, you would create apps/h/pages/about.php. You could then update index.php to link to this page by using

	<p>Hello, world!</p>
	
	<p><a href="/about">About me</a></p>
	
### Using the resolver

Pages in Pafera are not in the global scope, but are actually inserted inside a function in resolver.php. This means that if you want to use any global variables, you need to write at the top of your page as in a function.

	<?php
	
	global $globalvar;

