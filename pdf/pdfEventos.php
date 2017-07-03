<?php
    require_once('../includes/PrintPdf.class.php');
    
    $printPdf   = new PrintPdf();
    $ass        = "";
    $html       = "";

    if(isset($_REQUEST['relContent'])) {
        $ass .= '<font size="9">
                    <table width="50%" cellspacing="2" cellpadding="5">
                        <tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">
                            <td><font style="font-weight:bold;">Empresa:&nbsp;</font>'.$_REQUEST['nomeEmpresa'].'</td>
                        </tr>
                        <tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">
                            <td><font style="font-weight:bold;">Período:&nbsp;</font>'.$_REQUEST['dataIniRel'].' até '.$_REQUEST['dataFimRel'].'</td>
                        </tr>
                        <tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">
                            <td><font style="font-weight:bold;">Total de Eventos:&nbsp;</font>'.$_REQUEST['totalEventos'].'</td>
                        </tr>
                    </table>
                </font> <br><br>';
        $ass .= '<font size="9">
                    <table cellpadding="5" cellspacing="2" style="text-align:left;">
                        <thead>
                            <tr bgcolor="#006699" style="color: rgb(255, 255, 255); font-weight: bold;">
                                <th>Descrição</th>
                                <th>Data Hora</th>
                                <th>Placa</th>
                                <th>Condutor</th>
                            </tr>
                        </thead>
                        <tbody>'.utf8_decode($_REQUEST['relContent']).'</tbody>
                    </table>
                </font>';
    }

    $html .= utf8_decode($ass);

    $nomeRelatorio = utf8_decode("Jornada de Trabalho");
    $nomeArquivo = 'relatorio_eventos_'.$_REQUEST['nomeCondutor'].'_'.$_REQUEST['dataIniRel'].'_ate_'.$_REQUEST['dataFimRel'];
    $retorno = "I";
    $orientacao = 'L';

    $printPdf->setHeader($nomeRelatorio, 'Relatório de Eventos');
    $printPdf->setTitle($nomeRelatorio);
    $printPdf->setDocument();
    $printPdf->setBodyRelatorio($html, $orientacao);

    echo $printPdf->getPdf($nomeArquivo.'.pdf', $retorno);
?>