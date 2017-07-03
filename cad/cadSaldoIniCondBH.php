<?php
require_once('../includes/OracleCieloJornada.class.php');
require_once('../includes/Controles.class.php');

$OraCielo   = new OracleCielo();
$conexaoOra = $OraCielo->getCon();
$CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexaoOra);

if(isset($_SESSION)) {
    $idUserEmpresa = $CtrlAcesso->getUserEmpresa($_SESSION);
} else {
    header('Location: http://jornada.cielo.ind.br');
}

$idPeriodoBH        = $_REQUEST['idPeriodoBH'];
$vencimentoBH       = $_REQUEST['vencimentoBH'];
$dataIniBH          = $_REQUEST['dataIniBH'];
$horasDiasUteisBH   = $_REQUEST['horasDiasUteisBH'];
$horasSabadosBH     = $_REQUEST['horasSabadosBH'];
$idCondutorBH       = $_REQUEST['idCondutorBH'];
$idEmpresaBH        = $_REQUEST['idEmpresaBH'];

$idEmpresaBH        = ($idEmpresaBH == '')?$idUserEmpresa:$idEmpresaBH


?>
<script>
    var timeTest = /^[0-5][0-9][0-9]||[0-5][0-9]:[0-5][0-9]$/;
    var arrayCondutorHora = [];

    Ext.define('CondutoresSaldoIni', {
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

    var storeCondutorSaldoIni = Ext.create('Ext.data.Store', {
        model: 'CondutoresSaldoIni',
        autoLoad : true,
        proxy: {
            type: 'ajax',
            url: 'json/jsonCondutores.php',
            reader: {
                type: 'json',
                root: 'condutores'
            },
            extraParams: {
                idEmpresa: '<?=$idEmpresaBH?>',
                ativo: 'T',
                idCondutor: '<?=$idCondutorBH?>'
            }
        }
    });

    Ext.apply(Ext.form.field.VTypes, {
        time: function(val, field) {
            return timeTest.test(val);
        },
        timeText: 'Por Favor, utilize um formato de hora válido. Ex: "08:00" ou  "100:00"',
        timeMask: /[0-999:0-59]/
    });

    Ext.onReady(function () {

        Ext.getCmp('buttonCancelar').handler = function(){
            Ext.getCmp('cadSaldoIniCondutor').close();
        };

        Ext.getCmp('buttonProx').handler = function(){

            Ext.getCmp('cadSaldoIniCondutor').close();

            var mask = new Ext.LoadMask('gestorTabId', {msg: "Gerando dados do período..."});
            mask.show();
            Ext.Ajax.request({
                url: 'exec/execBancoHoras.php',
                params: {
                    idPeriodoBH: '<?=$idPeriodoBH?>',
                    vencimentoBH: '<?=$vencimentoBH?>',
                    dataIniBH: '<?=$dataIniBH?>',
                    horasDiasUteisBH: '<?=$horasDiasUteisBH?>',
                    horasSabadosBH: '<?=$horasSabadosBH?>',
                    idCondutorBH: '<?=$idCondutorBH?>',
                    arrayCondutorHora: {array: Ext.encode(arrayCondutorHora)},
                    idEmpresaBH: '<?=$idEmpresaBH?>',
                    acao: 'create'

                },
                success: function(conn, response, options, eOpts) {
                    mask.hide();
                    var result = Ext.decode(conn.responseText);
                    if (result.status == 'ERRO') {
                        Ext.Msg.show({
                            title:'Erro!',
                            msg: 'Favor informar o departamento de TI.',
                            icon: Ext.Msg.ERROR,
                            buttons: Ext.Msg.OK
                        });
                    }else{
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
                failure: function(conn, response, options, eOpts) {
                    mask.hide();
                    Ext.Msg.show({
                        title:'Erro!',
                        msg: 'Entre em contato com o administrador do sistema.',
                        icon: Ext.Msg.ERROR,
                        buttons: Ext.Msg.OK
                    });
                }
            });
        };

        var comboCondutorSaldoIni = Ext.create('Ext.form.ComboBox', {
            fieldLabel: 'Condutor',
            queryMode: 'local',
            id: 'idComboCondSaldoIni',
            name: 'nameComboCondSaldoIni',
            emptyText: 'Selecione a opção',
            allowBlank: true,
            valueField: 'idCondutor',
            displayField: 'nmCondutor',
            disabled: false,
            store: storeCondutorSaldoIni
        });

        var totalHoras = Ext.create('Ext.form.TextField', {
            id: 'idTotalHoras',
            fieldLabel: 'Saldo',
            labelWidth: 140,
            maxLength: 6,
            width: 396,
            style: 'margin-left:3px;margin-top:8px;margin-bottom:3px;',
            name: 'nameTotalHoras',
            emptyText: '00:00',
            value: '',
            readOnly: false,
            vtype: 'time',
            allowBlank: false
        });

        var buttonLancar = new Ext.create('Ext.Button',{
            text: 'Lançar',
            formBind: true,
            disabled: false,
            style: 'margin-bottom: 8px; margin-top: 8px;',
            icon: 'imagens/16x16/database_add.png',
            handler: function() {
                if(Ext.getCmp('idComboCondSaldoIni').value == '' || Ext.getCmp('idComboCondSaldoIni').value == null || Ext.getCmp('idTotalHoras').value == ''){
                    Ext.Msg.show({
                        title:'Informação:',
                        msg: 'Você deve selecionar condutor e preencher o saldo inicial',
                        icon: Ext.Msg.INFO,
                        buttons: Ext.Msg.OK
                    });
                }else{
                    var array = [];
                    var descOperacao = '';
                    var tipoOperacao = '';

                    var condutorSaldoIni        = Ext.getCmp('idComboCondSaldoIni').value,
                        horasSaldoIni           = Ext.getCmp('idTotalHoras').value,
                        operacaoSaldoIni        = Ext.getCmp('radioCreditar').value;

                    if(operacaoSaldoIni == 1){
                        descOperacao = 'Creditado';
                        tipoOperacao = 'C';
                    }else{
                        descOperacao = 'Debitado';
                        tipoOperacao = 'D';
                    }

                    array.push(condutorSaldoIni, horasSaldoIni, tipoOperacao);

                    arrayCondutorHora.push(array);

                    Ext.getCmp('condutorLancado').setText('<br>'+descOperacao + ' ' + horasSaldoIni + ' para ' + Ext.getCmp('idComboCondSaldoIni').rawValue);

                    this.up('form').getForm().reset();
                }
            }
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
                    id: 'idFormCondutorT',
                    border: false,
                    bodyPadding: 5,
                    items: [
                        {xtype: comboCondutorSaldoIni},
                        {xtype: totalHoras},
                        {
                            xtype: 'radiogroup',
                            id: 'idRadioOperacao',
                            fieldLabel: 'Operação',
                            items: [
                            {
                                boxLabel: 'Creditar',
                                name: 'acao',
                                inputValue: 1,
                                id: 'radioCreditar',
                                checked: true
                            },
                            {
                                boxLabel: 'Debitar',
                                name: 'acao',
                                inputValue: 2,
                                id: 'radioDebitar'
                            }]
                        },
                        {xtype: buttonLancar},
                        {xtype: 'tbtext', id: 'condutorLancado', height: 30, text:' '},
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

