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
            Ext.getCmp('cad_feriado').close();
        };

        Ext.getCmp('buttonFiltrar').handler = function(){


            var    dataFeriado        = Ext.Date.format(Ext.getCmp('idDataFeriado').value, 'd/m');

            if (
                dataFeriado== '' ||
                dataFeriado           == null) {

                Ext.Msg.show({
                    title: 'Atenção:',
                    msg: 'Selecione a data',
                    icon: Ext.Msg.WARNING,
                    buttons: Ext.Msg.OK
                });
            } else {
                Ext.Ajax.request({
                    url: 'json/jsonFeriados.php',
                    timeout: 60000,
                    params: {
                        dataFeriado: dataFeriado,
                        descFeriado: Ext.getCmp('descFeriado').value,
                        idEmpresa: '<?=$empresaUsu?>',
                        acao: 'cadastrar'
                    },
                    success: function (conn, response, options, eOpts) {

                        var result = Ext.decode(conn.responseText);

                        if(result.status == 'OK'){
                            Ext.Msg.show({
                                title:'Sucesso!',
                                msg: result.msg,
                                icon: Ext.Msg.INFO,
                                buttons: Ext.Msg.OK
                            });
                            Ext.getCmp('gridFeriados').store.reload();
                            Ext.getCmp('cad_feriado').close();
                        }else{
                            Ext.Msg.show({
                                title: 'Erro!',
                                msg: 'Favor informar o departamento de TI.',
                                icon: Ext.Msg.ERROR,
                                buttons: Ext.Msg.OK
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
        };

        var data = Ext.create('Ext.form.DateField', {
            id: 'idDataFeriado',
            fieldLabel: 'Data',
            maxLength: 10,
            minLength: 5,
            maskRe: /[0-9/]/,
            format: "d/m",
            emptyText: '00/00',
            submitEmptyText: false,
            allowBlank: false
        });
//
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
                        {xtype: data},
                        {
                            fieldLabel: 'Descrição',
                            xtype: 'textarea',
                            maxLength: 60,
                            minLength: 2,
                            emptyText: 'É feriado de que mesmo?',
                            name: 'descFeriado',
                            id: 'descFeriado',
                            value: ''
                        }
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

