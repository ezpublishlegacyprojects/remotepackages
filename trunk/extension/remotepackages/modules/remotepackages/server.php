<?php
include_once( "lib/ezxml/classes/ezxml.php" );
include_once( "lib/ezfile/classes/ezdir.php" );
include_once( "kernel/classes/ezpackage.php" );

$packageini = eZINI::instance( 'package.ini');

$doc = new eZDOMDocument( "packages" );
#$doc->setStylesheet( $base . '/tm/download/trados/ez-gui.xslt' );
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