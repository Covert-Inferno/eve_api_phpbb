<?php

namespace	omni\eveapi\user;

class	user
{
				/* @var \phpbb\config\config */
				protected	$config;

				/* @var \phpbb\controller\helper */
				protected	$helper;

				/**
					* Constructor
					*
					* @param \phpbb\config\config		$config
					* @param \phpbb\controller\helper	$helper
					*/
				public	function	__construct(	\phpbb\config\config	$config,	\phpbb\controller\helper	$helper	)
				{
								$this->config	=	$config;
								$this->helper	=	$helper;
				}

				public	function	login()
				{
								if(!$user->data['is_registered'])
								{
												if ($user->data['user_id'] == ANONYMOUS)
												{
																$this->login_box($phpbb_root_path . 'eveapi_update.' . $phpEx, 'Please fill in your Charactername, Password and working EVE API information to reactivate your account.', '', false, true, true);
												}
								}

								header('Location: ' . $phpbb_root_path . 'index.' . $phpEx);
				}
}
