<div class="grid grid-cols-2 gap-3 text-sm">
    <div>
        <p class="text-xs text-gray-400 dark:text-gray-500">Title</p>
        <p class="font-medium text-gray-800 dark:text-white/90">{{ $purchaseRequest->title }}</p>
    </div>
    <div>
        <p class="text-xs text-gray-400 dark:text-gray-500">Amount</p>
        <p class="font-medium text-gray-800 dark:text-white/90">{{ number_format((float) $purchaseRequest->amount, 2) }}</p>
    </div>
    @if ($purchaseRequest->description)
        <div class="col-span-2">
            <p class="text-xs text-gray-400 dark:text-gray-500">Goods to Purchase</p>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $purchaseRequest->description }}</p>
        </div>
    @endif
    <div class="col-span-2">
        <p class="text-xs text-gray-400 dark:text-gray-500">Requested by</p>
        <p class="text-gray-700 dark:text-gray-300">{{ $purchaseRequest->requester->name }}</p>
    </div>
</div>
