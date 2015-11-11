<?php
/**
 * archivo que tiene la definicion de la clase monitor
 *
 * PHP version 5.4.3
 *
 * @category File
 * @package  Cliente
 * @author   Jalir Duran <jalir.duran@nuevatel.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://10.40.4.20/bill/index_dev.php/
 *
 */
namespace Cliente;

/**
 * clase que tiene las funciones para el modulo de monitores
 *
 * @category Class
 * @package  Cliente
 * @author   Jalir Duran <jalir.duran@nuevatel.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://10.40.4.20/bill/index_dev.php/
 *
 */
class Cliente
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

  public function getUnidad($idUnidad)
  {
    $sqlUnidad="SELECT id_grupo,
                       name,
                       vname,
                       cod_layout,
                       layout,
                       estado,
                       id_cug,
                       aux_1,
                       limite_credito cred,
                       limite_consumo cons,
                       to_char(fecha_creacion,'dd-mm-yyyy hh24:mi:ss')fecha_creacion,
                       to_char(fecha_transaccion,'dd-mm-yyyy hh24:mi:ss') fecha_transaccion
                  from billing.cl_unidad
                 where id_unidad = $idUnidad
                union all
                select id_grupo,
                       name,
                       vname,
                       cod_layout,
                       layout,
                       estado,
                       id_cug,
                       aux_1,
                       limite_credito cred,
                       limite_consumo cons,
                       to_char(fecha_creacion,'dd-mm-yyyy hh24:mi:ss') fecha_creacion,
                       to_char(fecha_transaccion,'dd-mm-yyyy hh24:mi:ss') fecha_transaccion
                  from billing.cl_unidad_vpn
                 where id_unidad = $idUnidad";
    return $this->app['db']->fetchAll($sqlUnidad);
  }
  public function getUnidadHist($idUnidad)
  {
    $sqlUnidadHist="SELECT id_grupo,
                           name,
                           vname,
                           cod_layout,
                           layout,
                           estado,
                           id_cug,
                           aux_1,
                           limite_credito lcre,
                           limite_consumo lcon,
                           to_char(fecha_transaccion,'dd-mm-yyyy hh24:mi:ss') fecha_transaccion,
                           fecha_inicio,
                           fecha_fin fecha_hasta,
                           ultimo,
                           cod_usuario
                      from billing.cl_unidad_vpn_hist
                     where id_unidad = $idUnidad
                    union all
                    select id_grupo,
                           name,
                           vname,
                           cod_layout,
                           layout,
                           estado,
                           id_cug,
                           aux_1,
                           limite_credito lcre,
                           limite_consumo lcon,
                           to_char(fecha_transaccion,'dd-mm-yyyy hh24:mi:ss') fecha_transaccion,
                           fecha_inicio,
                           fecha_fin,
                           ultimo,
                           cod_usuario
                      from billing.cl_unidad_hist
                     where id_unidad = $idUnidad
                     order by fecha_inicio desc";
    return $this->app['db']->fetchAll($sqlUnidadHist);
  }

  public function getUnidadEstadosHist($idUnidad)
  {
    $sqlUnidadEstadosHist="SELECT estado_grupo,
                                  estado_unidad_col,
                                  estado_unidad_crm,
                                  estado_final,
                                  fecha_creacion,
                                  fecha_transaccion,
                                  ultimo,
                                  cod_usuario
                             from billing.cl_unidad_estado_historico_vpn
                            where id_unidad = $idUnidad
                           union all
                           select estado_grupo,
                                  estado_unidad_col,
                                  estado_unidad_crm,
                                  estado_final,
                                  fecha_creacion,
                                  fecha_transaccion,
                                  ultimo,
                                  cod_usuario
                             from billing.cl_unidad_estado_historico
                            where id_unidad = $idUnidad
                            order by fecha_creacion desc";
    return $this->app['db']->fetchAll($sqlUnidadEstadosHist);
  }
  /**
   * OBTENTER HISTORICO DE PLANES
   * @param  number $idUnidad id de la unidad a buscar
   * @return array           data del historico
   */
  public function getUnidadLayout($idUnidad)
  {
    $sqlUnidadLayout="SELECT id_unidad,
                             layout,
                             cod_layout,
                             fecha_inicio,
                             fecha_fin,
                             cod_usuario,
                             fecha_transaccion
                        from billing.tr_cambio_cod_layout t
                       where t.id_unidad = $idUnidad
                       order by fecha_inicio desc";
    return $this->app['db']->fetchAll($sqlUnidadLayout);
  }
  /**
   * obtiene los datos del grupo de la cl_grupo
   * @param  number $idGrupo Id del grupo
   * @return array          data del grupo
   */
  public function getGrupo($idGrupo)
  {
    $sqlGrupo="SELECT g.id_cliente,
                      g.id_persona,
                      (select df.id_cobranza
                         from billing.cl_datos_facturacion df
                        where df.id_grupo=g.id_grupo) id_cobranza,
                      g.estado,
                      g.estado_collections col,
                      g.cod_ciudad,
                      g.facturable f,
                      g.id_cta_corriente,
                      to_char(g.fecha_creacion,'dd-mm-yyyy hh24:mi:ss') fecha_creacion,
                      to_char(g.fecha_transaccion,'dd-mm-yyyy hh24:mi:ss') fecha_transaccion
                 from billing.cl_grupo g
                where g.id_grupo = $idGrupo";
    return $this->app['db']->fetchAll($sqlGrupo);
  }
  /**
   * obtiene la data para un grupo en su historico
   * @param  number $idGrupo id del grupo
   * @return array          data obtenida
   */
  public function getGrupoHist($idGrupo)
  {
    $sqlGrupoHist="SELECT g.id_cliente,
                          g.id_persona,
                          g.estado,
                          g.estado_collections col,
                          g.cod_ciudad,
                          g.facturable f,
                          g.id_cta_corriente,
                          to_char(g.fecha_transaccion,'dd-mm-yyyy hh24:mi:ss') fecha_transaccion,
                          to_char(g.fecha_inicio,'dd-mm-yyyy hh24:mi:ss') fecha_inicio,
                          to_char(g.fecha_fin,'dd-mm-yyyy hh24:mi:ss') fecha_fin,
                          g.ultimo,
                          g.cod_usuario
                     from billing.cl_grupo_hist g
                    where g.id_grupo = $idGrupo
                    order by g.fecha_inicio desc";
    return $this->app['db']->fetchAll($sqlGrupoHist);
  }
  /**
   * obtener data del grupo si tiene id_cug o es un id_cug
   * @param  number $idGrupo id del grupo
   * @return array          data resultante
   */
  public function getSdGrupo($idGrupo)
  {
    $sqlSdGrupo="SELECT t.id_grupo,
                        t.id_cug,
                        t.id_grupo_layout,
                        t.cod_layout,
                        to_char(t.fecha_creacion,'dd-mm-yyyy hh24:mi:ss') fecha_creacion,
                        to_char(t.fecha_transaccion,'dd-mm-yyyy hh24:mi:ss') fecha_transaccion,
                        t.cod_usuario
                   from billing.cl_sdgrupo t
                  where t.id_grupo = $idGrupo
                     or t.id_cug = $idGrupo
                  order by id_grupo, id_cug";
    return $this->app['db']->fetchAll($sqlSdGrupo);
  }
}
