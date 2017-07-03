<?
require_once('../includes/OracleCieloJornada.class.php');
require_once('../includes/Controles.class.php');

$OraCielo   = new OracleCielo();
$conexaoOra = $OraCielo->getCon();
$CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexaoOra);

$empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
?>
<script>

//Ext.define('Empresas', {
//    extend: 'Ext.data.Model',
//    fields:[
//        {name: 'idEmpresa', type: 'int'},
//        {name: 'nmEmpresa', type: 'string'},
//        {name: 'respEmpresa', type: 'string'},
//        {name: 'telEmpresa', type: 'string'},
//        {name: 'emailEmpresa', type: 'string'},
//        {name: 'cepEmpresa', type: 'string'},
//        {name: 'ufEmpresa', type: 'string'},
//        {name: 'cidEmpresa', type: 'string'},
//        {name: 'usuEmpresa', type: 'string'}
//    ]
//});

//var storeEmpresa = Ext.create('Ext.data.Store', {
//    model: 'Empresas',
//    autoLoad : true,
//    proxy: {
//        type: 'ajax',
//        url: 'json/jsonEmpresas.php',
//        reader: {
//            type: 'json',
//            root: 'empresas'
//        },
//        extraParams:{
//            idEmpresa:'<?=$empresaUsu?>'
//        }
//    }
//});

//Ext.define('Situacoes', {
//    extend: 'Ext.data.Model',
//    fields: [
//        {name: 'idSituacao', type: 'int'},
//        {name: 'descSituacao', type: 'string'}
//    ]
//});
	
//var storeSituacao = Ext.create('Ext.data.Store', {
//    model: 'Situacoes',
//    autoLoad: true,
//    proxy: {
//        type: 'ajax',
//        url: 'json/jsonSituacoes.php',
//        reader: {
//            type: 'json',
//            root: 'situacoes'
//        } 	
//    }
//});

Ext.create('Ext.form.Panel', {
    bodyPadding: 5,
    buttonAlign : 'center',
    width: '100%',
    url: 'exec/execEmpresas.php',
    layout: 'anchor',
    border: false,
    id: 'filtroRelEmpresas',
    defaults: {
        anchor: '100%'
    },
    items: [
        {
            xtype: 'textfield',
            fieldLabel: 'Nome',
            name: 'nmEmpresa',
            id: 'nmEmpresa'
        }
//        {
//            xtype: 'combobox',
//            fieldLabel: 'Situação',
//            queryMode: 'local',
//            id: 'idSituacao',
//            name: 'idSituacao',
//            displayField: 'descSituacao',
//            valueField: 'idSituacao',
//            store: storeSituacao,
//            emptyText: 'Seleciona a opção'
//        },
//        {
//            xtype: 'combobox',
//            fieldLabel: 'Empresa',
//            queryMode: 'local',
//            id: 'idEmpresa',
//            name: 'idEmpresa',
//            displayField: 'nmEmpresa',
//            valueField: 'idEmpresa',
//            store: storeEmpresa,
//            emptyText: 'Seleciona a opção',
//            submitEmptyText: false,
//            disabled: <?=''//($empresaUsu)?'true':'false'?>
//        }
    ],
    buttons: [
        {
            text: 'Limpar',
            iconCls: 'clear',
            handler: function() {
                this.up('form').getForm().reset();
            }
        }, {
            text: 'Filtrar',
            iconCls: 'filter',
            formBind: true, //only enabled once the form is valid
            disabled: true,
            handler: function() {
                Ext.getStore('storeEmpresasId').load({params:Ext.getCmp('filtroRelEmpresas').getValues()});
                Ext.getCmp('filtro_empresa').destroy();
            }
        }
    ],
    renderTo: 'contentId'
});

<? if($idEmpresa or $empresaUsu) { ?>
    Ext.getCmp("idEmpresa").setValue(<?=($idEmpresa) ? $idEmpresa : $empresaUsu;?>);
<? } ?>
//Ext.getCmp("idSituacao").setValue(<?=''//$idSituacao?>);
<? //} ?>
</script>

<div id="contentId"></div>