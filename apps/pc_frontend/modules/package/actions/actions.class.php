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
 * package actions.
 *
 * @package    opPluginChannelServerPlugin
 * @subpackage package
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
class packageActions extends sfActions
{
  public function preExecute()
  {
    error_reporting(error_reporting() & ~(E_STRICT | E_DEPRECATED));

    if ($this->getRoute() instanceof sfDoctrineRoute)
    {
      $object = $this->getRoute()->getObject();
      if ($object instanceof PluginPackage)
      {
        $this->package = $object;
      }
      elseif ($object instanceof PluginRelease)
      {
        $this->release = $object;
        $this->package = $object->Package;
      }
      elseif ($object instanceof Member)
      {
        $this->member = $object;
      }
    }

    if ($this->getUser()->hasCredential('SNSMember'))
    {
      $this->security[strtolower($this->actionName)] = array('is_secure' => true);
    }
  }

  public function executeHome(sfWebRequest $request)
  {
  }

  public function executeHomeRedirector(sfWebRequest $request)
  {
    $this->redirect('package_home', $this->package);
  }

  public function executeNew(sfWebRequest $request)
  {
    $this->form = new PluginPackageForm();
  }

  public function executeCreate(sfWebRequest $request)
  {
    $this->clearOutputCacheDirectory();

    $this->form = new PluginPackageForm();
    $this->redirectIf($this->form->bindAndSave($request['plugin_package'], $request->getFiles('plugin_package')),
      'package_home', $this->form->getObject());

    $this->setTemplate('new');
  }

  public function executeEdit(sfWebRequest $request)
  {
    $this->form = new PluginPackageForm($this->package);
    if($this->package->isDeletable($this->getUser()->getMemberId()))
    {
      $this->deleteForm = new sfForm();
    }
  }

  public function executeUpdate(sfWebRequest $request)
  {
    $this->clearOutputCacheDirectory();

    $this->form = new PluginPackageForm($this->package);
    $this->redirectIf($this->form->bindAndSave($request['plugin_package'], $request->getFiles('plugin_package')),
      'package_home', $this->form->getObject());

    $this->setTemplate('edit');
  }

  public function executeDelete(sfWebRequest $request)
  {
    if(!$this->package->isDeletable($this->getUser()->getMemberId()))
    {
      return sfView::ERROR;
    }
    if($request->isMethod(sfRequest::PUT))
    {
      $request->checkCSRFProtection();
      $this->package->delete();
      $this->getUser()->setFlash('notice', 'The package was deleted successfully.');
      $this->redirect('@homepage');
    }
    $this->deleteForm = new sfForm();
    $this->deleteForm->setWidget('sf_method', new sfWidgetFormInputHidden(array('default'=>'put')));
    $this->backForm = new sfForm();
  }

  public function executeAddRelease(sfWebRequest $request)
  {
    $this->form = new opPluginPackageReleaseForm();
    $this->form->setPluginPackage($this->package);
    if ($request->isMethod(sfWebRequest::POST))
    {
      $this->form->bind($request['plugin_release'], $request->getFiles('plugin_release'));
      if ($this->form->isValid())
      {
        try
        {
          $this->form->uploadPackage();
        }
        catch (RuntimeException $e)
        {
          $this->handleReleaseException($e);
        }
        catch (VersionControl_Git_Exception $e)
        {
          $this->handleReleaseException($e);
        }

        $this->getUser()->setFlash('notice', 'Released plugin package');
        $this->redirect('package_home', $this->package);
      }
    }
  }

  protected function handleReleaseException($e, $message = 'Invalid.')
  {
    if ('dev' === sfConfig::get('sf_environment'))
    {
      throw $e;
    }

    $this->getUser()->setFlash('error', $message);
    $this->redirect('@package_add_release?name='.$this->package->getName());
  }

  public function executeJoin(sfWebRequest $request)
  {
    $this->form = new opPluginPackageJoinForm();
    $this->form->setPluginPackage($this->package);

    if (opPlugin::getInstance('opMessagePlugin')->getIsActive())
    {
      $this->form->injectMessageField();
    }

    if ($request->isMethod(sfWebRequest::POST))
    {
      $this->form->bind($request['plugin_join']);
      if ($this->form->isValid())
      {
        $this->form->send();

        $this->getUser()->setFlash('notice', 'Sent join request');
        $this->redirect('package_home', $this->package);
      }
    }
  }

  public function executeToggleUsing(sfWebRequest $request)
  {
    $this->forward404Unless($this->getRequest()->isXmlHttpRequest());
    $this->getResponse()->setContentType('application/json');

    try
    {
      $request->checkCSRFProtection();
    }
    catch (sfValidatorErrorSchema $e)
    {
      $this->forward404();
    }

    $memberId = $this->getUser()->getMemberId();
    $isUse = $this->package->isUser($memberId);
    $this->package->toggleUsing($memberId);

    return $this->renderText(json_encode(array($this->package->countUsers(), !$isUse)));
  }

  public function executeManageMember(sfWebRequest $request)
  {
    $this->pager = Doctrine::getTable('PluginMember')->getPager($this->package->id, $request->getParameter('page', 1));

    if ($request->isMethod(sfWebRequest::POST))
    {
      $form = new opPluginMemberManageForm();
      $form->bind($request['plugin_manage']);
      if ($form->isValid())
      {
        $form->save();

        $this->getUser()->setFlash('notice', 'Configured.');

        $obj = Doctrine::getTable('PluginMember')
          ->findOneByMemberIdAndPackageId($this->getUser()->getMemberId(), $form->getValue('package_id'));

        $this->redirectIf($obj->getPosition() !== 'lead', 'package_home', $this->package);
        $this->redirect('package_manageMember', $this->package);
      }
      else
      {
        $this->getUser()->setFlash('error', (string)$form->getErrorSchema());
      }
    }
  }

  public function executeRelease(sfWebRequest $request)
  {
    foreach (array('channel_name', 'summary', 'suggestedalias') as $v)
    {
      $this->$v = opPluginChannelServerToolkit::getConfig($v, str_replace(':80', '', $this->getRequest()->getHost()));
    }

    $baseUrl = 'http://'.$this->channel_name.'pluginRest/';
    $channel = opPluginChannelServerToolkit::generatePearChannelFile($this->channel_name, $this->summary, $this->suggestedalias, $baseUrl);
    $this->pear = opPluginChannelServerToolkit::registerPearChannel($channel);

    $this->info = $this->pear->infoFromString($this->release->package_definition);

    $this->form = new BaseForm();
    $this->depForm = new opPluginReleaseEditOpenPNEDepsForm(array(
      'le' => $this->release->op_version_le_string,
      'ge' => $this->release->op_version_ge_string,
    ));
  }

  public function executeReleaseAddOpenPNEDeps(sfWebRequest $request)
  {
    $this->depForm = new opPluginReleaseEditOpenPNEDepsForm(array(), array(
      'release' => $this->release,
    ));
    $this->depForm->bind($request['release_dep']);
    if ($this->depForm->isValid())
    {
      $this->depForm->save();
      $this->getUser()->setFlash('notice', 'Updated target OpenPNE version.');
    }
    else
    {
      $this->getUser()->setFlash('error', 'Failed to update target OpenPNE version.');
    }

    $this->redirect('release_detail', $this->release);
  }

  public function executeReleaseList(sfWebRequest $request)
  {
    $this->version = $request->getParameter('version', null);
    $this->pager = Doctrine::getTable('PluginRelease')
      ->getPager($this->package->id, $request['page'], 20, $this->version);
  }

  public function executeListRecentReleaseAtom(sfWebRequest $request)
  {
    $this->list = Doctrine::getTable('PluginRelease')->getRecentRelease(20);
    $this->forward404Unless(count($this->list));
    $this->channel_name = opPluginChannelServerToolkit::getConfig('channel_name', $this->getRequest()->getHost());
  }

  public function executeMemberList(sfWebRequest $request)
  {
    $this->pager = Doctrine::getTable('PluginMember')
      ->getPager($this->package->id, $request['page'], 20);
  }

  public function executeSearch(sfWebRequest $request)
  {
    $params = $request->getParameter('package', array());
    if (isset($request['search_query']))
    {
      $params = array_merge($params, array('name' => $request->getParameter('search_query', '')));
    }

    $this->filters = new PluginPackageFormFilter();
    $this->filters->bind($request->getParameter('plugin_package_filters', array()));

    if (!isset($this->size))
    {
      $this->size = 20;
    }

    $this->pager = new sfDoctrinePager('PluginPackage', $this->size);
    $this->pager->setQuery($this->filters->getQuery()->orderBy('created_at DESC'));
    $this->pager->setPage($request->getParameter('page', 1));
    $this->pager->init();
  }

  public function executeReleaseDelete(sfWebRequest $request)
  {
    $request->checkCSRFProtection();

    $filename = $this->release->File->original_filename;

    $tgzFilename = $this->release->File->getName();
    $tarFile = Doctrine::getTable('File')->retrieveByFilename(str_replace('tgz', 'tar', $tgzFilename));
    if ($tarFile)
    {
      $tarFile->delete();
    }

    $this->release->File->delete();
    $this->release->delete();

    $path = opPluginChannelServerToolkit::getFilePathToCache($this->release->Package->name, $this->release->version);
    @unlink($path);

    $key = sfConfig::get('op_plugin_channel_s3_key');
    $secret = sfConfig::get('op_plugin_channel_s3_secret');
    $bucket = sfConfig::get('op_plugin_channel_s3_bucket');

    if ($key && $secret && $bucket)
    {
      opPluginChannelServerToolkit::deleteFileFromS3($key, $secret, $bucket, str_replace('.tgz', '.tar', $filename));
    }

    $this->clearOutputCacheDirectory();

    $this->getUser()->setFlash('notice', 'The release is removed successfully.');

    $this->redirect('package_home', $this->release->Package);
  }

  public function executeListMember(sfWebRequest $request)
  {
    if (!$this->member)
    {
      if (isset($request['id']))
      {
        $this->member = Doctrine::getTable('Member')->find($request['id']);
      }
      else
      {
        $this->member = $this->getUser()->getMember();
      }
    }

    $this->forward404Unless($this->member->id);

    if ($this->member->id !== $this->getUser()->getMemberId())
    {
      sfConfig::set('sf_nav_type', 'friend');
      sfConfig::set('sf_nav_id', $this->member->id);
    }

    $this->crownIds = array();
    foreach (Doctrine::getTable('PluginMember')->getLeadPlugins($this->member->id) as $v)
    {
      $this->crownIds[] = $v->id;
    }

    $this->pager = Doctrine::getTable('PluginPackage')->getMemberPluginPager($this->member->id, $request->getParameter('page', 1), 20);
  }

  public function executeListRecentRelease(sfWebRequest $request)
  {
    $this->pager = Doctrine::getTable('PluginRelease')
      ->getRecentPager($request['page'], 20);

    $this->forward404Unless(count($this->pager));
  }

  protected function clearOutputCacheDirectory()
  {
    $directory = $this->getContext()->getConfiguration()->getPluginConfiguration('opPluginChannelServerPlugin')->getCacheDir();
    $this->clearDirectoryWithoutGitIgnore($directory);
  }

  protected function clearDirectoryWithoutGitIgnore($directory)
  {
    // ported from sfToolkit::clearDirectory()
    if (!is_dir($directory))
    {
      return;
    }

    // open a file point to the cache dir
    $fp = opendir($directory);

    // ignore names
    $ignore = array('.', '..', 'CVS', '.svn', '.gitignore');

    while (($file = readdir($fp)) !== false)
    {
      if (!in_array($file, $ignore))
      {
        if (is_link($directory.'/'.$file))
        {
          // delete symlink
          unlink($directory.'/'.$file);
        }
        else if (is_dir($directory.'/'.$file))
        {
          // recurse through directory
          $this->clearDirectoryWithoutGitIgnore($directory.'/'.$file);

          // delete the directory
          rmdir($directory.'/'.$file);
        }
        else
        {
          // delete the file
          unlink($directory.'/'.$file);
        }
      }
    }

    // close file pointer
    closedir($fp);
  }
}
