/* OnTrip — JavaScript chung, trang chủ, danh sách tour và chi tiết tour. */

/* ===== NAVBAR DÙNG CHUNG ===== */

/* NAVBAR DÙNG CHUNG cho trang chủ và trang danh sách tour. */
document.addEventListener('DOMContentLoaded', () => {
    if (!document.querySelector('.site-header')) return;
    // 2. MENU: chuột, bàn phím và chạm trên điện thoại.
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const navigation = document.querySelector('.main-navigation');
    const searchToggle = document.querySelector('.nav-search-toggle');
    const searchPanel = document.querySelector('.nav-search-panel');
    const searchInput = document.querySelector('#navbar-search-input');

    // Ô tìm kiếm navbar: mở và đưa con trỏ vào ô nhập.
    function closeSearch(restoreFocus = false) {
        searchPanel.hidden = true;
        searchToggle.setAttribute('aria-expanded', 'false');
        searchToggle.setAttribute('aria-label', 'Mở ô tìm tour');
        if (restoreFocus) searchToggle.focus();
    }

    searchToggle.addEventListener('click', () => {
        if (!searchPanel.hidden) {
            closeSearch(true);
            return;
        }
        closeNavigation();
        searchPanel.hidden = false;
        searchToggle.setAttribute('aria-expanded', 'true');
        searchToggle.setAttribute('aria-label', 'Đóng ô tìm tour');
        searchInput.focus();
    });

    searchPanel.addEventListener('submit', (event) => {
        // Chỉ là giao diện; chưa gửi từ khóa hoặc chuyển sang trang kết quả.
        event.preventDefault();
        searchInput.focus();
    });
    document.querySelector('.nav-search').addEventListener('focusout', (event) => {
        if (!event.currentTarget.contains(event.relatedTarget)) closeSearch();
    });

    function closeMenus() {
        document.querySelectorAll('.dropdown').forEach((dropdown) => {
            dropdown.classList.remove('is-open');
            dropdown.querySelector('.dropdown-toggle').setAttribute('aria-expanded', 'false');
        });
    }

    document.querySelectorAll('.dropdown').forEach((dropdown) => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        toggle.addEventListener('click', () => {
            closeSearch();
            const open = !dropdown.classList.contains('is-open');
            closeMenus();
            dropdown.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', String(open));
        });
        dropdown.addEventListener('mouseenter', () => toggle.setAttribute('aria-expanded', 'true'));
        dropdown.addEventListener('mouseleave', () => toggle.setAttribute('aria-expanded', String(dropdown.classList.contains('is-open'))));
        dropdown.addEventListener('focusin', () => toggle.setAttribute('aria-expanded', 'true'));
        dropdown.addEventListener('focusout', (event) => {
            if (!dropdown.contains(event.relatedTarget)) {
                dropdown.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    });

    mobileToggle.addEventListener('click', () => {
        closeSearch();
        const open = navigation.classList.toggle('is-open');
        mobileToggle.setAttribute('aria-expanded', String(open));
        mobileToggle.setAttribute('aria-label', open ? 'Đóng menu' : 'Mở menu');
    });

    function closeNavigation() {
        navigation.classList.remove('is-open');
        mobileToggle.setAttribute('aria-expanded', 'false');
        mobileToggle.setAttribute('aria-label', 'Mở menu');
        closeMenus();
    }

    navigation.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeNavigation));
    document.addEventListener('click', (event) => {
        if (!event.target.closest('.nav-search')) closeSearch();
        if (!event.target.closest('.dropdown')) closeMenus();
        if (!event.target.closest('.navbar')) closeNavigation();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (!searchPanel.hidden) {
                event.preventDefault();
                closeSearch(true);
                return;
            }
            const activeToggle = document.activeElement.closest('.dropdown')?.querySelector('.dropdown-toggle');
            closeNavigation();
            if (activeToggle) activeToggle.focus();
        }
    });

});

/* ===== TRANG CHỦ ===== */

/* Chỉ xử lý giao diện. Chưa có tìm tour, đăng nhập hoặc kết nối dữ liệu. */
document.addEventListener('DOMContentLoaded', () => {
    if (!document.querySelector('.hero')) return;
    // 1. BANNER: đổi sau 7 giây; thao tác tay sẽ đặt lại thời gian chờ.
    const hero = document.querySelector('.hero');
    const slides = [...document.querySelectorAll('.hero-slide')];
    const dots = [...document.querySelectorAll('.hero-dot')];
    const pauseButton = document.querySelector('.hero-pause');
    const counter = document.querySelector('.hero-counter');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const SLIDE_INTERVAL = 7000; // 7.000 mili giây = 7 giây.
    let currentSlide = 0;
    let timer = null;
    let paused = reducedMotion.matches;
    let hovering = false;
    let focused = false;

    function showSlide(index) {
        currentSlide = (index + slides.length) % slides.length;
        slides.forEach((slide, i) => {
            const active = i === currentSlide;
            slide.classList.toggle('is-active', active);
            slide.setAttribute('aria-hidden', String(!active));
            slide.inert = !active;
            dots[i].classList.toggle('is-active', active);
            dots[i].setAttribute('aria-current', String(active));
        });
        if (counter) {
            counter.textContent = `${String(currentSlide + 1).padStart(2, '0')} / ${String(slides.length).padStart(2, '0')}`;
        }
    }

    function restartTimer() {
        clearInterval(timer);
        if (!paused && !hovering && !focused && !document.hidden) {
            timer = setInterval(() => showSlide(currentSlide + 1), SLIDE_INTERVAL);
        }
    }

    function updatePauseButton() {
        if (!pauseButton) return;
        pauseButton.setAttribute('aria-pressed', String(paused));
        pauseButton.setAttribute('aria-label', paused ? 'Tiếp tục chuyển banner' : 'Tạm dừng chuyển banner');
        pauseButton.textContent = paused ? '▷' : 'Ⅱ';
    }

    document.querySelector('.hero-prev').addEventListener('click', () => {
        showSlide(currentSlide - 1);
        restartTimer();
    });
    document.querySelector('.hero-next').addEventListener('click', () => {
        showSlide(currentSlide + 1);
        restartTimer();
    });
    dots.forEach((dot, index) => dot.addEventListener('click', () => {
        showSlide(index);
        restartTimer();
    }));
    pauseButton?.addEventListener('click', () => {
        paused = !paused;
        updatePauseButton();
        restartTimer();
    });
    hero.addEventListener('mouseenter', () => { hovering = true; restartTimer(); });
    hero.addEventListener('mouseleave', () => { hovering = false; restartTimer(); });
    hero.addEventListener('focusin', () => { focused = true; restartTimer(); });
    hero.addEventListener('focusout', (event) => { focused = hero.contains(event.relatedTarget); restartTimer(); });
    document.addEventListener('visibilitychange', restartTimer);
    reducedMotion.addEventListener('change', (event) => {
        paused = event.matches;
        updatePauseButton();
        restartTimer();
    });
    showSlide(0);
    updatePauseButton();
    restartTimer();

    // 3. ĐIỂM ĐẾN: chuyển hai nhóm đã được Controller lấy từ database.
    const tabs = [...document.querySelectorAll('.destination-tab')];

    function selectRegion(region) {
        tabs.forEach((tab) => {
            const active = tab.dataset.region === region;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', String(active));
            tab.tabIndex = active ? 0 : -1;
        });
        document.querySelectorAll('[data-region-panel]').forEach((panel) => {
            panel.hidden = panel.dataset.regionPanel !== region;
        });
    }

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => selectRegion(tab.dataset.region));
        tab.addEventListener('keydown', (event) => {
            if (['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) {
                event.preventDefault();
                let nextIndex = (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
                if (event.key === 'Home') nextIndex = 0;
                if (event.key === 'End') nextIndex = tabs.length - 1;
                selectRegion(tabs[nextIndex].dataset.region);
                tabs[nextIndex].focus();
            }
        });
    });
    document.querySelectorAll('[data-destination-link]').forEach((link) => {
        link.addEventListener('click', () => selectRegion(link.dataset.destinationLink));
    });
    if (window.location.hash === '#diem-den-quoc-te') selectRegion('international');

    // 4. NHU CẦU: chọn nhiều giá trị, chỉ cập nhật phần chữ trên giao diện.
    const needsPicker = document.querySelector('.search-needs');
    const needsCheckboxes = [...needsPicker.querySelectorAll('input[type="checkbox"]')];
    const needsValue = needsPicker.querySelector('.needs-value');

    function updateNeedsSummary() {
        const selected = needsCheckboxes.filter((checkbox) => checkbox.checked);
        if (selected.length === 0) {
            needsValue.textContent = 'Tất cả nhu cầu';
        } else if (selected.length === 1) {
            needsValue.textContent = selected[0].closest('label').querySelector('span').textContent;
        } else {
            needsValue.textContent = `Đã chọn ${selected.length} nhu cầu`;
        }
    }

    needsCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', updateNeedsSummary));
    document.addEventListener('click', (event) => {
        if (!needsPicker.contains(event.target)) needsPicker.open = false;
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && needsPicker.open) {
            event.preventDefault();
            needsPicker.open = false;
            needsPicker.querySelector('summary').focus();
        }
    });
    needsPicker.addEventListener('focusout', (event) => {
        if (!needsPicker.contains(event.relatedTarget)) needsPicker.open = false;
    });
    updateNeedsSummary();

    // Form GET gửi điều kiện tìm kiếm sang danh sách tour, hỗ trợ cả phím Enter.
});

/* ===== DANH SÁCH TOUR ===== */

/* Lọc/sắp xếp và phân trang trên dữ liệu database do Controller render. */
document.addEventListener('DOMContentLoaded', () => {
    if (!document.querySelector('#catalog-grid')) return;
    const PAGE_SIZE = 12;
    const root = document.querySelector('[data-catalog-type]');
    const form = document.querySelector('#catalog-filters');
    const grid = document.querySelector('#catalog-grid');
    const cards = [...grid.querySelectorAll('.catalog-card')];
    const schedulesByCard = new Map(cards.map(card => [card, JSON.parse(card.dataset.schedules)]));
    const keywordInput = document.querySelector('#catalog-keyword');
    const sortInput = document.querySelector('#catalog-sort');
    const pagination = document.querySelector('#catalog-pagination');
    const needsPicker = document.querySelector('.catalog-needs');
    const needsInputs = [...needsPicker.querySelectorAll('input[type="checkbox"]')];
    let currentPage = 1;

    // Bỏ dấu để có thể tìm “Da Nang” khi tên tour là “Đà Nẵng”.
    const normalize = (value) => value.toLocaleLowerCase('vi').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd').trim();

    function readCriteria() {
        const adults = Math.max(1, parseInt(form.elements.adults?.value, 10) || 1);
        const children = Math.max(0, parseInt(form.elements.children?.value, 10) || 0);
        const budget = Number(form.elements.budget?.value || 0);
        const hasCustomGroup = budget > 0 || adults > 1 || children > 0;
        return {
            region: form.elements.region.value,
            destination: Number(form.elements.destination.value),
            budget: budget,
            adults: adults,
            children: children,
            groupSearch: hasCustomGroup || Boolean(form.elements.adults && !form.elements.adults.disabled),
            guests: adults + children,
            keyword: normalize(keywordInput.value),
            origin: form.elements.origin.value,
            date: form.elements.date.value,
            price: form.elements.price.value,
            needs: needsInputs.filter((input) => input.checked).map((input) => input.value),
        };
    }

    let criteria = readCriteria();

    function matches(card) {
        const data = card.dataset;
        if (criteria.region && data.region !== criteria.region) return false;
        if (criteria.destination && Number(data.destinationId) !== criteria.destination) return false;
        if (criteria.keyword && !normalize(`${data.name} ${data.destination}`).includes(criteria.keyword)) return false;
        // Ngày, nơi khởi hành và mức giá phải cùng thuộc một lịch phù hợp.
        const schedules = schedulesByCard.get(card).filter(schedule => {
            if (criteria.groupSearch && criteria.guests > Number(schedule.capacity)) return false;
            if (criteria.origin && schedule.origin !== criteria.origin) return false;
            if (criteria.date && schedule.date !== criteria.date) return false;
            if (criteria.budget > 0 && schedule.group_total > criteria.budget) return false;
            if (criteria.price) {
                const [minimum, maximum] = criteria.price.split('-');
                if (schedule.adult_price < Number(minimum) || (maximum && schedule.adult_price >= Number(maximum))) return false;
            }
            return true;
        });
        if ((criteria.destination || criteria.groupSearch || criteria.origin || criteria.date || criteria.price || criteria.budget > 0) && schedules.length === 0) return false;
        // Chọn nhiều nhu cầu: tour phù hợp ít nhất một mục được chọn.
        if (criteria.needs.length && !criteria.needs.some((need) => data.needs.split(',').includes(need))) return false;
        const rate = criteria.guests >= 10 ? 8 : (criteria.guests >= 5 ? 5 : 0);
        schedules.forEach(schedule => {
            const subtotal = criteria.adults * schedule.adult_price + criteria.children * schedule.child_price;
            schedule.group_total = subtotal - Math.round(subtotal * rate / 100);
        });

        const bestSchedule = schedules.length ? schedules[0] : null;
        data.sortPrice = bestSchedule ? String(criteria.groupSearch ? bestSchedule.group_total : bestSchedule.adult_price) : '';
        const priceElement = card.querySelector('[data-card-price]');
        const priceLabel = card.querySelector('.card-price-label');

        if (priceElement && data.sortPrice !== '') {
            const amount = document.createElement('strong');
            amount.textContent = new Intl.NumberFormat('vi-VN').format(Number(data.sortPrice));
            const currency = document.createElement('span');
            currency.textContent = ' ₫';
            if (criteria.groupSearch && rate > 0) {
                const discountPill = document.createElement('small');
                discountPill.className = 'card-discount-pill';
                discountPill.textContent = ` -${rate}%`;
                priceElement.replaceChildren(amount, currency, discountPill);
            } else {
                priceElement.replaceChildren(amount, currency);
            }
            if (priceLabel) {
                priceLabel.textContent = criteria.groupSearch ? `Tổng nhóm (${criteria.guests} khách):` : 'Giá từ / người lớn';
            }
        } else if (priceElement) {
            priceElement.textContent = 'Đang cập nhật';
        }
        const origins = [...new Set(schedules.map(schedule => schedule.origin))];
        card.querySelector('[data-card-origin]').textContent = origins.length ? `Khởi hành từ ${origins.join(', ')}` : 'Chưa có lịch khởi hành';
        return true;
    }

    function buildPageButton(label, page, disabled = false, current = false) {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = label;
        button.dataset.page = String(page);
        button.disabled = disabled;
        button.setAttribute('aria-label', label === '‹' ? 'Trang trước' : label === '›' ? 'Trang tiếp theo' : `Trang ${page}`);
        if (current) button.setAttribute('aria-current', 'page');
        return button;
    }

    function renderResults() {
        const filtered = cards.filter(matches);
        filtered.sort((a, b) => {
            let difference = 0;
            if (sortInput.value === 'smart') {
                difference = Number(b.dataset.smartScore || 0) - Number(a.dataset.smartScore || 0);
            }
            if (sortInput.value.startsWith('price-')) {
                if (a.dataset.sortPrice === '' && b.dataset.sortPrice !== '') return 1;
                if (b.dataset.sortPrice === '' && a.dataset.sortPrice !== '') return -1;
                difference = sortInput.value === 'price-asc'
                    ? Number(a.dataset.sortPrice) - Number(b.dataset.sortPrice)
                    : Number(b.dataset.sortPrice) - Number(a.dataset.sortPrice);
            }
            if (sortInput.value === 'duration') difference = Number(a.dataset.days) - Number(b.dataset.days);
            return difference || Number(a.dataset.order) - Number(b.dataset.order);
        });
        const pageCount = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
        currentPage = Math.max(1, Math.min(currentPage, pageCount));
        const start = (currentPage - 1) * PAGE_SIZE;
        const shown = filtered.slice(start, start + PAGE_SIZE);
        cards.forEach((card) => { card.hidden = true; });
        const fragment = document.createDocumentFragment();
        shown.forEach((card) => { card.hidden = false; fragment.append(card); });
        grid.append(fragment);
        grid.hidden = filtered.length === 0;
        document.querySelector('#catalog-empty').hidden = filtered.length !== 0;

        const count = document.createElement('strong');
        count.textContent = String(filtered.length);
        document.querySelector('#catalog-result-description').replaceChildren(count, ` tour được tìm thấy · Trang ${currentPage}/${pageCount}`);

        pagination.replaceChildren();
        pagination.hidden = filtered.length === 0;
        pagination.append(buildPageButton('‹', currentPage - 1, currentPage === 1));
        for (let page = 1; page <= pageCount; page++) pagination.append(buildPageButton(String(page), page, false, page === currentPage));
        pagination.append(buildPageButton('›', currentPage + 1, currentPage === pageCount));
        const summary = [];
        const destination = cards.find(card => Number(card.dataset.destinationId) === criteria.destination);
        if (destination) summary.push(destination.dataset.destination);
        if (form.elements.adults && !form.elements.adults.disabled) {
            summary.push(`${form.elements.adults.value} người lớn, ${form.elements.children.value} trẻ em`);
        }
        if (criteria.budget > 0) summary.push(`Ngân sách nhóm: ${new Intl.NumberFormat('vi-VN').format(criteria.budget)} ₫ (sau ưu đãi)`);
        const summaryElement = document.querySelector('.catalog-search-summary');
        summaryElement.textContent = summary.join(' · ');
        summaryElement.hidden = summary.length === 0;
    }

    function searchUrl(type = root.dataset.catalogType) {
        const url = new URL(window.location.href);
        url.search = '';
        url.searchParams.set('page', 'tours');
        url.searchParams.set('type', type);
        for (const [name, value] of new FormData(form)) {
            if (value === '' || ((name === 'destination' || name === 'budget') && Number(value) === 0)) continue;
            url.searchParams.append(name, value);
        }
        if (keywordInput.value.trim()) url.searchParams.set('q', keywordInput.value.trim());
        return url;
    }

    function applyFilters(event) {
        event.preventDefault();
        const regionOption = form.elements.region.selectedOptions[0];
        const selectedType = regionOption.dataset.regionType;
        if (selectedType && root.dataset.catalogType !== 'all' && selectedType !== root.dataset.catalogType) {
            form.elements.destination.value = '0';
            window.location.assign(searchUrl(selectedType).href);
            return;
        }
        criteria = readCriteria();
        currentPage = 1;
        needsPicker.open = false;
        renderResults();
        window.history.replaceState(null, '', searchUrl());
    }

    form.addEventListener('submit', applyFilters);
    form.elements.region.addEventListener('change', event => {
        form.elements.destination.value = '0';
        applyFilters(event);
    });
    document.querySelector('.catalog-quick-search').addEventListener('submit', applyFilters);
    keywordInput.addEventListener('search', (event) => {
        if (keywordInput.value === '') applyFilters(event);
    });
    sortInput.addEventListener('change', () => { currentPage = 1; renderResults(); });
    pagination.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-page]');
        if (!button || button.disabled) return;
        currentPage = Number(button.dataset.page);
        renderResults();
        document.querySelector('#catalog-results').scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
    });

    function updateNeedsText() {
        const checked = needsInputs.filter((input) => input.checked);
        document.querySelector('#catalog-needs-value').textContent = checked.length === 0 ? 'Tất cả nhu cầu' : checked.length === 1 ? checked[0].closest('label').querySelector('span').textContent : `Đã chọn ${checked.length} nhu cầu`;
    }
    needsInputs.forEach((input) => input.addEventListener('change', updateNeedsText));
    document.addEventListener('click', (event) => {
        if (!needsPicker.contains(event.target)) needsPicker.open = false;
    });
    // Không đóng ở focusout: thu gọn ở mousedown sẽ làm nút Áp dụng
    // dịch chuyển trước mouseup, khiến trình duyệt không phát sinh submit.
    // Đóng bằng click bên ngoài, submit hoặc Escape thay thế.
    needsPicker.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            needsPicker.open = false;
            needsPicker.querySelector('summary').focus();
        }
    });

    document.querySelectorAll('[data-catalog-reset]').forEach((button) => button.addEventListener('click', () => {
        form.reset();
        keywordInput.value = '';
        sortInput.value = 'default';
        form.elements.region.value = '';
        form.elements.destination.value = '0';
        form.elements.origin.value = '';
        form.elements.date.value = '';
        form.elements.price.value = '';
        needsInputs.forEach(input => { input.checked = false; });
        if (form.elements.adults) form.elements.adults.value = '1';
        if (form.elements.children) form.elements.children.value = '0';
        if (form.elements.budget) form.elements.budget.value = '';
        updateCatalogGuestTier();
        currentPage = 1;
        criteria = readCriteria();
        needsPicker.open = false;
        updateNeedsText();
        document.querySelectorAll('[data-tour-type]').forEach((link) => {
            link.classList.toggle('is-selected', link.dataset.tourType === root.dataset.catalogType && link.dataset.tourRegion === '');
        });
        window.history.replaceState(null, '', searchUrl());
        renderResults();
    }));

    function updateCatalogGuestTier() {
        const adults = Math.max(1, parseInt(form.elements.adults?.value, 10) || 1);
        const children = Math.max(0, parseInt(form.elements.children?.value, 10) || 0);
        const total = adults + children;
        const rate = total >= 10 ? 8 : (total >= 5 ? 5 : 0);
        const countEl = form.querySelector('[data-guest-count]');
        const labelEl = form.querySelector('[data-discount-label]');
        if (countEl) countEl.textContent = `${total} khách`;
        if (labelEl) {
            labelEl.textContent = rate > 0 ? `Giảm ${rate}%` : 'Đạt 5 khách giảm 5%';
        }
    }
    if (form.elements.adults) form.elements.adults.addEventListener('input', () => { updateCatalogGuestTier(); renderResults(); });
    if (form.elements.children) form.elements.children.addEventListener('input', () => { updateCatalogGuestTier(); renderResults(); });
    if (form.elements.budget) form.elements.budget.addEventListener('input', () => { renderResults(); });

    updateCatalogGuestTier();
    updateNeedsText();
    renderResults();
});

/* ===== CHI TIẾT TOUR ===== */

// Tạm tính theo giá lịch đi từ database; tích hợp cập nhật API thời tiết khi đổi lịch.
document.addEventListener('DOMContentLoaded', () => {
    const preview = document.querySelector('[data-booking-preview]');
    if (preview) {
        let adultPrice = Number(preview.dataset.adultPrice);
        let childPrice = Number(preview.dataset.childPrice);
        const adultRow = preview.querySelector('[data-quantity="adult"]');
        const childRow = preview.querySelector('[data-quantity="child"]');
        let adults = 1;
        let children = 0;
        let lastSelectedDate = preview.querySelector('input[name="departure"]:checked')?.dataset.date || '';
        const money = value => `${new Intl.NumberFormat('vi-VN').format(value)} ₫`;

        function updateWeatherForecast(startDate, origin) {
            if (!startDate) return;
            const tourId = document.body.dataset.tourId;
            if (!tourId) return;

            const weatherOverview = document.querySelector('[data-weather-overview]');
            if (weatherOverview) {
                const originEl = weatherOverview.querySelector('[data-weather-origin]');
                if (originEl && origin) originEl.textContent = origin;
                const sourceText = weatherOverview.querySelector('[data-weather-source-text]');
                if (sourceText) sourceText.textContent = 'Đang đồng bộ API thời tiết...';
            }

            document.querySelectorAll('[data-weather-card]').forEach(card => card.classList.add('is-loading'));

            fetch(`index.php?page=tour-detail&action=weather&id=${encodeURIComponent(tourId)}&date=${encodeURIComponent(startDate)}`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.success && Array.isArray(data.weather)) {
                        if (weatherOverview) {
                            const startDateEl = weatherOverview.querySelector('[data-weather-start-date]');
                            if (startDateEl && data.departure_formatted) {
                                startDateEl.textContent = data.departure_formatted;
                            }
                            const sourceText = weatherOverview.querySelector('[data-weather-source-text]');
                            if (sourceText) sourceText.textContent = 'API Open-Meteo & Khí tượng du lịch';
                        }

                        data.weather.forEach(w => {
                            const dayNum = w.day;
                            const badge = document.querySelector(`[data-weather-badge="${dayNum}"]`);
                            const card = document.querySelector(`[data-weather-card="${dayNum}"]`);

                            if (badge) {
                                badge.classList.remove('is-hidden');
                                const iconEl = badge.querySelector('[data-weather-icon]');
                                const tempEl = badge.querySelector('[data-weather-temp]');
                                const tagEl = badge.querySelector('[data-weather-tag]');
                                if (iconEl) iconEl.textContent = w.icon;
                                if (tempEl) tempEl.textContent = w.temp_range;
                                if (tagEl) tagEl.textContent = w.badge;
                            }

                            if (card) {
                                card.classList.remove('is-hidden');
                                card.classList.remove('is-loading');

                                const dateEl = card.querySelector('[data-weather-date]');
                                const locEl = card.querySelector('[data-weather-loc]');
                                const rainEl = card.querySelector('[data-weather-rain]');
                                const statusEl = card.querySelector('[data-weather-status]');
                                const adviceEl = card.querySelector('[data-weather-advice]');
                                const adviceBox = card.querySelector('[data-weather-advice-box]');

                                if (dateEl) dateEl.textContent = w.date_formatted;
                                if (locEl) locEl.textContent = w.location;
                                if (rainEl) rainEl.textContent = `${w.rain}%`;
                                if (statusEl) statusEl.textContent = w.status;
                                if (adviceEl) adviceEl.textContent = w.advice;
                                if (adviceBox) {
                                    adviceBox.classList.toggle('is-hidden', !w.advice);
                                }

                                card.classList.add('weather-card-updated');
                                setTimeout(() => card.classList.remove('weather-card-updated'), 500);
                            }
                        });
                    }
                })
                .catch(err => {
                    console.warn('Lỗi đồng bộ thời tiết:', err);
                    document.querySelectorAll('[data-weather-card]').forEach(card => card.classList.remove('is-loading'));
                });
        }

        function renderEstimate() {
            const selected = preview.querySelector('input[name="departure"]:checked');
            if (selected) {
                adultPrice = Number(selected.dataset.adultPrice);
                childPrice = Number(selected.dataset.childPrice);
                preview.querySelector('[data-schedule-origin]').textContent = selected.dataset.origin;
                const seatsEl = preview.querySelector('[data-schedule-seats]');
                if (seatsEl && selected.dataset.seatsLeft !== undefined) seatsEl.textContent = selected.dataset.seatsLeft;
                const bookLink = preview.querySelector('[data-book-link]');
                if (bookLink && selected.value) bookLink.href = bookLink.href.replace(/schedule=\d*/, `schedule=${selected.value}`);
                if (selected.dataset.date && selected.dataset.date !== lastSelectedDate) {
                    lastSelectedDate = selected.dataset.date;
                    updateWeatherForecast(selected.dataset.date, selected.dataset.origin);
                }
            }
            adultRow.querySelector('[data-unit-price]').textContent = money(adultPrice);
            childRow.querySelector('[data-unit-price]').textContent = money(childPrice);
            const adultTotal = adults * adultPrice;
            const childTotal = children * childPrice;
            const subtotal = adultTotal + childTotal;
            const guests = adults + children;
            const discountRate = guests >= 10 ? 8 : guests >= 5 ? 5 : 0;
            const discount = Math.round(subtotal * discountRate / 100);

            adultRow.querySelector('[data-count]').textContent = adults;
            childRow.querySelector('[data-count]').textContent = children;
            adultRow.querySelector('[data-step="-1"]').disabled = adults <= 1;
            childRow.querySelector('[data-step="-1"]').disabled = children <= 0;
            adultRow.querySelector('[data-step="1"]').disabled = adults >= 99;
            childRow.querySelector('[data-step="1"]').disabled = children >= 99;
            preview.querySelector('[data-adult-count]').textContent = adults;
            preview.querySelector('[data-adult-total]').textContent = money(adultTotal);
            preview.querySelector('[data-child-count]').textContent = children;
            preview.querySelector('[data-child-total]').textContent = money(childTotal);
            preview.querySelector('[data-child-line]').hidden = children === 0;
            preview.querySelector('[data-subtotal]').textContent = money(subtotal);
            preview.querySelector('[data-discount-line]').hidden = discountRate === 0;
            preview.querySelector('[data-discount-rate]').textContent = `(${discountRate}%)`;
            preview.querySelector('[data-discount-amount]').textContent = `−${money(discount)}`;
            preview.querySelector('[data-total]').textContent = money(subtotal - discount);
        }

        preview.querySelectorAll('[data-step]').forEach(button => button.addEventListener('click', () => {
            const step = Number(button.dataset.step);
            if (button.closest('[data-quantity]').dataset.quantity === 'adult') adults = Math.max(1, Math.min(99, adults + step));
            else children = Math.max(0, Math.min(99, children + step));
            renderEstimate();
        }));
        preview.querySelectorAll('input[name="departure"]').forEach(input => input.addEventListener('change', renderEstimate));
        renderEstimate();
    }

    // Chuyển nhóm 3 tour liên quan.
    const cards = [...document.querySelectorAll('[data-related-card]')];
    const previous = document.querySelector('[data-related-prev]');
    const next = document.querySelector('[data-related-next]');
    if (!cards.length || !previous || !next) return;
    let start = 0;
    function show() {
        cards.forEach((card, index) => { card.hidden = ![0, 1, 2].some(offset => (start + offset) % cards.length === index); });
    }
    previous.addEventListener('click', () => { start = (start - 1 + cards.length) % cards.length; show(); });
    next.addEventListener('click', () => { start = (start + 1) % cards.length; show(); });
    show();
});

/* ===== KHUNG QUẢN TRỊ: THU GỌN THANH BÊN ===== */
document.addEventListener('DOMContentLoaded', () => {
    const layout = document.querySelector('[data-admin-layout]');
    const toggle = document.querySelector('[data-admin-toggle]');
    if (!layout || !toggle) return;
    const backdrop = layout.querySelector('[data-admin-accounts-backdrop]');
    const accountMobile = backdrop ? matchMedia('(max-width: 700px)') : null;
    function setSidebarCollapsed(collapsed) {
        layout.classList.toggle('is-sidebar-collapsed', collapsed);
        layout.querySelector('.admin-sidebar').inert = collapsed;
        toggle.setAttribute('aria-expanded', String(!collapsed));
        toggle.setAttribute('aria-label', collapsed ? 'Mở thanh bên' : 'Thu gọn thanh bên');
        if (backdrop) backdrop.hidden = collapsed || !accountMobile.matches;
    }
    toggle.addEventListener('click', () => setSidebarCollapsed(!layout.classList.contains('is-sidebar-collapsed')));
    if (accountMobile) {
        setSidebarCollapsed(accountMobile.matches);
        accountMobile.addEventListener('change', event => setSidebarCollapsed(event.matches));
        backdrop.addEventListener('click', () => { setSidebarCollapsed(true); toggle.focus(); });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && accountMobile.matches && !backdrop.hidden) {
                setSidebarCollapsed(true);
                toggle.focus();
            }
        });
    }
});

/* ===== QUẢN TRỊ: FORM HỒ SƠ VÀ ĐỔI MẬT KHẨU ===== */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-admin-password-form], [data-admin-profile-form]');
    if (!form) return;
    const message = form.querySelector('[data-admin-preview-message]');
    // Xem trước cục bộ; ảnh chỉ được gửi khi nhấn Lưu thay đổi.
    const avatarInput = form.querySelector('[data-admin-avatar-input]');
    if (avatarInput) {
        const preview = document.querySelector('[data-admin-avatar-preview]');
        const fallback = document.querySelector('[data-admin-avatar-default]');
        const error = document.getElementById('profile-avatar-error');
        const savedAvatarUrl = preview.getAttribute('src') || '';
        let previewUrl = '';
        function clearPreview() {
            if (previewUrl) URL.revokeObjectURL(previewUrl);
            previewUrl = '';
            preview.hidden = savedAvatarUrl === '';
            fallback.toggleAttribute('hidden', savedAvatarUrl !== '');
            if (savedAvatarUrl) preview.src = savedAvatarUrl;
            else preview.removeAttribute('src');
        }
        function showError(text) {
            clearPreview();
            avatarInput.value = '';
            error.textContent = text;
            error.hidden = false;
        }
        avatarInput.addEventListener('change', () => {
            error.hidden = true;
            const file = avatarInput.files[0];
            clearPreview();
            if (!file) return;
            if (!['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)) {
                showError('Vui lòng chọn ảnh JPG, PNG, GIF hoặc WEBP.');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                showError('Ảnh đại diện không vượt quá 5 MB.');
                return;
            }
            const url = URL.createObjectURL(file);
            previewUrl = url;
            preview.onload = () => {
                if (previewUrl !== url) return;
                preview.hidden = false;
                fallback.setAttribute('hidden', '');
            };
            preview.onerror = () => {
                if (previewUrl === url) showError('Không đọc được ảnh này. Vui lòng chọn ảnh khác.');
            };
            preview.src = url;
        });
        window.addEventListener('pagehide', clearPreview);
    }
    form.querySelectorAll('[data-admin-password-toggle]').forEach(button => {
        const input = document.getElementById(button.getAttribute('aria-controls'));
        const label = button.getAttribute('aria-label').replace(/^Hiện /, '');
        button.addEventListener('click', () => {
            const reveal = input.type === 'password';
            input.type = reveal ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(reveal));
            button.setAttribute('aria-label', `${reveal ? 'Ẩn' : 'Hiện'} ${label}`);
        });
    });
    form.addEventListener('input', () => {
        message.hidden = true;
        form.querySelectorAll('input').forEach(input => input.setCustomValidity(''));
    });
    form.addEventListener('submit', event => {
        if (form.hasAttribute('data-admin-profile-form')) {
            const name = form.elements.ho_ten;
            const phone = form.elements.so_dien_thoai;
            if (name.value.trim().length < 2) name.setCustomValidity('Họ và tên cần ít nhất 2 ký tự.');
            if (!/^\+?[0-9]{9,15}$/.test(phone.value.replace(/[\s().-]/g, ''))) phone.setCustomValidity('Số điện thoại cần từ 9 đến 15 chữ số.');
        } else {
            const current = form.elements.mat_khau_hien_tai;
            const next = form.elements.mat_khau_moi;
            const confirm = form.elements.xac_nhan_mat_khau;
            if (next.value.length < 8 || new TextEncoder().encode(next.value).length > 72) next.setCustomValidity('Mật khẩu mới cần ít nhất 8 ký tự và tối đa 72 byte.');
            else if (next.value === current.value) next.setCustomValidity('Mật khẩu mới phải khác mật khẩu hiện tại.');
            if (confirm.value !== next.value) confirm.setCustomValidity('Mật khẩu xác nhận chưa trùng khớp.');
        }
        if (!form.reportValidity()) {
            event.preventDefault();
            return;
        }
    });
});

/* ===== QUẢN TRỊ: QUẢN LÝ TÀI KHOẢN ===== */
document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('.admin-accounts');
    if (!root) return;
    root.querySelectorAll('[data-account-confirm]').forEach(form => {
        form.addEventListener('submit', event => {
            if (!window.confirm(form.dataset.accountConfirm)) event.preventDefault();
        });
    });
    root.querySelectorAll('[data-account-password-toggle]').forEach(button => {
        const input = document.getElementById(button.getAttribute('aria-controls'));
        const label = button.getAttribute('aria-label').replace(/^Hiện /, '');
        button.addEventListener('click', () => {
            const reveal = input.type === 'password';
            input.type = reveal ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(reveal));
            button.setAttribute('aria-label', `${reveal ? 'Ẩn' : 'Hiện'} ${label}`);
        });
    });
});

/* ===== TÀI KHOẢN: KIỂM TRA FORM TRƯỚC KHI GỬI ===== */
// Menu tài khoản quản trị: chỉ xử lý đóng/mở giao diện.
document.addEventListener('DOMContentLoaded', () => {
    const account = document.querySelector('[data-admin-account]');
    if (!account) return;
    const toggle = account.querySelector('[data-admin-account-toggle]');
    const panel = document.getElementById(toggle.getAttribute('aria-controls'));
    function setOpen(open) {
        toggle.setAttribute('aria-expanded', String(open));
        panel.hidden = !open;
    }
    toggle.addEventListener('click', () => setOpen(panel.hidden));
    document.addEventListener('click', event => {
        if (!account.contains(event.target)) setOpen(false);
    });
    account.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !panel.hidden) {
            setOpen(false);
            toggle.focus();
        }
    });
    account.addEventListener('focusout', event => {
        if (!account.contains(event.relatedTarget)) setOpen(false);
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-auth-form]');
    if (!form) return;
    const register = form.dataset.authMode === 'register';
    const fields = [...form.querySelectorAll('input:not([type="hidden"])')];
    const touched = new Set();
    const message = form.querySelector('[data-auth-message]');
    let submitted = false;

    function fieldMessage(input) {
        const value = input.value;
        switch (input.name) {
            case 'ho_ten':
                if (!value.trim()) return 'Vui lòng nhập họ và tên.';
                if (value.trim().length < 2) return 'Họ và tên cần ít nhất 2 ký tự.';
                return '';
            case 'email':
                if (!value.trim()) return 'Vui lòng nhập email.';
                if (input.validity.typeMismatch || /\s/.test(value.trim())) return 'Vui lòng nhập email đúng định dạng.';
                return '';
            case 'so_dien_thoai':
                if (!value.trim()) return 'Vui lòng nhập số điện thoại.';
                if (!/^\+?[0-9]{9,15}$/.test(value.replace(/[\s().-]/g, ''))) return 'Số điện thoại cần có từ 9 đến 15 chữ số.';
                return '';
            case 'mat_khau':
                if (!value) return 'Vui lòng nhập mật khẩu.';
                if (register && value.length < 8) return 'Mật khẩu cần ít nhất 8 ký tự.';
                if (register && new TextEncoder().encode(value).length > 72) return 'Mật khẩu không vượt quá 72 byte.';
                return '';
            case 'xac_nhan_mat_khau':
                if (!value) return 'Vui lòng nhập lại mật khẩu.';
                if (value !== form.elements.mat_khau.value) return 'Mật khẩu xác nhận chưa trùng khớp.';
                return '';
            default:
                return '';
        }
    }

    function validate(input) {
        const error = fieldMessage(input);
        const output = document.getElementById(`${input.id}-error`);
        input.setAttribute('aria-invalid', String(Boolean(error)));
        output.textContent = error;
        output.hidden = error === '';
        return error === '';
    }

    fields.forEach(input => {
        input.addEventListener('blur', () => { touched.add(input); validate(input); });
        input.addEventListener('input', () => {
            message.hidden = true;
            if (submitted || touched.has(input)) validate(input);
            if (input.name === 'mat_khau' && register && touched.has(form.elements.xac_nhan_mat_khau)) {
                validate(form.elements.xac_nhan_mat_khau);
            }
        });
    });

    form.querySelectorAll('[data-password-toggle]').forEach(button => {
        const input = document.getElementById(button.getAttribute('aria-controls'));
        const fieldName = input.name === 'xac_nhan_mat_khau' ? 'xác nhận mật khẩu' : 'mật khẩu';
        button.addEventListener('click', () => {
            const reveal = input.type === 'password';
            input.type = reveal ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(reveal));
            button.setAttribute('aria-label', `${reveal ? 'Ẩn' : 'Hiện'} ${fieldName}`);
        });
    });

    form.addEventListener('submit', event => {
        submitted = true;
        const invalid = fields.filter(input => !validate(input));
        if (invalid.length) {
            event.preventDefault();
            message.hidden = false;
            message.textContent = 'Vui lòng kiểm tra các thông tin được đánh dấu bên dưới.';
            invalid[0].focus();
        }
    });
});

/* ===== ĐẶT TOUR: THÊM NGƯỜI THAM GIA VÀ ĐỒNG BỘ TẠM TÍNH ===== */
// Tạm tính chỉ để khách hình dung; máy chủ kiểm tra lại số chỗ và đơn giá khi lưu đơn.
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-booking-form]');
    const money = value => `${new Intl.NumberFormat('vi-VN').format(value)} ₫`;

    // Trang đơn đặt: xác nhận trước khi hủy đơn.
    document.querySelectorAll('[data-confirm]').forEach(element => {
        element.addEventListener('submit', event => {
            if (!window.confirm(element.dataset.confirm)) event.preventDefault();
        });
    });

    if (!form) return;
    const list = form.querySelector('[data-participant-list]');
    const summary = document.querySelector('[data-booking-summary]');
    if (!list) return;
    const rows = () => [...list.querySelectorAll('[data-participant]')];
    const typeOf = row => (row.dataset.type === 'tre_em' ? 'tre_em' : 'nguoi_lon');

    function renumber() {
        const counts = { nguoi_lon: 0, tre_em: 0 };
        rows().forEach(row => {
            const type = typeOf(row);
            counts[type] += 1;
            const label = row.querySelector('[data-participant-label]');
            if (label) label.textContent = `${type === 'tre_em' ? 'Trẻ em' : 'Người lớn'} ${counts[type]}`;
            const remove = row.querySelector('[data-remove-participant]');
            if (remove) remove.disabled = type !== 'tre_em' && counts.nguoi_lon <= 1;
        });
        return counts;
    }

    function renderSummary() {
        if (!summary) return;
        const schedule = form.querySelector('input[name="id_lich_khoi_hanh"]:checked');
        const adultPrice = Number((schedule && schedule.dataset.adultPrice) || form.dataset.adultPrice || 0);
        const childPrice = Number((schedule && schedule.dataset.childPrice) || form.dataset.childPrice || 0);
        let adults = 0;
        let children = 0;
        rows().forEach(row => { if (typeOf(row) === 'tre_em') children += 1; else adults += 1; });
        const adultTotal = adults * adultPrice;
        const childTotal = children * childPrice;
        const subtotal = adultTotal + childTotal;
        const guests = adults + children;
        const rate = guests >= 10 ? 8 : guests >= 5 ? 5 : 0;
        const discount = Math.round(subtotal * rate / 100);
        const setText = (selector, value) => {
            const element = summary.querySelector(selector);
            if (element) element.textContent = value;
        };

        setText('[data-summary-adult-count]', adults);
        setText('[data-summary-adult-total]', money(adultTotal));
        setText('[data-summary-child-count]', children);
        setText('[data-summary-child-total]', money(childTotal));
        const childLine = summary.querySelector('[data-summary-child-line]');
        if (childLine) childLine.hidden = children === 0;
        setText('[data-summary-subtotal]', money(subtotal));
        const discountLine = summary.querySelector('[data-summary-discount-line]');
        if (discountLine) discountLine.hidden = rate === 0;
        setText('[data-summary-discount-rate]', `${rate}%`);
        setText('[data-summary-discount-amount]', `−${money(discount)}`);
        setText('[data-summary-total]', money(subtotal - discount));
        const meta = summary.querySelector('[data-summary-schedule]');
        if (meta) {
            meta.textContent = schedule
                ? `Khởi hành ${schedule.dataset.date} · ${schedule.dataset.origin}`
                : 'Chưa chọn lịch khởi hành';
        }
    }

    form.querySelectorAll('[data-add-participant]').forEach(button => {
        button.addEventListener('click', () => {
            const type = button.dataset.addParticipant === 'tre_em' ? 'tre_em' : 'nguoi_lon';
            const template = form.querySelector(`[data-participant-template="${type}"]`);
            if (!template) return;
            list.appendChild(template.content.cloneNode(true));
            renumber();
            renderSummary();
            const added = rows().pop();
            const input = added && added.querySelector('input[name="khach[ho_ten][]"]');
            if (input) input.focus();
        });
    });

    list.addEventListener('click', event => {
        const button = event.target.closest('[data-remove-participant]');
        if (!button) return;
        const row = button.closest('[data-participant]');
        if (!row) return;
        if (typeOf(row) !== 'tre_em' && rows().filter(item => typeOf(item) !== 'tre_em').length <= 1) return;
        row.remove();
        renumber();
        renderSummary();
    });

    form.querySelectorAll('input[name="id_lich_khoi_hanh"]').forEach(input => {
        input.addEventListener('change', renderSummary);
    });

    renumber();
    renderSummary();
});
