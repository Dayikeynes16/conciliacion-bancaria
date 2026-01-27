<?php

namespace App\Services\Xml;

use Carbon\Carbon;
use SimpleXMLElement;

class CfdiParserService
{
    public function parse(string $content): array
    {
        $xml = new SimpleXMLElement($content);
        $ns = $xml->getNamespaces(true);

        // Ensure namespaces are registered
        if (! isset($ns['cfdi'])) {
            $xml->registerXPathNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
        } else {
            $xml->registerXPathNamespace('cfdi', $ns['cfdi']);
        }

        if (! isset($ns['tfd'])) {
            $xml->registerXPathNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');
        } else {
            $xml->registerXPathNamespace('tfd', $ns['tfd']);
        }

        // Use XPath for safer extraction of attributes in namespaces
        $timbre = $xml->xpath('//tfd:TimbreFiscalDigital');
        $uuid = isset($timbre[0]) ? (string) $timbre[0]['UUID'] : '';

        // Extract Root attributes
        $total = (float) $xml['Total'];
        $fechaRaw = (string) $xml['Fecha'];
        $fecha = Carbon::parse($fechaRaw)->format('Y-m-d');
        $folio = (string) $xml['Folio'];

        // Extract Emisor
        $emisor = $xml->xpath('//cfdi:Emisor');
        $rfcEmisor = isset($emisor[0]) ? (string) $emisor[0]['Rfc'] : '';
        $nombreEmisor = isset($emisor[0]) ? (string) $emisor[0]['Nombre'] : '';

        // Extract Receptor
        $receptor = $xml->xpath('//cfdi:Receptor');
        $rfcReceptor = isset($receptor[0]) ? (string) $receptor[0]['Rfc'] : '';
        $nombreReceptor = isset($receptor[0]) ? (string) $receptor[0]['Nombre'] : '';

        return [
            'uuid' => $uuid,
            'folio' => $folio,
            'fecha_emision' => $fecha,
            'total' => $total,
            'rfc_emisor' => $rfcEmisor,
            'nombre_emisor' => $nombreEmisor,
            'rfc_receptor' => $rfcReceptor,
            'nombre_receptor' => $nombreReceptor,
        ];
    }
}
