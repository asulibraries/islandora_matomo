<?php

namespace Drupal\islandora_matomo;

/**
 * Interface IslandoraMatomoServiceInterface.
 */
interface IslandoraMatomoServiceInterface {

  public function queryMatomoApi(array $params);
  public function getViewsForNode(array $params);
  public function getDownloadsForFile(array $params);
  public function getSummedDownloadsForFiles(array $params);
  public function getFileFromMedia($mid);

}
