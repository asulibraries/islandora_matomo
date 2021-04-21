<?php

namespace Drupal\islandora_matomo;

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
   *
   * @param array $params
   *  Array that must include $params['url'], the URL to be queried, and $params['mode'] as 'views' or 'downloads'.
   *  May optionally include  $params['start_date'] and/or $params['end_date'] if a range is desired.
   */
  public function queryMatomoApi(string $value, string $mode) {
    $matomo_config = \Drupal::config('matomo.settings');
    $matomo_url = $matomo_config->get('url_http');
    $matomo_id = $matomo_config->get('site_id');
    $matomo_hits_or_visits = \Drupal::config('islandora_matomo.settings')->get('islandora_matomo_hits_or_visits');
    $matomo_metric = ($matomo_hits_or_visits == 0 ? 'nb_hits' : 'nb_visits');
    $matomo_token = \Drupal::config('islandora_matomo.settings')->get('islandora_matomo_user_token');
    $matomo_token_param = ($matomo_token != '' ? "&token_auth={$matomo_token}" : ''); // If no token is configured, assume anonymous viewing
    if ($matomo_url == '' || $matomo_id == '') {
      $this->messenger->addMessage(t('Error: Matomo not configured. Please make sure Matomo URL and site ID are set.'), 'error');
      return NULL;
    }
    else {
      $current_date = date('Y-m-d', time());
      $date_range = "2000-01-01,{$current_date}";
      $url = rtrim($value, '/');
      $result = 0;
      switch ($params['mode']) :
        case 'views':
          $query = "index.php?module=API&method=Actions.getPageUrl&pageUrl={$url}&idSite={$matomo_id}&period=range&date={$date_range}&format=json{$matomo_token_param}";
          break;

        case 'downloads':
          $query = "index.php?module=API&method=Actions.getDownload&downloadUrl={$url}&idSite={$matomo_id}&period=range&date={$date_range}&format=json{$matomo_token_param}";
          break;

        case 'items_in_matomo':
          $segment_encoded = 'pageUrl=^' . urlencode($value);
          $query = "index.php?module=API&method=Actions.getPageUrls&idSite={$matomo_id}&segment={$segment_encoded}&expanded=1&period=range&date={$date_range}&format=json{$matomo_token_param}";
          break;

        default:
          $this->messenger->addMessage(t('Error: Invalid mode "{$mode}" provided to islandora_matomo_service.'), 'error');
          $result = 0;

      endswitch;
      $request_url = $matomo_url . $query;

      try {
        $response = $this->httpClient->get($request_url);
        $response_body = $response->getBody();
        $status_code = $response->getStatusCode();
        if ($status_code != 200) {
          \Drupal::logger('islandora_matomo')->warning($status_code . " returned from Matomo : <pre>" . print_r($response, TRUE) . "</pre>");
        }
        else {
          $resource = json_decode($response_body, TRUE);
          if ($resource) {
            if ($mode == 'items_in_matomo') {
              $node_views = [];
              if (array_key_exists('subtable', $resource[0])) {
                foreach ($resource[0]['subtable'] as $k => $metrics) {
                  $nid = ltrim($metrics['label'], "/");
                  $node_views[$nid] = $metrics[$matomo_metric];
                }
              }
              $result = $node_views;
            } else {
              if (array_key_exists('result', $resource) && $resource['result'] == 'error') {
                \Drupal::logger('islandora_matomo')->warning("Error returned from Matomo : <pre>" . print_r($resource, TRUE) . "</pre>");
                $result = 0;
              } else {
                $result = (array_key_exists(0, $resource) ? (int) $resource[0][$matomo_metric] : 0);
              }
            }
          }
        }
      }
      catch (RequestException $e) {
        \Drupal::logger('islandora_matomo')->warning("Unable to return data from Matomo : <pre>" . $e->getMessage() . "</pre>");
      }
      return $result;
    }
  }

  /**
   * Get views for node.
   *
   * @param array $params
   *  Array that must include $params['nid'], the node ID of the node to be queried.
   *  May optionally include  $params['start_date'] and/or $params['end_date'] if a range is desired.
   */
  public function getViewsForNode($nid) {
    $node = Node::load($nid);
    $node_url = \Drupal::request()->getSchemeAndHttpHost() . $node->toUrl()->toString();
    $views = \Drupal::service('islandora_matomo.default')->queryMatomoApi($node_url, 'views');
    return $views;
  }

  /**
   * Get views for node.
   */
  public function getAllPages(string $segment = '') {
    $matomo_data = \Drupal::service('islandora_matomo.default')->queryMatomoApi($segment, 'items_in_matomo');
    return $matomo_data;
  }

  /**
   * Get download counts for single file.
   *
   * @param array $params
   *  Array that must include $params['fid'], the file entity ID of the file to be queried.
   *  May optionally include  $params['start_date'] and/or $params['end_date'] if a range is desired.
   */
  public function getDownloadsForFile($fid) {
    $file = File::load($fid);
    $file_uri = $file->getFileUri();
    $file_url = file_create_url($file_uri);
    $downloads = \Drupal::service('islandora_matomo.default')->queryMatomoApi($file_url, 'downloads');
    return $downloads;
  }

  /**
   * Calculate sum of downloads.
   *
   * @param array $params
   *  Array that must include $params['fids'], an array of file entity IDs of the files to be queried.
   *  May optionally include  $params['start_date'] and/or $params['end_date'] if a range is desired.
   */
  public function getSummedDownloadsForFiles(array $params) {
    $_islandora_matomo_sum = 0;
    foreach ($params['fids'] as $fid) {
      $params['fid'] = $fid;
      $file_downloads = \Drupal::service('islandora_matomo.default')->getDownloadsForFile($params);
      global $_islandora_matomo_sum;
      $_islandora_matomo_sum = $_islandora_matomo_sum + $file_downloads;
    }
    return $_islandora_matomo_sum;
  }

  /**
   * Get file-containing field from arbitrary Islandora media entities.
   *
   * @param int $mid
   * An integer representing a media entity ID.
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
