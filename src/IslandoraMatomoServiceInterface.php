<?php

namespace Drupal\islandora_matomo;

/**
 * Interface IslandoraMatomoServiceInterface.
 */
interface IslandoraMatomoServiceInterface {

  public function queryMatomoApi(string $value, string $mode);

  public function getViewsForNode($nid);

  public function getDownloadsForFile($fid);

  public function getSummedDownloadsForFiles($fids);

  public function getFileFromMedia($mid);

  public function getAllPages(string $segment = '');

}
