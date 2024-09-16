<?php

namespace App\Twig\Components;

use App\Enum\ThemeEnum;
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
    public ThemeEnum $theme = ThemeEnum::LIGHT;

    public function mount(Seo $seo)
    {
        $this->seo = $seo;
    }

    #[LiveListener('themeChanged')]
    public function changeTheme()
    {
        $this->theme = match($this->theme) {
            ThemeEnum::LIGHT => ThemeEnum::DARK,
            ThemeEnum::DARK => ThemeEnum::SEPIA,
            ThemeEnum::SEPIA => ThemeEnum::LIGHT,
        };
    }
}
