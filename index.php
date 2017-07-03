<?
/* Limpa a cache */
//header("Pragma: no-cache");
//header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
//header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
//header("Cache-Control: no-cache, cachehack=".time());
//header("Cache-Control: no-store, must-revalidate");
//header("Cache-Control: post-check=-1, pre-check=-1", false);
/* Fim Limpa a cache */

//session_start();
//session_unset();
//$_SESSION['sessionUserLogin'] = "";
//$_SESSION['sessionUserId']    = "";
echo "Aguarde... ";             
?>
<html>
    <head>
        <title>Jornada de Trabalho | Cielo</title>
        <link rel="icon" href="imagens/truck_icon.png">
        <link rel="stylesheet" type="text/css" href="ext421/resources/css/ext-all-gray.css">
        <link rel="stylesheet" type="text/css" href="css/menus.css">
        <script type="text/javascript" src="ext421/ext-all-debug.js"></script>
        <script type="text/javascript">
            Ext.require([
                'Ext.form.Panel',
                'Ext.layout.container.Anchor'  
            ]);
            Ext.onReady(function() {
                var wind = Ext.create('Ext.window.Window', {
                    title: 'Jornada de Trabalho',
                    id: 'mainWindownId',
                    width: 420,
                    height: 160,
                    resizable: false,
                    closable: false,
                    layout: 'fit',
                    plain: true,
                    autoScroll: true,
                    stateful : false,
                    modal: true,
                    items: {
                        xtype: 'form',
                        id: 'formLogin',
                        bodyPadding: 20,
                        width: '100%',
                        url: 'exec/login.php',
                        layout: 'anchor',
                        defaults: {
                            anchor: '90%'
                        },
                        items: [{
                            xtype: 'textfield',
                            fieldLabel: 'Login',
                            name: 'loginUsuario',
                            id: 'loginUsuario',
                            allowBlank: false
                        },{
                            xtype: 'textfield', //4
                            name: 'pswUsuario',
                            id: 'pswUsuario',
                            inputType: 'password',
                            fieldLabel: 'Senha',
                            allowBlank: false
                        }],
                        listeners: {
                            boxready: function (win) {
                                var map = new Ext.KeyMap(win.getEl(), {
                                    key: Ext.EventObject.ENTER,
                                    fn: function () {
                                        var form = this.getForm();
                                        if (form.isValid()) {
                                            form.submit({
                                                success: function(form, action) {
                                                    form.reset();
                                                    window.location = 'jornada.php';
                                                },
                                                failure: function(form, action) {
                                                    Ext.Msg.alert('Falhou', action.result.msg);
                                                }
                                            });
                                        }
                                    },
                                    scope: win
                                });
                            }
                        },			
                        // Reset and Submit buttons
                        buttons: [{
                            text: 'Limpar',
                            handler: function() {
                                this.up('form').getForm().reset();
                            }
                        }, {
                            text: 'Acessar',
                            formBind: true, //only enabled once the form is valid
                            disabled: true,
                            handler: function() {
                                //console.log('TESTE');
                                var form = this.up('form').getForm();
                                if (form.isValid()) {
                                    form.submit({
                                        success: function(form, action) {
                                            //console.log(action.result);
                                            form.reset();
                                            window.location = 'jornada.php';
                                        },
                                        failure: function(form, action) {
                                            //console.log(action.result);
                                            Ext.Msg.alert('Falhou', action.result.msg);
                                        }
                                    });
                                }
                            }
                        }]
                    }
                }).show();

                Ext.getCmp('loginUsuario').focus(true);
	
                Ext.create('Ext.container.Viewport', {
                    id: 'mainViewport',
                    layout: 'border',
                    autoWidth: true,
                    items: [{
                        region: 'center',
                        layout: 'fit',
                        id: 'centerPanel',
                        xtype: 'panel',
                        autoScroll: true,
                        border: true,
                        items: [wind]
                    }]
                });
            });
        </script>
    </head>
    <body></body>
</html>
