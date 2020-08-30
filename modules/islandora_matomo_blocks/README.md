# Islandora Matomo Blocks

## Introduction

A Drupal 8 module that provides blocks displaying the number of hits or visits on a node, the number of media downloads for a node, or a summed report of node hits/visits and media downloads.

Hits are the total number of times a page was loaded or a file downloaded, while visits counts all page loads and file downloads from the same visitor within 30 minutes as a single visit.

## Requirements

* [Islandora 8](https://github.com/Islandora/islandora)
* [Islandora Matomo](https://github.com/asulibraries/islandora_matomo)

## Installation

1. Clone this repo into your Islandora's `drupal/web/modules/contrib` directory.
1. Enable the module either under the "Admin > Extend" menu or by running `drush en -y islandora_matomo_blocks`.
1. Configure the blocks as usual.

## Current maintainer

* [Arizona State University Libraries ](https://github.com/asulibraries)

## License

[GPLv2](http://www.gnu.org/licenses/gpl-2.0.txt)
