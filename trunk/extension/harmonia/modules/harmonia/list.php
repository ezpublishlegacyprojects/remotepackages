<?php

include_once( 'kernel/classes/ezpackage.php' );
include_once( 'kernel/common/template.php' );

$Module =& $Params['Module'];

if ( $Module->isCurrentAction( 'Switch' ) && $Module->hasActionParameter( 'RepositoryID' ) )
{
    return $Module->redirect( 'harmonia', 'list', array( $Module->actionParameter( 'RepositoryID' ) ) );
}

$repositoryID =& $Params['repository'];

include_once( 'lib/ezutils/classes/ezini.php' );
$ini =& eZINI::instance( 'harmonia.ini' );
$repositories = $ini->variable( 'RepositorySettings', 'Repositories' );

if ( !isset( $repositoryID ) )
{
    // display list of remote repositories
    $tpl =& templateInit();
    $tpl->setVariable( 'repositories', $repositories );

    $Result = array();
    $Result['content'] = $tpl->fetch( 'design:harmonia/list_repositories.tpl' );
    $Result['path'] = array( array( 'url' => false, 'text' => 'Harmonia' ), array( 'url' => false, 'text' => 'Repository list' ) );
    $Result['left_menu'] = 'design:parts/harmonia/menu.tpl';
    return;
}

if ( !array_key_exists( $repositoryID, $repositories ) )
{
    return $Module->handleError( EZ_ERROR_KERNEL_NOT_FOUND, 'kernel' );
}

$indexURL = $repositories[$repositoryID];
include_once( 'extension/harmonia/classes/ezremotepackagerepository.php' );
$repo = new eZRemotePackageRepository( $indexURL );
$list = false;
$code = $repo->retrievePackagesList( $list );

if ( $Module->isCurrentAction( 'Import' ) && $Module->hasActionParameter( 'PackageNameArray' ) )
{
    $packageNames = $Module->actionParameter( 'PackageNameArray' );

    foreach ( $packageNames as $packageName )
    {
        if ( ! array_key_exists( $packageName, $list ) )
        {
            // package does not exist in the repository
            continue;
        }

        eZDebug::writeDebug( 'importing package: ' . $packageName );
        $package = $repo->downloadAndImportPackage( $packageName, $list[$packageName]['url'], true );
        if ( is_object( $package ) )
        {
            $result = $repo->downloadDependantPackages( $package );
        }
    }
}

foreach ( array_keys( $list ) as $packageName )
{
    $localPackage = eZPackage::fetch( $packageName );

    $status = 0;
    if ( is_object( $localPackage ) )
    {
        $list[$packageName]['local'] = $localPackage;

        $localVersion = $localPackage->attribute( 'version-number' ) . '-' . $localPackage->attribute( 'release-number' );

        $comparison = version_compare( $localVersion, $list[$packageName]['version'] );
        $status = $comparison + 2;
    }
    else
    {
        $list[$packageName]['local'] = false;
    }

    $list[$packageName]['status'] = $status;
}

$tpl =& templateInit();
$tpl->setVariable( 'selected_repository', $repositoryID );
$tpl->setVariable( 'repositories', $repositories );
$tpl->setVariable( 'error', $code );

$tpl->setVariable( 'list', $list );


$typeList = eZPackage::typeList();
$tpl->setVariable( 'type_list', $typeList );

$Result = array();
$Result['content'] = $tpl->fetch( 'design:harmonia/list.tpl' );
$Result['path'] = array( array( 'url' => false, 'text' => 'Harmonia' ), array( 'url' => false, 'text' => 'List' ), array( 'url' => false, 'text' => $repositoryID ) );
$Result['left_menu'] = 'design:parts/harmonia/menu.tpl';