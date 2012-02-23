
	/*
	 *
	 * Updated On 2010/09/06 By Suresh Shinde
	 *
	 *
	 */

	var dialog		= window.parent;
	var oEditor		= dialog.InnerDialogLoaded() ;
	var FCK			= oEditor.FCK ;
	var FCKLang		= oEditor.FCKLang ;
	var FCKConfig	= oEditor.FCKConfig ;
	var FCKTools	= oEditor.FCKTools ;

	var ePreview ;

	var FlowPlayerPath = FCKConfig.BasePath+"plugins/FlowPlayer/";
	var FCKFlashExt	= ".(flv)$" ;

	var oUploadAllowedExtRegex	= new RegExp( FCKConfig.FlashUploadAllowedExtensions, 'i' ) ;
	var oUploadDeniedExtRegex	= new RegExp( FCKConfig.FlashUploadDeniedExtensions, 'i' ) ;

	var oUploadFlashExtRegex	= new RegExp( FCKFlashExt, 'i' ) ;

	var FlowPlayerId = Math.floor(Math.random()*1001);


	var getFlowPlayerId = function () { return FlowPlayerId;};

	var isFlashObject = function () { return (oUploadFlashExtRegex.test( GetE('txtUrl').value )) ? true : false;};



	// Get the selected flash (if available).
	var oFakeImage = dialog.Selection.GetSelectedElement() ;
	var oFlash ;

	//#### Dialog Tabs

	// Set the dialog tabs.
	dialog.AddTab( 'Info', oEditor.FCKLang.DlgInfoTab ) ;

	if ( FCKConfig.FlashUpload )
		dialog.AddTab( 'Upload', FCKLang.DlgLnkUpload ) ;

	if ( !FCKConfig.FlashDlgHideAdvanced )
		dialog.AddTab( 'Advanced', oEditor.FCKLang.DlgAdvancedTag ) ;

	// Function called when a dialog tag is selected.
	function OnDialogTabChange( tabCode )
	{
		ShowE('divInfo'		, ( tabCode == 'Info' ) ) ;
		ShowE('divUpload'	, ( tabCode == 'Upload' ) ) ;
		ShowE('divAdvanced'	, ( tabCode == 'Advanced' ) ) ;
	}

	if ( oFakeImage )
	{
		if ( oFakeImage.tagName == 'IMG' && (oFakeImage.getAttribute('_fckflash') || oFakeImage.className=="FCK__Flash" ))
			oFlash = FCK.GetRealElement( oFakeImage ) ;
		else
			oFakeImage = null ;
	}

	window.onload = function()
	{
		// Translate the dialog box texts.
		oEditor.FCKLanguageManager.TranslatePage(document) ;

		// Load the selected element information (if any).
		LoadSelection() ;

		// Show/Hide the "Browse Server" button.
		GetE('tdBrowse').style.display = FCKConfig.FlashBrowser	? '' : 'none' ;

		// Set the actual uploader URL.
		if ( FCKConfig.FlashUpload )
			GetE('frmUpload').action = FCKConfig.FlashUploadURL ;

		dialog.SetAutoSize( true ) ;

		// Activate the "OK" button.
		dialog.SetOkButton( true ) ;

		SelectField( 'txtUrl' ) ;
	}

	function LoadSelection()
	{
		if ( ! oFlash ) return ;

		GetE('txtUrl').value    = GetAttribute( oFlash, 'src', '' ) ;
		GetE('txtWidth').value  = GetAttribute( oFlash, 'width', '' ) ;
		GetE('txtHeight').value = GetAttribute( oFlash, 'height', '' ) ;

		UpdatePreview() ;
	}

	//#### The OK button was hit.
	function Ok()
	{
		if ( GetE('txtUrl').value.length == 0 )
		{
			dialog.SetSelectedTab( 'Info' ) ;
			GetE('txtUrl').focus() ;

			alert( oEditor.FCKLang.DlgAlertUrl ) ;

			return false ;
		}

		oEditor.FCKUndo.SaveUndoStep() ;

		if ( !oFlash )
		{
			oFlash		= isFlashObject() ? FCK.EditorDocument.createElement( 'div' ): FCK.EditorDocument.createElement( 'embed' ) ;
			oFakeImage  = null ;
		}

		isFlashObject() ? UpdateObjectFlash( oFlash ) : UpdateEmbedFlash(oFlash ) ;

		if ( !oFakeImage )
		{
			oFakeImage	= oEditor.FCKDocumentProcessor_CreateFakeImage( 'FCK__Flash', oFlash ) ;
			oFakeImage.setAttribute( '_fckflash', 'true', 0 ) ;
			oFakeImage	= FCK.InsertElement( oFakeImage ) ;
		}

		oEditor.FCKEmbedAndObjectProcessor.RefreshView( oFakeImage, oFlash ) ;

		return true ;
	}

	function SetPreviewElement( previewEl )
	{
		ePreview = previewEl ;

		if (GetE('txtUrl').value.length > 0 )
			UpdatePreview() ;
	}

	function UpdatePreview()
	{
		if ( !ePreview )
			return ;
		while ( ePreview.firstChild )
			ePreview.removeChild( ePreview.firstChild ) ;
		if ( GetE('txtUrl').value.length == 0 )
			ePreview.innerHTML = '&nbsp;' ;
		else
		{
			var oDoc	= ePreview.ownerDocument || ePreview.document ;
			var e		= isFlashObject() ? getObjectFlash(oDoc) : getEmbedFlash(oDoc) ;
			ePreview.appendChild( e ) ;
		}
	}

	function BrowseServer()
	{
		OpenFileBrowser( FCKConfig.FlashBrowserURL, FCKConfig.FlashBrowserWindowWidth, FCKConfig.FlashBrowserWindowHeight ) ;
	}

	function SetUrl( url, width, height )
	{
		GetE('txtUrl').value = url ;

		if ( width )
			GetE('txtWidth').value = width ;

		if ( height )
			GetE('txtHeight').value = height ;

		if(!oEditor.FCKBrowserInfo.IsIE)
			UpdatePreview() ;

		dialog.SetSelectedTab( 'Info' ) ;
	}

	function OnUploadCompleted( errorNumber, fileUrl, fileName, customMsg )
	{
		// Remove animation
		window.parent.Throbber.Hide() ;
		GetE( 'divUpload' ).style.display  = '' ;

		switch ( errorNumber )
		{
			case 0 :	// No errors
				alert( 'Your file has been successfully uploaded' ) ;
				break ;
			case 1 :	// Custom error
				alert( customMsg ) ;
				return ;
			case 101 :	// Custom warning
				alert( customMsg ) ;
				break ;
			case 201 :
				alert( 'A file with the same name is already available. The uploaded file has been renamed to "' + fileName + '"' ) ;
				break ;
			case 202 :
				alert( 'Invalid file type' ) ;
				return ;
			case 203 :
				alert( "Security error. You probably don't have enough permissions to upload. Please check your server." ) ;
				return ;
			case 500 :
				alert( 'The connector is disabled' ) ;
				break ;
			default :
				alert( 'Error on file upload. Error number: ' + errorNumber ) ;
				return ;
		}

		SetUrl( fileUrl ) ;
		GetE('frmUpload').reset() ;
	}

	function CheckUpload()
	{
		var sFile = GetE('txtUploadFile').value ;

		if ( sFile.length == 0 )
		{
			alert( 'Please select a file to upload' ) ;
			return false ;
		}

		if ( ( FCKConfig.FlashUploadAllowedExtensions.length > 0 && !oUploadAllowedExtRegex.test( sFile ) ) ||
			( FCKConfig.FlashUploadDeniedExtensions.length > 0 && oUploadDeniedExtRegex.test( sFile ) ) )
		{
			OnUploadCompleted( 202 ) ;
			return false ;
		}

		// Show animation
		window.parent.Throbber.Show( 100 ) ;
		GetE( 'divUpload' ).style.display  = 'none' ;

		return true ;
	}

	function addFlashAttributes(e)
	{
		SetAttribute( e, 'class', "flowplayer") ;
		SetAttribute( e, 'id',"FCKFlowPlayer-"+getFlowPlayerId()) ;

		SetAttribute( e, 'type', 'application/x-shockwave-flash' ) ;
		SetAttribute( e, 'src', GetE('txtUrl').value ) ;

		SetAttribute( e, "width" , GetE('txtWidth').value ) ;
		SetAttribute( e, "height", GetE('txtHeight').value ) ;
	}

	function setFlashAttributes(e)
	{
		SetAttribute( e, 'class', "flowplayer") ;
		SetAttribute( e, 'id',"FCKFlowPlayer-"+getFlowPlayerId()) ;

		SetAttribute( e, 'type', 'application/x-shockwave-flash' ) ;
		SetAttribute( e, 'src', GetE('txtUrl').value ) ;

		SetAttribute( e, "width" , "100%" ) ;
		SetAttribute( e, "height", "100%" ) ;

		SetAttribute( e, "play", "false" ) ;
	}

	function setEmbedFlashAttributes(e)
	{
		SetAttribute( e, "play", "false" ) ;
		SetAttribute( e, "wmode", "transparent" ) ;
		SetAttribute( e, 'id',"FCKFlowPlayer-"+getFlowPlayerId()) ;
		SetAttribute( e, "loop", "false" ) ;

		SetAttribute( e, 'pluginspage'	, 'http://www.macromedia.com/go/getflashplayer' ) ;

		SetAttribute( e, 'allowfullscreen', 'true') ; // Allow Full Screen
	}

	function addFlashStyles(e)
	{
		e.style.width = GetE('txtWidth').value;
		e.style.height = GetE('txtHeight').value;
		//e.style.display = "block";
	}

	function getEmbedFlash(oDoc)
	{
		var e = oDoc.createElement( 'embed' ) ;

		// General Style
		setFlashAttributes(e) ;

		return e;
	}

	function getObjectFlash(oDoc)
	{
		var e = oDoc.createElement( 'div' ) ;

		addFlashStyles(e);

		// General Style
		setFlashAttributes(e) ;

		// Add Script
		addFlowPlayerScript(e);

		return e;
	}

	function UpdateEmbedFlash( e )
	{
		e.innerHTML = "";

		// General Style
		addFlashAttributes(e) ;

		// Embed Attributes
		setEmbedFlashAttributes(e);
	}

	function UpdateObjectFlash(e)
	{
		e.innerHTML = "";

		addFlashStyles(e);

		// General Style
		addFlashAttributes(e) ;

		// Add Script
		addFlowPlayerScript(e);
	}

	function getPlayerPath()
	{
		return FlowPlayerPath+"flowplayer-3.2.3.swf";
	}

	function includeFlowPlayerScript(e)
	{
		var oScript = document.createElement( 'script' );

		SetAttribute( oScript, 'type',"text/javascript" ) ;
		SetAttribute( oScript, 'src', FlowPlayerPath+"flowplayer-3.2.3.min.js" ) ;

		e.appendChild(oScript);

	}

	function addFlowPlayerScript(e)
	{
		includeFlowPlayerScript(e);

		var oScript = document.createElement( 'script' );
		var FlashURL = GetE('txtUrl').value;

		SetAttribute( oScript, 'type',"text/javascript" ) ;

		var txtScript = 'flowplayer("FCKFlowPlayer-'+getFlowPlayerId()+'", "'+getPlayerPath()+'",{clip:{autoPlay : true,autoBuffering : false,url : "'+FlashURL+'"},plugins:{controls:{autoHide:true,url : "flowplayer.controls-tube-3.2.2.swf"}}});';

		var scriptNode = document.createTextNode(txtScript)

		if (null == oScript.canHaveChildren || oScript.canHaveChildren)
			oScript.appendChild(scriptNode);
		else
			oScript.text = txtScript;

		e.appendChild(oScript);
	}
