<?php
class loginxeserverView extends loginxeserver
{
  function init()
  {
	$this->setTemplatePath($this->module_path . 'tpl');
	$this->setTemplateFile(strtolower(str_replace('dispLoginxeserver', '', $this->act)));
  }

	/**
     *
     */
  function dispLoginxeserverOAuth()
  {
	//oauth display & redirect act
	//load config here and redirect to service
	//key check & domain check needed
    //needed value=service,id,key,state(client-generated),callback-url(urlencoded)
	$service = Context::get('provider');
    $id = Context::get('id');
    $key = Context::get('key');
    $state = Context::get('state');
    $_SESSION['loginxe_state'] = $state;
    $callback = urldecode(Context::get('callback'));
    $_SESSION['loginxe_callback'] = $callback;
    $oLoginXEServerModel = getModel('loginxeserver');
    $module_config = $oLoginXEServerModel->getConfig();


    if($module_config->id!=$id || $module_config->key!=$key)
    {
      Context::set('url',getNotEncodedUrl('','module','loginxeserver','act','dispLoginxeserverOAuthFinish','error','1','message','msg_invalid_request'));
      return;
    }

	if($service=='naver')
	{
      if(!isset($module_config->clientid) || $module_config->clientid=='' || !isset($module_config->clientkey) || $module_config->clientkey=='')
      {
        Context::set('url',getNotEncodedUrl('','module','loginxeserver','act','dispLoginxeserverOAuthFinish','error','1','message','loginxe_not_finished_setting'));
        return;
      }

      Context::set('url','https://nid.naver.com/oauth2.0/authorize?client_id=' . $module_config->clientid . '&response_type=code&redirect_uri=' . urlencode(getNotEncodedFullUrl('','module','loginxeserver','act','dispLoginxeserverOAuthFinish','provider','naver','callback','')) . '&state=' . $state);
      return;
	}
    elseif($service=='xe')
    {
      Context::set('url',getNotEncodedUrl('','module','loginxeserver','act','dispLoginxeserverOAuthFinish','error','1','message','loginxe_not_implemented'));
      return;
    }
    elseif($service=='github')
    {
      if(!isset($module_config->githubclientid) || $module_config->githubclientid=='' || !isset($module_config->githubclientkey) || $module_config->githubclientkey=='')
      {
        Context::set('url',getNotEncodedUrl('','module','loginxeserver','act','dispLoginxeserverOAuthFinish','error','1','message','loginxe_not_finished_setting'));
        return;
      }
      Context::set('url','https://github.com/login/oauth/authorize?client_id=' . $module_config->githubclientid . '&redirect_uri=' . urlencode(getNotEncodedFullUrl('','module','loginxeserver','act','dispLoginxeserverOAuthFinish','provider','github','callback','')) . '&state=' . $state . '&scope=user');
      //Context::set('url',getNotEncodedUrl('','module','loginxeserver','act','dispLoginxeserverOAuthFinish','error','1','message','loginxe_not_implemented'));

      return;
    }
    else
    {
      Context::set('url',getNotEncodedUrl('','module','loginxeserver','act','dispLoginxeserverOAuthFinish','error','1','message','loginxe_not_implemented'));
    }
  }

  function dispLoginxeserverOAuthFinish()
  {
	//proc oauth value and save
	//save with loginxeclient-publickey, generated key, statekey, oauth-key,oauth-secret
	//redirect to loginxeclient-returnurl with generated key and statekey
    $isError = Context::get('error');
    $message = Context::get('message');
    $service = Context::get('provider');
    $state = Context::get('state');
    $code = Context::get('code');

    if($code=='' || $state=='' || $service=='' || !isset($_SESSION['loginxe_callback']) || $_SESSION['loginxe_callback']=='')
    {
      //필요한 값이 없으므로 오류
      return new Object(-1,'msg_invalid_request');
    }

    if($isError=='1')
    {
      Context::setBrowserTitle('LoginXE Server Error');
      return new Object(-1,$message);
    }

    if($isError!="") return new Object(-1, Context::get("error_description"));
    $stored_state = $_SESSION['loginxe_state'];

    //세션변수 비교(CSRF 방지)
    if( $state != $stored_state ) {
      return new Object(-1, 'loginxesvr_invalid_state');
    }

    //ssl 연결을 지원하지 않는 경우 리턴(API 연결은 반드시 https 연결이여야 함)
    //SSL 미지원시 리턴
    if(!$this->checkOpenSSLSupport())
    {
      return new Object(-1,'loginxesvr_need_openssl');
    }

    $oLoginXEServerModel = getModel('loginxeserver');
    $module_config = $oLoginXEServerModel->getConfig();

    if($service=='naver')
    {
      //API 서버에 code와 state값을 보내 인증키를 받아 온다
      $ping_url = 'https://nid.naver.com/oauth2.0/token?client_id=' . $module_config->clientid . '&client_secret=' . $module_config->clientkey . '&grant_type=authorization_code&state=' . $state . '&code=' . $code;
      $ping_header = array();
      $ping_header['Host'] = 'nid.naver.com';
      $ping_header['Pragma'] = 'no-cache';
      $ping_header['Accept'] = '*/*';

      $request_config = array();
      $request_config['ssl_verify_peer'] = false;

      $buff = FileHandler::getRemoteResource($ping_url, null, 10, 'GET', 'application/x-www-form-urlencoded', $ping_header, array(), array(), $request_config);
      $data= json_decode($buff);
    }
    elseif($service=='github')
    {
      //API 서버에 code와 state값을 보내 인증키를 받아 온다
      $ping_url = 'https://github.com/login/oauth/access_token';
      $ping_header = array();
      $ping_header['Host'] = 'github.com';
      $ping_header['Pragma'] = 'no-cache';
      $ping_header['Accept'] = 'application/json';

      $request_config = array();
      $request_config['ssl_verify_peer'] = false;

      $buff=FileHandler::getRemoteResource($ping_url, null, 10, 'POST', 'application/x-www-form-urlencoded', $ping_header, array(), array('client_id'=>$module_config->githubclientid,'client_secret'=>$module_config->githubclientkey,'code'=>$code), $request_config);
      $data=json_decode($buff);
      debugPrint($buff);
    }
    else
    {
      return new Object(-1, 'msg_invalid_request');
    }


    Context::set('url',$_SESSION['loginxe_callback'] . '&token=' . urlencode($data->access_token) . '&state=' . $state);
  }
}