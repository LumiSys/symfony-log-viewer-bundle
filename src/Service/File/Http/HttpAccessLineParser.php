<?php
declare(strict_types=1);

namespace FD\LogViewer\Service\File\Http;

use FD\LogViewer\Entity\Config\LogFilesConfig;
use FD\LogViewer\Service\File\LogLineParserInterface;

class HttpAccessLineParser implements LogLineParserInterface
{
    /** @noinspection RequiredAttributes */
    public const LOG_LINE_PATTERN =
        '/^(?P<ip>\S+) ' .
        '(?P<identity>\S+) ' .
        '(?P<remote_user>\S+) ' .
        '\[(?P<date>[^\]]+)\] ' .
        '"(?P<method>\S+) (?P<path>\S+) (?P<http_version>\S+)" ' .
        '(?P<status_code>\S+) ' .
        '(?P<content_length>\S+) ' .
        '"(?P<referrer>[^"]*)" ' .
        '"(?P<user_agent>[^"]*)"/';

    public const DATE_FORMAT = 'd/M/Y:H:i:s O';

    private readonly string $logLinePattern;
    private readonly string $dateFormat;

    public function __construct(private readonly LogFilesConfig $config)
    {
        $this->logLinePattern = $this->config->logMessagePattern ?? self::LOG_LINE_PATTERN;
        $this->dateFormat = $this->config->dateFormat ?? self::DATE_FORMAT;
    }

    /**
     * @inheritDoc
     */
    public function matches(string $line): int
    {
        return self::MATCH_START;
    }

    /**
     * @inheritDoc
     */
    public function parse(string $message): ?array
    {
        if (preg_match($this->logLinePattern, $message, $matches) !== 1) {
            return null;
        }

        $filter  = ['date', 'method', 'path'];
        $context = array_filter(
            $matches,
            static fn($value, $key) => trim($value) !== '' && is_int($key) === false && in_array($key, $filter, true) === false,
            ARRAY_FILTER_USE_BOTH
        );

        return [
            'date'     => $matches['date'],
            'severity' => $matches['status_code'],
            'channel'  => '',
            'message'  => sprintf('%s %s', $matches['method'] ?? '', $matches['path'] ?? ''),
            'context'  => $context,
            'extra'    => '',
        ];
    }

    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }
}
