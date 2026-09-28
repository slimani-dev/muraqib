<?php

namespace App\Http\Controllers;

use App\Services\MediaArrService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Temporary development page for previewing dashboard widgets in each mode
 * at real dashboard widths. Not registered in production.
 */
class WidgetPreviewController extends DashboardController
{
    public function __invoke(MediaArrService $mediaService): Response
    {
        return Inertia::render('dashboard/WidgetPreview', $this->dashboardProps($mediaService));
    }
}
