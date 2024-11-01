=== WPSummarize ===
Contributors: julianyanover
Tags: AI summaries, summarizer, key takeaways
Requires at least: 6.5
Requires PHP: 7.0
Tested up to: 6.6
Stable tag: 1.0.16
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

AI Summaries for your WordPress

== Description ==

[WPSummarize](https://wpsummarize.com/) automatically creates concise summaries of your blog posts. It helps your readers quickly grasp the main points of your content, improving user experience and engagement.

https://www.youtube.com/watch?v=uehYM4UTlZA

## Key Features

* Automatic summarization of blog posts
* You decide where the summaries are displayed, with options such as above or below the content, before the first heading tag or even manually through shortcode.
* Choose whether you want a list of key takeaways or a narrative approach
* Support for custom post types
* Support for Gutenberg, Elementor, Divi, Oxygen, etc.
* 1 pre-built design
* Custom CSS
* UI available in Spanish, German, Italian, French and Portuguese.
* 50 credits per month. 1 credit = 1 summary.

## Do more with WPSummarize Pro

* 10,000 credits per month. 1 credit = 1 summary.
* Automatically add style tags like strong and em
* Bulk summarization for existing posts
* Edit settings per post
* Manually edit summaries
* 5 pre-built designs
* Custom CSS
* List type and narrative styles
* Custom post types supported
* Gutenberg, Elementor, Divi, all supported
* Unfold summary on click
* Priority support

[Get WPSummarize Pro](https://wpsummarize.com/)

## Why use WPSummarize?

* **Time-Saving**
Why perform a task that can be automated at the same highest standard you expect?
We use advance AI to automatically generate concise, accurate summaries of your blog posts. No manual work required - just publish your content and let our plugin do the rest.

* **No Coding, yet Fully Customizable**
You do not need to do any coding to make this plugin your own. We made sure you can tweak exactly how, when and where summaries are generated and displayed.
We have themes ready, a great batch process for your old posts and all the options you might need.

* **Improved Reader Experience**
Not to be mean, but not everybody wants to read your whole 2,000 word article. Enhance user engagement by providing quick, digestible takeaways on each post.
Help your audience decide if the content is relevant to them, improving overall satisfaction and time spent on your site.

* **SEO Friendly**
Search engines favor pages that fulfill the search intent right away, without making users go through entire articles.
You can also choose to hide the summary content from search engines by enabling the Unfold summary on click option. The content is then dynamically loaded on click.

* **Fast and Convenient**
All summaries created are cached in your own database as post meta fields, making sure your website keeps loading super fast.
The entire summary generation process happens in the background, not wasting you any time at all.

* **OpenAI API Key needed**
Once you install WPSummarize, you will need to add your OpenAI API key in order to process your posts and generate summaries. You can get your API key at https://platform.openai.com/api-keys

## Third-Party Services

1. WPSummarize uses an external API service to generate summaries of your content. Here's what you need to know:
Our plugin connects to https://wpsummarize.com to process and generate summaries.
When you use the summary feature, your post content is sent to our servers for processing.
We do not store your content on our servers beyond the time needed to generate the summary.
For more information about how we handle your data, please see our Privacy Policy at https://wpsummarize.com/privacy-policy/
By using this plugin, you agree to our Terms of Service, available at https://wpsummarize.com/terms-and-conditions/

2. OpenAI API
While the OpenAI post content processing happens mostly on our server, we have a brief connection inside the plugin to check if your API key is valid.
OpenAI API website: https://openai.com/api/
OpenAI Terms of Service: https://openai.com/policies/terms-of-use
OpenAI Privacy Policy: https://openai.com/policies/privacy-policy
We take your privacy seriously and are committed to protecting your data. If you have any questions or concerns, please contact us at support@wpsummarize.com.

## Third-Party Libraries

1. WPSummarize uses the Action Scheduler library (https://actionscheduler.org/) to manage background processing tasks.
Action Scheduler is a robust scheduling library for WordPress, originally developed by the WooCommerce team.
It runs within your WordPress site and does not send data to external services.
The library is included with our plugin and does not require separate installation.

2. WPSummarize also uses Freemius (https://freemius.com) to manage our premium plans, licensing, and plugin updates.
Freemius collects and processes certain data as described in their Privacy Policy.
This includes basic site data (WordPress version, PHP version, etc.) and user data (email, name) for customers who purchase premium plans.
Freemius is only active if you opt in to usage tracking or purchase a premium plan.
You can opt out of non-essential data collection at any time through the plugin settings.

== Frequently Asked Questions ==

= Do I need an OpenAI API key to use WPSummarize? =

Yes, you need an OpenAI API key to generate summaries. You can obtain one from [OpenAI's website](https://platform.openai.com/api-keys).

= Is my content sent to external servers? =

Yes, your post content is sent to our servers at [wpsummarize.com](https://wpsummarize.com) to generate summaries. We do not store your content beyond the time needed to process it.

= Can I customize where the summaries appear on my site? =

Yes, you can choose where the summaries are displayed, such as above or below the content, before the first heading tag, or even manually using a shortcode.

== Change Log ==

= 1.0.16 =

* Fixed storing correctly OpenAI API key for the first time

= 1.0.15 =

* Minor bug fixes with quotas

= 1.0.14 =

* Minor bug fixes

= 1.0.13 =

* Fixed escaping

= 1.0.12 =

* Adjusted settings

= 1.0.11 =

* Sanitization fixes

* readme.txt now has short description

= 1.0.10 =

* Sanitization fixes

* Better json handling with wp_json_encode

= 1.0.9 =

* Added check for API key in the meta boxes

* Better input sanitization

* OpenAI added as third party service

= 1.0.8 =

* Fixed security issues

= 1.0.7 =

* Improved speed for generating the summary

= 1.0.6 =

* Fixed bugs when sending data for a long period of time

= 1.0.5 =

* Added missing information in readme file

= 1.0.4 =

* Added a maximum attempts control to the summarize in real time checker

= 1.0.3 =

* Fixed a javascript error related to the shortcode button and QTags not being available

= 1.0.2 =

* Fixed the displayed number of batch processes done

= 1.0.1 =

* Fixed a bug when generating the summary under specific conditions

= 1.0.0 =

* Initial release