=== Codex Generator ===
Contributors: Rarst
Tags: documentation
Requires at least: 3.2.1
Tested up to: 3.2.1
Stable tag: 1.2

Codex Generator is search, research and documentation generation tool for WordPress functions.

== Description ==

Creating page of function reference in Codex involves looking up information about function and dealing with complicated wiki markup.

This plugin automates much of the process by reading function's inline documentation and converting to wiki markup:

* short and long function descriptions
* parameters and their default values
* version of WordPress function was added in
* source file that contains a function

You can also navigate, search, sort and filter functions by much of that information in plugin's interface.

== Installation ==

1. Upload `codex-generator` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the `Plugins` menu in WordPress
3. Use plugin's page in WordPress admin area under `Tools` > `Codex Generator`

Note that this plugin **won't work properly with opcode caching enabled**. Opcode caching mechanisms (such as APC or eAccelerator) discard PHPDoc data and make it unavailable to plugin.

== Frequently Asked Questions ==

= Does it list every single WordPress function? =

Plugin draws data from currently defined functions. It cannot see functions from files that are only loaded in specific cases and inactive plugins.

= Why does it complain about opcode caching? =

Opcode caching makes things run snappier and discards non-essential information while at it. So PHPDoc (documentation embedded in source) is not available in that case. And this plugin needs it.

= Should I ask my hosting to disable opcode caching? =

Absolutely not! This plugin is mostly meant to be used in development environment. You can run it in production, but you should not compromise performance because of it.

== Screenshots ==

1. Using plugin's page in administration area.
2. Generating markup for wiki page.
3. Resulting page in Codex.

== Changelog ==

= 1.3 =
* _(bug fix)_ fixed parsing @param with excessive whitespace
* _(bug fix)_ fixed "booleanlean" in type sanitization
* _(bug fix)_ fixed vertical bar character encoding for wiki
* _(bug fix)_ fixed file paths, broken by moving main class to sub-directory

= 1.2 =
* _(enhancement)_ added searchable and sortable table of functions to interface
* _(bug fix)_ added spaces when merging multiline short description
* _(internal)_ moved parser to separate class
* _(internal)_ added utility class
* _(internal)_ added function query class

= 1.1 =
* _(enhancement)_ implemented suggest for function names
* _(enhancement)_ added notice when PHPDoc is not available for a function
* _(enhancement)_ added link to plugin's page in plugin list
* _(bug fix)_ fixed and improved type and value conversion into strings
* _(bug fix)_ changed piped parameter types from "mixed" to joined with pipe HTML entity
* _(internal)_ refactored parser

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.2 =
Considerable interface update. Internal refactoring. Magic.

= 1.1 =
Bugs fixed. More magic. Okay - dynamic suggest for function names.