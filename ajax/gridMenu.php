<script>
function renderIcon(val) {
    
    var tmp;

    if(val == 'Painel'){
        tmp = '<div> <img src="imagens/16x16/grafico.png"> ' + val + '</div>';
    }else if(val == 'Cartão Ponto'){
        tmp = '<div> <img src="imagens/16x16/id_card.png"> ' + val + '</div>';
    }else if(val == 'Banco de Horas') {
        tmp = '<div> <img src="imagens/16x16/database.png"> ' + val + '</div>';
    }else if(val == 'Relatório Totalizador'){
        tmp = '<div> <img src="imagens/16x16/table_multiple.png"> ' + val + '</div>';
    }else if(val == 'Relatório de Alertas'){
        tmp = '<div> <img src="imagens/16x16/monitor_error.png"> ' + val + '</div>';
    }else if(val == 'Relatório de Eventos'){
        tmp = '<div> <img src="imagens/16x16/table_lightning.png"> ' + val + '</div>';
    }else if(val == 'Sair'){
        tmp = '<div> <img src="imagens/16x16/door_out.png"> ' + val + '</div>';
    }

    return tmp;
}

Ext.onReady(function () {
    var menuSistemaStore = new Ext.data.ArrayStore({
        fields: ['title'],
        data: [
            ['Painel'],
            ['Cartão Ponto'],
            ['Banco de Horas'],
            ['Relatório Totalizador'],
            ['Relatório de Alertas'],
            ['Relatório de Eventos'],
            ['Sair'],
            [''],
            ['']
        ]
    });

    Ext.create('Ext.grid.Panel', {
        store: menuSistemaStore,
        border: false,
        columnLines: false,
        hideHeaders: true,
        columns: [{ 
            id: 'relCartao', 
            flex: 1,
            dataIndex: 'title', 
            menuDisabled: true,
            renderer: renderIcon
        }],
        viewConfig: {
            getRowClass: function(record, rowIndex, rowParams, store) {
                return 'gridPointerRow';
            },
            stripeRows: false
        },
        listeners: {
            itemclick: function(grid, rowIndex, colIndex) {
                var tabPanJor = Ext.getCmp('tabPanelJornada');
                switch (rowIndex.index) {
                    case 0:
                        tabPanJor.setActiveTab('gestorTabId');
                        panelLoad('gestorTabId', 'Painel', 'ajax/dashboard.php', '');
                        break;
                    case 1:
                        tabPanJor.setActiveTab('gestorTabId');
                        panelLoad('gestorTabId', 'Cartão Ponto', 'relatorios/relCartaoPonto.php', '');
                        break;
                    case 2:
                        tabPanJor.setActiveTab('gestorTabId');
                        panelLoad('gestorTabId', 'Banco de Horas', 'relatorios/relBancoCondutor.php', '');
                        break;
                    case 3:
                        tabPanJor.setActiveTab('gestorTabId');
                        panelLoad('gestorTabId', 'Relatório Totalizador', 'relatorios/relTotalizadorHoras.php', '');
                        break;
                    case 4:
                        tabPanJor.setActiveTab('gestorTabId');
                        panelLoad('gestorTabId', 'Relatório de Alertas', 'relatorios/relAlertas.php', '');
                        break;
                    case 5:
                        tabPanJor.setActiveTab('gestorTabId');
                        panelLoad('gestorTabId', 'Relatório de Eventos', 'relatorios/relEventos.php', '');
                        break;
                    case 6:
                        exit();
                 }
            },
            beforeselect : function() {
                return false;
            }
        },
        renderTo: 'menuDivId'
    });
});

</script>

<div id="menuDivId" style="width:100%"></div>