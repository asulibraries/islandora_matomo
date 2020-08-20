<?php

namespace Drupal\islandora_matomo_services;

use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class IslandoraMatomoService.
 */
class IslandoraMatomoService implements IslandoraMatomoServiceInterface {
  /**
   * An http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  private $messenger;

  /**
   * Constructs a new IslandoraMatomoService object.
   *
   * @param \GuzzleHttp\Client $httpClient
   *   Guzzle client.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(Client $httpClient, MessengerInterface $messenger) {
    $this->httpClient = $httpClient;
    $this->messenger = $messenger;
  }

  /**
   * Query the Matomo API.
   */
  public function queryMatomoApi($url, $mode) {
    $url = rtrim($url, '/');
    $matomo_config = \Drupal::config('matomo.settings');
    $matomo_url = $matomo_config->get('url_http');
    $matomo_id = $matomo_config->get('site_id');
    if ($matomo_url == '' || $matomo_id == '') {
      $this->messenger->addMessage(t('Error: Matomo not configured. Please make sure Matomo URL and site ID are set.'), 'error');
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
          $this->messenger->addMessage(t('Error: Invalid mode "{$mode}" provided to islandora_matomo_service.'), 'error');
          $result = 0;

      endswitch;
      $request_url = $matomo_url . $query;
      $response = json_decode(file_get_contents($request_url), TRUE); 
      $result = (int) $response[0]['nb_hits'];    
      return $result;
    }
  }

  /**
   * Get views for node.
   */
  public function getViewsForNode($nid) {
    $node = Node::load($nid);
    $path = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $node->id()])->toString();
    global $base_url;
    $node_url = $base_url . $path;
    $views = \Drupal::service('islandora_matomo_services.default')->queryMatomoApi($node_url, 'views');
    return $views;
  }

  /**
   * Get download counts for single file.
   */
  public function getDownloadsForFile($fid) {
    $file = file_load($fid);
    $file_uri = $file->getFileUri();
    $file_url = file_create_url($file_uri);
    $downloads = \Drupal::service('islandora_matomo_services.default')->queryMatomoApi($file_url, 'downloads');
    return $downloads;
  }

  /**
   * Calculate sum of downloads.
   */
  public function getSummedDownloadsForFiles($fids) {
    $sum = 0;
    foreach ($fids as $fid) {
      $file_downloads = \Drupal::service('islandora_matomo_services.default')->getDownloadsForFile($fid);
      global $sum;
      $sum = $sum + $file_downloads;
    }
    return $sum;
  }

  /**
   * Get files from media.
   */
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
