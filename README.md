# Facebook Page Analyzer

[![License](https://poser.pugx.org/falsch/facebook_page_analyzer/license)](https://packagist.org/packages/falsch/facebook_page_analyzer)
[![Latest Stable Version](https://poser.pugx.org/falsch/facebook_page_analyzer/v/stable)](https://packagist.org/packages/falsch/facebook_page_analyzer)
[![Latest Unstable Version](https://poser.pugx.org/falsch/facebook_page_analyzer/v/unstable)](https://packagist.org/packages/falsch/facebook_page_analyzer)
[![Gitter chat](https://badges.gitter.im/0x46616c6b/facebook_page_analyzer.png)](https://gitter.im/0x46616c6b/facebook_page_analyzer)

![Logo](http://i.imgur.com/I5mjWip.png =250px)

The Facebook Page Analyzer edits the public data of any facebook page, making it possible to browse and analyze it.

**Motivation**

For months the PEGIDA-movement (Patriotic Europeans Against the Islamization of the West) has been walking through Dresden. While taciturn in public, PEGIDA's fans do comment very openly on the facebook page. Thus, the idea came up to analyze the contents of the movement and of those persons commenting and liking it.

**Requirements**

- PHP
- Elasticsearch
- Facebook Application (needed for appId, appSecret and accessToken)

**Installation**

	composer create-project falsch/facebook_page_analyzer analyzer dev-master

	cd analyzer

	php app/console fetch --fetch-likes --fetch-comments


**Usage**

	Usage:
	 fetch [--only-posts] [--fetch-likes] [--fetch-comments] [--limit[="..."]] [--since[="..."]]
	
	Options:
	 --only-posts          If set, the task fetch only posts
	 --fetch-likes         If set, the task fetch the likes
	 --fetch-comments      If set, the task fetch the comments
	 --limit               Paging for Facebook Request (default: 250)
	 --since               Fetch only data since a special "strtotime" term, like "yesterday"