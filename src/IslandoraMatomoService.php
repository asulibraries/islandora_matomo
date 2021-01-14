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
   */
  public function queryMatomoApi(string $url, string $mode) {
    $url = rtrim($url, '/');
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
      $result = 0;
      switch ($mode) :
        case 'views':
          $query = "index.php?module=API&method=Actions.getPageUrl&pageUrl={$url}&idSite={$matomo_id}&period=range&date={$date_range}&format=json{$matomo_token_param}";
          break;

        case 'downloads':
          $query = "index.php?module=API&method=Actions.getDownload&downloadUrl={$url}&idSite={$matomo_id}&period=range&date={$date_range}&format=json{$matomo_token_param}";
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
            if (array_key_exists('result', $resource) && $resource['result'] == 'error') {
              \Drupal::logger('islandora_matomo')->warning("Error returned from Matomo : <pre>" . print_r($resource, TRUE) . "</pre>");
              $result = 0;
            } else {
              $result = (array_key_exists(0, $resource) ? (int) $resource[0][$matomo_metric] : 0);
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
   */
  public function getViewsForNode($nid) {
    $node = Node::load($nid);
    $node_url = \Drupal::request()->getSchemeAndHttpHost() . $node->toUrl()->toString();
    $views = \Drupal::service('islandora_matomo.default')->queryMatomoApi($node_url, 'views');
    return $views;
  }

  /**
   * Get download counts for single file.
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
   */
  public function getSummedDownloadsForFiles($fids) {
    $sum = 0;
    foreach ($fids as $fid) {
      $file_downloads = \Drupal::service('islandora_matomo.default')->getDownloadsForFile($fid);
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
