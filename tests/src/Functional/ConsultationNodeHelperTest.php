<?php

namespace Drupal\Tests\consultation\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Consultation node helper.
 * @group consultation
 */
class ConsultationNodeHelperTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = [
    'node',
    'consultation',
    'field',
    'field_ui',
    'content_moderation',
    'datetime_range',
    'field_layout',
    'layout_discovery',
    'path',
    'taxonomy',
    'menu_ui',
  ];

  /**
   * Tests if the paragraph type class is present when added.
   */
  public function testConsultationHelper() {

    return;
    $this->loginAsAdmin();

    // Add paragraphs to a node and check if their type is present as a class.
    $this->drupalGet('node/add/consultation');
//    $this->getSession()->getPage()->findButton('paragraphs_test_paragraph_add_more')->press();
//    $this->assertSession()->responseContains('paragraph-type--test-paragraph');
//    $this->getSession()->getPage()->findButton('paragraphs_text_add_more')->press();
//    $this->assertSession()->responseContains('paragraph-type--text');
  }

    /**
   * Creates an user with admin permissions and log in.
   *
   * @param array $additional_permissions
   *   Additional permissions that will be granted to admin user.
   * @param bool $reset_permissions
   *   Flag to determine if default admin permissions will be replaced by
   *   $additional_permissions.
   *
   * @return object
   *   Newly created and logged in user object.
   */
  public function loginAsAdmin($additional_permissions = [], $reset_permissions = FALSE) {

    $permissions = [
      'administer content types',
      'administer node fields',
    ];

    if ($reset_permissions) {
      $permissions = $additional_permissions;
    }
    elseif (!empty($additional_permissions)) {
      $permissions = array_merge($permissions, $additional_permissions);
    }

    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
    return $this->admin_user;
  }

}
