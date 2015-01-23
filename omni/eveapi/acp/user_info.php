<?php

namespace	omni\eveapi\acp;

class	user_info
{
				function	module()
				{
								return	array(
												'filename'	=>	'\omni\eveapi\acp\user',
												'title'				=>	'ACP_EVEAPI_USER_MANAGEMENT',
												'version'		=>	'0.0.1',
												'modes'				=>	array(
																'eveapi_ts'						=>	array(
																				'title'	=>	'UCP_EVEAPI_TS',
																				'auth'		=>	'acl_a_user',
																				'cat'			=>	array(
																								'ACP_CAT_USERS')),
																'eveapi_jabber'		=>	array(
																				'title'	=>	'UCP_EVEAPI_JABBER',
																				'auth'		=>	'acl_a_user',
																				'cat'			=>	array(
																								'ACP_CAT_USERS')),
																'eveapi_account'	=>	array(
																				'title'	=>	'UCP_EVEAPI_ACCOUNT',
																				'auth'		=>	'acl_a_user',
																				'cat'			=>	array(
																								'ACP_CAT_USERS')),
												),
								);
				}
}
