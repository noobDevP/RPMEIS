<?php

namespace App\Services;

use Generator;
use RuntimeException;

class LegacySqlDumpParser
{
    /**
     * Stream associative rows from INSERT INTO `{$tableName}` statements in a SQL dump.
     *
     * @return Generator<int, array<string, ?string>>
     */
    public function parseTableRows(string $sqlFilePath, string $tableName = 'patient_data'): Generator
    {
        if (! is_file($sqlFilePath) || ! is_readable($sqlFilePath)) {
            throw new RuntimeException("SQL file is missing or unreadable: {$sqlFilePath}");
        }

        $handle = fopen($sqlFilePath, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Unable to open SQL file: {$sqlFilePath}");
        }

        try {
            $fallbackColumns = [];
            $statementBuffer = '';
            $capturingCreate = false;
            $capturingInsert = false;
            $createPattern = '/^\s*CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?' . preg_quote($tableName, '/') . '`?/i';
            $insertPattern = '/^\s*INSERT\s+(?:IGNORE\s+)?INTO\s+`?' . preg_quote($tableName, '/') . '`?\b/i';

            while (($line = fgets($handle)) !== false) {
                if (! $capturingCreate && ! $capturingInsert) {
                    if ($fallbackColumns === [] && preg_match($createPattern, $line) === 1) {
                        $capturingCreate = true;
                        $statementBuffer = $line;
                        if ($this->endsStatement($statementBuffer)) {
                            $fallbackColumns = $this->extractCreateColumns($statementBuffer);
                            $capturingCreate = false;
                            $statementBuffer = '';
                        }
                    } elseif (preg_match($insertPattern, $line) === 1) {
                        $capturingInsert = true;
                        $statementBuffer = $line;
                        if ($this->endsStatement($statementBuffer)) {
                            foreach ($this->parseInsertStatement($statementBuffer, $fallbackColumns) as $row) {
                                yield $row;
                            }
                            $capturingInsert = false;
                            $statementBuffer = '';
                        }
                    }

                    continue;
                }

                $statementBuffer .= $line;
                if ($this->endsStatement($statementBuffer)) {
                    if ($capturingCreate) {
                        $fallbackColumns = $this->extractCreateColumns($statementBuffer);
                        $capturingCreate = false;
                    } elseif ($capturingInsert) {
                        foreach ($this->parseInsertStatement($statementBuffer, $fallbackColumns) as $row) {
                            yield $row;
                        }
                        $capturingInsert = false;
                    }
                    $statementBuffer = '';
                }
            }

            if ($capturingInsert && trim($statementBuffer) !== '') {
                foreach ($this->parseInsertStatement($statementBuffer, $fallbackColumns) as $row) {
                    yield $row;
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Check whether a SQL statement buffer ends with an unquoted semicolon.
     */
    private function endsStatement(string $sql): bool
    {
        $trimmed = rtrim($sql);
        if (! str_ends_with($trimmed, ';')) {
            return false;
        }

        $inQuote = false;
        $escaped = false;
        $len = strlen($trimmed);
        for ($i = 0; $i < $len; $i++) {
            $ch = $trimmed[$i];
            if ($escaped) {
                $escaped = false;
                continue;
            }
            if ($ch === '\\') {
                $escaped = true;
                continue;
            }
            if ($ch === "'") {
                if ($inQuote && $i + 1 < $len && $trimmed[$i + 1] === "'") {
                    $i++;
                    continue;
                }
                $inQuote = ! $inQuote;
            }
        }

        return ! $inQuote;
    }

    /**
     * Extract column names from a CREATE TABLE block as fallback when INSERT omits column list.
     *
     * @return list<string>
     */
    private function extractCreateColumns(string $createSql): array
    {
        $columns = [];
        foreach (preg_split('/\R/', $createSql) ?: [] as $line) {
            if (preg_match('/^\s*`([^`]+)`\s+/i', $line, $m) === 1) {
                $columns[] = $m[1];
            }
        }

        return $columns;
    }

    /**
     * Parse one INSERT INTO `table` [(cols)] VALUES (...), (...); statement.
     *
     * @param  list<string>  $fallbackColumns
     * @return list<array<string, ?string>>
     */
    private function parseInsertStatement(string $sql, array $fallbackColumns): array
    {
        $valuesPos = stripos($sql, 'VALUES');
        if ($valuesPos === false) {
            return [];
        }

        $header = substr($sql, 0, $valuesPos);
        $columns = $fallbackColumns;

        $openParen = strpos($header, '(');
        $closeParen = strrpos($header, ')');
        if ($openParen !== false && $closeParen !== false && $closeParen > $openParen) {
            $colList = substr($header, $openParen + 1, $closeParen - $openParen - 1);
            preg_match_all('/`([^`]+)`|([A-Za-z0-9_]+)/', $colList, $matches);
            $parsed = [];
            foreach ($matches[1] as $idx => $quotedCol) {
                $parsed[] = $quotedCol !== '' ? $quotedCol : $matches[2][$idx];
            }
            if ($parsed !== []) {
                $columns = $parsed;
            }
        }

        if ($columns === []) {
            return [];
        }

        $valuesSection = substr($sql, $valuesPos + 6);
        $tuples = $this->parseTuples($valuesSection);

        $rows = [];
        $colCount = count($columns);
        foreach ($tuples as $tuple) {
            if (count($tuple) !== $colCount) {
                continue;
            }
            $rows[] = array_combine($columns, $tuple);
        }

        return $rows;
    }

    /**
     * Tokenize all "(...)" tuples in a SQL VALUES payload.
     *
     * @return list<list<?string>>
     */
    private function parseTuples(string $valuesSection): array
    {
        $tuples = [];
        $len = strlen($valuesSection);
        $i = 0;

        while ($i < $len) {
            $ch = $valuesSection[$i];
            if ($ch === '(') {
                $i++;
                $fields = [];
                while ($i < $len) {
                    while ($i < $len && ctype_space($valuesSection[$i])) {
                        $i++;
                    }
                    if ($i >= $len) {
                        break;
                    }
                    if ($valuesSection[$i] === ')') {
                        $i++;
                        break;
                    }

                    if ($valuesSection[$i] === "'") {
                        [$value, $i] = $this->readQuotedString($valuesSection, $i + 1, $len);
                        $fields[] = $value;
                    } else {
                        [$value, $i] = $this->readUnquotedValue($valuesSection, $i, $len);
                        $fields[] = $value;
                    }

                    while ($i < $len && ctype_space($valuesSection[$i])) {
                        $i++;
                    }
                    if ($i < $len && $valuesSection[$i] === ',') {
                        $i++;
                        continue;
                    }
                    if ($i < $len && $valuesSection[$i] === ')') {
                        $i++;
                        break;
                    }
                }
                $tuples[] = $fields;
                continue;
            }

            if ($ch === ';') {
                break;
            }
            $i++;
        }

        return $tuples;
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function readQuotedString(string $sql, int $pos, int $len): array
    {
        $out = '';
        $escapes = [
            '0' => "\0",
            'b' => "\x08",
            'n' => "\n",
            'r' => "\r",
            't' => "\t",
            'Z' => "\x1A",
            '\\' => '\\',
            "'" => "'",
            '"' => '"',
        ];

        while ($pos < $len) {
            $ch = $sql[$pos];
            if ($ch === '\\' && $pos + 1 < $len) {
                $next = $sql[$pos + 1];
                $out .= $escapes[$next] ?? $next;
                $pos += 2;
                continue;
            }
            if ($ch === "'") {
                if ($pos + 1 < $len && $sql[$pos + 1] === "'") {
                    $out .= "'";
                    $pos += 2;
                    continue;
                }
                $pos++;
                break;
            }
            $out .= $ch;
            $pos++;
        }

        return [$out, $pos];
    }

    /**
     * @return array{0: ?string, 1: int}
     */
    private function readUnquotedValue(string $sql, int $pos, int $len): array
    {
        $start = $pos;
        while ($pos < $len && $sql[$pos] !== ',' && $sql[$pos] !== ')') {
            $pos++;
        }

        $raw = trim(substr($sql, $start, $pos - $start));
        if ($raw === '' || strtoupper($raw) === 'NULL') {
            return [null, $pos];
        }

        return [$raw, $pos];
    }
}
