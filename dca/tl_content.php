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
 * Add palettes to tl_content
 */

$GLOBALS['TL_DCA']['tl_content']['palettes']['gallery_creator_frontend_upload'] = 'name,type,headline;{miscellaneous_legend};{protected_legend:hide},protected;{expert_legend:hide},align,space,cssID';

?>