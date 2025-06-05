<?php

namespace Drupal\Tests\dfp\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests DFP global configuration.
 *
 * @group dfp
 *
 * @see dfp.settings.yml
 * @see \Drupal\dfp\Form\AdminSettings
 */
class GlobalSettingsTest extends DfpTestBase {

  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['dfp'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests \Drupal\dfp\Form\AdminSettings form and dfp_page_attachments().
   */
  public function testGlobalSettings() {
    $edit = [
      'adunit_pattern' => 'example.com',
      'network_id' => '123456789',
      'async_rendering' => TRUE,
      'single_request' => TRUE,
      'collapse_empty_divs' => '1',
      'targeting[0][target]' => '<em>test target</em>',
      'targeting[0][value]' => '<em>test value</em>, test value 2 ',
    ];
    $this->drupalGet('admin/structure/dfp/settings');
    $this->submitForm($edit, $this->t('Save configuration'));

    $this->drupalGet('<front>');
    $this->assertSession()->responseNotContains('googletag');

    // Create a tag.
    $tag = $this->dfpCreateTag();
    $tag->set('adunit', '');
    $tag->save();

    $this->drupalGet('<front>');
    $this->assertSession()
      ->responseContains('googletag.pubads().enableAsyncRendering();');
    $this->assertSession()
      ->responseContains('googletag.pubads().enableSingleRequest();');
    $this->assertSession()
      ->responseContains('googletag.pubads().collapseEmptyDivs();');
    $this->assertSession()
      ->responseContains("googletag.pubads().setTargeting('&lt;em&gt;test target&lt;/em&gt;', ['&lt;em&gt;test value&lt;/em&gt;','test value 2']);");
    $this->assertSession()->responseContains('/123456789/example.com');

    $edit = [
      'network_id' => '123456789',
      'async_rendering' => FALSE,
      'single_request' => FALSE,
      'collapse_empty_divs' => '0',
      'click_url' => '/custom_click_url',
      'targeting[0][target]' => 'test target ',
      'targeting[0][value]' => 'test value 3',
      'targeting[1][target]' => 'test target 2',
      'targeting[1][value]' => 'test value 4',
    ];
    $this->drupalGet('admin/structure/dfp/settings');
    $this->submitForm($edit, $this->t('Save configuration'));

    $this->drupalGet('<front>');
    $this->assertSession()
      ->responseNotContains('googletag.pubads().enableAsyncRendering();');
    $this->assertSession()
      ->responseNotContains('googletag.pubads().enableSingleRequest();');
    $this->assertSession()
      ->responseNotContains('googletag.pubads().collapseEmptyDivs();');
    $this->assertSession()
      ->responseContains("googletag.pubads().setTargeting('test target', ['test value 3']);");
    $this->assertSession()
      ->responseContains("googletag.pubads().setTargeting('test target 2', ['test value 4']);");
    $this->assertEquals('/custom_click_url', \Drupal::config('dfp.settings')
      ->get('click_url'));

    $edit = [
      'async_rendering' => TRUE,
      'click_url' => '/custom_click_url',
      'adunit_pattern' => '$has_an_illegal_character',
    ];
    $this->drupalGet('admin/structure/dfp/settings');
    $this->submitForm($edit, $this->t('Save configuration'));
    $this->assertSession()
      ->pageTextContains($this->t('Setting a click URL does not work with async rendering.'));
    $this->assertSession()
      ->pageTextContains($this->t('Ad Unit Patterns can only include letters, numbers, hyphens, dashes, periods, slashes and tokens.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $web_user = $this->drupalCreateUser(['administer DFP']);
    $this->drupalLogin($web_user);
  }

}
