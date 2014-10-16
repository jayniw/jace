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
  }
  /**
   * obtiene los registros por estado del proceso de generaicon de imagen
   *
   * @return array
   */
  public function getImagenProcess()
  {
    $sqlImagenProcess="SELECT Cola, Estado, Cant, Round(Cant * 100 / Tot, 2) por
                         From (Select Cola,
                                      a.Estado,
                                      Count(*) Cant,
                                      (Select Count(*)
                                         From billing.Bl_Tmp_Imagen_Facturacion
                                        Where Cola = a.Cola) Tot
                                 From billing.Bl_Tmp_Imagen_Facturacion a
                                Group By Cola, Estado
                                Order By 2,1)";
    return $this->app['db']->fetchAll($sqlImagenProcess);
  }
  /**
   * obtiene la demora de los jobs de imagen
   *
   * @return array
   */
  public function getImagenProcessDem()
  {
    $sqlImagenProcessDem="SELECT max( round(((sysdate-THIS_DATE)*60*24),2))dem,
                                 to_char(sysdate,'dd.mm.yyyy hh24:mi:ss') ahora
                            from user_jobs
                           where lower(what) like '%crear_imagen%'";
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
    $sqlResumenProcess="SELECT Estado,
                               Count(*) Cant,
                               Round((Count(*) * 100) /
                                     (SELECT COUNT(*)
                                        FROM billing.bl_imagen_grupo
                                       where cod_periodo = '$periodo'),
                                     2) Por
                          From billing.Bl_Imagen_Grupo g
                         Where Cod_Periodo = '$periodo'
                         Group By Estado
                         Order By 2 asc";
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
  public function getResumenProcessDem()
  {
    $sqlResumenProcessDem="SELECT max( round(((sysdate-THIS_DATE)*60*24),2))dem,
                                 to_char(sysdate,'dd.mm.yyyy hh24:mi:ss') ahora
                            from user_jobs
                           where lower(what) like '%generar_resumen%'";
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
}
