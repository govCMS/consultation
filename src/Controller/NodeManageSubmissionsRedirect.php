<?php

namespace Drupal\consultation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\consultation\ConsultationNodeHelper;

/**
 * Defines NodeManageSubmissionsRedirect class.
 */
class NodeManageSubmissionsRedirect extends ControllerBase {

  /**
   * Redirect to the submissions for this node, or page not found.
   *
   * @return array
   *   Return markup array.
   */
  public function handler(NodeInterface $node) {

    if ($node->bundle() == 'consultation') {
      $helper = new ConsultationNodeHelper($node);
      if ($webform = $helper->getWebform()) {
        $admin_url = $webform->toUrl('results-submissions')->toString();

        // We can't pass node/263 or node%3A263 in link options above because they end
        // up node/263 and node%253A263. Whereas we want WebformSubmissionFilterForm
        // to get node%3A263. Unclear if this is a bug or PEBCAK.
        $admin_url .= '?entity=node%3A263';

        return new RedirectResponse($admin_url);
      }
    }

    throw new NotFoundHttpException();
  }

}
