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
                               sum(h6_12) h6_12,
                               sum(h12_24) h12_24,
                               sum(h24_48) h24_48,
                               sum(h48_72) h48_72,
                               sum(h72_168) h72_168,
                               sum(h168) h168,
                               count(*) total
                          from (select x.grupo,
                                       x.demora,
                                       case when demora<6 then 1 else 0 end h6,
                                       case when demora between 6 and 12 then 1 else 0 end h6_12,
                                       case when demora between 12 and 24 then 1 else 0 end h12_24,
                                       case when demora between 24 and 48 then 1 else 0 end h24_48,
                                       case when demora between 48 and 72 then 1 else 0 end h48_72,
                                       case when demora between 72 and 168 then 1 else 0 end h72_168,
                                       case when demora>168 then 1 else 0 end h168
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
                         order by grupo asc";
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
                               where gb.NAME=t.grupo)";
        break;
      case null:
        $where =null;
        break;
      default:
        $where="and t.grupo like ('%$mesa%')";
        break;
    }
    $sqlGetIncidentesPenDet=" SELECT t.sla_horas sla,
                                     nvl(substr(t.usuario_asignado, 10), 'SIN ASIGNAR') esp,
                                     t.codigo,
                                     t.categoria,
                                     t.titulo,
                                     to_char(t.fecha_creacion,'dd.mm.yyyy hh24:mi:ss') creacion,
                                     to_char(fecha_creacion + sla_horas / 24,'dd.mm.yyyy hh24:mi:ss') vencimiento,
                                     round(sysdate - fecha_creacion, 2) * 24 dem,
                                     t.grupo,
                                     case
                                       when (fecha_creacion + sla_horas / 24) < sysdate then
                                        'VENCIDO'
                                       when (fecha_creacion + sla_horas / 24) between sysdate - 1 / 24 and
                                            sysdate then
                                        'POR VENCER'
                                       else
                                        'A TIEMPO'
                                     end estado,
                                     decode(tipo,0,'I',1,'R') tipo
                                from BIL_MON.VW_ALLINCIDENTS t
                               where t.estado = 'Asignada a un grupo'
                                 and t.subestado = 'En atención'
                                 $where
                               order by t.sla_horas asc, vencimiento asc";
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
                                  pp.pawSvcAuthUsersInvFullName
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
                          order by pp.creationDate--pp.pawSvcAuthGroupsName
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
    $sqlCerradosDia=" SELECT a.Usuario As Esp,
                             a.D01, a.D02, a.D03, a.D04, a.D05, a.D06, a.D07, a.D08,
                             a.D09, a.D10,
                             a.D11, a.D12, a.D13, a.D14, a.D15, a.D16, a.D17, a.D18,
                             a.D19, a.D20,
                             a.D21, a.D22, a.D23, a.D24, a.D25, a.D26, a.D27, a.D28,
                             a.D29, a.D30, a.D31,
                             (a.D01 + a.D02 + a.D03 + a.D04 + a.D05 + a.D06 + a.D07
                             +a.D08 + a.D09 + a.D10 + a.D11 + a.D12 + a.D13 + a.D14
                             +a.D15 + a.D16 + a.D17 + a.D18 + a.D19 + a.D20 + a.D21
                             +a.D22 + a.D23 + a.D24 + a.D25 + a.D26 + a.D27 + a.D28
                             +a.D29 + a.D30 + a.D31) As Total
                        From Hpsc_Rep_Closed_Month a
                       Where a.Fecha = '$periodo'
                         And Usuario In (".$this->app['esp'].")
                      UNION
                      SELECT 'TOTAL',
                             Sum(D01),Sum(D02),Sum(D03),Sum(D04),Sum(D05),Sum(D06),
                             Sum(D07),Sum(D08),Sum(D09),Sum(D10),Sum(D11),Sum(D12),
                             Sum(D13),Sum(D14),Sum(D15),Sum(D16),Sum(D17),Sum(D18),
                             Sum(D19),Sum(D20),Sum(D21),Sum(D22),Sum(D23),Sum(D24),
                             Sum(D25),Sum(D26),Sum(D27),Sum(D28),Sum(D29),Sum(D30),
                             Sum(D31),
                             (Sum(D01)+Sum(D02)+Sum(D03)+Sum(D04)+Sum(D05)+Sum(D06)
                             +Sum(D07)+Sum(D08)+Sum(D09)+Sum(D10)+Sum(D11)+Sum(D12)
                             +Sum(D13)+Sum(D14)+Sum(D15)+Sum(D16)+Sum(D17)+Sum(D18)
                             +Sum(D19)+Sum(D20)+Sum(D21)+Sum(D22)+Sum(D23)+Sum(D24)
                             +Sum(D25)+Sum(D26)+Sum(D27)+Sum(D28)+Sum(D29)+Sum(D30)
                             +Sum(D31)) As Total
                        From Hpsc_Rep_Closed_Month a
                       Where Fecha = '$periodo'
                         And Usuario In (".$this->app['esp'].")
                       Order By Total";
    return $this->app['dbs']['scenter']->fetchAll($sqlCerradosDia);
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
}
