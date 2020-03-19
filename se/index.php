<?php

// Start URL to begin crawling.
$start = "http://localhost:3000/se/test.html";

// Used to keep track of crawled (processed) sites and the sites to be crawled.
$crawled = array();
$crawling = array();

// Gets title, description, and keywords from a website and parses them into 
// JSON format.
function get_details($url)
{
  // Sets my user agent --> "web-crawler/1.0" 
  $options = array('http' => array('method' => "GET", 'headers' => "User-Agent: web-crawler/1.0"));
  $context = stream_context_create($options);
  $doc = new DOMDocument();
  @$doc->loadHTML(@file_get_contents($url, false, $context));

  $title = $doc->getElementsByTagName("title");
  $title = $title->item(0)->nodeValue;

  $desc = "";
  $keywords = "";
  $metaList = $doc->getElementsByTagName("meta");

  foreach ($metaList as $meta) {
    $name = $meta->getAttribute("name");
    if (strtolower($name) == "description") {
      $desc = $meta->getAttribute("content");
    } else if (strtolower($name) == "keywords") {
      $keywords = $meta->getAttribute("content");
    }
  }

  return '{ "Title": "' . str_replace("\n", "", $title) . '", "Description": "'
    . str_replace("\n", "", $desc) . '", "Keywords": "'
    . str_replace("\n", "", $keywords) . '", "URL": "' . $url . '"}';
}

// Crawls each site, parsing each link into an absolute link, logging and 
// printing each site's metadata into a JSON-friendly format.
function follow_links($url)
{
  global $crawled;
  global $crawling;

  // Sets my user agent
  $options = array('http' => array('method' => "GET", 'headers' => "User-Agent: web-crawler/1.0"));
  $context = stream_context_create($options);
  $doc = new DOMDocument();
  @$doc->loadHTML(@file_get_contents($url, false, $context));

  // Randomizes the order to traverse the list of links.
  $linkList = $doc->getElementsByTagName("a");
  $linkArray = iterator_to_array($linkList);
  shuffle($linkArray);

  // Parses the URLs of the list of links, to a maximum (to encourage wider 
  // traversal). Cap can be adjusted to preference.
  $cap = 5;
  for ($i = 0; $i < min(count($linkArray), $cap); $i++) {
    $link = $linkArray[$i];

    $l = $link->getAttribute("href");
    $parsed = parse_url($url);

    if (substr($l, 0, 1) == "/") {
      if (substr($l, 0, 2) != "//") { // Relative links to current dir
        if ($parsed["port"] != "") {
          $l = $parsed["scheme"] . "://" . $parsed["host"] . ":" . $parsed["port"] . $l;
        } else {
          $l = $parsed["scheme"] . "://" . $parsed["host"] . $l;
        }
      } else { // Links without scheme
        $l = $parsed["scheme"] . ":" . $l;
      }
    } else if (substr($l, 0, 2) == "./") { // Relative links but with "."
      if ($parsed["port"] != "") {
        $l = $parsed["scheme"] . "://" . $parsed["host"] . ":" . $parsed["port"]
          . dirname($parsed["path"]) . substr($l, 1);
      } else {
        $l = $parsed["scheme"] . "://" . $parsed["host"]
          . dirname($parsed["path"]) . substr($l, 1);
      }
    } else if (substr($l, 0, 1) == "#") { // Anchor links
      if ($parsed["port"] != "") {
        $l = $parsed["scheme"] . "://" . $parsed["host"] . ":" . $parsed["port"]
          . $parsed["path"] . $l;
      } else {
        $l = $parsed["scheme"] . "://" . $parsed["host"]
          . $parsed["path"] . $l;
      }
    } else if (substr($l, 0, 3) == "../") { // Relative & nested links
      if ($parsed["port"] != "") {
        $l = $parsed["scheme"] . "://" . $parsed["host"] . ":" . $parsed["port"]
          . "/" . $l;
      } else {
        $l = $parsed["scheme"] . "://" . $parsed["host"] . "/" . $l;
      }
    } else if ( // JavaScript and mailto links
      substr($l, 0, 11) == "javascript:" || substr($l, 0, 7) == "mailto:"
    ) { // important to skip loop entirely, else "good" links skipped
      continue;
    } else if (substr($l, 0, 4) != "http" && substr($l, 0, 5) != "https") { // The rest
      if ($parsed["port"] != "") {
        $l = $parsed["scheme"] . "://" . $parsed["host"] . ":" . $parsed["port"]
          . "/" . $l;
      } else {
        $l = $parsed["scheme"] . "://" . $parsed["host"] . "/" . $l;
      }
    }

    // Logs the list of processed URLs--"crawled" is more aptly "processed".
    if (!in_array($l, $crawled)) {
      $crawled[] = $l;
      $crawling[] = $l;
      echo get_details($l) . ",\n"; // "," needed to separate each JSON entry
    }
  }

  // Randomly goes through each site using DFS to crawl every site from 
  // the given url. Should not stop until interrupted, or reaches dead end.
  $site = end($crawling);
  array_splice($crawling, count($crawling) - 1);
  // echo "Following: " . $site . "\n";
  follow_links($site);
}

// Used for JSON-formatted array -> MUST append "]" at the end manually, 
// since KeyboardInterrupt (^C) used to stop writing to the file.
echo "[\n";
follow_links($start);
