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
        return {
            region: form.elements.region.value,
            destination: Number(form.elements.destination.value),
            budget: Number(form.elements.budget?.value || 0),
            groupSearch: Boolean(form.elements.adults && !form.elements.adults.disabled),
            guests: Number(form.elements.adults?.value || 1) + Number(form.elements.children?.value || 0),
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
        data.sortPrice = schedules.length ? String(Math.min(...schedules.map(schedule => schedule.adult_price))) : '';
        const priceElement = card.querySelector('[data-card-price]');
        if (data.sortPrice !== '') {
            const amount = document.createElement('strong');
            amount.textContent = new Intl.NumberFormat('vi-VN').format(Number(data.sortPrice));
            const currency = document.createElement('span');
            currency.textContent = ' ₫';
            priceElement.replaceChildren(amount, currency);
        } else priceElement.textContent = 'Đang cập nhật';
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
        for (const name of ['adults', 'children', 'budget']) {
            if (form.elements[name]) {
                form.elements[name].value = name === 'adults' ? '1' : '0';
                form.elements[name].disabled = true;
            }
        }
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

    updateNeedsText();
    renderResults();
});

/* ===== CHI TIẾT TOUR ===== */

// Tạm tính theo giá lịch đi từ database; nút đặt tour chưa gửi dữ liệu.
document.addEventListener('DOMContentLoaded', () => {
    const preview = document.querySelector('[data-booking-preview]');
    if (preview) {
        let adultPrice = Number(preview.dataset.adultPrice);
        let childPrice = Number(preview.dataset.childPrice);
        const adultRow = preview.querySelector('[data-quantity="adult"]');
        const childRow = preview.querySelector('[data-quantity="child"]');
        let adults = 1;
        let children = 0;
        const money = value => `${new Intl.NumberFormat('vi-VN').format(value)} ₫`;

        function renderEstimate() {
            const selected = preview.querySelector('input[name="departure"]:checked');
            if (selected) {
                adultPrice = Number(selected.dataset.adultPrice);
                childPrice = Number(selected.dataset.childPrice);
                preview.querySelector('[data-schedule-origin]').textContent = selected.dataset.origin;
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

/* ===== TÀI KHOẢN: KIỂM TRA FORM TRƯỚC KHI GỬI ===== */
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
