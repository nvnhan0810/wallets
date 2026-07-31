export function formatMoney(value, withSuffix = true) {
    const n = Math.round(Number(value) || 0);
    const formatted = new Intl.NumberFormat('vi-VN').format(n);
    return withSuffix ? `${formatted} ₫` : formatted;
}

export function parseMoney(value) {
    const digits = String(value ?? '').replace(/\D/g, '');
    return digits ? parseInt(digits, 10) : 0;
}

export function formatDateVi(value) {
    if (!value) return '';
    const d = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(d.getTime())) {
        // already d/m/Y
        if (/^\d{1,2}\/\d{1,2}\/\d{4}$/.test(String(value))) return String(value);
        return String(value);
    }
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    return `${dd}/${mm}/${d.getFullYear()}`;
}

export function todayVi() {
    return formatDateVi(new Date());
}

export function greetingLabel() {
    const h = new Date().getHours();
    if (h < 12) return 'Chào buổi sáng';
    if (h < 18) return 'Chào buổi chiều';
    return 'Chào buổi tối';
}

export function toggleTheme() {
    const root = document.documentElement;
    if (root.classList.contains('dark')) {
        root.classList.remove('dark');
        localStorage.setItem('color-theme', 'light');
    } else {
        root.classList.add('dark');
        localStorage.setItem('color-theme', 'dark');
    }
}

export const WALLET_TYPES = {
    cash: 'Tiền mặt',
    bank: 'Tài khoản ngân hàng',
    credit_card: 'Thẻ tín dụng',
    e_wallet: 'Ví điện tử',
};

export const TX_TYPES = {
    income: 'Thu',
    expense: 'Chi',
    adjustment: 'Cân đối',
    transfer: 'Chuyển ví',
};
