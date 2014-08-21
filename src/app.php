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

$app->register(new Silex\Provider\DoctrineServiceProvider(), array (
                   'dbs.options' => array (
                      'billing' => array (
                              'driver'        => 'pdo_oci',
                              'host'          => '10.49.5.110',
                              'port'          => '1521',
                              'servicename'   => 'CTL',
                              'dbname'        => 'CTL',
                              'user'          => 'billing',
                              'password'      => 'BILL123',
                              'charset'       => 'utf8'
                              ),
                      'scenter' => array (
                              'driver'        => 'pdo_oci',
                              'host'          => '10.49.4.40',
                              'port'          => '1521',
                              'servicename'   => 'itdb',
                              'dbname'        => 'itdb',
                              'user'          => 'scenter',
                              'password'      => 'service',
                              'charset'       => 'utf8'
                              ),
                    ),
                  )
                );

return $app;
