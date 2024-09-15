<?php

namespace App\Twig\Components;

use App\Enum\ThemeEnum;
use App\Model\Seo;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class Header
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp]
    public Seo $seo;

    #[LiveProp]
    public ThemeEnum $theme = ThemeEnum::LIGHT;

    #[LiveAction]
    public function themeChanged()
    {
        $this->emit('themeChanged');
    }
}
