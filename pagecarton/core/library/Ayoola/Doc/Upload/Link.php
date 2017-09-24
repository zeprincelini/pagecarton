<?php
/**
 * PageCarton Content Management System
 *
 * LICENSE
 *
 * @category   PageCarton CMS
 * @package    Ayoola_Doc_Upload_Link
 * @copyright  Copyright (c) 2011-2016 PageCarton (http://www.pagecarton.com)
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @version    $Id: Link.php 4.17.2012 7.55am ayoola $
 */

/**
 * @see Ayoola_Doc_Upload_Exception   
 */
 
require_once 'Ayoola/Doc/Exception.php';


/**
 * @category   PageCarton CMS
 * @package    Ayoola_Doc_Upload_Link
 * @copyright  Copyright (c) 2011-2016 PageCarton (http://www.pagecarton.com)  
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

class Ayoola_Doc_Upload_Link extends Ayoola_Doc_Upload_Abstract
{
	
    /**
     * Whether class is playable or not
     *
     * @var boolean
     */
	protected static $_playable = true;
	
    /**
     * Access level for player
     *
     * @var boolean
     */
	protected static $_accessLevel = 0;
		
    /**
     * Does the class process
     * 
     */
	public function init()
    {
		try
		{
			$docSettings = Ayoola_Doc_Settings::getSettings( 'Documents' );
		//		self::v( $docSettings ); 
			
			//	everyone must have a viewer 
			@$docSettings['allowed_viewers'] = @$docSettings['allowed_viewers'] ? : array();
			@$docSettings['allowed_viewers'][] = 98;	// allow us to user domain owners
			if( ! Ayoola_Abstract_Table::hasPriviledge( @$docSettings['allowed_viewers'] ) )
			{ 
			//	$message = 'You are not allowed to use the file manager.';
				$message = '';
				$this->_objectData['badnews'][1] = 'You are not allowed to use the file manager.';
				$this->setViewContent( $message );
			//	throw new Ayoola_Doc_Upload_Exception( $message );
				return false;
			}
			$plainUrl = @$_REQUEST['image_url'] ? : ( $this->getParameter( 'suggested_url' ) ? : $this->getParameter( 'image_url' ) );
			$imageUrl = $plainUrl;    
			if( Ayoola_Application::getUrlPrefix() && $plainUrl[0] === '/' )
			{
				$imageUrl = Ayoola_Application::getUrlPrefix() . $plainUrl;
			}
	//		var_export( $plainUrl ); 
	//		var_export( $imageUrl ); 
			switch( array_pop( explode( '.', strtolower( $imageUrl ) ) ) )
			{
				case 'jpg':
				case 'jpeg':
				case 'png':  
				case 'gif':
					//	var_export( Application_Slideshow_Abstract::getImageInfo( $imageUrl ) );
					if( $imageUrl AND $imageInfo = Application_Slideshow_Abstract::getImageInfo( $imageUrl ) )
					{
					//	var_export( $imageInfo );
						if( ! empty( $imageInfo['width'] ) && ! empty( $imageInfo['height'] ) )
						{ 
							$imageInfo['image_preview'] = $imageUrl; 
							$imageInfo['suggested_url'] = $plainUrl; 
							if( ! empty( $_REQUEST['crop'] ) )
							{
								 $imageInfo['crop'] = true; 
							}
						//	if( isset( $_REQUEST['preview_text'] ) )
							{
								 $imageInfo['preview_text'] = $this->getParameter( 'preview_text' ) . ' ' . @$_REQUEST['preview_text'] . ' ' . $imageInfo['width'] . ' x ' . $imageInfo['height']; 
							}
						}
						$this->setParameter( $imageInfo );
					}
				break;
				case 'ico':
					//	Add support for ico files
					$imageInfo['image_preview'] = $imageUrl; 
					$imageInfo['suggested_url'] = $plainUrl; 
					if( ! empty( $_REQUEST['crop'] ) )
					{
						 $imageInfo['crop'] = true; 
					}
					$this->setParameter( $imageInfo );
				break;
			}
	//		self::v( $this->getParameter() );
	//		var_export( $this->getParameter() );    
			$name = $this->getParameter( 'field_name' );
			$imageId = 'x' . md5( $name . microtime() );
			$js = null;
			if( ! $this->getParameter( 'ignore_width_and_height' ) )  
			{
				$js .= 'ayoola.image.maxWidth = \'' . $this->getParameter( 'width' ) . '\'; ';
				$js .= 'ayoola.image.maxHeight = \'' . $this->getParameter( 'height' ) . '\';';
			}
			$js .= 'ayoola.image.suggestedUrl = \'' . $this->getParameter( 'suggested_url' ) . '\';';
			$js .= 'ayoola.image.cropping.crop = ' . ( $this->getParameter( 'crop' ) ? 'true' : 'false' ) . ';';
				
			//	use image id to ensure only one preview change when update is made
			$js .= 'ayoola.image.imageId = \'' . $imageId . '\';'; 
//		var_export( $js );
			
			//	Make the upload link
		//	Application_Javascript::addFile( '/js/objects/spin.min.js' );
 			Application_Javascript::addCode( 
											'
												ayoola.events.add
												( 
													window, 
													"load", 
													function()
													{
														ayoola.image.setAfterStateChangeCallback( ayoola.image.setStatus );
													} 
												);
											' 
											);
			$jsSetFieldName = 'ayoola.image.fieldName=\'' . $this->getParameter( 'field_name' ) . '\'; ayoola.image.fieldNameValue=\'' . $this->getParameter( 'field_name_value' ) . '\'; ' . $js;
			$optionName = $name . '_option';
	//		$previewName = $name . '_preview';
			
			//{ accept: \'' . @$element['data-document_type'] . '/*\' }
			$jsSelectElement = ' ayoola.div.selectElement( { element: this, disableUnSelect: true, name: \'' . $optionName . '\', } ); ';
			$dropZoneName = $name . '_drop_zone';
			$previewZoneName = $imageId . '_preview_zone';
			$previewImageName = $imageId . '_preview_zone_image'; 

			//	Let the changes to the fieldName changes the preview
 			Application_Javascript::addCode( 
											'
												ayoola.events.add
												( 
													window, 
													"load", 
													function()
													{
														var a = document.getElementsByName( \'' . $name . '\' );
														var previewChanges = function( e )
														{
															var target = a[0] || ayoola.events.getTarget( e );
															//	alert( target );
															var c = document.getElementsByName( \'' . $previewImageName . '\' );
															for( var b = 0; b < c.length; b++ )
															{ 
															//	alert( target );
															//	alert( c[b] );
																c[b].src = target.value;
															}
														}
														var initFormElements = function()
														{
															for( var b = 0; b < a.length; b++ )
															{ 
															//	alert( a[b] );
															//	var f = function(){ previewChanges( a[b] ) }
														
																ayoola.events.add( a[b], "change", previewChanges );
																var d = ayoola.form.elementValueChangeCallbacks["' . $name . '"]
																ayoola.form.elementValueChangeCallbacks["' . $name . '"] = d ? d : [];
																ayoola.form.elementValueChangeCallbacks["' . $name . '"].push( function(){ previewChanges( a[b] ) } );
																
															}
														}
														initFormElements();
														ayoola.xmlHttp.setAfterStateChangeCallback( initFormElements );
													} 
												);
											' 
											);
 			
			$dropZoneJs = ' var a = document.getElementsByName(\'' . $dropZoneName . '\'); for( var b = 0; b < a.length; b++ ){ ayoola.image.setDropZone( a[b] ); a[b].style.display == \'none\' ? a[b].style.display=\'block\' : a[b].style.display=\'none\'; } ';
			$showMenuJs = ' var a = document.getElementsByName(\'' . $optionName . '\'); for( var b = 0; b < a.length; b++ ){ a[b].style.display == \'none\' ? a[b].style.display=\'inline-block\' : a[b].style.display=\'none\'; }  this.style.display=\'inline-block\';  this.innerHTML=\'Show or hide menu...\';';
		//	$link = '' . Ayoola_Application::getUrlPrefix() . '/ayoola/thirdparty/Filemanager/index.php?field_name=' . $this->getParameter( 'field_name' );
		//	var_export( $this->getParameter( 'field_name' ) );
		//	var_export( $this->getGlobalValue( $this->getParameter( 'field_name' ) ) ); 
			$uri = $plainUrl;
			if( $dedicatedUri = Ayoola_Doc::uriToDedicatedUrl( $uri ) )   
			{
				$uri = $dedicatedUri;
			}
		//	var_export( $this->getParameter( 'image_preview' ) );
		//	var_export( $uri );
			if( ! is_string( $uri ) )
			{
				$uri = null;
			}
			$html = '
				<div title="This is a live preview of the selected file." style="display:block;clear:both; text-align:center;" class="" >
					<img name="' . $previewImageName . '" src="' . 
					( ( $uri ? 
					$uri : 
					( is_string( $this->getParameter( 'field_name' ) ) ? 
					@$this->getGlobalValue( $this->getParameter( 'field_name' ) ) : null ) ? : 'http://placehold.it/' . 
					( $this->getParameter( 'width' ) ? : '300' ) . 'x' .
					( $this->getParameter( 'height' ) ? : '300' ) . '&text=' .   
					
					( 'Preview' ) . '' ) ) . '"  class="" onClick="" style="width:' . 

					$this->getParameter( 'width' ) . 'px;max-width:100%;height:' . 
					$this->getParameter( 'height' ) . '; max-height:50%;"  > 
				</div>
				<div title="Click here to select a file to upload or drag and drop a file here." style="text-align:center;" class="" name="upload_through_ajax_link">
					<div title="Select an option here" style="display:block;" >
						<span name="' . $optionName . '" onClick="' . $js . ' ayoola.div.selectElement( { element: this, disableUnSelect: false, name: \'' . $optionName . '\' } ); ' . $showMenuJs . '  " title="Show or hide menu..." class="pc-btn pc-btn-small" style="display:inline-block;" >
							' . ( $this->getParameter( 'call_to_action' ) ? : 'Browse...' ) . '  
						</span>
						<span name="' . $optionName . '" onClick="' . $js . ' ' . $jsSelectElement . ' ' . $jsSetFieldName . ' ayoola.image.clickBrowseButton( { accept: \'' . $this->getParameter( 'file_types_to_accept' ) . '\', } ); " title="Click here to upload a file" class="pc-btn pc-btn-small" style="display:none;" >
							Upload
						</span>
						<span name="' . $optionName . '" onClick="' . $js . ' ayoola.div.selectElement( { element: this, disableUnSelect: false, name: \'' . $optionName . '\' } ); ' . $dropZoneJs . ' ' . $jsSetFieldName . ' " title="Click here to select a file from the previous files on the website" class="pc-btn pc-btn-small" style="display:none;" >
							Drag N Drop
						</span>
						<span name="' . $optionName . '" onClick="' . $js . ' ayoola.div.selectElement( { element: this, disableUnSelect: false, name: \'' . $optionName . '\' } );   var a = document.getElementsByName( \'' . $name . '\' ); for( var b = 0; b < a.length; b++ ){var c = a[b].type; if( c == \'text\' ){ a[b].type = \'hidden\' }else{ a[b].type = \'text\'; }  a[b].style.display=\'block\';  a[b].focus();  a[b].click(); } " title="Click here to a URL link to the file" class=" pc-btn pc-btn-small" style="display:none;" >
							URL
						</span>
					</div>
					<div name="' . $dropZoneName . '" style="max-width:100%;display:none;text-align:center;" title="Drag and drop files here" class="boxednews centerednews badnews">	
						<img src="' . Ayoola_Application::getUrlPrefix() . '/public/drag_and_drop.png?document_time=1" onClick="" style="max-height:7em;max-width:100%;"  >
					</div>
					<div name="' . $previewZoneName . '" style="max-width:100%;" title="Upload previews" class="">	
					</div>
				</div>
			';
			$this->setViewContent( $html );
		}
		catch( Application_Slideshow_Exception $e )
		{ 
			return false; 
		}
	}
	
	// END OF CLASS
}