<?php

test('it can parse cfdi xml', function () {
    $path = __DIR__ . '/../../context/xml_example/007800-4d1d3424-cdd2-44b8-a1e7-2ec045d3506f.xml';
    
    if (!file_exists($path)) {
        $this->markTestSkipped('Example XML file not found at: ' . $path);
    }

    $content = file_get_contents($path);
    $parser = new \App\Services\Xml\CfdiParserService();
    $data = $parser->parse($content);

    expect($data['uuid'])->toBe('4d1d3424-cdd2-44b8-a1e7-2ec045d3506f');
    expect($data['total'])->toBe(47972.00);
    expect($data['fecha_emision'])->toBe('2026-01-22');
});
