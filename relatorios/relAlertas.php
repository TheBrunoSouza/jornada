<?
    //require('../includes/BancoPost.class.php');
    //$conexao = new BancoPost();

    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');

    require_once('../includes/Controles.class.php');
    $CtrlAcesso=new Controles($_SERVER['REMOTE_ADDR'], $conexao);

    $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
?>
<script>
    function reloadAlerta(){
        var mask = new Ext.LoadMask('gestorRelId', {msg:"Carregando..."});
        mask.show();

        Ext.getCmp("relAlertasId").reconfigure(storeAlertas);
        setTimeout(function() {
            storeAlertas.load({
                params: {
                    'dtIni': Ext.Date.format(Ext.getCmp('idDateIni').value, 'Y-m-d'),
                    'dtFim': Ext.Date.format(Ext.getCmp('idDateFim').value, 'Y-m-d'),
                    'idCondutor': Ext.getCmp('idCondutor').value,
                    'origem': 'relatorio'
                },
                callback:function(records, operation, success) {
                    mask.hide();
                }
            });
        }, 2000);
    }

    Ext.define('Condutores', {
        extend: 'Ext.data.Model',
        requires : 'Ext.data.Model',
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

    var storeCondutor = Ext.create('Ext.data.Store', {
        model: 'Condutores',
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
                excluiInativos: true
            }
        }
    });

    var storeAlertas = Ext.create('Ext.data.Store', {
        model: 'Alertas',
        autoLoad: false,
        proxy: {
            type: 'ajax',
            url: 'json/jsonAlertas.php',
            reader: {
                type: 'json',
                root: 'alertas'
            },
            extraParams: {
                idEmpresa: '<?=$empresaUsu?>',
                origem: 'grid'
            }
        },
        fields: [
            {name: 'idAlerta', type: 'int'},
            {name: 'descAlerta', type: 'string'},
            {name: 'tempo', type: 'int'},
            {name: 'dtHrAlerta', type: 'string'},
            {name: 'nmCondutor', type: 'string'},
            {name: 'plcAlerta', type: 'string'},
            {name: 'justAlerta', type: 'string'},
            {name: 'dtHrAlertaAtend', type: 'string'}
        ]	
    });

    var dateFim = new Ext.form.DateField({
        id: 'idDateFim',
        fieldLabel: 'Fim',
        labelWidth: 23,
        format: "d/m/Y",
        emptyText: 'dd/mm/aaaa',
        maxValue: new Date(),    
        width: 125,
        value: new Date()
    }); 

    var dateIni = new Ext.form.DateField({
        id: 'idDateIni',
        fieldLabel: 'Início:',
        labelWidth: 33,
        format: "d/m/Y",
        emptyText: 'dd/mm/aaaa',
        maxValue: new Date(),    
        width: 135,
        value: Ext.Date.add(new Date(), Ext.Date.MONTH, -1),
        listeners: {
            'afterrender': function(me) {
                setMinDateFieldFim(dateFim, me.getSubmitValue());
            },
            'change': function(me) {
                setMinDateFieldFim(dateFim, me.getSubmitValue());
            }
        }    
    });

    var toolbar = Ext.create('Ext.toolbar.Toolbar', {
        id: 'toolbarRelId',
        region: 'north',
        items: [
            ' ',
            { xtype: dateIni},
            '  ',
            { xtype: dateFim},
            ' ','  ',{
                xtype: 'combobox',
                fieldLabel: 'Condutor',
                labelWidth: 50,
                width: 250,
                queryMode: 'local',
                id: 'idCondutor',
                name: 'idCondutor',
                displayField: 'nmCondutor',
                valueField: 'idCondutor',
                store: storeCondutor,
                emptyText: 'Selecione para filtrar...',
                listeners:{
                    select: function(f, r, i){
                       reloadAlerta();
                    }
                }
            },'  ','-',{ 
                xtype: 'button', 
                text: 'Filtrar',
                iconCls: 'filter',
                style: 'margin-top: 8px;',
                handler: function() {
                    reloadAlerta();
                }
            }
        ]
    });
			  
    Ext.onReady(function () {
        Ext.create('Ext.grid.Panel', {
            tbar: toolbar,
            id: 'relAlertasId',
            forceFit: true,
            height: Ext.getBody().getHeight()-59,
            autoWidth: true,
            viewConfig: {
                emptyText: '<b>Nenhum registro de alerta encontrado para este filtro</b>',
                deferEmptyText: false
            },
            columnLine: true,
            layout: 'fit',
            autoWidth: true,
            renderTo: 'gestorRelId',
            columns: [
                { text: 'Data Hora',  dataIndex: 'dtHrAlerta'},
                { text: 'Condutor', flex: 1, dataIndex: 'nmCondutor'},
                { text: 'Placa', dataIndex: 'plcAlerta'},
                { text: 'Descrição', flex:1,  dataIndex: 'descAlerta', menuDisabled: true },
                {
                    text: 'Tempo', dataIndex: 'tempo', menuDisabled: true, tdCls: 'wrap', flex: 1,
                    renderer: function (value, metaData, record, row, col, store, gridView) {
                        var teste = rectime(record.get('tempo'));
                        return teste+':00';
                    }
                },
            ]
        });
    });

    function setMinDateFieldFim(dateMin, dati) {
        dateMin.setMinValue(dati);
    }
</script>
<div id="gestorRelId" style="width: 100%; height: 100%;"></div>
