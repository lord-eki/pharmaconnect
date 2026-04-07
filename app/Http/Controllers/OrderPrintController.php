<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderPrintService;
use Illuminate\Http\Response;

/**.
 */
class OrderPrintController extends Controller
{
    public function __construct(
        private readonly OrderPrintService $printService
    ) {}

    /** Open the order PDF inline in the browser. */
    public function stream(Order $order): Response
    {
        $this->authorizeAccess($order);

        return $this->printService->streamPdf($order);
    }

    /** Force-download the order PDF. */
    public function download(Order $order): Response
    {
        $this->authorizeAccess($order);

        return $this->printService->downloadPdf($order);
    }

    /** Ensure the authenticated supplier owns this order. */
    private function authorizeAccess(Order $order): void
    {
        abort_unless(
            $order->supplier_id === auth()->user()->supplier?->id,
            403,
            'You do not have access to this order.'
        );
    }
}