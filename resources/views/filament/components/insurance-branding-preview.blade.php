<div class="border border-gray-300 rounded-lg overflow-hidden bg-white">
    <div class="p-4 bg-gradient-to-r" style="background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);">
        <div class="flex items-center justify-between">
            <div>
                @if($logoPath)
                    <div class="mb-2 p-2 bg-white/10 rounded inline-block">
                        <span class="text-white text-xs">Logo will appear here</span>
                    </div>
                @endif
                <h3 class="text-white font-bold text-lg">{{ $headerText }}</h3>
            </div>
            <div class="bg-white/95 rounded-lg px-4 py-2" style="color: {{ $primaryColor }};">
                <div class="text-xs opacity-70 uppercase">Claim Number</div>
                <div class="font-bold text-sm">CLM-2025-00001</div>
            </div>
        </div>
    </div>

    <div class="p-4 bg-gray-50">
        <div class="bg-white rounded-lg border" style="border-left: 4px solid {{ $primaryColor }};">
            <div class="p-3">
                <div class="text-xs font-semibold mb-2" style="color: {{ $primaryColor }};">
                    PATIENT INFORMATION
                </div>
                <div class="space-y-1 text-xs text-gray-600">
                    <div class="flex justify-between">
                        <span class="font-medium" style="color: {{ $secondaryColor }};">Patient Name:</span>
                        <span>John Doe</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium" style="color: {{ $secondaryColor }};">Policy Number:</span>
                        <span>POL-123456</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium" style="color: {{ $secondaryColor }};">Claimed Amount:</span>
                        <span class="font-bold">KES 5,000.00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="px-4 py-3 bg-gray-100 border-t-2" style="border-color: {{ $secondaryColor }};">
        <p class="text-xs text-gray-600 text-center">{{ $footerText }}</p>
        <p class="text-xs text-gray-500 text-center mt-1">PRIVATE AND CONFIDENTIAL</p>
    </div>
</div>

<div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
    <div class="flex items-start gap-2">
        <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div class="text-xs text-blue-700">
            <strong>Preview:</strong> This shows how your branding will appear on insurance claim forms. 
            The actual PDF will include all prescription details, medicines, and amounts.
        </div>
    </div>
</div>