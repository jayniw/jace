<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

//Request::setTrustedProxies(array('127.0.0.1'));

/*$app->get('/', 
          function () use ($app) {
            return $app['twig']->render('index.html', array());
          })
      ->bind('homepage');*/

$app->match('/',
           function (Request $request) use ($app){
            $error=array();
            if ($request->get('logout')){
              $app['session']->clear();
            }
            if ($request->get('login')){
              $login= $request->get('login');
              /*conectar a AD*/
              try{
                $adldap = new adLDAP\adLDAP(array('account_suffix' => "@nuevatel.net",
                                                  'base_dn' => "DC=nuevatel,DC=net",
                                                  'domain_controllers' => array ("10.40.3.97:389",
                                                                                 "adserverlpz.nuevatel.net:389")));
              }
              catch (adLDAPException $e) {
                  $error['ldap']=$e;
                  exit();   
              }
              /*autenticar en AD*/
              if ($adldap->authenticate($login['_username'],$login['_password'])) {
                $app['session']->start();
                $sec=new Seguridad\seguridad();
                $rol=$sec->getRol($login['_username']);
                $app['session']->set('user',array('username'=>$login['_username'],
                                                  'userrol'=>$rol));
              } else {
                $error['login']='Usuario o contraseña incorrecto.';
              }
              $adldap->close();
            }
            return $app['twig']->render('index.html', array('error'=>$error));
           })
      ->bind('homepage');

$app->get('/db', 
          function () use ($app) {
            $sql="SELECT EC_BILL_STATUS,EC_CRM_STATUS,count(*) c
                    from itjduran.dc_imagen_201407
                   where EC_BILL_STATUS<>EC_CRM_STATUS
                   group by EC_BILL_STATUS,EC_CRM_STATUS
                   order by c desc";
            $rows=$app['db']->fetchAll($sql);
            return $app['twig']->render('db.html', array('hoy'=>$rows));
          })
      ->bind('db');

$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html',
        'errors/'.substr($code, 0, 2).'x.html',
        'errors/'.substr($code, 0, 1).'xx.html',
        'errors/default.html',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});
