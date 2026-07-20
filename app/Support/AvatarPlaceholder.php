<?php

namespace App\Support;

/**
 * Генерирует заглушку-аватар (первая буква имени на цветном фоне) в виде SVG,
 * закодированного как data-URI. Раньше для этого использовался внешний сервис
 * ui-avatars.com — каждая такая иконка была отдельным сетевым запросом к
 * стороннему серверу, из-за чего на странице было заметно, как аватарки
 * "проявляются" одна за другой при загрузке. Data-URI встроен прямо в HTML —
 * рендерится мгновенно, без единого дополнительного запроса.
 */
class AvatarPlaceholder
{
    /**
     * Палитра фоновых цветов — детерминированный выбор по имени, чтобы у
     * одного и того же пользователя/сервера всегда был один и тот же цвет.
     */
    private const COLORS = [
        '#5865F2', '#EB459E', '#57F287', '#FEE75C',
        '#ED4245', '#3BA55D', '#FAA61A', '#9B59B6',
    ];

    public static function dataUri(string $name, ?string $backgroundColor = null): string
    {
        $letter = mb_strtoupper(mb_substr(trim($name) ?: '?', 0, 1));
        $letter = htmlspecialchars($letter, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $color = $backgroundColor ?? self::COLORS[crc32($name) % count(self::COLORS)];

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">
    <rect width="128" height="128" rx="64" fill="{$color}"/>
    <text x="50%" y="50%" dy=".08em" text-anchor="middle" dominant-baseline="middle"
          font-family="Arial, Helvetica, sans-serif" font-size="56" font-weight="600" fill="#ffffff">{$letter}</text>
</svg>
SVG;

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
