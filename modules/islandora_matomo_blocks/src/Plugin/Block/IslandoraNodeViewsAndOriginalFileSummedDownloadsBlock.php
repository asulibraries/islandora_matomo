<?php

namespace Drupal\islandora_matomo_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;
use Drupal\media\Entity\Media;



/**
 * Provides a 'IslandoraNodeViewsAndOriginalFileSummedDownloadsBlock' block.
 *
 * @Block(
 *  id = "islandora_node_views_and_original_file_summed_downloads_block",
 *  admin_label = @Translation("Islandora node views and Original Files summed downloads block"),
 * )
 */
class IslandoraNodeViewsAndOriginalFileSummedDownloadsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (\Drupal::routeMatch()->getRouteName() == 'entity.node.canonical') {

      // Get views for node
      $node = \Drupal::routeMatch()->getParameter('node');
      $views = \Drupal::service('islandora_matomo.default')->getViewsForNode(['nid' => $node->id()]);
      // Get downloads for Original File media of node
      $original_file_tid = key(\Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties(['name' => "Original File"]));
      $original_file_mids = \Drupal::entityQuery('media')
        ->condition('field_media_of', $node->id())
        ->condition('field_media_use', $original_file_tid)
        ->execute();
      $fids = array();
      foreach ($original_file_mids as $mid) {
        $fid = \Drupal::service('islandora_matomo.default')->getFileFromMedia($mid);
        $fids[] = $fid;
      }
      $downloads = \Drupal::service('islandora_matomo.default')->getSummedDownloadsForFiles(['fids' => $fids]);
      $content = <<<EOS
<div id='islandora-node-and-original-files-download-block-wrapper' class='islandora-node-and-original-files-download-block'>
  <span id='islandora-node-and-original-files-download-block-views' class='islandora-node-and-original-files-download-block'>Views: {$views}</span><br/>
  <span id='islandora-node-and-original-files-download-block-downloads' class='islandora-node-and-original-files-download-block'>Downloads: {$downloads}</span>
</div>
EOS;
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
