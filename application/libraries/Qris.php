<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;

class Qris
{
    public function sanitizeQrisString($qris)
    {
        $qris = (string) $qris;
        $qris = trim($qris);

        // Only remove common copy/paste control characters.
        // IMPORTANT: do not remove normal spaces because they can be part of TLV values
        // (e.g., merchant name) and would break EMV length fields.
        $qris = str_replace(["\r", "\n", "\t"], '', $qris);

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
     * Validates that an EMV QR string is TLV well-formed and ends with a CRC tag (63).
     *
     * This catches common corruption like removing spaces inside TLV values, which breaks
     * the declared length fields and causes strict (banking) scanners to reject the QR.
     */
    public function isWellFormedEmv($qris)
    {
        $qris = $this->sanitizeQrisString($qris);

        if (strpos($qris, '000201') !== 0)
        {
            return false;
        }

        $len = strlen($qris);
        $pos = 0;
        $seenCrc = false;

        while ($pos + 4 <= $len)
        {
            $id = substr($qris, $pos, 2);
            $pos += 2;

            $lstr = substr($qris, $pos, 2);
            $pos += 2;

            if (!ctype_digit($lstr))
            {
                return false;
            }

            $l = (int) $lstr;
            if ($l < 0 || ($pos + $l) > $len)
            {
                return false;
            }

            if ($id === '63')
            {
                // CRC value length must be 4 and must be the last field
                if ($l !== 4)
                {
                    return false;
                }

                $pos += 4;
                $seenCrc = true;
                break;
            }

            $pos += $l;
        }

        return $seenCrc && ($pos === $len);
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

        // Prefer the exact algorithm used by qris-dinamis-generator for strict compatibility:
        // 1) strip CRC (last 4 chars)
        // 2) replace ONCE: 010211 -> 010212
        // 3) insert amount tag BEFORE "5802ID"
        // 4) recompute CRC16 on payload (which already ends with "6304")
        $looksLikeEmvWithCrc = (bool) preg_match('/^000201.*6304[0-9A-Fa-f]{4}$/', $staticQris);
        if ($looksLikeEmvWithCrc && strpos($staticQris, '5802ID') !== false)
        {
            $qrisWithoutCrc = substr($staticQris, 0, -4);
            $step1 = preg_replace('/010211/', '010212', $qrisWithoutCrc, 1);

            $parts = explode('5802ID', $step1);
            if (count($parts) !== 2)
            {
                // Unexpected but safe: fallback to TLV-based method below.
                $parts = null;
            }

            if ($parts !== null)
            {
                $amountStr = (string) $amountInt;
                $amountTag = '54' . str_pad((string) strlen($amountStr), 2, '0', STR_PAD_LEFT) . $amountStr;

                $payload = $parts[0] . $amountTag . '5802ID' . $parts[1];
                $crc = $this->crc16($payload);

                return $payload . $crc;
            }
        }

        // Fallback: TLV-based (more tolerant for uncommon payloads)
        $tags = $this->parseTlv($staticQris);

        // Force Point of Initiation Method to dynamic
        $tags = $this->upsertTag($tags, '01', '12', 'after', '00');

        // Transaction amount (no decimals for IDR by default)
        if ($this->hasTag($tags, '53'))
        {
            $tags = $this->upsertTag($tags, '54', (string) $amountInt, 'after', '53');
        }
        else
        {
            // Some payloads might not include 53 in an unusual way; insert amount in a safe canonical spot.
            $tags = $this->insertBeforeFirstOf($tags, '54', (string) $amountInt, ['58', '59', '60', '61', '62']);
        }

        // Remove tag 63 if present, we'll re-add it at the end
        $tags = $this->removeTag($tags, '63');

        $withoutCrcValue = $this->buildTlv($tags) . '6304';
        $crc = $this->crc16($withoutCrcValue);

        return $withoutCrcValue . $crc;
    }

    private function hasTag($tags, $id)
    {
        $id = (string) $id;
        foreach ($tags as $tag)
        {
            if ($tag['id'] === $id)
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Inserts a new tag before the first occurrence of any tag id in $candidates.
     * If the tag already exists, it will be replaced.
     */
    private function insertBeforeFirstOf($tags, $id, $value, $candidates)
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

        $candidates = (array) $candidates;
        for ($i = 0; $i < count($tags); $i++)
        {
            if (in_array($tags[$i]['id'], $candidates, true))
            {
                array_splice($tags, $i, 0, [[
                    'id' => $id,
                    'value' => $value,
                ]]);
                return $tags;
            }
        }

        $tags[] = ['id' => $id, 'value' => $value];
        return $tags;
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
    public function renderPng($payload, $scale = 10)
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            // Bigger modules + generous quietzone help mobile banking scanners.
            'scale' => max(6, (int) $scale),
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
            'quietzoneSize' => 8,
            // Return raw PNG bytes (NOT a data URI)
            'outputBase64' => false,
        ]);

        return (new QRCode($options))->render((string) $payload);
    }
}
