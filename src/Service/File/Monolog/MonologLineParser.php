<?php
declare(strict_types=1);

namespace FD\LogViewer\Service\File\Monolog;

use FD\LogViewer\Entity\Config\LogFilesConfig;
use FD\LogViewer\Service\File\LogLineParserInterface;
use JsonException;

class MonologLineParser implements LogLineParserInterface
{
    /* [YYYY-MM-DD*] channel.level: */
    public const START_OF_MESSAGE_PATTERN = '/^\[\d{4}-\d{2}-\d{2}[^]]*]\s+\S+\.\S+:/';

    /* [date] channel.level: message {context} {extra} */
    public const LOG_LINE_PATTERN =
        '/^\[(?P<date>[^\]]+)\]\s+' .
        '(?P<channel>[^\.]+)\.(?P<severity>[^:]+):\s+' .
        '(?P<message>.*)\s+' .
        '(?P<context>[[{].*?[\]}])\s+' .
        '(?P<extra>[[{].*?[\]}])\s+$/s';

    public const DATE_FORMAT = 'Y-m-d H:i:s';

    private readonly ?string $startOfMessagePattern;
    private readonly string $logLinePattern;
    private readonly string $dateFormat;

    public function __construct(private readonly LogFilesConfig $config)
    {
        $this->startOfMessagePattern = $this->config->startOfLinePattern;
        $this->logLinePattern = $this->config->logMessagePattern ?? self::LOG_LINE_PATTERN;
        $this->dateFormat = $this->config->dateFormat ?? self::DATE_FORMAT;
    }

    /**
     * @inheritDoc
     */
    public function matches(string $line): int
    {
        if ($this->startOfMessagePattern === null || $this->startOfMessagePattern === '') {
            return self::MATCH_START;
        }

        return preg_match($this->startOfMessagePattern, $line) === 1 ? self::MATCH_START : self::MATCH_APPEND;
    }

    /**
     * @inheritDoc
     */
    public function parse(string $message): ?array
    {
        if (preg_match($this->logLinePattern, $message, $matches) !== 1) {
            return null;
        }

        return [
            'date'     => $matches['date'],
            'severity' => $matches['severity'],
            'channel'  => $matches['channel'],
            'message'  => $matches['message'],
            'context'  => self::toJsonOrString($matches['context']),
            'extra'    => self::toJsonOrString($matches['extra']),
        ];
    }

    /**
     * @return string|array<int|string, mixed>
     */
    private function toJsonOrString(string $data): string|array
    {
        try {
            $json = json_decode($data, true, flags: JSON_THROW_ON_ERROR);

            return is_array($json) ? $json : $data;
        } catch (JsonException) {
            return $data;
        }
    }

    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }
}
