<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\Branch;
use App\Models\Category;

class QrMenuController extends Controller
{
    public function index($tenantId, $branchId)
    {
        $branch = Branch::where('tenant_id', $tenantId)
            ->where('id', $branchId)
            ->where('is_active', true)
            ->firstOrFail();

        $categories = Category::where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('status', true)
            ->with(['products' => function($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                      ->where('status', true);
            }])
            ->get();
            
        return view('menu.index', compact('branch', 'categories'));
    }

    public function generate($branchId)
    {
        $branch = Branch::findOrFail($branchId);
        
        $url = route('menu.public', ['tenantId' => $branch->tenant_id, 'branchId' => $branch->id]);
        
        $qrCode = QrCode::size(300)->generate($url);
        
        return view('admin.qr.show', compact('branch', 'qrCode', 'url'));
    }
    
    public function download($branchId)
    {
        $branch = Branch::findOrFail($branchId);
        $url = route('menu.public', ['tenantId' => $branch->tenant_id, 'branchId' => $branch->id]);
        
        $headers = array(
            'Content-Type' => 'image/png',
        );

        $image = QrCode::format('png')->size(500)->generate($url);

        return response($image, 200, $headers);
    }
}
