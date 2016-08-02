<?php
/**
 * archivo que tiene la definicion de la clase facturacion
 *
 * PHP version 5.4.3
 *
 * @category File
 * @package  Facturacion
 * @author   Jalir Duran <jalir.duran@nuevatel.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://10.40.4.20/bill/index_dev.php/
 *
 */
namespace Facturacion;

 /**
 * Clase para el manejo de la facturacion por periodo.
 *
 * @category PHP
 * @package  BILLING_INTERFAZ
 * @author   Jalir Duran <jalir.duran@nuevatel.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://10.40.4.20/bill/index_dev.php/
 *
 */
class Facturacion
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
    $nlsDateFormat=$this->app['db']->prepare($sqlNlsDateFormat);
    $nlsDateFormat->execute();
  }
  /**
   * obtiene los registros por estado del proceso de generaicon de imagen
   *
   * @return array
   */
  public function getImagenProcess($periodo)
  {
    $sqlImagenProcess="SELECT cola,total,pen,ok,error from table(itjduran.pck_facturacion.info_imagen('$periodo'))";
    return $this->app['db']->fetchAll($sqlImagenProcess);
  }
  /**
   * obtiene la demora de los jobs de imagen
   *
   * @return array
   */
  public function getImagenProcessDem($periodo)
  {
    $sqlImagenProcessDem="SELECT proceso,
                                 inicio,
                                 fin,
                                 itjduran.format_time((nvl(fin, sysdate)-inicio) * 24) demora
                            from itjduran.log_proc_time
                           where lower(proceso) like ('%imagen%')
                             and to_char(created_at, 'yyyymm') = '$periodo'
                           order by id";
    return $this->app['db']->fetchAll($sqlImagenProcessDem);
  }
  /**
   * procesar resumen de un periodo, avance
   *
   * @param string $periodo codgio de periodo
   *
   * @return array
   */
  public function getResumenProcess($periodo)
  {
    $sqlResumenProcess="SELECT cola,total,pen,ok,error from table(itjduran.pck_facturacion.info_resumen('$periodo'))";
    return $this->app['db']->fetchAll($sqlResumenProcess);
  }
  /**
   * procesar resumen de un periodo, avance por estado y cola
   *
   * @param string $periodo periodo de facturacion
   * @param string $estado  estado del registro
   *
   * @return array
   */
  public function getResumenProcessEstado($periodo, $estado)
  {
    $sqlResumenProcessEstado="SELECT x.*,
                                     Round((cant * 100) /
                                           ((SELECT COUNT(*)
                                               FROM billing.bl_imagen_grupo
                                              where cod_periodo = '$periodo') / 20),
                                           2) Por
                                from (SELECT Cola,  Count(*) Cant
                                        From billing.Bl_Imagen_Grupo
                                       Where Cod_Periodo = '$periodo'
                                         and estado = '$estado'
                                       Group By Cola) x
                               Order By 1 asc";
    return $this->app['db']->fetchAll($sqlResumenProcessEstado);
  }
  /**
   * Demora del proceso de generaicon resumen
   *
   * @return array resultado de la consulta
   */
  public function getResumenProcessDem($periodo)
  {
    $sqlResumenProcessDem="SELECT proceso,
                                 inicio,
                                 fin,
                                 itjduran.format_time((nvl(fin, sysdate)-inicio) * 24) demora
                            from itjduran.log_proc_time
                           where lower(proceso) like ('%resumen%')
                             and to_char(created_at, 'yyyymm') = '$periodo'
                           order by id desc";
    return $this->app['db']->fetchAll($sqlResumenProcessDem);
  }
  /**
   * obtener los datos de la imagen de un grupo de un periodo
   *
   * @param number $grupo   id_comunidad, id_grupo
   * @param string $periodo periodo a evaluar
   *
   * @return array          data devuelta
   */
  public function getImagenGrupo($grupo, $periodo)
  {
    $sqlImagenGrupo="SELECT ID_CLIENTE,
                            ID_COBRANZA,
                            ESTADO_GRUPO as E,
                            FACTURABLE as f,
                            CANTIDAD_UNIDADES as unidades,
                            COLA,
                            ESTADO,
                            COD_ERROR,
                            MENSAJE_ERROR,
                            COD_USUARIO as usr,
                            to_char(FECHA_TRANSACCION,'dd.mm.yyyy hh24:mi:ss') fecha
                       from billing.bl_imagen_grupo
                      where cod_periodo='$periodo'
                        and ID_GRUPO =$grupo";
    return $this->app['db']->fetchAll($sqlImagenGrupo);
  }
  /**
   * Obtener el detalle de unidades de la imagen de un grupo en un periodo
   *
   * @param number  $grupo   id de grupo
   * @param varchar $periodo codioo de periodo
   *
   * @return array          resultado de la busqueda
   */
  public function getImagenUnidadGrupo($grupo, $periodo)
  {
    $sqlImagenUnidadGrupo="SELECT ID_CLIENTE,
                                  ID_COBRANZA,
                                  ID_UNIDAD,
                                  NAME,
                                  LAYOUT,
                                  COD_LAYOUT,
                                  ESTADO_UNIDAD as E,
                                  FACTURABLE as F,
                                  LIMITE_CREDITO as LCRE,
                                  LIMITE_CONSUMO as LCON,
                                  id_cta_corriente as cta_corriente,
                                  COD_USUARIO as usr,
                                  to_char(FECHA_TRANSACCION,
                                         'dd.mm.yyyy hh24:mi:ss') fecha
                             from billing.bl_imagen_unidad
                            where cod_periodo='$periodo'
                              and ID_GRUPO =$grupo";
    return $this->app['db']->fetchAll($sqlImagenUnidadGrupo);
  }
  /**
   * Obtener el resumen de facturacion de un grupo en un periodo
   *
   * @param integer $grupo   id de grupo
   * @param varchar $periodo codigo de periodo
   *
   * @return array          resultado de la busqueda
   */
  public function getResumenGrupo($grupo, $periodo)
  {
    $sqlResumenGrupo="SELECT ID_CLIENTE,
                             ID_CARGO as cargo,
                             (select descripcion
                                from billing.CF_CARGO
                               where ID_CARGO=rg.ID_CARGO) as descripcion_cargo,
                             rg.cod_carrier carrier,
                             CANTIDAD as cant,
                             to_char(SALDO,'FM9999990.00') as imp,
                             COD_USUARIO as usr,
                             to_char(FECHA_TRANSACCION,'dd.mm.yyyy hh24:mi:ss') fecha
                        from billing.BL_resumen_grupo rg
                       where cod_periodo='$periodo'
                         and id_grupo=$grupo
                       order by cargo ";
    return $this->app['db']->fetchAll($sqlResumenGrupo);
  }
  /**
   * Obtener el detalle de unidades del resumen de un grupo en un periodo
   *
   * @param number  $grupo   id de grupo
   * @param varchar $periodo codigo de periodo
   *
   * @return array          resultado de la busqueda
   */
  public function getResumenUnidadGrupo($grupo, $periodo)
  {
    $sqlResumenUnidadGrupo="SELECT ID_CLIENTE,
                                   ID_UNIDAD,
                                   ID_CARGO as cargo,
                                   (select descripcion
                                      from billing.CF_CARGO
                                     where ID_CARGO=ru.ID_CARGO) descripcion_cargo,
                                   ru.cod_carrier CARRIER,
                                   LAYOUT,
                                   COD_LAYOUT,
                                   CANTIDAD as cant,
                                   to_char(IMPORTE,'FM9999990.00') as imp,
                                   COD_USUARIO as usr,
                                   to_char(FECHA_TRANSACCION,
                                          'dd.mm.yyyy hh24:mi:ss') fecha
                              from billing.BL_resumen_unidad ru
                             where cod_periodo='$periodo'
                               and ID_GRUPO =$grupo";
    return $this->app['db']->fetchAll($sqlResumenUnidadGrupo);
  }
  /**
   * Obtener el detalle de univ_factura
   *
   * @param number  $grupo   id de grupo
   * @param varchar $periodo codigo de periodo
   *
   * @return array          resultado de la busqueda
   */
  public function getUnivFact($grupo, $periodo)
  {
    $sqlUnivFact="SELECT ID_CLIENTE,
                         ID_CTA_CORRIENTE,
                         to_char(SUMA_NEGATIVOS,'FM9999990.00') as NEG,
                         to_char(SUMA_POSITIVOS,'FM9999990.00') as POS,
                         to_char(TOTAL_CARRIER,'FM9999990.00') as CARRIER,
                         to_char(TOTAL_DEUDA,'FM9999990.00') as DEUDA,
                         to_char(TOTAL_FACTURAR,'FM9999990.00') as TOTAL,
                         COD_USUARIO as usr,
                         COLA,
                         to_char(FCH_PROCESADO,'dd.mm.yyyy hh24:mi:ss') as fecha
                    from billing.BL_UNIVERSO_FACTURA
                   where cod_periodo='$periodo' and id_grupo=$grupo";
    return $this->app['db']->fetchAll($sqlUnivFact);
  }
  /**
   * Obtener el detalle de bl_factura
   *
   * @param number  $grupo   id de grupo
   * @param varchar $periodo codigo de periodo
   *
   * @return array          resultado de la busqueda
   */
  public function getBlFactura($grupo, $periodo)
  {
    $sqlBlFactura="SELECT ID_CLIENTE,
                          ID_DOCUMENTO,
                          ESTADO,
                          to_char(FECHA_EMISION,'dd.mm.yyyy hh24:mi:ss') as EMISION,
                          to_char(IMPORTE_CREDITO_FISCAL,'FM9999990.00') as IMPORTE,
                          to_char(FECHA_PAGO,'dd.mm.yyyy hh24:mi:ss') as PAGO,
                          COD_USUARIO as usr,
                          to_char(FECHA_TRANSACCION,'dd.mm.yyyy hh24:mi:ss') as fecha
                     from billing.bl_factura
                    where id_grupo=$grupo
                      and cod_periodo='$periodo'
                    order by FECHA_TRANSACCION";
    return $this->app['db']->fetchAll($sqlBlFactura);
  }
  /**
   * obtener el id_cobranza del grupo
   *
   * @param number $grupo ID DEL GRUPO
   *
   * @return number        id de cobranza
   */
  public function getIdCobranza($grupo)
  {
    $sqlIdCobranza="SELECT ID_COBRANZA
                      from billing.CL_DATOS_FACTURACION
                     where id_grupo=$grupo";
    $idCobranza=$this->app['db']->fetchAll($sqlIdCobranza);
    if (count($idCobranza)>0) {
      return $idCobranza[0]['ID_COBRANZA'];
    } else {
      return 0;
    }
  }
  /**
   * obtener datos de factura en CTL para un grupo y periodo
   *
   * @param number $grupo   id del grupo
   * @param string $periodo codigo de periodo
   *
   * @return array          factura del grupo
   */
  public function getCtlFactura($grupo, $periodo)
  {
    $idCobranza=$this->getIdCobranza($grupo);
    $sqlCtlFactura="SELECT Periodo,
                           Cod_Cliente,
                           Imp_1,
                           Imp_7,
                           Imp_Local,
                           Nvl(Nvl(Imp_Gral, 0) + Nvl(Imp_Gral_Entel, 0) +
                               Nvl(Imp_Boliviatel, 0) + Nvl(Imp_Telecel, 0) +
                               Nvl(Imp_Teledata, 0) + Nvl(Imp_Nuevatel_Ld, 0) +
                               Nvl(Imp_Its, 0)+Nvl(Imp_Cotel, 0)+Nvl(Imp_Unete, 0) +
                               Nvl(Imp_Bossnet, 0) + Nvl(Imp_Bolitel, 0) +
                               Nvl(Imp_Transmedes, 0), 0) Imp_Ld,
                           Estado,
                           Nro_Orden,
                           Nro_Fact,
                           Cod_Control_Sin,
                           Fecha_Emision
                      From Ctl.Factura
                     Where Cod_Cliente = $idCobranza
                       And Periodo = '$periodo'";
    return $this->app['db']->fetchAll($sqlCtlFactura);
  }
  /**
   * Obtener la lista de solicitudes de refacturacion del periodo
   *
   * @param string $periodo Periodo a evaluar
   *
   * @return array data con los resultados
   */
  public function getRefactPeriodo($periodo)
  {
    $sqlRefactPeriodo="SELECT t.id_grupo,
                              t.solicitante,
                              to_char(fecha, 'dd.mm.yyyy hh24:mi:ss') fch_solicitud,
                              t.motivo,
                              to_char(t.fecha_resp, 'dd.mm.yyyy hh24:mi:ss') fch_respuesta,
                              respuesta
                         from billing.refacturar t
                        where t.cod_periodo = '$periodo'
                        order by fecha desc";
    return $this->app['db']->fetchAll($sqlRefactPeriodo);
  }
  /**
   * Registrar un grupo a refacturar
   *
   * @param number $grupo   id del grupo a refacturar
   * @param string $motivo  motivo por le cual se requiere refacturar
   * @param string $periodo periodo a refacturar
   */
  public function setRefactGrupo($grupo, $motivo, $periodo, $usuario)
  {
    $smtRefactGrupo="INSERT into billing.refacturar
                            (id_grupo, motivo, fecha, solicitante, cod_periodo)
                          values
                            ($grupo, '$motivo', sysdate, '$usuario', '$periodo')";
    $count = $this->app['db']->executeUpdate($smtRefactGrupo);
    return $count;
  }
  /**
   * Obtener el prog de procesos ejecutados y su tiempo de duracion
   * @return array data de logs del periodo
   */
  public function getLogProgTime()
  {
    $sqlLogProgTime="SELECT t.proceso,
                            to_char(t.inicio, 'dd.mm.yyyy hh24:mi:ss') inicio,
                            to_char(t.fin, 'dd.mm.yyyy hh24:mi:ss') fin,
                            itjduran.format_time(round((nvl(t.fin,sysdate)-t.inicio)*24,2)) demora
                       from itjduran.log_proc_time t
                      where t.created_at >= trunc(sysdate - 1)
                      order by id desc";
    return  $this->app['db']->fetchAll($sqlLogProgTime);
  }
  /**
   * Obtiene la cantidad de abonos de un periodo para un grupo plan
   * @param  string $periodo   cod_periodo
   * @param  string $grupoPlan cod_grupo_plan, cod_clasificacion
   * @return integer            cantidad de abonos
   */
  public function getTendenciaAbonoGrupoPlan($periodo,$grupoPlan)
  {
    $sqlTendenciaAbonoGrupoPlan="SELECT COUNT(unique name) as cant
                                   FROM billing.BL_ABONO
                                  WHERE cod_periodo = '$periodo'
                                    and grupo_plan = '$grupoPlan'
                                    AND estado_abono != 'ERR'";
    $data= $this->app['db']->fetchAll($sqlTendenciaAbonoGrupoPlan);
    return $data[0]['CANT'];
  }
  /**
   * obtener los grupo plan que se abonaron
   * @return array lista de grupo plan [cod_clasificacion]
   */
  public function getTendenciaGrupoPlan()
  {
    $sqlTendenciaGrupoPlan="SELECT distinct cod_clasificacion as grupo_plan
                              from billing.cf_cod_layout ll
                             where exists (select *
                                      from billing.BL_ABONO bb
                                     where bb.grupo_plan = ll.cod_clasificacion)
                               AND ll.cod_clasificacion NOT IN ('GPPPSR', 'GPPPSR4G')";
    return $this->app['db']->fetchAll($sqlTendenciaGrupoPlan);
  }
  /**
   * obtener las tendencias de los servicios
   * @param  string $periodo cod_periodo
   * @return number          cantidad de abonos por servicio en el periodo
   */
  public function getTendenciaAbonoServicios($periodo)
  {
    $sqlTendenciaAbonoServicios="SELECT COUNT(unique name) cantidad
                                   FROM billing.BL_ABONO_SERVICIO
                                  WHERE cod_periodo = '$periodo'
                                    AND estado_ser != 'ERR'";
    $data = $this->app['db']->fetchAll($sqlTendenciaAbonoServicios);
    return $data[0]['CANTIDAD'];
  }
  /**
   * obtener la tendencia de SR
   * @param  string $grupoPlan grupo_plan
   * @param  string $periodo   periodo
   * @return number            cantidad por grupo plab SR y periodo
   */
  public function getTendenciaAbonoSR($periodo,$grupoPlan)
  {
    $sqlTendenciaAbonoSR="SELECT COUNT(unique name) cantidad
                            FROM prvbill.BL_ABONO@odbprvbill
                           WHERE cod_periodo ='$periodo'
                             AND grupo_plan = '$grupoPlan'
                             AND cod_servicio !='COMBO_4G'
                             AND estado_abono != 'ERR'";
    $data = $this->app['db']->fetchAll($sqlTendenciaAbonoSR);
    return $data[0]['CANTIDAD'];
  }
  /**
   * monitoreo del proceso mora_nof
   * @return array data obtenida
   */
  public function infoMora_nof($periodo)
  {
    $sqlinfoMora_nof="SELECT * from table(itjduran.pck_facturacion.info_mora_nof('$periodo'))";
    return $this->app['db']->fetchAll($sqlinfoMora_nof);
  }
  /**
   * procesos de mora_nof ejecutados en el periodo
   * @param  string $periodo cod_periodo
   * @return array          data
   */
  public function procMora_nof($periodo)
  {
    $sqlProcMora_nof="SELECT substr(proceso,24) proceso,
                             inicio,
                             fin,
                             itjduran.format_time((nvl(fin, sysdate)-inicio) * 24) demora
                        from itjduran.log_proc_time
                       where lower(proceso) like ('%co_cmb_estado_col_bulk%')
                         and to_char(created_at, 'yyyymm') = '$periodo'
                       order by id";
    return $this->app['db']->fetchAll($sqlProcMora_nof);
  }
  /**
   * monitoreo de abonos
   * @param  string $periodo cod_periodo
   * @return array          data
   */
  public function infoAbonos($periodo)
  {
    $sqlInfoAbonos="SELECT cola,total,pen,ok,error from table(itjduran.pck_facturacion.info_abonos('$periodo'))";
    return $this->app['db']->fetchAll($sqlInfoAbonos);
  }

  public function procAbonos($periodo)
  {
    $sqlProcAbonos="SELECT proceso,
                           inicio,
                           fin,
                           itjduran.format_time((nvl(fin, sysdate)-inicio) * 24) demora
                      from itjduran.log_proc_time
                     where lower(proceso) like ('%abono%')
                       and created_at>=trunc(last_day(sysdate-5))
                     order by id";
    return $this->app['db']->fetchAll($sqlProcAbonos);
  }
  /**
   * obtener las tareas que no se hayan concluido
   * @return array data con las tareas no conlcuidas.
   */
  public function getTareasBitacora()
  {
    $sqlGetTareasBitacora="SELECT bb.id,
                                  tt.nombre,
                                  bb.responsable,
                                  tt.hora_fin,
                                  tt.dia_ejecucion,
                                  bb.fecha_inicio,
                                  bb.fecha_fin
                             from billing.bit_bitacora bb, billing.bit_tareas tt
                            where nvl(bb.fecha_fin, sysdate) between
                                  trunc(last_day(sysdate- 5)) and
                                  to_date('05-' || to_char(sysdate+1, 'mm-yyyy'),
                                          'DD-MM-YYYY HH24:MI:SS')
                               and bb.id_tarea = tt.id
                            order by tt.secuencia asc";
    return $this->app['db']->fetchAll($sqlGetTareasBitacora);
  }
  /**
   * registrar la fecha inicio/fin de la tarea en bitacora
   * @param number $id id de la bitacora
   * @return string respuesta del procedimiento almacenado.
   */
  public function setTareaBitacora($id,$usr,$ip)
  {
    $smtSetTareaBitacora="BEGIN billing.bit_pck_bitacora.registra_bitacora($id, '$usr', '$ip', ? ); END;";
    $spSetTareaBitacora=$this->app['db']->prepare($smtSetTareaBitacora);
    $spSetTareaBitacora->bindParam(1, $respSetTareaBitacora, \PDO::PARAM_STR, 4000);
    $spSetTareaBitacora->execute();
    return $respSetTareaBitacora;
  }
}
