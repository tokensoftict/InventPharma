<?php

namespace App\Services\Printing\EscPos;

/**
 * Low-level ESC/POS command builder.
 * Generates raw binary byte strings for thermal printers.
 *
 * Reference: Epson ESC/POS Application Programming Guide
 */
class EscPosBuilder
{
    /** @var string Binary output buffer */
    private string $buffer = '';

    /** @var int Characters per line based on paper width */
    private int $charsPerLine;

    /** @var int Paper width in mm */
    private int $paperWidth;

    /** @var string Character encoding */
    private string $encoding;

    // ESC/POS Constants
    const ESC = "\x1B";
    const GS = "\x1D";
    const FS = "\x1C";
    const LF = "\x0A";
    const NUL = "\x00";

    // Alignment
    const ALIGN_LEFT = 0;
    const ALIGN_CENTER = 1;
    const ALIGN_RIGHT = 2;

    // Barcode types for GS k
    const BARCODE_CODE39 = 4;
    const BARCODE_CODE128 = 73;

    public function __construct(int $paperWidth = 80, string $encoding = 'UTF-8')
    {
        $this->paperWidth = $paperWidth;
        $this->encoding = $encoding;

        // Standard character widths for Font A (12x24 dots)
        // 80mm: 48 chars, 58mm: 32 chars
        $this->charsPerLine = $paperWidth >= 80 ? 48 : 32;
    }

    /**
     * Get characters per line for the configured paper width.
     */
    public function getCharsPerLine(): int
    {
        return $this->charsPerLine;
    }

    /**
     * Get paper width in mm.
     */
    public function getPaperWidth(): int
    {
        return $this->paperWidth;
    }

    /**
     * Get max print width in dots.
     * 80mm paper = 576 dots, 58mm = 384 dots (at 203 DPI)
     */
    public function getMaxDots(): int
    {
        return $this->paperWidth >= 80 ? 576 : 384;
    }

    /**
     * Initialize printer - ESC @
     */
    public function initialize(): self
    {
        $this->buffer .= self::ESC . "@";
        return $this;
    }

    /**
     * Set text alignment - ESC a n
     */
    public function setAlignment(int $align): self
    {
        $this->buffer .= self::ESC . "a" . chr($align);
        return $this;
    }

    /**
     * Set bold mode - ESC E n
     */
    public function setBold(bool $on): self
    {
        $this->buffer .= self::ESC . "E" . chr($on ? 1 : 0);
        return $this;
    }

    /**
     * Set underline mode - ESC - n
     * 0=off, 1=1-dot, 2=2-dot
     */
    public function setUnderline(int $mode = 0): self
    {
        $this->buffer .= self::ESC . "-" . chr($mode);
        return $this;
    }

    /**
     * Set character size - GS ! n
     * Width and height multipliers (1-8)
     */
    public function setCharacterSize(int $widthMultiplier = 1, int $heightMultiplier = 1): self
    {
        $w = max(0, min(7, $widthMultiplier - 1));
        $h = max(0, min(7, $heightMultiplier - 1));
        $n = ($w << 4) | $h;
        $this->buffer .= self::GS . "!" . chr($n);
        return $this;
    }

    /**
     * Set double width.
     */
    public function setDoubleWidth(bool $on): self
    {
        return $this->setCharacterSize($on ? 2 : 1, 1);
    }

    /**
     * Set double height.
     */
    public function setDoubleHeight(bool $on): self
    {
        return $this->setCharacterSize(1, $on ? 2 : 1);
    }

    /**
     * Set double width and height simultaneously.
     */
    public function setDoubleSize(bool $on): self
    {
        return $this->setCharacterSize($on ? 2 : 1, $on ? 2 : 1);
    }

    /**
     * Select Font A (default 12x24) or Font B (9x17) - ESC M n
     */
    public function setFont(int $font = 0): self
    {
        $this->buffer .= self::ESC . "M" . chr($font);
        return $this;
    }

    /**
     * Print text with encoding conversion.
     */
    public function text(string $text): self
    {
        $encoded = $this->encodeText($text);
        $this->buffer .= $encoded;
        return $this;
    }

    /**
     * Print text followed by newline.
     */
    public function textLine(string $text): self
    {
        return $this->text($text)->newLine();
    }

    /**
     * Print a new line.
     */
    public function newLine(): self
    {
        $this->buffer .= self::LF;
        return $this;
    }

    /**
     * Feed n lines - ESC d n
     */
    public function feed(int $lines = 1): self
    {
        $this->buffer .= self::ESC . "d" . chr($lines);
        return $this;
    }

    /**
     * Print a dashed separator line.
     */
    public function dashedLine(): self
    {
        $this->buffer .= str_repeat('-', $this->charsPerLine) . self::LF;
        return $this;
    }

    /**
     * Print a equals separator line.
     */
    public function doubleLine(): self
    {
        $this->buffer .= str_repeat('=', $this->charsPerLine) . self::LF;
        return $this;
    }

    /**
     * Print two columns — left-aligned label and right-aligned value.
     */
    public function twoColumnLine(string $left, string $right): self
    {
        $leftLen = mb_strlen($left);
        $rightLen = mb_strlen($right);
        $spaces = max(1, $this->charsPerLine - $leftLen - $rightLen);
        return $this->textLine($left . str_repeat(' ', $spaces) . $right);
    }

    /**
     * Print a row with multiple columns.
     * $columns = [['text' => 'foo', 'width' => 10, 'align' => 'left'], ...]
     */
    public function multiColumnLine(array $columns): self
    {
        $line = '';
        foreach ($columns as $col) {
            $text = $col['text'] ?? '';
            $width = $col['width'] ?? 10;
            $align = $col['align'] ?? 'left';

            // Truncate if too long
            if (mb_strlen($text) > $width) {
                $text = mb_substr($text, 0, $width);
            }

            switch ($align) {
                case 'right':
                    $line .= str_pad($text, $width, ' ', STR_PAD_LEFT);
                    break;
                case 'center':
                    $padTotal = $width - mb_strlen($text);
                    $padLeft = (int) floor($padTotal / 2);
                    $padRight = $padTotal - $padLeft;
                    $line .= str_repeat(' ', $padLeft) . $text . str_repeat(' ', $padRight);
                    break;
                default: // left
                    $line .= str_pad($text, $width);
                    break;
            }
        }
        return $this->textLine($line);
    }

    /**
     * Cut paper - GS V
     * Mode: 0=full cut, 1=partial cut, 66=partial cut with feed
     */
    public function cut(int $mode = 66, int $feed = 3): self
    {
        $this->buffer .= self::GS . "V" . chr($mode) . chr($feed);
        return $this;
    }

    /**
     * Open cash drawer - ESC p m t1 t2
     */
    public function openCashDrawer(): self
    {
        $this->buffer .= self::ESC . "p" . chr(0) . chr(25) . chr(250);
        return $this;
    }

    /**
     * Print raster bit image - GS v 0
     * $imageData should be the output of ImageConverter::convert()
     */
    public function printRasterImage(array $imageData): self
    {
        $width = $imageData['width'];
        $height = $imageData['height'];
        $data = $imageData['data'];

        // Bytes per line
        $bytesPerLine = (int) ceil($width / 8);

        // GS v 0 m xL xH yL yH d1...dk
        $this->buffer .= self::GS . "v0" . chr(0); // mode 0 = normal
        $this->buffer .= chr($bytesPerLine & 0xFF) . chr(($bytesPerLine >> 8) & 0xFF);
        $this->buffer .= chr($height & 0xFF) . chr(($height >> 8) & 0xFF);
        $this->buffer .= $data;

        return $this;
    }

    /**
     * Print barcode using native ESC/POS commands.
     * GS k m d1...dk NUL (for format A types)
     * GS k m n d1...dn (for format B types)
     */
    public function printBarcode(string $data, int $type = self::BARCODE_CODE39, int $height = 80): self
    {
        // Set barcode height - GS h n
        $this->buffer .= self::GS . "h" . chr($height);

        // Set barcode width - GS w n (2 = normal)
        $this->buffer .= self::GS . "w" . chr(2);

        // Set HRI (Human Readable Interpretation) position - GS H n
        // 2 = below barcode
        $this->buffer .= self::GS . "H" . chr(2);

        // Set HRI font - GS f n (0 = Font A)
        $this->buffer .= self::GS . "f" . chr(0);

        if ($type <= 6) {
            // Format A: GS k m d1...dk NUL
            $this->buffer .= self::GS . "k" . chr($type) . $data . self::NUL;
        } else {
            // Format B: GS k m n d1...dn
            $this->buffer .= self::GS . "k" . chr($type) . chr(strlen($data)) . $data;
        }

        return $this;
    }

    /**
     * Print QR code using native ESC/POS commands.
     * Uses GS ( k function for QR code.
     */
    public function printQrCode(string $data, int $moduleSize = 6, int $errorCorrection = 49): self
    {
        $len = strlen($data) + 3;

        // QR Code: Select model (Model 2)
        $this->buffer .= self::GS . "(k" . chr(4) . chr(0) . "1A" . chr(50) . chr(0);

        // QR Code: Set module size
        $this->buffer .= self::GS . "(k" . chr(3) . chr(0) . "1C" . chr($moduleSize);

        // QR Code: Set error correction level (48=L, 49=M, 50=Q, 51=H)
        $this->buffer .= self::GS . "(k" . chr(3) . chr(0) . "1E" . chr($errorCorrection);

        // QR Code: Store data
        $pL = ($len) & 0xFF;
        $pH = (($len) >> 8) & 0xFF;
        $this->buffer .= self::GS . "(k" . chr($pL) . chr($pH) . "1P0" . $data;

        // QR Code: Print
        $this->buffer .= self::GS . "(k" . chr(3) . chr(0) . "1Q0";

        return $this;
    }

    /**
     * Set character code page - ESC t n
     */
    public function setCodePage(int $page): self
    {
        $this->buffer .= self::ESC . "t" . chr($page);
        return $this;
    }

    /**
     * Raw bytes output.
     */
    public function raw(string $bytes): self
    {
        $this->buffer .= $bytes;
        return $this;
    }

    /**
     * Get the complete binary output.
     */
    public function getOutput(): string
    {
        return $this->buffer;
    }

    /**
     * Reset the buffer.
     */
    public function reset(): self
    {
        $this->buffer = '';
        return $this;
    }

    /**
     * Encode text according to configured encoding.
     * Handles special characters like ₦ (Naira sign).
     */
    private function encodeText(string $text): string
    {
        if ($this->encoding === 'UTF-8') {
            // Most modern thermal printers support UTF-8 natively
            // Replace ₦ with N= if printer doesn't handle it
            return $text;
        }

        // For legacy code pages, replace unsupported characters
        $text = str_replace('₦', 'NGN', $text);

        // Convert from UTF-8 to target encoding
        $converted = @iconv('UTF-8', $this->encoding . '//TRANSLIT//IGNORE', $text);
        return $converted !== false ? $converted : $text;
    }
}
