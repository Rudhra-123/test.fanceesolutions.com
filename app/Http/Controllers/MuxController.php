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

    public function upload(Request $request): \Illuminate\Http\RedirectResponse
    {
        dd($request);
        $request->validate([
            'video' => 'required|mimes:mp4,mov,avi|max:2000000', // Adjust max size as needed
        ]);

        $videoPath = $request->file('video')->getPathName();

        // Upload the video to Mux
        $muxAssetId = $this->muxService->uploadVideo($videoPath);

        // Store the asset ID
        MuxUri::create([
            'asset_id' => $muxAssetId,
            'order_id' => $request->input('order_id'), // Save the order_id
        ]);

        return redirect()->back()->with('muxAssetId', $muxAssetId);
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
