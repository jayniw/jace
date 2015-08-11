<?php
/**
 * archivo que tiene la definicion de la clase monitor
 *
 * PHP version 5.4.3
 *
 * @category File
 * @package  Monitor
 * @author   Jalir Duran <jalir.duran@nuevatel.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://10.40.4.20/bill/index_dev.php/
 *
 */
namespace Monitor;

/**
 * clase que tiene las funciones para el modulo de monitores
 *
 * @category Class
 * @package  Monitor
 * @author   Jalir Duran <jalir.duran@nuevatel.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://10.40.4.20/bill/index_dev.php/
 *
 */
class Monitor
{
  /**
   * __construct
   *
   * @param object $app objeto aplicacion
   */
  public function __construct($app)
  {
    $this->app= $app;
  }
  public function getLogRedirect($fecha = null)
  {
    $cond='=';
    if (is_null($fecha)) {
      $sqlFecha = "SELECT to_date('01'||to_char(sysdate-5,'-mm-yyyy'),'dd-mm-yyyy hh24:mi:ss') as fecha
                     from dual";
      $dataFecha = $this->app['db']->fetchAll($sqlFecha);
      $fecha = $dataFecha[0]['FECHA'];
      $cond = '>=';
    }
    $sqlLogRedirect="SELECT to_char(t.created_at,'dd.mm.yyyy hh24:mi:ss') as FECHA,
                            t.id,
                            t.name,
                            t.id_unidad,
                            t.id_grupo,
                            t.id_grupo_new,
                            t.vpn,
                            t.crm_process_id,
                            t.crm_cod_err CRM_ERR,
                            t.prv_order_id,
                            t.prv_cod_err PRV_ERR,
                            t.bill_cod_err BILL_ERR,
                            t.usuario
                       FROM billing.log_redireccionamiento t
                      where trunc(t.created_at) $cond '$fecha'
                      order by id desc";
    return $this->app['db']->fetchAll($sqlLogRedirect);
  }
  /**
   * Monitor para encontrar los pagos que no tienen alguna categoria
   *
   * @return array cantidades y localidades por tipo de pago
   */
  public function getPagosSinCat()
  {
    /*localidad*/
    $sqlPagosSinCat="SELECT 'LOCALIDAD', 'CTL.PAGO', pp.cod_loc codigo,pp.cod_depto, count(*) cant
                        from ctl.pago pp
                       where pp.fch_pago between to_char(sysdate - 1, 'yyyymm') || '01' and
                             to_char(sysdate, 'yyyymmdd')
                         and not exists
                       (select * from ctl.localidad ll where ll.codigo = pp.cod_loc)
                       group by pp.cod_loc,pp.cod_depto
                      union all
                      select 'LOCALIDAD', 'CTL.PAGO_ADEL', pp.cod_loc codigo,pp.cod_depto, count(*) cant
                        from ctl.pago_adel pp
                       where pp.fch_pago between to_char(sysdate - 1, 'yyyymm') || '01' and
                             to_char(sysdate, 'yyyymmdd')
                         and not exists
                       (select * from ctl.localidad ll where ll.codigo = pp.cod_loc)
                       group by pp.cod_loc,pp.cod_depto
                      union all
                      select 'LOCALIDAD', 'CTL.PAGO_ADEL', pp.cod_loc codigo,pp.cod_depto, count(*) cant
                        from ctl.pago_prepago pp
                       where pp.fch_pago between to_char(sysdate - 1, 'yyyymm') || '01' and
                             to_char(sysdate, 'yyyymmdd')
                         and not exists
                       (select * from ctl.localidad ll where ll.codigo = pp.cod_loc)
                       group by pp.cod_loc,pp.cod_depto
                      union all
                      select 'INSTITUCION',
                             'CTL.PAGO',
                             pp.cod_inst codigo,
                             pp.tipo_pago,
                             count(*) cant
                        from ctl.pago pp
                       where pp.fch_pago between to_char(sysdate - 1, 'yyyymm') || '01' and
                             to_char(sysdate, 'yyyymmdd')
                         and not exists (SELECT *
                                FROM ctl.instituciones_sf ii
                               where ii.codigo = pp.cod_inst
                                 and ii.tipo_pago = pp.tipo_pago)
                       group by pp.cod_inst, pp.tipo_pago
                      union all
                      select 'INSTITUCION',
                             'CTL.PAGO_ADEL',
                             pp.cod_inst codigo,
                             pp.tipo_pago,
                             count(*) cant
                        from ctl.pago_adel pp
                       where pp.fch_pago between to_char(sysdate - 1, 'yyyymm') || '01' and
                             to_char(sysdate, 'yyyymmdd')
                         and not exists (SELECT *
                                FROM ctl.instituciones_sf ii
                               where ii.codigo = pp.cod_inst
                                 and ii.tipo_pago = pp.tipo_pago)
                       group by pp.cod_inst, pp.tipo_pago
                      union all
                      select 'INSTITUCION',
                             'CTL.PAGO_PREPAGO',
                             pp.cod_inst codigo,
                             pp.tipo_pago,
                             count(*) cant
                        from ctl.pago_prepago pp
                       where pp.fch_pago between to_char(sysdate - 1, 'yyyymm') || '01' and
                             to_char(sysdate, 'yyyymmdd')
                         and not exists (SELECT *
                                FROM ctl.instituciones_sf ii
                               where ii.codigo = pp.cod_inst
                                 and ii.tipo_pago = pp.tipo_pago)
                       group by pp.cod_inst, pp.tipo_pago";

  }
}
