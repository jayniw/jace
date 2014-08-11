<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

//Request::setTrustedProxies(array('127.0.0.1'));

/*$app->get('/', 
          function () use ($app) {
            return $app['twig']->render('index.html', array());
          })
      ->bind('homepage');*/

$app->match('/',
           function (Request $request) use ($app){
            $error=array();
            if ($request->get('logout')){
              $app['session']->clear();
            }
            if ($request->get('login')){
              $login= $request->get('login');
              /*conectar a AD*/
              try{
                $adldap = new adLDAP\adLDAP(array('account_suffix' => "@nuevatel.net",
                                                  'base_dn' => "DC=nuevatel,DC=net",
                                                  'domain_controllers' => array ("10.40.3.97:389",
                                                                                 "adserverlpz.nuevatel.net:389")));
              }
              catch (adLDAPException $e) {
                  $error['ldap']=$e;
                  exit();   
              }
              /*autenticar en AD*/
              if ($adldap->authenticate($login['_username'],$login['_password'])) {
                $app['session']->start();
                $sec=new Seguridad\seguridad();
                $rol=$sec->getRol($login['_username']);
                $app['session']->set('user',array('username'=>$login['_username'],
                                                  'userrol'=>$rol));
              } else {
                $error['login']='Usuario o contraseña incorrecto.';
              }
              $adldap->close();
            }
            return $app['twig']->render('index.html', array('error'=>$error));
           })
      ->bind('homepage');

$app->get('/imagen', 
          function () use ($app) {
            $sql="SELECT Cola, Estado, Cant, Round(Cant * 100 / Tot, 2) por
                    From (Select Cola,
                                 a.Estado,
                                 Count(*) Cant,
                                 (Select Count(*)
                                    From billing.Bl_Tmp_Imagen_Facturacion
                                   Where Cola = a.Cola) Tot
                            From billing.Bl_Tmp_Imagen_Facturacion a
                           Group By Cola, Estado
                           Order By 2)";
            $sqlDem="SELECT max(dem) dem 
                       from (select round(((sysdate-THIS_DATE)*60*24),2)dem,
                                    job,
                                    last_date,
                                    this_date,
                                    next_date,
                                    broken,
                                    failures,
                                    interval,
                                    what 
                               from user_jobs
                              where what like '%crear_imagen%')";
            $rows=$app['db']->fetchAll($sql);
            $dem=$app['db']->fetchAll($sqlDem);
            $demora=$dem[0]['DEM'];
            /*para probar jgrid*/
            \YsJQuery::useComponent(\YsJQueryConstant::COMPONENT_JQGRID);
            /* The Grid */
            $grid = new \YsGrid('gridId',NULL); // <- id|name and caption

            /* The columns */
            $colaCol = new \YsGridField('COLA', 'COLA');
            $estadoCol = new \YsGridField('ESTADO', 'ESTADO');
            $cantCol = new \YsGridField('CANT', 'CANTIDAD');
            $porCol = new \YsGridField('POR', 'PORCENTAJE');

            /* The Data (Local Type) */
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
            /* jqGrid options */
            $grid->setWidth("100%");
            $grid->setDataType(\YsGridConstants::DATA_TYPE_LOCAL);
            $grid->setHeight('auto');
            $grid->setRowNum(20);
            $grid->setRowList(array(10,30,count($rows)));
            $grid->setViewRecords(true);
            $grid->setSortname('name');
            /*--*/
            return $app['twig']->render('imagen.html', array('hoy'=>$rows,'grid' => $grid,'demora'=>$demora));
          })
      ->bind('imagen');

$app->get('/resumen', 
          function () use ($app) {
            set_time_limit(0);
            $sql="SELECT Cola,
                         Estado,
                         Count(*) Cant,
                         Round((Count(*) * 100) / 18174.25, 2) Por
                    From Bl_Imagen_Grupo
                   Where Cod_Periodo = '201407'
                   Group By Cola, Estado
                   Order By 2 desc,1 asc";
            $sqlDem="SELECT max(dem) dem 
                       from (select round(((sysdate-THIS_DATE)*60*24),2)dem,
                                    job,
                                    last_date,
                                    this_date,
                                    next_date,
                                    broken,
                                    failures,
                                    interval,
                                    what 
                               from user_jobs
                              where what like '%generar_resumen%')";
            $rows=$app['db']->fetchAll($sql);
            $dem=$app['db']->fetchAll($sqlDem);
            $demora=$dem[0]['DEM'];
            /*para probar jgrid*/
            \YsJQuery::useComponent(\YsJQueryConstant::COMPONENT_JQGRID);
            /* The Grid */
            $grid = new \YsGrid('gridId',NULL); // <- id|name and caption

            /* The columns */
            $colaCol = new \YsGridField('COLA', 'COLA');
            $estadoCol = new \YsGridField('ESTADO', 'ESTADO');
            $cantCol = new \YsGridField('CANT', 'CANTIDAD');
            $porCol = new \YsGridField('POR', 'PORCENTAJE');

            /* The Data (Local Type) */
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
            /* jqGrid options */
            $grid->setWidth("100%");
            $grid->setDataType(\YsGridConstants::DATA_TYPE_LOCAL);
            $grid->setHeight('auto');
            $grid->setRowNum(20);
            $grid->setRowList(array(10,30,count($rows)));
            $grid->setViewRecords(true);
            $grid->setSortname('name');
            /*--*/
            return $app['twig']->render('resumen.html', array('hoy'=>$rows,'grid' => $grid,'demora'=>$demora));
          })
      ->bind('resumen');

$app->get('/facturacion/{periodo}/{grupo}', 
          function (Request $request) use ($app) {
            $fact=new Facturacion\facturacion($app);
            $imagen=$fact->getImagenGrupo($request->get('grupo'),$request->get('periodo'));
            foreach ($imagen[0] as $key => $value) {
              echo $key.'<br>';
            }
            $jq=new jquery4php\jquery4php();
            $grid=$jq->tabla($imagen);

            /*--*/
            return $app['twig']->render('fact/factGrupo.html', array('imagen'=>$imagen));
          })
      ->bind('facturacion');

$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html',
        'errors/'.substr($code, 0, 2).'x.html',
        'errors/'.substr($code, 0, 1).'xx.html',
        'errors/default.html',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});
