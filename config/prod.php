<?php

// configure your app for the production environment

$app['twig.path'] = array(__DIR__.'/../templates');
$app['twig.options'] = array('cache' => __DIR__.'/../var/cache/twig');
/*constantes*/
$app['usr_root']=array('jduran');
$app['usr_admin']=array('jgallinate','gmercado','hmendez');
$app['usr_esp']=array('jdaviu','gmaldonado','joscastro','mvelasco','htorres');
$app['esp']=null;
for ($i=0; $i < count($app['usr_esp']); $i++) {
  $app['esp'].="'".$app['usr_esp'][$i]."',";
}
$app['esp']=substr($app['esp'],0,strlen($app['esp'])-1);

$app['periodo']=date('Ym');
$app['antPeriodo']=date('Ym',strtotime('-1 month',strtotime(date('Ym'))));
$app['antPeriodo'] = ($app['periodo']==$app['antPeriodo'])
                     ? $app['antPeriodo']-1
                     : $app['antPeriodo'] ;
$app['antePeriodo']=date('Ym',strtotime('-2 month',strtotime(date('Ym'))));
$app['antePeriodo']=($app['antePeriodo']==$app['antPeriodo'])
                     ? $app['antePeriodo']-1
                     : $app['antePeriodo'] ;