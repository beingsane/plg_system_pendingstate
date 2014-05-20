plg_system_pendingstate
=======================
This Joomla! plugin allows for published articles with the field "publish_up" set in the future to have a pending state.
The plugin is triggered when an article is saved. When the published up date is in the future, the state is changed into 3.
Additionally, the plugin is run every request to make sure pending articles are published again once the right date is reached.

Backgrounds
===========
Joomla! normally uses one of 2 states to determine whether an article should show or not: 0 (unpublished) or 1 (published).
Additionally there is a state archived (2).
While this works in most cases, there are a couple of issues here:

1) Once the article is published while still having a publish_up in the future, the article is hidden for normal users. It is displayed
to admins though. Once an admin visits a frontend page and the contents of that frontend are cached (full page cache, Varnish, etc) the
pending article will be cached and will be presented to all users even though they don't have access to it.

2) Once the article is published because the publish_up date now lies in the past, the article is shown. But if the frontend is cached
(full page cache, Varnish) there is no event onContentChangeState event triggered, so other plugins can hook into this.

This plugin fixes these issues.

Current issues with this plugin
===============================
1) To autopublish articles again, queries are run on the fly (onAfterRender event). There is a plugin parameter to allow for disabling
for this, but this would mean a cronjob takes over here. This cronjob is still missing.

2) The $context argument of onContentBeforeSave() is currently not checked. This should be in sync with the tables that can be auto
cleaned up.

3) Joomla! code compliance is not 100%. Blame my editor.

4) Articles with state 3 (pending) do not show up in default backend overview, unless State = All is selected.
