@php
    // Вставляем class/атрибуты пользователя прямо в корневой <svg>, сохраняя
    // остальную разметку иконки как есть (safe: содержимое приходит только
    // из локальных файлов resources/icons, не от пользователя).
    $attrs = $attributes->merge(['class' => 'inline-block shrink-0'])->get('class');
    $extra = $attributes->except(['class'])->merge([])->toHtml();
    $out = preg_replace('/^<svg/', '<svg class="'.e($attrs).'" '.$extra, $svg, 1);
@endphp
{!! $out !!}
