<?php

namespace Drupal\islandora_matomo_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;


/**
 * Provides a 'IslandoraNodeViewsBlock' block.
 *
 * @Block(
 *  id = "islandora_node_views_block",
 *  admin_label = @Translation("Islandora node views block"),
 * )
 */
class IslandoraNodeViewsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (\Drupal::routeMatch()->getRouteName() == 'entity.node.canonical') {
      $node = \Drupal::routeMatch()->getParameter('node');
      $views = \Drupal::service('islandora_matomo.default')->getViewsForNode(['nid' => $node->id()]);
      $content = <<<EOS
<div id='islandora-node-views-block-wrapper' class='islandora-node-views-block'>
  <span id='islandora-node-views-block-views' class='islandora-node-views-block'>Views: {$views}</span>
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
