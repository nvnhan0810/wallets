# Kỳ thanh toán khoản vay & Cron

## Tính năng

### Thanh toán trước (early)
- Thanh toán **trước ngày kỳ cố định** (`payment_day`, VD: 25) → ghi nhận ví, **không trừ gốc**.
- Trên lịch khoản vay (`/loans/{id}`): hiện dòng xanh **TT trước** giữa hai kỳ, kèm ghi chú *Gốc còn lại (kỳ trước)*.
- Dashboard **không nhắc** chi cố định / khoản vay nếu đã thanh toán trước cho kỳ sắp tới.

### Từ 06/2026
- Chỉ thanh toán **đúng kỳ** (`paid_at` ≥ ngày đến hạn kỳ) mới:
  - Đánh dấu kỳ **Đã trả** trên lịch
  - Cập nhật `months_paid` / trừ gốc theo lịch amortization

Cấu hình: `LOAN_PRINCIPAL_REDUCTION_CUTOFF=2026-06-01` trong `.env` (mặc định trong `config/loans.php`).

### Chi cố định
- Tạo vay ngân hàng + tick **Tạo chi cố định** → tự tạo `recurring_items` gắn `loan_id`.
- Ngày chi = `payment_day` trên khoản vay.

## Cron (production)

Thêm vào crontab user chạy web (mỗi phút gọi scheduler Laravel):

```cron
* * * * * cd /opt/apps/debt.nvnhan0810.com && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler chạy lệnh (mỗi ngày 06:00):

```bash
php artisan loans:sync-payment-periods
```

Chạy tay:

```bash
cd /opt/apps/debt.nvnhan0810.com
php artisan migrate
php artisan view:clear
php artisan loans:sync-payment-periods
```

## Migration

`2026_05_20_000001_loan_payment_periods.php`

- `loans.payment_day`, `loans.recurring_item_id`
- `recurring_items.loan_id`
- `payments.kind`, `period_due_date`, `schedule_month_index`, `reduces_principal`

## Files đã chỉnh

| File | Thay đổi |
|------|----------|
| `config/loans.php` | Ngày bắt đầu bắt buộc TT để trừ gốc |
| `app/Services/LoanPaymentScheduleService.php` | Kỳ, TT trước, timeline, sync recurring |
| `app/Services/LoanPaymentReminderService.php` | Nhắc vay + lọc đã TT trước |
| `app/Console/Commands/SyncLoanPaymentPeriodsCommand.php` | Cron sync |
| `routes/console.php` | `Schedule::command(...)` |
| `app/Http/Controllers/LoanController.php` | store/storePayment/show/index |
| `app/Http/Controllers/DashboardController.php` | Nhắc khoản vay |
| `app/Services/RecurringItemService.php` | Ẩn nhắc nếu đã TT trước |
| `app/Models/Loan.php`, `Payment.php`, `RecurringItem.php` | Fields & relations |
| `resources/views/loans/show.blade.php` | Timeline + lịch sử TT |
| `resources/views/loans/create.blade.php` | `payment_day`, link recurring |
| `resources/views/loans/index.blade.php` | Gợi ý TT trước |
| `resources/views/dashboard/index.blade.php` | Block nhắc khoản vay |
