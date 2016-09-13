<?php

namespace Drupal\content_moderation_actions\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\content_moderation\ModerationInformationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StateChangeDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new StateChangeDeriver instance.
   */
  public function __construct(ModerationInformationInterface $moderationInformation, EntityTypeManagerInterface $entityTypeManager) {
    $this->moderationInformation = $moderationInformation;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('content_moderation.moderation_information'),
      $container->get('entity_type.manager')
    );
  }

  protected function getModeratedEntityTypeLabels() {
    $entity_types = $this->moderationInformation->selectRevisionableEntities($this->entityTypeManager->getDefinitions());
    return array_map(function (EntityTypeInterface $entityType) {
      return $entityType->getLabel();
    }, $entity_types);
  }

  /**
   * @return \Drupal\content_moderation\ModerationStateInterface[]
   */
  protected function getAvailableStates() {
    return $this->entityTypeManager->getStorage('moderation_state')->loadMultiple();
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (empty($this->derivatives)) {
      $plugin = $base_plugin_definition;
      $states = $this->getAvailableStates();
      $entity_type_labels = $this->getModeratedEntityTypeLabels();

      foreach ($entity_type_labels as $entity_type_id => $entity_label) {
        $plugin['type'] = $entity_type_id;
        foreach ($states as $state_id => $state) {
          $plugin['state'] = $state_id;
          $plugin['label'] = t('Set @entity_type_label as @state_label', [
            '@entity_type_label' => $entity_label,
            '@state_label' => $state->label(),
          ]);
          $this->derivatives[$entity_type_id . '__' . $state_id] = $plugin;
        }
      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
