<?php

namespace Drupal\content_moderation_actions\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\content_moderation\Entity\ModerationState;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\StateTransitionValidation;
use Drupal\content_moderation_actions\AjaxReloadCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StateChange extends ControllerBase {

  /**
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * @var \Drupal\content_moderation\StateTransitionValidation
   */
  protected $validation;

  public function __construct(ModerationInformationInterface $moderationInformation, StateTransitionValidation $validation) {
    $this->moderationInfo = $moderationInformation;
    $this->validation = $validation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_moderation.moderation_information'),
      $container->get('content_moderation.state_transition_validation')
    );
  }

  public function change($entity_type_id, $entity_id, $from, $to) {
    $latest_revision = $this->moderationInfo->getLatestRevision($entity_type_id, $entity_id);
    $latest_revision->get('moderation_state')->target_id = $to;
    $latest_revision->save();

    drupal_set_message(t('%entity_label got changed from %from to %to', [
      '%entity_label' => $latest_revision->label(),
      '%from' => ModerationState::load($from)->label(),
      '%to' => ModerationState::load($to)->label(),
    ]));

    return (new AjaxResponse())
      ->addCommand(new AjaxReloadCommand());
  }

  public function access($entity_type_id, $entity_id, $from, $to) {
    return AccessResult::allowedIf($this->validation->userMayTransition($from, $to, $this->currentUser()))
      ->cachePerPermissions();
  }

}
