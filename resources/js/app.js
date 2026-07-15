import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

/**
 * Открывает всплывающую карточку профиля пользователя (как в Discord).
 * Вызывается из onclick/@click в шаблонах: openProfile(userId, event).
 * Сам попап слушает событие 'show-profile-popover' (см. components/profile-popover.blade.php).
 */
window.openProfile = function (userId, event) {
    if (event) {
        event.stopPropagation();
    }
    const rect = event?.currentTarget?.getBoundingClientRect();
    window.dispatchEvent(new CustomEvent('show-profile-popover', {
        detail: {
            userId,
            x: rect ? rect.right + 8 : 200,
            y: rect ? rect.top : 200,
        },
    }));
};

