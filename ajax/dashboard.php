<script>
    Ext.require('Ext.chart.*');
    Ext.require(['Ext.layout.container.Fit', 'Ext.window.MessageBox']);


    Ext.onReady(function () {

        Ext.define('Dados', {
            extend: 'Ext.data.Model',
            requires : 'Ext.data.Model',
            fields: ['name', 'data1', 'data2', 'data3', 'data4', 'data5'],
        });

        var store = Ext.create('Ext.data.Store', {
            model: 'Dados',
            autoLoad : true,
            proxy: {
                type : 'ajax',
                url  : 'json/jsonChartPie.php',
                reader: {
                    type: 'json',
                    root: 'dados'
                }
            }
        });

        var chart = Ext.create('Ext.chart.Chart', {
//            renderTo: Ext.getBody(),
//            width: 500,
//            height: 300,
            animate: true,
            store: store,
            insetPadding: 30, // TAMANHO DO GRAFICO DENTRO DO PANEL
            shadow: true,
            theme: 'Base:gradients',
            series: [{
                type: 'pie',
                field: 'data1',
                showInLegend: true,
                tips: { //TOOLTIP
                    trackMouse: true,
                    width: 140,
                    height: 28,
                    renderer: function(storeItem, item) {
                        //calculate and display percentage on hover
                        var total = 0;
                        store.each(function(rec) {
                            total += rec.get('data1');
                        });
                        this.setTitle(storeItem.get('name') + ': ' + Math.round(storeItem.get('data1') / total * 100) + '%');
                    }
                },
                highlight: { //AFASTAMENTO
                    segment: {
                        margin: 20
                    }
                },
                label: {
                    field: 'name',
                    display: 'rotate',
                    contrast: true,
                    font: '13px Arial'
                }
            }]
        });

        var panel2 = Ext.create('widget.panel', {
            id: 'idPanel2',
            width: Ext.getBody().getWidth()-800,
            height: 570,
            style: 'margin-left: 10px; margin-top: 10px;',
            title: 'Total de Condutores por Situação',
//            renderTo: 'panelDashboardId',
            border: 1,
            layout: 'fit',
            items: chart
        });

//        var panel3 = Ext.create('widget.panel', {
//            id: 'idPanel3',
//            width: 400,
//            height: 400,
//            style: 'margin-left: 10px; margin-top: 10px;',
//            title: 'Outro Gráfico',
//            border: 1,
//            layout: 'fit',
//        });
//
//        var panel4 = Ext.create('widget.panel', {
//            id: 'idPanel4',
//            width: 320,
//            height: 200,
//            style: 'margin-left: 10px; margin-top: 10px;',
//            title: 'Outro Gráfico',
//            border: 1,
//            layout: 'fit',
//        });
//
//        var panel5 = Ext.create('widget.panel', {
//            id: 'idPanel5',
//            width: 320,
//            height: 190,
//            style: 'margin-left: 10px; margin-top: 10px;',
//            title: 'Outro Gráfico',
//            border: 1,
//            layout: 'fit',
//        });

        var panel1 = Ext.create('widget.panel', {
            id: 'idPanel1',
            width: '100%',
            height: '100%',
//            title: 'Panel 1',
            renderTo: 'panelDashboardId',
            border: 0,
            layout: 'fit',
            items: [{
                layout: 'column',
                items: [
                    {xtype: panel2}//,
//                    {xtype: panel3},
//                    {xtype: panel4},
//                    {xtype: panel5}
                ]
            }]
        });

        Ext.TaskManager.start({
            interval: 60000,
            run: function() {
                store.load();
            }
        });
    });
</script>
<div id="panelDashboardId" style="width: 100%;"></div>
