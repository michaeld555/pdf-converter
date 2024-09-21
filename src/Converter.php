<?php

namespace Michaeld555;

use setasign\Fpdi\Tcpdf\Fpdi;

class Converter {

    /**
     * Convert the input file to pdf and save in the output path
     *
     * @param string $file Path of the input file
     * @param string $output Path of the output file
     */
    public static function docx_to_pdf(?string $file, ?string $output)
    {

        $urlPdf = 'https://br2-word-view.officeapps.live.com/wv/WordViewer/document.pdf?WOPIsrc=http://br2-view-wopi.wopi.online.office.net:808/oh/wopi/files/@/wFileId?wFileId=' . $file . '&access_token=1&access_token_ttl=0&type=printpdf';

        $pdfContent = file_get_contents($urlPdf);

        $tempFilePath = tempnam(sys_get_temp_dir(), 'pdf');

        file_put_contents($tempFilePath, $pdfContent);

        $pdf = new FPDI();

        $paginaCount = $pdf->setSourceFile($tempFilePath);

        for ($paginaId = 1; $paginaId <= $paginaCount; $paginaId++) {

            $pdf->AddPage('', '', true);

            $templateId = $pdf->importPage($paginaId);

            $pdf->useTemplate($templateId, 0, 0, 210, 297, true);

        }

        $pdf->Output($output, 'F');

        unlink($tempFilePath);

    }


}