/* OnTrip — JavaScript chung, trang chủ, danh sách tour và chi tiết tour. */

/* ===== NAVBAR DÙNG CHUNG ===== */

/* NAVBAR DÙNG CHUNG cho trang chủ và trang danh sách tour. */
document.addEventListener('DOMContentLoaded', () => {
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

    // 3. ĐIỂM ĐẾN: chuyển hai nhóm dữ liệu mẫu, không truy vấn database.
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

    // Ngăn Enter gửi form hoặc tải lại trang khi chưa có xử lý tìm kiếm.
    document.querySelector('.tour-search').addEventListener('submit', (event) => event.preventDefault());
});

/* ===== DANH SÁCH TOUR ===== */

/* TƯƠNG TÁC TRANG TOUR — chỉ lọc/sắp xếp dữ liệu mẫu đã render, chưa gọi API/database. */
document.addEventListener('DOMContentLoaded', () => {
    if (!document.querySelector('#catalog-grid')) return;
    const PAGE_SIZE = 12;
    const root = document.querySelector('[data-catalog-type]');
    const form = document.querySelector('#catalog-filters');
    const grid = document.querySelector('#catalog-grid');
    const cards = [...grid.querySelectorAll('.catalog-card')];
    const keywordInput = document.querySelector('#catalog-keyword');
    const sortInput = document.querySelector('#catalog-sort');
    const pagination = document.querySelector('#catalog-pagination');
    const needsPicker = document.querySelector('.catalog-needs');
    const needsInputs = [...needsPicker.querySelectorAll('input[type="checkbox"]')];
    let currentRegion = root.dataset.region;
    let currentPage = 1;

    // Bỏ dấu để có thể tìm “Da Nang” khi tên tour là “Đà Nẵng”.
    const normalize = (value) => value.toLocaleLowerCase('vi').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd').trim();

    function readCriteria() {
        return {
            region: currentRegion,
            keyword: normalize(keywordInput.value),
            destination: form.elements.destination.value,
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
        if (criteria.destination && data.destination !== criteria.destination) return false;
        if (criteria.origin && data.origin !== criteria.origin) return false;
        if (criteria.keyword && !normalize(`${data.name} ${data.destination}`).includes(criteria.keyword)) return false;
        if (criteria.date && !data.dates.split(',').includes(criteria.date)) return false;
        if (criteria.price) {
            const [minimum, maximum] = criteria.price.split('-');
            const price = Number(data.price);
            if (price < Number(minimum) || (maximum && price >= Number(maximum))) return false;
        }
        // Chọn nhiều nhu cầu: tour phù hợp ít nhất một mục được chọn.
        if (criteria.needs.length && !criteria.needs.some((need) => data.needs.split(',').includes(need))) return false;
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
            if (sortInput.value === 'price-asc') difference = Number(a.dataset.price) - Number(b.dataset.price);
            if (sortInput.value === 'price-desc') difference = Number(b.dataset.price) - Number(a.dataset.price);
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
        document.querySelector('#catalog-range').textContent = `Hiển thị ${filtered.length ? start + 1 : 0}–${start + shown.length} / ${filtered.length} tour`;

        pagination.replaceChildren();
        pagination.hidden = filtered.length === 0;
        pagination.append(buildPageButton('‹', currentPage - 1, currentPage === 1));
        for (let page = 1; page <= pageCount; page++) pagination.append(buildPageButton(String(page), page, false, page === currentPage));
        pagination.append(buildPageButton('›', currentPage + 1, currentPage === pageCount));
    }

    function applyFilters(event) {
        event.preventDefault();
        criteria = readCriteria();
        currentPage = 1;
        needsPicker.open = false;
        renderResults();
    }

    form.addEventListener('submit', applyFilters);
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
    needsPicker.addEventListener('focusout', (event) => {
        if (!needsPicker.contains(event.relatedTarget)) needsPicker.open = false;
    });
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
        currentRegion = '';
        currentPage = 1;
        criteria = readCriteria();
        needsPicker.open = false;
        updateNeedsText();
        document.querySelectorAll('[data-tour-type]').forEach((link) => {
            link.classList.toggle('is-selected', link.dataset.tourType === root.dataset.catalogType && link.dataset.tourRegion === '');
        });
        const url = new URL(window.location.href);
        url.searchParams.delete('region');
        url.searchParams.delete('q');
        window.history.replaceState(null, '', url);
        renderResults();
    }));

    updateNeedsText();
    renderResults();
});

/* ===== CHI TIẾT TOUR ===== */

// Chỉ chuyển nhóm 3 thẻ tour liên quan. Giá/đặt tour chưa có xử lý nghiệp vụ.
document.addEventListener('DOMContentLoaded', () => {
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
