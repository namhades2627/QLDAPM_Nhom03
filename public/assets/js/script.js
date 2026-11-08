/* OnTrip — JavaScript chung, trang chủ, danh sách tour và chi tiết tour. */

const ONTRIP_DESTINATIONS = [
    { id: 1, name: 'Hà Nội', region: 'north', aliases: ['ha noi', 'hanoi'] },
    { id: 2, name: 'Hà Giang', region: 'north', aliases: ['ha giang', 'hagiang'] },
    { id: 3, name: 'Sa Pa', region: 'north', aliases: ['sa pa', 'sapa'] },
    { id: 4, name: 'Hạ Long', region: 'north', aliases: ['ha long', 'halong'] },
    { id: 5, name: 'Ninh Bình', region: 'north', aliases: ['ninh binh', 'ninhbinh'] },
    { id: 6, name: 'Cao Bằng', region: 'north', aliases: ['cao bang', 'caobang'] },
    { id: 7, name: 'Mộc Châu', region: 'north', aliases: ['moc chau', 'mocchau'] },
    { id: 8, name: 'Yên Bái', region: 'north', aliases: ['yen bai', 'yenbai'] },
    { id: 9, name: 'Đà Nẵng', region: 'central', aliases: ['da nang', 'danang'] },
    { id: 10, name: 'Huế', region: 'central', aliases: ['hue'] },
    { id: 11, name: 'Hội An', region: 'central', aliases: ['hoi an', 'hoian'] },
    { id: 12, name: 'Phan Thiết', region: 'central', aliases: ['phan thiet', 'phanthiet', 'mui ne', 'muine'] },
    { id: 13, name: 'Quy Nhơn', region: 'central', aliases: ['quy nhon', 'quynhon'] },
    { id: 14, name: 'Phú Yên', region: 'central', aliases: ['phu yen', 'phuyen'] },
    { id: 15, name: 'Nha Trang', region: 'central', aliases: ['nha trang', 'nhatrang'] },
    { id: 16, name: 'Đà Lạt', region: 'central', aliases: ['da lat', 'dalat'] },
    { id: 17, name: 'Phú Quốc', region: 'south', aliases: ['phu quoc', 'phuquoc'] },
    { id: 18, name: 'Côn Đảo', region: 'south', aliases: ['con dao', 'condao'] },
    { id: 19, name: 'Cần Thơ', region: 'south', aliases: ['can tho', 'cantho'] },
    { id: 20, name: 'An Giang', region: 'south', aliases: ['an giang', 'angiang'] },
    { id: 21, name: 'Đồng Nai', region: 'south', aliases: ['dong nai', 'dongnai'] },
    { id: 22, name: 'Kiên Giang', region: 'south', aliases: ['kien giang', 'kiengiang'] },
    { id: 23, name: 'Cà Mau', region: 'south', aliases: ['ca mau', 'camau'] },
    { id: 24, name: 'Tây Ninh', region: 'south', aliases: ['tay ninh', 'tayninh'] },
    { id: 25, name: 'Nhật Bản', region: 'asia', aliases: ['nhat ban', 'japan', 'nhat'] },
    { id: 26, name: 'Hàn Quốc', region: 'asia', aliases: ['han quoc', 'korea', 'han'] },
    { id: 27, name: 'Thái Lan', region: 'asia', aliases: ['thai lan', 'thailand', 'thai'] },
    { id: 28, name: 'Singapore', region: 'asia', aliases: ['singapore', 'sing'] },
    { id: 29, name: 'Malaysia', region: 'asia', aliases: ['malaysia', 'malay'] },
    { id: 30, name: 'Đài Loan', region: 'asia', aliases: ['dai loan', 'taiwan'] },
    { id: 31, name: 'Trung Quốc', region: 'asia', aliases: ['trung quoc', 'china'] },
    { id: 32, name: 'Indonesia', region: 'asia', aliases: ['indonesia', 'bali'] },
    { id: 33, name: 'Anh', region: 'europe', aliases: ['anh', 'nuoc anh', 'uk'] },
    { id: 34, name: 'Thụy Sĩ', region: 'europe', aliases: ['thuy si', 'thuy sy', 'switzerland'] },
    { id: 35, name: 'Ý', region: 'europe', aliases: ['y', 'nuoc y', 'italia', 'italy'] },
    { id: 36, name: 'Đức', region: 'europe', aliases: ['duc', 'nuoc duc', 'germany'] },
    { id: 37, name: 'Thổ Nhĩ Kỳ', region: 'europe', aliases: ['tho nhi ky', 'turkey'] },
    { id: 38, name: 'Hà Lan', region: 'europe', aliases: ['ha lan', 'netherlands'] },
    { id: 39, name: 'Pháp', region: 'europe', aliases: ['phap', 'nuoc phap', 'france'] },
    { id: 40, name: 'Nga', region: 'europe', aliases: ['nga', 'nuoc nga', 'russia'] },
    { id: 41, name: 'Mỹ', region: 'americas-oceania', aliases: ['my', 'hoa ky', 'usa'] },
    { id: 42, name: 'Úc', region: 'americas-oceania', aliases: ['uc', 'australia'] },
    { id: 43, name: 'Canada', region: 'americas-oceania', aliases: ['canada'] },
    { id: 44, name: 'Brazil', region: 'americas-oceania', aliases: ['brazil'] }
];

const ONTRIP_REGIONS = [
    { key: 'north', name: 'Miền Bắc', aliases: ['mien bac', 'bac'] },
    { key: 'central', name: 'Miền Trung', aliases: ['mien trung', 'trung'] },
    { key: 'south', name: 'Miền Nam', aliases: ['mien nam', 'nam'] },
    { key: 'asia', name: 'Châu Á', aliases: ['chau a'] },
    { key: 'europe', name: 'Châu Âu', aliases: ['chau au'] },
    { key: 'americas-oceania', name: 'Châu Mỹ – Châu Úc', aliases: ['chau my', 'chau uc', 'chau my chau uc'] }
];

function normalizeVietnameseSearch(text) {
    return String(text || '').toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[đĐ]/g, 'd')
        .replace(/[^a-z0-9\s]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

/**
 * Phân tích câu lệnh tìm kiếm ngôn ngữ tự nhiên thành JSON chuẩn và URLSearchParams.
 */
function parseNaturalTourSearch(query) {
    let norm = ' ' + normalizeVietnameseSearch(query) + ' ';
    const params = new URLSearchParams();
    const json = {
        diem_den: null,
        diem_khoi_hanh: null,
        ngan_sach_max: null,
        so_ngay: null,
        thang: null,
        mua_vu: null,
        ma_nhu_cau: []
    };

    // 1. Số ngày (so_ngay)
    const daysMatch = norm.match(/\s(\d{1,2})\s*(?:ngay|ng)(?:\s*(\d{1,2})\s*(?:dem|d))?\s/) 
                   || norm.match(/\s(\d{1,2})[nN](?:(\d{1,2})[dD])?\s/);
    if (daysMatch) {
        const d = parseInt(daysMatch[1], 10);
        json.so_ngay = d;
        params.set('days', String(d));
        norm = norm.replace(daysMatch[0], ' ');
    }

    // 2. Số người (adults & children)
    const adultsMatch = norm.match(/\s(?:nhom\s+)?(\d{1,2})\s*(?:nguoi|khach|ve|thanh vien)(?:\s+lon)?\s/);
    if (adultsMatch) {
        const adults = Math.max(1, parseInt(adultsMatch[1], 10));
        params.set('adults', String(adults));
        norm = norm.replace(adultsMatch[0], ' ');
    }
    const childrenMatch = norm.match(/\s(\d{1,2})\s*(?:tre em|em be|tre nho)\s/);
    if (childrenMatch) {
        const children = parseInt(childrenMatch[1], 10);
        params.set('children', String(children));
        norm = norm.replace(childrenMatch[0], ' ');
    } else if (adultsMatch) {
        params.set('children', '0');
    }

    // 3. Ngân sách tối đa (ngan_sach_max)
    const budgetMatch = norm.match(/\s(?:duoi|toi da|ngan sach|tam|khoang|gia duoi|<|<=)\s*(\d+(?:[.,]\d+)?)\s*(trieu|tr|m|cu|k|nghin|ngan|dong|vnd|d)?\s/)
                     || norm.match(/\s(\d+(?:[.,]\d+)?)\s*(trieu|tr|m|cu)\s/);
    if (budgetMatch) {
        let amount = parseFloat(budgetMatch[1].replace(',', '.'));
        const unit = budgetMatch[2];
        if (['trieu', 'tr', 'm'].includes(unit)) amount *= 1000000;
        else if (unit === 'cu') amount *= 1000000000;
        else if (['k', 'nghin', 'ngan'].includes(unit)) amount *= 1000;
        else if (!unit && amount < 1000) amount *= 1000000;
        const finalBudget = Math.round(amount);
        json.ngan_sach_max = finalBudget;
        params.set('budget', String(finalBudget));
        norm = norm.replace(budgetMatch[0], ' ');
    }

    // 4. Điểm khởi hành (diem_khoi_hanh)
    const originMatch = norm.match(/\s(?:khoi hanh|xuat phat|di tu|tu)\s+(ha noi|tp\s*ho\s*chi\s*minh|tphcm|tp\s*hcm|sai\s*gon|da\s*nang|can\s*tho)\s/)
                     || norm.match(/\s(ha noi|tp\s*ho\s*chi\s*minh|tphcm|sai gon)\s/);
    if (originMatch) {
        const o = originMatch[1].trim();
        let detectedOrigin = null;
        if (['ha noi'].includes(o)) detectedOrigin = 'Hà Nội';
        else if (['tp ho chi minh', 'tphcm', 'tp hcm', 'sai gon'].includes(o)) detectedOrigin = 'TP. Hồ Chí Minh';
        else if (['da nang'].includes(o)) detectedOrigin = 'Đà Nẵng';
        else if (['can tho'].includes(o)) detectedOrigin = 'Cần Thơ';
        if (detectedOrigin) {
            json.diem_khoi_hanh = detectedOrigin;
            params.set('origin', detectedOrigin);
            norm = norm.replace(originMatch[0], ' ');
        }
    }

    // 5. Tháng khởi hành (thang: 1-12)
    const monthMatch = norm.match(/\s(?:thang|t)\s*(\d{1,2})\s/);
    if (monthMatch) {
        const m = parseInt(monthMatch[1], 10);
        if (m >= 1 && m <= 12) {
            json.thang = m;
            params.set('month', String(m));
            norm = norm.replace(monthMatch[0], ' ');
        }
    }

    // 6. Mùa vụ (mua_vu: xuan | ha | thu | dong)
    if (/\s(?:mua\s+xuan|xuan|tet)\s/.test(norm)) {
        json.mua_vu = 'xuan';
        params.set('season', 'xuan');
        norm = norm.replace(/\s(?:mua\s+xuan|xuan|tet)\s/, ' ');
    } else if (/\s(?:mua\s+he|mua\s+ha|he|ha)\s/.test(norm)) {
        json.mua_vu = 'ha';
        params.set('season', 'ha');
        norm = norm.replace(/\s(?:mua\s+he|mua\s+ha|he|ha)\s/, ' ');
    } else if (/\s(?:mua\s+thu|thu)\s/.test(norm)) {
        json.mua_vu = 'thu';
        params.set('season', 'thu');
        norm = norm.replace(/\s(?:mua\s+thu|thu)\s/, ' ');
    } else if (/\s(?:mua\s+dong|dong)\s/.test(norm)) {
        json.mua_vu = 'dong';
        params.set('season', 'dong');
        norm = norm.replace(/\s(?:mua\s+dong|dong)\s/, ' ');
    }

    // 7. Nhu cầu (ma_nhu_cau)
    const needRules = [
        { code: 'relaxation', regex: /\s(bien|bai bien|bo bien|tam bien|ngam bien|resort|nghi duong|spa|thu gian|xa stress|nghi ngoi)\s/g },
        { code: 'nature', regex: /\s(thien nhien|ngam canh|phong canh|nui rung|nui|thac|vinh|hang dong|song nuoc|sinh thai|san may|rung)\s/g },
        { code: 'culture', regex: /\s(van hoa|lich su|den|chua|mieu|lang tam|pho co|di tich|di san|tam linh|le hoi|bao tang)\s/g },
        { code: 'food', regex: /\s(am thuc|mon ngon|an uong|dac san|food tour|an vat|buffet|thuong thuc)\s/g },
        { code: 'entertainment', regex: /\s(vui choi|giai tri|cong vien|theme park|bar|pub|vinwonders|sunworld|pho di bo|cho dem|mua sam|shopping)\s/g },
        { code: 'adventure', regex: /\s(van dong|leo nui|trekking|mao hiem|tham hiem|phuot|lan bien|lan ngam san ho|cheo sup|kayak|cam trai|camping)\s/g }
    ];
    for (const rule of needRules) {
        if (rule.regex.test(norm)) {
            if (!json.ma_nhu_cau.includes(rule.code)) {
                json.ma_nhu_cau.push(rule.code);
                params.append('needs[]', rule.code);
            }
            norm = norm.replace(rule.regex, ' ');
        }
    }

    // 8. Điểm đến & Khu vực (diem_den)
    const foundDestinations = [];
    for (const dest of ONTRIP_DESTINATIONS) {
        for (const alias of dest.aliases) {
            const aliasRegex = new RegExp(`\\s${alias}\\s`, 'g');
            if (aliasRegex.test(norm)) {
                foundDestinations.push(dest.name);
                params.set('destination', String(dest.id));
                if (!params.has('region')) params.set('region', dest.region);
                norm = norm.replace(aliasRegex, ' ');
                break;
            }
        }
    }
    for (const reg of ONTRIP_REGIONS) {
        for (const alias of reg.aliases) {
            const aliasRegex = new RegExp(`\\s${alias}\\s`, 'g');
            if (aliasRegex.test(norm)) {
                foundDestinations.push(reg.name);
                if (!params.has('region')) params.set('region', reg.key);
                norm = norm.replace(aliasRegex, ' ');
                break;
            }
        }
    }
    if (foundDestinations.length > 0) {
        json.diem_den = [...new Set(foundDestinations)];
    }

    // 9. Lọc bỏ các từ dừng (stop-words) tiếng Việt
    const stopWords = [
        'di', 'cho', 'tour', 'tim', 'du lich', 'chuyen di', 'khoang', 'tam', 
        'muon', 'can', 'duoi', 'tren', 'gia', 'nguoi', 'khach', 'ngay', 'dem', 
        've', 'voi', 'o', 'tai', 'nao', 'gi', 'het', 'toi', 'den', 'trong', 'ngoai',
        'kham pha', 'trai nghiem', 'cho toi', 'muon di'
    ];
    for (const sw of stopWords) {
        norm = norm.replace(new RegExp(`\\s${sw}\\s`, 'g'), ' ');
    }
    const cleanKeyword = norm.replace(/\s+/g, ' ').trim();
    if (cleanKeyword) {
        params.set('q', cleanKeyword);
    }

    params._json = json;
    return params;
}

function createTourSearchUrl(query, form = null) {
    const rawQuery = String(query || '').trim();
    const parsed = parseNaturalTourSearch(rawQuery);
    const params = new URLSearchParams();
    params.set('page', 'tours');
    params.set('type', 'all');

    if (rawQuery) {
        params.set('raw_q', rawQuery);
    }

    for (const [name, value] of parsed) {
        if (name !== '_json') params.set(name, value);
    }

    const parsedNeeds = parsed.getAll('needs[]');
    if (parsedNeeds.length > 0) {
        params.delete('needs[]');
        for (const need of parsedNeeds) params.append('needs[]', need);
    }

    const hasNaturalIntent = parsed.has('days') || parsed.has('budget') || parsed.has('adults') || 
                             parsedNeeds.length > 0 || parsed.has('destination') || parsed.has('region') || 
                             parsed.has('origin') || parsed.has('month') || parsed.has('season');

    if (form && !hasNaturalIntent) {
        for (const [name, value] of new FormData(form)) {
            if (value && !params.has(name) && name !== 'q') {
                params.append(name, value);
            }
        }
    }

    if (!parsed.has('q')) params.delete('q');
    return new URL(`index.php?${params.toString()}`, window.location.href);
}

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
        event.preventDefault();
        window.location.assign(createTourSearchUrl(searchInput.value).href);
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

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.tour-search');
    if (!form) return;
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        window.location.assign(createTourSearchUrl(form.elements.q.value, form).href);
    });
});

/* Hiệu ứng trang chủ; gửi tìm kiếm được xử lý riêng ở trên. */
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
        const month = Number(form.elements.month?.value || 0);
        const season = String(form.elements.season?.value || '').trim();
        const hasCustomGroup = budget > 0 || adults > 1 || children > 0;
        let activeKeyword = keywordInput.value.trim();
        // Nếu ô tìm kiếm chứa câu tự nhiên, bóc tách để chỉ lấy phần từ khóa chưa nhận diện (nếu có)
        const parsedNatural = parseNaturalTourSearch(activeKeyword);
        const naturalRemaining = parsedNatural.get('q') || '';
        if (keywordInput.dataset.displayQuery && activeKeyword === keywordInput.dataset.displayQuery.trim()) {
            activeKeyword = keywordInput.dataset.realKeyword !== undefined ? keywordInput.dataset.realKeyword : naturalRemaining;
        } else {
            activeKeyword = naturalRemaining;
        }
        return {
            region: form.elements.region.value,
            destination: Number(form.elements.destination.value),
            days: Number(form.elements.days?.value || 0),
            budget: budget,
            month: month,
            season: season,
            adults: adults,
            children: children,
            groupSearch: hasCustomGroup || Boolean(form.elements.adults && !form.elements.adults.disabled),
            guests: adults + children,
            keyword: normalize(activeKeyword),
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
        if (criteria.days && Number(data.days) !== criteria.days) return false;
        if (criteria.season && data.season && data.season !== criteria.season) return false;
        if (criteria.keyword && !normalize(`${data.name} ${data.destination}`).includes(criteria.keyword)) return false;

        const rate = criteria.guests >= 10 ? 8 : (criteria.guests >= 5 ? 5 : 0);
        const cardSchedules = schedulesByCard.get(card) || [];
        cardSchedules.forEach(schedule => {
            const subtotal = criteria.adults * Number(schedule.adult_price || 0) + criteria.children * Number(schedule.child_price || 0);
            schedule.group_total = subtotal - Math.round(subtotal * rate / 100);
        });

        // Ngày, nơi khởi hành và mức giá phải cùng thuộc một lịch phù hợp.
        const schedules = cardSchedules.filter(schedule => {
            if (criteria.groupSearch && criteria.guests > Number(schedule.capacity)) return false;
            if (criteria.origin && schedule.origin !== criteria.origin) return false;
            if (criteria.date && schedule.date !== criteria.date) return false;
            if (criteria.month && (new Date(schedule.date).getMonth() + 1) !== criteria.month) return false;
            const comparePrice = criteria.groupSearch && typeof schedule.group_total === 'number' ? schedule.group_total : schedule.adult_price;
            if (criteria.budget > 0 && comparePrice > criteria.budget) return false;
            if (criteria.price) {
                const [minimum, maximum] = criteria.price.split('-');
                if (schedule.adult_price < Number(minimum) || (maximum && schedule.adult_price >= Number(maximum))) return false;
            }
            return true;
        });
        if ((criteria.destination || criteria.groupSearch || criteria.origin || criteria.date || criteria.price || criteria.budget > 0 || criteria.month > 0) && schedules.length === 0) return false;
        // Chọn nhiều nhu cầu: tour phù hợp ít nhất một mục được chọn.
        if (criteria.needs.length && !criteria.needs.some((need) => data.needs.split(',').includes(need))) return false;

        const bestSchedule = schedules.length ? schedules[0] : null;
        data.sortPrice = bestSchedule ? String(bestSchedule.adult_price) : '';
        const priceElement = card.querySelector('[data-card-price]');
        const priceLabel = card.querySelector('.catalog-card-bottom span, .card-price-label');

        if (priceElement && data.sortPrice !== '') {
            const amount = document.createElement('strong');
            amount.textContent = new Intl.NumberFormat('vi-VN').format(Number(bestSchedule.adult_price));
            const currency = document.createElement('span');
            currency.textContent = ' ₫';
            priceElement.replaceChildren(amount, currency);
            if (priceLabel) {
                priceLabel.textContent = 'Giá từ / người lớn';
            }
        } else if (priceElement) {
            priceElement.textContent = 'Đang cập nhật';
        }
        const origins = [...new Set(schedules.map(schedule => schedule.origin))];
        card.querySelector('[data-card-origin]').textContent = origins.length ? `Khởi hành từ ${origins.join(', ')}` : 'Chưa có lịch khởi hành';

        const scheduleDates = [...new Set(schedules.map(s => s.date))];
        const scheduleContainer = card.querySelector('[data-card-schedules]');
        if (scheduleContainer) {
            if (scheduleDates.length > 0) {
                const firstDate = scheduleDates[0];
                const [y, m, d] = firstDate.split('-');
                const formattedFirst = `${d}/${m}/${y}`;
                let badgesHtml = '';
                if (scheduleDates.length > 1) {
                    const others = scheduleDates.slice(1, 3);
                    const pills = others.map(od => {
                        const [, om, odD] = od.split('-');
                        return `<span class="date-pill">${odD}/${om}</span>`;
                    }).join(' ');
                    const more = scheduleDates.length > 3 ? `<span class="date-pill-more">+${scheduleDates.length - 3}</span>` : '';
                    badgesHtml = `<span class="schedule-badges">${pills} ${more}</span>`;
                }
                scheduleContainer.innerHTML = `<span class="schedule-intro">Khởi hành gần nhất:</span> <strong>${formattedFirst}</strong> ${badgesHtml}`;
            } else {
                scheduleContainer.innerHTML = '<span class="schedule-empty">Đang cập nhật lịch</span>';
            }
        }
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
            } else if (sortInput.value === 'featured') {
                difference = Number(b.dataset.featured || 0) - Number(a.dataset.featured || 0);
            } else if (sortInput.value === 'seasonal') {
                const aMatch = Number(a.dataset.seasonMatch || 0) * 2 + (a.dataset.season ? 1 : 0);
                const bMatch = Number(b.dataset.seasonMatch || 0) * 2 + (b.dataset.season ? 1 : 0);
                difference = bMatch - aMatch;
            } else if (sortInput.value === 'promo') {
                difference = Number(b.dataset.discount || 0) - Number(a.dataset.discount || 0);
            } else if (sortInput.value.startsWith('price-')) {
                if (a.dataset.sortPrice === '' && b.dataset.sortPrice !== '') return 1;
                if (b.dataset.sortPrice === '' && a.dataset.sortPrice !== '') return -1;
                difference = sortInput.value === 'price-asc'
                    ? Number(a.dataset.sortPrice) - Number(b.dataset.sortPrice)
                    : Number(b.dataset.sortPrice) - Number(a.dataset.sortPrice);
            } else if (sortInput.value === 'duration') {
                difference = Number(a.dataset.days) - Number(b.dataset.days);
            }
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
        const summaryElement = document.querySelector('.catalog-search-summary');
        if (summaryElement) {
            summaryElement.textContent = summary.join(' · ');
            summaryElement.hidden = summary.length === 0 || filtered.length === 0;
        }

        const smartBadge = document.querySelector('#smart-search-badge');
        const smartBadgeText = document.querySelector('#smart-badge-text');
        if (smartBadge && smartBadgeText) {
            if (filtered.length === 0) {
                smartBadge.hidden = false;
                smartBadge.classList.add('is-warning');
                smartBadgeText.textContent = '⚠️ Không tìm thấy tour phù hợp. Vui lòng kiểm tra lại.';
            } else {
                smartBadge.classList.remove('is-warning');
                if (summary.length > 0) {
                    smartBadge.hidden = false;
                    smartBadgeText.textContent = summary.join(' · ');
                }
            }
        }
    }

    function searchUrl(type = root.dataset.catalogType) {
        const url = new URL(window.location.href);
        const existingRawQ = url.searchParams.get('raw_q') || '';
        url.search = '';
        url.searchParams.set('page', 'tours');
        url.searchParams.set('type', type);
        for (const [name, value] of new FormData(form)) {
            if (value === '' || ((name === 'destination' || name === 'budget') && Number(value) === 0)) continue;
            url.searchParams.append(name, value);
        }
        if (sortInput && sortInput.value && sortInput.value !== 'default') {
            url.searchParams.set('sort', sortInput.value);
        }
        if (keywordInput.value.trim()) {
            url.searchParams.set('raw_q', keywordInput.value.trim());
        } else if (existingRawQ) {
            url.searchParams.set('raw_q', existingRawQ);
        }
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
    const highlightBackdrop = keywordInput.parentElement?.querySelector('.search-highlight-backdrop');
    function syncHighlight() {
        if (!highlightBackdrop) return;
        const unrec = keywordInput.dataset.unrecognized;
        const val = keywordInput.value;
        if (!unrec || !val.toLowerCase().includes(unrec.toLowerCase())) {
            highlightBackdrop.innerHTML = '';
            return;
        }
        const escapedVal = val.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        const escapedWord = unrec.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        highlightBackdrop.innerHTML = escapedVal.replace(new RegExp(`(${escapedWord})`, 'gi'), '<span class="unrecognized-word">$1</span>');
        highlightBackdrop.scrollLeft = keywordInput.scrollLeft;
    }
    keywordInput.addEventListener('input', syncHighlight);
    keywordInput.addEventListener('scroll', () => {
        if (highlightBackdrop) highlightBackdrop.scrollLeft = keywordInput.scrollLeft;
    });

    document.querySelector('.catalog-quick-search').addEventListener('submit', (event) => {
        event.preventDefault();
        window.location.assign(createTourSearchUrl(keywordInput.value, form).href);
    });
    keywordInput.addEventListener('search', (event) => {
        if (keywordInput.value === '') applyFilters(event);
    });
    sortInput.addEventListener('change', () => {
        currentPage = 1;
        const currentUrl = new URL(window.location.href);
        if (sortInput.value && sortInput.value !== 'default') {
            currentUrl.searchParams.set('sort', sortInput.value);
        } else {
            currentUrl.searchParams.delete('sort');
        }
        window.history.replaceState(null, '', currentUrl.toString());
        renderResults();
    });
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
        keywordInput.dataset.displayQuery = '';
        keywordInput.dataset.realKeyword = '';
        keywordInput.dataset.unrecognized = '';
        if (highlightBackdrop) highlightBackdrop.innerHTML = '';
        sortInput.value = 'default';
        form.elements.region.value = '';
        form.elements.destination.value = '0';
        form.elements.origin.value = '';
        form.elements.date.value = '';
        if (form.elements.days) form.elements.days.value = '0';
        if (form.elements.month) form.elements.month.value = '0';
        if (form.elements.season) form.elements.season.value = '';
        form.elements.price.value = '';
        needsInputs.forEach(input => { input.checked = false; });
        if (form.elements.adults) form.elements.adults.value = '1';
        if (form.elements.children) form.elements.children.value = '0';
        if (form.elements.budget) form.elements.budget.value = '';
        const smartBadge = document.querySelector('#smart-search-badge');
        if (smartBadge) smartBadge.hidden = true;
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
            const tourType = document.body.dataset.tourType || '';
            if (!tourId) return;

            const weatherOverview = document.querySelector('[data-weather-overview]');
            if (weatherOverview) {
                const originEl = weatherOverview.querySelector('[data-weather-origin]');
                if (originEl && origin) originEl.textContent = origin;
                const sourceText = weatherOverview.querySelector('[data-weather-source-text]');
                if (sourceText) sourceText.textContent = 'Đang đồng bộ API thời tiết...';
            }

            document.querySelectorAll('[data-weather-card]').forEach(card => card.classList.add('is-loading'));

            fetch(`index.php?page=tour-detail&action=weather&id=${encodeURIComponent(tourId)}&type=${encodeURIComponent(tourType)}&date=${encodeURIComponent(startDate)}`)
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
                                if (dateEl) dateEl.textContent = w.date_formatted;
                                if (locEl) locEl.textContent = w.location;
                                if (rainEl) rainEl.textContent = `${w.rain}%`;
                                if (statusEl) statusEl.textContent = w.status;

                                card.classList.add('weather-card-updated');
                                setTimeout(() => card.classList.remove('weather-card-updated'), 500);
                            }
                        });

                        if (data.general_advice) {
                            const summaryEl = document.querySelector('[data-general-weather-summary]');
                            const clothingEl = document.querySelector('[data-prep-clothing]');
                            const itemsEl = document.querySelector('[data-prep-items]');
                            if (summaryEl && data.general_advice.weather_summary) {
                                summaryEl.textContent = data.general_advice.weather_summary;
                            }
                            if (clothingEl && data.general_advice.clothing) {
                                clothingEl.textContent = data.general_advice.clothing;
                            }
                            if (itemsEl && data.general_advice.personal_items) {
                                itemsEl.textContent = data.general_advice.personal_items;
                            }
                        }
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
    const input = document.querySelector('[data-managed-avatar-input]');
    if (!input) return;
    const form = input.closest('form');
    const preview = form.querySelector('[data-managed-avatar-preview]');
    const fallback = form.querySelector('[data-managed-avatar-default]');
    const error = form.querySelector('#managed-avatar-error');
    const savedUrl = preview.getAttribute('src') || '';
    let objectUrl = '';
    const reset = () => {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = '';
        preview.hidden = !savedUrl;
        fallback.toggleAttribute('hidden', !!savedUrl);
        if (savedUrl) preview.src = savedUrl;
        else preview.removeAttribute('src');
    };
    const reject = text => { reset(); input.value = ''; error.textContent = text; error.hidden = false; };
    input.addEventListener('change', () => {
        error.hidden = true;
        reset();
        const file = input.files[0];
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)) { reject('Vui lòng chọn ảnh JPG, PNG, GIF hoặc WEBP.'); return; }
        if (file.size > 5 * 1024 * 1024) { reject('Ảnh đại diện không vượt quá 5 MB.'); return; }
        const url = URL.createObjectURL(file);
        objectUrl = url;
        preview.onload = () => { if (objectUrl === url) { preview.hidden = false; fallback.setAttribute('hidden', ''); } };
        preview.onerror = () => { if (objectUrl === url) reject('Không đọc được ảnh này. Vui lòng chọn ảnh khác.'); };
        preview.src = url;
    });
    window.addEventListener('pagehide', reset);
});

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

// Native details giu chuong thong bao hoat dong khi khong co JavaScript.
document.addEventListener('DOMContentLoaded', () => {
    const notifications = document.querySelector('.customer-notifications');
    if (!notifications) return;
    document.addEventListener('click', event => {
        if (!notifications.contains(event.target)) notifications.open = false;
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && notifications.open) {
            notifications.open = false;
            notifications.querySelector('summary').focus();
        }
    });
});
