<div class="grid grid-cols-2 gap-3 text-sm">
    <div>
        <p class="text-xs font-medium text-gray-400">PR Number</p>
        <p class="font-semibold text-gray-800 dark:text-white">{{ $purchaseRequest->pr_number }}</p>
    </div>
    <div>
        <p class="text-xs font-medium text-gray-400">Total Amount</p>
        <p class="font-semibold text-gray-800 dark:text-white">${{ number_format((float) $purchaseRequest->total_amount, 2) }}</p>
    </div>
    <div>
        <p class="text-xs font-medium text-gray-400">Title</p>
        <p class="text-gray-800 dark:text-gray-200">{{ $purchaseRequest->title }}</p>
    </div>
    <div>
        <p class="text-xs font-medium text-gray-400">Department</p>
        <p class="text-gray-800 dark:text-gray-200">{{ $purchaseRequest->department }}</p>
    </div>
    @if ($purchaseRequest->description)
        <div class="col-span-2">
            <p class="text-xs font-medium text-gray-400">Description</p>
            <p class="text-gray-700 dark:text-gray-300">{{ $purchaseRequest->description }}</p>
        </div>
    @endif
    <div class="col-span-2">
        <p class="text-xs font-medium text-gray-400">Requester</p>
        <p class="text-gray-800 dark:text-gray-200">{{ $purchaseRequest->requester?->name }}</p>
    </div>
</div>
