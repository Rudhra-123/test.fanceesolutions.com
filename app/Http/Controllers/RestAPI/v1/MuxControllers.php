<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\MuxService;
use Exception;

class MuxControllers extends Controller
{
    protected $muxService;

    public function __construct(MuxService $muxService)
    {
        $this->muxService = $muxService;
    }

    // API to handle video upload
    public function uploadVideoApi(Request $request)
    {
        // Validate video input
        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,avi|max:2048000', // Max size ~2GB
            'order_id' => 'required', // Ensure order ID is provided
        ]);

        try {
            // Generate direct upload URL
            $data = $this->muxService->createDirectUpload();
            $uploadUrl = $data['data']['url'];
            $uploadId = $data['data']['id'];

            // Open file stream and upload to Mux
            $file = $request->file('video');
            $fileStream = fopen($file->getRealPath(), 'r');
            if (!$fileStream) {
                throw new Exception("Failed to open file for reading.");
            }

            $ch = curl_init($uploadUrl);
            curl_setopt($ch, CURLOPT_PUT, true);
            curl_setopt($ch, CURLOPT_INFILE, $fileStream);
            curl_setopt($ch, CURLOPT_INFILESIZE, $file->getSize());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            fclose($fileStream);
            curl_close($ch);

            if ($response === false || $httpCode !== 200) {
                throw new Exception("Failed to upload video to Mux. HTTP Code: {$httpCode}");
            }

            // Fetch asset details using upload ID
            $assetDetails = $this->muxService->getAssetDetailsFromUploadId($uploadId);
            $playbackId = $assetDetails['playback_id'] ?? null;

            if (!$playbackId) {
                throw new Exception("Playback ID not found.");
            }

            // Save playback ID and order ID to the database
            DB::table('mux_videos')->insert([
                'uri' => $playbackId,
                'order_id' => $request->input('order_id'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Video uploaded successfully!',
                'playback_id' => $playbackId,
                'order_id' => $request->input('order_id'),
            ]);
        } catch (Exception $e) {
            \Log::error('Mux Upload Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload video: ' . $e->getMessage(),
            ], 500);
        }
    }

    // API to fetch video by order ID
    public function getVideoByOrderId($orderId)
    {
        try {
            $muxVideo = DB::table('mux_videos')->where('order_id', $orderId)->latest()->first();

            if (!$muxVideo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video not found!',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'playback_id' => $muxVideo->uri,
                'order_id' => $orderId,
            ]);
        } catch (Exception $e) {
            \Log::error('Error fetching video: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch video: ' . $e->getMessage(),
            ], 500);
        }
    }
}
