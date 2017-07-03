<?php
    require_once('../includes/PrintPdf.class.php');
    
    $printPdf = new PrintPdf();

    if(isset($_REQUEST['relContent'])){
        $ass .= '<font size="9">
                        <table width="50%" cellspacing="2" cellpadding="5">
                            <tr bgcolor="#E1EEF4">
                                <td><font style="font-weight:bold;">Empresa:&nbsp;</font>'.$_REQUEST['formListCondIdEmpresa'].'</td>
                            </tr>
                        </table>
                    </font>';
        $ass .= '<br><font size="9">
                            <table cellpadding="2" cellspacing="2">
                                <thead>
                                    <tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">
                                        <th>Codigo do Condutor</th>
                                        <th>Nome do Condutor</th>
                                        <th>Placa</th>
                                    </tr>
                                </thead>
                                <tbody>'.utf8_decode($_REQUEST['relContent']).'</tbody>
                            </table>
                        </font>';
        $ass .= '<font size="9">
                    <table width="50%" cellspacing="2" cellpadding="5">
                        <tr bgcolor="#E1EEF4">
                            <td><font style="font-weight:bold;">Total:&nbsp;</font>'.$_REQUEST['totalCondutores'].'</td>
                        </tr>
                    </table>
                </font>';
    }

    $html .= utf8_decode($ass);

    $nomeRelatorio = utf8_decode("Jornada de Trabalho");
    $nomeArquivo = 'Relatório de Condutores';
    $retorno = "I";
    $orientacao = 'L';

    $printPdf->setHeader($nomeRelatorio, 'Relatorio de Condutores');
    $printPdf->setTitle($nomeRelatorio);
    $printPdf->setDocument();
    $printPdf->setBodyRelatorio($html, $orientacao);

    echo $printPdf->getPdf($nomeArquivo.'.pdf', $retorno);
?>