# Web Crawler v1.0.0
Figured I'd look into making one.

## Functionality
The web crawler... erm, well... crawls the web.

This is my first go at making one. It uses a randomized DFS (depth-first search) algorithm to ensure a 
diversified crawling pattern, with an adjustable cap to how many sites it can crawl on any given iteration of the DFS. 
It works (mostly) as intended, except with a minor "bug" (though, not really) as detailed below.

## Changelog
* March 18th, 2020
  * Initial version - v1.0
  * Fully functional with minor bug
  * Planned development of scraping functionality

## Known bugs
* Crawler can get stuck in an infinite loop for pages that redirect to each other, given that the URL is unique 
every time. For example, this can occur when the URL encodes some specific user-related session ID.

## Images

### Crawling in action -- in Terminal
![Terminal Crawler](images/example.png)

