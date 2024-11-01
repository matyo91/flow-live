<?php

declare(strict_types=1);

namespace App\EnumType\SymfonyCertification;

/**
 * Based on https://certification.symfony.com/faq.html.
 */
enum QuestionEnumType: string
{
    case TRUE_FALSE = 'true_false';
    case SINGLE_ANSWER = 'single_answer';
    case MULTIPLE_CHOICE = 'multiple_choice';
}
