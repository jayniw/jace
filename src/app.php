<?php

use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SessionServiceProvider;

$app = new Application();
$app->register(new UrlGeneratorServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new TwigServiceProvider());
$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
  // add custom globals, filters, tags, ...
  /*config extension smarTwig*/
  $twig->setExtensions(
        Yepsua\SmarTwig\Twig\Extension\SmarTwigExtension::getAllExtensions()
    );
  return $twig;
}));

$app->register(new SessionServiceProvider());

$app->register(new Silex\Provider\DoctrineServiceProvider(), 
                   array('db.options'    => array(
                              'driver'        => 'pdo_oci',
                              'host'          => '10.49.5.110',
                              'port'          => '1521',
                              'servicename'   => 'CTL',
                              'dbname'        => 'CTL',
                              'user'          => 'itjduran',
                              'password'      => 'jduran2014it',
                              'charset'       => 'utf8'
                              )
                  )
              );
   
  /*$app->register(new Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider, array(
      "orm.em.options" => array(
           "mappings" => array(
              array(
                 "type"      => "yml",
                 "namespace" => "Entity",
                 "path"      => realpath(__DIR__."/../config/doctrine"),
                ),
              ),
           ),
  ));*/



return $app;
