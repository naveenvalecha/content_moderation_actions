<?php

namespace Drupal\content_moderation_actions;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AjaxReloadCommand class.
 */
class AjaxReloadCommand implements CommandInterface {

  /**
   * Return an array to be run through json_encode and sent to the client.
   */
  public function render() {
    return [
      'command' => 'reload',
    ];
  }

}
