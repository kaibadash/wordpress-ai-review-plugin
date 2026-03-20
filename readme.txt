=== AI Review ===
Contributors: kaibadash
Tags: ai, review, editor, gutenberg, llm
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A plugin that uses AI to revise your posts.

== Description ==

AI Review adds a sidebar panel to the WordPress post editor that lets you refine your posts with AI.

* Adds an AI Review panel to the post editor sidebar
* Enter optional instructions and click Execute to let AI revise your title and content
* Supports any OpenAI-compatible Chat Completions API
* Configure LLM provider, model, API key, and system prompt in the settings page
* Uses structured output to return the revised title, body, and a summary of changes

== Installation ==

1. Upload the plugin to the `wp-content/plugins/` directory
2. Activate the plugin on the Plugins page
3. Go to Settings > AI Review and configure your LLM settings
4. Open the AI Review panel in the post editor sidebar

== Changelog ==

= 1.0.1 =
* Support Classic Editor
* Support async request to avoid timeout

= 1.0.0 =
* Initial release
* AI-powered post revision
* LLM settings management
* Structured output with title, body, and changes

