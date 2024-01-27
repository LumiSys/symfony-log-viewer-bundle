<?php
declare(strict_types=1);

namespace FD\LogViewer\Service\Parser;

use Exception;
use FD\LogViewer\Entity\Expression\DateAfterTerm;
use FD\LogViewer\Entity\Expression\DateBeforeTerm;
use FD\LogViewer\Entity\Expression\TermInterface;
use FD\LogViewer\Entity\Expression\WordTerm;
use FD\LogViewer\Reader\String\StringReader;

/**
 * BNF
 * <term> ::= <date-term> | <exclude-term> | <string>
 * <exclude-term> ::= exclude:<string>
 * <date-term> ::= before:<string> | after:<string>
 */
class TermParser
{
    public function __construct(private readonly StringParser $stringParser, private readonly DateParser $dateParser)
    {
    }

    /**
     * @throws InvalidDateTimeException
     */
    public function parse(StringReader $string): TermInterface
    {
        $string->skipWhitespace();

        if ($string->read('before:') || $string->read('b:')) {
            return new DateBeforeTerm($this->dateParser->toDateTimeImmutable($this->stringParser->parse($string)));
        }

        if ($string->read('after:') || $string->read('a:')) {
            return new DateAfterTerm($this->dateParser->toDateTimeImmutable($this->stringParser->parse($string)));
        }

        if ($string->read('exclude:') || $string->read('-:')) {
            return new WordTerm($this->stringParser->parse($string), WordTerm::TYPE_EXCLUDE);
        }

        return new WordTerm($this->stringParser->parse($string), WordTerm::TYPE_INCLUDE);
    }
}
