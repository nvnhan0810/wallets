const DMY_RE = /^\d{1,2}\/\d{1,2}\/\d{4}$/;

export function formatDateVi(value: unknown): string {
    if (!value) {
        return '';
    }

    const d = value instanceof Date ? value : new Date(String(value));
    if (Number.isNaN(d.getTime())) {
        if (DMY_RE.test(String(value))) {
            return String(value);
        }
        return String(value);
    }

    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    return `${dd}/${mm}/${d.getFullYear()}`;
}

export function todayVi(): string {
    return formatDateVi(new Date());
}

export function greetingLabel(now: Date = new Date()): string {
    const h = now.getHours();
    if (h < 12) {
        return 'Chào buổi sáng';
    }
    if (h < 18) {
        return 'Chào buổi chiều';
    }
    return 'Chào buổi tối';
}
