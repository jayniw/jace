<?php
/**
 * archivo que tiene la definicion de la clase
 *
 * PHP version 5.4.3
 *
 * @category File
 * @package  JqTools
 * @author   Jalir Duran <jalir.duran@nuevatel.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://10.40.4.20/bill/index_dev.php/
 *
 */
namespace jqTools;

 /**
 *  clase que prepara la data ingresada para mostrarla como componente de jquery4php.
 *
 * @category Class
 * @package  JqTools
 * @author   Jalir Duran <jalir.duran@nuevatel.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://10.40.4.20/bill/index_dev.php/
 *
 */
class JqTools
{
  /**
   * grid base para mostrar
   *
   * @param array   $data    data a mostrar
   * @param varchar $caption titulo de grid
   * @param varchar $divId   id html de la etiqueta
   *
   * @return object          grid generado a mostrar
   *
   */
  public function grid($data, $caption, $divId)
  {
    \YsJQuery::useComponent(\YsJQueryConstant::COMPONENT_JQGRID);
    /* The Grid */
    $grid = new \YsGrid($divId, $caption); // <- id|name and caption
    /* The columns */
    $cols=null;

    foreach ($data[0] as $key => $value) {
      $idGridField[$key] = new \YsGridField($key, str_replace('_', ' ', $key));
      $grid->addGridField($idGridField[$key]);
      $idGridField[$key]->setWidth(max(strlen($value), strlen($key))*8+15);
      $idGridField[$key]->setAlign(\YsAlignment::$RIGHT);
    }
    $recordList = new \YsGridRecordList();
    for ($i=0; $i < count($data); $i++) {
      $record = new \YsGridRecord();
      foreach ($data[$i] as $key => $value) {
        $record->setAttribute($key, $value);
      }
      $recordList->append($record);
    }
    //options
    $grid->setRecordList($recordList);
    //grid width is recalculated automatically to the width of parent element.
    $grid->setAutoWidth(true);
    $grid->setWidth('auto');
    $grid->setDataType(\YsGridConstants::DATA_TYPE_LOCAL);
    $grid->setHeight('auto');
    $grid->setRowNum(20);
    $grid->setRowList(array(10,30,count($data)));
    $grid->setViewRecords(true);
    $grid->setLoadText('Cargando la data...');
    //The purpose of this column is to count the number of available rows
    //$grid->setRowNumbers(true);
    //If this flag is set to true a multi selection of rows is enabled.
    //$grid->setMultiselect(true);
    return $grid;
  }
  /**
   * grid generico
   *
   * @param array   $data    data a mostrar
   * @param varchar $caption titulo de grid
   * @param varchar $divId   id html de la etiqueta
   *
   * @return object          grid generado a mostrar
   */
  public function tabla($data, $caption, $divId)
  {
     return $this->grid($data, $caption, $divId);
  }
  /**
   * grid con filtro
   *
   * @param array   $data    data a mostrar
   * @param varchar $caption titulo de grid
   * @param varchar $divId   id html de la etiqueta
   *
   * @return object          grid generado a mostrar
   */
  public function tablaFiltro($data, $caption, $divId)
  {
    $grid=$this->grid($data, $caption, $divId);
    /*filtros para columnas*/
    $filterToolBar = new \YsGridFilterToolbar();
    $filterToolBar->setStringResult(true);
    $filterToolBar->setSearchOnEnter(false);

    $grid->setFilterToolbar($filterToolBar);

    return $grid;
  }

  /**
   * Grid especifico para mostrar los reclamos cerrados
   *
   * @param array   $data    data a mostrar
   * @param varchar $caption titulo de grid
   * @param varchar $divId   id html de la etiqueta
   * @param varchar $periodo codigo del periodo a mostrar
   *
   * @return object grid generado a mostrar
   */
  public function tablaReclamos($data, $caption, $divId, $periodo)
  {
    $diasMes=cal_days_in_month(
      CAL_GREGORIAN,
      substr($periodo, 4, 2),
      substr($periodo, 0, 4)
    );
    for ($i=0; $i < count($data); $i++) {
      foreach ($data[$i] as $key => $value) {
        if (substr($key, 1)<=$diasMes) {
          $dataPeriodo[$i][$key]=$value;
        }
      }
    }
    return $this->grid($dataPeriodo, $caption, $divId);
  }
}
