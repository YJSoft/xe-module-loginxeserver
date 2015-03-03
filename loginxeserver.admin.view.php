<?php
class loginxeserverAdminView extends loginxeserver
{
  function init()
  {
    $this->setTemplatePath($this->module_path . 'tpl');
    $this->setTemplateFile(strtolower(str_replace('dispLoginxeserverAdmin', '', $this->act)));
  }

  function dispLoginxeserverAdminConfig()
  {
    $oLoginXEServerModel = getModel('loginxeserver');
    $module_config = $oLoginXEServerModel->getConfig();

    Context::set('module_config', $module_config);
  }
}
