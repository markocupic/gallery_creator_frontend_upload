<?php

/**
 * Contao Open Source CMS
 * 
 * Copyright (C) 2005-2012 Leo Feyer
 * 
 * @package GalleryCreatorFrontendUpload
 * @link    http://www.contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
       'GalleryCreator',
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Modules
	'GalleryCreator\GalleryCreatorFrontendUpload' => 'system/modules/gallery_creator_frontend_upload/modules/GalleryCreatorFrontendUpload.php'
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	//partial template		
	'ce_jumploader_view' 					=> 'system/modules/gallery_creator_frontend_upload/templates',
	'ce_gallery_creator_fe_upload_view'			=> 'system/modules/gallery_creator_frontend_upload/templates',
	'ce_gallery_creator_fe_upload_picture_list_partial' 	=> 'system/modules/gallery_creator_frontend_upload/templates',
	'ce_gallery_creator_fe_upload_album_list_partial' 	=> 'system/modules/gallery_creator_frontend_upload/templates'
));
