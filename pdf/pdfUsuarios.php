<?php
    require_once('../includes/PrintPdf.class.php');
    
    $printPdf = new PrintPdf();

    if(isset($_REQUEST['relContent'])) {
	/*$ass .= '<font size="9">
                    <table width="60%" cellspacing="2" cellpadding="2">
                        <tr bgcolor="#E1EEF4">
                            <td><font style="font-weight:bold;">Empresa:&nbsp;</font>'.$_REQUEST['formListUsrIdEmpresa'].'</td>
                        </tr>
                    </table>
                </font>';*/	
	$ass .= '<font size="9">
                        <table cellpadding="1" cellspacing="1" style="text-align:left;">
                            <thead>
                                <tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">
                                    <th style="width:100px;">Cod. Usuario</th>
                                    <th>Nome do Usuario</th>
                                    <th>Tipo</th>
                                    <th style="width:auto;">Empresa</th>
                                </tr>
                            </thead>
                            <tbody>'.utf8_decode($_REQUEST['relContent']).'</tbody>
                        </table>
                    </font>';		                                                    
    }

    $html .= utf8_decode($ass);

    $nomeRelatorio = utf8_decode("Jornada de Trabalho");
    $nomeArquivo = 'Relatorio de Usuarios';
    $retorno = "I";
    $orientacao = 'L';

    $printPdf->setHeader($nomeRelatorio, 'Relatorio de Usuarios');
    $printPdf->setTitle($nomeRelatorio);
    $printPdf->setDocument();
    $printPdf->setBodyRelatorio($html, $orientacao);

    echo $printPdf->getPdf($nomeArquivo.'.pdf', $retorno);
?>