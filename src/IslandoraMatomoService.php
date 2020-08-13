<?php

namespace Drupal\islandora_matomo_services;

use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;

/**
 * Class IslandoraMatomoService.
 */
class IslandoraMatomoService implements IslandoraMatomoServiceInterface {

  /**
   * Constructs a new IslandoraMatomoService object.
   */
  public function __construct() {
  }

  public function queryMatomoApi($url, $mode) {
    $url = rtrim($url, '/');
    $matomo_config = \Drupal::config('matomo.settings');
    $matomo_url = $matomo_config->get('url_http');
    $matomo_id = $matomo_config->get('site_id');
    if ($matomo_url == '' || $matomo_id == '') {
      drupal_set_message(t('Error: Matomo not configured. Please make sure Matomo URL and site ID are set.'), 'error');
      return NULL;
    }
    else {
      $current_date = date('Y-m-d', time());
      $date_range = "2000-01-01,{$current_date}";
      switch ($mode):
        case 'views':
          $query = "index.php?module=API&method=Actions.getPageUrl&pageUrl={$url}&idSite={$matomo_id}&period=range&date={$date_range}&format=json";
          break;
        case 'downloads':
          $query = "index.php?module=API&method=Actions.getDownload&downloadUrl={$url}&idSite={$matomo_id}&period=range&date={$date_range}&format=json";
          break;
        default:
          drupal_set_message(t('Error: Invalid mode "{$mode}" provided to islandora_matomo_service.'), 'error');
          return NULL;         
      endswitch;
      $request_url = $matomo_url . $query;
      $response = json_decode(file_get_contents($request_url), TRUE); 
      $result = (int) $response[0]['nb_hits'];    
      return $result;
    }
  }

  public function getViewsForNode($nid) {
    $node = Node::load($nid);
    $path = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $node->id()])->toString();
    global $base_url;
    $node_url = $base_url . $path;
    $views = \Drupal::service('islandora_matomo_services.default')->queryMatomoApi($node_url, 'views');
    return $views;
  }

  public function getDownloadsForFile($fid) {
    $file = file_load($fid);
    $file_uri = $file->getFileUri();
    $file_url = file_create_url($file_uri);
    $downloads = \Drupal::service('islandora_matomo_services.default')->queryMatomoApi($file_url, 'downloads');
    return $downloads;
  }

  public function getSummedDownloadsForFiles($fids) {
    $sum = 0;
    foreach ($fids as $fid) {
      $file_downloads = \Drupal::service('islandora_matomo_services.default')->getDownloadsForFile($fid);
      global $sum;
      $sum = $sum + $file_downloads;
    }
    return $sum;
  }

  public function getFileFromMedia($mid) {
    $media_file_fields = [
      'audio'                   => 'field_media_audio_file',
      'document'                => 'field_media_document',
      'extracted_text'          => 'field_media_file',
      'file'                    => 'field_media_file',
      'fits_technical_metadata' => 'field_media_file',
      'image'                   => 'field_media_image', 
      'video'                   => 'field_media_video_file',
    ];
    $media = Media::load($mid);
    $media_bundle = $media->bundle();
    $media_file_field = $media_file_fields["{$media_bundle}"];
    $media_file_id = $media->{$media_file_field}->target_id;
    return $media_file_id;
  }

}
