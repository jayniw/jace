<?php
namespace jqTools;
/**
*  clase que prepara la informacion ingresada para mostrarla como componente de jquery4php.
*/
class jqTools
{
	function grid($data,$caption,$divId)
  {
    \YsJQuery::useComponent(\YsJQueryConstant::COMPONENT_JQGRID);
    /* The Grid */
    $grid = new \YsGrid($divId,$caption); // <- id|name and caption
    /* The columns */
    $cols=null;
       
    foreach ($data[0] as $key => $value) {
      $idGridField[$key] = new \YsGridField($key, str_replace('_', ' ',$key));
      $grid->addGridField($idGridField[$key]);
      $idGridField[$key]->setWidth(max(strlen($value),strlen($key))*8+15);
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
    /*options*/
    $grid->setRecordList($recordList);
    $grid->setAutoWidth(true);//the grid width is recalculated automatically to the width of the parent element.
    $grid->setWidth('auto');
    $grid->setDataType(\YsGridConstants::DATA_TYPE_LOCAL);
    $grid->setHeight('auto');
    $grid->setRowNum(20);
    $grid->setRowList(array(10,30,count($data)));
    $grid->setViewRecords(true);
    $grid->setLoadText('Cargando la data...');
    //$grid->setRowNumbers(true);//The purpose of this column is to count the number of available rows
    //$grid->setMultiselect(true);//If this flag is set to true a multi selection of rows is enabled.
    return $grid;
  }

	public function tabla($data,$caption,$divId)
	{
     return $this->grid($data,$caption,$divId);
	}

  public function tablaFiltro($data,$caption,$divId)
  {
    $grid=$this->grid($data,$caption,$divId);
    /*filtros para columnas*/
    $filterToolBar = new \YsGridFilterToolbar();
    $filterToolBar->setStringResult(true);
    $filterToolBar->setSearchOnEnter(false);
    
    $grid->setFilterToolbar($filterToolBar);

    return $grid; 
  }
  public function tablaReclamos($data,$caption,$divId,$periodo)
  {
    $diasMes=cal_days_in_month(CAL_GREGORIAN, substr($periodo,4,2), substr($periodo,0,4));
    //echo $diasMes.'<br>';
    for ($i=0; $i < count($data); $i++) {
      //echo '<pre>'; print_r($data[$i]); echo '</pre>'; 
      foreach ($data[$i] as $key => $value) {
        if (substr($key,1)<=$diasMes) {
          $dataPeriodo[$i][$key]=$value;
        }
      }
    }
    //echo '<pre>'; print_r($dataPeriodo); echo '</pre>';  
    return $this->grid($dataPeriodo,$caption,$divId);
  }  
}
