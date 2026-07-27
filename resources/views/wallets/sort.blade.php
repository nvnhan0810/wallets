@extends('layouts.app')

@section('title', 'Sắp xếp & Chọn ví hiển thị')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-content">Sắp xếp ví</h2>
            <p class="mt-1 text-sm text-content-muted">Chọn ví ghim lên Dashboard và thứ tự hiển thị</p>
        </div>
        <a href="{{ route('wallets.index') }}" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">Quay lại</a>
    </div>

    <form action="{{ route('wallets.update-sort') }}" method="POST"
          x-data="{
              wallets: {{ $wallets->map(fn($w) => ['id' => $w->id, 'name' => $w->name, 'is_pinned' => (bool)$w->is_pinned, 'typeLabel' => $w->typeLabel()])->toJson() }},
              moveUp(index) {
                  if (index > 0) {
                      const temp = this.wallets[index];
                      this.wallets[index] = this.wallets[index - 1];
                      this.wallets[index - 1] = temp;
                  }
              },
              moveDown(index) {
                  if (index < this.wallets.length - 1) {
                      const temp = this.wallets[index];
                      this.wallets[index] = this.wallets[index + 1];
                      this.wallets[index + 1] = temp;
                  }
              }
          }">
        @csrf
        <div class="bg-surface shadow rounded-lg border border-subtle divide-y divide-subtle mb-6">
            <template x-for="(wallet, index) in wallets" :key="wallet.id">
                <div class="px-4 py-3 flex items-center justify-between gap-4">
                    <input type="hidden" :name="`wallets[${index}][id]`" :value="wallet.id">
                    <input type="hidden" :name="`wallets[${index}][order]`" :value="index">
                    <input type="hidden" :name="`wallets[${index}][is_pinned]`" :value="wallet.is_pinned ? 1 : 0">

                    <div class="flex items-center gap-3">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" x-model="wallet.is_pinned" class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 w-5 h-5">
                        </label>
                        <div>
                            <p class="font-medium text-content" x-text="wallet.name"></p>
                            <p class="text-xs text-content-muted" x-text="wallet.typeLabel"></p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-1">
                        <button type="button" @click="moveUp(index)" :disabled="index === 0" class="p-1 text-gray-400 hover:text-content disabled:opacity-30 disabled:cursor-not-allowed">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                        </button>
                        <button type="button" @click="moveDown(index)" :disabled="index === wallets.length - 1" class="p-1 text-gray-400 hover:text-content disabled:opacity-30 disabled:cursor-not-allowed">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-4 py-2 rounded-md shadow-sm text-white bg-primary-600 dark:bg-primary-500 hover:bg-primary-700 font-medium text-sm">
                Lưu cài đặt
            </button>
        </div>
    </form>
</div>
@endsection
