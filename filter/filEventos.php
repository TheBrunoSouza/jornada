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
    $iEmpresa = $_REQUEST['idEmpresa'];
    $condutorFiltroInicial = $_REQUEST['condutor'];
?>
<script>    
    //Verificação de: Usuário acessando é cliente ou um Adm do sistema
    var idEmpresa = '<?=$CtrlAcesso->getUserEmpresa($_SESSION)?>';

    if(idEmpresa == ''){
        console.info('Usuário sem empresa - Administrador');
    }

    Ext.onReady(function () {
        Ext.define('EmpresasEv', {
            extend: 'Ext.data.Model',
            fields:[
                {name: 'idEmpresa', type: 'int'},
                {name: 'nmEmpresa', type: 'string'},
                {name: 'respEmpresa', type: 'string'},
                {name: 'telEmpresa', type: 'string'},
                {name: 'emailEmpresa', type: 'string'},
                {name: 'cepEmpresa', type: 'string'},
                {name: 'ufEmpresa', type: 'string'},
                {name: 'cidEmpresa', type: 'string'},
                {name: 'VeicEmpresa', type: 'string'},
                {name: 'he100', type: 'string'}
            ]
        });

        Ext.define('CondutoresEv', {
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

        Ext.define('Veiculos', {
            extend: 'Ext.data.Model',
            requires : 'Ext.data.Model',
            fields:[
                {name: 'placa', type: 'string'}
            ]
        });

        var storeEmpresaEv = Ext.create('Ext.data.Store', {
            model: 'EmpresasEv',
            autoLoad : <?=$loadEmpresa?>,
            proxy: {
                type: 'ajax',
                url: 'json/jsonEmpresas.php',
                reader: {
                    type: 'json',
                    root: 'empresas'
                }
            }
        });

        var storeCondutorEv = Ext.create('Ext.data.Store', {
            model: 'CondutoresEv',
            autoLoad: true,
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

        var storeVeiculoEv = Ext.create('Ext.data.Store', {
            model: 'Veiculos',
            autoLoad: true,
            proxy: {
                type: 'ajax',
                url: 'json/jsonVeiculos.php',
                reader: {
                    type: 'json',
                    root: 'veiculos'
                },
                extraParams: {
                    idEmpresa: idEmpresa,
                    excluiInativos: true
                }
            }
        });

        var comboCondutorEv = Ext.create('Ext.form.ComboBox', {
            fieldLabel: 'Nome:',
            labelWidth: 55,
            width: 415,
            style: 'margin-top: 6px; margin-bottom: 6px;',
            queryMode: 'local',
            id: 'idComboCondutorEv',
            name: 'idCondutor',
            displayField: 'nmCondutor',
            valueField: 'idCondutor',
            value: '',
            store: storeCondutorEv,
            emptyText: 'Selecione um condutor...'
        });    

        var comboEmpresaEv = Ext.create('Ext.form.ComboBox', {
            id: 'idComboEmpresa',
            fieldLabel: 'Empresa',
            labelWidth: 55,
            width: 509,
            style: 'margin-top: 4px;',
            queryMode: 'local',
            name: 'idempresaPonto',
            displayField: 'nmEmpresa',
            valueField: 'idEmpresa',
            store: storeEmpresaEv,
            readOnly: <?=($empresaUsu)?'true':'false'?>,
            hidden: <?=($empresaUsu)?'true':'false'?>,
            emptyText: 'Selecione uma empresa...',
            listeners: {
                select: function(f, r, i) {
                    //Condutores
                    storeCondutorEv.getProxy().extraParams = { idEmpresa: f.getValue()};
                    storeCondutorEv.load();
                    Ext.getCmp("idComboCondutorEv").setValue('');

                    //Placas
                    storeVeiculoEv.getProxy().extraParams = { idEmpresa: f.getValue()};
                    storeVeiculoEv.load();
                    Ext.getCmp("idComboPlacaEv").setValue('');
                }   
            }
        });

        var comboVeiculoEv = Ext.create('Ext.form.ComboBox', {
            fieldLabel: 'Placa',
            labelWidth: 55,
            width: 210,
            queryMode: 'local',
            id: 'idComboPlacaEv',
            name: 'placa',
            displayField: 'placa',
            valueField: 'placa',
            value: '',
            store: storeVeiculoEv,
            emptyText: 'Selecione uma placa...'
        });

        var dateFimEv = Ext.create('Ext.form.DateField', {
            id: 'idDateFimEv',
            fieldLabel: 'Data final',
            labelWidth: 70,
            width: 170,
            style: 'margin-left:70px;margin-top:3px;margin-bottom:6px;',  
            value: new Date(),
            maxLength: 10,
            minLength: 10,
            maskRe: /[0-9/]/,
            format: "d/m/Y",
            emptyText: 'dd/mm/aaaa',
            maxValue: new Date()
        });    

        var dateIniEv = Ext.create('Ext.form.DateField', {
            id: 'idDateIniEv',
            fieldLabel: 'Data inicial:',
            labelWidth: 70,
            width: 170,
            style: 'margin-left:3px;margin-top:3px;margin-bottom:6px;',
            value: Ext.Date.add(new Date(), Ext.Date.MONTH, -1),
            maxLength: 10,
            minLength: 10,
            maskRe: /[0-9/]/,
            format: "d/m/Y",
            emptyText: 'dd/mm/aaaa',
            maxValue: new Date(),
            listeners: {
                'afterrender': function(me) {
                    setMinDateFieldFim(dateFimEv, me.getSubmitValue());
                },
                'change': function(me) {
                    setMinDateFieldFim(dateFimEv, me.getSubmitValue());
                }
            }        
        });

        var checkJornada = Ext.create('Ext.form.field.Checkbox', {
            id: 'ckbJornadaEv',
            name: 'ckbJornada',
            boxLabel: 'Jornada Completa',
            labelWidth: 40,
            trueText: 'T',
            falseText: 'F',
            checked: false
        });

        var buttonLimparEv = Ext.create('Ext.Button', {
            xtype: 'button', 
            text: 'Limpar',
            iconCls: 'clear',
            handler: function() {
                Ext.getCmp('idPanelFilEvento').getForm().reset();
            }
        });

        var buttonFiltrarEv = Ext.create('Ext.button.Button', {
            xtype: 'button', 
            text: 'Filtrar',
            formBind: true,
            disabled: true,
            iconCls: 'filter',
            handler: function() {
                if(Ext.getCmp('idComboPlacaEv').value == '' && Ext.getCmp('idComboCondutorEv').value == ''){
                    Ext.Msg.show({
                        title:'Informação:',
                        msg: 'Você precisa selecionar condutor ou placa.',
                        icon: Ext.Msg.INFO,
                        buttons: Ext.Msg.OK
                      });
                }else{
                    var mask = new Ext.LoadMask('filtro_eventos', {msg:"Carregando..."});
                    mask.show();

                    Ext.getCmp("relEventosId").reconfigure(storeEventos);

                    Ext.getDom('dataIniRel').value  = Ext.Date.format(Ext.getCmp('idDateIniEv').value, 'd/m/Y');
                    Ext.getDom('dataFimRel').value  = Ext.Date.format(Ext.getCmp('idDateFimEv').value, 'd/m/Y');

                    storeEventos.load({
                        params:{
                            'dtIni': Ext.Date.format(Ext.getCmp('idDateIniEv').value, 'dmY'),
                            'dtFim': Ext.Date.format(Ext.getCmp('idDateFimEv').value, 'dmY'),
                            'idCondutor':Ext.getCmp('idComboCondutorEv').value,
                            'placa': Ext.getCmp('idComboPlacaEv').value,
                            'ckbJornada': Ext.getCmp('ckbJornadaEv').value,
                            'origem': 'relatorio',
                            'idEmpresa': '<?=$empresaUsu?>'
                        },
                        callback:function(records, operation, success) {
                            mask.hide();
                            Ext.getCmp('filtro_eventos').close();
                        }
                    });
                }
            }
        });

        var panelCondutorEv = Ext.create('Ext.form.FieldSet', {
            id: 'idPanelCondutorEv',
            bodyPadding: 5,
            title: '<b>Condutor</b>',
            width: '100%',
            layout: 'anchor',
            defaults: {
                border: false
            },
            items: [
                {xtype: comboEmpresaEv},
                {
                    layout: 'column',
                    items: [
                        {xtype: comboCondutorEv}                    
                    ]
                }
            ]
        });

        var panelVeiculoEv = Ext.create('Ext.form.FieldSet', {
            id: 'idPanelVeiculoEv',
            bodyPadding: 5,
            title: '<b>Veículo</b>',
            width: '100%',
            layout: 'anchor',
            defaults: {
                border: false
            },
            items: [
                {xtype: comboVeiculoEv}
            ]
        });

        var panelOutros = Ext.create('Ext.form.FieldSet', {
            id: 'idPanelOutrosEv',
            bodyPadding: 5,
            title: '<b>Outros</b>',
            width: '100%',
            layout: 'anchor',
            defaults: {
                border: false
            },
            items: [
                {xtype: checkJornada}
            ]
        });

        var panelDatepickers = Ext.create('Ext.form.FieldSet', {
            id: 'idPanelDatepickers',
            bodyPadding: 5,
            title: '<b>Período</b>',
            width: '100%',
            layout: 'anchor',
            defaults: {
                border: false
            },
            items: [
                {
                    layout: 'column',
                    items: [
                        {xtype: dateIniEv},
                        {xtype: dateFimEv}
                    ]
                }
            ]
        });    

        Ext.create('Ext.form.Panel', {
            id: 'idPanelFilEvento',
            bodyPadding: 5,
            buttonAlign : 'center',
            width: '100%',
            layout: 'anchor',
            defaults: {
                anchor: '100%',
                border: false
            },
            items: [
                {xtype: panelCondutorEv},
                {xtype: panelVeiculoEv},
                {xtype: panelOutros},
                {xtype: panelDatepickers}
            ],
            buttons: [
                {xtype: buttonLimparEv},
                {xtype: buttonFiltrarEv}
            ],
            renderTo: 'divFilAvancado'
        });
    });

    function setMinDateFieldFim(dateMin, dati) {
        dateMin.setMinValue(dati);
    }
</script>
<div id="divFilAvancado"></div>