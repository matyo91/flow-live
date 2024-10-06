<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\EnumType\ThemeEnumType;
use App\Model\Seo;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class Content
{
    use DefaultActionTrait;

    #[LiveProp]
    public Seo $seo;

    #[LiveProp]
    public ThemeEnumType $theme = ThemeEnumType::LIGHT;

    public function mount(Seo $seo): void
    {
        $this->seo = $seo;
    }

    #[LiveListener('themeChanged')]
    public function changeTheme(): void
    {
        $this->theme = match ($this->theme) {
            ThemeEnumType::LIGHT => ThemeEnumType::DARK,
            ThemeEnumType::DARK => ThemeEnumType::SEPIA,
            ThemeEnumType::SEPIA => ThemeEnumType::LIGHT,
        };
    }
}
