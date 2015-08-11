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
use Symfony\Component\HttpFoundation\Request;

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
    $mon=new Monitor\Monitor($app);
    $jq=new jqTools\JqTools();
    $dataLogRedirect=$mon->getLogRedirect();
    if (count($dataLogRedirect)==0) {
      $gridLogRedirect=null;
    } else {
      $gridLogRedirect=$jq->tablaFiltro(
        $dataLogRedirect,
        'LOG REDIRECCIONAMIENTO',
        'logRedirectId'
      );
    }
    return $app['twig']->render(
      'backend/backend.twig',
      array('grid' => $gridLogRedirect)
    );
  }
)->bind('admin');

$backend->match(
  '/usuarios',
  function (Request $request) use ($app) {
    $sec=new Seguridad\Seguridad($app);
    $jq=new jqTools\JqTools();
    $resp=null;
    /*Datos de formualario*/
    $crear=$request->get('new_crear');
    /*registrar nuevo usuario*/
    if ($crear<>null) {
      $idUsuario=$request->get('new_usuario');
      $email=$request->get('new_email');
      $telf=$request->get('new_telf');
      $ip=$request->get('new_ip');
      $rol=$request->get('new_rol');
      $resp=$sec->setUsuario($idUsuario,$email,$telf,$ip,$rol);
    }
    /*Obtener los usuarios*/
    $dataUsers=$sec->getUsuarios();
    $gridUsers=$jq->tabla(
      $dataUsers,
      'USUARIOS',
      'usersId'
    );
    $dataRoles=$sec->getRoles();

    return $app['twig']->render(
      'backend/usuarios.twig',
      array('grid' => $gridUsers,'roles'=>$dataRoles,'resp'=>$resp)
    );
  }
)->bind('usuarios');

$backend->match(
  '/roles',
  function (Request $request) use ($app) {
    $sec=new Seguridad\Seguridad($app);
    $jq=new jqTools\JqTools();
    $resp=null;
    /*registrar nuevo rol*/
    $crear=$request->get('new_crear');
    if ($crear<>null) {
      $rol=$request->get('new_rol');
      $resp=$sec->setRol($rol);
    }

    $dataRoles=$sec->getRoles();
    $gridRoles=$jq->tabla($dataRoles, 'ROLES', 'rolesId');
    return $app['twig']->render(
      'backend/roles.twig',
      array('grid' => $gridRoles,'resp'=>$resp)
    );
  }
)->bind('roles');

$backend->match(
  '/menu',
  function (Request $request) use ($app) {
    $sec=new Seguridad\Seguridad($app);
    $jq=new jqTools\JqTools();
    $resp=null;
    /*registrar nuevo menu*/
    $crear=$request->get('new_crear');
    if ($crear<>null) {
      $nombre=$request->get('new_nombre');
      $ruta=$request->get('new_ruta');
      $resp=$sec->setMenu($nombre,$ruta);
    }

    $dataMenu=$sec->getMenu();
    $gridMenu=$jq->tabla($dataMenu, 'Menu', 'menuId');

    return $app['twig']->render(
      'backend/menu.twig',
      array('grid' => $gridMenu,'resp'=>$resp)
    );
  }
)->bind('menu');

$backend->match(
  '/rolMenu',
  function (Request $request) use ($app) {
    $sec=new Seguridad\Seguridad($app);
    $jq=new jqTools\JqTools();
    $resp=null;
    /*registrar nuevo rol-menu*/
    $crear=$request->get('new_crear');
    if ($crear<>null) {
      $rol=$request->get('new_rol');
      $menu=$request->get('new_menu');
      $resp=$sec->setRolMenu($rol,$menu);
    }
    $dataRoles=$sec->getRoles();
    $dataMenu=$sec->getMenu();
    $dataRolMenu=$sec->getRolMenu(0);
    $gridRolMenu=$jq->tabla($dataRolMenu, 'Rol - Menu', 'rolMenuId');

    return $app['twig']->render(
      'backend/rolMenu.twig',
      array('grid' => $gridRolMenu,
            'roles'=>$dataRoles,
            'menus'=>$dataMenu,
            'resp'=>$resp)
    );
  }
)->bind('rolMenu');

return $backend;
