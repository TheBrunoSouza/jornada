<?php
    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');

    $OraCielo       = new OracleCielo();
    $conexaoOra     = $OraCielo->getCon();
    $CtrlAcesso     = new Controles($_SERVER['REMOTE_ADDR'], $conexaoOra);
    $idEmpresaBH    = $_REQUEST['idEmpresaBH'];

    if(isset($_SESSION)) {
        $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
    } else {
        header('Location: http://jornada.cielo.ind.br');
    }

?>
<script>        
    Ext.onReady(function () {

        Ext.getCmp('buttonCancelar').handler = function(){
            Ext.getCmp('formManutBancoHoras').close();
        };

        Ext.getCmp('buttonFiltrar').handler = function(){

            var idPeriodoBH         = Ext.getCmp('idComboPeriodo').value,
                vencimentoBH        = Ext.Date.format(Ext.getCmp('dataIniBH').value, 'd'),
                dataIniBH           = Ext.Date.format(Ext.getCmp('dataIniBH').value, 'd/m/Y'),
                horasDiasUteisBH    = Ext.getCmp('idTotalHorasDiasUteis').value,
                horasSabadosBH      = Ext.getCmp('idHorasSabado').value,
                idEmpresaBH         = '<?=$idEmpresaBH?>';

                if (
                    idPeriodoBH           == '' ||
                    idPeriodoBH           == null ||
                    //vencimentoBH        == '' ||
                    dataIniBH           == null ||
                    horasDiasUteisBH == '' ||
                    horasSabadosBH == '') {

                    Ext.Msg.show({
                        title: 'Atenção:',
                        msg: 'Você deve preencher os campos corretamente',
                        icon: Ext.Msg.WARNING,
                        buttons: Ext.Msg.OK
                    });
                } else {
                    Ext.getCmp('formManutBancoHoras').close();

                    Ext.Msg.confirm('Saldo inicial:', 'Deseja lançar saldo inicial para determinado condutor manualmente?', function (button) {
                        if (button == 'yes') {

                            var buttonInserirCredDeb = Ext.create('Ext.Button', {
                                id: 'buttonProx',
                                text: 'Finalizar',
                                icon: 'imagens/16x16/database_save.png',
                                handler: function () {
                                    console.info('ok');
                                }
                            });

                            var buttonCancelar = Ext.create('Ext.Button', {
                                id: 'buttonCancelar',
                                text: 'Cancelar',
                                icon: 'imagens/16x16/cancel.png',
                                handler: function () {
                                    console.info('ok');
                                }
                            });

                            var toolbarSaldo = Ext.create('Ext.toolbar.Toolbar', {
                                id: 'teste',
                                region: 'south',
                                items: [
                                    '->',
                                    {xtype: buttonCancelar},
                                    {xtype: buttonInserirCredDeb}
                                ]
                            });

                            showWindowToolbar(
                                'cadSaldoIniCondutor',
                                'Registrar saldo incial',
                                'cad/cadSaldoIniCondBH.php',
                                'idPeriodoBH=' + idPeriodoBH+ '&vencimentoBH=' + vencimentoBH+ '&dataIniBH=' + dataIniBH+ '&horasDiasUteisBH=' + horasDiasUteisBH + '&horasSabadosBH=' + horasSabadosBH + '&idEmpresaBH=' + idEmpresaBH,
                                450,
                                250,
                                true,
                                true,
                                toolbarSaldo
                            );
                        } else {
                            var mask = new Ext.LoadMask('gestorTabId', {msg: "Gerando dados do período..."});
                            mask.show();
                            Ext.Ajax.request({
                                url: 'exec/execBancoHoras.php',
                                timeout: 60000,
                                params: {
                                    idPeriodoBH: idPeriodoBH,
                                    vencimentoBH: vencimentoBH,
                                    dataIniBH: dataIniBH,
                                    horasDiasUteisBH: horasDiasUteisBH,
                                    horasSabadosBH: horasSabadosBH,
                                    idEmpresaBH: idEmpresaBH,
                                    acao: 'create'
                                },
                                success: function (conn, response, options, eOpts) {
                                    mask.hide();
                                    var result = Ext.decode(conn.responseText);
                                    if (result.status == 'ERRO') {
                                        Ext.Msg.show({
                                            title: 'Erro!',
                                            msg: 'Favor informar o departamento de TI.',
                                            icon: Ext.Msg.ERROR,
                                            buttons: Ext.Msg.OK
                                        });
                                    } else {
                                        Ext.Msg.show({
                                            title:'Sucesso!',
                                            msg: result.msg,
                                            icon: Ext.Msg.INFO,
                                            buttons: Ext.Msg.OK
                                        });
                                        storeBancoCondutor.load({
                                            params: {
                                                'idEmpresa': '<?=$idEmpresaBH?>'
                                            },
                                            callback: function(records, operation, success) {
                                                //console.info('entrou callback');
                                            }
                                        });
                                    }
                                },
                                failure: function (conn, response, options, eOpts) {
                                    mask.hide()
                                    Ext.Msg.show({
                                        title: 'Erro!',
                                        msg: 'Entre em contato com o administrador do sistema.',
                                        icon: 'imagens/16x16/accept.png',
                                        buttons: Ext.Msg.OK
                                    });
                                }
                            });
                        }
                    });
                }
//            }
        };

        Ext.define('CondutoresBancoHoras', {
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

        Ext.define('periodo', {
            extend: 'Ext.data.Model',
            requires: 'Ext.data.Model',
            fields:[
                {name: 'idPeriodo', type: 'int'},
                {name: 'descPeriodo', type: 'string'},
                {name: 'totalSemanas', type: 'int'}
            ]
        });

        var storeCondutorBancoHora = Ext.create('Ext.data.Store', {
            model: 'CondutoresBancoHoras',
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

        var storePeriodo = Ext.create('Ext.data.Store', {
            model: 'periodo',
            autoLoad : true,
            proxy: {
                type: 'ajax',
                url: 'json/jsonBancoPeriodo.php',
                reader: {
                    type: 'json',
                    root: 'periodos'
                }
            }
        });

        var comboCondutorBancoHora = Ext.create('Ext.form.ComboBox', {
            fieldLabel: 'Condutor',
            queryMode: 'local',
            id: 'idComboCondutorBancoHora',
            emptyText: 'Selecione a opção',
            allowBlank: true,
            valueField: 'idCondutor',
            displayField: 'nmCondutor',
            disabled: true,
            store: storeCondutorBancoHora
        });

        var comboPeriodo = Ext.create('Ext.form.ComboBox', {
            fieldLabel: 'Período',
            queryMode: 'local',
            id: 'idComboPeriodo',
            emptyText: 'Selecione a opção',
            allowBlank: false,
            valueField: 'idPeriodo',
            displayField: 'descPeriodo',
            disabled: false,
            store: storePeriodo
        });

        var dataIniBH = Ext.create('Ext.form.DateField', {
            id: 'dataIniBH',
            fieldLabel: 'Data de inicio',
            maxLength: 10,
            minLength: 10,
            maxValue: new Date(),
            minValue: Ext.Date.add(new Date(), Ext.Date.MONTH, -4),
            maskRe: /[0-9/]/,
            format: "d/m/Y",
            emptyText: '00/00/00',
            submitEmptyText: false,
            allowBlank: false
        });

        var timeTest = /^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/;
        Ext.apply(Ext.form.field.VTypes, {
            time: function(val, field) {
                return timeTest.test(val);
            },
            timeText: 'Por Favor, utilize um formato de hora válido. Ex: "08:00"',
            timeMask: /[0-999:0-59]/
        });
        
        var totalHorasUteis = Ext.create('Ext.form.TextField', {
            id: 'idTotalHorasDiasUteis',
            fieldLabel: 'Total horas/dia útil',
            labelWidth: 140,
            maxLength: 6,
            width: 396,
//            style: 'margin-left:3px;margin-top:8px;margin-bottom:3px;',
            name: 'nameTxtHoraInicial',
            emptyText: '00:00',
            value: '',
            readOnly: false,
            vtype: 'time',
            allowBlank: false
        });

        var totalHorasSab = Ext.create('Ext.form.TextField', {
            id: 'idHorasSabado',
            fieldLabel: 'Total horas sábado',
            labelWidth: 140,
            maxLength: 6,
            width: 396,
//            style: 'margin-left:3px;margin-top:5px;margin-bottom:15px;',
            name: 'idHorasSabado',
            emptyText: '00:00',
            value: '',
            readOnly: false,
            vtype: 'time',
            allowBlank: false
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
                        {xtype: comboPeriodo},
//                        {xtype: diaVencimento},
                        {xtype: dataIniBH},
                        {xtype: totalHorasUteis},
                        {xtype: totalHorasSab}

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

