<?php
class loginxeserverAdminController extends loginxeserver
{
  function init()
  {
  }
  function procLoginxeserverAdminInsertConfig()
  {
    $oModuleController = getController('module');

    $config = Context::getRequestVars();

    if(substr($config->def_url,-1)!='/')
    {
      $config->def_url .= '/';
    }

    if(substr($config->githubdef_url,-1)=='/')
    {
      $config->githubdef_url = substr($config->githubdef_url,0,strlen($config->githubdef_url)-1);
    }

    $oModuleController->updateModuleConfig('loginxeserver', $config);


    $this->setMessage('success_updated');
    $this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispLoginxeserverAdminConfig'));
  }
}
