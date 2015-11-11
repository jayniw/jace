<?php
/**
 * archivo que tiene la definicion de controlador para el periodo de facturacion
 *
 * PHP version 5.4.3
 *
 * @category File
 * @package  fact
 * @author   Jalir Duran <jalir.duran@nuevatel.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://10.47.17.20/bill/index_dev.php/
 *
 */
use Symfony\Component\HttpFoundation\Request;

$fact = $app['controllers_factory'];

$fact->match(
  '/moranof',
  function () use ($app) {
    $fact=new Facturacion\Facturacion($app);
    $jq=new jqTools\JqTools();
    $dataInfoMora_nof=$fact->infoMora_nof($app['antPeriodo']);
    $gridInfoMora_nof = (count($dataInfoMora_nof)>0)
                        ? $jq->tabla($dataInfoMora_nof,
                                     'MONITOREO',
                                     'infoMora_nofId')
                        : null ;
    $dataProcMora_nof = $fact->procMora_nof($app['periodo']);
    $gridProcMora_nof = (count($dataProcMora_nof)>0)
                        ? $jq->tabla($dataProcMora_nof,
                                     'PROCESOS',
                                     'procMora_nofId')
                        : null ;
    return $app['twig']->render(
      'fact/mora_nof.twig',
      array('periodo' => $app['antPeriodo'],
            'gridInfoMora_nof' => $gridInfoMora_nof,
            'gridProcMora_nof' => $gridProcMora_nof,
           )
    );
  }
)->bind('moranof');

$fact->match(
  '/tendencias',
  function (Request $request) use ($app) {
    set_time_limit(0);
    $fact=new Facturacion\Facturacion($app);
    $jq=new jqTools\JqTools();
    /*grupo plan*/
    $dataGrupoPlan=$fact->getTendenciaGrupoPlan();
    for ($i=0; $i < count($dataGrupoPlan); $i++) {
      $dataGP[$i]['GRUPO_PLAN']=$dataGrupoPlan[$i]['GRUPO_PLAN'];
      $dataGP[$i][$app['antePeriodo']]=$fact->getTendenciaAbonoGrupoPlan($app['antePeriodo'],$dataGrupoPlan[$i]['GRUPO_PLAN']);
      $dataGP[$i][$app['antPeriodo']]=$fact->getTendenciaAbonoGrupoPlan($app['antPeriodo'],$dataGrupoPlan[$i]['GRUPO_PLAN']);
      $dataGP[$i][$app['periodo']]=$fact->getTendenciaAbonoGrupoPlan($app['periodo'],$dataGrupoPlan[$i]['GRUPO_PLAN']);
    }
    $gridDataGP=$jq->tabla($dataGP,'Tendencia Abonos Grupo Plan','idGrupoPlan');
    /*servicios*/
    $dataServ[0]['SERVICIO']='Servicios';
    $dataServ[0][$app['antePeriodo']]=$fact->getTendenciaAbonoServicios($app['antePeriodo']);
    $dataServ[0][$app['antPeriodo']]=$fact->getTendenciaAbonoServicios($app['antPeriodo']);
    $dataServ[0][$app['periodo']]=$fact->getTendenciaAbonoServicios($app['periodo']);
    $gridDataServ=$jq->tabla($dataServ,'Tendencia Abonos Servicio','idServicio');
    /*sin requisitos*/
    $dataGrupoPlanSR=array(array('GRUPO_PLAN'=>'GPPPSR'),
                           array('GRUPO_PLAN'=>'GPPPSR4G')
                          );
    for ($i=0; $i < count($dataGrupoPlanSR); $i++) {
      $dataSR[$i]['GRUPO_PLAN']=$dataGrupoPlanSR[$i]['GRUPO_PLAN'];
      $dataSR[$i][$app['antePeriodo']]=$fact->getTendenciaAbonoSR($app['antePeriodo'],$dataGrupoPlanSR[$i]['GRUPO_PLAN']);
      $dataSR[$i][$app['antPeriodo']]=$fact->getTendenciaAbonoSR($app['antPeriodo'],$dataGrupoPlanSR[$i]['GRUPO_PLAN']);
      $dataSR[$i][$app['periodo']]=$fact->getTendenciaAbonoSR($app['periodo'],$dataGrupoPlanSR[$i]['GRUPO_PLAN']);
    }
    $gridDataSR=$jq->tabla($dataSR,'Tendencia Abonos Sin Requisitos','idSinRequisitos');
    return $app['twig']->render('fact/tendencias.twig',array(
        'periodo' => $app['periodo'],
        'gridDataGP'=> $gridDataGP,
        'gridDataServ'=> $gridDataServ,
        'gridDataSR'=> $gridDataSR,
        ));
  }
)->bind('tendencias');

$fact->match(
  '/abonos',
  function () use ($app) {
    $fact=new Facturacion\Facturacion($app);
    $jq=new jqTools\JqTools();
    $dataMonAbono=$fact->infoAbonos($app['periodo']);
    $gridMonAbono = (count($dataMonAbono))>0
                    ? $jq->tabla($dataMonAbono,
                                 'MONITOREO',
                                 'monAbonoId')
                    : null ;
    $dataProcAbono=$fact->procAbonos($app['periodo']);
    $gridprocAbono = (count($dataProcAbono))>0
                    ? $jq->tabla($dataProcAbono,
                                 'PROCESOS',
                                 'procAbonoId')
                    : null ;
    return $app['twig']->render(
      'fact\abonos.twig',
      array('periodo' => $app['periodo'],
            'gridMonAbono' => $gridMonAbono,
            'gridprocAbono' => $gridprocAbono,

           )
      );
  }
)->bind('abonos');

$fact->get(
  '/imagen',
  function () use ($app) {
    $fact=new Facturacion\Facturacion($app);
    $jq=new jqTools\JqTools();
    $dataMonImagen=$fact->getImagenProcess($app['antPeriodo']);
    $gridMonImagen=(count($dataMonImagen)>0 )
                   ? $jq->tabla($dataMonImagen,
                                'MONITOREO',
                                'monImagenId')
                   : null ;
    $dataProcImagen=$fact->getImagenProcessDem($app['periodo']);
    $gridProcImagen = (count($dataProcImagen))
                      ? $jq->tabla($dataProcImagen,
                                   'PROCESOS',
                                   'procImagenId')
                      : null ;
    return $app['twig']->render(
            'fact\imagen.twig',
            array('periodo'=>$app['antPeriodo'],
                  'gridMonImagen' => $gridMonImagen,
                  'gridProcImagen' => $gridProcImagen)
            );
  }
)->bind('imagen');

$fact->match(
  '/resumen',
  function (Request $request) use ($app) {
    set_time_limit(0);
    $fact=new Facturacion\Facturacion($app);
    $jq=new jqTools\JqTools();
    $dataResumen=$fact->getResumenProcess($app['antPeriodo']);
    $gridMonResumen=(count($dataResumen)>0 )
                   ? $jq->tabla($dataResumen,
                                'MONITOREO',
                                'monResumenId')
                   : null ;
    $dataProcResumen=$fact->getResumenProcessDem($app['periodo']);
    $gridProcResumen = (count($dataProcResumen))
                      ? $jq->tabla($dataProcResumen,
                                   'PROCESOS',
                                   'procResumenId')
                      : null ;
    return $app['twig']->render(
            'fact/resumen.twig',
            array('periodo'=>$app['antPeriodo'],
                  'gridMonResumen' => $gridMonResumen,
                  'gridProcResumen' => $gridProcResumen)
            );
  }
)->bind('resumen');

$fact->match(
  '/facturar',
  function (Request $request) use ($app) {
    $periodo=date('Ym',
                  strtotime('-1 month',
                            strtotime(date('Ym'))
                            )
                  );
    $fact=new Facturacion\Facturacion($app);
    $dataLog=$fact->getLogProgTime();
    return $app['twig']->render('fact/facturar.twig',array(
        'periodo' => $periodo,
        'dataLog' => $dataLog,
        ));
  }
)->bind('facturar');

return $fact;