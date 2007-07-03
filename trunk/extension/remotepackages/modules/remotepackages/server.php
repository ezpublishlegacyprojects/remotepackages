<?php
include_once( "lib/ezxml/classes/ezxml.php" );
include_once( "lib/ezfile/classes/ezdir.php" );
include_once( "kernel/classes/ezpackage.php" );

$Module =& $Params['Module'];
$packageini = eZINI::instance( 'package.ini');
if ( isset( $Params['PackageName'] ) and $Params['PackageName'] == 'repository.xsl')
{
    eZFile::download( eZExtension::baseDirectory() . '/remotepackages/repository.xsl' );
}
if ( isset( $Params['PackageName'] ) and $Params['PackageName'] != 'index.xml' )
{
    $list = eZDir::recursiveFind( $packageini->variable( 'RepositorySettings', 'LocalRepositoryPath' ), eZPackage::definitionFilename() );
    foreach ( $list as $item )
    {
        $dir = eZDir::dirpath( $item );
        $package = eZPackage::fetch( false, $dir );
        if ( is_a( $package , 'ezpackage' ) and $Params['PackageName'] = $package->attribute('name') )
        {
            if ( !$package->attribute( 'can_export' ) )
                return $Module->handleError( EZ_ERROR_KERNEL_ACCESS_DENIED, 'kernel' );


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
    if ( !$package )
    {
        return $Module->handleError( EZ_ERROR_KERNEL_NOT_AVAILABLE, 'kernel' );
    }
}


$doc = new eZDOMDocument( "packages" );
$doc->setStylesheet( "http://" . eZSys::hostname() . eZSys::indexDir() . eZSys::indexFile() . '/remotepackages/server/repository.xsl' );
$doc->setDocTypeDefinition( "http://" . eZSys::hostname() . eZSys::indexDir() . eZSys::indexFile() . '/'. eZExtension::baseDirectory() . '/remotepackes/ezpackage.dtd', "-//xrow GbR//DTD ". "repository" . " eZ Package Repository V 1.0//EN" );
$root =& $doc->createElementNode( "repository" );
#vendor should be part of a package
$root->appendAttribute( eZDOMDocument::createAttributeNode( 'repository-id', $packageini->variable( 'RepositorySettings', 'LocalRepositoryID' ) ) );
$root->appendAttribute( eZDOMDocument::createAttributeNode( 'repository-url', "http://" . eZSys::hostname() . eZSys::indexDir() . eZSys::indexFile() . "/remotepackages/server" ) );

$doc->setRoot( $root );

$list = eZDir::recursiveFind( $packageini->variable( 'RepositorySettings', 'LocalRepositoryPath' ), eZPackage::definitionFilename() );

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
            
$load = $doc->toString();

header( "Content-Type: text/xml; charset=\"UTF-8\"" );
header( "Content-Length: " . strlen( $load ) );
header( 'Content-Disposition: inline; filename="index.xml"' );
header( 'Content-Transfer-Encoding: binary' );
header( 'Accept-Ranges: bytes' );

while ( @ob_end_flush() );

echo $doc->toString();

eZExecution::cleanExit();
?>