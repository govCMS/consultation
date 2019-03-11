<?php

namespace Drupal\consultation\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\file\FileStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\webform\WebformSubmissionStorageInterface;

/**
 * Provides a 'PublicSubmissions' block.
 *
 * @Block(
 *  id = "consultation_public_submissions",
 *  admin_label = @Translation("Public submissions"),
 * )
 *
 * @deprecated
 * This block is possibly temporary in lieu of webform_views in govcms.
 */
class PublicSubmissions extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $submissionStorage;

  /**
   * File storage.
   *
   * @var \Drupal\file\FileStorage
   */
  protected $fileStorage;

  /**
   * Creates a LocalActionsBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\webform\WebformSubmissionStorageInterface $webform_submission_storage
   *   The webform submission storage.
   * @param \Drupal\file\FileStorage $file_storage
   *   The Drupal file storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, RouteMatchInterface $route_match, WebformSubmissionStorageInterface $webform_submission_storage, FileStorage $file_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->database = $database;
    $this->submissionStorage = $webform_submission_storage;
    $this->fileStorage = $file_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('current_route_match'),
      $container->get('entity.manager')->getStorage('webform_submission'),
      $container->get('entity.manager')->getStorage('file')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    if ($submissions = $this->getSubmissions()) {
      $webform_submissions = $this->submissionStorage->loadMultiple(array_keys($submissions));
      $output = [
        '#type' => 'container',
        '#attributes' => ['class' => ['consultation-public-submissions']],
        'list_wrapper' => [],
      ];

      /** @var \Drupal\webform\WebformSubmissionInterface $submission */
      foreach ($webform_submissions as $submission) {
        $data = $submission->getData();
        $name = $data['published_name'];
        $files = [];

        foreach ($data['uploads'] as $fid) {
          $file = $this->fileStorage->load($fid);
          $mime = $file->filemime->value;
          $link = $file->url();
          $size = $file->filesize->value;
          if ($mime == 'application/pdf') {
            $icon = '/core/themes/classy/images/icons/application-pdf.png';
            $label = 'Download PDF';
          }
          else {
            $icon = '/core/themes/classy/images/icons/x-office-document.png';
            $label = 'Download Doc';
          }

          $files[] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['submission-file']],
            'one-file' => [
              '#type' => 'container',
              'icon' => [
                '#type' => 'html_tag',
                '#tag' => 'img',
                '#attributes' => [
                  'alt' => 'file type logo',
                  'src' => $icon,
                ],
              ],
              'download' => [
                '#markup' => '<a href="' . $link . '" type="' . $mime . '; length=' . $size . '">' . $this->t($label) . '</a>', // phpcs:ignore
              ],
            ],
          ];
        }

        $output['list_wrapper'][] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['submission-row']],
          'row_wrapper' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['public-name']],
            'name' => [
              '#type' => 'html_tag',
              '#tag' => 'p',
              '#value' => $name,
            ],
            'files_wrapper' => [
              '#type' => 'container',
              '#attributes' => ['class' => ['submission-files']],
              'files' => $files,
            ],
          ],
        ];

      }

      $build['consultation_public_submissions'] = $output;
    }

    return $build;
  }

  /**
   * Get submission ids relevant to the current node.
   *
   * @return array|bool
   *   The submissions keyed on sid.
   *
   * @todo query will either need search/paging, or govcms needs webform_views.
   */
  public function getSubmissions() {

    $parameters = $this->routeMatch->getParameters();
    if ($node = $parameters->get('node')) {
      if ($node->bundle() == 'consultation') {
        $nid = $node->id();

        $sql = '
          SELECT ws.sid, ws.webform_id, wsd2.value
          FROM
            {webform_submission} AS ws
            INNER JOIN {webform_submission_data} AS wsd
              ON ws.sid = wsd.sid AND wsd.name = :field_approval
            LEFT JOIN {webform_submission_data} AS wsd2
              ON ws.sid = wsd2.sid AND wsd2.name = :field_name
          WHERE
            ws.entity_type = :entity_type
            AND ws.entity_id = :nid
          ORDER BY wsd2.value ASC
        ';

        $result = $this->database->query($sql, [
          ':nid' => $nid,
          ':entity_type' => 'node',
          ':field_name' => 'published_name',
          ':field_approval' => 'admin_display_approval',
        ]);
        if ($result) {
          $sids = [];
          while ($row = $result->fetchAssoc()) {
            $sids[$row['sid']] = $row['value'];
          }
        }

        if (count($sids)) {
          return $sids;
        }

      }
    }

    return FALSE;
  }

}
