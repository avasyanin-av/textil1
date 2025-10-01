/**
 * Основной JavaScript файл для TextilServer.ru
 * Интерактивные функции для улучшения пользовательского опыта
 */

document.addEventListener('DOMContentLoaded', function() {
    // Инициализация всех компонентов
    initSmoothScrolling();
    initFormValidation();
    initImageLazyLoading();
    initTooltips();
    initModals();
    initMobileMenu();
    initSearchFunctionality();
    initListingInteractions();
});

/**
 * Плавная прокрутка для якорных ссылок
 */
function initSmoothScrolling() {
    const anchors = document.querySelectorAll('a[href^="#"]');
    
    anchors.forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                e.preventDefault();
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

/**
 * Валидация форм в реальном времени
 */
function initFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            // Валидация при потере фокуса
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            // Очистка ошибок при вводе
            input.addEventListener('input', function() {
                clearFieldError(this);
            });
        });
        
        // Валидация при отправке формы
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Валидация отдельного поля
 */
function validateField(field) {
    const value = field.value.trim();
    const fieldType = field.type;
    const isRequired = field.hasAttribute('required');
    
    // Очистка предыдущих ошибок
    clearFieldError(field);
    
    // Проверка обязательных полей
    if (isRequired && !value) {
        showFieldError(field, 'Это поле обязательно для заполнения');
        return false;
    }
    
    // Валидация email
    if (fieldType === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            showFieldError(field, 'Введите корректный email адрес');
            return false;
        }
    }
    
    // Валидация телефона
    if (fieldType === 'tel' && value) {
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        if (!phoneRegex.test(value.replace(/[\s\-\(\)]/g, ''))) {
            showFieldError(field, 'Введите корректный номер телефона');
            return false;
        }
    }
    
    // Валидация паролей
    if (fieldType === 'password' && value) {
        if (value.length < 6) {
            showFieldError(field, 'Пароль должен содержать минимум 6 символов');
            return false;
        }
    }
    
    return true;
}

/**
 * Валидация всей формы
 */
function validateForm(form) {
    const fields = form.querySelectorAll('input, textarea, select');
    let isValid = true;
    
    fields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    return isValid;
}

/**
 * Отображение ошибки поля
 */
function showFieldError(field, message) {
    field.classList.add('error');
    
    // Удаление существующего сообщения об ошибке
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    
    // Создание нового сообщения об ошибке
    const errorElement = document.createElement('div');
    errorElement.className = 'field-error';
    errorElement.textContent = message;
    
    field.parentNode.appendChild(errorElement);
}

/**
 * Очистка ошибки поля
 */
function clearFieldError(field) {
    field.classList.remove('error');
    
    const errorElement = field.parentNode.querySelector('.field-error');
    if (errorElement) {
        errorElement.remove();
    }
}

/**
 * Ленивая загрузка изображений
 */
function initImageLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback для старых браузеров
        images.forEach(img => {
            img.src = img.dataset.src;
            img.classList.remove('lazy');
        });
    }
}

/**
 * Инициализация тултипов
 */
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

/**
 * Показать тултип
 */
function showTooltip(e) {
    const element = e.target;
    const tooltipText = element.getAttribute('data-tooltip');
    
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = tooltipText;
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
    
    element._tooltip = tooltip;
}

/**
 * Скрыть тултип
 */
function hideTooltip(e) {
    const element = e.target;
    if (element._tooltip) {
        element._tooltip.remove();
        delete element._tooltip;
    }
}

/**
 * Инициализация модальных окон
 */
function initModals() {
    const modalTriggers = document.querySelectorAll('[data-modal]');
    const modals = document.querySelectorAll('.modal');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                showModal(modal);
            }
        });
    });
    
    modals.forEach(modal => {
        const closeBtn = modal.querySelector('.modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => hideModal(modal));
        }
        
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                hideModal(this);
            }
        });
    });
    
    // Закрытие модалей по Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const activeModal = document.querySelector('.modal.active');
            if (activeModal) {
                hideModal(activeModal);
            }
        }
    });
}

/**
 * Показать модальное окно
 */
function showModal(modal) {
    modal.classList.add('active');
    document.body.classList.add('modal-open');
}

/**
 * Скрыть модальное окно
 */
function hideModal(modal) {
    modal.classList.remove('active');
    document.body.classList.remove('modal-open');
}

/**
 * Мобильное меню
 */
function initMobileMenu() {
    const menuToggle = document.querySelector('.menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    
    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', function() {
            mainNav.classList.toggle('active');
            this.classList.toggle('active');
        });
        
        // Закрытие меню при клике на ссылку
        const navLinks = mainNav.querySelectorAll('a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                mainNav.classList.remove('active');
                menuToggle.classList.remove('active');
            });
        });
    }
}

/**
 * Функциональность поиска
 */
function initSearchFunctionality() {
    const searchInputs = document.querySelectorAll('.search-input');
    
    searchInputs.forEach(input => {
        let searchTimeout;
        
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(() => {
                    performSearch(query, this);
                }, 300);
            } else {
                hideSearchResults(this);
            }
        });
        
        // Скрытие результатов при потере фокуса
        input.addEventListener('blur', function() {
            setTimeout(() => hideSearchResults(this), 200);
        });
    });
}

/**
 * Выполнение поиска
 */
function performSearch(query, inputElement) {
    const searchType = inputElement.getAttribute('data-search-type') || 'listings';
    
    fetch(`api/search.php?q=${encodeURIComponent(query)}&type=${searchType}`)
        .then(response => response.json())
        .then(data => {
            showSearchResults(data, inputElement);
        })
        .catch(error => {
            console.error('Ошибка поиска:', error);
        });
}

/**
 * Показать результаты поиска
 */
function showSearchResults(results, inputElement) {
    const container = inputElement.parentNode;
    let resultsContainer = container.querySelector('.search-results');
    
    if (!resultsContainer) {
        resultsContainer = document.createElement('div');
        resultsContainer.className = 'search-results';
        container.appendChild(resultsContainer);
    }
    
    if (results.length === 0) {
        resultsContainer.innerHTML = '<div class="search-no-results">Ничего не найдено</div>';
    } else {
        resultsContainer.innerHTML = results.map(item => 
            `<div class="search-result-item" data-id="${item.id}">
                <div class="search-result-title">${item.title}</div>
                <div class="search-result-meta">${item.meta}</div>
            </div>`
        ).join('');
        
        // Обработка кликов по результатам
        resultsContainer.querySelectorAll('.search-result-item').forEach(item => {
            item.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const type = inputElement.getAttribute('data-search-type') || 'listings';
                window.location.href = `${type}.php?id=${id}`;
            });
        });
    }
    
    resultsContainer.classList.add('visible');
}

/**
 * Скрыть результаты поиска
 */
function hideSearchResults(inputElement) {
    const container = inputElement.parentNode;
    const resultsContainer = container.querySelector('.search-results');
    
    if (resultsContainer) {
        resultsContainer.classList.remove('visible');
    }
}

/**
 * Интерактивность для объявлений
 */
function initListingInteractions() {
    // Кнопки "Показать телефон"
    const phoneButtons = document.querySelectorAll('.show-phone-btn');
    phoneButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const phone = this.getAttribute('data-phone');
            if (phone) {
                this.textContent = phone;
                this.classList.add('revealed');
            }
        });
    });
    
    // Избранное
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    favoriteButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const listingId = this.getAttribute('data-listing-id');
            toggleFavorite(listingId, this);
        });
    });
    
    // Жалобы
    const reportButtons = document.querySelectorAll('.report-btn');
    reportButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const listingId = this.getAttribute('data-listing-id');
            showReportModal(listingId);
        });
    });
}

/**
 * Переключение избранного
 */
function toggleFavorite(listingId, button) {
    fetch('api/favorites.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            listing_id: listingId,
            action: 'toggle'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.classList.toggle('active', data.is_favorite);
            button.title = data.is_favorite ? 'Удалить из избранного' : 'Добавить в избранное';
        }
    })
    .catch(error => {
        console.error('Ошибка при работе с избранным:', error);
    });
}

/**
 * Показать модальное окно жалобы
 */
function showReportModal(listingId) {
    const modal = document.getElementById('report-modal');
    if (modal) {
        modal.setAttribute('data-listing-id', listingId);
        showModal(modal);
    }
}

/**
 * Утилитарные функции
 */

// Дебаунс функция
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Троттлинг функция
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Анимация чисел
function animateNumber(element, start, end, duration = 2000) {
    const range = end - start;
    const stepTime = Math.abs(Math.floor(duration / range));
    const startTime = new Date().getTime();
    const endTime = startTime + duration;
    
    function run() {
        const now = new Date().getTime();
        const remaining = Math.max((endTime - now) / duration, 0);
        const value = Math.round(end - (remaining * range));
        
        element.textContent = value.toLocaleString();
        
        if (value !== end) {
            requestAnimationFrame(run);
        }
    }
    
    run();
}

// Показать уведомление
function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    const container = document.querySelector('.notifications-container') || 
                     createNotificationsContainer();
    
    container.appendChild(notification);
    
    // Анимация появления
    requestAnimationFrame(() => {
        notification.classList.add('show');
    });
    
    // Автоматическое скрытие
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, duration);
    
    // Закрытие по клику
    notification.addEventListener('click', () => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    });
}

// Создать контейнер для уведомлений
function createNotificationsContainer() {
    const container = document.createElement('div');
    container.className = 'notifications-container';
    document.body.appendChild(container);
    return container;
}

// Экспорт функций для использования в других скриптах
window.TextilServer = {
    showNotification,
    showModal,
    hideModal,
    animateNumber,
    debounce,
    throttle
};