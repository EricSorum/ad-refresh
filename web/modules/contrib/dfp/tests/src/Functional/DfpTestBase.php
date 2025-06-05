<?php

namespace Drupal\Tests\dfp\Functional;

use Drupal\dfp\Entity\Tag;
use Drupal\dfp\Entity\TagInterface;
use Drupal\dfp\View\TagView;
use Drupal\Tests\BrowserTestBase;

/**
 * An abstract class to build DFP tests from.
 */
abstract class DfpTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['dfp'];

  /**
   * An admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an admin user with all the permissions needed to run tests.
    $this->adminUser = $this->drupalCreateUser([
      'administer DFP',
      'access administration pages',
      'administer blocks',
    ]);
    $this->drupalLogin($this->adminUser);

    // Set up global settings needed for DFP ads to work.
    \Drupal::configFactory()->getEditable('dfp.settings')
      ->set('network_id', '12345')
      ->set('default_slug', 'Global DFP slug')
      ->save();
  }

  /**
   * Creates a basic dfp ad tag.
   *
   * @param array $edit
   *   An array of values for the DFP tag form.
   *
   * @return \Drupal\dfp\Entity\Tag
   *   The created DFP tag.
   */
  protected function dfpCreateTag($edit = []) {
    // Create a new tag.
    $edit += $this->dfpBasicTagEditValues();
    $this->drupalGet('admin/structure/dfp/tags/add');
    $this->submitForm($edit, 'Save');

    // Load the tag object.
    $tag = Tag::load($edit['id']);
    $this->assertTrue(is_object($tag) && $tag->id() == $edit['id'], 'The new DFP tag was saved correctly.');

    // Display the new tag.
    $this->drupalPlaceBlock('dfp_ad:' . $tag->uuid());

    return $tag;
  }

  /**
   * Creates a simple form values $edit array to be used to create a DFP tag.
   *
   * @return array
   *   A simple $edit array to be used on the DFP tag form.
   */
  protected function dfpBasicTagEditValues() {
    $machineName = $this->randomMachineName(16);
    $basicTag = [
      'id' => mb_strtolower($machineName),
      'slot' => $machineName,
      'size' => implode(',', $this->dfpGenerateSize(2)),
      'adunit' => $this->randomMachineName(),
      'block' => 1,
      'slug' => $this->randomMachineName(32),
      'adsense_backfill[ad_types]' => '',
      'adsense_backfill[channel_ids]' => '',
      'adsense_backfill[color][background]' => '',
      'adsense_backfill[color][border]' => '',
      'adsense_backfill[color][link]' => '',
      'adsense_backfill[color][text]' => '',
      'adsense_backfill[color][url]' => '',
      'targeting[0][target]' => $this->randomMachineName(8),
      'targeting[0][value]' => $this->randomMachineName(8),
      'breakpoints[0][browser_size]' => $this->dfpGenerateSize(),
      'breakpoints[0][ad_sizes]' => implode(',', $this->dfpGenerateSize(2)),
    ];

    return $basicTag;
  }

  /**
   * Generates a random size (or array of sizes) to use when testing tags.
   *
   * @param int $count
   *   How many sizes to generate.
   *
   * @return string|array
   *   A size formatted as ###x### or an array of sizes if $count > 1.
   */
  protected function dfpGenerateSize($count = 1) {
    $sizes = [
      '300x250',
      '300x600',
      '728x90',
      '728x10',
      '160x600',
      '120x80',
      '300x100',
      '50x50',
      '160x300',
    ];
    shuffle($sizes);

    return $count == 1 ? array_pop($sizes) : array_slice($sizes, 0, min($count, count($sizes)));
  }

  /**
   * Edits a given tag specified by $id with the given values.
   *
   * @param string $id
   *   The DFP tag ID.
   * @param array $edit
   *   An array of values for the DFP tag form.
   *
   * @return \Drupal\dfp\Entity\Tag
   *   The edited DFP tag.
   */
  protected function dfpEditTag($id, &$edit) {
    // Make sure there is no machinename set when we are editing.
    if (isset($edit['id'])) {
      unset($edit['id']);
    }
    $this->drupalGet('admin/structure/dfp/tags/manage/' . $id);
    $this->submitForm($edit, 'Save');
    return Tag::load($id);
  }

  /**
   * Converts a DFP Tag config entity to a TagView object.
   *
   * @param \Drupal\dfp\Entity\TagInterface $tag
   *   The DFP tag.
   *
   * @return \Drupal\dfp\View\TagView
   *   The TagView object.
   */
  protected function dfpTagToTagView(TagInterface $tag) {
    return new TagView($tag, $this->getGlobalConfig(), $this->container->get('dfp.token'), $this->container->get('module_handler'));
  }

  /**
   * Gets the global DFP settings.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The global DFP settings.
   */
  protected function getGlobalConfig() {
    return \Drupal::config('dfp.settings');
  }

  /**
   * Assert that a property is properly being set.
   *
   * @param string $property
   *   The property.
   * @param string $key
   *   The key.
   * @param string $val
   *   The value.
   *
   * @return bool
   *   TRUE if the property is set, FALSE otherwise.
   */
  protected function assertPropertySet($property, $key, $val) {
    $pattern = $this->getPropertyPattern($property, $key, $val);
    return $this->assertSession()->responseMatches($pattern);
  }

  /**
   * Gets pattern used in assertPropertySet() and assertPropertyNotSet().
   *
   * @param string $property
   *   The property.
   * @param string $key
   *   The key.
   * @param string $val
   *   The value.
   *
   * @return string
   *   The pattern.
   */
  private function getPropertyPattern($property, $key, $val) {
    return '|' . '.set' . $property . '\(\'' . $key . '\',{1}\s(.)*' . addslashes($val) . '|';
  }

  /**
   * Assert that a property is not being set.
   *
   * @param string $property
   *   The property.
   * @param string $key
   *   The key.
   * @param string $val
   *   The value.
   */
  protected function assertPropertyNotSet($property, $key, $val) {
    $pattern = $this->getPropertyPattern($property, $key, $val);
    $this->assertSession()
      ->responseNotMatches($pattern);
  }

}
