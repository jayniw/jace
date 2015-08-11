<?php
/**
 * archivo que tiene la definicion de la clase para operativa del area
 *
 * PHP version 5.4.3
 *
 * @category File
 * @package  Controller
 * @author   Jalir Duran <jalir.duran@nuevatel.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://10.40.4.20/bill/index_dev.php/
 *
 */
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->mount('/admin', include 'backend.php');

$app->mount('/factPer', include 'fact.php');

$app->match(
  '/',
  function (Request $request) use ($app) {
    if ($request->get('logout')) {
      $app['session']->clear();
    }
    if ($request->get('login')) {
      $login= $request->get('login');
      /*conectar a AD*/
      try {
        $adldap = new adLDAP\adLDAP(
          array('account_suffix' => "@nuevatel.net",
                'base_dn' => "DC=nuevatel,DC=net",
                'domain_controllers' => array ("nuevatel.net:389"))
        );
      } catch (adLDAPException $e) {
        $app['session']->getFlashBag()->add('error', $e);
        exit();
      }
      /*autenticar en AD*/
      if ($adldap->authenticate($login['_username'], $login['_password'])) {
        $app['session']->start();
        $sec=new Seguridad\Seguridad($app);
        $rol=$sec->getRol($login['_username']);
        $app['session']->set(
          'user',
          array('username'=>$login['_username'], 'userrol'=>$rol)
        );
        $menuRol=$sec->getRolMenu($rol);
        $app['session']->set('menu', array());
        if (count($menuRol)>0) {
          for ($i=0; $i < count($menuRol); $i++) {
            $menu[$i]=array('nombre' => $menuRol[$i]['MENU'],
                            'ruta' => $app['url_generator']
                                      ->generate($menuRol[$i]['RUTA'])
                          );
          }
          $app['session']->set('menu', $menu);
        }
      } else {
        $app['session']->getFlashBag()->add(
          'error',
          'Usuario o contraseña incorrecto.'
        );
      }
      $adldap->close();
    }
    return $app['twig']->render('index.html', array());
  }
)->bind('homepage');

$before=function (Request $request) use ($app) {
  $userSession=$app['session']->get('user');
  if (count($userSession)==0) {
    $app['session']->getFlashBag()->add(
      'error',
      'Para acceder a los modulos debe estar logueado.'
    );
    return $app->redirect($app['url_generator']->generate('homepage', array()));
  }
};

$app->match(
  '/monitor',
  function () use ($app) {
    $jq=new jqTools\JqTools();
    /*obtener los tickets pendientes y su demora para clientes internos*/
    $ticket=new Operativa\Operativa($app);
    $dataIncPend=$ticket->getIncidentesPenDet();
    $dataIncPendRes=$ticket->getIncPendResumen();
    $dataProbBill=$ticket->getProbPend();

    return $app['twig']->render('monitor.twig',array(
          'dataIncPendRes'=>$dataIncPendRes,
          'dataProbBill'=>$dataProbBill,
          'dataIncPend'=> $dataIncPend
          ));
  }
)->bind('monitor');

$app->match(
  '/itsm',
  function () use ($app) {
  /*Obtener incidentes de IT*/
  $ticket=new Operativa\Operativa($app);
  $jq=new jqTools\JqTools();
  $dataIncBill=$ticket->getIncPendResumen('facturacion');
  $dataIncPend=$ticket->getIncidentesPenDet('facturacion');
  $dataProbBill=$ticket->getProbPend('facturacion');
  $dataIncRep=$ticket->getIncRep(date('Ymd'));
  $dataRepPeriodo=$ticket->getRepPeriodo(date('Ym'));
  if (count($dataRepPeriodo)==0) {
    $gridRepPeriodo=null;
  } else {
    $gridRepPeriodo=$jq->tabla($dataRepPeriodo,
                               'Reporte de periodo',
                               'repPeriodoId');
  }
  return $app['twig']->render('operativa/itsm.twig',array(
      'dataIncBill' => $dataIncBill,
      'dataProbBill'=>$dataProbBill,
      'dataIncRep' => $dataIncRep,
      'gridRepPeriodo' => $gridRepPeriodo,
      'dataIncPend' => $dataIncPend,
      'dataIncPendRes' => $dataIncBill,
      ));
}
)->bind('itsm');

$app->match(
  '/refact',
  function (Request $request) use ($app) {
    $periodo=date('Ym', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
    $fact=new Facturacion\Facturacion($app);
    $jq=new jqTools\JqTools();
    /*se registra la solicitud de refacturacion*/
    $grupo=$request->get('grupo');
    $motivo=$request->get('motivo');
    if ($grupo<>null || $motivo<>null) {
      $usuarioSession=$app['session']->get('user');
      if ($usuarioSession == null) {
        $usuario=$app['request']->server->get("REMOTE_ADDR");
      } else {
        $usuario=$usuarioSession['username'];
      }
      $regIns=$fact->setRefactGrupo($grupo, $motivo, $periodo, $usuario);
    }
    /*se obtiene la data de refacturacion del periodo*/
    $dataRefact=$fact->getRefactPeriodo($periodo);
    if (count($dataRefact)==0) {
      $gridRefact=null;
    } else {
      $gridRefact=$jq->tablaFiltro($dataRefact, 'Refacturacion', 'refactId');
    }
    return $app['twig']->render('fact/refact.twig', array('grid' => $gridRefact, ));
  }
)->before($before)
 ->bind('refact');



$app->match(
  '/grupo',
  function (Request $request) use ($app) {
    if ($request->get('grupo')) {
      $grupo=$request->get('grupo');
      $cli=new Cliente\Cliente($app);
      $jq=new jqTools\JqTools();
      /*grupo*/
      $dataGrupo=$cli->getGrupo($grupo);
      if (count($dataGrupo)>0) {
        $gridGrupo=$jq->tabla($dataGrupo, 'GRUPO', 'GrupoId');
      } else {
        $gridGrupo=null;
      }
      /*grupo historico*/
      $dataGrupoHist=$cli->getGrupoHist($grupo);
      if (count($dataGrupoHist)>0) {
        $gridGrupoHist=$jq->tabla($dataGrupoHist, 'GRUPO HISTORICO', 'GrupoHistd');
      } else {
        $gridGrupoHist=null;
      }
      /*sd grupo*/
      $dataSdGrupo=$cli->getSdGrupo($grupo);
      if (count($dataSdGrupo)>0) {
        $gridSdGrupo=$jq->tabla($dataSdGrupo, 'SD GRUPO', 'sdGrupoid');
      } else {
        $gridSdGrupo=null;
      }
      return $app['twig']->render(
        'cliente/grupo.twig',
        array('idGrupo' => $grupo,
              'grupo' => $gridGrupo,
              'grupoHist' => $gridGrupoHist,
              'sdGrupo' => $gridSdGrupo,
              )
        );
    } else {
      return $app['twig']->render('cliente/grupo.twig');
    }

  }
)->before($before)
 ->bind('grupo');

$app->match(
  '/unidad',
  function (Request $request) use ($app) {
    if ($request->get('unidad')) {
      $unidad=$request->get('unidad');
      $cli=new Cliente\Cliente($app);
      $jq=new jqTools\JqTools();
      /*unidad*/
      $dataUnidad=$cli->getUnidad($unidad);
      if (count($dataUnidad)>0) {
        $gridUnidad=$jq->tabla($dataUnidad, 'UNIDAD', 'unidadId');
      } else {
        $gridUnidad=null;
      }
      /*unidad historico*/
      $dataUnidadHist=$cli->getUnidadHist($unidad);
      if (count($dataUnidadHist)>0) {
        $gridUnidadHist=$jq->tabla($dataUnidadHist, 'UNIDAD HISTORICO', 'unidadHistId');
      } else {
        $gridUnidadHist=null;
      }
      /*unidad estado historico*/
      $dataUnidadEstadosHist=$cli->getUnidadEstadosHist($unidad);
      if (count($dataUnidadEstadosHist)>0) {
        $gridUnidadEstadosHist=$jq->tabla($dataUnidadEstadosHist, 'UNIDAD ESTADOS HISTORICO', 'unidadEstadosHistId');
      } else {
        $gridUnidadEstadosHist=null;
      }
      /*tr unidad cod layout*/
      $dataUnidadLayout=$cli->getUnidadLayout($unidad);
      if (count($dataUnidadLayout)>0) {
        $gridUnidadLayout=$jq->tabla($dataUnidadLayout, 'UNIDAD LAYOUT HISTORICO', 'UnidadLayoutId');
      } else {
        $gridUnidadLayout=null;
      }

      return $app['twig']->render(
        'cliente/unidad.twig',
        array('idUnidad'=>$unidad,
              'unidad'=>$gridUnidad,
              'unidadHist'=>$gridUnidadHist,
              'unidadEstadosHist'=>$gridUnidadEstadosHist,
              'unidadLayout'=>$gridUnidadLayout,)
      );
    } else {
      return $app['twig']->render('cliente/unidad.twig');
    }
  }
)->before($before)
 ->bind('unidad');

$app->match(
  '/reclamos',
  function (Request $request) use ($app) {
    if ($request->get('periodo')) {
      $periodo=$request->get('periodo');
    } else {
      $periodo=date('Ym');
    }
    $scenter=new Operativa\Operativa($app);
    $jq=new jqTools\JqTools();
    /*cierre reclamos diario*/
    $dataCerradosDia=$scenter->getCerradosDia($periodo);
    $gridCerradosDia=$jq->tablaReclamos(
      $dataCerradosDia,
      'RECLAMOS CERRADOS POR DIA DEL PERIODO '.$periodo,
      'cerradosDiaId',
      $periodo
    );
    return $app['twig']->render(
      'operativa/reclamos.twig',
      array('cerradosDia'=>$gridCerradosDia)
    );
  }
)->before($before)
 ->bind('reclamos');

$app->match(
  '/facturacion',
  function (Request $request) use ($app) {
    set_time_limit(0);
    if ($request->get('periodo')) {
      return $app->redirect(
        $app['url_generator']->generate(
          'fact',
          array('periodo'=>$request->get('periodo'),
                'grupo'=>$request->get('grupo'))
        )
      );
    } else {
      $i=1;
      $periodo=date('Ym');
      while ($i <= 6) {
        $periodo=date('Ym',
                      strtotime('-'.$i.' months',
                                strtotime($periodo))
                     );
        $periodos[$i]=$periodo;
        $i++;
      }
      return $app['twig']->render(
        'fact/factGrupo.twig',
        array('periodos' => $periodos, )
        );
    }
  }
)->bind('facturacion');

$app->match(
  '/facturacion/{periodo}/{grupo}',
  function (Request $request) use ($app) {
    $grupo=$request->get('grupo');
    $periodo=$request->get('periodo');
    $fact=new Facturacion\Facturacion($app);
    $jq=new jqTools\JqTools();
    /*imagen grupo*/
    $dataImagenGrupo=$fact->getImagenGrupo($grupo, $periodo);
    if (count($dataImagenGrupo)>0) {
      $gridImagenGrupo=$jq->tabla($dataImagenGrupo, 'IMAGEN GRUPO', 'imagenGrupoId');
    } else {
      $gridImagenGrupo=null;
    }
    /*imagen unidad*/
    $dataImagenUnidad=$fact->getImagenUnidadGrupo($grupo, $periodo);
    if (count($dataImagenUnidad)>0) {
      $gridImagenUnidad=$jq->tablaFiltro(
        $dataImagenUnidad,
        'IMAGEN UNIDAD',
        'imagenUnidadId'
      );
    } else {
      $gridImagenUnidad=null;
    }
    /*resumen grupo*/
    $dataResumenGrupo=$fact->getResumenGrupo($grupo, $periodo);
    if (count($dataResumenGrupo)>0) {
      $gridResumenGrupo=$jq->tabla(
        $dataResumenGrupo,
        'RESUMEN GRUPO',
        'resumenGrupoId'
      );
    } else {
      $gridResumenGrupo=null;
    }
    /*resumen unidad*/
    $dataResumenUnidad=$fact->getResumenUnidadGrupo($grupo, $periodo);
    if (count($dataResumenUnidad)>0) {
      $gridResumenUnidad=$jq->tablaFiltro(
        $dataResumenUnidad,
        'RESUMEN UNIDAD',
        'resumenUnidadId'
      );
    } else {
      $gridResumenUnidad=null;
    }
    /*universo factura*/
    $dataUnivFact=$fact->getUnivFAct($grupo, $periodo);
    if (count($dataUnivFact)>0) {
      $gridUnivFact=$jq->tabla($dataUnivFact, 'UNIVERSO FACTURA', 'univFactId');
    } else {
      $gridUnivFact=null;
    }
    /*billing factura*/
    $dataBlFact=$fact->getBlFactura($grupo, $periodo);
    if (count($dataBlFact)>0) {
      $gridBlFact=$jq->tabla($dataBlFact, 'BL FACTURA', 'blFactId');
    } else {
      $gridBlFact=null;
    }
    /*ctl factura*/
    $dataCtlFact=$fact->getCtlFactura($grupo, $periodo);
    if (count($dataCtlFact)>0) {
      $gridCtlFact=$jq->tabla($dataCtlFact, 'CTL FACTURA', 'ctlFactId');
    } else {
      $gridCtlFact=null;
    }
    return $app['twig']->render(
      'fact/factGrupo.twig',
      array('imagenGrupo'=>$gridImagenGrupo,
            'imagenUnidad'=>$gridImagenUnidad,
            'resumenGrupo'=>$gridResumenGrupo,
            'resumenUnidad'=>$gridResumenUnidad,
            'univFact'=>$gridUnivFact,
            'blFact'=>$gridBlFact,
            'ctlFact'=>$gridCtlFact)
    );
  }
)->bind('fact');

$app->error(
  function (\Exception $e, $code) use ($app) {
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

    return new Response(
      $app['twig']->resolveTemplate($templates)
                  ->render(array('code' => $code)),
      $code
    );
  }
);
