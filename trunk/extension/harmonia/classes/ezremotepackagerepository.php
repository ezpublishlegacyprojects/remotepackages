<?php

/*!
 based on code in ezstep_site_types.php
*/
class eZRemotePackageRepository
{
    function eZRemotePackageRepository( $indexURL )
    {
        $this->IndexURL = $indexURL;
    }

    function XMLIndexURL()
    {
        if ( substr( $this->IndexURL, -1, 1 ) == '/' )
        {
            $XMLIndexURL = $this->IndexURL . 'index.xml';
        }
        else
        {
            $XMLIndexURL = $this->IndexURL . '/index.xml';
        }
        return $XMLIndexURL;
    }

    /*!
     \return an array of packages, or an integer error code
     Error codes:
     1: retrieving package failed
     2: malformed index file
    */
    function retrievePackagesList( &$packageList, $types = false )
    {
        $xmlIndexURL = $this->XMLIndexURL();
        $idxFileName = $this->downloadFile( $xmlIndexURL, $this->tempDir(), 'index.xml' );

        if ( $idxFileName === false )
        {
            eZDebug::writeNotice( "Cannot download remote packages index file from '$XMLIndexURL'." );
            return 1;
        }

        include_once( 'lib/ezfile/classes/ezfile.php' );
        include_once( "lib/ezxml/classes/ezxml.php" );

        $xmlString = eZFile::getContents( $idxFileName );
        @unlink( $idxFileName );
        $xml = new eZXML();
        $domDocument = $xml->domTree( $xmlString );

        if ( !is_object( $domDocument ) )
        {
            return 2;
        }

        $root = $domDocument->root();

        if ( $root->name() != 'packages' )
        {
            return 2;
        }

        $packageList = array();
        foreach ( $root->children() as $packageNode )
        {
            if ( $packageNode->name() != 'package' )
            {
                // skip unwanted chilren
                continue;
            }

            if ( is_array( $types ) && !in_array( $packageNode->getAttribute( 'type' ), $types ) )
            {
                // skip packages of unwanted types
                continue;
            }

            $packageAttributes = $packageNode->attributeValues();
            $packageList[$packageAttributes['name']] = $packageAttributes;
        }

        return 0;
    }

    /**
     * Wrapper for standard fopen() doing error checking.
     *
     * \private
     * \static
     */
    function fopen( $fileName, $mode )
    {
        $savedTrackErrorsFlag = ini_get( 'track_errors' );
        ini_set( 'track_errors', 1 );

        if ( ( $handle = @fopen( $fileName, 'wb' ) ) === false )
            $this->FileOpenErrorMsg = $php_errormsg;

        ini_set( 'track_errors', $savedTrackErrorsFlag );

        return $handle;
    }

    /**
     * Returns temporary directory used to download files to.
     *
     * \static
     */
    function tempDir()
    {
        return eZDir::path( array( eZSys::cacheDirectory(),
                                    'packages' ) );
    }

    /**
     * Downloads file.
     *
     * Sets $this->ErrorMsg in case of an error.
     *
     * \private
     * \param $url            URL.
     * \param $outDir         Directory where to put downloaded file to.
     * \param $forcedFileName Force saving downloaded file under this name.
     * \return false on error, path to downloaded package otherwise.
     */
    function downloadFile( $url, $outDir, $forcedFileName = false )
    {
        $fileName = $outDir . "/" . ( $forcedFileName ? $forcedFileName : basename( $url ) );

        /* Do nothing if the file already exists (no need to download).
        if ( file_exists( $fileName ) )
        {
            eZDebug::writeNotice( "Skipping download to '$fileName': file already exists." );
            return $fileName;
        }
        */
        eZDebug::writeNotice( "Downloading file '$fileName' from $url" );

        // Create the out directory if not exists.
        if ( !file_exists( $outDir ) )
            eZDir::mkdir( $outDir, eZDir::directoryPermission(), true );

        // First try CURL
        if ( extension_loaded( 'curl' ) )
        {
            $ch = curl_init( $url );
            $fp = eZRemotePackageRepository::fopen( $fileName, 'wb' );

            if ( $fp === false )
            {
                $this->ErrorMsg = ezi18n( 'design/standard/setup/init', 'Cannot write to file' ) .
                    ': ' . $this->FileOpenErrorMsg;
                return false;
            }

            curl_setopt( $ch, CURLOPT_FILE, $fp );
            curl_setopt( $ch, CURLOPT_HEADER, 0 );
            curl_setopt( $ch, CURLOPT_FAILONERROR, 1 );
            curl_setopt( $ch, CURLOPT_USERAGENT, 'eZ publish Harmonia package manager add-on' );
            // Get proxy
            $ini =& eZINI::instance();
            $proxy = $ini->hasVariable( 'ProxySettings', 'ProxyServer' ) ? $ini->variable( 'ProxySettings', 'ProxyServer' ) : false;
            if ( $proxy )
            {
                curl_setopt ( $ch, CURLOPT_PROXY , $proxy );
                $userName = $ini->hasVariable( 'ProxySettings', 'User' ) ? $ini->variable( 'ProxySettings', 'User' ) : false;
                $password = $ini->hasVariable( 'ProxySettings', 'Password' ) ? $ini->variable( 'ProxySettings', 'Password' ) : false;
                if ( $userName )
                {
                    curl_setopt ( $ch, CURLOPT_PROXYUSERPWD, "$userName:$password" );
                }
            }

            if ( !curl_exec( $ch ) )
            {
                $this->ErrorMsg = curl_error( $ch );
                return false;
            }

            curl_close( $ch );
            fclose( $fp );
        }
        else
        {
            $parsedUrl = parse_url( $url );
            $checkIP = isset( $parsedUrl[ 'host' ] ) ? ip2long( gethostbyname( $parsedUrl[ 'host' ] ) ) : false;
            if ( $checkIP === false )
            {
                return false;
            }

            // If we don't have CURL installed we used standard fopen urlwrappers
            // Note: Could be blocked by not allowing remote calls.
            if ( !copy( $url, $fileName ) )
            {
                include_once( 'lib/ezutils/classes/ezhttptool.php' );
                include_once( 'lib/ezfile/classes/ezfile.php' );

                $buf = eZHTTPTool::sendHTTPRequest( $url, 80, false, 'eZ publish Harmonia package manager add-on', false );

                $header = false;
                $body = false;
                if ( eZHTTPTool::parseHTTPResponse( $buf, $header, $body ) )
                {
                    eZFile::create( $fileName, false, $body );
                }
                else
                {
                    $this->ErrorMsg = ezi18n( 'design/standard/setup/init', 'Failed to copy %url to local file %filename', null,
                                              array( "%url" => $url,
                                                     "%filename" => $fileName ) );
                    return false;
                }
            }
        }

        return $fileName;
    }

    /**
     * Downloads and imports package.
     *
     * Sets $this->ErrorMsg in case of an error.
     *
     * \param $forceDownload  download even if this package already exists.
     * \private
     * \return false on error, package object otherwise.
     */
    function downloadAndImportPackage( $packageName, $packageUrl, $forceDownload = false )
    {
        include_once( 'kernel/classes/ezpackage.php' );
        $package = eZPackage::fetch( $packageName, false, false, false );

        if ( is_object( $package ) )
        {
            if ( $forceDownload )
            {
                $package->remove();
            }
            else
            {
                eZDebug::writeNotice( "Skipping download of package '$packageName': package already exists." );
                return $package;
            }
        }

        $archiveName = $this->downloadFile( $packageUrl, /* $outDir = */ eZRemotePackageRepository::tempDir() );
        if ( $archiveName === false )
        {
            eZDebug::writeWarning( "Download of package '$packageName' from '$packageUrl' failed: $this->ErrorMsg" );
            $this->ErrorMsg = ezi18n( 'design/standard/setup/init',
                                      'Download of package \'%pkg\' failed. You may upload the package manually.',
                                      false, array( '%pkg' => $packageName ) );

            return false;
        }

        $package = eZPackage::import( $archiveName, $packageName, false );

        // Remove downloaded ezpkg file
        include_once( 'lib/ezfile/classes/ezfilehandler.php' );
        eZFileHandler::unlink( $archiveName );

        if ( !is_object( $package ) )
        {
            eZDebug::writeNotice( "Invalid package" );
            $this->ErrorMsg = ezi18n( 'design/standard/setup/init', 'Invalid package' );
            return false;
        }

        return $package;
    }


    /*!
     * Download packages required by the given package.
     *
     * \private
     */
    function downloadDependantPackages( $sitePackage )
    {
        $dependencies = $sitePackage->attribute( 'dependencies' );
        $requirements = $dependencies['requires'];
        $this->retrievePackagesList( $remotePackagesInfo );

        foreach ( $requirements as $req )
        {
            $requiredPackageName = $req['name'];

            if ( isset( $req['min-version'] ) )
                $requiredPackageVersion = $req['min-version'];
            else
                $requiredPackageVersion = 0;

            $downloadNewPackage   = false;
            $removeCurrentPackage = false;

            // try to fetch the required package
            $package = eZPackage::fetch( $requiredPackageName, false, false, false );

            // if it already exists
            if ( is_object( $package ) )
            {
                // check its version
                $currentPackageVersion = $package->getVersion();

                // if existing package's version is less than required one
                // we remove the package and download newer one.

                if ( version_compare( $currentPackageVersion, $requiredPackageVersion ) < 0 )
                {
                    $downloadNewPackage   = true;
                    $removeCurrentPackage = true;
                }

                // else (if the version is greater or equal to the required one)
                // then do nothing (skip downloading)
            }
            else
                // if the package does not exist, we download it.
                $downloadNewPackage   = true;

            if ( $removeCurrentPackage )
            {
                $package->remove();
                unset( $package );
            }

            if ( $downloadNewPackage )
            {
                if ( !isset( $remotePackagesInfo[$requiredPackageName]['url'] ) )
                {
                    eZDebug::writeWarning( "Download of package '$requiredPackageName' failed: the URL is unknown." );
                    $this->ErrorMsg = ezi18n( 'design/standard/setup/init',
                                              'Download of package \'%pkg\' failed. You may upload the package manually.',
                                              false, array( '%pkg' => $requiredPackageName ) );
                    $this->ShowURL = true;

                    return false;
                }

                $requiredPackageURL = $remotePackagesInfo[$requiredPackageName]['url'];
                $rc = $this->downloadAndImportPackage( $requiredPackageName, $requiredPackageURL );
                if( !is_object( $rc ) )
                {
                    return false;
                }
            }
        }

        return true;
    }

    var $IndexURL;
    var $FileOpenErrorMsg = false;
}

?>