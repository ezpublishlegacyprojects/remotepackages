<?php

$Module = array( 'name' => 'Harmonia', 'variable_params' => false, 'ui_component_match' => 'module' );

$ViewList = array();
$ViewList['list'] = array(
    'script' => 'list.php',
    'params' => array( 'repository' ),
    'default_navigation_part' => 'ezharmonianavigationpart',
    'single_post_actions' => array( 'SwitchButton' => 'Switch', 'ImportButton' => 'Import' ),
    'post_action_parameters' => array( 'Switch' => array( 'RepositoryID' => 'RepositoryID' ),
                                       'Import' => array( 'PackageNameArray' => 'PackageNameArray' )
                                     )
);

$ViewList['history'] = array(
    'script' => 'history.php',
    'params' => array(),
    'default_navigation_part' => 'ezharmonianavigationpart'
);

$ViewList['preferences'] = array(
    'script' => 'preferences.php',
    'params' => array(),
    'default_navigation_part' => 'ezharmonianavigationpart',
    'single_post_actions' => array( 'StoreButton' => 'Store' ),
    'post_action_parameters' => array( 'Store' => array(  ) )
);

$FunctionList = array();

?>