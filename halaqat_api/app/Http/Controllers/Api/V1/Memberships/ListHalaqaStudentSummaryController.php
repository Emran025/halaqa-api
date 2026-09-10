<?php

namespace App\Http\Controllers\Api\V1\Memberships;

use App\Http\Controllers\Controller;
use App\Models\Halaqa;
use App\Services\Memberships\HalaqaStudentSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * GET /api/v1/halaqas/{halaqa}/students-summary
 *
 * يُرجع بيانات جميع الطلاب النشطين في الحلقة دفعةً واحدة:
 * - خطة المتابعة
 * - بنود المتابعة الأخيرة
 * - التتبع اليومي الأخير
 * - إحصاءات التقدم
 *
 * هذا يحل مشكلة N+1 التي كانت تُنتج N×4 طلبات HTTP منفصلة.
 */
class ListHalaqaStudentSummaryController extends Controller
{
    public function __invoke(Halaqa $halaqa, HalaqaStudentSummaryService $service): JsonResponse
    {
        Gate::authorize('manageMembers', $halaqa);

        $summary = $service->forHalaqa($halaqa);

        return response()->json([
            'data' => array_values($summary),
            'meta' => [
                'halaqa_id'     => (string) $halaqa->id,
                'student_count' => count($summary),
            ],
        ]);
    }
}
