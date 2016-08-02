<?php
/**
 * archivo que tiene la definicion de controlador para el ITSM
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

$itsm = $app['controllers_factory'];

$itsm->match(
  '/mon_fact',
  function (Request $request) use ($app) {
  $periodo = ($request->get('periodo'))
           ? str_replace('/','-',$request->get('periodo'))
           : date('m-d-Y');
  $subgerencia = ($request->get('subgerencia')) ? $request->get('subgerencia') : 'all' ;
  /*Obtener incidentes de IT*/
  $ticket=new Operativa\Operativa($app);
  $jq=new jqTools\JqTools();
  //obtener los incidentes del mes y agruparlos por subgerencia
  $dataKPIinc=$ticket->getKPIincidentes($periodo,$subgerencia,0);
  //obtener los incidentes del mes y agruparlos por subgerencia y esp
  $dataKPIincEsp=$ticket->getKPIincidentes($periodo,$subgerencia,1);
  //obtener problemas por subgerencia
  $dataKPIprob=$ticket->getKPIproblemas($periodo,$subgerencia,0);
  //obtener problemas por especialista
  $dataKPIprobEsp=$ticket->getKPIproblemas($periodo,$subgerencia,1);
  //
  return $app['twig']->render('monitor\board.twig',array(
      'periodo'=>$periodo,
      'dataKPIincMes' => $dataKPIinc,
      'dataKPIincEsp' => $dataKPIincEsp,
      'dataKPIprob' => $dataKPIprob,
      'dataKPIprobEsp' => $dataKPIprobEsp,
      ));
}
)->bind('itsm_mon_fact');

$itsm->match(
  '/inc_det/{subgerencia}/{grupo}/{esp}/{vencido}/{periodo}/{estado}',
  function (Request $request) use ($app) {
    $sm=new Operativa\Operativa($app);
    $jq=new jqTools\JqTools();
    //parametros enviados
    $subgerencia=$request->get('subgerencia');
    $grupo=$request->get('grupo');
    $esp=$request->get('esp');
    $vencido=$request->get('vencido');
    $periodo=$request->get('periodo');
    $estado=$request->get('estado');
    //obtener el detalle de los incidentes
    //args->$subgerencia,$grupo,$esp,$vencido,$periodo,$estado
    $dataIncDet=$sm->getIncidentesDetalle($subgerencia,
                                          $grupo,
                                          $esp,
                                          $vencido,
                                          $periodo,
                                          $estado);
    //generar la grilla
    if (count($dataIncDet)==0) {
      $gridIncDet=null;
    } else {
      $gridIncDet=$jq->tablaFiltro($dataIncDet,
                                   'Detalle de Incidentes',
                                   'detIncId');
    }

    return $app['twig']->render(
              'monitor\inc_det.twig',
              array(
                'gridIncDet' => $gridIncDet,
              ));

  }
)->bind('itsm_inc_det');

$itsm->match(
  '/inc_prob/{subgerencia}/{grupo}/{esp}/{vencido}/{periodo}/{estado}',
  function (Request $request) use ($app) {
    $sm=new Operativa\Operativa($app);
    $jq=new jqTools\JqTools();
    //parametros enviados
    $subgerencia=$request->get('subgerencia');
    $grupo=$request->get('grupo');
    $esp=$request->get('esp');
    $vencido=$request->get('vencido');
    $periodo=$request->get('periodo');
    $estado=$request->get('estado');
    //obtener el detalle de los incidentes
    //args->$subgerencia,$grupo,$esp,$vencido,$periodo,$estado
    $dataProbDet=$sm->getProblemasDetalle($subgerencia,
                                          $grupo,
                                          $esp,
                                          $vencido,
                                          $periodo,
                                          $estado);
    //generar la grilla
    if (count($dataProbDet)==0) {
      $gridProbDet=null;
    } else {
      $gridProbDet=$jq->tablaFiltro($dataProbDet,
                                   'Detalle de Problemas',
                                   'detProbId');
    }

    return $app['twig']->render(
              'monitor\prob_det.twig',
              array(
                'gridProbDet' => $gridProbDet,
              ));

  }
)->bind('itsm_prob_det');

return $itsm;