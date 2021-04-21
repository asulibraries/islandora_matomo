<?php

namespace Drupal\islandora_matomo;

/**
 * Interface IslandoraMatomoServiceInterface.
 */
interface IslandoraMatomoServiceInterface {

  public function queryMatomoApi(string $value, string $mode);

  public function getViewsForNode(int $nid);

  public function getDownloadsForFile(int $fid);

  public function getSummedDownloadsForFiles(array $fids);

  public function getFileFromMedia(int $mid);

  public function getAllPages(string $segment = '');

}
