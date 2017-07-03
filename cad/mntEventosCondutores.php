<?php
require_once('../includes/Controles.class.php');
require_once('../includes/OracleCieloJornada.class.php');

$OraCielo   = new OracleCielo();
$conexao    = $OraCielo->getCon();
$CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
$empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);

(!$CtrlAcesso->checkUsuario($_SESSION))?exit:null;

//if($_REQUEST['acao']=='insert'){
//    $acao = 'insert';
//    $sql = "SELECT id_condutor, nome, empresa
//                    FROM condutor
//                    WHERE id_condutor = ".$_REQUEST['idCondutor']."";
//    //echo $sqlUsu;
//    $res = $conexao->getResult($sql);
//    foreach($res as $row){
//        $idCondutor     = $row['id_condutor'];		
//        $nmCondutor     = utf8_encode($row['nome']);		
//        $idEmpresa      = $row['empresa'];
//    }
//}
//else{
//    $acao = 'delete';
//}
?>

<script>
Ext.define('Empresas', {
    extend: 'Ext.data.Model',
    //requires : 'Ext.data.Model',
    fields:[
        {name: 'idEmpresa', type: 'int'},
        {name: 'nmEmpresa', type: 'string'},
        {name: 'respEmpresa', type: 'string'},
        {name: 'telEmpresa', type: 'string'},
        {name: 'emailEmpresa', type: 'string'},
        {name: 'cepEmpresa', type: 'string'},
        {name: 'ufEmpresa', type: 'string'},
        {name: 'cidEmpresa', type: 'string'},
        {name: 'usuEmpresa', type: 'string'}]
});

var storeEmpresa = Ext.create('Ext.data.Store', {
    model: 'Empresas',
    //groupField: 'SeasonName',
    autoLoad: true,
    proxy: {
        type: 'ajax',
//        url: 'json/jsonEmpresas.php',
        reader: {
            type: 'json',
            root: 'empresas'
        },
        extraParams:{								
            idEmpresa: '<?=$empresaUsu?>'
        }
    }
});

Ext.define('modelEventosTipo', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'idEventoTipo', type: 'int'},
        {name: 'descEventoTipo', type: 'string'},
        {name: 'codigoEventoTipo', type: 'int'}
    ]
});

var storeEventosTipo = Ext.create('Ext.data.Store', {
    model: 'modelEventosTipo',
    autoLoad: true,
    proxy: {
        type : 'ajax',
//        url  : 'json/jsonEventosTipo.php',
        reader: {
            type: 'json',
            root: 'eventosTipo'
        } 	
    }
});

Ext.create('Ext.form.Panel', {
    bodyPadding: 5,
    width: '100%',
//    url: 'exec/execEventos.php',
    layout: 'anchor',
    border: false,
    defaults: {
        anchor: '100%'
    },
    items: [
        {
            xtype: 'hiddenfield',
            name: 'acao',
            id: 'acao',
            value: '<?=$acao?>'
        },{
            xtype: 'hiddenfield',
            name: 'idCondutorh',
            id: 'idCondutorh',
            value: '<?=$idCondutor?>'
        },{
            xtype: 'textfield',
            fieldLabel: 'Nome',
            name: 'nmCondutor',
            id: 'nmCondutor',
            allowBlank: false,
            value: '<?=$nmCondutor?>',
            disabled: true
        },{
            xtype: 'datefield',
            fieldLabel: 'Data/Hora Evento',
            id: 'dtDataHoraEvento',
            name: 'dtDataHoraEvento',
            format: 'd/m/Y H:i:s',
            //submitFormat: 'Y-m-d H:i:s',
            allowBlank: false,
            value: ''
        },{
            xtype: 'textfield',
            fieldLabel: 'Placa',
            name: 'placaCondutor',
            id: 'placaCondutor',
            enforceMaxLength : true,
            maxLength : 8,			
            allowBlank: false,
            value: ''
        },{
            xtype: 'combobox',
            fieldLabel: 'Tipo de Evento',
            queryMode: 'local',
            id: 'codigoEventoTipo',
            name: 'codigoEventoTipo',
            displayField: 'descEventoTipo',
            valueField: 'codigoEventoTipo',
            store: storeEventosTipo,
            allowBlank: false,
            emptyText: 'Seleciona a opção'
        },{
            xtype: 'combobox',
            fieldLabel: 'Empresa',
            queryMode: 'local',
            id: 'idEmpresa',
            name: 'idEmpresa',
            displayField: 'nmEmpresa',
            valueField: 'idEmpresa',
            store: storeEmpresa,
            emptyText: 'Seleciona a opção',
            submitEmptyText: false,
            disabled: <?=($empresaUsu)?'true':'false'?>
        }
    ],
    buttons: [
        {
            text: 'Limpar',
            handler: function() {
                var empresa = this.up('form').getComponent('idEmpresa').getValue();
                this.up('form').getForm().reset();
                this.up('form').getComponent('idEmpresa').setValue(empresa);
            }
        },{
            text: 'Enviar',
            formBind: true,
            disabled: true,
            handler: function() {
                var form = this.up('form').getForm();
                if (form.isValid()) {
                    form.submit({
                        submitEmptyText: false,
                        success: function(form, action) {
//                            console.log(action.result);
                            Ext.Msg.alert('Informação', action.result.msg);
                            Ext.getCmp('manutencao_eventos_condutor').close();
                            Ext.getCmp('relEventosId').store.reload();
                        },
                        failure: function(form, action) {
                            console.log(action.result);
                            Ext.Msg.alert('Falhou', action.result.msg);
                        }
                    });
                }
            }
        }
    ],
    renderTo: 'contentId'
});

<? if($idEmpresa or $empresaUsu){?>
Ext.getCmp("idEmpresa").setValue(<?=($idEmpresa)?$idEmpresa:$empresaUsu?>);
<?
}if($idSituacao){
?>
Ext.getCmp("idSituacao").setValue(<?=$idSituacao?>);
<? }?>
</script>

<div id="contentId"></div>