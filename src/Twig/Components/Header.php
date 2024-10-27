<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\EnumType\ThemeEnumType;
use App\Model\Seo;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class Header
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public Seo $seo;

    #[LiveProp]
    public ThemeEnumType $theme = ThemeEnumType::LIGHT;

    public function mount(Seo $seo, ThemeEnumType $theme): void
    {
        $this->seo = $seo;
        $this->theme = $theme;
    }
}
