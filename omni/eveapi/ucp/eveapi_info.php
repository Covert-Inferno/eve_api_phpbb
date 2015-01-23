<?php

class ucp_eveapi_info
{
	function module()
	{
		return array(
			'filename'	=> 'ucp_eveapi',
			'title'		=> 'UCP_EVEAPI',
			'version'	=> '0.0.1',
			'modes'		=> array(
				'register_ts'	=> array('title' => 'UCP_EVEAPI_TS', 'auth' => 'acl_u_teamspeak', 'cat' => array('UCP_EVE')),
				'register_jabber'	=> array('title' => 'UCP_EVEAPI_JABBER', 'auth' => 'acl_u_jabber', 'cat' => array('UCP_EVE')),
				'register_account'	=> array('title' => 'UCP_EVEAPI_ACCOUNT', 'auth' => '', 'cat' => array('UCP_EVE')),
			),
		);
	}

	function install()
	{
	}

	function uninstall()
	{
	}
}

?>