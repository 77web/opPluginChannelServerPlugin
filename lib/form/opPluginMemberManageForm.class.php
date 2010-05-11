<?php

/**
* Copyright 2010 Kousuke Ebihara
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
* http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*/

/**
 * Plugin manage from
 *
 * @package    opPluginChannelServerPlugin
 * @subpackage form
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
class opPluginMemberManageForm extends BaseForm
{
  protected $package;

  public function configure()
  {
    $choices = array(
      'lead' => 'lead',
      'developer' => 'developer',
      'contributor' => 'contributor',
    );

    $this
      ->setWidget('position', new sfWidgetFormChoice(array('choices' => $choices)))
      ->setValidator('position', new sfValidatorChoice(array('choices' => array_values($choices))))

      ->setWidget('member_id', new sfWidgetFormInputHidden())
      ->setValidator('member_id', new sfValidatorString())

      ->setWidget('package_id', new sfWidgetFormInputHidden())
      ->setValidator('package_id', new sfValidatorString())
    ;

    $this->widgetSchema->setNameFormat('plugin_manage[%s]');

    $this->mergePostValidator(new sfValidatorCallback(array('callback' => array($this, 'validatePositionUpdate'))));
  }

  public function validatePositionUpdate($validator, $value, $arguments)
  {
    $obj = Doctrine::getTable('PluginMember')->findOneByMemberIdAndPackageId($value['member_id'], $value['package_id']);
    $leadCount = count($obj->getPackage()->getLeadMemberIds());

    // lead-count won't be changed
    if ($obj->getPosition() !== 'lead' || 'lead' === $value['position'])
    {
      return $value;
    }

    // postion won't be changed
    if ($obj->getPosition() === $value['position'])
    {
      return $value;
    }

    // there will be no lead for this plugin
    if ('lead' !== $value['position'] && 1 === $leadCount)
    {
      throw new sfValidatorError($validator, 'At least a lead is required in a plugin.');
    }

    return $value;
  }

  public function save()
  {
    $obj = Doctrine::getTable('PluginMember')->findOneByMemberIdAndPackageId($this->getValue('member_id'), $this->getValue('package_id'));
    $obj->position = $this->getValue('position');
    $obj->save();
  }
}
