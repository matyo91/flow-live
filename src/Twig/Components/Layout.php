<?php

namespace App\Twig\Components;

use App\Enum\ThemeEnum;
use App\Model\Seo;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class Layout
{
    use DefaultActionTrait;

    #[LiveProp]
    public Seo $seo;

    #[LiveProp]
    public ThemeEnum $theme = ThemeEnum::LIGHT;
}
