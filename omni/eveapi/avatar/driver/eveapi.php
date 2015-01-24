<?php

namespace	omni\eveapi\avatar\driver;

/**
	* Handles avatars hosted at eveonline.com
	*/
class	eveapi	extends	\phpbb\avatar\driver\driver
{
				/**
					* The URL for the eveonline service
					*/
				const	EVEAPI_URL	=	'https://image.eveonline.com/Character/';

				/**
					* @var \phpbb\mimetype\guesser
					*/
				protected	$mimetype_guesser;

				/**
					* Construct a driver object
					*
					* @param \phpbb\config\config $config phpBB configuration
					* @param string $phpbb_root_path Path to the phpBB root
					* @param string $php_ext PHP file extension
					* @param \phpbb_path_helper $path_helper phpBB path helper
					* @param \phpbb\mimetype\guesser $mimetype_guesser Mimetype guesser
					* @param \phpbb\cache\driver\driver_interface $cache Cache driver
					*/
				public	function	__construct(	\phpbb\config\config	$config,	$phpbb_root_path,	$php_ext,	\phpbb\path_helper	$path_helper,	\phpbb\mimetype\guesser	$mimetype_guesser,	\phpbb\cache\driver\driver_interface	$cache	=	null	)
				{
								$this->config											=	$config;
								$this->phpbb_root_path		=	$phpbb_root_path;
								$this->php_ext										=	$php_ext;
								$this->path_helper						=	$path_helper;
								$this->mimetype_guesser	=	$mimetype_guesser;
								$this->cache												=	$cache;
				}

				/**
					* {@inheritdoc}
					*/
				public	function	get_data(	$row,	$ignore_config	=	false	)
				{
								return	array(
												'src'				=>	$this->path_helper->get_web_root_path()	.	'download/file.'	.	$this->php_ext	.	'?avatar='	.	$row['avatar'],
												'width'		=>	$row['avatar_width'],
												'height'	=>	$row['avatar_height'],
								);
				}

				/**
					* {@inheritdoc}
					*/
				public	function	prepare_form(	$request,	$template,	$user,	$row,	&$error	)
				{
								if	(	!$this->can_upload()	||	$user->data['user_character_id']	==	0	)
								{
												return	false;
								}

								return	true;
				}

				/**
					* {@inheritdoc}
					*/
				public	function	process_form(	$request,	$template,	$user,	$row,	&$error	)
				{
								if	(	$user->data['user_character_id']	==	0	)
								{
												return	false;
								}

								if	(	!class_exists('fileupload')	)
								{
												include($this->phpbb_root_path	.	'includes/functions_upload.'	.	$this->php_ext);
								}

								$upload	=	new	\fileupload('AVATAR_',	$this->allowed_extensions,	100000,	64,	64,	256,	256,	(isset($this->config['mime_triggers'])	?	explode('|',	$this->config['mime_triggers'])	:	false));

								$url	=	$this->get_eveapi_url($user->data['user_character_id'],	$this->config['eveapi_portrait_size']);

								$file	=	$upload->remote_upload($url,	$this->mimetype_guesser);

								$prefix	=	$this->config['avatar_salt']	.	'_';
								$file->clean_filename('avatar',	$prefix,	$row['id']);

								$destination	=	$this->config['avatar_path'];

								// Adjust destination path (no trailing slash)
								if	(	substr($destination,	-1,	1)	==	'/'	||	substr($destination,	-1,	1)	==	'\\'	)
								{
												$destination	=	substr($destination,	0,	-1);
								}

								$destination	=	str_replace(array(
												'../',
												'..\\',
												'./',
												'.\\'),	'',	$destination);
								if	(	$destination	&&	($destination[0]	==	'/'	||	$destination[0]	==	"\\")	)
								{
												$destination	=	'';
								}

								// Move file and overwrite any existing image
								$file->move_file($destination,	true);

								if	(	sizeof($file->error)	)
								{
												$file->remove();
												$error	=	array_merge($error,	$file->error);
												return	false;
								}

								return	array(
												'avatar'								=>	$row['id']	.	'_'	.	time()	.	'.'	.	$file->get('extension'),
												'avatar_width'		=>	$file->get('width'),
												'avatar_height'	=>	$file->get('height'),
								);
				}

				/**
					* {@inheritdoc}
					*/
				public	function	get_template_name()
				{
								return	'ucp_avatar_options_eveapi.html';
				}

				/**
					* Build gravatar URL for output on page
					*
					* @param array $row User data or group data that has been cleaned with
					*        \phpbb\avatar\manager::clean_row
					* @return string EVE Online URL
					*/
				protected	function	get_eveapi_url(	$avatar,	$size	)
				{
								$url	=	self::EVEAPI_URL;
								$url	.=	$avatar	.	'_'	.	$size	.	'.jpg';

								return	$url;
				}

				/**
					* {@inheritdoc}
					*/
				public	function	delete(	$row	)
				{
								$ext						=	substr(strrchr($row['avatar'],	'.'),	1);
								$filename	=	$this->phpbb_root_path	.	$this->config['avatar_path']	.	'/'	.	$this->config['avatar_salt']	.	'_'	.	$row['id']	.	'.'	.	$ext;

								if	(	file_exists($filename)	)
								{
												@unlink($filename);
								}

								return	true;
				}

				/**
					* Check if user is able to upload an avatar
					*
					* @return bool True if user can upload, false if not
					*/
				protected	function	can_upload()
				{
								return	(file_exists($this->phpbb_root_path	.	$this->config['avatar_path'])	&&	phpbb_is_writable($this->phpbb_root_path	.	$this->config['avatar_path'])	&&	(@ini_get('file_uploads')	||	strtolower(@ini_get('file_uploads'))	==	'on'));
				}
}
