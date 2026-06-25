<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use App\Models\Supplier;

class OrderPrintService
{
    /**
     * Return an inline browser-viewable PDF response for the given order.
     */
    public function streamPdf(Order $order): Response
    {
        $order->loadMissing([
            'items.medicine',
            'supplier',
            'delivery.rider.user',
        ]);



        $pdf = Pdf::loadView('pdf.order-print', ['order' => $order])
            ->setPaper('a4', 'portrait');

        return $pdf->stream("order-{$order->id}.pdf");
    }

    /**
     * Return a downloadable PDF response for the given order.
     */
    public function downloadPdf(Order $order): Response
    {
        $order->loadMissing([
            'items.medicine',
            'supplier',
            'delivery.rider.user',
        ]);



        


        $pdf = Pdf::loadView('pdf.order-print', ['order' => $order])
            ->setPaper('a4', 'portrait');

        return $pdf->download("order-{$order->id}.pdf");
    }
}