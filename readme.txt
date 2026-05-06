=== Hide Page & Post Title ===
Contributors: jcjason12108-alt
Tags: title, page title, post title, block themes, classic themes
Requires at least: 5.8
Tested up to: 6.9.4
Requires PHP: 7.0
Stable tag: 1.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a per-post checkbox to hide the theme-rendered title without touching headings typed in the editor.

Requires WordPress Version: 5.8 or higher
Compatible up to: 6.9.4
Requires PHP Version: 7.0 or higher

== Description ==

Hide Page & Post Title adds a sidebar checkbox to posts, pages, and public custom post types. When enabled, it hides only the title rendered by the active theme.

For block themes, the plugin removes the core/post-title block output. For classic themes, it injects scoped CSS for common title containers on the current singular post.

== Installation ==

1. Download the release ZIP from GitHub Releases.
2. In WordPress, go to Plugins > Add New > Upload Plugin.
3. Upload the ZIP and activate the plugin.

== GitHub Updates ==

Automatic updates are powered by Plugin Update Checker and use the GitHub repository at https://github.com/jcjason12108-alt/hide-page-post-title.

If the repository is private, add a GitHub token with read access to the repository in wp-config.php:

`define( 'HPT_UPDATE_GITHUB_TOKEN', 'your-github-token' );`

== Frequently Asked Questions ==

= Does this hide headings in my post content? =

No. It avoids filtering the_title and targets only theme-rendered title output.

= Does it work with custom post types? =

Yes. The checkbox is added to public custom post types.

== Changelog ==

= 1.3.1 =
* Fixed title hiding on pages and themes that do not use the postid body class.
* Added a plugin-specific front-end body class for safer title scoping.

= 1.3.0 =
* Added GitHub update support with Plugin Update Checker.
* Added optional private repository token support.
* Added current WordPress and PHP compatibility metadata.

= 1.2.0 =
* Added safe CSS scoping for classic themes.
* Ensures headings inside content are not removed.
* Block themes: removes core/post-title output directly.

= 1.0.0 =
* Initial release with per-post title hiding.
