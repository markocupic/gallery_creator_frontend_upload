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
 * Run in a custom namespace, so the class can be replaced
 */
namespace GalleryCreator;


/**
 * Class GalleryCreatorFrontendUpload
 *
 * Front end module "gallery_creator_frontend_upload".
 * @copyright  Leo Feyer 2005-2012
 * @author     Leo Feyer <http://www.contao.org>
 * @package    Calendar
 */
class GalleryCreatorFrontendUpload extends \Module
{

	
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_gallery_creator_fe_upload_view';

       
	/**
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### GALLERY CREATOR FRONTEND UPLOAD ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = ampersand('contao/main.php?do=themes&table=tl_module&act=edit&id=' . $this->id);

			return $objTemplate->parse();
		}

		return parent::generate();
	}


	/**
	 * Generate the module
	 */
	protected function compile()
	{
		switch(\Input::get('mode'))
		{
			default:
				$this->Template->mainContent = $this->generateAlbumList();
				break;
			
			case 'fileupload':
                     if ($_FILES['file'])
                     {
                            $objAlb = \GalleryCreatorAlbumsModel::findById(\Input::get('id'));
                            
                            // move uploaded file in the album-directory
                            if ($arrFileupload = \GcHelpers::fileupload($objAlb->id, \Input::post('fileName')))
                            {
                                   //write the new entry in tl_gallery_creator_pictures
                                   \GcHelpers::createNewImage($objAlb->id, $arrFileupload['strFileSrc']);
                            }
                            exit;
                     }
                     if (\Input::get('id'))
                     {
                            $this->Template->referer = str_replace('?' . $_SERVER['QUERY_STRING'],'',\Environment::get('url') . \Environment::get('requestUri'));
                            $this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];
                            $this->Template->mainContent = self::generateUploader(\Input::get('id'));
                     }
                     break;
			
			case 'edit_album':

                            if (\Input::get('act') == 'imagerotate')
                            {
                                   $objImage = \GalleryCreatorPicturesModel::findByPk(\Input::get('imgId'));
                                   if ($objImage !== null)
                                   {
                                          \GcHelpers::imageRotate($objImage->path, '270');
                                   }
                                   $href = str_replace('?' . $_SERVER['QUERY_STRING'], '?mode=edit_album&id=' . \Input::get('id'), \Environment::get('url') . \Environment::get('requestUri'));
                                   $this->redirect(ampersand($href));
                            }
                            if (\Input::get('id'))
       			{
                                   $this->Template->referer = str_replace('?' . $_SERVER['QUERY_STRING'],'',\Environment::get('url') . \Environment::get('requestUri'));
                                   $this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];
                                   $this->Template->mainContent = $this->generatePictureList(\Input::get('id'));
       			}
       			break;
			
			case 'edit_picture':
				if (\Input::get('id'))
				{
                                   $objPicture = $this->Database->prepare('SELECT * FROM tl_gallery_creator_pictures WHERE id=?')->executeUncached(\Input::get('id'));
                                   $objNextItem = $this->Database->prepare('SELECT * FROM tl_gallery_creator_pictures WHERE pid=? AND published=? AND sorting>? ORDER BY sorting ASC LIMIT 0,1')->executeUncached($objPicture->pid, '1', $objPicture->sorting);
                                   $objPrevItem = $this->Database->prepare('SELECT * FROM tl_gallery_creator_pictures WHERE pid=? AND published=? AND sorting<? ORDER BY sorting DESC LIMIT 0,1')->executeUncached($objPicture->pid, '1', $objPicture->sorting);
                                   $url = str_replace('?' . $_SERVER['QUERY_STRING'],'',\Environment::get('url') . \Environment::get('requestUri'));
                                   if ($objNextItem->next())
                                   {
                                          $this->Template->nextItem = ampersand($url . sprintf('?mode=edit_picture&id=%s', $objNextItem->id));
                                   }
                                   if ($objPrevItem->next())
                                   {
                                          $this->Template->prevItem = ampersand($url . sprintf('?mode=edit_picture&id=%s', $objPrevItem->id));
                                   }
                                   
                                   $this->Template->referer = ampersand($url . sprintf('?mode=edit_album&id=%s', $objPicture->pid));
                                   $this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];
                                   $this->Template->mainContent = $this->generateEditPictureForm(\Input::get('id'));
				}
				break;
		}
	}
	
	
	
	protected function generateAlbumList()
	{
		$html = '<table>' . chr(13);
		$objAlbums = \Database::getInstance()->prepare('SELECT * FROM tl_gallery_creator_albums WHERE published=? ORDER BY sorting')->execute('1');
		$row=0;
		while ($objAlbums->next())
		{
			$template = new \FrontendTemplate('ce_gallery_creator_fe_upload_album_list_partial');
			$url = \Environment::get('url') . \Environment::get('requestUri');
		 	$template->rowClass = 'row_' . $row . (($row%2) == 0 ? ' even' : ' odd');
			$template->arrAlbum = $objAlbums->row();
			$template->urlAddImages = ampersand($url . sprintf('?mode=fileupload&id=%s', $objAlbums->id));
			$template->urlEditAlbum = ampersand($url . sprintf('?mode=edit_album&id=%s', $objAlbums->id));
			$html .= $template->parse();
			$row++;
			
		}
		$html .= chr(13) . '</table>';
		
		return $html;
	}
	
	
	
	protected function generatePictureList()
	{
		$url = str_replace('?' . $_SERVER['QUERY_STRING'],'',\Environment::get('url') . \Environment::get('requestUri'));
		$html = '<table>' . chr(13);
		$row=0;
		$objPictures = $this->Database->prepare('SELECT * FROM tl_gallery_creator_pictures WHERE published=? AND pid=? ORDER BY sorting ASC')->execute('1', \Input::get('id'));
		while ($objPictures->next())
		{
			$template = new \FrontendTemplate('ce_gallery_creator_fe_upload_picture_list_partial');
			$template->rowClass = 'row_' . $row . (($row%2) == 0 ? ' even' : ' odd');
			$template->arrImage = $objPictures->row();
			$template->urlEditImage = ampersand($url . sprintf('?mode=edit_picture&id=%s', $objPictures->id));
       		$template->href = $objPictures->path;
       		$template->caption = specialchars($objPictures->comment);
                     $template->thumb = $this->generateImage (\Image::get($objPictures->path,80,80,'center_center'), $objPictures->name);
                     $template->rotateImgSrc = 'system/modules/gallery_creator_frontend_upload/assets/images/rotate.png';
                     $template->rotateImgHref = \Environment::get('url') . \Environment::get('requestUri') . '&act=imagerotate&imgId=' . $objPictures->id;
			$html .= $template->parse();
			$row++;
		}
		$html .= '</table>';
		
		
		return $html;
	}
	
	
	
	protected function generateEditPictureForm($id)
	{
		$objPicture = \GalleryCreatorPicturesModel::findById($id);
		$this->loadDataContainer('tl_gallery_creator_pictures');
		$dca = $GLOBALS['TL_DCA']['tl_gallery_creator_pictures']['fields'];
		
		
		//add the thumbnail and the picture path to the first 2 rows
		$html = '
<tr class="row_0 even">
       <td class="label col_0 col_first">thumb</td>
       <td class="col_1 col_last"><div class="image_container"><a href="' . $objPicture->path . '" title="' . specialchars($objPicture->comment) . '" data-lightbox="lbox">' . $this->generateImage (\Image::get($objPicture->path,'',150,'proportional')) . '</a></div></td>
</tr>
<tr class="row_1 odd">
       <td class="label col_0 col_first">path</td>
       <td class="col_1 col_last">' . $objPicture->path . '</td>
</tr>';

		$row=1;
		//add the fields
		foreach($dca as $fieldname => $arrField)
		{
                     $value = \Input::post($arrField['id']) ? \Input::post($arrField['id']) : '';
                     $arrField = $this->prepareForWidget($arrField, $fieldname, $value);
            
			if ($arrField['id'] != 'title' && $arrField['id'] != 'comment' && $arrField['id'] != 'date') continue;
			if ($arrField['type'] != 'text' && $arrField['type'] != 'textarea') continue;
			
			$row++;
			// create the widget-classname
			$strClass = strtolower($arrField['type']) == 'text' ? '\FormTextField' : '';
			$strClass = strtolower($arrField['type']) == 'textarea' ? '\FormTextArea' : $strClass;
			if ($arrField['rgxp'] == 'date')
                     {
                            $strClass = '\FormCalendarField';
                     }
                     
			// instantiate the widget object
			$widget = new $strClass($arrField);
			
                     //add the name and the label to the widget object
                     $widget->name = $fieldname;
                     $widget->label = $fieldname;
                     
			// validate input
			if (\Input::post('FORM_SUBMIT') == 'tl_gallery_creator_pictures' && \Input::post($arrField['id']))
			{
                            $blnSave = true;
                            $widget->validate();
                            if ($widget->hasErrors())
                            {
                                   $blnSave = false;
                                   $widget->getErrorAsString();
                            }
                            // set the input value
                            $widget->value = \Input::post($arrField['id']);
                            if ($blnSave)
                            {
                                   //update entry
                                   $arrSet = array($arrField['id'] => $widget->value);
                            
                                   //convert date to unix timestamp
                                   if (in_array($arrField['rgxp'], array('date', 'time', 'datim')))
                                   {
                                          $objDate = new \Date(trim($widget->value), $GLOBALS['TL_CONFIG'][$arrField['rgxp'] . 'Format']);
                                          $arrSet[$arrField['id']] = $objDate->tstamp;
                                   }
                                   $this->Database->prepare('UPDATE tl_gallery_creator_pictures %s WHERE id=?')->set($arrSet)->execute(\Input::get('id'));
			       }	
			} else {
			  
                            $objPicture = \GalleryCreatorPicturesModel::findById($id);
                            // set the input value
                            $widget->value = $objPicture->{$arrField['id']};
                            
                            //convert unix timestamp to date
                            if ($widget->value != '' && in_array($arrField['rgxp'], array('date', 'time', 'datim')))
                            {
                                   $widget->value = $this->parseDate($GLOBALS['TL_CONFIG'][$arrField['rgxp'] . 'Format'], $widget->value);
                            }
			}
			$html.= $widget->parse();
		}
		
		//add the submit button
		$row++;
		$widget = new \FormSubmit();
		$widget->slabel = 'speichern';
		$widget->rowClass = 'row_' . $row . (($row%2) == 0 ? ' even' : ' odd');
		$html .= $widget->parse();
		
		//add the form 
		$form = new \FrontendTemplate('form');
		$form->action = ampersand(\Environment::get('url') . \Environment::get('requestUri'));
		$form->enctype = $hasUpload ? 'multipart/form-data' : 'application/x-www-form-urlencoded';
		$form->method = 'post';
		$form->formId = 'tl_gallery_creator_pictures';
		$form->formSubmit = 'tl_gallery_creator_pictures';
		$form->attributes = null;
		
		//add the fields to the form-template;
		$form->fields .= $html;
		
		return $form->parse();

	}
	
	
	
	
	protected static function generateUploader($albumId)
	{
		//create the partial view
		$objTemplate = new \FrontendTemplate('ce_jumploader_view');
			
		//upload url
		$url = str_replace('?' . $_SERVER['QUERY_STRING'],'',\Environment::get('url') . \Environment::get('requestUri'));
		$objTemplate->uploadUrl = ampersand($url .sprintf('?mode=fileupload&id=%s', $albumId));
       
              //security tokens
              $objTemplate->securityTokens = sprintf('PHPSESSID=%s; path=/; %s_USER_AUTH=%s; path=/;', session_id(), TL_MODE, $_COOKIE[TL_MODE . '_USER_AUTH']);
              
		//security tokens
		$objTemplate->requestToken = REQUEST_TOKEN;
		
		//languageFiles
		$language = 'de';
		$objTemplate->jumploaderLanguageFiles = \Environment::get('base') . 'system/modules/gallery_creator/assets/plugins/jumploader/lang/messages_' . $language . '.zip';
		
		//jumploader Archive
		$pathToArchive = \Environment::get('base') . 'system/modules/gallery_creator/assets/plugins/jumploader';
		$arrJumploaderArchive = array (
			sprintf('%s/mediautil_z.jar', $pathToArchive),
			sprintf('%s/sanselan_z.jar', $pathToArchive),
			sprintf('%s/jumploader_z.jar', $pathToArchive),
                     sprintf('%s/xfiledialog.jar', $pathToArchive)
		);
		$objTemplate->jumploaderArchive = implode(',', $arrJumploaderArchive);
        
              //optional jumploader adds a watermark to each uploaded image
           	if (strlen($GLOBALS['TL_CONFIG']['gc_watermark_path']))
              {
                     $objFile = \FilesModel::findById($GLOBALS['TL_CONFIG']['gc_watermark_path']);
                     if (is_object($objFile) && is_file(TL_ROOT . '/' . $objFile->path))
                     {
                            $objFile = new \File($objFile->path);
                            if ($objFile->isGdImage)
                            {
                                   $objTemplate->watermarkHalign = $GLOBALS['TL_CONFIG']['gc_watermark_halign'];
                                   $objTemplate->watermarkValign = $GLOBALS['TL_CONFIG']['gc_watermark_valign'];
                                   $objTemplate->watermarkOpacity = $GLOBALS['TL_CONFIG']['gc_watermark_opacity'];
                                   $objTemplate->watermarkSource = \Environment::get('base') . $objFile->path;
                            }
                     }
              }
       		
       	//parse the partial template
       	return $objTemplate->parse();
       }
}
