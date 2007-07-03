<?php

include_once( 'kernel/classes/ezpackage.php' );
include_once( 'kernel/common/template.php' );

$Module =& $Params['Module'];

if ( $Module->isCurrentAction( 'Switch' ) && $Module->hasActionParameter( 'RepositoryID' ) )
{
    return $Module->redirect( 'remotepackages', 'list', array( $Module->actionParameter( 'RepositoryID' ) ) );
}

$repositoryID =& $Params['repository'];

include_once( 'lib/ezutils/classes/ezini.php' );
$ini =& eZINI::instance( 'package.ini' );
$repositories = $ini->variable( 'RepositorySettings', 'Repositories' );

if ( !isset( $repositoryID ) )
{
    // display list of remote repositories
    $tpl =& templateInit();
    $tpl->setVariable( 'repositories', $repositories );

    $Result = array();
    $Result['content'] = $tpl->fetch( 'design:remotepackages/list_repositories.tpl' );
    $Result['path'] = array( array( 'url' => false, 'text' => 'Remote packages' ), array( 'url' => false, 'text' => 'Repository list' ) );
    $Result['left_menu'] = 'design:parts/remotepackages/menu.tpl';
    return;
}

if ( !array_key_exists( $repositoryID, $repositories ) )
{
    return $Module->handleError( EZ_ERROR_KERNEL_NOT_FOUND, 'kernel' );
}

$indexURL = $repositories[$repositoryID];
include_once( eZExtension::baseDirectory() . '/remotepackages/classes/ezremotepackagerepository.php' );
$repo = new eZRemotePackageRepository( $indexURL );
$list = false;
$code = $repo->retrievePackagesList( $list );

if ( $Module->isCurrentAction( 'Import' ) && $Module->hasActionParameter( 'PackageNameArray' ) )
{
    $packageNames = $Module->actionParameter( 'PackageNameArray' );

    foreach ( $packageNames as $packageName => $versions )
    {
        $taken_version = null;
        foreach ( $versions as $version => $value )
        {
            if ( version_compare( $taken_version, $version ) == -1 )
            {
                $taken_version = $version;
            }
        }
        eZDebug::writeDebug( 'importing package: ' . $packageName . ' ' . $taken_version );
        $package = $repo->downloadAndImportPackage( $packageName, $taken_version, true );
        if ( is_object( $package ) )
        {
                $result = $repo->downloadDependantPackages( $package );
        }
    }
}
$locales = array();
foreach ( array_keys( $list ) as $key )
{
    $localPackage = eZPackage::fetch( $list[$key]->attribute( 'name' ) );
    if ( is_object( $localPackage ) )
    {
        $locales[$localPackage->attribute( 'name' )] = $localPackage;
    }
}

$tpl =& templateInit();
$tpl->setVariable( 'selected_repository', $repositoryID );
$tpl->setVariable( 'repositories', $repositories );
$tpl->setVariable( 'error', $code );

$tpl->setVariable( 'list', $list );
$tpl->setVariable( 'local_list', $locales );

$typeList = eZPackage::typeList();
$tpl->setVariable( 'type_list', $typeList );

$Result = array();
$Result['content'] = $tpl->fetch( 'design:remotepackages/list.tpl' );
$Result['path'] = array( array( 'url' => false, 'text' => 'Remote packages' ), array( 'url' => false, 'text' => 'List' ), array( 'url' => false, 'text' => $repositoryID ) );
$Result['left_menu'] = 'design:parts/remotepackages/menu.tpl';