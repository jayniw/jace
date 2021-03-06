<?php
/**
 * archivo que tiene la definicion de la clase para operativa del area
 *
 * PHP version 5.4.3
 *
 * @category File
 * @package  Operativa
 * @author   Jalir Duran <jalir.duran@nuevatel.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://10.40.4.20/bill/index_dev.php/
 *
 */
namespace Operativa;

/**
 *  clase para obntener informacion sobre la operativa del area.
 *
 * @category Class
 * @package  Operativa
 * @author   Jalir Duran <jalir.duran@nuevatel.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://10.40.4.20/bill/index_dev.php/
 *
 */
class Operativa
{
  /**
   * Inicializacion de la clase
   *
   * @param object $app entorno de la aplicacion
   */
  public function __construct($app)
  {
    $this->app= $app;
    $sqlNlsDateFormat="ALTER SESSION SET NLS_DATE_FORMAT = 'DD.MM.YYYY HH24:MI:SS'";
    $nlsDateFormat=$this->app['dbs']['itsm']->prepare($sqlNlsDateFormat);
    $nlsDateFormat->execute();
  }
  /**
   * obtener el reporte de incidentes generado cada dia a las 7.
   * @param  date $fecha fecha del reporte a ser generado
   * @return array       data del reporte
   */
  public function getIncRep($fecha)
  {
    $sqlIncRep="SELECT grupo, h6, h6_12, h12_24, h24_48, h48_72, h72_168, h168, total
                  from bil_mon.rep_pend_x_grupo
                 where fecha = to_date('$fecha', 'YYYYMMDD')
                   and grupo!='TOTAL'";
    return $this->app['dbs']['itsm']->fetchAll($sqlIncRep);
  }
  /**
   * Obtiene los incidentes pendientes de facturacion segun reporte a Wilson
   * @return [type] [description]
   */
  public function getIncPendResumen($mesa = 'Billing')
  {
    switch ($mesa) {
      case 'facturacion':
        $where = "and exists (select *
                                from bil_mon.vw_grupos_billing gb
                               where gb.NAME=t.grupo)";
        break;
      case null:
        $where =null;
        break;
      default:
        $where="and t.grupo like ('%$mesa%')";
        break;
    }
    $sqlIncPendResumen="SELECT grupo,
                               sum(h6) h6,
                               sum(h16) h16,
                               sum(h24) h24,
                               sum(h32) h32,
                               sum(h48) h48,
                               sum(h_48) h_48,
                               sum(h0) h0,
                               count(*) total
                          from (select x.grupo,
                                       x.demora,
                                       case when sla_horas = 6 then 1 else 0 end h6,
                                       case when sla_horas = 16 then 1 else 0 end h16,
                                       case when sla_horas = 24 then 1 else 0 end h24,
                                       case when sla_horas = 32 then 1 else 0 end h32,
                                       case when sla_horas = 48 then 1 else 0 end h48,
                                       case when sla_horas > 48 then 1 else 0 end h_48,
                                       case when nvl(sla_horas, 0) = 0 then 1 else 0 end h0,
                                       x.sla_horas,
                                       x.codigo
                                  from (select t.grupo,
                                               t.sla_horas,
                                               t.fecha_creacion,
                                               round(sysdate - t.fecha_creacion, 2) * 24 demora,
                                               t.codigo,
                                               t.titulo,
                                               t.usuario_asignado
                                          from BIL_MON.VW_ALLINCIDENTS t
                                         where t.estado = 'Asignada a un grupo'
                                           and t.subestado='En atención'".
                                               $where
                                           .") x)
                         group by grupo
                         order by 9 desc";
    return $this->app['dbs']['itsm']->fetchAll($sqlIncPendResumen);
  }
  /**
   * Obtener la lista detallada de incidentes pendientes de facturacion
   * @return array data obtenida
   */
  public function getIncidentesPenDet($mesa = 'Billing')
  {
    switch ($mesa) {
      case 'facturacion':
        $where = "and exists (select *
                                from bil_mon.vw_grupos_billing gb
                               where gb.NAME=vv.grupo)";
        break;
      case null:
        $where =null;
        break;
      default:
        $where="and vv.grupo like ('%$mesa%')";
        break;
    }
    $sqlGetIncidentesPenDet="SELECT vv.sla_horas   sla,
                                     vv.esp,
                                     vv.codigo,
                                     vv.titulo,
                                     vv.creacion,
                                     vv.vencimiento,
                                     vv.venc estado,
                                     vv.grupo
                                from bil_mon.vw_incidentes_bill vv
                              where vv.estado = 'Asignada a un grupo'
                                 $where
                               order by vv.vencimiento asc";
    return $this->app['dbs']['itsm']->fetchAll($sqlGetIncidentesPenDet);
  }
  /**
   * Obtener el resumen deincidentes pendientes al equipo de facturacion.
   *
   * @return array data obtenida
   */
  public function getIncidentesPend()
  {
    $sqlGetIncidentesPend=" SELECT sla,
                                   substr(esp,10) esp,
                                   count(*) total,
                                   sum(vencido) vencido,
                                   sum(por_vencer) por_vencer,
                                   sum(a_tiempo) a_tiempo
                              from (select t.sla_horas sla,
                                           t.usuario_asignado esp,
                                           case
                                             when (fecha_creacion + sla_horas / 24) < sysdate then
                                              1
                                             else
                                              0
                                           end vencido,
                                           case
                                             when (fecha_creacion + sla_horas / 24) between
                                                  sysdate - 1 / 24 and sysdate then
                                              1
                                             else
                                              0
                                           end por_vencer,
                                           case
                                             when (fecha_creacion + sla_horas / 24) > sysdate - 1 / 24 then
                                              1
                                             else
                                              0
                                           end a_tiempo
                                      from BIL_MON.VW_ALLINCIDENTS t
                                     where t.estado = 'Asignada a un grupo'
                                       and t.subestado='En atención'
                                       and t.grupo like ('%Billing%')
                                     order by t.sla_horas)
                             group by sla, esp
                             order by sla, total";
    return $this->app['dbs']['itsm']->fetchAll($sqlGetIncidentesPend);
  }
  /**
   * Obtiene el reporte de cumplimiento del periodo
   * @param  string $periodo periodo del reporte
   * @return array          data del reporte
   */
  public function getRepPeriodo($periodo)
  {
    $sqlRepPeriodo="SELECT grupo,
                           d01, d02, d03, d04, d05, d06, d07, d08, d09, d10,
                           d11, d12, d13, d14, d15, d16, d17, d18, d19, d20,
                           d21, d22, d23, d24, d25, d26, d27, d28, d29, d30,
                           d31
                      from bil_mon.rep_pend_periodo
                     where periodo = '$periodo'";
    return $this->app['dbs']['itsm']->fetchAll($sqlRepPeriodo);

  }

  public function getProbPend($mesa = 'Billing')
  {
    switch ($mesa) {
      case 'facturacion':
        $where = "and exists (select *
                                from bil_mon.vw_grupos_billing gb
                               where gb.NAME=pp.pawSvcAuthGroupsName)";
        break;
      case 'esp':
        $where = "and (pp.pawSvcAuthUsersInvSignFullName in
                       ('Daviu Arevalo, Julio',
                         'Maldonado, Gustavo',
                         'Gallinate, Juan',
                         'Mercado, Georgina',
                         'Duran, Jalir',
                         'Velasco, Miguel',
                         'Torres, Rodrigo') or
                       pp.pawSvcAuthUsersCloserFullName in
                       ('Daviu Arevalo, Julio',
                         'Maldonado, Gustavo',
                         'Gallinate, Juan',
                         'Mercado, Georgina',
                         'Duran, Jalir',
                         'Velasco, Miguel',
                         'Torres, Rodrigo'))
                  and pp.pawSvcAuthGroupsName not like ('%Billing%')";
        break;
      case null:
        $where =null;
        break;
      default:
        $where="and pp.pawSvcAuthGroupsName like ('%$mesa%')";
        break;
    }
    $sqlProbPendResumen="SELECT substr(pp.code,5) codigo,
                                pp.creationDate creacion,
                                pp.estimatedDate estimado,
                                pp.pawSvcAuthUsersCreatorFullName creado_por,
                                pp.pawSvcAuthGroupsName grupo,
                                case
                                 when pp.status = 4 then
                                  pp.pawSvcAuthUsersCloserFullName
                                 else
                                  nvl(pp.pawSvcAuthUsersInvFullName,pp.pawSvcAuthUsersInvSignFullName)
                                end asignado_a,
                                substr(pp.padSLAsName, 7, 5) sla,
                                substr(pp.padCalendarsName, 12) cal,
                                pp.problemtitle titulo,
                                round(sysdate - pp.creationDate, 2) demora_dias,
                                decode(pp.status,0,'NUEVO',
                                                 2,'ASIGNADO',
                                                 3,'ERROR CONOCIDO',
                                                 4,'ERROR CONOCIDO CON SOLUCION PROPUESTA',
                                                 pp.status) estado
                           from panet.viewallproblems pp
                          where status not in (1,5,6)
                                $where
                          order by pp.pawSvcAuthGroupsName asc,pp.estimatedDate asc
                          ";
    return $this->app['dbs']['itsm']->fetchAll($sqlProbPendResumen);
  }

  /**
   * obtener los incidentes cerrados de los especialistas por dia
   *
   * @param varchar $periodo periodo a ser evaluado
   *
   * @return array data a mostrar
   */
  public function getCerradosDia($periodo)
  {
    $sqlCerradosDia="SELECT especialista,
                             sum(D01) D01,sum(D02) D02,sum(D03) D03,sum(D04) D04,sum(D05) D05,sum(D06) D06,sum(D07) D07,sum(D08) D08,
                             sum(D09) D09,sum(D10) D10,sum(D11) D11,sum(D12) D12,sum(D13) D13,sum(D14) D14,sum(D15) D15,sum(D16) D16,
                             sum(D17) D17,sum(D18) D18,sum(D19) D19,sum(D20) D20,sum(D21) D21,sum(D22) D22,sum(D23) D23,sum(D24) D24,
                             sum(D25) D25,sum(D26) D26,sum(D27) D27,sum(D28) D28,sum(D29) D29,sum(D30) D30,sum(D31) D31,
                             sum(D01)+sum(D02)+sum(D03)+sum(D04)+sum(D05)+sum(D06)+sum(D07)+sum(D08)+
                             sum(D09)+sum(D10)+sum(D11)+sum(D12)+sum(D13)+sum(D14)+sum(D15)+sum(D16)+
                             sum(D17)+sum(D18)+sum(D19)+sum(D20)+sum(D21)+sum(D22)+sum(D23)+sum(D24)+
                             sum(D25)+sum(D26)+sum(D27)+sum(D28)+sum(D29)+sum(D30)+sum(D31) total
                        from (
                      select responsiblesignaturedate fecha_respondido,
                             vv.PAWSVCAUTHUSERSRESPSIGFULLNAME especialista,
                             case to_char(responsiblesignaturedate,'DD') when '01' then 1 else 0 end D01,
                             case to_char(responsiblesignaturedate,'DD') when '02' then 1 else 0 end D02,
                             case to_char(responsiblesignaturedate,'DD') when '03' then 1 else 0 end D03,
                             case to_char(responsiblesignaturedate,'DD') when '04' then 1 else 0 end D04,
                             case to_char(responsiblesignaturedate,'DD') when '05' then 1 else 0 end D05,
                             case to_char(responsiblesignaturedate,'DD') when '06' then 1 else 0 end D06,
                             case to_char(responsiblesignaturedate,'DD') when '07' then 1 else 0 end D07,
                             case to_char(responsiblesignaturedate,'DD') when '08' then 1 else 0 end D08,
                             case to_char(responsiblesignaturedate,'DD') when '09' then 1 else 0 end D09,
                             case to_char(responsiblesignaturedate,'DD') when '10' then 1 else 0 end D10,
                             case to_char(responsiblesignaturedate,'DD') when '11' then 1 else 0 end D11,
                             case to_char(responsiblesignaturedate,'DD') when '12' then 1 else 0 end D12,
                             case to_char(responsiblesignaturedate,'DD') when '13' then 1 else 0 end D13,
                             case to_char(responsiblesignaturedate,'DD') when '14' then 1 else 0 end D14,
                             case to_char(responsiblesignaturedate,'DD') when '15' then 1 else 0 end D15,
                             case to_char(responsiblesignaturedate,'DD') when '16' then 1 else 0 end D16,
                             case to_char(responsiblesignaturedate,'DD') when '17' then 1 else 0 end D17,
                             case to_char(responsiblesignaturedate,'DD') when '18' then 1 else 0 end D18,
                             case to_char(responsiblesignaturedate,'DD') when '19' then 1 else 0 end D19,
                             case to_char(responsiblesignaturedate,'DD') when '20' then 1 else 0 end D20,
                             case to_char(responsiblesignaturedate,'DD') when '21' then 1 else 0 end D21,
                             case to_char(responsiblesignaturedate,'DD') when '22' then 1 else 0 end D22,
                             case to_char(responsiblesignaturedate,'DD') when '23' then 1 else 0 end D23,
                             case to_char(responsiblesignaturedate,'DD') when '24' then 1 else 0 end D24,
                             case to_char(responsiblesignaturedate,'DD') when '25' then 1 else 0 end D25,
                             case to_char(responsiblesignaturedate,'DD') when '26' then 1 else 0 end D26,
                             case to_char(responsiblesignaturedate,'DD') when '27' then 1 else 0 end D27,
                             case to_char(responsiblesignaturedate,'DD') when '28' then 1 else 0 end D28,
                             case to_char(responsiblesignaturedate,'DD') when '29' then 1 else 0 end D29,
                             case to_char(responsiblesignaturedate,'DD') when '30' then 1 else 0 end D30,
                             case to_char(responsiblesignaturedate,'DD') when '31' then 1 else 0 end D31
                        from bil_mon.vw_incidentes vv
                       where vv.PAWSVCAUTHUSERSRESPSIGFULLNAME in ('Daviu Arevalo, Julio',
                                                                   'Duran, Jalir',
                                                                   'Gallinate, Juan',
                                                                   'Maldonado, Gustavo',
                                                                   'Torres, Rodrigo',
                                                                   'Velasco, Miguel')
                         and vv.STATUS not in (0,2)
                         and to_char(vv.responsiblesignaturedate,'YYYYMM')='$periodo'
                      order by vv.CREATIONDATE asc)
                      group by especialista
                      order by especialista asc";
    return $this->app['dbs']['itsm']->fetchAll($sqlCerradosDia);
  }

  public function getTicketsCliInt()
  {
    $sqlTicketsCliInt="SELECT ESP,
                              count(*) cant,
                              min(open_time) mas_antiguo,
                              round(sysdate - min(open_time), 2) demora_dias
                         from hpsm.hpsc_iteracion@hpsmprod
                        where GRUPO = 'Soporte Facturacion'
                          and ESTADO = 'Open - Idle'
                        group by ESP
                        order by min(open_time)";
    return $this->app['dbs']['scenter']->fetchAll($sqlTicketsCliInt);
  }
  public function getTicketsCall()
  {
    $sqlTicketsCall="SELECT esp,
                            count(*) cant,
                            min(apertura) mas_antiguo,
                            round(sysdate - min(apertura), 2) demora_dias
                       from hpsm.hpsc_incidentes@hpsmprod
                      where ESTADO = 'open'
                        and grupo = 'Soporte Facturacion'
                      group by esp
                      order by min(apertura)";
    return $this->app['dbs']['scenter']->fetchAll($sqlTicketsCall);
  }
  /**
   * obtener cumplimiento de KPI por subgerencia
   * @param  string $periodo     periodo a evaluar en formato YYYYMM
   * @param  string $subgerencia subgenerencia a mostra, '' todas
   * @return array              data de resultados
   */
  public function getKPIincidentes($periodo,$subgerencia,$esp)
  {
    //echo $periodo.'|'.count($periodo).'|'.(strpos($periodo,' - ')+3);
    $where = (strlen ($periodo) <= 10)
           ? " where trunc(nvl(vv.respuesta_esp,sysdate)) =  to_date('$periodo','mm-dd-yyyy')"
           : " where nvl(vv.respuesta_esp,sysdate) between
                          to_date('".substr($periodo, 0,strpos($periodo,' - '))."','mm-dd-yyyy') and
                          to_date('".substr($periodo, (strpos($periodo,' - ')+3))."','mm-dd-yyyy')+1" ;
    $where .= ($subgerencia<>'all') ? " and subgerencia='$subgerencia'" : null ;
    $groupEsp = ($esp==1) ? ',esp' : null ;
    $sqlKPIincidentes="SELECT subgerencia
                              $groupEsp,
                              count(*) total,
                              sum(exp) demorados,
                              count(*) - sum(exp) cumplidos,
                              round(sum(exp) / count(*) * 100, 2) por_demorados,
                              round((count(*) - sum(exp)) / count(*) * 100, 2) por_cumplidos
                         from (select subgerencia,
                                      esp,
                                      case
                                       when exp=0 then 0
                                       else 1
                                       end exp
                                 from bil_mon.vw_incidentes_bill vv
                               $where
                        )
                        group by subgerencia$groupEsp
                        order by por_demorados desc";
    return  $this->app['dbs']['itsm']->fetchAll($sqlKPIincidentes);
  }
  /**
   * obtener los incidentes segun criterio ingresado
   * @param  string $subgerencia subgenrecia
   * @param  string $grupo       grupo, mesa
   * @param  string $esp         especialista
   * @param  string $vencido     mostrar vencidos?
   * @param  string $periodo     periodo de fechas a mostrar
   * @param  string $estado      filtro de estados
   * @return array              data resultante
   */
  public function getIncidentesDetalle($subgerencia='all',$grupo='all',$esp='all',$vencido='all',$periodo='all',$estado='all')
  {
    $wherePeriodo = (strlen ($periodo) === 10)
           ? " and trunc(nvl(vv.respuesta_esp,sysdate)) =  to_date('$periodo','mm-dd-yyyy')"
           : " and trunc(nvl(vv.respuesta_esp,sysdate)) between
                          to_date('".substr($periodo, 0,strpos($periodo,' - '))."','mm-dd-yyyy') and
                          to_date('".substr($periodo, (strpos($periodo,' - ')+3))."','mm-dd-yyyy')+1" ;

    $whereSubgerencia = ($subgerencia<>'all') ? " and vv.subgerencia='$subgerencia'" : null ;
    $whereGrupo = ($grupo<>'all') ? " and vv.grupo='$grupo'" : null ;
    $whereEsp = ($esp<>'all') ? " and vv.esp='$esp'" : null ;
    if ($vencido<>'all') {
      if ($vencido==0) {
        $whereVencido = " and vv.exp=$vencido";
      } else {
        $whereVencido = " and vv.exp<>0";
      }
     }else {
      $whereVencido = null;
    }
    $whereEstado = ($estado<>'all') ? " and vv.estado='$estado'" : null ;

    $sqlIncidentesDetalle="SELECT vv.sla_horas sla,
                                  vv.esp,
                                  vv.codigo,
                                  vv.titulo,
                                  vv.creacion,
                                  vv.vencimiento,
                                  vv.venc expirado,
                                  vv.estado,
                                  vv.subestado,
                                  vv.grupo,
                                  vv.subgerencia
                             from bil_mon.vw_incidentes_bill vv
                            where 1=1
                            $whereSubgerencia
                            $whereGrupo
                            $whereEsp
                            $whereVencido
                            $wherePeriodo
                            $whereEstado
                            order by vv.vencimiento asc";

    return  $this->app['dbs']['itsm']->fetchAll($sqlIncidentesDetalle);
  }

  /**
   * obtener cumplimiento de KPI por subgerencia
   * @param  string $periodo     periodo a evaluar en formato YYYYMM
   * @param  string $subgerencia subgenerencia a mostra, '' todas
   * @return array              data de resultados
   */
  public function getKPIproblemas($periodo,$subgerencia,$esp)
  {
    //echo $periodo.'|'.count($periodo).'|'.(strpos($periodo,' - ')+3);
    $where = (strlen ($periodo) <= 10)
           ? " where trunc(nvl(vv.modificacion,sysdate)) =  to_date('$periodo','mm-dd-yyyy')"
           : " where nvl(vv.modificacion,sysdate) between
                          to_date('".substr($periodo, 0,strpos($periodo,' - '))."','mm-dd-yyyy') and
                          to_date('".substr($periodo, (strpos($periodo,' - ')+3))."','mm-dd-yyyy')+1" ;
    $where .= ($subgerencia<>'all') ? " and subgerencia='$subgerencia'" : null ;
    $groupEsp = ($esp==1) ? ',esp' : null ;
    $sqlKPIproblemas="SELECT subgerencia
                              $groupEsp,
                              count(*) total,
                              sum(exp) demorados,
                              count(*) - sum(exp) cumplidos,
                              round(sum(exp) / count(*) * 100, 2) por_demorados,
                              round((count(*) - sum(exp)) / count(*) * 100, 2) por_cumplidos
                         from (select subgerencia,
                                      asignado_a esp,
                                      exp,
                                      codigo
                                 from bil_mon.vw_problemas_bill vv
                               $where
                        )
                        group by subgerencia$groupEsp
                        order by por_demorados desc";
    return  $this->app['dbs']['itsm']->fetchAll($sqlKPIproblemas);
  }

  /**
   * obtener los problemas segun criterio ingresado
   * @param  string $subgerencia subgenrecia
   * @param  string $grupo       grupo, mesa
   * @param  string $esp         especialista
   * @param  string $vencido     mostrar vencidos?
   * @param  string $periodo     periodo de fechas a mostrar
   * @param  string $estado      filtro de estados
   * @return array              data resultante
   */
  public function getProblemasDetalle($subgerencia='all',$grupo='all',$esp='all',$vencido='all',$periodo='all',$estado='all')
  {
    $wherePeriodo = (strlen ($periodo) === 10)
           ? " and trunc(nvl(vv.modificacion,sysdate)) =  to_date('$periodo','mm-dd-yyyy')"
           : " and trunc(nvl(vv.modificacion,sysdate)) between
                          to_date('".substr($periodo, 0,strpos($periodo,' - '))."','mm-dd-yyyy') and
                          to_date('".substr($periodo, (strpos($periodo,' - ')+3))."','mm-dd-yyyy')+1" ;

    $whereSubgerencia = ($subgerencia<>'all') ? " and vv.subgerencia='$subgerencia'" : null ;
    $whereGrupo = ($grupo<>'all') ? " and vv.grupo='$grupo'" : null ;
    $whereEsp = ($esp<>'all') ? " and vv.asignado_a='$esp'" : null ;
    if ($vencido<>'all') {
      if ($vencido==0) {
        $whereVencido = " and vv.exp=$vencido";
      } else {
        $whereVencido = " and vv.exp<>0";
      }
     }else {
      $whereVencido = null;
    }
    $whereEstado = ($estado<>'all') ? " and vv.estado='$estado'" : null ;

    $sqlProblemasDetalle="SELECT vv.sla,
                                  vv.asignado_a esp,
                                  vv.codigo,
                                  vv.titulo,
                                  vv.creacion,
                                  vv.vencimiento,
                                  vv.venc expirado,
                                  vv.estado,
                                  vv.grupo,
                                  vv.subgerencia
                             from bil_mon.vw_problemas_bill vv
                            where 1=1
                            $whereSubgerencia
                            $whereGrupo
                            $whereEsp
                            $whereVencido
                            $wherePeriodo
                            $whereEstado
                            order by vv.vencimiento asc";

    return  $this->app['dbs']['itsm']->fetchAll($sqlProblemasDetalle);
  }
}
