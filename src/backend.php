<?php
/**
 * archivo que tiene la definicion de controlador para el backend
 *
 * PHP version 5.4.3
 *
 * @category File
 * @package  Backend
 * @author   Jalir Duran <jalir.duran@nuevatel.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://10.40.4.20/bill/index_dev.php/
 *
 */
$backend = $app['controllers_factory'];

$backend->before(
  function () use ($app) {
    $userSession=$app['session']->get('user');
    if ($userSession['userrol']!=1) {
      $app['session']->getFlashBag()->add(
        'error',
        'Su rol no permite ingresar al modulo de administracion.'
      );
      return $app->redirect($app['url_generator']->generate('homepage', array()));
    }
  }
);

$backend->match(
  '/',
  function () use ($app) {
    return $app['twig']->render(
      'backend/backend.twig',
      array(
      '' => '',
      )
    );
  }
)->bind('admin');

$backend->match(
  '/usuarios',
  function () use ($app) {
    $sec=new Seguridad\Seguridad($app);
    $jq=new jqTools\JqTools();
    $dataUsers=$sec->getUsuarios();
    $gridUsers=$jq->tabla(
      $dataUsers,
      'USUARIOS',
      'usersId'
    );
    return $app['twig']->render(
      'backend/usuarios.twig',
      array('grid' => $gridUsers)
    );
  }
)->bind('usuarios');

return $backend;
