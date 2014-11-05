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
    if (is_null($fecha)) {
      $sqlFecha = "SELECT sysdate as fecha from dual";
      $dataFecha = $this->app['db']->fetchAll($sqlFecha);
      $fecha = $dataFecha[0]['FECHA'];
    }
    $sqlLogRedirect="SELECT to_char(t.created_at,'hh24:mi:ss') as HORA,
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
                      where trunc(t.created_at)='$fecha'
                      order by id desc";
    return $this->app['db']->fetchAll($sqlLogRedirect);
  }
}
