<?php

namespace Drupal\stanford_profile_helper\Plugin\Scheduler;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\scheduler\SchedulerPluginBase;

/**
 * Plugin for Media entity type.
 *
 * @package Drupal\Scheduler\Plugin\Scheduler
 *
 * @SchedulerPlugin(
 *  id = "paragraph_scheduler",
 *  label = @Translation("Paragraph Scheduler Plugin"),
 *  description = @Translation("Provides support for scheduling paragraph entities"),
 *  entityType = "paragraph",
 *  dependency = "paragraphs",
 *  develGenerateForm = "devel_generate_form_media",
 *  userViewRoute = "view.scheduler_scheduled_media.user_page",
 *  schedulerEventClass = "\Drupal\stanford_profile_helper\Event\SchedulerParagraphEvents"
 * )
 */
class ParagraphScheduler extends SchedulerPluginBase implements ContainerFactoryPluginInterface {}
