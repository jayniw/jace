<?php
$backend = $app['controllers_factory'];

$backend->before(function () use ($app) {
  $userSession=$app['session']->get('user');
  if ($userSession['userrol']!=1) {
      $app['session']->getFlashBag()->add('error','Su rol no permite ingresar al modulo de administracion.');
      return $app->redirect($app['url_generator']->generate('homepage', array()));
    }
}
);

$backend->match('/', function () use ($app) {

  return $app['twig']->render('backend/backend.twig',array(
      '' => '',
      ));
}
)->bind('admin');

$backend->match('/usuarios', function () use ($app) {
  $sec=new Seguridad\seguridad($app);
  $jq=new jqTools\jqTools();
  $dataUsers=$sec->getUsuarios();
  $gridUsers=$jq->tabla($dataUsers,
                        'USUARIOS',
                        'usersId');
  return $app['twig']->render('backend/usuarios.twig',
                              array(
                                'grid' => $gridUsers,
                              ));
}
)->bind('usuarios');

return $backend;