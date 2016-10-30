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
	
### Using the provided objects

Pages in Pafera are not in the global scope, but are actually inserted inside a function in resolver.php. This means that if you want to use any global variables, you need to write at the top of your page as in a function.

	<?php
	
	global $globalvar;

Another consequence of this scope is that the Resolver will automatically include several useful variables for you as well.

+ $pathargs    # Any arguments passed via the URL but not by query string
+ $D           # Database
+ $R           # Resolver
+ $S           # Session
+ $T           # Translator
+ $T_SYSTEM    # System translations
+ $T_MAIN      # Main translations

#### $pathargs

The $pathargs variable contains any extra parameters to the script passed via the URL.

For example, if you have a script at the URL /view and the URL is /view/0/100/icon, $pathargs would become

	['0', '100', 'icon']
	
This is the equivalent positional parameters of a function

	function View($start, $limit, $style)
	
just using the URL rather than calling a function directly.

#### $D

This is the default database for the site, which is initialized before it comes to your page. Paferalib supports using multiple databases simultaneously, which you may want to do if you're supporting multiple simultaneous users using SQLite to avoid locking down the whole database file down whenever a script wants to insert something. If you're using MySQL or anything else that locks only individual tables, it's unlikely that you'll use anything beyond this variable.

#### $R

The Resolver is the object that you will use to identify which app you're in. It also has convenience functions for images, scripts, and other resources that your app might use.

For example, if your app is called test and you have an image at apps/test/images/logo.png, you can either type

	<img src="/i/test/logo.png" />
	
or you can use

	<?=$R->IMG('logo.png')?>
	
to do the same thing without hardcoding the name of your app into your code?

Why would you not want to hardcode the name of your app into your code? 

Paferalib supports app instances, which are copies of your app using different names implemented by Unix or NTFS soft links. Each app has the same code, but different data. If you want your billing department and your sales department to both have forums, you can use the same code under apps/billingforums and apps/salesforums to achieve this result.

It's also possible to have different code paths depending on the name of your app. For example, our file manager "share" doubles as a TV remote under the name "pitv." The additional features are activated by a code block that reads

	if ($R->currentapp == "pitv")
	{
		...
	}

The Resolver is also useful when including other scripts. Instead of writing

	include('apps/test/libs/lib.php');
	
You can write

	$R->IncludePHP('libs/lib.php');
	
to do the same thing or use

	$R->IncludeDir('libs/lib1');
	
to include an entire directory of PHP files at the same time. 

#### $S

The session object exists to save session data into a database rather than on the filesystem. This allows many web servers to share a database server and thus have user sessions available anywhere. You should probably not worry too much about this unless you're a big company with multiple A records for your domain name.

#### $T

Translations are natively built into Paferalib. On the application side, they're stored in JSON files in the translations directory and loadable by this object. On the database side, they're stored into a JSON field.

Let's say that you have an app called test with a translation file called main. You could then load these translations by calling

	$T_TEST_MAIN	= $T->Load('test/main');
	
	print_r($T_TEST_MAIN);
	
$T_TEST_MAIN would then be an array of strings.

#### $T_SYSTEM

System-wide collection of strings useful on every page. Things like "Go," "Cancel," and "Back" to make your life easier.

#### $T_MAIN

Site-specific collection of strings. This should contain your site name and anything else that's useful on multiple pages.

