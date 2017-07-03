<?php
    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');

    $OraCielo   = new OracleCielo();
    $conexaoOra = $OraCielo->getCon();
    $CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexaoOra);
    $idEmpresa  = $_REQUEST['idEmpresa'];

    if(isset($_SESSION)) {
        $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
    } else {
        header('Location: http://jornada.cielo.ind.br');
    }

?>
<script>
    function setMinDateFieldFim(dateMin, dati) {
        dateMin.setMinValue(dati);
    }

    Ext.onReady(function () {

        Ext.getCmp('buttonCancelar').handler = function(){
            Ext.getCmp('idFormPeriodo').getForm().reset();
        };

        Ext.getCmp('buttonFiltrar').handler = function(){
            var mask = new Ext.LoadMask('filterBancoFechamento', {msg: "Aguarde..."});
            mask.show();
            Ext.getCmp('gridBCFechamento').getStore().load({
                params: {
                    'idEmpresa': '<?=$idEmpresa?>',
                    'idCondutor': Ext.getCmp('idComboCondutorFechamento').value,
                    'tipoFechamento': Ext.getCmp('idComboTipoFechamento').value,
                    'dataIniBF': Ext.Date.format(Ext.getCmp('dataIniBF').value, 'd/m/Y'),
                    'dataFimBF': Ext.Date.format(Ext.getCmp('dataFimBF').value, 'd/m/Y'),
                    'dataIniBC': Ext.Date.format(Ext.getCmp('dataIniBC').value, 'd/m/Y'),
                    'dataFimBC': Ext.Date.format(Ext.getCmp('dataFimBC').value, 'd/m/Y'),
                    'radioPendente': (Ext.getCmp('radioPendente').value == true)?'true':'false',
                    'radioFechado': (Ext.getCmp('radioFechado').value == true)?'true':'false',
                    'radioTodos': (Ext.getCmp('radioTodos').value == true)?'true':'false'
                },
                callback: function(records, operation, success) {
                    Ext.getCmp('filterBancoFechamento').close();
                    mask.hide();
                }
            });
        };

        Ext.define('CondutoresFechamento', {
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

        Ext.define('tipoFechamento', {
            extend: 'Ext.data.Model',
            requires: 'Ext.data.Model',
            fields:[
                {name: 'idTpFechamento', type: 'int'},
                {name: 'descTpFechamento', type: 'string'}
            ]
        });

        var storeCondutorFechamento = Ext.create('Ext.data.Store', {
            model: 'CondutoresFechamento',
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

        var storeTipoFechamento = Ext.create('Ext.data.Store', {
            model: 'tipoFechamento',
            autoLoad : true,
            proxy: {
                type: 'ajax',
                url: 'json/jsonBancoTpFechamento.php',
                reader: {
                    type: 'json',
                    root: 'tipoFechamentos'
                }
            }
        });

        var comboCondutorFechamento = Ext.create('Ext.form.ComboBox', {
            fieldLabel: 'Condutor',
            queryMode: 'local',
            id: 'idComboCondutorFechamento',
            emptyText: 'Selecione...',
            allowBlank: true,
            valueField: 'idCondutor',
            displayField: 'nmCondutor',
            disabled: false,
            value: '<?$idCondutor?>',
            store: storeCondutorFechamento
        });

        var comboTipoFechamento = Ext.create('Ext.form.ComboBox', {
            fieldLabel: 'Tipo fechamento',
            queryMode: 'local',
            id: 'idComboTipoFechamento',
            emptyText: 'Selecione...',
            allowBlank: true,
            valueField: 'idTpFechamento',
            displayField: 'descTpFechamento',
            disabled: true,
            store: storeTipoFechamento
        });

        var dataFimBC = Ext.create('Ext.form.DateField', {
            id: 'dataFimBC',
            fieldLabel: 'Banco fim',
            maxLength: 10,
            minLength: 10,
            maskRe: /[0-9/]/,
            format: "d/m/Y",
            emptyText: '00/00/00',
            value: '<?($dataFimBF == '')?'':$dataFimBF?>',
            maxValue: new Date(),
            submitEmptyText: false,
            allowBlank: true,
            disabled: false
        });

        var dataIniBC = Ext.create('Ext.form.DateField', {
            id: 'dataIniBC',
            fieldLabel: 'Banco inicio',
            labelWidth: 140,
            maxLength: 10,
            minLength: 10,
            minValue: Ext.Date.add(new Date(), Ext.Date.MONTH, -4),
            maskRe: /[0-9/]/,
            format: "d/m/Y",
            emptyText: '00/00/00',
            value: '<?($dataIniBF == '')?'':$dataIniBF?>',
            submitEmptyText: false,
            allowBlank: true,
            disabled: false,
            listeners: {
                'afterrender': function(me) {
                    setMinDateFieldFim(dataFimBC, me.getSubmitValue());
                },
                'change': function(me) {
                    setMinDateFieldFim(dataFimBC, me.getSubmitValue());
                }
            }
        });

        var dataFimBF = Ext.create('Ext.form.DateField', {
            id: 'dataFimBF',
            fieldLabel: 'Até',
            maxLength: 10,
            minLength: 10,
            maskRe: /[0-9/]/,
            format: "d/m/Y",
            emptyText: '00/00/00',
            submitEmptyText: false,
            allowBlank: true,
            disabled: true
        });

        var dataIniBF = Ext.create('Ext.form.DateField', {
            id: 'dataIniBF',
            fieldLabel: 'Fechamento entre',
            labelWidth: 140,
            maxLength: 10,
            minLength: 10,
            maskRe: /[0-9/]/,
            format: "d/m/Y",
            emptyText: '00/00/00',
            submitEmptyText: false,
            allowBlank: true,
            disabled: true,
            listeners: {
                'afterrender': function(me) {
                    setMinDateFieldFim(dataFimBF, me.getSubmitValue());
                },
                'change': function(me) {
                    setMinDateFieldFim(dataFimBF, me.getSubmitValue());
                }
            }
        });

        var timeTest = /^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/;
        Ext.apply(Ext.form.field.VTypes, {
            time: function(val, field) {
                return timeTest.test(val);
            },
            timeText: 'Por Favor, utilize um formato de hora válido. Ex: "08:00"',
            timeMask: /[0-999:0-59]/
        });
        
        var dadosBcHorasCondutor = Ext.create('Ext.form.FieldSet', {
            id: 'idDadosBcHorasCondutor',
            bodyPadding: 5,
            title: '<b>Dados Gerais</b>',
            width: '100%',
            layout: 'anchor',
            defaults: {
                border: false
            },
            items: [
                {
                    xtype: 'form',
                    layout: 'form',
                    id: 'idFormPeriodo',
                    border: false,
                    bodyPadding: 5,
                    items: [
                        {xtype: comboCondutorFechamento},
                        {xtype: dataIniBC},
                        {xtype: dataFimBC},
                        {
                            fieldLabel: 'Situação',
                            xtype: 'radiogroup',
                            id: 'radioTodosIndividual',
                            items: [
                                {
                                    boxLabel: 'Todos',
                                    name: 'acao',
                                    inputValue: 1,
                                    id: 'radioTodos',
                                    checked: true
                                },
                                {
                                    boxLabel: 'Pendente',
                                    name: 'acao',
                                    inputValue: 1,
                                    id: 'radioPendente',
                                    style: 'margin-left:-04px;',
                                    checked: false
                                },
                                {
                                    boxLabel: 'Fechado',
                                    name: 'acao',
                                    inputValue: 2,
                                    id: 'radioFechado',
                                    checked: false
                                }
                            ],
                            listeners: {
                                change: function (field, newValue, oldValue) {
                                    switch (newValue['acao']) {
                                        case 1:
                                            comboTipoFechamento.allowBlank = true;
                                            comboTipoFechamento.setDisabled(true);
                                            comboTipoFechamento.setReadOnly(true);
                                            dataIniBF.allowBlank = true;
                                            dataIniBF.setDisabled(true);
                                            dataIniBF.setReadOnly(true);
                                            dataFimBF.allowBlank = true;
                                            dataFimBF.setDisabled(true);
                                            dataFimBF.setReadOnly(true);
                                            break;
                                        case 2:
                                            comboTipoFechamento.allowBlank = false;
                                            comboTipoFechamento.setDisabled(false);
                                            comboTipoFechamento.setReadOnly(false);
                                            dataIniBF.allowBlank = false;
                                            dataIniBF.setDisabled(false);
                                            dataIniBF.setReadOnly(false);
                                            dataFimBF.allowBlank = false;
                                            dataFimBF.setDisabled(false);
                                            dataFimBF.setReadOnly(false);
                                            break;
                                    }
                                }
                            }
                        },
                        {xtype: comboTipoFechamento},
                        {xtype: dataIniBF},
                        {xtype: dataFimBF}
                    ]
                }
            ]
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
                {xtype: dadosBcHorasCondutor}
            ],
            renderTo: 'divFilAvancado'
        });
    });           
</script>
<div id="divFilAvancado"></div>

