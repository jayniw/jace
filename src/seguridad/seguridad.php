<?php
/**
 * archivo que tiene la definicion de la clase seguridad
 *
 * PHP version 5.4.3
 *
 * @category File
 * @package  Seguridad
 * @author   Jalir Duran <jalir.duran@nuevatel.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://10.40.4.20/bill/index_dev.php/
 *
 */
namespace Seguridad;

/**
 * clase que tiene las funciones para el modulo de seguridad
 *
 * @category Class
 * @package  Seguridad
 * @author   Jalir Duran <jalir.duran@nuevatel.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://10.40.4.20/bill/index_dev.php/
 *
 */
class Seguridad
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
  /**
   * Obtener rol correspondiente al usuario
   *
   * @param string $usr usuario logueado
   *
   * @return integer      id rol
   *
   */
  public function getRol($usr)
  {
    $sqlRol="SELECT id_rol
               from itjduran.bill_usuario
              where id_usuario='$usr'";
    $rol=$this->app['db']->fetchAll($sqlRol);
    if (count($rol)==0) {
      $rol[0]['ID_ROL']=0;
    }
    return $rol[0]['ID_ROL'];
  }
  /**
   * Obtener los roles configurados
   *
   * @return array roles configurados en el sistema
   */
  public function getRoles()
  {
    $sqlRoles="SELECT id,
                      descripcion,
                      estado,
                      to_char(created_at, 'dd.mm.yyyy hh24:mi:ss') fecha_creacion,
                      created_by creado_por,
                      to_char(updated_at,
                              'dd.mm.yyyy hh24:mi:ss') fecha_actualizacion,
                      updated_by actualizado_por
                 from itjduran.bill_rol
                order by estado,id";
    return $this->app['db']->fetchAll($sqlRoles);
  }
  /**
   * Obtener los menus de la aplicacion
   *
   * @return array menus de la aplicacion
   */
  public function getMenu()
  {
    $sqlMenu="SELECT id,
                     nombre,
                     ruta,
                     estado,
                     to_char(created_at, 'dd.mm.yyyy hh24:mi:ss') fecha_creacion,
                     created_by creado_por,
                     to_char(updated_at,
                             'dd.mm.yyyy hh24:mi:ss') fecha_actualizacion,
                     updated_by actualizado_por
                from itjduran.bill_menu
               order by estado,id";
    return $this->app['db']->fetchAll($sqlMenu);
  }
  /**
   * Obtener el menu a mostra segun el rol
   *
   * @param number $rol id del rol del usuario
   *
   * @return array data con el menu a mostrar
   */
  public function getMenuRol($rol)
  {
    if ($rol==1) {
      $sqlMenuRol="SELECT nombre menu, ruta
                     FROM itjduran.bill_menu
                    ORDER BY estado,id";
    } else {
      $sqlMenuRol="SELECT m.nombre menu,
                          m.ruta
                     FROM itjduran.bill_rol_menu rm,
                          itjduran.bill_menu m,
                          itjduran.bill_rol r
                    where rm.id_rol = r.id
                      and rm.id_menu = m.id
                      and r.estado = 'A'
                      and m.estado = 'A'
                      and rm.estado = 'A'
                      and rm.id_rol = $rol
                    order by rm.estado, rm.id_rol, rm.id_menu";
    }


    return $this->app['db']->fetchAll($sqlMenuRol);
  }
  /**
   * obtener la lista de usuarios del modulo billing
   *
   * @return array lista de usuarios
   *
   */
  public function getUsuarios()
  {
    $sqlUsuarios="SELECT id_usuario,
                         email,
                         telefono,
                         ip,
                         (SELECT descripcion
                            from itjduran.bill_rol
                           where id=u.id_rol ) rol,
                         to_char(created_at,'dd.mm.yyyy hh24:mi:ss') fecha_creacion,
                         created_by creado_por,
                         to_char(updated_at,
                                 'dd.mm.yyyy hh24:mi:ss') fecha_actualizacion,
                         updated_by actualizado_por
                    from itjduran.bill_usuario u
                   order by rol, id_usuario";
    return $this->app['db']->fetchAll($sqlUsuarios);
  }
}
