<?php
namespace Seguridad;
/**
* clase para autentificacion y control de roles a la aplicacion
*/
class seguridad
{
  /**
   * __construct
   * @param object $app objeto aplicacion
   */
  function __construct($app) {
    $this->app= $app;
  }
  /**
   * Obtener rol correspondiente al usuario
   * @param  string $usr usuario logueado
   * @return integer      id rol
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
  public function getMenuRol($rol)
  {
    $sqlMenuRol="";

  }
  /**
   * obtener la lista de usuarios del modulo billing
   * @return array lista de usuarios
   */
  public function getUsuarios()
  {
    $sqlUsuarios="SELECT id_usuario,
                         email,
                         telefono,
                         ip,
                         (SELECT descripcion from itjduran.bill_rol where id=u.id_rol ) rol,
                         to_char(created_at,'dd.mm.yyyy hh24:mi:ss') fecha_creacion,
                         created_by creado_por,
                         to_char(updated_at,'dd.mm.yyyy hh24:mi:ss') fecha_actualizacion,
                         updated_by actualizado_por
                    from itjduran.bill_usuario u
                   order by rol, id_usuario";
    return $this->app['db']->fetchAll($sqlUsuarios);
  }

}

?>