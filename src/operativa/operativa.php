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
    $sqlCerradosDia=" SELECT a.Usuario As Esp,
                             a.D01, a.D02, a.D03, a.D04, a.D05, a.D06, a.D07, a.D08, a.D09, a.D10, 
                             a.D11, a.D12, a.D13, a.D14, a.D15, a.D16, a.D17, a.D18, a.D19, a.D20,
                             a.D21, a.D22, a.D23, a.D24, a.D25, a.D26, a.D27, a.D28, a.D29, a.D30, a.D31,
                             (a.D01 + a.D02 + a.D03 + a.D04 + a.D05 + a.D06 + a.D07 + a.D08 +
                              a.D09 + a.D10 + a.D11 + a.D12 + a.D13 + a.D14 + a.D15 + a.D16 +
                              a.D17 + a.D18 + a.D19 + a.D20 + a.D21 + a.D22 + a.D23 + a.D24 +
                              a.D25 + a.D26 + a.D27 + a.D28 + a.D29 + a.D30 + a.D31) As Total
                        From Hpsc_Rep_Closed_Month a
                       Where a.Fecha = '$periodo'
                         And Usuario In (".$this->app['esp'].")
                      Union
                      SELECT 'TOTAL',
                             Sum(D01), Sum(D02), Sum(D03), Sum(D04), Sum(D05), Sum(D06), Sum(D07), Sum(D08),
                             Sum(D09), Sum(D10), Sum(D11), Sum(D12), Sum(D13), Sum(D14), Sum(D15), Sum(D16),
                             Sum(D17), Sum(D18), Sum(D19), Sum(D20), Sum(D21), Sum(D22), Sum(D23), Sum(D24),
                             Sum(D25), Sum(D26), Sum(D27), Sum(D28), Sum(D29), Sum(D30), Sum(D31),
                             (Sum(D01) + Sum(D02) + Sum(D03) + Sum(D04) + Sum(D05) + Sum(D06) +
                              Sum(D07) + Sum(D08) + Sum(D09) + Sum(D10) + Sum(D11) + Sum(D12) +
                              Sum(D13) + Sum(D14) + Sum(D15) + Sum(D16) + Sum(D17) + Sum(D18) +
                              Sum(D19) + Sum(D20) + Sum(D21) + Sum(D22) + Sum(D23) + Sum(D24) +
                              Sum(D25) + Sum(D26) + Sum(D27) + Sum(D28) + Sum(D29) + Sum(D30) + Sum(D31)) As Total
                        From Hpsc_Rep_Closed_Month a
                       Where Fecha = '$periodo'
                         And Usuario In (".$this->app['esp'].")
                       Order By Total";
    return $this->app['dbs']['scenter']->fetchAll($sqlCerradosDia);
  }
}
