<?php 
namespace Seguridad;
/**
* clase para autentificacion y control de roles a la aplicacion
*/
class seguridad
{
	private $menuAdmin = array('Home' => '/', );
  private $menuRepor = array('Cliente' => '/repor/cliente',
                     'Grupo' => '/repor/grupo',
                     'Unidad' => '/repor/unidad', );
  private $menuAbm = array('Crear' => '/abm/crear',
                   'Aprobar' => '/abm/aprobar', );
	public function getRol($usr)
  {
    $this->root=array('jduran');
    $this->admin=array('jgallinate','gmercado');
    $this->esp=array('jdaviu','gmaldonado','JoseCastro','mvelasco','htorres');
    switch (true) {
      case in_array($usr, $this->root):
        return 1;
        break;
      case in_array($usr, $this->admin):
        return 2;
        break;
      case in_array($usr, $this->esp): 
        return 3;
        break; 
      default:
        return 0;
        break;
    }
  }
  public function getMenuRol($rol)
  {
    
  }
}

?>