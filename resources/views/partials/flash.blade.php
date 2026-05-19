@if (session('success'))
    <div class="mb-6 rounded-md bg-green-50 p-4">
        <p class="text-sm text-green-700">{{ session('success') }}</p>
    </div>
@endif

@if ($errors->any())
    <div class="mb-6 rounded-md bg-red-50 p-4">
        @foreach ($errors->all() as $error)
            <p class="text-sm text-red-700">{{ $error }}</p>
        @endforeach
    </div>
@endif
