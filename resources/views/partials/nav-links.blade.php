<a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-md {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-indigo-600' }}">Tổng quan</a>
<a href="{{ route('wallets.index') }}" class="px-3 py-2 rounded-md {{ request()->routeIs('wallets.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-indigo-600' }}">Ví</a>
<a href="{{ route('transactions.index') }}" class="px-3 py-2 rounded-md {{ request()->routeIs('transactions.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-indigo-600' }}">Giao dịch</a>
<a href="{{ route('recurring-items.index') }}" class="px-3 py-2 rounded-md {{ request()->routeIs('recurring-items.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-indigo-600' }}">Thu chi cố định</a>
<a href="{{ route('loans.index') }}" class="px-3 py-2 rounded-md {{ request()->routeIs('loans.*', 'payments.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-indigo-600' }}">Khoản vay</a>
<a href="{{ route('transaction-templates.index') }}" class="px-3 py-2 rounded-md {{ request()->routeIs('transaction-templates.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-indigo-600' }}">Mẫu GD</a>
<a href="{{ route('holidays.index') }}" class="px-3 py-2 rounded-md {{ request()->routeIs('holidays.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-indigo-600' }}">Ngày lễ</a>
<a href="{{ route('settings.index') }}" class="px-3 py-2 rounded-md {{ request()->routeIs('settings.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-indigo-600' }}">Cài đặt</a>
