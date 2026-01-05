<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;

class Qris
{
    public function sanitizeQrisString($qris)
    {
        $qris = (string) $qris;
        $qris = trim($qris);

        // Remove whitespace/newlines that often appear when copy/pasting.
        $qris = preg_replace('/\s+/', '', $qris);

        return $qris;
    }

    /**
     * CRC-16/CCITT-FALSE implementation (poly 0x1021, init 0xFFFF), output uppercase hex (4 chars).
     */
    public function crc16($str)
    {
        $crc = 0xFFFF;
        $strlen = strlen($str);

        for ($c = 0; $c < $strlen; $c++)
        {
            $crc ^= (ord($str[$c]) << 8);
            for ($i = 0; $i < 8; $i++)
            {
                if ($crc & 0x8000)
                {
                    $crc = (($crc << 1) ^ 0x1021);
                }
                else
                {
                    $crc = ($crc << 1);
                }
            }
        }

        $hex = strtoupper(dechex($crc & 0xFFFF));
        return str_pad($hex, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generates a Dynamic QRIS payload from a static QRIS EMV string.
     *
     * Implementation notes:
     * - Parses EMV TLV (ID=2 chars, LEN=2 chars, VALUE=LEN chars).
     * - Forces tag 01 to dynamic: "12".
     * - Sets/replaces tag 54 (transaction amount).
     * - Recomputes CRC (tag 63) based on the payload ending in "6304".
     */
    public function generateDynamicQris($staticQris, $amount)
    {
        $staticQris = $this->sanitizeQrisString($staticQris);

        if (strlen($staticQris) < 8)
        {
            throw new InvalidArgumentException('Invalid static QRIS data.');
        }

        $amountInt = (int) $amount;
        if ($amountInt <= 0)
        {
            throw new InvalidArgumentException('Amount must be greater than 0.');
        }

        $tags = $this->parseTlv($staticQris);

        // Force Point of Initiation Method to dynamic
        $tags = $this->upsertTag($tags, '01', '12');

        // Transaction amount (no decimals for IDR by default)
        $tags = $this->upsertTag($tags, '54', (string) $amountInt, 'after', '53');

        // Remove tag 63 if present, we'll re-add it at the end
        $tags = $this->removeTag($tags, '63');

        $withoutCrcValue = $this->buildTlv($tags) . '6304';
        $crc = $this->crc16($withoutCrcValue);

        return $withoutCrcValue . $crc;
    }

    private function parseTlv($data)
    {
        $data = (string) $data;
        $len = strlen($data);
        $pos = 0;
        $tags = [];

        while ($pos + 4 <= $len)
        {
            $id = substr($data, $pos, 2);
            $pos += 2;
            $lstr = substr($data, $pos, 2);
            $pos += 2;

            // Some decoders return truncated strings; if we can't parse further, stop gracefully.
            if (!ctype_digit($lstr))
            {
                break;
            }

            $l = (int) $lstr;
            if ($l < 0 || $pos + $l > $len)
            {
                break;
            }

            $value = substr($data, $pos, $l);
            $pos += $l;

            // Stop parsing if CRC tag encountered (ignore existing CRC value)
            if ($id === '63')
            {
                break;
            }

            $tags[] = ['id' => $id, 'value' => $value];
        }

        if (empty($tags) || strpos($data, '000201') !== 0)
        {
            // Not strictly required, but helps catch non-QRIS data early.
            throw new InvalidArgumentException('QRIS data does not start with EMV header (000201).');
        }

        return $tags;
    }

    private function buildTlv($tags)
    {
        $out = '';
        foreach ($tags as $tag)
        {
            $id = (string) $tag['id'];
            $value = (string) $tag['value'];
            $out .= $id . str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT) . $value;
        }
        return $out;
    }

    private function removeTag($tags, $id)
    {
        $id = (string) $id;
        $out = [];
        foreach ($tags as $tag)
        {
            if ($tag['id'] === $id)
            {
                continue;
            }
            $out[] = $tag;
        }
        return $out;
    }

    /**
     * Inserts or replaces a tag.
     * If $insertMode is provided, inserts relative to an existing tag id.
     */
    private function upsertTag($tags, $id, $value, $insertMode = null, $relativeTo = null)
    {
        $id = (string) $id;
        $value = (string) $value;

        // Replace if exists
        for ($i = 0; $i < count($tags); $i++)
        {
            if ($tags[$i]['id'] === $id)
            {
                $tags[$i]['value'] = $value;
                return $tags;
            }
        }

        // Insert new
        if ($insertMode && $relativeTo)
        {
            for ($i = 0; $i < count($tags); $i++)
            {
                if ($tags[$i]['id'] === $relativeTo)
                {
                    $insertAt = ($insertMode === 'after') ? $i + 1 : $i;
                    array_splice($tags, $insertAt, 0, [[
                        'id' => $id,
                        'value' => $value,
                    ]]);
                    return $tags;
                }
            }
        }

        $tags[] = ['id' => $id, 'value' => $value];
        return $tags;
    }

    /**
     * Renders a QR payload as PNG binary string.
     */
    public function renderPng($payload, $scale = 6)
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'scale' => max(1, (int) $scale),
            'imageBase64' => false,
        ]);

        return (new QRCode($options))->render((string) $payload);
    }
}
