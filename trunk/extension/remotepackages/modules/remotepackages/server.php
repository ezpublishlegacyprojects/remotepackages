<?php
include_once( "lib/ezxml/classes/ezxml.php" );
include_once( "lib/ezfile/classes/ezdir.php" );
include_once( "kernel/classes/ezpackage.php" );
include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );

if ( isset( $Params['Repository'] ) and $Params['Repository'] == 'repository.xsl')
{
    eZFile::download( eZExtension::baseDirectory() . '/remotepackages/repository.xsl' );
}
if ( isset( $Params['Repository'] ) and $Params['Repository'] == 'repositories.xsl')
{
    eZFile::download( eZExtension::baseDirectory() . '/remotepackages/repositories.xsl' );
}

// AUTH WORKAROUND for mod_fgicd
// to be removed later when mod_fcgid is fixed
// RewriteRule .* /index.php [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L]
function fixAuthFCGID()
{
	if ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) )
    {
        $ha = base64_decode( substr($_SERVER['HTTP_AUTHORIZATION'],6 ) );
        list( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) = explode( ':', $ha );
    }
}
function doHTTPAUTH()
{
	// Strip away "domainname\" from a possible "domainname\password" string.
    if ( preg_match( "#(.*)\\\\(.*)$#", $_SERVER['PHP_AUTH_USER'], $matches ) )
    {
        $_SERVER['PHP_AUTH_USER'] = $matches[2];
    }

    $user = false;
    $ini = eZINI::instance();

    eZDebug::writeDebug( $_SERVER['PHP_AUTH_USER'], "HTTP USER");
    eZDebug::writeDebug( $_SERVER['PHP_AUTH_PW'], "HTTP PASS");
    if ( isset( $_SERVER['PHP_AUTH_USER'] ) && isset( $_SERVER['PHP_AUTH_PW'] ) )
    {
        include_once( 'kernel/classes/datatypes/ezuser/ezuserloginhandler.php' );

        if ( $ini->hasVariable( 'UserSettings', 'LoginHandler' ) )
        {
            $loginHandlers = $ini->variable( 'UserSettings', 'LoginHandler' );
        }
        else
        {
            $loginHandlers = array( 'standard' );
        }

        foreach ( array_keys ( $loginHandlers ) as $key )
        {
            $loginHandler = $loginHandlers[$key];
            $userClass =& eZUserLoginHandler::instance( $loginHandler );
            $user = $userClass->loginUser( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );
            if ( get_class( $user ) == 'ezuser' )
            break;
        }
    }
    if ( !isset( $user ) )
    {
        $user = eZUser::currentUser();
    }
}
fixAuthFCGID();
doHTTPAUTH();


define( 'REPOSITORY_AUTH_REALM', 'eZ publish Repository interface' );

$Module =& $Params['Module'];


$package = new eZPackage();
$user = eZUser::currentUser();

// Check if username & password contain someting, attempt to login.
if ( !$package->attribute( 'can_read' ) )
{
    header( 'HTTP/1.0 401 Unauthorized' );
    header( 'WWW-Authenticate: Basic realm="' . REPOSITORY_AUTH_REALM . '"' );
    eZExecution::cleanExit();
}

if ( !isset( $Params['Repository'] ) )
    showRepositories();

if ( isset( $Params['Repository'] ) && !isset( $Params['PackageName'] ) )
    showRepository( $Params['Repository'] );

if ( isset( $Params['Repository'] ) && isset( $Params['PackageName'] ) )
    getPackage( $Params['Repository'], $Params['PackageName'], $Params['Version'] );
    
function getPackage( $repository, $packagename, $version )
{
    $package = new eZPackage();
    if ( !$package->attribute( 'can_export' ) )
    {
        header( 'HTTP/1.0 401 Unauthorized' );
        header( 'WWW-Authenticate: Basic realm="' . REPOSITORY_AUTH_REALM . '"' );
        eZExecution::cleanExit();
    }
    $packageini = eZINI::instance( 'remotepackages.ini');
    $paths = $packageini->variable( 'RemotepackagesSettings', 'Repositories' );
    if( isset( $paths[$repository] ) )
        $path = $paths[$repository];
    else
        showRepositories();
    
    $list = eZDir::recursiveFind( $path, eZPackage::definitionFilename() );
    foreach ( $list as $item )
    {
        $dir = eZDir::dirpath( $item );
        $package = eZPackage::fetch( false, $dir );
        if ( is_a( $package , 'ezpackage' ) and $Params['PackageName'] = $package->attribute('name') )
        {

            $exportDirectory = eZPackage::temporaryExportPath();
            $exportName = $package->exportName();
            $exportPath = $exportDirectory . '/' . $exportName;
            $exportPath = $package->exportToArchive( $exportPath );

            $fileName = $exportPath;
            if ( $fileName != "" and file_exists( $fileName ) )
            {
                clearstatcache();
                $fileSize = filesize( $fileName );
                $mimeType =  'application/octet-stream';
                $originalFileName = $exportName;
                $contentLength = $fileSize;
                $fileOffset = false;
                $fileLength = false;
                if ( isset( $_SERVER['HTTP_RANGE'] ) )
                {
                    $httpRange = trim( $_SERVER['HTTP_RANGE'] );
                    if ( preg_match( "/^bytes=([0-9]+)-$/", $httpRange, $matches ) )
                    {
                        $fileOffset = $matches[1];
                        header( "Content-Range: bytes $fileOffset-" . $fileSize - 1 . "/$fileSize" );
                        header( "HTTP/1.1 206 Partial content" );
                        $contentLength -= $fileOffset;
                    }
                }

                header( "Pragma: " );
                header( "Cache-Control: " );
                header( "Content-Length: $contentLength" );
                header( "Content-Type: $mimeType" );
                header( "X-Powered-By: eZ publish" );
                header( "Content-disposition: attachment; filename=$originalFileName" );
                header( "Content-Transfer-Encoding: binary" );
                header( "Accept-Ranges: bytes" );

                $fh = fopen( $fileName, "rb" );
                if ( $fileOffset )
                {
                    eZDebug::writeDebug( $fileOffset, "seeking to fileoffset" );
                    fseek( $fh, $fileOffset );
                }

                ob_end_clean();
                fpassthru( $fh );
                fflush( $fh );
                fclose( $fh );
                unlink( $fileName );
                eZExecution::cleanExit();
            }
            break;
        }
        unset( $package );
    }
}

function showRepository( $name )
{
    $packageini = eZINI::instance( 'remotepackages.ini');
    $paths = $packageini->variable( 'RemotepackagesSettings', 'Repositories' );
    if( isset( $paths[$name] ) )
        $path = $paths[$name];
    else
        showRepositories();
    
    $doc = new eZDOMDocument( "packages" );
    $doc->setStylesheet( "http://" . eZSys::hostname() . eZSys::indexDir() . eZSys::indexFile() . '/remotepackages/server/repository.xsl' );
    $doc->setDocTypeDefinition( "http://" . eZSys::hostname() . eZSys::indexDir() . eZSys::indexFile() . '/'. eZExtension::baseDirectory() . '/remotepackes/ezpackage.dtd', "-//xrow GbR//DTD ". "repository" . " eZ Package Repository V 1.0//EN" );
    $root =& $doc->createElementNode( "repository" );

    $root->appendAttribute( eZDOMDocument::createAttributeNode( 'repository-id', $name ) );
    $root->appendAttribute( eZDOMDocument::createAttributeNode( 'repository-url', "http://" . eZSys::hostname() . eZSys::indexDir() . eZSys::indexFile() . "/remotepackages/server/" . $name ) );

    $doc->setRoot( $root );

    $list = eZDir::recursiveFind( $path, eZPackage::definitionFilename() );

    foreach ( $list as $item )
    {
        $dir = eZDir::dirpath( $item );
        /*
        $dir_array = split( "/", $dir );
        $name = array_pop( $dir_array );
        $package = eZPackage::fetch( $name, join( '/', $dir_array ) );
        */
        $package = eZPackage::fetch( false, $dir );
        if ( is_a( $package , 'ezpackage' ) )
        {
            $xml = new eZXML();
            $packagedoc = $xml->domTree( file_get_contents( $item ) );
            $packageroot = $packagedoc->get_root();
            $removals = array( "files", "settings", "simple-files", "install", "uninstall" );
            foreach ( array_keys( $packageroot->Children ) as $key )
            {
                if ( in_array( $packageroot->Children[$key]->name(), $removals ) )
                {
                    $packageroot->removeChild( $packageroot->Children[$key] );
                }
            }
            $root->appendChild( $packageroot->clone() );
        }
    }

    sendXML( $doc->toString(), 'index.xml' );
}

function showRepositories()
{
    $packageini = eZINI::instance( 'remotepackages.ini');
    $doc = new eZDOMDocument( "repositories" );
    $doc->setStylesheet( "http://" . eZSys::hostname() . eZSys::indexDir() . eZSys::indexFile() . '/remotepackages/server/repositories.xsl' );
    #$doc->setDocTypeDefinition( "http://" . eZSys::hostname() . eZSys::indexDir() . eZSys::indexFile() . '/'. eZExtension::baseDirectory() . '/remotepackes/ezpackage.dtd', "-//xrow GbR//DTD ". "repository" . " eZ Package Repository V 1.0//EN" );
    $root =& $doc->createElementNode( "repositories" );
    #vendor should be part of a package
    $root->appendAttribute( eZDOMDocument::createAttributeNode( 'server-id', $packageini->variable( 'RemotepackagesSettings', 'ServerID' ) ) );
    $root->appendAttribute( eZDOMDocument::createAttributeNode( 'server-url', "http://" . eZSys::hostname() . eZSys::indexDir() . eZSys::indexFile() . "/remotepackages/server" ) );

    $doc->setRoot( $root );

    foreach ( $packageini->variable( 'RemotepackagesSettings', 'Repositories' ) as $key => $item )
    {
        $repository =& $doc->createElementNode( "repository" );
        $repository->appendAttribute( eZDOMDocument::createAttributeNode( 'repository-id', $key ) );
        $root->appendChild( $repository->clone() );
    }
    sendXML( $doc->toString(), 'index.xml' );
}

function sendXML( $xml, $filename = "index.xml" )
{
    header( "Content-Type: text/xml; charset=\"UTF-8\"" );
    header( "Content-Length: " . strlen( $xml ) );
    header( 'Content-Disposition: inline; filename="'.$filename.'"' );
    header( 'Content-Transfer-Encoding: binary' );
    header( 'Accept-Ranges: bytes' );

    while ( @ob_end_flush() );

    echo $xml;

    eZExecution::cleanExit();
}
?>