<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Tài chính Cá nhân</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        [x-cloak] { display: none !important; }

        /* Custom Flatpickr styling to match Tailwind */
        .flatpickr-calendar {
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            border-radius: 0.5rem;
            border: 1px solid rgb(229 231 235);
        }

        .flatpickr-day.selected {
            background: rgb(79 70 229);
            border-color: rgb(79 70 229);
        }

        .flatpickr-day.today {
            border-color: rgb(79 70 229);
        }

        .flatpickr-day:hover {
            background: rgb(224 231 255);
        }
    </style>
</head>
<body class="bg-gray-50 text-slate-800 font-sans">
    <div class="min-h-screen flex flex-col">
        <!-- Navbar -->
        <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <a href="{{ route('dashboard') }}" class="text-2xl font-bold text-indigo-600 tracking-tight">
                                MyDebt
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('holidays.index') }}" class="text-gray-700 hover:text-indigo-600 text-sm font-medium">
                            Ngày Lễ
                        </a>
                        <a href="{{ route('loans.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Tạo Khoản Mới
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <main class="flex-1 py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                @yield('content')
            </div>
        </main>

        <footer class="bg-white border-t border-gray-200 mt-auto">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <p class="text-center text-sm text-gray-500">&copy; {{ date('Y') }} MyDebt Manager.</p>
            </div>
        </footer>
    </div>

    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize Flatpickr for all date inputs
        function initDatepickers() {
            flatpickr('.datepicker', {
                dateFormat: 'd/m/Y',
                altInput: false,
                allowInput: true,
                locale: {
                    firstDayOfWeek: 1,
                    weekdays: {
                        shorthand: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
                        longhand: ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy']
                    },
                    months: {
                        shorthand: ['Th1', 'Th2', 'Th3', 'Th4', 'Th5', 'Th6', 'Th7', 'Th8', 'Th9', 'Th10', 'Th11', 'Th12'],
                        longhand: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12']
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            initDatepickers();

            // Re-initialize when modals open (for Alpine.js modals)
            document.addEventListener('click', function() {
                setTimeout(initDatepickers, 100);
            });
        });
    </script>
</body>
</html>

