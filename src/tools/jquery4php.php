<?php
namespace jquery4php;
/**
* 
*/
class jquery4php
{
	
	public function tabla($data)
	{
		/*para probar jgrid*/
    //\YsJQuery::useComponent(\YsJQueryConstant::COMPONENT_JQGRID);
    /* The Grid */
    //$grid = new \YsGrid('gridId',NULL); // <- id|name and caption

    /* The columns */
    foreach ($data[0] as $key => $value) {
      $$key.'Col' = $value;//new \YsGridField($key, $key);
      $cols.=$$key.'Col'.',';
    }
    return $cols;
    /*
    $colaCol = new \YsGridField('COLA', 'COLA');
    $estadoCol = new \YsGridField('ESTADO', 'ESTADO');
    $cantCol = new \YsGridField('CANT', 'CANTIDAD');
    $porCol = new \YsGridField('POR', 'PORCENTAJE');

    $grid->addGridFields($colaCol, $estadoCol, $cantCol, $porCol); 

    $records = new \YsGridRecordList(); // To add a list of records (Rows)
    for ($i=0; $i < count($rows); $i++) { 
      $record = new \YsGridRecord();
      $record->setAttribute('COLA',$rows[$i]['COLA']);
      $record->setAttribute('ESTADO',$rows[$i]['ESTADO']);
      $record->setAttribute('CANT',$rows[$i]['CANT']);
      $record->setAttribute('POR',$rows[$i]['POR']);
      $records->append($record);
    }
    $grid->setRecordList($records); // set the RecordList to the Grid
    -- jqGrid options 
    $grid->setWidth("100%");
    $grid->setDataType(\YsGridConstants::DATA_TYPE_LOCAL);
    $grid->setHeight('auto');
    $grid->setRowNum(20);
    $grid->setRowList(array(10,30,count($rows)));
    $grid->setViewRecords(true);
    $grid->setSortname('name');*/
	}
}
