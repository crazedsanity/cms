# Crazed(Sanity) CMS

This is a PHP-based web CMS, similar (at least in concept) to Wordpress. 

## Important Stuff to Know...

### There's a place for (mis)guidance!

There's a Trello board or an issues listing that shows bugs, plans for things, 
etc.  It should be more up-to-date than this README... Try 
[this link to Trello](https://trello.com/b/8fqSradP).  

### Permissions !!!

Permissions are linked "casually" in a lot of places.  Older versions of the CMS 
had each page checking permissions (for add, edit, and delete). This affected 
what was displayed on the page (e.g. "add" buttons disappeared if the user lacked
the "add" privilege, etc)... now, there's a couple of levels.

#### ADMIN ACCESS

Access to the admin section is purely based on whether or not the current user is 
in the 'admin' group.  This used to be based on a particular permission, `site`, 
which was the same permission necessary to access the settings page... this meant 
that anyone accessing the CMS admin could implicitly change things.

Now you can remove all permissions from the `admin` group, avoiding any having 
unnecessary access to settings.  Create a secondary group, e.g. `Full Admin Access`,
and give that group access to everything.

#### Menu Access

Menu items (in the `admin_menus` table) are linked to assets.  If a user doesn't 
have access to the linked asset, they won't see the menu item.  "Access" means 
they must have add or edit privileges (having only delete access doesn't make 
any sense).

#### SECTION ACCESS

A user is allowed (or denied) access prior to viewing a sections' index page. To 
be initially allowed, the user must have add/edit privileges (having only delete 
privileges doesn't make sense).  The index page may have different (or even 
conflicting) permission requirements.

#### DELETE/ADD ACCESS

For now, add/delete access must be checked explicitely.  Eventually the CMS will 
be able to enforce this, but there's still some hurdles to overcome.

### Home Page Asset

The page used as the homepage (the default page, on the URL `/`) is determined by 
finding a page that has the "Homepage" (`home`) asset.  Why?  Well, we can't do 
a lookup by URL, because the CMS doesn't allow a blank URL; after stripping extra 
slashes, `/` turns into nothing.

Note that the URL for the page with the "Homepage" asset will not work.  It is 
specifically ignored when searching for pages based on URL.

### Page Template

There's now a "template" column for pages.  There's no database table to list 
the available templates, as this would just cause unnecessary administrative 
overhead. The specified "main" template will only go into effect if it exists.

Assets still can still use their own sub-templates.  They can even reset the 
specified template, though they shouldn't be doing such a thing (anymore).

### POST-Based Deletes

*(this is an old note, intended for developer reference)*

In the CMS admin, deleting a record previously involved just creating a link that 
was something like `/update/section/delete.php?section_id=123`, which are GET-
based deletes.  Deleting based on GET (or URL) parameters are insecure, and 
generally operations using GET should perform read-only operations.

The CMS has a hidden "delete" form in the base template.  To use this, change 
the link as in this example:

*BEFORE:* `<a href="/update/section/delete.php?section_id=123">delete`

*AFTER::* `<a href="javascript:;" data-id="123" class="formdelete">delete`

The `formdelete` class, in combination with the `data-id` element, will trigger 
a JavaScript action that will submit the hidden form to the `delete.php` script.
For pages where multiple types of records can be deleted, just add another 
attribute to the link, called `data-keyname`.  The media library uses this: the 
folder delete buttons have `data-keyname="media_folder_id"` whereas the links 
for individual media records have `data-keyname="media_id"`.

### Session Alerts (instead of URL-based alerts)

Previously, information about an operation (like a delete) was passed in the URL 
by redirecting with a URL like `/update/section/?alert=Record deleted`.  This 
kind of alert is limited and has security implications.

Instead, there's an alternate way to create alerts that's more robust: they are 
session-based alerts using a function called `addAlert()`.  Multiple alerts can 
be set with this. A simple example:

`addAlert("Title", "The alert contents go here", "notice")`

The types of alerts (the third argument) are:

1. notice
1. status
1. error
1. fatal

Generally "fatal" alerts should only be used when something very seriously wrong 
happened. A "notice" should be informational, while a "status" should indicate 
the result of an operation (they can be used interchangeably most of the time).
Errors are to highlight problems, such as when a delete operation unexpectedly 
failed.


### Self-Update Mechanism (web hooks + git)

There's a self-update mechanism for this site, using a simple script in 
`public_html/self-update.php`.  Using this URL, a webhook can be setup that will 
automatically pull data from the git repository.  The URL must have a "key" 
value that matches what's in the `config/site-config.ini` file, e.g. 
`http://site.com/self-update.php?key=xyz123`

### Using the Constraint Class

*(NOTE: this is probably really only useful within PHP classes, probably not so 
much for front-end stuff or directly within assets.  Use at your own risk.)*

There's a constraint class (`cms\database\constraint`) that's used when passing 
an array of constraints through to `core` methods (e.g. `core::getAll()`). This 
basically makes it easier to handle dates.  The syntax is fairly straightforward:

```
$newsObj = new \cms\cms\core\news($db);

// only records that have started but not ended and are approved.
$orderBy = null;
$constraints = array(
	'start_date' => new constraint('date', '<=', 'current_date()'),
	'end_date'   => new constraint('date', '>=', 'current_date()'),
	'approved'   => 1,
);
$records = $newsObj->getAll($orderBy, $constraints);

```

## Where Stuff is Located

### Database Configuration.

The database connection information is stored in `/config/siteconfig.ini`.  

1. By default, it uses `/config/siteconfig.ini`
1. Local instances can replace using `/config/siteconfig-dev.ini` (overrides the 
default; for local development)
1. If no INI is set, it falls back on server configuration (directives set in the 
Apache config via `SetEnv` directives; using environment variables is not a 
recommended/secure practice and should be avoided).

### Admin (/update) Files

"I can't find any files!"

It's okay, don't panic.  The files you're looking for are in `/_app/admin/`, with 
straight-forward names: the url `/update/pages/item.php` would be in `/_app/admin/pages/item.php`.


### Template Locations

"Templates?  What are templates?"

First off, templates are... well, they're templates for the HTML.  The code reads 
these files (ala `getTemplate()`) and adds data into `{template_vars}`: a template 
var is any string enclosed in curly braces. The string should be alpha-numeric, 
with the same constraints that a PHP variable would take.

One template might get another template shoved into it.  For instance, in `/templates/base.html`, 
there's a template var called `BASE_CONTENT` (literally `{BASE_CONTENT}`): this 
will be filled with another template based on the URL.  So the `/` URL for this 
website will use `/templates/home.html` to fill that content.

## Things to consider:

### Database Settings

The database settings are no longer stored in the Apache configuration (previously, 
this was accomplished using `SetEnv` directives). Instead this is stored in the 
`/config/siteconfig.ini` file, which should always match production values; for 
local development, create a `/config/siteconfig-dev.ini` file, which *should* be 
ignored by git already (if not, add it to the `/.gitignore` file).


### PHP

The following configuration options should be set:

 * the directive "short_open_tag" should be set to "On"

