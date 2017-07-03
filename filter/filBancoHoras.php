<?php
    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');

    $OraCielo   = new OracleCielo();
    $conexaoOra = $OraCielo->getCon();
    $CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexaoOra);

    if(isset($_SESSION)) {
        $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
    } else {
        header('Location: http://jornada.cielo.ind.br');
    }

    $loadEmpresa = ($empresaUsu) ? 'false' : 'true';
    $dataIniFiltroInicial = $_REQUEST['dataIni'];
    $dataFimFiltroInicial = $_REQUEST['dataFim'];
    $empresaFiltroInicial = $_REQUEST['empresa'];
    $condutorFiltroInicial = $_REQUEST['condutor'];
    $checkAtivos = $_REQUEST['checkAtivos'];
?>
<script>        
    Ext.onReady(function () {
        Ext.getCmp('buttonFiltrar').handler = function(){
            console.info('mudou ok');
        };

        Ext.define('TipoMovimento', {
            extend: 'Ext.data.Model',
            fields:[
                {name: 'idTpMovimento', type: 'int'},
                {name: 'descTpMovimento', type: 'string'}
            ]
        });

        Ext.define('CondutoresAvancado', {
            extend: 'Ext.data.Model',
            requires: 'Ext.data.Model',
            fields:[
                {name: 'idCondutor', type: 'int'},
                {name: 'nmCondutor', type: 'string'},
                {name: 'cnhCondutor', type: 'string'},
                {name: 'dtNascCondutor', type: 'string'},
                {name: 'cpfCondutor', type: 'string'},
                {name: 'rgCondutor', type: 'string'},
                {name: 'telCondutor', type: 'string'},
                {name: 'celCondutor', type: 'string'},
                {name: 'matCondutor', type: 'string'},
                {name: 'empCondutor', type: 'string'},
                {name: 'sitCondutor', type: 'string'}
            ]
        });

        Ext.define('BancoDeHoras', {
            extend: 'Ext.data.Model',
            requires: 'Ext.data.Model',
            fields: [
                {name: 'idRegFechamento', type: 'int'},
                {name: 'bancoHoras', type: 'int'},
                {name: 'dataDb', type: 'date'},
                {name: 'dataIni', type: 'date'},
                {name: 'dataFim', type: 'date'},
                {name: 'hrAcumAnterior', type: 'auto'},
                {name: 'hrComputMovimento', type: 'auto'},
                {name: 'hrAcumAtual', type: 'auto'},
                {name: 'movimento', type: 'auto'},
                {name: 'observacao', type: 'string'}
            ]
        });

        var storeTipoMovimento = Ext.create('Ext.data.Store', {
            model: 'TipoMovimento',
            autoLoad: false,
            proxy: {
                type: 'ajax',
                url: 'json/jsonBcHrTpMovimento.php',
                reader: {
                    type: 'json',
                    root: 'movimentoBancoHoras'
                }
            }
        });

        var storeCondutorAvancado = Ext.create('Ext.data.Store', {
            model: 'CondutoresAvancado',
            autoLoad : true,
            proxy: {
                type: 'ajax',
                url: 'json/jsonCondutores.php',
                reader: {
                    type: 'json',
                    root: 'condutores'
                },
                extraParams: {
                    idEmpresa: '<?=$empresaUsu?>',
                    ativo: 'T'
                }
            }
        });

        var storeBcHorasManut = Ext.create('Ext.data.Store', {
            model: 'BancoDeHoras',
            autoLoad: false,
            proxy: {
                type: 'ajax',
                url: 'json/jsonBcHorasManut.php',
                reader: {
                    type: 'json',
                    root: 'bancoHoras'
                },
                /*extraParams: {

                }*/
            }
        });
                        
        storeBcHorasManut.load({
            params:{
                idEmpresa: '<?=$empresaUsu?>',
                idCondutor: Ext.getCmp('idCondutor').value
            },
            callback: function(records, operation, success) {
                if (success === true) {
                    var result = Ext.decode(operation.response.responseText);
                    if(typeof(result.bancoHoras[0]) != "undefined") {
                        console.info(result.bancoHoras[0]);
                        Ext.getCmp('idDtIniBcHoras').setValue(result.bancoHoras[0].dataIni);
                        Ext.getCmp('idDtFimBcHoras').setValue(result.bancoHoras[0].dataFim);
                        Ext.getCmp('idHrIniAcumPerAnterior').setValue(result.bancoHoras[0].hrAcumAtual);
                    }
                } else {
                    console.info('algum problema para carregar a store');
                }
            }
        });

        var nomeCondutor = Ext.create('Ext.form.TextField', {
            fieldLabel: 'Condutor:',
            labelWidth: 60,
//            width: 300,
            style: 'margin-left:3px;margin-top:8px;margin-bottom:8px;',
            id: 'idCondutorBcHoras',
            name: 'nomeCondutor',
            value: Ext.getCmp('idCondutor').rawValue,
            readOnly: true
        });

        var comboTipoMovimento = Ext.create('Ext.form.ComboBox', {
            id: 'idTipoMovimento',
            fieldLabel: 'Movimento',
            labelWidth: 60,
            width: 408,
            style: 'margin-left:3px;margin-top:8px;margin-bottom:8px;',
            //queryMode: 'local',
            name: 'descTpMovimento',
            displayField: 'descTpMovimento',
            valueField: 'idTpMovimento',
            store: storeTipoMovimento,
            //readOnly: <?=($empresaUsu)?'true':'false'?>,
            //hidden: <?=($empresaUsu)?'true':'false'?>,
            emptyText: 'Selecione uma opção...',
            allowBlank: true
//            listeners:{
//                select: function(f, r, i){			  
//                    storeCondutorAvancado.getProxy().extraParams = { idEmpresa: f.getValue()};
//                    storeCondutorAvancado.load();
//                    Ext.getCmp("idCondutor").setValue('');			   
//
//                    Ext.getCmp('gridCartaoId').columns[3].setVisible(r[0].get('he100')=='t');				
//                }   
//            }
        });

        var dateIniAvan = Ext.create('Ext.form.DateField', {
            id: 'idDtIniBcHoras',
            fieldLabel: 'Data inicial',
            labelWidth: 70,
            width: 170,
            style: 'margin-left:3px;margin-top:8px;margin-bottom:8px;',
            value: '', 
            format: "d/m/Y",
            emptyText: '00/00/0000',
            readOnly: false,
            allowBlank: false
        });

        var dateFimAvan = Ext.create('Ext.form.DateField', {
            id: 'idDtFimBcHoras',
            fieldLabel: 'Data final',
            labelWidth: 70,
            width: 170,
            style: 'margin-left:60px; margin-top:8px;margin-bottom:8px;',
            value: '',
            format: "d/m/Y",
            emptyText: '00/00/0000',
            readOnly: false,
            allowBlank: false
        });
                
        // custom Vtype for vtype:'time'
        var timeTest = /^\d{1,3}:([0-5][0-9])$/i;
        Ext.apply(Ext.form.field.VTypes, {
            time: function(val, field) {
                return timeTest.test(val);
            },
            // vtype Text property: The error text to display when the validation function returns false
            timeText: 'Por Favor utilize um formato de hora válido como "99:59" ou "999:59"',
            // vtype Mask property: The keystroke filter mask
            timeMask: /[0-999:0-59]/
        });        
        
        var horaIni = Ext.create('Ext.form.TextField', {
            id: 'idHrIniAcumPerAnterior',
            fieldLabel: 'Total de horas do Banco de Dados',
            labelWidth: 200,
            maxLength: 6,
            width: 400,
            style: 'margin-left:3px;margin-top:8px;margin-bottom:15px;',
            name: 'nameTxtHoraInicial',
            emptyText: '000:00',
            value: '',
            readOnly: true,
            vtype: 'time',
            allowBlank: true
        });

        var horaFim = Ext.create('Ext.form.TextField', {
            id: 'idHrMovimento',
            fieldLabel: 'Montante de Horas para o Movimento',
            labelWidth: 215,
            maxLength: 6,
            width: 270,
            style: 'margin-left:3px;margin-top:8px;margin-bottom:15px;',
            name: 'nameTxtHoraFinal',
            emptyText: '000:00',
            value: '',
            readOnly: true,
            vtype: 'time',
            allowBlank: true
        });

        var btnLimpar = Ext.create('Ext.Button', {
            xtype: 'button', 
            text: 'Limpar',
            iconCls: 'clear',
            handler: function() {
//                Ext.getCmp('idFechamentoBancoHoras').getForm().reset();
            }
        });

        var btnEnviar = Ext.create('Ext.button.Button', {
            xtype: 'button', 
            text: 'Enviar',
            formBind: true,
            disabled: true,
            iconCls: 'filter',               
/*========================================================================================================================================*/                
            handler: function() {  
                var inclusao = Ext.getCmp('radioInclusao'),
                    ajuste = Ext.getCmp('radioAjuste'),
                    fechamento = Ext.getCmp('radioFechamento'),                    
                    relatorio = Ext.getCmp('radioRelatorio'),
                    mask = new Ext.LoadMask('gridBancoHorasId', {msg: "Carregando..."}),
                    acao = '';
                            
                if(inclusao.checked) {
                    acao = 'inclusao';
                } else if(ajuste.checked) {
                    acao = 'ajuste';
                } else if(fechamento.checked) {
                    acao = 'fechamento';
                } else if(relatorio.checked) {
                    acao = 'relatorio';
                };
                          
                                                                              
                    Ext.getCmp('formFechamentoBancoHoras').close();          
                    mask.show();
                    
                    if(acao == 'relatorio') {
                        panelLoad('gestorTabId', 'Banco de Horas - Relatório de Fechamento', 'relatorios/consFechamentoBcHoras.php', 'idCondutor='+Ext.getCmp('idCondutor').value+'&dtIniBcHoras='+Ext.Date.format(dateIniAvan.value, 'd/m/Y')+'&dtFimBcHoras='+Ext.Date.format(dateFimAvan.value, 'd/m/Y'));
                    } else {                    
                        Ext.Ajax.request({
                            //async: false,
                            url: 'exec/execBancoDeHoras.php',
                            method: 'POST',
                            params: {
                                idCondutor: Ext.getCmp('idCondutor').value,
                                idEmpresa: '<?=$empresaUsu?>',
                                acao: acao,
                                dtIni: Ext.Date.format(dateIniAvan.value, 'd-m-Y'),
                                dtFim: Ext.Date.format(dateFimAvan.value, 'd-m-Y'),
                                hrAcumPerAnt: horaIni.value,
                                hrMovimento: horaFim.value,
                                tipoMovimento: comboTipoMovimento.value
                                //dataGeracao: Ext.Date.format(Ext.getCmp('idDateRegera').value, 'Y-m-d')
                            },
                            success: function(conn, response, options, eOpts) {
                                mask.hide();
                                var result = Ext.decode(conn.responseText);
                                //console.info('Status de retorno ajax inclusao banco de horas: ', result.status);
                                if (result.status === 'ERRO') {
                                    Ext.Msg.show({
                                        title: 'Erro!',
                                        msg: result.msg,
                                        icon: Ext.Msg.ERROR,
                                        buttons: Ext.Msg.OK
                                    });
                                } else {
                                    //Ext.getCmp('idGridAfastamento').store.reload();
                                    Ext.Msg.show({
                                        title: 'Informação',
                                        msg: result.msg,
                                        icon: Ext.Msg.INFO,
                                        buttons: Ext.Msg.OK
                                    });
                                }

        //                        var maskCp = new Ext.LoadMask('gestorRelId', {msg:"Carregando..."});
        //                        maskCp.show();
        //                        storeCartao.load({
        //                            params:{
        //                                'dtIni': Ext.Date.format(Ext.getCmp('idDateIni').value, 'dmY'),
        //                                'dtFim': Ext.Date.format(Ext.getCmp('idDateFim').value, 'dmY'),
        //                                'idCondutor':Ext.getCmp('idCondutor').value	
        //                            },
        //                            callback:function(){
        //                                maskCp.hide();
        //                            }
        //                        });
                            },
                            failure: function(){
                                console.log('failure');
        //                        mask.hide();
                            }
                        });
                    }
            }                
/*========================================================================================================================================*/                                            
        });

        var optsBcHorasCondutor = Ext.create('Ext.form.FieldSet', {
            id: 'idOptsBcHorasCondutor',
            bodyPadding: 5,
            title: 'Opções',
            width: '100%',
            layout: 'anchor',
            defaults: {
                border: false
            },
            items: [
                {
                    xtype: 'radiogroup',
                    fieldLabel : 'Selecionar',
                    labelWidth: 70,
//                    style: 'margin-top:8px;margin-bottom:15px;',
                    items: [                        
                        {
                            boxLabel: 'Novo banco de horas',
                            name: 'acao',
                            inputValue: 1,
                            id: 'radioInclusao'
                        },
                        {
                            boxLabel: 'Relatório',
                            name: 'acao',
                            inputValue: 4,
                            id: 'radioRelatorio',
                            checked: true
                        }
                    ],
                    listeners: {
                        change: function (field, newValue, oldValue) {
                            switch (newValue['acao']) {
                                case 1:
                                    console.log('1...');
                                    dateIniAvan.setReadOnly(false);
                                    dateFimAvan.setReadOnly(false);
                                    horaIni.allowBlank = true;
                                    horaIni.setDisabled(true);
                                    horaIni.setReadOnly(true);
                                    comboTipoMovimento.allowBlank = true;
                                    //comboTipoMovimento.setDisabled(true);
                                    horaFim.allowBlank = true;
                                    horaFim.setReadOnly(false);
                                break;                                
                                case 2:
                                    console.log('2...');
                                    dateIniAvan.setReadOnly(true);
                                    dateFimAvan.setReadOnly(true);
                                    comboTipoMovimento.allowBlank = false;
                                    comboTipoMovimento.setDisabled(false);
                                    horaIni.setDisabled(false);
                                    horaIni.setReadOnly(true);                                    
                                    horaFim.allowBlank = false;
                                    horaFim.setDisabled(false);
                                    horaFim.setReadOnly(false);
                                break;
                                case 3:
                                    console.log('3...');
                                    dateIniAvan.setReadOnly(true);
                                    dateFimAvan.setReadOnly(true);
                                    comboTipoMovimento.allowBlank = false;
                                    comboTipoMovimento.setDisabled(false);
                                    horaIni.allowBlank = true;
                                    horaIni.setDisabled(false);
                                    horaFim.allowBlank = false;
                                    horaFim.setDisabled(false);
                                    horaFim.setReadOnly(false);
                                break;  
                                case 4:
                                    console.log('4...');
                                    dateIniAvan.setReadOnly(false);
                                    dateFimAvan.setReadOnly(false);
                                    comboTipoMovimento.allowBlank = true;
                                    comboTipoMovimento.setDisabled(false);
                                    horaIni.allowBlank = true;
                                    horaIni.setDisabled(true);
                                    horaFim.allowBlank = true;
                                    horaFim.setDisabled(true);                                    
                                break;                                 
                            }
                        }
                    }
                }                
            ]
        });
        
        var dadosBcHorasCondutor = Ext.create('Ext.form.FieldSet', {
            id: 'idDadosBcHorasCondutor',
            bodyPadding: 5,
            title: 'Dados Gerais',
            width: '100%',
            layout: 'anchor',
            defaults: {
                border: false
            },
            items: [
                {
                    layout: 'column',
                    items: [
                        {xtype: dateIniAvan},
                        {xtype: dateFimAvan}
                    ]
                },
                {
                    layout: 'column',
                    items: [
                        {xtype: horaIni},
                        //{xtype: horaFim}
                    ]
                },

            {
                xtype: 'radiogroup',
//                fieldLabel : 'Selecionar',
                labelWidth: 70,
//                    style: 'margin-top:8px;margin-bottom:15px;',
                items: [
                    {
                        boxLabel: 'Criar para todos os condutores',
                        name: 'acao',
                        inputValue: 1,
                        id: 'radioTodos'
                    },
                    {
                        boxLabel: 'Criar para um condutor',
                        name: 'acao',
                        inputValue: 4,
                        id: 'radioIndividual',
                        checked: true
                    }
                ],
                listeners: {
                    change: function (field, newValue, oldValue) {
                        switch (newValue['acao']) {
                            case 1:
                                console.log('1...');
                                dateIniAvan.setReadOnly(false);
                                dateFimAvan.setReadOnly(false);
                                horaIni.allowBlank = true;
                                horaIni.setDisabled(true);
                                horaIni.setReadOnly(true);
                                comboTipoMovimento.allowBlank = true;
                                //comboTipoMovimento.setDisabled(true);
                                horaFim.allowBlank = true;
                                horaFim.setReadOnly(false);
                                break;
                            case 2:
                                console.log('2...');
                                dateIniAvan.setReadOnly(true);
                                dateFimAvan.setReadOnly(true);
                                comboTipoMovimento.allowBlank = false;
                                comboTipoMovimento.setDisabled(false);
                                horaIni.setDisabled(false);
                                horaIni.setReadOnly(true);
                                horaFim.allowBlank = false;
                                horaFim.setDisabled(false);
                                horaFim.setReadOnly(false);
                                break;
                            case 3:
                                console.log('3...');
                                dateIniAvan.setReadOnly(true);
                                dateFimAvan.setReadOnly(true);
                                comboTipoMovimento.allowBlank = false;
                                comboTipoMovimento.setDisabled(false);
                                horaIni.allowBlank = true;
                                horaIni.setDisabled(false);
                                horaFim.allowBlank = false;
                                horaFim.setDisabled(false);
                                horaFim.setReadOnly(false);
                                break;
                            case 4:
                                console.log('4...');
                                dateIniAvan.setReadOnly(false);
                                dateFimAvan.setReadOnly(false);
                                comboTipoMovimento.allowBlank = true;
                                comboTipoMovimento.setDisabled(false);
                                horaIni.allowBlank = true;
                                horaIni.setDisabled(true);
                                horaFim.allowBlank = true;
                                horaFim.setDisabled(true);
                                break;
                        }
                    }
                }
            },{
                layout: 'column',
                items: [
                    {
                        xtype: 'combobox',
                        fieldLabel: 'Condutor',
                        queryMode: 'local',
                        id: 'idComboCondutorBancoHora',
                        name: 'idAfastMotivo',
                        displayField: 'descricao',
                        style: 'margin-left:3px;margin-top:8px;margin-bottom:8px;',
                        valueField: 'idMotivo',
                        value: '',
//                        store: storeMotivos,
                        emptyText: 'Selecione a opção',
                        submitEmptyText: false,
                        allowBlank: false
                    }
                ]
            }]
        });

        Ext.create('Ext.form.Panel', {
            id: 'idFechamentoBancoHoras',
            bodyPadding: 5,
            buttonAlign : 'center',
            width: '100%',
            height: '100%',
            layout: 'anchor',
            defaults: {
                anchor: '100%',
                border: false
            },
            items: [
//                {xtype: optsBcHorasCondutor},
                {xtype: dadosBcHorasCondutor}//,
//                {xtype: movBcHorasCondutor}
//                {
//                    layout: 'column',
//                    items: [
//                        {xtype: dateIniAvan},
//                        {xtype: dateFimAvan}
//                    ]
//                },
//                {
//                    layout: 'column',
//                    items: [
//                        {xtype: horaIni},
//                        {xtype: horaFim}
//                    ]
//                }
            ],
//            buttons: [
//                {xtype: btnLimpar},
//                {xtype: btnEnviar}
//            ],
//            bbar: [
//                {xtype: btnLimpar},
//                {xtype: btnEnviar}
//            ],
            renderTo: 'divFilAvancado'
        });
    });           
</script>
<div id="divFilAvancado"></div>

