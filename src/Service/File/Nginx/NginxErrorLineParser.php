<?php
declare(strict_types=1);

namespace FD\LogViewer\Service\File\Nginx;

use FD\LogViewer\Entity\Config\LogFilesConfig;
use FD\LogViewer\Service\File\LogLineParserInterface;

class NginxErrorLineParser implements LogLineParserInterface
{
    public const LOG_LINE_PATTERN =
        '/^(?P<date>[\d+\/ :]+) ' .
        '\[(?P<severity>.+)\] .*?: ' .
        '(?P<message>.+?)' .
        '(?:, client: (?P<ip>.+?))?' .
        '(?:, server: (?P<server>.*?))?' .
        '(?:, request: "?(?P<request>.+?)"?)?' .
        '(?:, upstream: "?(?P<upstream>.+?)"?)?' .
        '(?:, host: "?(?P<host>.+?)"?)?$/';

    public const DATE_FORMAT = 'Y/m/d H:i:s';

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

        $filter  = ['date', 'severity', 'message'];
        $context = array_filter(
            $matches,
            static fn($value, $key) => trim($value) !== '' && is_int($key) === false && in_array($key, $filter, true) === false,
            ARRAY_FILTER_USE_BOTH
        );

        return [
            'date'     => $matches['date'],
            'severity' => $matches['severity'],
            'channel'  => '',
            'message'  => $matches['message'],
            'context'  => $context,
            'extra'    => '',
        ];
    }

    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }
}
