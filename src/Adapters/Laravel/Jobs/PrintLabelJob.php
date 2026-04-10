<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt\Adapters\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use TsengYuChen\BMPtxt\BmpConverter;
use TsengYuChen\BMPtxt\Enums\FontFamily;

/**
 * Laravel Job for asynchronous printing to a thermal label printer via TCP Socket.
 *
 * This prevents the HTTP request from blocking while waiting for the printer and network.
 *
 * Usage:
 *   use TsengYuChen\BMPtxt\Adapters\Laravel\Jobs\PrintLabelJob;
 *   use TsengYuChen\BMPtxt\Enums\FontFamily;
 *
 *   PrintLabelJob::dispatch(
 *       content: '出貨品號：A12345',
 *       printerIp: '192.168.8.243',
 *       printerPort: 9100,
 *       font: FontFamily::KAIU,
 *       size: 14
 *   )->onQueue('printing');
 */
class PrintLabelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param string      $content     The text to print
     * @param string      $printerIp   The IP address of the target printer
     * @param int         $printerPort The TCP port of the target printer (default: 9100)
     * @param FontFamily  $font        The font family enum instance
     * @param float       $size        The font size in points
     * @param int         $x           X position on the label in dots
     * @param int         $y           Y position on the label in dots
     * @param int         $dpi         The printer DPI (default 232)
     */
    public function __construct(
        public readonly string $content,
        public readonly string $printerIp,
        public readonly int $printerPort = 9100,
        public readonly FontFamily $font = FontFamily::KAIU,
        public readonly float $size = 12.0,
        public readonly int $x = 0,
        public readonly int $y = 0,
        public readonly int $dpi = 232,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(BmpConverter $bmptxt): void
    {
        $binary = $bmptxt->text($this->content)
            ->font($this->font)
            ->size($this->size)
            ->dpi($this->dpi)
            ->toEzpl(x: $this->x, y: $this->y)
            ->toBinary();

        $this->sendToPrinter($binary);
    }

    /**
     * Send the binary payload to the printer via TCP socket.
     *
     * @throws \RuntimeException if socket connection or send fails.
     */
    protected function sendToPrinter(string $binaryPayload): void
    {
        $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            $error = socket_strerror(socket_last_error());
            throw new \RuntimeException("PrintLabelJob failed to create socket: {$error}");
        }

        // Set 5 second timeout for connecting and sending
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 5, 'usec' => 0]);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);

        $connected = @socket_connect($socket, $this->printerIp, $this->printerPort);
        if (!$connected) {
            $error = socket_strerror(socket_last_error($socket));
            @socket_close($socket);
            throw new \RuntimeException("PrintLabelJob failed to connect to printer {$this->printerIp}:{$this->printerPort} - {$error}");
        }

        $ln  = "\r\n";
        // EZPL basic structure: Select standard memory, initialize, set label width, send image
        $cmd = "^C1{$ln}^P1{$ln}^L{$ln}^W90{$ln}Q{$this->x},{$this->y}{$ln}" . $binaryPayload . "E{$ln}";

        $sent = @socket_send($socket, $cmd, strlen($cmd), 0);
        
        @socket_close($socket);

        if ($sent === false) {
            throw new \RuntimeException("PrintLabelJob failed to send data to printer {$this->printerIp}:{$this->printerPort}.");
        }
    }
}
