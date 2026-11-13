<?php

namespace Noop\Documents;

enum Format: string
{
    case Pdf = 'application/pdf';
    case Docx = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    /** The leading bytes every valid file of this format starts with. */
    public function signature(): string
    {
        return match ($this) {
            self::Pdf => '%PDF',
            self::Docx => 'PK',
        };
    }
}
