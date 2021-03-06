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
 * PluginPluginPackage form.
 *
 * @package    opPluginChannelServerPlugin
 * @subpackage filter
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
abstract class PluginPluginPackageFormFilter extends BasePluginPackageFormFilter
{
  public function __construct($defaults = array(), $options = array(), $CSRFSecret = null)
  {
    parent::__construct($defaults, $options, false);
  }

  public function setup()
  {
    parent::setup();

    $this->setWidget('keyword', new sfWidgetFormFilterInput(array('with_empty' => false)));
    $this->setValidator('keyword', new sfValidatorPass(array('required' => false)));

    $this->useFields(array('keyword', 'category_id'));
  }

  public function getFields()
  {
    return array_merge(
      parent::getFields(),
      array('keyword' => 'Keyword')
    );
  }

  public function addKeywordColumnQuery(Doctrine_Query $q, $field, $values)
  {
    $alias = $q->getRootAlias();
    $text = '%'.$values['text'].'%';

    $q->andWhere($alias.'.name LIKE ? OR '.$alias.'.description LIKE ?', array($text, $text));
  }
}
