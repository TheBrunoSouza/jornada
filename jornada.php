<?php
    ini_set('display_errors', 1);
    //error_reporting(E_ALL);
    error_reporting(0);
    session_start();

    require_once('includes/OracleCieloJornada.class.php');
    require_once('includes/Controles.class.php');

    $OraCielo   = new OracleCielo();
    $conexao    = $OraCielo->getCon();
    $CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
    if(!$CtrlAcesso->checkUsuario($_SESSION)){
        echo '<script type="text/javascript">
                alert("Você precisa logar-se ao sistema!");
                window.location.href = "http://jornada.cielo.ind.br";
              </script>';
        exit();
    }
    else{
        #echo 'Verificando sessão! ';
    }
    $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
    ?>

    <html>
    <head>
    <title>Jornada de Trabalho | Cielo</title>
    <link rel="icon" href="imagens/truck_icon.png">
    <link rel="stylesheet" type="text/css" href="ext421/resources/css/ext-all-gray.css">
    <link rel="stylesheet" type="text/css" href="css/menus.css">

    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript" src="ext421/ext-all-debug.js"></script>
    <script type="text/javascript" src="ext421/packages/ext-locale/build/ext-locale-pt_BR.js"></script>

    <script type="text/javascript">
    google.load('visualization', '1.0', {'packages':['timeline']});

    function drawChart(dt, cond) {
        Ext.Ajax.request({
            url: 'json/jsonChartDiario.php',
            params:{
                dtRequest: dt,
                idCondutor: cond
            },
            success: function(response){
                jsonData = Ext.JSON.decode(response.responseText);
                if(jsonData.count_data==0 || jsonData.count_data==null){
                    Ext.getCmp('gestorPanelTitleId').update('<br><br><div><p class="x-grid-empty"><b>Não há dados para este dia</b></p></div>');
                    Ext.getCmp('gestorPanelChartId').update('<br><div id=\"gestorPanelChart\" style=\"width: 100%;\"></div>');
                }
                else{
                    Ext.getCmp('gestorPanelTitleId').update('');
                    var container = document.getElementById('gestorPanelChart');
                    var chart = new google.visualization.Timeline(container);
                    var options = {
                        timeline: {colorByRowLabel: true}
                    };
                    var dataTable = new google.visualization.DataTable(jsonData.chart_data);
                    chart.draw(dataTable, options);
                }
            }
        });
    }

    function exit(){
        window.location = 'index.php';
    }

    /**
     * Função para formatar o numero de telefone
     */
    Ext.apply(Ext.form.VTypes, {
        'phone': function () {
            var re = /^[\(\)\.\- ]{0,}[0-9]{2}[\(\)\.\- ]{0,}[0-9]{4}[\(\)\.\- ]{0,}[0-9]{4,5}[\(\)\.\- ]{0,}$/;
            return function (v) { return re.test(v); };
        }(), 'phoneText': 'O formato do telefone está incorreto, digite: 00-0000-0000 (dashes optional) Or (00) 0000-0000',
        'fax': function () {
            var re = /^[\(\)\.\- ]{0,}[0-9]{3}[\(\)\.\- ]{0,}[0-9]{3}[\(\)\.\- ]{0,}[0-9]{4}[\(\)\.\- ]{0,}$/;
            return function(v) { return re.test(v); };
        }(), 'faxText': 'O formato do FAX está errado',
        'zipCode': function () {
            var re = /^\d{5}(-\d{3})?$/;
            return function (v) { return re.test(v); };
        }(), 'zipCodeText': 'O Formato do CEP está errado, digite: 94105-000 or 94105'
    });

    Ext.define('Estados', {
        extend: 'Ext.data.Model',
        fields:[
            {name: 'idEstado', type: 'int'},
            {name: 'sglEstado', type: 'string'},
            {name: 'nmEstado', type: 'string'}
        ]
    });

    /* Submete os dados de para uma URL */
    function panelLoad(inObj, title, url, param, paramJson){
        if(Ext.getCmp('mainContent')){
            Ext.getCmp('mainContent').destroy();
        }

        if(Ext.getCmp('mask')){
            mask.destroy();
        }

        var mask = new Ext.LoadMask(inObj, {msg:"Carregando..."});
        mask.show();

        Ext.getCmp(inObj).setTitle(title);

        Ext.getCmp(inObj).body.load({
            url: url+'?'+param,
            autoLoad    : true,
            scripts     : true,
            callback	: function(){
                mask.hide();
            }
        });
    }

    /**
     * Função que retorna as horas
     * @Param: min: minutos;
     * @Param: sinais: Se quiser com sinais de positivo (+) e negativo (-). Obs:  Se for negativo passar 'true';
     **/
    function rectime(min, sinais=false) {
        var tempo = '';

        if(sinais == true){
            if(min < 0){
                min = min * -1;
                tempo = '(-) ';
            }
            else if(min == 0){
                tempo = '    ';
            }else {
                tempo = '(+) ';
            }
        }

        var auxHora = Math.floor(min / 60),
            auxMin  = min - (auxHora * 60),
            hora    = auxHora.toString(),
            min     = auxMin.toString();

        if (hora.length < 2){hora = '0' + hora;}
        if (min.length < 2) {min = '0' + min;}
        tempo = tempo + hora + 'h' + min;
        return tempo;
    }

    /**
     * Função para criar uma janela flutuante
     * @param id_window
     * @param title
     * @param url
     * @param param
     * @param width
     * @param height
     * @param isModal
     * @param isClosable
     * @param toolbar: Passar a toolbar montada e, no arquivo que cria a janela, definir o handler dos botões. Ex:  (Ext.getCmp('idButton').handler = function(){};) Se não for necessária, passar: ''
     */
    function showWindowToolbar(id_window, title, url, param, width, height, isModal, isClosable, toolbar){

        if(toolbar == null || toolbar  == ''){
            var wind = Ext.create('Ext.window.Window', {
                title: title,
                id : id_window,
                width: width,
                height: height,
                resizable: false,
                closable: isClosable,
                layout: 'fit',
                plain:true,
                autoScroll: true,
                stateful : false,
                modal: isModal,
                autoLoad: {
                    url: url+'?'+param,
                    //params: parametros,
                    scripts: true
                }
            }).show();
        }else{
            var wind = Ext.create('Ext.window.Window', {
                title: title,
                id : id_window,
                width: width,
                height: height,
                resizable: false,
                closable: isClosable,
                layout: 'fit',
                plain:true,
                autoScroll: true,
                stateful : false,
                modal: isModal,
                autoLoad: {
                    url: url+'?'+param,
                    //params: parametros,
                    scripts: true
                },
                bbar: toolbar
            }).show();
        }
    }

    function showWindow(id_window, title, url, param, width, height, isModal, isClosable){
        var wind = Ext.create('Ext.window.Window', {
            title: title,
            id : id_window,
            width: width,
            height: height,
            resizable: false,
            closable: isClosable,
            layout: 'fit',
            plain:true,
            autoScroll: true,
            stateful : false,
            modal: isModal,
            autoLoad: {
                url: url+'?'+param,
                //params: parametros,
                scripts: true
            }
        }).show();
    }

    var tbSistema = Ext.create('Ext.toolbar.Toolbar', {
        border: false,
        items: [
        <?
            $tmp = 0;
            $permisUsu = $CtrlAcesso->checkPermissao(6, '');
            if($permisUsu['permissao']==true){
        ?>
                {
                    text: 'Usuários',
                    split: false,
                    icon: 'imagens/16x16/user.png',
                    listeners:{
                        click: function(node, event){
                            panelLoad('tabConfigId', 'Lista de Usuarios', 'lst/lstUsuarios.php', '');
                        }
                    }
//                    menu: [
//                        {
//                            text: 'Lista de Usuários',
//                            iconCls: 'list',
//                            listeners:{
//                                click: function(node, event){
//                                    panelLoad('tabConfigId', 'Lista de Usuarios', 'lst/lstUsuarios.php', '');
//                                }
//                            }
//                        }
//                    ]
                }
        <?
            $tmp++;
            }
            $permisCond = $CtrlAcesso->checkPermissao(11, '');
            if($permisCond['permissao']==true){
                if($tmp>0)echo ",'-',";
        ?>
                {
                    text: 'Condutores',
                    split: false,
                    style: 'margin-top: 8px;',
                    //arrowAlign: 'bottom',
                    //iconAlign : 'top',
                    //scale: 'large',
                    icon: 'imagens/16x16/card_id.png',
                    listeners:{
                        click: function(node, event){
                            panelLoad('tabConfigId', 'Lista de Condutores', 'lst/lstCondutores.php', '');
                        }
                    }
    //						menu      : [
    //							{
    //								text     : 'Lista de Condutores',
    //								iconCls  : 'list',
    //								listeners:{
    //									click: function(node, event){
    //										panelLoad('tabConfigId', 'Lista de Condutores', 'lst/lstCondutores.php', '');
    //									}
    //								}
    //							},
    //							{
    //								text     : 'Relatório de Ativos',
    //								iconCls  : 'gera_relatorio',
    //								listeners:{
    //									click: function(node, event){
    //										panelLoad('tabConfigId', 'Relatório de Ativos', 'lst/lstRelAtivos.php', '');
    //									}
    //								}
    //							}
                                                            //<?if($permisCond['add']=='T'){?>
    //							,'-',
    //							{
    //								text     : 'Novo Condutor',
    //								iconCls  : 'add',
    //								listeners:{
    //									click: function(node, event){
    //										showWindow('cadastro_condutor','Cadastro de Condutor', 'cad/cadCondutores.php', 'acao=insert', 400, 350, true, true);
    //									}
    //								}
    //							}
                                                            //<? }?>
    //						]
                        }
    <!--				--><?//
    //					$tmp++;
    //				}
    //				$permisCli = $CtrlAcesso->checkPermissao(4, '');
    //				if($permisCli['permissao']==true){
    //					if($tmp>0)echo ",'-',";
    //				?>
    //					{
    //						text      : 'Grupos',
    //						split     : true,
    //                                                style: 'margin-top: 8px;',
    //						//arrowAlign: 'bottom',
    //						//iconAlign : 'top',
    //						//scale     : 'large',
    //						icon      : 'imagens/16x16/group.png',
    //						menu      : [
    //							{
    //								text     : 'Lista de Grupos',
    //								iconCls  : 'list',
    //								listeners:{
    //									click: function(node, event){
    //										panelLoad('tabConfigId', 'Lista de Grupo', 'lst/lstGrupos.php', '');
    //									}
    //								}
    //							}
    //						]
    //					}
    <!--			--><?//
    //				$tmp++;
    //			}
    // 			$permisEquip = $CtrlAcesso->checkPermissao(8, '');
    //			if($permisEquip['permissao']==true){
    //				if($tmp>0)echo ",'-',";
    //			?>
    //					{
    //						text      : 'Equipamentos',
    //						split     : true,
    //                                                style: 'margin-top: 8px;',
    //						//arrowAlign: 'bottom',
    //						//iconAlign : 'top',
    //						//scale     : 'large',
    //						icon      : 'imagens/16x16/ipod.png',
    //						menu      : [
    //							{
    //								text     : 'Lista de Equipamentos',
    //								iconCls  : 'list',
    //								listeners:{
    //									click: function(node, event){
    //										panelLoad('tabConfigId', 'Lista de Equipamentos', 'ajax/lstEquipamentos.php', '');
    //									}
    //								}
    //							}
    //                                                        <?//
    //                                                        if($permisEquip['add']=='T'){?>
    //							,{
    //								text   : 'Novo Equipamento',
    //								iconCls: 'add',
    //								listeners:{
    //									click: function(node, event){
    //										showWindow('cadastro_equipamento','Cadastro de Equipamentos', 'ajax/cadEquipamentos.php', '', 460, 145, true, true);
    //									}
    //								}
    //							}
    //                                                        <?//
    //                                                        }
    //                                                        ?>
    //						]
    //					}
                    <?
                        $tmp++;
                    }
                    $permisCli = $CtrlAcesso->checkPermissao(9, '');
                    if($permisCli['permissao']==true){
                        if($tmp>0)echo ",'-',";
                    ?>
                        {
                            text: 'Empresas',
    //						split     : true,
                            style: 'margin-top: 8px;',
                            //arrowAlign: 'bottom',
                            //iconAlign : 'top',
                            //scale     : 'large',
                            icon      : 'imagens/16x16/factory_icon.gif',
                            listeners:{
                                click: function(node, event){
                                    panelLoad('tabConfigId', 'Lista de Empresas', 'lst/lstEmpresas.php', '');
                                }
                            }
    //						menu      : [
    //							{
    //								text     : 'Lista de Empresas',
    //								iconCls  : 'list',
    //								listeners:{
    //									click: function(node, event){
    //										panelLoad('tabConfigId', 'Lista de Empresas', 'lst/lstEmpresas.php', '');
    //									}
    //								}
    //							}
    //						]
                        }
                    <?
                        $tmp++;
                    }
                    $permisCli = $CtrlAcesso->checkPermissao(2, '');
                    if($permisCli['permissao']==true){
                        if($tmp>0)echo ",'-',";
                    ?>
    //					{
    //						text      : 'Relatorios',
    //						split     : true,
    //                                                style: 'margin-top: 8px;',
    //						//arrowAlign: 'bottom',
    //						//iconAlign : 'top',
    //						//scale     : 'large',
    //						icon      : 'imagens/16x16/group.png',
    //						menu      : [
    //							{
    //								text   : 'Relatório de Instalações',
    //								iconCls: 'list',
    //								listeners:{
    //									click: function(node, event){
    //										showWindow('filtro_instalacoes','Filtro', 'ajax/filInstalacoes.php', '', 460, 270, true, true);
    //									}
    //								}
    //							}
    //						]
    //					}
                    <?
                        $tmp++;
                    }
                    $permisMod = $CtrlAcesso->checkPermissao(1, '');
                    if($permisMod['permissao']==true){
    //					if($tmp>0)echo ",'-',";
                    ?>
    //					{
    //						text      : 'Módulos',
    //						split     : true,
    //                                                style: 'margin-top: 8px;',
    //						icon      : 'imagens/16x16/application_cascade.png',
    //						menu      : [
    //							{
    //								text     : 'Lista de Módulos',
    //								iconCls  : 'list',
    //								listeners:{
    //									click: function(node, event){
    //										panelLoad('tabConfigId', 'Lista de Modulos', 'lst/lstModulos.php', '');
    //									}
    //								}
    //							}
    //						]
    //					}
                    <?
                        $tmp++;
                    }
                $permisFeriado = $CtrlAcesso->checkPermissao(19, '');
                if($permisFeriado['permissao']==true){
    //                if($tmp>0)echo ",'-',";
                    ?>
                        ,{
                        text: 'Feriados',
                        iconCls: 'calendar',
                        style: 'margin-top: 8px;',
                        listeners:{
                            click: function(node, event){
                                <?if($empresaUsu != null or $empresaUsu != ''){?>
                                    showWindow('lstFeriados', 'Feriados', 'lst/lstFeriados.php', "idEmpresa=<?=$empresaUsu?>", 600, 300, true, true);
                                <?}else{?>
                                    Ext.Msg.show({
                                        title:'Informação:',
                                        msg: 'Acesse com o login do usuário para visualizar os feriados da empresa.',
                                        icon: Ext.Msg.WARNING,
                                        buttons: Ext.Msg.OK
                                    });
                                <?}?>

                            }
                        }
                }
                <? } $tmp++; ?>
            ]
            });

        Ext.require(['*']);

        function readGrid(id){

            if(Ext.getCmp('contentGridId'+id)){
                Ext.getCmp('contentGridId'+id).store.reload();
            }else{
                dynamicPanel = new Ext.Component({
                   loader: {
                      url: 'ajax/gridCondutores.php?gridIdSituacao='+id,
                      renderer: 'php',
                      autoLoad: true,
                      scripts: true
                   }
                });

                //Ext.getCmp('idSituacao'+id).remove();
                Ext.getCmp('idSituacao'+id).add(dynamicPanel);
            }
        }

        Ext.onReady(function() {

            Ext.TaskManager.start({
                interval: 60000,
                run: function() {
                    storeAlertas.load();
                }
            });

            var storeAlertas = Ext.create('Ext.data.Store', {
                autoLoad : false,
                proxy: {
                    type: 'ajax',
                    url: 'json/jsonAlertas.php',
                    reader: {
                        type: 'json',
                        root: 'alertas'
                    },
                    extraParams: {
                        idEmpresa:'<?=$empresaUsu?>',
                        origem: 'grid'
                    }
                },
                fields:[
                    {name: 'idAlerta', type: 'int'},
                    {name: 'descAlerta', type: 'string'},
                    {name: 'tempo', type: 'int'},
                    {name: 'dtHrAlerta', type: 'string'},
                    {name: 'nmCondutor', type: 'string'},
                    {name: 'plcAlerta', type: 'string'},
                    {name: 'justAlerta', type: 'string'},
                    {name: 'dtHrAlertaAtend', type: 'string'}
                ],
                listeners: {
                    load: function(store, operation, success){
                        var total = store.getCount();
                        Ext.getCmp('idPanelSouth').setTitle('<span style="color: red;"> '+total+'</span> Alertas');
                    }
                }
            });

            Ext.QuickTips.init();

            Ext.state.Manager.setProvider(Ext.create('Ext.state.CookieProvider'));

            var viewport = Ext.create('Ext.Viewport', {
                id: 'border-example',
                layout: 'border',
                items: [
                {
                    region: 'south',
                    split: true,
                    height: 200,
                    minSize: 100,
                    maxSize: 200,
                    collapsible: true,
                    collapsed: true,
                    title: 'Alertas',
                    id: 'idPanelSouth',
                    margins: '0 0 0 0',
                    layout: 'fit',
                    items: [{
                        xtype: 'grid',
                        id: 'idGridAlertas',
                        store: storeAlertas,
                        autoScroll: true,
                        forceFit: true,
                        columnLine: true,
                        columns: [
                            {text: 'Data Hora', sortable: true, width:30, dataIndex: 'dtHrAlerta'},
                            {text: 'Condutor', sortable: true, width:50, dataIndex: 'nmCondutor'},
                            {text: 'Descrição', menuDisabled: true, sortable: true, dataIndex: 'descAlerta'},
                            {
                                text: 'Tempo', dataIndex: 'tempo', menuDisabled: true, width: 20,
                                renderer: function (value, metaData, record, row, col, store, gridView) {
                                    var tempo = rectime(record.get('tempo'));
                                    return tempo;
                                }
                            },
                            {text: 'Placa', menuDisabled: true, sortable: true, width:20, dataIndex: 'plcAlerta'},
                            {
                                xtype:'actioncolumn',
                                width:8,
                                menuDisabled: true,
                                items: [{
                                    icon: 'imagens/16x16/eye.png',  // Use a URL in the icon config
                                    tooltip: 'Atender alerta',
                                    handler: function(grid, rowIndex, colIndex) {
                                        storeAlertas.removeAt(rowIndex);
                                        //storeAlertas.sync();
                                        storeAlertas.sync({
                                            success: function(){
                                                var total = storeAlertas.getCount();
                                                Ext.getCmp('idPanelSouth').setTitle('<span style="color: red;"> '+total+'</span> Alertas');
                                            }
                                        });
                                    }
                                }]
                            }
                        ]
                    }]
                },{
                    region: 'west',
                    stateId: 'navigation-panel',
                    id: 'west-panel', // see Ext.getCmp() below
                    title: 'Condutores',
                    split: true,
                    width: 180,
                    minWidth: 100,
                    maxWidth: 180,
                    collapsible: true,
                    animCollapse: true,
                    //collapsed: true,
                    margins: '2 0 0 3',
                    layout: 'accordion',
                    border: true,
                    items: [
                    {
                        title: 'Sistema',
                        iconCls: 'modulo',
                        //autoScroll: true,
                        id: 'sistemaId',
                        border: false,
                        loader: {
                            url: 'ajax/gridMenu.php',
                            contentType: 'php',
                            border: false,
                            autoLoad: true,
                            autoHeight: true,
                            scripts: true
                        }
                    },
                    {
                        title: 'Ativos',
                        iconCls: 'caminhao',
                        autoScroll: true,
                        id: 'idSituacao1',
                        onExpand: function() {
                            this.doLayout();
                        },
                        listeners:{
                            expand: function(){
                                //console.log('Passou Aqui!!!');
                                readGrid('1');
                            }
                        }
                        /*
                        loader: {
                                    url: 'ajax/gridCondutores.php?gridIdSituacao=1',
                                    contentType: 'php',
                                    autoScroll: true,
                                    autoLoad: true,
                                    autoHeight: true,
                                    scripts: true
                         }*/
                    }]
                },
                // in this instance the TabPanel is not wrapped by another panel
                // since no title is needed, this Panel is added directly
                // as a Container
                Ext.create('Ext.tab.Panel', {
                    region: 'center', // a center region is ALWAYS required for border layout
                    deferredRender: false,
                    id: 'tabPanelJornada',
                    activeTab: 0,     // first tab initially active
                    border : true,
                    margins: '2 0 0 0',
                    plain: true,
                    items: [
                        {
                            title: 'Painel',
                            border: false,
                            //tbar  : tbSistema,
                            autoScroll: false,
                            layout: 'fit',
    //                                                autoWidth: true,
    //                                                forceFit: true,
    //                                                height: Ext.getBody().getHeight(),
                            id: 'gestorTabId',
                            loader: {
    //							url: 'relatorios/relCartaoPonto.php',
                                url: '<?=($empresaUsu)?"ajax/dashboard.php":"relatorios/relCartaoPonto.php"?>',
                                contentType: 'php',
                                border: false,
                                autoLoad: true,
                                autoHeight: false,
                                scripts: true
                            }
                        }, {
                            title: 'Configurações',
                            id: 'tabConfigId',
                            tbar: tbSistema,
                            autoScroll: false,
                                                    html: '<p class="x-grid-empty"><b>Selecione uma opção no menu acima</b></p>'
                        }
                    ]
                })]
            });

            // get a reference to the HTML element with id "hideit" and add a click listener to it
            /*Ext.get("hideit").on('click', function(){
                // get a reference to the Panel that was created with id = 'west-panel'
                var w = Ext.getCmp('west-panel');
                // expand or collapse that Panel based on its collapsed property state
                w.collapsed ? w.expand() : w.collapse();
            });*/
        });

        </script>

    </head>
    <body>
    </body>
    </html>
