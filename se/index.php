<?php

$start = "http://localhost:3000/se/test.html";

$crawled = array();
$crawling = array();

// Gets title, description, and keywords from a website and parses them into 
// JSON format.
function get_details($url)
{
  // Sets my user agent
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
    . str_replace("\n", "", $keywords) . '"}';
  // return $title;
}

function follow_links($url)
{
  global $crawled;
  global $crawling;

  // Sets my user agent
  $options = array('http' => array('method' => "GET", 'headers' => "User-Agent: web-crawler/1.0"));
  $context = stream_context_create($options);
  $doc = new DOMDocument();
  @$doc->loadHTML(@file_get_contents($url, false, $context));

  $linkList = $doc->getElementsByTagName("a");
  $linkArray = iterator_to_array($linkList);
  shuffle($linkArray);
  // print_r($linkArray);

  // foreach ($linkArray as $link) {
  for ($i = 0; $i < min(count($linkArray), 5); $i++) {
    $link = $linkArray[$i];

    $l = $link->getAttribute("href");
    // echo $l . "\n";
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

    if (!in_array($l, $crawled)) {
      $crawled[] = $l;
      $crawling[] = $l;
      echo get_details($l) . "\n";
      // echo $l . "\n";
    }
  }

  // Pops current site from the queue (stack?) of websites.

  // Randomly goes through each site using BFS-DFS to crawl every site from 
  // the given url.
  // foreach ($crawling as $site) {
  $site = end($crawling);
  // print_r($crawling);
  array_splice($crawling, count($crawling) - 1);
  if ($site != NULL) {
    echo "Following: " . $site . "\n";
    follow_links($site);
  }
  // }
}

follow_links($start);

print_r($crawled);
