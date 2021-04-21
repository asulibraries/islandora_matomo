<?php

namespace Drupal\islandora_matomo_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;
use Drupal\media\Entity\Media;



/**
 * Provides a 'IslandoraNodeAllMediaDownloadsBlock' block.
 *
 * @Block(
 *  id = "islandora_node_all_media_downloads_block",
 *  admin_label = @Translation("Islandora node all media downloads block"),
 * )
 */
class IslandoraNodeAllMediaDownloadsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (\Drupal::routeMatch()->getRouteName() == 'entity.node.canonical') {

      // Get views for node
      $node = \Drupal::routeMatch()->getParameter('node');
      $views = \Drupal::service('islandora_matomo.default')->getViewsForNode(['nid' => $node->id()]);
      $mids = \Drupal::entityQuery('media')
        ->condition('field_media_of', $node->id())
        ->execute();
      $media_data = [];
      foreach ($mids as $mid) {
        $media = Media::load($mid);
        $fid = \Drupal::service('islandora_matomo.default')->getFileFromMedia($mid);
        $media_data[$mid]['label'] = $media->label();
        $media_data[$mid]['downloads'] = \Drupal::service('islandora_matomo.default')->getDownloadsForFile(['fid' => $fid]);
      }
      $content = "<div id='islandora-node-all-media-download-block-wrapper' class='islandora-node-all-media-download-block'>";
      $content .= "<span id='islandora-node-all-media-download-block-views' class='islandora-node-all-media-download-block'>Views for '{$node->label()}': {$views}</span><br/>";
      foreach ($media_data as $data) {
        $content .= "<span class='islandora-node-all-media-download-block islandora-node-all-media-download-block-download'>Downloads for '{$data['label']}': {$data['downloads']}</span><br/>";
      }
      $content .= "</div>";
    }
    else {
      $content = "This page is not a node. Please restrict this block's configuration to display on nodes only.";
    }

    return [
      '#markup' => Markup::create($content),
    ];
  }

  public function getCacheMaxAge() {
    return 0;
  }

}
