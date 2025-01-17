<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MuxService;
use App\Contracts\Repositories\BusinessSettingRepositoryInterface;
use App\Models\MuxUri;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Brian2694\Toastr\Facades\Toastr;
use App\Utils\Helpers;
use Illuminate\Support\Facades\DB;


class MuxController extends Controller
{
    protected $muxService;
    private $businessSettingRepo;

    public function __construct(MuxService $muxService, BusinessSettingRepositoryInterface $businessSettingRepo)
    {
        $this->muxService = $muxService;
        $this->businessSettingRepo = $businessSettingRepo;
    }

    public function index(Request|null $request): \Illuminate\Contracts\View\View
    {
        return $this->getView();
    }



    public function upload(Request $request)
    {
        // Validate video file input
        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,avi|max:2048000',
        ]);
    
        // Get the uploaded file
        $file = $request->file('video');
    
        // Generate the direct upload URL from Mux
        $uploadUrl = $this->muxService->createDirectUpload();
        
    
        // Stream the file to Mux using the upload URL
        $fileStream = fopen($file->getRealPath(), 'r');
        $ch = curl_init($uploadUrl);
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_INFILE, $fileStream);
        curl_setopt($ch, CURLOPT_INFILESIZE, $file->getSize());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        $response = curl_exec($ch);
        fclose($fileStream);
        curl_close($ch);
    
        if ($response === false) {
            return back()->withErrors(['error' => 'Failed to upload video to Mux']);
        }
    
        // Extract unique upload_id from the URL
        $urlParts = parse_url($uploadUrl);
        parse_str($urlParts['query'], $queryParams);
        $uploadId = $queryParams['upload_id'] ?? null;
        
        if ($uploadId) {
            
            DB::table('mux_videos')->insert([
                'uri' => $uploadId,
                'order_id' => $request->input('order_id'), // Can be null
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    
            return back()->with('success', 'Video uploaded successfully!');
        } else {
            return back()->withErrors(['error' => 'Failed to retrieve upload ID']);
        }
    }
    
    
    

    public function getVideoInfo($id): JsonResponse
    {
        $video = MuxUri::find($id);

        if (!$video) {
            return response()->json([
                'success' => false,
                'message' => 'Video not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $video,
        ]);
    }

    public function getView(): \Illuminate\Contracts\View\View
    {
        $muxAccessToken = Helpers::get_business_settings('mux_access_token');
        $muxSecretKey = Helpers::get_business_settings('mux_secret_key');

        return view('admin.mux.settings', compact('muxAccessToken', 'muxSecretKey'));
    }

    public function update(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'mux_access_token' => 'required|string',
            'mux_secret_key' => 'required|string',
        ]);

        $envFilePath = base_path('.env');
        if (File::exists($envFilePath)) {
            File::put($envFilePath, str_replace(
                [
                    'MUX_ACCESS_TOKEN=' . env('MUX_ACCESS_TOKEN'),
                    'MUX_SECRET_KEY=' . env('MUX_SECRET_KEY'),
                ],
                [
                    'MUX_ACCESS_TOKEN=' . $request->input('mux_access_token'),
                    'MUX_SECRET_KEY=' . $request->input('mux_secret_key'),
                ],
                File::get($envFilePath)
            ));
        }

        // Clear config cache to reflect changes
        Artisan::call('config:cache');

        $this->businessSettingRepo->updateOrInsert(type: 'mux_access_token', value: $request['mux_access_token'] ?? '');
        $this->businessSettingRepo->updateOrInsert(type: 'mux_secret_key', value: $request['mux_secret_key'] ?? '');

        Toastr::success(translate('config_data_updated'));
        return back();
    }
}
