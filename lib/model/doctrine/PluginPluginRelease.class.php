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
 * PluginPluginRelease
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    opPluginChannelServerPlugin
 * @subpackage model
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
abstract class PluginPluginRelease extends BasePluginRelease implements opAccessControlRecordInterface
{
  public function setOpenPNEDeps($ge, $le)
  {
    if (!$ge)
    {
      $ge = null;
    }

    if (!$le)
    {
      $le = null;
    }

    $this->op_version_ge_string = $ge;
    $this->op_version_le_string = $le;
    $this->op_version_ge = opPluginChannelServerToolkit::calculateVersionId($ge);
    $this->op_version_le = opPluginChannelServerToolkit::calculateVersionId($le);

    return $this;
  }

  public function generateRoleId(Member $member)
  {
    if ($this->Package->isLead($member->id))
    {
      return 'lead';
    }
    elseif ($this->Package->isDeveloper($member->id))
    {
      return 'developer';
    }
    elseif ($this->Package->isContributor($member->id))
    {
      return 'contributor';
    }
    elseif ($member->id)
    {
      return 'sns_member';
    }

    return 'anonymous';
  }
}
