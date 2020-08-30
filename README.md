# Islandora Matomo

## Introduction

A Drupal 8 module that provides an API for querying a Matomo instance for usage data on individual nodes and file downloads.

## Overview

In addition to the API, which other modules can use, this module includes a submodule, [Islandora Matomo Blocks](modules/islandora_matomo_blocks), which provides blocks for displaying the number of hits or visits on a node, the number of media downloads for a node, or a summed report of node hits/visits and media downloads. You enable these blocks like you would any other.

Hits are the total number of times a page was loaded or a file downloaded, while visits counts all page loads and file downloads from the same visitor within 30 minutes as a single visit.

## Requirements

* [Islandora 8](https://github.com/Islandora/islandora)

## Installation

1. Clone this repo into your Islandora's `drupal/web/modules/contrib` directory.
1. Enable the module either under the "Admin > Extend" menu or by running `drush en -y islandora_matomo`.
1. The Islandora Matomo Blocks module must be enabled separately: `drush en -y islandora_matomo_blocks` .

## Configuration

Configuration options are available at Admin > Islandora > Islandora Matomo, or at `admin/config/islandora/matomo`. 

## Current maintainer

* [Arizona State University Libraries ](https://github.com/asulibraries)

## License

[GPLv2](http://www.gnu.org/licenses/gpl-2.0.txt)
