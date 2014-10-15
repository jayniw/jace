<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->mount('/admin',include 'backend.php');

$app->match('/', function (Request $request) use ($app){
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
      $app['session']->getFlashBag()->add('error',$e);
      exit();
    }
    /*autenticar en AD*/
    if ($adldap->authenticate($login['_username'],$login['_password'])) {
      $app['session']->start();
      $sec=new Seguridad\seguridad($app);
      $rol=$sec->getRol($login['_username']);
      $app['session']->set('user',array('username'=>$login['_username'],
                                        'userrol'=>$rol));
    } else {
      $app['session']->getFlashBag()->add('error','Usuario o contraseña incorrecto.');
    }
    $adldap->close();
  }
  return $app['twig']->render('index.html', array());
})->bind('homepage');

$before=function (Request $request) use ($app) {
  $userSession=$app['session']->get('user');
  if (count($userSession)==0) {
    $app['session']->getFlashBag()->add('error','Para acceder a los modulos debe estar logueado.');
    return $app->redirect($app['url_generator']->generate('homepage', array()));
  }
};

$app->get('/imagen', function () use ($app) {
  $fact=new Facturacion\Facturacion($app);
  $jq=new jqTools\jqTools();
  $dataTmpImagen=$fact->getImagenProcess();
  $dataTmpImagenDem=$fact->getImagenProcessDem();
  $gridTmpImagen=$jq->tabla($dataTmpImagen,
                            '['.$dataTmpImagenDem[0]['AHORA'].']Porcentaje generado de la imagen. Demora de '.$dataTmpImagenDem[0]['DEM'].' minutos.',
                            'tmpImagenId');
  return $app['twig']->render('imagen.twig', array('grid' => $gridTmpImagen));
}
)->before($before)
 ->bind('imagen');

$app->match('/resumen', function (Request $request) use ($app) {
  set_time_limit(0);
  $periodo=date('Ym')-1;
  $fact=new Facturacion\Facturacion($app);
  $jq=new jqTools\jqTools();
  $dataResumen=$fact->getResumenProcess($periodo);
  $dataResumenDem=$fact->getResumenProcessDem();
  $gridResumen=$jq->tablaFiltro($dataResumen,
                          '['.$dataResumenDem[0]['AHORA'].']Porcentaje generado del resumen. Demora de '.$dataResumenDem[0]['DEM'].' minutos.',
                          'tmpResumenId');
  //for para obtener los datos por estado
  //creacion de array poara almacenar los grids a mostrar en template
  return $app['twig']->render('resumen.twig', array('grid' => $gridResumen));
}
)->before($before)
 ->bind('resumen');

$app->match('/facturacion', function(Request $request) use ($app){
  if ($request->get('periodo')){
    return $app->redirect(
    $app['url_generator']->generate('fact',
                                    array('periodo'=>$request->get('periodo'),
                                          'grupo'=>$request->get('grupo')))
    );
   }else{
    return $app['twig']->render('fact/factGrupo.twig');
  }
}
)->bind('facturacion');

$app->match('/facturacion/{periodo}/{grupo}', function (Request $request) use ($app) {
  $grupo=$request->get('grupo');
  $periodo=$request->get('periodo');
  $fact=new Facturacion\Facturacion($app);
  $jq=new jqTools\jqTools();
  /*imagen grupo*/
  $dataImagenGrupo=$fact->getImagenGrupo($grupo,$periodo);
  if (count($dataImagenGrupo)>0) {
    $gridImagenGrupo=$jq->tabla($dataImagenGrupo,'IMAGEN GRUPO','imagenGrupoId');
  } else {
    $gridImagenGrupo=null;
  }
  /*imagen unidad*/
  $dataImagenUnidad=$fact->getImagenUnidadGrupo($grupo,$periodo);
  if (count($dataImagenUnidad)>0) {
    $gridImagenUnidad=$jq->tablaFiltro($dataImagenUnidad,'IMAGEN UNIDAD','imagenUnidadId');
  } else {
    $gridImagenUnidad=null;
  }
  /*resumen grupo*/
  $dataResumenGrupo=$fact->getResumenGrupo($grupo,$periodo);
  if (count($dataResumenGrupo)>0) {
    $gridResumenGrupo=$jq->tabla($dataResumenGrupo,'RESUMEN GRUPO','resumenGrupoId');
  } else {
    $gridResumenGrupo=null;
  }
  /*resumen unidad*/
  $dataResumenUnidad=$fact->getResumenUnidadGrupo($grupo,$periodo);
  if (count($dataResumenUnidad)>0) {
    $gridResumenUnidad=$jq->tablaFiltro($dataResumenUnidad,'RESUMEN UNIDAD','resumenUnidadId');
  } else {
    $gridResumenUnidad=null;
  }
  /*universo factura*/
  $dataUnivFact=$fact->getUnivFAct($grupo,$periodo);
  if (count($dataUnivFact)>0) {
    $gridUnivFact=$jq->tabla($dataUnivFact,'UNIVERSO FACTURA','univFactId');
  } else {
    $gridUnivFact=null;
  }
  /*billing factura*/
  $dataBlFact=$fact->getBlFactura($grupo,$periodo);
  if (count($dataBlFact)>0) {
    $gridBlFact=$jq->tabla($dataBlFact,'BL FACTURA','blFactId');
  } else {
    $gridBlFact=null;
  }
  /*ctl factura*/
  $dataCtlFact=$fact->getCtlFactura($grupo,$periodo);
  if (count($dataCtlFact)>0) {
    $gridCtlFact=$jq->tabla($dataCtlFact,'CTL FACTURA','ctlFactId');
  } else {
    $gridCtlFact=null;
  }
  return $app['twig']->render('fact/factGrupo.twig',
                              array('imagenGrupo'=>$gridImagenGrupo,
                                    'imagenUnidad'=>$gridImagenUnidad,
                                    'resumenGrupo'=>$gridResumenGrupo,
                                    'resumenUnidad'=>$gridResumenUnidad,
                                    'univFact'=>$gridUnivFact,
                                    'blFact'=>$gridBlFact,
                                    'ctlFact'=>$gridCtlFact));
}
)->bind('fact');

$app->match('/reclamos', function(Request $request) use ($app){
  if ($request->get('periodo')) {
    $periodo=$request->get('periodo');
  } else {
    $periodo=date('Ym');
  }
  $scenter=new Operativa\operativa($app);
  $jq=new jqTools\jqTools();
  /*cierre reclamos diario*/
  $dataCerradosDia=$scenter->getCerradosDia($periodo);
  $gridCerradosDia=$jq->tablaReclamos($dataCerradosDia,
                                      'RECLAMOS CERRADOS POR DIA DEL PERIODO '.$periodo,
                                      'cerradosDiaId',
                                      $periodo);
  return $app['twig']->render('operativa/reclamos.twig',array('cerradosDia'=>$gridCerradosDia));
}
)->before($before)
 ->bind('reclamos');

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
