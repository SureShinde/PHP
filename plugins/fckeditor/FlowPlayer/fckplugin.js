	// Register the related commands.
	var dialogPath = FCKConfig.PluginsPath + 'FlowPlayer/FlowPlayer.html';
	var flashDialogCmd = new FCKDialogCommand( FCKLang["DlgFlowPlayerTitle"], FCKLang["DlgFlowPlayerTitle"], dialogPath, 480, 470 );
	FCKCommands.RegisterCommand( 'FlowPlayer', flashDialogCmd ) ;

	// Create the Flash toolbar button.
	var oFlashItem		= new FCKToolbarButton( 'FlowPlayer', FCKLang["DlgFlowPlayerTitle"]) ;
	oFlashItem.IconPath	= FCKConfig.PluginsPath + 'FlowPlayer/FlowPlayer.png' ;

	FCKToolbarItems.RegisterItem( 'FlowPlayer', oFlashItem ) ;
	// 'FlowPlayer' is the name used in the Toolbar config.
