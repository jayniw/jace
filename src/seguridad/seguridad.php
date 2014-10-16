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
   * Obtener el menu a mostra segun el rol
   *
   * @param number $rol id del rol del usuario
   *
   * @return array data con el menu a mostrar
   */
  public function getMenuRol($rol)
  {
    $sqlMenuRol="";

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