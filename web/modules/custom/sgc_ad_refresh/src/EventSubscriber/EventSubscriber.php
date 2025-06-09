<?php

namespace Drupal\sgc_ad_refresh\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Drupal\Core\Render\AttachmentsInterface;

class EventSubscriber implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    /* 
    The event we want to subscribe to is KernelEvents::RESPONSE.
    The local method we want to run is called onResponse.
    The priority level of 999 is right below Olytics.
    */
    $events[KernelEvents::RESPONSE][] = ['onResponse', 999];
    return $events;
  }

  /**
   *
   */
  public function onResponse(ResponseEvent $event) {
    // Only act upon the main request and not sub-requests.
    if ($event->isMainRequest()) {
      // Get module configuration.
      $config = \Drupal::config('sgc_ad_refresh.settings') ?? NULL;
      // Put configuration into PHP array.
      $config_arr = !empty($config->get('items')) ? json_decode($config->get('items'), true) : [];
      // The status is either 1 or 0, determining if the module is enabled.
      $status = $config_arr['status'] ?? NULL;
      // Need to use empty() function to check if status is not undefined, and is also set to 1
      if (!empty($status) && $status == 1) {
        // Get the response object from the kernel event.
        $response = $event->getResponse();
        $page_exclusions = $config_arr['page_exclusions'];
        // Only act if the response is one that is able to have attachments.
        if (_sgc_ad_refresh_visibility_pages($page_exclusions) && $response instanceof AttachmentsInterface) {
          // First set all existing attachments to $attachments.
          $attachments = $response->getAttachments();
          // Then add the JavaScript library to $attachments.
          $attachments['library'][] = 'sgc_ad_refresh/sgc_ad_refresh';
          // Set the updated set of attachments to the response object
          $response->setAttachments($attachments);
        }
      }
    }
  }
}
