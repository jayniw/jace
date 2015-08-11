<?php
/**
 * archivo que tiene la definicion de la clase para uso de REST
 *
 * PHP version 5.4.3
 *
 * @category File
 * @package  Rest
 * @author   Jalir Duran <jalir.duran@nuevatel.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://10.40.4.20/bill/index_dev.php/
 *
 */
namespace Rest;

/**
 *  clase para obntener la data de un link como se muestra via web (REST).
 *
 * @category Class
 * @package  Rest
 * @author   Jalir Duran <jalir.duran@nuevatel.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://10.40.4.20/bill/index_dev.php/
 *
 */
class Rest
{
  /**
   * driver CURL
   * @var object
   */
  protected $ch;
  /**
   * Inicializacion de la clase
   *
   * @param object $app entorno de la aplicacion
   */
  public function __construct($authString=null)
  {
    $this->ch = curl_init();
    curl_setopt($this->ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

    if($authString)
    {
      curl_setopt($this->ch, CURLOPT_USERPWD, $authString);
    }

    return $this;
  }
  /**
   * ejecutar la obtencion de la data requerida
   * @return object data obtenida
   */
  protected function execute()
  {
    $data = curl_exec($this->ch);
    $code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
    if($code == 200)
      return $data;
    else
      throw new \Exception('Code returned by the REST process: ' .
                           $code. ': ' .
                           curl_error($this->ch)
                          );
  }
  /**
   * definir URL para obtener la data
   * @param  string $url URL a procesar
   * @return object      driver con URL definida
   */
  public function url($url)
  {
    curl_setopt($this->ch, CURLOPT_URL, $url);
    return $this;
  }
  /**
   * Funcion para envio de operaciones al driver CURL
   * @param  array  $params lista de parametros
   * @return object         data obtenida de la ejecucion
   */
  public function post(array $params)
  {
    curl_setopt($this->ch, CURLOPT_POST, true);
    curl_setopt($this->ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, false);
    $this->execute($this->ch);
  }
  /**
   * obtener la data de la ejecuion del driver
   * @return object data obtenida
   */
  public function get()
  {
    curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
    return $this->execute($this->ch);
  }
}