<?php
namespace Operativa;
/**
* 
*/
class operativa
{
	
	function __construct($app) {
    $this->app= $app;
  }

  public function getCerradosDia($periodo)
  {
    $sqlCerradosDia="SELECT a.usuario as esp, a.d01, a.d02,
                            a.d03, a.d04, a.d05, a.d06, a.d07, a.d08, a.d09, a.d10, a.d11,
                            a.d12, a.d13, a.d14, a.d15, a.d16, a.d17, a.d18, a.d19, a.d20,
                            a.d21, a.d22, a.d23, a.d24, a.d25, a.d26, a.d27, a.d28, a.d29,
                            a.d30, a.d31
                       FROM hpsc_rep_closed_month a
                      where a.fecha='$periodo'
                        and usuario in (".$this->app['esp'].")";
    return $this->app['dbs']['scenter']->fetchAll($sqlCerradosDia);
  }
}
