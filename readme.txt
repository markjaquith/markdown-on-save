=== Markdown on Save ===
Contributors: markjaquith
Donate link: http://txfx.net/wordpress-plugins/donate
Tags: markdown, formatting
Requires at least: 3.1
Tested up to: 3.3
Stable tag: 1.1.5

Allows you to compose content in Markdown on a per-item basis. The markdown version is stored separately, so you can deactivate this plugin any time.

== Description ==

This plugin allows you to compose content in Markdown on a per-item basis. The markdown version is stored separately (in the `post_content_formatted` column), so you can deactivate this plugin and your posts won't spew out Markdown, because HTML is stored in the `post_content`, just like normal. This is also much faster than doing on-the-fly Markdown conversion on every page load. It's only done once! When you re-edit the post, the markdown version is swapped into the editor for you to edit. If something external updates the post content, you'll lose the Markdown version.

== Installation ==

1. Upload the `markdown-on-save` folder to your `/wp-content/plugins/` directory

2. Activate the "Markdown On Save" plugin in your WordPress administration interface

3. Create a new post with Markdown, and check the "This post is formatted with Markdown" box.

4. Done! Now that post can be edited using Markdown, but will save as processed HTML on the backend.

== Screenshots ==

1. The meta box where you designate a post as containing Markdown. This is the only UI for the plugin!

== Frequently Asked Questions ==

= How do I use Markdown syntax? =

Please refer to this resource: [http://michelf.com/projects/php-markdown/extra/](PHP Markdown Extra).

= What happens if I uncheck the Markdown box? =

Your post will no longer be interpreted as Markdown, and you may have to alter the post to remove Markdown formatting.

= What happens if I decide I don't want this plugin anymore? =

Just deactivate it. The Markdown version is stored separately, so without the plugin, you'll just revert to editing the HTML version.

== Changelog ==
= 1.1.5 =
* Fix a `stripslashes()` error

= 1.1.4 =
* XML-RPC support (use <!--markdown--> to enable Markdown mode)

= 1.1.3 =
* Disables the Visual Editor if the post being edited is in Markdown mode

= 1.1.2 =
* Fix a slashes bug which would cause link titles to fail
* Enable Markdown when posting remotely by using <!--markdown--> anywhere in post content

= 1.1.1 =
* Fix bug which made the metabox show up on the Dashboard

= 1.1 =
* Some extra nonce protection
* Enable the plugin for all content types, not just posts

= 1.0 =
* First public version
* Fixed a regex bug that could break current menu highlighting. props skarab

== Upgrade Notice ==
= 1.1.5 =
Update to fix issues with slashes disappearing.

= 1.1.4 =
Upgrade to use Markdown over XML-RPC (use <!--markdown--> to enable it)

= 1.1.3 =
Upgrade to fix the bug that caused "titles" in links to fail parsing and to disable the visual editor in Markdown mode

= 1.1.2 =
Upgrade to fix the bug that caused "titles" in links to fail parsing

= 1.1.1 =
Prevents the meta box from mistakenly appearing on the Dashboard

= 1.1 =
Enables the Markdown option for all content types, instead of limiting it to posts
