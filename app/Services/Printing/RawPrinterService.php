<?php

namespace App\Services\Printing;

class RawPrinterService
{
    /**
     * @return array{ok:bool,detected:bool,message:string}
     */
    public function sendTestLabel(string $ip, ?string $printerName = null, int $dpi = 203): array
    {
        $timeoutSeconds = 3;
        $socket = $this->openSocket($ip, $timeoutSeconds, $errorCode, $errorMessage);

        if ($socket === false) {
            return [
                'ok' => false,
                'detected' => false,
                'message' => "No se pudo conectar a la impresora {$ip}:9100. {$errorMessage}",
            ];
        }

        stream_set_timeout($socket, $timeoutSeconds);

        $payload = $this->buildTestZpl($printerName, $dpi);
        $writtenBytes = fwrite($socket, $payload);
        fclose($socket);

        if ($writtenBytes === false || $writtenBytes <= 0) {
            return [
                'ok' => false,
                'detected' => true,
                'message' => 'Se pudo abrir conexión, pero no se pudo enviar la etiqueta de prueba.',
            ];
        }

        $printerLabel = $printerName ? " ({$printerName})" : '';

        return [
            'ok' => true,
            'detected' => true,
            'message' => "Impresión de prueba enviada a {$ip}{$printerLabel}.",
        ];
    }

    private function buildTestZpl(?string $printerName, int $dpi): string
    {
        $title = $printerName ? "PRINTER: {$printerName}" : 'PRINTER: GENERICA';
        $timestamp = now()->format('Y-m-d H:i:s');

        return "^XA\n"
            . "^PW812\n"
            . "^LL406\n"
            . "^FO40,40^A0N,40,40^FDPRUEBA DE IMPRESION^FS\n"
            . "^FO40,100^A0N,30,30^FD{$title}^FS\n"
            . "^FO40,150^A0N,30,30^FDDPI: {$dpi}^FS\n"
            . "^FO40,200^A0N,30,30^FDENVIADO: {$timestamp}^FS\n"
            . "^FO40,260^GB730,3,3^FS\n"
            . "^XZ";
    }

    private function openSocket(string $ip, int $timeoutSeconds, ?int &$errorCode, ?string &$errorMessage)
    {
        set_error_handler(static function (): bool {
            return true;
        });

        try {
            return stream_socket_client("tcp://{$ip}:9100", $errorCode, $errorMessage, $timeoutSeconds);
        } finally {
            restore_error_handler();
        }
    }
}
