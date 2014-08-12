<?php
namespace Facturacion;
/**
* Clase para el manejo de la facturacion por periodo.
*/
class facturacion
{
	function __construct($app) {
    $this->app= $app;
  }

	public function getImagenGrupo($grupo,$periodo)
	{
		$sqlImagenGrupo="SELECT ID_CLIENTE,ID_GRUPO,ID_COBRANZA,ESTADO_GRUPO as E,FACTURABLE as f,
                            CANTIDAD_UNIDADES as unidades,COLA,ESTADO,
                            COD_ERROR,MENSAJE_ERROR,COD_USUARIO as usuario,
                            to_char(FECHA_TRANSACCION,'dd.mm.yyyy hh24:mi:ss') as fecha
                       from billing.bl_imagen_grupo 
                      where cod_periodo='$periodo' 
                        and ID_GRUPO =$grupo";
    return $this->app['db']->fetchAll($sqlImagenGrupo);
	}
	public function getImagenUnidadGrupo($grupo,$periodo)
	{
		$sqlImagenUnidadGrupo="SELECT ID_CLIENTE,ID_GRUPO,ID_COBRANZA,ID_UNIDAD,NAME,LAYOUT,COD_LAYOUT,ESTADO_UNIDAD as E,
                                  FACTURABLE as F,LIMITE_CREDITO as LCRE,LIMITE_CONSUMO as LCON,COD_USUARIO as usuario,
                                  to_char(FECHA_TRANSACCION,'dd.mm.yyyy hh24:mi:ss') as fecha
                             from billing.bl_imagen_unidad
                            where cod_periodo='$periodo'
                              and ID_GRUPO =$grupo";
    return $this->app['db']->fetchAll($sqlImagenUnidadGrupo);
	}
  public function getResumenGrupo($grupo,$periodo)
  {
    $sqlResumenGrupo="SELECT ID_CLIENTE,ID_GRUPO,ID_CARGO,
                             (select descripcion 
                                from billing.CF_CARGO 
                               where ID_CARGO=rg.ID_CARGO) cargo,
                             CANTIDAD,SALDO,COD_USUARIO,FECHA_TRANSACCION
                        from billing.BL_resumen_grupo rg 
                       where cod_periodo='$periodo' 
                         and id_grupo=$id_grupo";
    return $this->app['db']->fetchAll($sqlResumenGrupo);
  }
  public function getResumenUnidadGrupo($grupo,$periodo)
  {
    $sqlResumenUnidadGrupo="SELECT ID_CLIENTE,ID_GRUPO,ID_UNIDAD,ID_CARGO,
                                   (select descripcion 
                                      from billing.CF_CARGO 
                                     where ID_CARGO=ru.ID_CARGO) cargo,
                                   LAYOUT,COD_LAYOUT,CANTIDAD,IMPORTE,COD_USUARIO,FECHA_TRANSACCION
                              from billing.BL_resumen_unidad ru 
                             where cod_periodo='$periodo'
                               and ID_GRUPO =$grupo";
    return $this->app['db']->fetchAll($sqlResumenUnidadGrupo);
  }
  public function getBlFactura($grupo,$periodo)
  {
    $sqlBlFactura="SELECT COD_PERIODO,ID_CLIENTE,ID_GRUPO,ID_DOCUMENTO,ESTADO,FECHA_EMISION,
                          IMPORTE_CREDITO_FISCAL,COD_USUARIO,FECHA_TRANSACCION
                     from bl_factura 
                    where id_grupo=$grupo
                      and cod_periodo='$periodo'
                    order by FECHA_TRANSACCION";
    return $this->app['db']->fetchAll($sqlBlFactura);
  }
  public function getCtlFactura($grupo,$periodo)
  {
    $idCobranza=getIdCobranza($grupo);
    $sqlCtlFactura="SELECT Periodo,
                           Cod_Cliente,
                           Imp_1,
                           Imp_7,
                           Imp_Local,
                           Nvl(Nvl(Imp_Gral, 0) + Nvl(Imp_Gral_Entel, 0) +
                               Nvl(Imp_Boliviatel, 0) + Nvl(Imp_Telecel, 0) +
                               Nvl(Imp_Teledata, 0) + Nvl(Imp_Nuevatel_Ld, 0) + Nvl(Imp_Its, 0) +
                               Nvl(Imp_Cotel, 0) + Nvl(Imp_Unete, 0) + Nvl(Imp_Bossnet, 0) +
                               Nvl(Imp_Bolitel, 0) + Nvl(Imp_Transmedes, 0),
                               0) Imp_Ld,
                           Estado,
                           Nro_Orden,
                           Nro_Fact,
                           Cod_Control_Sin,
                           Fecha_Emision
                      From Ctl.Factura
                     Where Cod_Cliente = $idCobranza
                       And Periodo = '$periodo";
    return $this->app['db']->fetchAll($sqlCtlFactura);
  }
  function getIdCobranza($grupo)
  {
    $sqlIdCobranza="select ID_COBRANZA from billing.CL_DATOS_FACTURACION where id_grupo=$grupo";
    $idCobranza= $this->app['db']->fetchAll($sqlIdCobranza);
    return $idCobranza[0]['ID_COBRANZA'];
  }
}
?>