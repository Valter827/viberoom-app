<?php

namespace App\View\Components;

use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * <x-icon name="phone" class="w-5 h-5" />
 *
 * Инлайнит SVG-иконку из Lucide (resources/icons/*.svg) прямо в разметку —
 * никакого JS-инициализатора и мигания "пустых" иконок при загрузке страницы,
 * как бывает с шрифтовыми/CDN-иконками. Все лишние атрибуты (width/height/class
 * из самого файла) убираются, чтобы стили полностью управлялись снаружи через
 * проп class, а stroke-width можно поджать/утолщить пропом.
 */
class Icon extends Component
{
    public string $svg;

    public function __construct(
        public string $name,
        public string $strokeWidth = '2',
    ) {
        $this->svg = $this->loadSvg($name, $strokeWidth);
    }

    private function loadSvg(string $name, string $strokeWidth): string
    {
        $safeName = preg_replace('/[^a-z0-9\-]/', '', $name);
        $cacheKey = "icon-svg:{$safeName}:{$strokeWidth}";

        return Cache::rememberForever($cacheKey, function () use ($safeName, $strokeWidth) {
            $path = resource_path("icons/{$safeName}.svg");

            if (! is_file($path)) {
                // Иконка не найдена — рисуем маленький крестик-заглушку,
                // чтобы сразу было заметно в разработке, а не тихо ломало вёрстку.
                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>';
            }

            $raw = file_get_contents($path);
            // Убираем комментарий с лицензией и внешние width/height/class —
            // размер и цвет полностью отдаём Tailwind-классам снаружи.
            $raw = preg_replace('/<!--.*?-->/s', '', $raw);
            $raw = preg_replace('/\s(width|height|class)="[^"]*"/', '', $raw);
            $raw = preg_replace('/stroke-width="[\d.]+"/', 'stroke-width="'.htmlspecialchars($strokeWidth).'"', $raw);

            return trim($raw);
        });
    }

    public function render(): View
    {
        return view('components.icon');
    }
}
